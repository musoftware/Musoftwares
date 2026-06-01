<?php

namespace Modules\Marketplace\Http\Controllers\Seller;

use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\Marketplace\Models\ServiceLandingQuestion;
use Modules\Marketplace\Models\ServiceLandingFaq;
use Modules\Marketplace\Models\ServiceLandingPricingTable;
use Modules\Marketplace\Models\ServiceLandingFormSubmission;
use Modules\Marketplace\Models\ServiceLandingPageCtaVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class ServiceLandingPageSubmissionController extends Controller
{


    public function submitForm(Request $request, $slug)
    {
        // Find the parent landing page (variants share the same slug)
        $landingPage = ServiceLandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->whereNull('parent_variant_id') // Get the parent, not a variant
            ->with(['questions', 'service.user'])
            ->firstOrFail();

        $formData = [];
        $validationRules = [];

        foreach ($landingPage->questions as $question) {
            $fieldName = 'question_' . $question->id;
            if ($question->is_required) {
                $validationRules[$fieldName] = 'required';
            }
        }

        $request->validate($validationRules);

        foreach ($landingPage->questions as $question) {
            $fieldName = 'question_' . $question->id;
            $formData[$question->question_text] = $request->input($fieldName);
        }

        $leadConfig = $landingPage->lead_routing_config ?? [];
        $saveToDb = $leadConfig['save_to_db'] ?? true;
        
        $submission = null;
        if ($saveToDb) {
            $submission = ServiceLandingFormSubmission::create([
                'landing_page_id' => $landingPage->id,
                'form_data' => $formData,
                'submitted_by_name' => $request->input('name'),
                'submitted_by_email' => $request->input('email'),
                'submitted_by_phone' => $request->input('phone'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Send notification to service owner (Email)
        $emailEnabled = $leadConfig['email_notification'] ?? true;
        
        if ($emailEnabled) {
            $service = $landingPage->service;
            if ($service && $service->user) {
                $serviceOwner = $service->user;
                // If saved to DB, link to submission, otherwise link to leads page
                $viewUrl = ($submission) 
                    ? route('services.landing-page.submissions', $service) . '#submission-' . $submission->id
                    : route('services.landing-page.submissions', $service);
                
                // Determine additional recipients (External emails)
                $recipients = [];
                if (!empty($leadConfig['notification_email'])) {
                    $emails = array_map('trim', explode(',', $leadConfig['notification_email']));
                    foreach ($emails as $email) {
                        // We exclude the owner here because we notify them explicitly below
                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== $serviceOwner->email) {
                            $recipients[] = $email;
                        }
                    }
                }
                
                // Prepare Notification
                $notification = new \App\Notifications\NewFormSubmissionNotification(
                    $submission ?? (object)[
                        'form_data' => $formData, 
                        'submitted_by_name' => $request->input('name'),
                        'submitted_by_email' => $request->input('email'),
                        'landing_page_id' => $landingPage->id,
                        'created_at' => now()
                    ], 
                    $landingPage, 
                    $viewUrl
                );

                // 1. Always Notify Owner (triggers database, fcm, mail based on Notification class)
                try {
                    $serviceOwner->notify($notification);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to notify owner ' . $serviceOwner->email . ': ' . $e->getMessage());
                }

                // 2. Send to additional recipients (Mail only)
                foreach ($recipients as $recipientEmail) {
                    try {
                        \Illuminate\Support\Facades\Notification::route('mail', $recipientEmail)
                            ->notify($notification);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send lead notification to ' . $recipientEmail . ': ' . $e->getMessage());
                    }
                }
            }
        }

        // Webhook Integration
        $webhookEnabled = $leadConfig['webhook_enabled'] ?? false;
        $webhookUrl = $leadConfig['webhook_url'] ?? null;

        if ($webhookEnabled && !empty($webhookUrl) && filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
             try {
                \Illuminate\Support\Facades\Http::post($webhookUrl, [
                    'event' => 'form_submission',
                    'landing_page_id' => $landingPage->id,
                    'landing_page_title' => $landingPage->hero_title,
                    'submitted_at' => now()->toIso8601String(),
                    'data' => [
                        'name' => $request->input('name'),
                        'email' => $request->input('email'),
                        'phone' => $request->input('phone'),
                        'fields' => $formData,
                    ]
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Webhook failed for landing page ' . $landingPage->id . ': ' . $e->getMessage());
            }
        }

        // WhatsApp Redirection
        $whatsappEnabled = $leadConfig['whatsapp_enabled'] ?? false;
        $whatsappNumber = $leadConfig['whatsapp_number'] ?? '';

        if ($whatsappEnabled && !empty($whatsappNumber)) {
            // Clean number (remove spaces, dashes, plus)
            $cleanNumber = preg_replace('/[^0-9]/', '', $whatsappNumber);
            
            // Format message
            $message = "New Inquiry : " . $landingPage->hero_title . "\n";
            $message .= "Name: " . $request->input('name') . "\n";
            $message .= "Email: " . $request->input('email') . "\n";
            if ($request->filled('phone')) {
                $message .= "Phone: " . $request->input('phone') . "\n";
            }
            // Add custom fields summary
            $message .= "\nDetails:\n";
            foreach ($formData as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $message .= "$key: $value\n";
            }
            
            $whatsappUrl = "https://wa.me/{$cleanNumber}?text=" . urlencode($message);
            
            // Redirect to WhatsApp
            // We use standard redirect. The frontend might need to handle this if it expects to stay on page,
            // but for "Redirect to WhatsApp", typically the user is taken there.
            return redirect($whatsappUrl);
        }

        return redirect()->back()->with('success', __('general.thank_you_your_form_has_been_submitted_successfully'));
    }



    public function submissions(Service $service, Request $request)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;

        if (!$landingPage) {
            return redirect()->route('services.mine')
                ->with('error', __('general.no_landing_page_found_for_this_service'));
        }

        $query = $landingPage->formSubmissions();

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('submitted_by_name', 'like', "%{$search}%")
                  ->orWhere('submitted_by_email', 'like', "%{$search}%")
                  ->orWhere('submitted_by_phone', 'like', "%{$search}%");
            });
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $submissions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return Inertia::render('Marketplace/Seller/LandingPages/Submissions', ['service' => $service, 'landingPage' => $landingPage, 'submissions' => $submissions]);
    }



    public function destroySubmission(Service $service, ServiceLandingFormSubmission $submission)
    {
        $this->authorize('update', $service);

        // Verify the submission belongs to the service's landing page
        $landingPage = $service->landingPage;
        if (!$landingPage || $submission->landing_page_id !== $landingPage->id) {
            return redirect()->route('services.landing-page.submissions', $service)
                ->with('error', __('general.submission_not_found_or_access_denied'));
        }

        $submissionName = $submission->submitted_by_name ?? 'Unknown';
        $submission->delete();

        return redirect()->route('services.landing-page.submissions', $service)
            ->with('success', "Submission from '{$submissionName}' has been deleted successfully.");
    }



    public function exportSubmissions(Service $service, Request $request)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;

        if (!$landingPage) {
            return redirect()->route('services.mine')
                ->with('error', __('general.no_landing_page_found_for_this_service'));
        }

        $query = $landingPage->formSubmissions();

        // Apply same filters as submissions method
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('submitted_by_name', 'like', "%{$search}%")
                  ->orWhere('submitted_by_email', 'like', "%{$search}%")
                  ->orWhere('submitted_by_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        $filename = 'submissions_' . $service->slug . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($submissions) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID',
                'Submitted By',
                'Email',
                'Phone',
                'Submitted At',
                'IP Address',
                'User Agent',
                'Form Answers (JSON)'
            ]);

            // Data rows
            foreach ($submissions as $submission) {
                $formDataJson = !empty($submission->form_data)
                    ? json_encode($submission->form_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : '';

                fputcsv($file, [
                    $submission->id,
                    $submission->submitted_by_name ?? '',
                    $submission->submitted_by_email ?? '',
                    $submission->submitted_by_phone ?? '',
                    $submission->created_at->format('Y-m-d H:i:s'),
                    $submission->ip_address ?? '',
                    $submission->user_agent ?? '',
                    $formDataJson
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

}
