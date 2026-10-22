<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientSoftwareWinbackLog;
use App\Models\User;
use App\Services\ClientSoftwareWinbackService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class AdminClientSoftwareWinbackController extends Controller
{
    public function __construct(
        private readonly ClientSoftwareWinbackService $winbackService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $statusFilter = $request->string('status')->trim()->toString(); // all, eligible_now, waiting_cadence, completed, unsubscribed, open_ticket

        $coupon = $this->winbackService->ensureWinbackCoupon();

        // 1. Gather all candidates who have past engagement and are inactive >= 180 days
        $allCandidates = $this->winbackService->getCandidateUsers()
            ->filter(fn (User $user) => $this->winbackService->hasPastEngagement($user));

        // 2. Compute status metadata for all candidates
        $now = now();
        $enriched = $allCandidates->map(function (User $user) use ($now) {
            $logs = ClientSoftwareWinbackLog::where('user_id', $user->id)
                ->orderBy('reminder_number')
                ->get();

            $logCount = $logs->count();
            $lastLog = $logs->last();

            $hasOpenTicket = $user->tickets()->whereNotIn('ticket_status', ['closed'])->exists();
            $isUnsubscribed = $user->winback_unsubscribed_at !== null;
            $hasRecent = $this->winbackService->hasRecentActivity($user);

            $nextReminder = $this->winbackService->determineNextReminderNumber($user);

            $recoveredLog = ClientSoftwareWinbackLog::where('user_id', $user->id)
                ->where('status', 'recovered')
                ->latest('recovered_at')
                ->first();

            // Compute human status
            if ($isUnsubscribed) {
                $status = 'unsubscribed';
                $statusLabel = 'ملغي الاشتراك';
            } elseif ($hasOpenTicket) {
                $status = 'open_ticket';
                $statusLabel = 'محجوب (تذكرة مفتوحة)';
            } elseif ($recoveredLog) {
                $status = 'recovered';
                $statusLabel = 'تمت الاستعادة بنجاح (' . ($recoveredLog->recovery_event_type ?: 'تفاعل جديد') . ')';
            } elseif ($hasRecent) {
                $status = 'recent_activity';
                $statusLabel = 'نشط حديثاً';
            } elseif ($logCount >= ClientSoftwareWinbackService::MAX_REMINDERS) {
                $status = 'completed';
                $statusLabel = 'مكتمل (4 تذكيرات)';
            } elseif ($nextReminder !== null) {
                $status = 'eligible_now';
                $statusLabel = "مؤهل للتذكير ({$nextReminder}) الآن";
            } elseif ($logCount > 0 && $lastLog && $lastLog->sent_at) {
                $daysSince = (int) $lastLog->sent_at->diffInDays($now);
                $daysRemaining = max(0, ClientSoftwareWinbackService::CADENCE_DAYS - $daysSince);
                $status = 'waiting_cadence';
                $statusLabel = "بانتظار دورة الـ 90 يوم ({$daysRemaining} يوم متبقي)";
            } else {
                $status = 'ineligible';
                $statusLabel = 'غير مؤهل';
            }

            // Inactivity days calculation
            $latestActivity = $user->last_activity_at;
            $inactivityDays = $latestActivity ? (int) $latestActivity->diffInDays($now) : 180;

            return [
                'id'                      => $user->id,
                'name'                    => $user->name,
                'email'                   => $user->email,
                'phone'                   => $user->phone ?: $user->mobile_1,
                'whatsapp_number'         => $user->whatsapp_number ?: $user->phone ?: $user->mobile_1,
                'avatar_url'              => $user->avatar_url,
                'inactivity_days'         => $inactivityDays,
                'last_activity_at'        => $user->last_activity_at?->format('Y-m-d H:i'),
                'status'                  => $status,
                'status_label'            => $statusLabel,
                'next_reminder'           => $nextReminder,
                'reminders_sent_count'    => $logCount,
                'last_sent_at'            => $lastLog?->sent_at?->format('Y-m-d H:i'),
                'can_send_now'            => $nextReminder !== null,
                'winback_unsubscribed_at' => $user->winback_unsubscribed_at?->format('Y-m-d'),
                'has_open_ticket'         => $hasOpenTicket,
                'past_invoices_count'     => $user->invoices()->where('status', 'paid')->count(),
                'past_projects_count'     => $user->projects()->count(),
            ];
        });

        // 3. Filter enriched list by search & status
        $filtered = $enriched->filter(function ($item) use ($search, $statusFilter) {
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $matchName = str_contains(mb_strtolower($item['name'] ?? ''), $needle);
                $matchEmail = str_contains(mb_strtolower($item['email'] ?? ''), $needle);
                $matchPhone = str_contains(mb_strtolower($item['phone'] ?? ''), $needle);

                if (! ($matchName || $matchEmail || $matchPhone)) {
                    return false;
                }
            }

            if ($statusFilter !== '' && $statusFilter !== 'all') {
                if ($item['status'] !== $statusFilter) {
                    return false;
                }
            }

            return true;
        })->values();

        // 4. Paginate manually
        $page = max(1, $request->integer('page', 1));
        $perPage = 15;
        $totalItems = $filtered->count();
        $pagedItems = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pagedItems,
            $totalItems,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 5. Aggregate KPI stats
        $stats = [
            'total_inactive_clients' => $allCandidates->count(),
            'eligible_now_count'     => $enriched->where('status', 'eligible_now')->count(),
            'waiting_cadence_count'  => $enriched->where('status', 'waiting_cadence')->count(),
            'completed_count'        => $enriched->where('status', 'completed')->count(),
            'recovered_count'        => $enriched->where('status', 'recovered')->count(),
            'unsubscribed_count'     => $enriched->where('status', 'unsubscribed')->count(),
            'total_sent_reminders'   => ClientSoftwareWinbackLog::count(),
            'reminders_by_stage'     => [
                1 => ClientSoftwareWinbackLog::where('reminder_number', 1)->count(),
                2 => ClientSoftwareWinbackLog::where('reminder_number', 2)->count(),
                3 => ClientSoftwareWinbackLog::where('reminder_number', 3)->count(),
                4 => ClientSoftwareWinbackLog::where('reminder_number', 4)->count(),
            ],
        ];

        // 6. Recent sent logs
        $recentLogs = ClientSoftwareWinbackLog::with('user:id,name,email')
            ->latest('sent_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id'              => $log->id,
                'user_name'       => $log->user?->name ?? 'مستخدم محذوف',
                'user_email'      => $log->user?->email ?? '-',
                'reminder_number' => $log->reminder_number,
                'status'          => $log->status,
                'coupon_code'     => $log->coupon_code,
                'sent_at'         => $log->sent_at?->diffForHumans() ?? '-',
                'sent_at_exact'   => $log->sent_at?->format('Y-m-d H:i') ?? '-',
                'recovered_at'    => $log->recovered_at?->diffForHumans(),
            ]);

        return Inertia::render('Admin/Winback/Index', [
            'clients'    => $paginator,
            'stats'      => $stats,
            'recentLogs' => $recentLogs,
            'coupon'     => [
                'code'               => $coupon->code,
                'discount_percentage'=> (float) $coupon->discount_percentage,
                'is_active'          => (bool) $coupon->is_active,
            ],
            'whatsappPhone' => ClientSoftwareWinbackService::WHATSAPP_PHONE,
            'filters'    => [
                'search' => $search,
                'status' => $statusFilter ?: 'all',
            ],
        ]);
    }

    /**
     * Dispatch winback emails to all eligible clients.
     */
    public function runAll(Request $request)
    {
        $stats = $this->winbackService->processAllEligible(dryRun: false);

        $msg = sprintf(
            'اكتمل تشغيل الحملة بنجاح: تم إرسال %d رسالة، وتخطي %d غير مؤهلين.',
            $stats['sent'],
            $stats['skipped']
        );

        if ($stats['errors'] > 0) {
            $msg .= sprintf(' وحدثت أخطاء في %d حالات.', $stats['errors']);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Manually send a reminder to a specific user.
     */
    public function sendToUser(Request $request, User $user)
    {
        $reminderNumber = $this->winbackService->determineNextReminderNumber($user);

        if ($reminderNumber === null) {
            return redirect()->back()->with(
                'error',
                'هذا العميل غير مؤهل حالياً للإرسال (إما لديه نشاط حديث، أو لم تمر 90 يوماً على التذكير السابق، أو ألغى الاشتراك).'
            );
        }

        $coupon = $this->winbackService->ensureWinbackCoupon();
        $dispatched = $this->winbackService->sendReminder($user, $reminderNumber, $coupon->code, false);

        if (! $dispatched) {
            return redirect()->back()->with('error', 'تعذر إرسال التذكير للعميل، يرجى مراجعة سجلات النظام.');
        }

        return redirect()->back()->with(
            'success',
            "تم إرسال التذكير رقم ({$reminderNumber}) بنجاح للعميل {$user->name}."
        );
    }

    /**
     * Render the email template in HTML for preview.
     */
    public function preview(Request $request)
    {
        $reminderNumber = max(1, min(4, $request->integer('reminder_number', 1)));
        $coupon = $this->winbackService->ensureWinbackCoupon();

        $whatsappUrl = $this->winbackService->generateWhatsAppUrl($coupon->code);
        $projectUrl = $this->winbackService->generateProjectUrl($coupon->code);
        $unsubscribeUrl = url('/winback/unsubscribe-demo');

        return view('emails.winback.software_reactivation', [
            'clientFirstName' => 'أحمد',
            'reminderNumber'  => $reminderNumber,
            'couponCode'      => $coupon->code,
            'whatsappUrl'     => $whatsappUrl,
            'projectUrl'      => $projectUrl,
            'unsubscribeUrl'  => $unsubscribeUrl,
        ]);
    }
}
