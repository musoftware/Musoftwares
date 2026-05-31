<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\CrmWidget;
use Modules\CRM\Models\Lead;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CrmWidgetCaptureController extends Controller
{
    /**
     * Render the embeddable Lead Capture form for a specific widget.
     * The token uniquely identifies the widget.
     */
    public function show(Request $request, $token)
    {
        $widget = CrmWidget::where('embed_token', $token)->where('is_active', true)->firstOrFail();

        // Optional: Check allowed domains if provided via HTTP_REFERER
        // (If embedded in an iframe, the browser will send the referer of the parent page)
        $referer = $request->headers->get('referer');
        if ($referer && !empty($widget->allowed_domains)) {
            $parsedHost = parse_url($referer, PHP_URL_HOST);
            $isAllowed = false;
            foreach ($widget->allowed_domains as $domain) {
                if ($domain === '*' || $parsedHost === $domain || str_ends_with($parsedHost, '.' . $domain)) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                abort(403, 'Widget not allowed on this domain.');
            }
        }

        return Inertia::render('CRM/Embeds/CrmWidgetIframe', [
            'widget' => $widget,
            'token' => $token,
        ]);
    }

    /**
     * Handle the form submission from the embedded iframe.
     */
    public function store(Request $request, $token)
    {
        $widget = CrmWidget::where('embed_token', $token)->where('is_active', true)->firstOrFail();

        // Validate generic fields. The frontend controls what is visible.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255', // Could be required depending on form_config
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'custom_fields' => 'nullable|array',
        ]);

        try {
            $lead = new Lead();
            $lead->workspace_id = $widget->workspace_id; // Scope to the workspace who owns the widget
            $lead->crm_widget_id = $widget->id;
            $lead->name = $validated['name'];
            $lead->email = $validated['email'] ?? null;
            $lead->phone = $validated['phone'] ?? null;
            $lead->company = $validated['company'] ?? null;
            $lead->message = $validated['message'] ?? null;
            $lead->custom_data = $validated['custom_fields'] ?? [];
            $lead->status = 'new';
            $lead->source = 'widget';
            
            // Capture IP and User Agent for security
            $lead->ip_address = $request->ip();
            $lead->user_agent = $request->userAgent();

            $lead->save();

            return redirect()->back()->with('success', __('crm.lead_info_submitted'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Lead capture failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('crm.lead_info_error'));
        }
    }
}
