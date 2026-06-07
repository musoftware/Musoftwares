<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        
        $query = Ticket::with(['user', 'conversation.messages.sender']);

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->latest()->paginate(15);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High',
            'description' => 'required|string',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            // 1. Create the support ticket using legacy columns
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'ticket_subject' => $validated['subject'],
                'ticket_message' => $validated['description'],
                'ticket_status' => 'open',
                'priority' => strtolower($validated['priority']),
            ]);

            // 2. Create the associated chat conversation (if using the new reply system)
            $conversation = Conversation::create([
                'conversable_type' => Ticket::class,
                'conversable_id' => $ticket->id,
                'type' => 'support_ticket',
                'status' => 'open',
            ]);

            // Add the user as a participant
            $conversation->participants()->create([
                'user_id' => $user->id,
                'role' => 'client',
            ]);

            // Create the first message in the conversation representing the ticket description
            $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => $validated['description'],
                'is_system' => false,
            ]);

            // Add all admin users as participants so they can view and reply
            $admins = \App\Models\User::role('admin')->get();
            foreach ($admins as $admin) {
                $conversation->participants()->create([
                    'user_id' => $admin->id,
                    'role' => 'admin',
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', __('general.support_ticket_opened_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }

    public function resolve($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        // Authorize (only owner or admin)
        $isAdmin = $user && ($user->hasRole(['admin', 'Admin']) || $user->roles->contains('name', 'Admin'));
        if (!$user || ($user->id !== $ticket->user_id && !$isAdmin)) {
            abort(403);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['ticket_status' => 'closed']);
            
            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'closed']);
            }
        });

        return redirect()->back()->with('success', __('general.ticket_resolved'));
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isAdmin = $user && ($user->hasRole(['admin', 'Admin']) || $user->roles->contains('name', 'Admin'));
        if (!$user || ($user->id !== $ticket->user_id && !$isAdmin)) {
            abort(403);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['ticket_status' => 'closed']);
            
            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'closed']);
            }
        });

        return redirect()->back()->with('success', __('general.ticket_closed'));
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isAdmin = $user && ($user->hasRole(['admin', 'Admin']) || $user->roles->contains('name', 'Admin'));
        if (!$user || ($user->id !== $ticket->user_id && !$isAdmin)) {
            abort(403);
        }

        $ticket->delete();

        return redirect()->back()->with('success', __('general.ticket_deleted'));
    }

    public function guestStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'description' => 'required|string',
            'g-recaptcha-response' => ['required', new \App\Rules\Recaptcha()],
        ], [
            'g-recaptcha-response.required' => __('general.recaptcha_required') ?? 'يرجى التحقق من الكابتشا (Google reCAPTCHA).'
        ]);

        DB::beginTransaction();

        try {
            $message = $validated['description'] . "\n\nرقم الهاتف: " . $validated['phone'];

            $ticket = Ticket::create([
                'user_id' => null,
                'anonymous_name' => $validated['name'],
                'anonymous_email' => $validated['email'],
                'ticket_subject' => 'طلب خدمة حصرية',
                'ticket_message' => $message,
                'ticket_status' => 'open',
                'priority' => 'high',
            ]);

            // Notify Admins
            $admins = \App\Models\User::role(['admin', 'Admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewGuestTicketNotification($ticket));

            // Firebase Notification
            try {
                $messaging = app('firebase.messaging');
                $notification = \Kreait\Firebase\Messaging\Notification::create(
                    'طلب خدمة حصرية جديد',
                    'طلب جديد من ' . $validated['name']
                );

                $tokens = $admins->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                if (!empty($tokens)) {
                    $messageObj = \Kreait\Firebase\Messaging\CloudMessage::new()
                        ->withNotification($notification)
                        ->withData([
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'url' => route('admin.tickets.show', $ticket->id)
                        ]);
                    $messaging->sendMulticast($messageObj, $tokens);
                }
            } catch (\Exception $e) {
                \Log::error('Firebase Notification Failed: ' . $e->getMessage());
            }

            DB::commit();

            return redirect()->back()->with('success', 'تم ارسال الطلب بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }
}
