<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MessagesController extends Controller
{
    /** Roles a client can always write to (our team). */
    private const STAFF_ROLES = ['super_admin', 'admin', 'superadmin', 'Admin', 'moderator', 'support_agent'];

    public function index(Request $request)
    {
        $user = $request->user();
        $recipientId = $request->query('recipient_id') ?? $request->query('user_id');
        $activeConversationId = null;

        if ($recipientId && (int) $recipientId !== (int) $user->id) {
            $recipient = User::find($recipientId);
            if ($recipient && $this->canMessage($user, $recipient)) {
                // Find or create direct_message conversation between user and recipient
                $conv = Conversation::where('type', 'direct_message')
                    ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                    ->whereHas('participants', fn ($q) => $q->where('user_id', $recipient->id))
                    ->first();

                if (! $conv) {
                    $conv = Conversation::create([
                        'conversable_type' => User::class,
                        'conversable_id' => $user->id,
                        'type' => 'direct_message',
                        'status' => 'open',
                    ]);

                    $conv->participants()->create([
                        'user_id' => $user->id,
                        'role' => 'buyer',
                    ]);

                    $conv->participants()->create([
                        'user_id' => $recipient->id,
                        'role' => 'seller',
                    ]);
                }

                $activeConversationId = $conv->id;
            }
        }

        // Fetch all conversations where user is participant
        $conversations = Conversation::with([
            'participants.user',
            'messages' => fn ($q) => $q->latest()->take(1),
            'conversable' => fn ($morphTo) => $morphTo->morphWith([
                \Modules\Marketplace\Models\ServiceOrder::class => ['service', 'package.service'],
            ]),
        ])
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get()
            ->map(fn ($conv) => $this->formatConversation($conv, $user));

        $users = $this->contactableUsers($user);

        return Inertia::render('Client/Messages/Index', [
            'conversations' => $conversations,
            'users' => $users,
            'activeConversationId' => $activeConversationId,
        ]);
    }

    public function storeDirectMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($request->recipient_id);

        if ($sender->id === $recipient->id) {
            return back()->withErrors(['recipient_id' => 'Cannot send message to yourself.']);
        }
        abort_unless($this->canMessage($sender, $recipient), 403);

        try {
            DB::transaction(function () use ($sender, $recipient, $request) {
                // Check if direct message conversation already exists between these two
                $existing = Conversation::where('type', 'direct_message')
                    ->whereHas('participants', fn ($q) => $q->where('user_id', $sender->id))
                    ->whereHas('participants', fn ($q) => $q->where('user_id', $recipient->id))
                    ->first();

                if ($existing) {
                    $msg = $existing->messages()->create([
                        'sender_id' => $sender->id,
                        'body' => $request->message,
                    ]);

                    $msg->load(['sender', 'conversation']);
                    $recipient->notify(new \App\Notifications\NewMessageNotification($msg));

                    return $existing;
                }

                // Create new conversation
                $conv = Conversation::create([
                    'conversable_type' => User::class,
                    'conversable_id' => $sender->id,
                    'type' => 'direct_message',
                    'status' => 'open',
                ]);

                $conv->participants()->create([
                    'user_id' => $sender->id,
                    'role' => 'buyer',
                ]);

                $conv->participants()->create([
                    'user_id' => $recipient->id,
                    'role' => 'seller',
                ]);

                $msg = $conv->messages()->create([
                    'sender_id' => $sender->id,
                    'body' => $request->message,
                ]);

                $msg->load(['sender', 'conversation']);
                $recipient->notify(new \App\Notifications\NewMessageNotification($msg));

                return $conv;
            });

            return back()->with('success', __('general.message_sent_successfully'));
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to send message: '.$e->getMessage()]);
        }
    }

    /**
     * People this user may start a conversation with. Clients only see our team and people they
     * already talk to, by name; the full user list with e-mails is for admins only.
     */
    private function contactableUsers(User $user)
    {
        $query = User::where('id', '!=', $user->id)->select('id', 'name', 'email')->with('roles')->orderBy('name');

        if (! $user->isAdmin()) {
            $query->where(fn ($q) => $q
                ->whereHas('roles', fn ($r) => $r->whereIn('name', self::STAFF_ROLES))
                ->orWhereIn('id', $this->counterpartIds($user)));
        }

        return $query->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $user->isAdmin() ? $u->email : null,
            'role' => $u->roles->first()?->name ?? 'user',
        ]);
    }

    private function canMessage(User $sender, User $recipient): bool
    {
        return $sender->isAdmin()
            || $recipient->hasRole(self::STAFF_ROLES)
            || in_array($recipient->id, $this->counterpartIds($sender), true);
    }

    /** @return array<int, int> users who share a conversation with this user */
    private function counterpartIds(User $user): array
    {
        return DB::table('conversation_participants as mine')
            ->join('conversation_participants as other', 'other.conversation_id', '=', 'mine.conversation_id')
            ->where('mine.user_id', $user->id)
            ->where('other.user_id', '!=', $user->id)
            ->distinct()
            ->pluck('other.user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Format conversation data into a standardized structure for the frontend UI.
     */
    private function formatConversation(Conversation $conv, User $user): array
    {
        $participant = $conv->participants->firstWhere('user_id', $user->id);
        $lastRead = $participant?->last_read_at ?? '2000-01-01 00:00:00';

        $unreadCount = $conv->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('created_at', '>', $lastRead)
            ->count();

        $type = $conv->type;
        $conversable = rescue(fn () => $conv->conversable, null, false);
        $conversableType = (string) $conv->conversable_type;

        $otherUser = $conv->participants->firstWhere('user_id', '!=', $user->id)?->user;
        $otherUserName = $otherUser?->name ?? __('general.unknown_user');

        $category = 'direct_messages';
        $categoryLabel = __('general.direct_messages') ?: 'Direct Messages';
        $title = $otherUserName;
        // other people's e-mail addresses are only shown to admins
        $subtitle = $user->isAdmin() && $otherUser?->email ? $otherUser->email : __('general.support_team');
        $targetUrl = null;

        if ($type === 'marketplace_order' || $type === 'service_order' || str_contains($conversableType, 'ServiceOrder')) {
            $category = 'service_orders';
            $categoryLabel = __('general.service_orders') ?: 'Service Orders';
            $orderNum = $conversable?->order_number ?? $conversable?->id ?? $conv->conversable_id;
            $serviceTitle = $conversable?->service?->title ?? '';
            $title = $serviceTitle ? "Order #{$orderNum}: {$serviceTitle}" : "Order #{$orderNum}";
            $statusVal = $conversable?->status instanceof \BackedEnum ? $conversable->status->value : $conversable?->status;
            $subtitle = $statusVal ? "Status: {$statusVal}" : $otherUserName;
            if ($conversable?->id) {
                $targetUrl = "/marketplace/orders/{$conversable->id}";
            }
        } elseif ($type === 'support_ticket' || $type === 'support' || str_contains($conversableType, 'Ticket')) {
            $category = 'support_tickets';
            $categoryLabel = __('general.support_tickets') ?: 'Support Tickets';
            $ticketNum = $conversable?->ticket_number ?? $conv->conversable_id;
            $subject = $conversable?->subject ?? '';
            $title = $subject ? "Ticket #{$ticketNum}: {$subject}" : "Support Ticket #{$ticketNum}";
            $subtitle = $conversable?->status ? "Status: {$conversable->status}" : 'Support Desk';
        } elseif ($type === 'custom_project' || $type === 'freelance_contract' || str_contains($conversableType, 'Project') || str_contains($conversableType, 'Proposal')) {
            $category = 'custom_projects';
            $categoryLabel = __('general.custom_projects') ?: 'Custom Projects';
            $projTitle = $conversable?->title ?? $conversable?->name ?? "Project #{$conv->conversable_id}";
            $title = "Project: {$projTitle}";
            $subtitle = $conversable?->status ? "Status: {$conversable->status}" : $otherUserName;
        }

        return [
            'id' => $conv->id,
            'conversable_id' => $conv->conversable_id,
            'conversable_type' => $conv->conversable_type,
            'type' => $conv->type,
            'status' => $conv->status,
            'category' => $category,
            'category_label' => $categoryLabel,
            'title' => $title,
            'subtitle' => $subtitle,
            'target_url' => $targetUrl,
            'unread_count' => $unreadCount,
            'participants' => $conv->participants,
            'messages' => $conv->messages,
            'updated_at' => $conv->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}

