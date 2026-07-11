<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuestTicketStatus;
use App\Http\Controllers\Controller;
use App\Mail\GuestTicketOutboundReplyMail;
use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use App\Models\User;
use App\Notifications\GuestTicketReplyNotification;
use App\Services\GuestTicketMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class GuestTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = GuestTicket::query()
            ->with(['messages' => fn ($q) => $q->latest('created_at')->latest('id')->limit(1)])
            ->search($request->string('search')->toString())
            ->ofStatus($request->string('status')->toString())
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/GuestTickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => array_map(fn (GuestTicketStatus $s) => $s->value, GuestTicketStatus::cases()),
        ]);
    }

    public function show(GuestTicket $guest_ticket)
    {
        $guest_ticket->load(['messages' => fn ($q) => $q->orderBy('created_at')->orderBy('id')]);

        return Inertia::render('Admin/GuestTickets/Show', [
            'ticket' => $guest_ticket,
            'messages' => $guest_ticket->messages,
            'statuses' => array_map(fn (GuestTicketStatus $s) => $s->value, GuestTicketStatus::cases()),
        ]);
    }

    public function reply(Request $request, GuestTicket $guest_ticket, GuestTicketMailer $mailer): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'required|string|min:1',
            'attachment' => 'nullable|file|max:'.(int) config('imap.max_attachments_mb', 10) * 1024,
        ]);

        $previousOutbound = $guest_ticket->messages()
            ->where('direction', GuestTicket::DIRECTION_OUTBOUND)
            ->latest('created_at')
            ->latest('id')
            ->first();

        $messageId = $mailer->generateMessageId($guest_ticket);

        $attachmentMeta = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store("guest-tickets/{$guest_ticket->id}", 'local');
            $attachmentMeta = [[
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
            ]];
        }

        $message = GuestTicketMessage::create([
            'guest_ticket_id' => $guest_ticket->id,
            'direction' => GuestTicket::DIRECTION_OUTBOUND,
            'from_email' => config('mail.from.address'),
            'to_email' => $guest_ticket->email,
            'subject' => $mailer->generateSubject($guest_ticket),
            'body_text' => $data['body'],
            'message_id' => $messageId,
            'in_reply_to' => optional($previousOutbound)->message_id,
            'attachments_json' => $attachmentMeta,
            'sent_at' => now(),
        ]);

        $guest_ticket->update([
            'status' => GuestTicketStatus::Replied->value,
            'last_message_at' => now(),
            'last_message_message_id' => $messageId,
        ]);

        try {
            Mail::send(new GuestTicketOutboundReplyMail($guest_ticket, $message));
        } catch (\Throwable $e) {
            Log::warning('Guest ticket reply mail failed', ['ticket_id' => $guest_ticket->id, 'error' => $e->getMessage()]);
        }

        $this->notifyAdmins($guest_ticket);

        return back()->with('success', __('general.reply_sent_successfully'));
    }

    public function updateStatus(Request $request, GuestTicket $guest_ticket): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_map(fn ($c) => $c->value, GuestTicketStatus::cases())),
        ]);

        $current = GuestTicketStatus::from($guest_ticket->status);
        $next = GuestTicketStatus::from($data['status']);

        if (! $current->canTransitionTo($next)) {
            return back()->withErrors(['status' => __('general.invalid_status_transition')]);
        }

        $guest_ticket->update(['status' => $next->value]);

        return $request->wantsJson()
            ? response()->json(['status' => $next->value])
            : back();
    }

    public function destroy(GuestTicket $guest_ticket): RedirectResponse
    {
        $guest_ticket->delete();

        return redirect()->route('admin.guest-tickets.index')->with('success', __('general.deleted_successfully'));
    }

    private function notifyAdmins(GuestTicket $ticket): void
    {
        try {
            $admins = User::role(['admin', 'Admin'])->get();
            Notification::send($admins, new GuestTicketReplyNotification($ticket));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
