<?php

namespace App\Services;

use App\Helpers\FcmHelper;
use App\Mail\AdminGuestTicketCreatedMail;
use App\Mail\AdminTicketCreatedMail;
use App\Mail\AdminTicketRepliedMail;
use App\Mail\ClientTicketPricedMail;
use App\Mail\ClientTicketReceivedMail;
use App\Mail\ClientTicketRepliedMail;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketStatusUpdatedMail;
use App\Models\Currency;
use App\Models\GuestTicket;
use App\Models\Message;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketNotificationService
{
    /**
     * Dispatch email and FCM notifications to client and all admins when a ticket is created.
     */
    public static function notifyOnTicketCreated(Ticket $ticket): void
    {
        $client = $ticket->user;
        $clientName = $client?->name ?? $ticket->anonymous_name ?? 'العميل';
        $projectName = $ticket->project ? ($ticket->project->project_name ?? $ticket->project->name) : null;
        $projectSuffix = $projectName ? " [مشروع: {$projectName}]" : '';

        // 1. Send Email to Client
        $clientEmail = $client?->email ?? $ticket->anonymous_email;
        if (! empty($clientEmail)) {
            try {
                Mail::to($clientEmail)->queue(new ClientTicketReceivedMail($ticket, $client));
            } catch (\Throwable $e) {
                Log::error("Failed to send ticket confirmation email to client: " . $e->getMessage());
            }
        }

        // 2. Send FCM to Client
        if ($client) {
            self::sendFcmToUser(
                $client,
                [
                    'title' => "تم استلام تذكرتك بنجاح (#{$ticket->id})",
                    'description' => "كلامك وصل للإدارة! تم استلام تذكرتك '{$ticket->ticket_subject}' وجارٍ متابعتها والرد عليك.",
                    'type' => 'ticket_created_client',
                    'data_id' => (string) $ticket->id,
                ],
                url("/tickets/{$ticket->id}")
            );
        }

        // 3. Send Email to all Admins
        try {
            $admins = self::getStaffUsers();
            foreach ($admins as $admin) {
                if ($client && $admin->id === $client->id) {
                    continue;
                }
                if (! empty($admin->email)) {
                    Mail::to($admin->email)->queue(new AdminTicketCreatedMail($ticket, $client));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send new ticket email to admins: ' . $e->getMessage());
        }

        // 4. Send FCM to all Admins
        self::sendFcmToStaff(
            [
                'title' => "تذكرة دعم فني جديدة (#{$ticket->id})",
                'description' => "قام {$clientName} بفتح تذكرة: {$ticket->ticket_subject}{$projectSuffix}",
                'type' => 'ticket_created',
                'data_id' => (string) $ticket->id,
            ],
            url("/admin/tickets/{$ticket->id}"),
            excludeUserId: $client?->id
        );
    }

    /**
     * Dispatch email and FCM notifications when a reply is posted to a ticket.
     */
    public static function notifyOnTicketReplied(Ticket $ticket, Message $message, User $sender, bool $isInternal = false): void
    {
        if ($isInternal) {
            return;
        }

        $client = $ticket->user;
        $isClientSender = $client && $sender->id === $client->id;

        if ($isClientSender) {
            // Client replied -> notify assigned agent or all admins
            $ticket->update(['ticket_status' => 'user_replied']);

            // 1. Notify Assigned Employee or Admins via Email
            $recipients = collect();
            if ($ticket->assignedEmployee && $ticket->assignedEmployee->email) {
                $recipients->push($ticket->assignedEmployee);
            } else {
                $recipients = self::getStaffUsers();
            }

            foreach ($recipients as $recipient) {
                if ($recipient->id === $sender->id || empty($recipient->email)) {
                    continue;
                }
                try {
                    Mail::to($recipient->email)->queue(new AdminTicketRepliedMail($ticket, $message, $sender));
                } catch (\Throwable $e) {
                    Log::error("Failed to send ticket reply email to staff {$recipient->id}: " . $e->getMessage());
                }
            }

            // 2. Notify Assigned Employee or Admins via FCM
            $snippet = Str::limit($message->body ?: 'مرفق جديد', 100);
            if ($ticket->assignedEmployee) {
                self::sendFcmToUser(
                    $ticket->assignedEmployee,
                    [
                        'title' => "رد جديد من {$sender->name} (#{$ticket->id})",
                        'description' => $snippet,
                        'type' => 'ticket_reply_admin',
                        'data_id' => (string) $ticket->id,
                    ],
                    url("/admin/tickets/{$ticket->id}")
                );
            } else {
                self::sendFcmToStaff(
                    [
                        'title' => "رد جديد من {$sender->name} (#{$ticket->id})",
                        'description' => $snippet,
                        'type' => 'ticket_reply_admin',
                        'data_id' => (string) $ticket->id,
                    ],
                    url("/admin/tickets/{$ticket->id}"),
                    excludeUserId: $sender->id
                );
            }

            return;
        }

        // Admin / Agent replied -> notify Client
        $ticket->update(['ticket_status' => 'agent_replied']);

        // 1. Send Email to Client
        $clientEmail = $client?->email ?? $ticket->anonymous_email;
        if (! empty($clientEmail)) {
            try {
                Mail::to($clientEmail)->queue(new ClientTicketRepliedMail($ticket, $message, $sender, $client));
            } catch (\Throwable $e) {
                Log::error("Failed to send ticket reply email to client: " . $e->getMessage());
            }
        }

        // 2. Send FCM to Client
        if ($client) {
            $snippet = Str::limit($message->body ?: 'مرفق جديد', 100);
            self::sendFcmToUser(
                $client,
                [
                    'title' => "رد جديد على تذكرتك (#{$ticket->id})",
                    'description' => $snippet,
                    'type' => 'ticket_reply_client',
                    'data_id' => (string) $ticket->id,
                ],
                url("/tickets/{$ticket->id}")
            );
        }
    }

    /**
     * Dispatch email and FCM notifications when a ticket's status changes (closed, resolved, reopened).
     */
    public static function notifyOnTicketStatusChanged(Ticket $ticket, string $newStatus, ?string $comment = null): void
    {
        $statusMap = [
            'closed' => 'مغلقة',
            'resolved' => 'تم الحل بنجاح',
            'open' => 'مفتوحة / قيد المتابعة',
            'agent_replied' => 'تم الرد من فريق الدعم',
            'user_replied' => 'بانتظار رد فريق الدعم',
        ];

        $statusArabic = $statusMap[$newStatus] ?? $newStatus;
        $client = $ticket->user;

        // 1. Send Email to Client
        $clientEmail = $client?->email ?? $ticket->anonymous_email;
        if (! empty($clientEmail)) {
            try {
                Mail::to($clientEmail)->queue(new TicketStatusUpdatedMail($ticket, $statusArabic, $comment, $client));
            } catch (\Throwable $e) {
                Log::error("Failed to send ticket status email to client: " . $e->getMessage());
            }
        }

        // 2. Send FCM to Client
        if ($client) {
            $fcmBody = "تم تحديث حالة تذكرتك إلى: {$statusArabic}";
            if ($comment) {
                $fcmBody .= " — " . Str::limit($comment, 60);
            }

            self::sendFcmToUser(
                $client,
                [
                    'title' => "تحديث حالة التذكرة (#{$ticket->id})",
                    'description' => $fcmBody,
                    'type' => 'ticket_status_changed',
                    'data_id' => (string) $ticket->id,
                ],
                url("/tickets/{$ticket->id}")
            );
        }

        // 3. If reopened, also notify staff via FCM
        if ($newStatus === 'open') {
            $clientName = $client?->name ?? 'العميل';
            self::sendFcmToStaff(
                [
                    'title' => "إعادة فتح التذكرة (#{$ticket->id})",
                    'description' => "قام {$clientName} أو المشرف بإعادة فتح التذكرة: {$ticket->ticket_subject}",
                    'type' => 'ticket_reopened',
                    'data_id' => (string) $ticket->id,
                ],
                url("/admin/tickets/{$ticket->id}")
            );
        }
    }

    /**
     * Dispatch email and FCM notifications when a ticket is assigned to a staff member.
     */
    public static function notifyOnTicketAssigned(Ticket $ticket, User $assignedAgent): void
    {
        // 1. Send Email to Assigned Agent
        if (! empty($assignedAgent->email)) {
            try {
                Mail::to($assignedAgent->email)->queue(new TicketAssignedMail($ticket, $assignedAgent));
            } catch (\Throwable $e) {
                Log::error("Failed to send ticket assigned email to agent {$assignedAgent->id}: " . $e->getMessage());
            }
        }

        // 2. Send FCM to Assigned Agent
        self::sendFcmToUser(
            $assignedAgent,
            [
                'title' => "تم تعيين تذكرة جديدة لك (#{$ticket->id})",
                'description' => "قام المشرف بتعيين التذكرة '{$ticket->ticket_subject}' لك للمتابعة.",
                'type' => 'ticket_assigned',
                'data_id' => (string) $ticket->id,
            ],
            url("/admin/tickets/{$ticket->id}")
        );
    }

    /**
     * Dispatch FCM and Email notifications to Client and Admin when a price quote is set for a ticket.
     */
    public static function notifyOnTicketPriced(Ticket $ticket): void
    {
        try {
            $currencySymbol = 'EGP';
            if ($ticket->currency_id) {
                $currencySymbol = Currency::find($ticket->currency_id)?->symbol ?? 'EGP';
            }

            $priceFormatted = number_format((float) ($ticket->price ?? 0), 2);
            $client = $ticket->user;

            // 1. Send Email to Client
            $clientEmail = $client?->email ?? $ticket->anonymous_email;
            if (! empty($clientEmail)) {
                try {
                    Mail::to($clientEmail)->queue(new ClientTicketPricedMail($ticket, $client));
                } catch (\Throwable $e) {
                    Log::error("Failed to send ticket pricing email to client: " . $e->getMessage());
                }
            }

            // 2. Send FCM to Client
            if ($client) {
                self::sendFcmToUser(
                    $client,
                    [
                        'title' => "تم تسعير طلبك للتذكرة (#{$ticket->id})",
                        'description' => "تم تحديد التكلفة بمبلغ {$priceFormatted} {$currencySymbol}. اضغط هنا لمراجعة العرض والموافقة.",
                        'type' => 'ticket_priced',
                        'data_id' => (string) $ticket->id,
                    ],
                    url("/tickets/{$ticket->id}")
                );
            }

            // 3. Notify Staff via FCM
            $clientName = $client?->name ?? 'العميل';
            self::sendFcmToStaff(
                [
                    'title' => "تم إرسال تسعير التذكرة (#{$ticket->id})",
                    'description' => "تم تحديد التكلفة بمبلغ {$priceFormatted} {$currencySymbol} للعميل {$clientName}.",
                    'type' => 'ticket_priced_admin',
                    'data_id' => (string) $ticket->id,
                ],
                url("/admin/tickets/{$ticket->id}")
            );
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch notification for ticket pricing: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch email and FCM notifications to all admins when a guest ticket is submitted.
     */
    public static function notifyOnGuestTicketCreated(GuestTicket $guestTicket): void
    {
        // 1. Send Email to Admins
        try {
            $admins = self::getStaffUsers();
            foreach ($admins as $admin) {
                if (! empty($admin->email)) {
                    Mail::to($admin->email)->queue(new AdminGuestTicketCreatedMail($guestTicket));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send guest ticket email to admins: ' . $e->getMessage());
        }

        // 2. Send FCM to Admins
        self::sendFcmToStaff(
            [
                'title' => "تذكرة زائر جديدة (#{$guestTicket->id})",
                'description' => "طلب جديد من {$guestTicket->name}: " . ($guestTicket->subject ?? 'استفسار'),
                'type' => 'guest_ticket_created',
                'data_id' => (string) $guestTicket->id,
            ],
            url("/admin/guest-tickets/{$guestTicket->id}")
        );
    }

    /**
     * Dispatch FCM notifications to Client and Admin when a project budget / price is determined.
     */
    public static function notifyOnProjectPriced(Project $project, ?float $newBudget = null): void
    {
        try {
            $budget = $newBudget ?? (float) ($project->budget ?? 0);
            if ($budget <= 0) {
                return;
            }

            $budgetFormatted = number_format($budget, 2);
            $currencySymbol = $project->currencySymbol() ?? 'EGP';
            $projectName = $project->project_name ?? $project->name ?? ('#' . $project->id);
            $client = $project->client ?? $project->user;

            // 1. Notify Client
            if ($client) {
                self::sendFcmToUser(
                    $client,
                    [
                        'title' => "تم تحديد تكلفة مشروعك: {$projectName}",
                        'description' => "تم اعتماد الميزانية والتكلفة بقيمة {$budgetFormatted} {$currencySymbol}. يمكنك الاطلاع على خطة العمل ومراحل التنفيذ الآن.",
                        'type' => 'project_priced',
                        'data_id' => (string) $project->id,
                    ],
                    url("/client/projects/{$project->id}")
                );
            }

            // 2. Notify Admins
            $clientName = $client?->name ?? 'العميل';
            self::sendFcmToStaff(
                [
                    'title' => "اعتماد ميزانية مشروع: {$projectName}",
                    'description' => "تم تحديد التكلفة بمبلغ {$budgetFormatted} {$currencySymbol} للعميل {$clientName}.",
                    'type' => 'project_priced_admin',
                    'data_id' => (string) $project->id,
                ],
                url("/admin/projects/{$project->id}")
            );
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch FCM notification for project pricing: ' . $e->getMessage());
        }
    }

    /**
     * Helper to send FCM push to a specific user.
     */
    public static function sendFcmToUser(User $user, array $data, ?string $webPushLink = null): bool
    {
        try {
            $tokens = $user->routeNotificationForFcm();
            $tokens = is_array($tokens) ? array_values(array_filter($tokens)) : ($tokens ? [$tokens] : []);

            if (empty($tokens)) {
                return false;
            }

            return FcmHelper::send_push_notif_to_device($tokens, $data, $webPushLink);
        } catch (\Throwable $e) {
            Log::error("Failed to send FCM to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to send FCM push to all admin / staff users.
     */
    public static function sendFcmToStaff(array $data, ?string $webPushLink = null, ?int $excludeUserId = null): bool
    {
        try {
            $staff = self::getStaffUsers();
            $allTokens = [];

            foreach ($staff as $user) {
                if ($excludeUserId && $user->id === $excludeUserId) {
                    continue;
                }
                $tokens = $user->routeNotificationForFcm();
                if (is_array($tokens)) {
                    $allTokens = array_merge($allTokens, $tokens);
                } elseif ($tokens) {
                    $allTokens[] = $tokens;
                }
            }

            $allTokens = array_values(array_unique(array_filter($allTokens)));

            if (empty($allTokens)) {
                return false;
            }

            return FcmHelper::send_push_notif_to_device($allTokens, $data, $webPushLink);
        } catch (\Throwable $e) {
            Log::error('Failed to send FCM to staff: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all admin and moderator users.
     */
    public static function getStaffUsers()
    {
        return rescue(
            fn () => User::role(['admin', 'super_admin', 'moderator'])->get(),
            User::where('role', 'admin')->orWhere('is_admin', true)->get()
        );
    }
}
