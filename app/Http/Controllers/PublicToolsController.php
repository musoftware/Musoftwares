<?php

namespace App\Http\Controllers;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Models\GuestTicket;
use App\Services\Privacy\ConsentAuditService;
use Illuminate\Http\Request;

/**
 * Lead and newsletter forms on public pages. The free tools that used to live here moved into
 * /services (tools.musoftwares.com was retired); old /tools/* URLs redirect there (routes/web.php).
 */
class PublicToolsController extends Controller
{
    /**
     * Capture Lead Magnet submissions and store as a CRM Guest Ticket.
     */
    public function captureLead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:50',
            'business_name' => 'nullable|string|max:255',
            'business' => 'nullable|string|max:255',
            'tool_name' => 'nullable|string|max:255',
            'breakdown' => 'required_without:scope|nullable|string|max:20000',
            'scope' => 'required_without:breakdown|nullable|array',
        ]);

        $business = $request->input('business_name') ?: $request->input('business');
        $toolName = $request->input('tool_name') ?: 'Estimator';
        $breakdown = $request->input('breakdown') ?: $this->formatScope((array) $request->input('scope', []));

        $ticket = GuestTicket::create([
            'name' => $request->input('name'),
            'email' => (string) $request->input('email', ''),
            'mobile' => $request->input('mobile'),
            'subject' => "Lead Magnet: {$toolName} estimation for " . ($business ?: 'Individual'),
            'body' => 'Business Name: ' . ($business ?: 'N/A') . "\n\nBreakdown Details:\n" . $breakdown,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('tools.fb_lead_success'),
            'ticket_id' => $ticket->id
        ]);
    }

    /**
     * Turn the scope object sent by the estimator / system builder into readable lines.
     */
    private function formatScope(array $scope): string
    {
        $lines = [];
        foreach ($scope as $key => $value) {
            $text = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
            $lines[] = str_replace('_', ' ', (string) $key) . ': ' . $text;
        }

        return mb_substr(implode("\n", $lines), 0, 20000);
    }

    /**
     * Subscribe email to newsletter / technical updates.
     */
    public function subscribeNewsletter(Request $request, ConsentAuditService $consentService)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $consentService->record($request->user(), ConsentType::DirectMarketingEmail, ConsentAction::Granted, $request);

        $email = $request->input('email');

        // Check if ticket already exists recently for this email
        $existing = GuestTicket::where('email', $email)
            ->where('subject', 'LIKE', 'Newsletter Subscription%')
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if (!$existing) {
            GuestTicket::create([
                'name' => 'Newsletter Subscriber',
                'email' => $email,
                'mobile' => '',
                'subject' => 'Newsletter Subscription: ' . $email,
                'body' => 'User subscribed to technical updates and platform release notes via footer newsletter form.',
                'status' => 'open',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to our technical updates!',
        ]);
    }
}
