<?php

namespace App\Services;

use App\Helpers\FcmHelper;
use App\Mail\AdminTicketCreatedMail;
use App\Mail\ClientTicketReceivedMail;
use App\Models\Currency;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // 1. Send Email to Client ("كلامك وصل للإدارة")
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
            try {
                $clientTokens = $client->routeNotificationForFcm();
                $clientTokens = is_array($clientTokens) ? array_values(array_filter($clientTokens)) : ($clientTokens ? [$clientTokens] : []);

                if (! empty($clientTokens)) {
                    FcmHelper::send_push_notif_to_device(
                        $clientTokens,
                        [
                            'title' => "تم استلام تذكرتك بنجاح (#{$ticket->id})",
                            'description' => "كلامك وصل للإدارة! تم استلام تذكرتك '{$ticket->ticket_subject}' وجارٍ متابعتها والرد عليك.",
                            'type' => 'ticket_created_client',
                            'data_id' => (string) $ticket->id,
                        ],
                        url("/tickets/{$ticket->id}")
                    );
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send ticket FCM to client: " . $e->getMessage());
            }
        }

        // 3. Send Email to all Admins
        try {
            $admins = User::role(['admin', 'super_admin'])->get();
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
        try {
            $admins = $admins ?? User::role(['admin', 'super_admin'])->get();
            $adminTokens = [];

            foreach ($admins as $admin) {
                if ($client && $admin->id === $client->id) {
                    continue;
                }
                $tokens = $admin->routeNotificationForFcm();
                if (is_array($tokens)) {
                    $adminTokens = array_merge($adminTokens, $tokens);
                } elseif ($tokens) {
                    $adminTokens[] = $tokens;
                }
            }

            $adminTokens = array_values(array_unique(array_filter($adminTokens)));

            if (! empty($adminTokens)) {
                FcmHelper::send_push_notif_to_device(
                    $adminTokens,
                    [
                        'title' => "تذكرة دعم فني جديدة (#{$ticket->id})",
                        'description' => "قام {$clientName} بفتح تذكرة: {$ticket->ticket_subject}{$projectSuffix}",
                        'type' => 'ticket_created',
                        'data_id' => (string) $ticket->id,
                    ],
                    url("/admin/tickets/{$ticket->id}")
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch FCM notification for ticket creation to admins: ' . $e->getMessage());
        }
    }

    /**
     * Backwards-compatible alias for notifyOnTicketCreated.
     */
    public static function notifyAdminOnTicketCreated(Ticket $ticket): void
    {
        self::notifyOnTicketCreated($ticket);
    }

    /**
     * Dispatch FCM notifications to Client and Admin when a price quote is set for a ticket.
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

            // 1. Notify Client
            if ($client) {
                $clientTokens = $client->routeNotificationForFcm();
                $clientTokens = is_array($clientTokens) ? array_values(array_filter($clientTokens)) : ($clientTokens ? [$clientTokens] : []);

                if (! empty($clientTokens)) {
                    FcmHelper::send_push_notif_to_device(
                        $clientTokens,
                        [
                            'title' => "تم تسعير طلبك للتذكرة (#{$ticket->id})",
                            'description' => "تم تحديد التكلفة بمبلغ {$priceFormatted} {$currencySymbol}. اضغط هنا لمراجعة العرض والموافقة.",
                            'type' => 'ticket_priced',
                            'data_id' => (string) $ticket->id,
                        ],
                        url("/tickets/{$ticket->id}")
                    );
                }
            }

            // 2. Notify Admins
            $admins = User::role(['admin', 'super_admin'])->get();
            $adminTokens = [];
            foreach ($admins as $admin) {
                $tokens = $admin->routeNotificationForFcm();
                if (is_array($tokens)) {
                    $adminTokens = array_merge($adminTokens, $tokens);
                } elseif ($tokens) {
                    $adminTokens[] = $tokens;
                }
            }
            $adminTokens = array_values(array_unique(array_filter($adminTokens)));

            if (! empty($adminTokens)) {
                $clientName = $client?->name ?? 'العميل';
                FcmHelper::send_push_notif_to_device(
                    $adminTokens,
                    [
                        'title' => "تم إرسال تسعير التذكرة (#{$ticket->id})",
                        'description' => "تم تحديد التكلفة بمبلغ {$priceFormatted} {$currencySymbol} للعميل {$clientName}.",
                        'type' => 'ticket_priced_admin',
                        'data_id' => (string) $ticket->id,
                    ],
                    url("/admin/tickets/{$ticket->id}")
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch FCM notification for ticket pricing: ' . $e->getMessage());
        }
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
                $clientTokens = $client->routeNotificationForFcm();
                $clientTokens = is_array($clientTokens) ? array_values(array_filter($clientTokens)) : ($clientTokens ? [$clientTokens] : []);

                if (! empty($clientTokens)) {
                    FcmHelper::send_push_notif_to_device(
                        $clientTokens,
                        [
                            'title' => "تم تحديد تكلفة مشروعك: {$projectName}",
                            'description' => "تم اعتماد الميزانية والتكلفة بقيمة {$budgetFormatted} {$currencySymbol}. يمكنك الاطلاع على خطة العمل ومراحل التنفيذ الآن.",
                            'type' => 'project_priced',
                            'data_id' => (string) $project->id,
                        ],
                        url("/client/projects/{$project->id}")
                    );
                }
            }

            // 2. Notify Admins
            $admins = User::role(['admin', 'super_admin'])->get();
            $adminTokens = [];
            foreach ($admins as $admin) {
                $tokens = $admin->routeNotificationForFcm();
                if (is_array($tokens)) {
                    $adminTokens = array_merge($adminTokens, $tokens);
                } elseif ($tokens) {
                    $adminTokens[] = $tokens;
                }
            }
            $adminTokens = array_values(array_unique(array_filter($adminTokens)));

            if (! empty($adminTokens)) {
                $clientName = $client?->name ?? 'العميل';
                FcmHelper::send_push_notif_to_device(
                    $adminTokens,
                    [
                        'title' => "اعتماد ميزانية مشروع: {$projectName}",
                        'description' => "تم تحديد التكلفة بمبلغ {$budgetFormatted} {$currencySymbol} للعميل {$clientName}.",
                        'type' => 'project_priced_admin',
                        'data_id' => (string) $project->id,
                    ],
                    url("/admin/projects/{$project->id}")
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch FCM notification for project pricing: ' . $e->getMessage());
        }
    }
}
