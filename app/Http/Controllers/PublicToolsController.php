<?php

namespace App\Http\Controllers;

use App\Models\GuestTicket;
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
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:50',
            'business_name' => 'nullable|string|max:255',
            'tool_name' => 'required|string|max:255',
            'breakdown' => 'required|string',
        ]);

        $ticket = GuestTicket::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'subject' => "Lead Magnet: " . $request->input('tool_name') . " estimation for " . ($request->input('business_name') ?: 'Individual'),
            'body' => "Business Name: " . ($request->input('business_name') ?: 'N/A') . "\n\nBreakdown Details:\n" . $request->input('breakdown'),
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('tools.fb_lead_success'),
            'ticket_id' => $ticket->id
        ]);
    }

    /**
     * Subscribe email to newsletter / technical updates.
     */
    public function subscribeNewsletter(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

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
