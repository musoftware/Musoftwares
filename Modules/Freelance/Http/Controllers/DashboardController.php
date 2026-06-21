<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Proposal;
use App\Models\PointTransaction;
use Modules\Freelance\Models\Job;
use Illuminate\Support\Facades\Schema;
use App\Traits\ConvertsCurrency;

class DashboardController extends Controller
{
    use ConvertsCurrency;

    public function index(Request $request)
    {
        $user = $request->user();

        // Detect which columns exist on contracts (migration-safe)
        $hasAmountCol    = Schema::hasColumn('freelance_contracts', 'amount');
        $hasCurrencyCol  = Schema::hasColumn('freelance_contracts', 'currency_id');
        $earningsField   = $hasAmountCol ? 'amount' : 'contract_points';

        // ── FREELANCER DATA ────────────────────────────────────────────

        // 1. Active Proposals
        $activeProposals = Proposal::where('freelancer_id', $user->id)
            ->whereIn('status', ['pending'])
            ->with(['job:id,title,currency_id', 'job.currency'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($proposal) use ($hasCurrencyCol, $user) {
                $this->convertModelCurrency($proposal, 'bid_amount', 'currency_id', $user->currency_id, (string) $proposal->created_at);
                return [
                    'id'          => $proposal->id,
                    'title'       => $proposal->job->title ?? __('freelance.unknown_job'),
                    'status'      => $proposal->status,
                    'budget'      => $proposal->bid_amount ?? $proposal->proposed_budget_points ?? 0,
                    'submittedAt' => $proposal->created_at->format('Y-m-d'),
                    'connectsUsed' => $proposal->points_spent ?? 0,
                    'currency'    => $proposal->job->currency ?? null,
                ];
            });

        // 2. Active Contracts (as freelancer)
        $contractQuery = Contract::where('freelancer_id', $user->id)
            ->where('status', 'active')
            ->with(['client:id,name', 'job:id,title,currency_id', 'job.currency'])
            ->latest()
            ->take(5);

        $activeContracts = $contractQuery->get()->map(function ($contract) use ($hasAmountCol, $user) {
            $this->convertModelCurrency($contract, $hasAmountCol ? 'amount' : 'contract_points', 'currency_id', $user->currency_id, (string) $contract->created_at);
            return [
                'id'         => $contract->id,
                'title'      => $contract->job->title ?? __('freelance.unknown_job'),
                'clientName' => $contract->client->name ?? __('freelance.unknown_client'),
                'startDate'  => $contract->created_at->format('Y-m-d'),
                'value'      => $hasAmountCol ? ($contract->amount ?? 0) : ($contract->contract_points ?? 0),
                'progress'   => $this->calculateContractProgress($contract),
                'status'     => $contract->status,
                'currency'   => $contract->job->currency ?? null,
            ];
        });

        // 3. Stats
        $totalEarnings = $hasAmountCol
            ? Contract::where('freelancer_id', $user->id)->where('status', 'completed')->sum('amount')
            : Contract::where('freelancer_id', $user->id)->where('status', 'completed')->sum('contract_points');

        // Get user's currency model (no hardcoded string fallback)
        $userCurrencyModel = $this->getUserCurrencyObject($user);
        $userCurrencyId = $userCurrencyModel->id ?? null;

        $stats = [
            'pointsBalance'   => $user->points_balance ?? 0,
            'activeProposals' => Proposal::where('freelancer_id', $user->id)->whereIn('status', ['pending'])->count(),
            'activeContracts' => Contract::where('freelancer_id', $user->id)->where('status', 'active')->count(),
            'totalEarnings'   => $totalEarnings,
            'currency'        => $userCurrencyModel->currency ?? null,
            'symbol'          => $userCurrencyModel->symbol ?? null,
            'string_format'   => $userCurrencyModel->string_format ?? null,
            'isFiat'          => $hasAmountCol,
        ];

        // 4. Recent Activities
        $activities = [];

        $recentProposals = Proposal::where('freelancer_id', $user->id)->with('job')->latest()->take(3)->get();
        foreach ($recentProposals as $prop) {
            $activities[] = [
                'id'          => 'p_' . $prop->id,
                'type'        => 'proposal',
                'description' => __('freelance.submitted_bid_for', ['job' => $prop->job->title ?? __('freelance.unknown_job')]),
                'created_at'  => $prop->created_at->toISOString(),
                'timestamp'   => $prop->created_at->timestamp,
                'color'       => 'blue',
            ];
        }

        $recentContracts = Contract::where('freelancer_id', $user->id)->with('job')->latest()->take(3)->get();
        foreach ($recentContracts as $contract) {
            $activities[] = [
                'id'          => 'c_' . $contract->id,
                'type'        => 'contract',
                'description' => __('freelance.contract_update_for', ['job' => $contract->job->title ?? __('freelance.unknown_job')]),
                'created_at'  => $contract->updated_at->toISOString(),
                'timestamp'   => $contract->updated_at->timestamp,
                'color'       => 'emerald',
            ];
        }

        $recentPoints = PointTransaction::where('user_id', $user->id)->latest()->take(3)->get();
        foreach ($recentPoints as $pt) {
            $activities[] = [
                'id'          => 'pt_' . $pt->id,
                'type'        => 'connects',
                'description' => $pt->description ?? __('freelance.points_transaction'),
                'created_at'  => $pt->created_at->toISOString(),
                'timestamp'   => $pt->created_at->timestamp,
                'color'       => 'amber',
            ];
        }

        usort($activities, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        $recentActivities = array_slice($activities, 0, 5);

        // 5. Upcoming Bookings (optional — requires Booking module subscription)
        $upcomingBookings = [];
        if (
            $user->hasModuleSubscription('booking') &&
            class_exists(\Modules\Booking\Models\Booking::class)
        ) {
            try {
                $upcomingBookings = \Modules\Booking\Models\Booking::with('eventType')
                    ->whereHas('eventType', fn($q) => $q->where('user_id', $user->id))
                    ->where('starts_at', '>=', now())
                    ->whereIn('status', ['confirmed', 'paid'])
                    ->orderBy('starts_at', 'asc')
                    ->take(5)
                    ->get();
            } catch (\Throwable $e) {
                $upcomingBookings = [];
            }
        }

        // ── CLIENT DATA ────────────────────────────────────────────────

        // 1. Client's posted jobs
        $clientJobs = Job::where('client_id', $user->id)
            ->whereIn('status', ['open'])
            ->withCount('proposals')
            ->with('currency')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($job) use ($hasCurrencyCol, $user) {
                $this->convertModelCurrency($job, 'budget', 'currency_id', $user->currency_id, (string) $job->created_at);
                return [
                    'id'             => $job->id,
                    'title'          => $job->title,
                    'status'         => $job->status,
                    'budget'         => $job->budget ?? 0,
                    'currencySymbol'  => $job->currency?->symbol,
                    'currency'        => $job->currency,
                    'proposalsCount' => $job->proposals_count ?? 0,
                    'createdAt'      => $job->created_at->format('Y-m-d'),
                ];
            });

        // 2. Client's active contracts
        $clientContracts = Contract::where('client_id', $user->id)
            ->where('status', 'active')
            ->with(['freelancer:id,name', 'job:id,title,currency_id', 'job.currency'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($contract) use ($hasAmountCol, $user) {
                $this->convertModelCurrency($contract, $hasAmountCol ? 'amount' : 'contract_points', 'currency_id', $user->currency_id, (string) $contract->created_at);
                return [
                    'id'             => $contract->id,
                    'title'          => $contract->job->title ?? __('freelance.unknown_job'),
                    'freelancerName' => $contract->freelancer->name ?? __('freelance.unknown_freelancer'),
                    'startDate'      => $contract->created_at->format('Y-m-d'),
                    'value'          => $hasAmountCol ? ($contract->amount ?? 0) : ($contract->contract_points ?? 0),
                    'progress'       => $this->calculateContractProgress($contract),
                    'status'         => $contract->status,
                    'currency'       => $contract->job->currency ?? null,
                ];
            });

        // 3. Proposals received on client's jobs
        $clientProposals = Proposal::whereHas('job', fn($q) => $q->where('client_id', $user->id))
            ->whereIn('status', ['pending'])
            ->with(['job:id,title,currency_id', 'job.currency', 'freelancer:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($proposal) use ($user) {
                $this->convertModelCurrency($proposal, 'bid_amount', 'currency_id', $user->currency_id, (string) $proposal->created_at);
                return [
                    'id'             => $proposal->id,
                    'title'          => $proposal->job->title ?? __('freelance.unknown_job'),
                    'freelancerName' => $proposal->freelancer->name ?? __('freelance.unknown_freelancer'),
                    'status'         => $proposal->status,
                    'budget'         => $proposal->bid_amount ?? $proposal->proposed_budget_points ?? 0,
                    'pointsSpent'    => $proposal->points_spent ?? 0,
                    'submittedAt'    => $proposal->created_at->format('Y-m-d'),
                    'currency'       => $proposal->job->currency ?? null,
                ];
            });

        // 4. Client stats
        $totalContractedValue = $hasAmountCol
            ? Contract::where('client_id', $user->id)->where('status', 'completed')->sum('amount')
            : Contract::where('client_id', $user->id)->where('status', 'completed')->sum('contract_points');

        // Points spent by this client = points deducted for posting jobs
        $pointsSpent = \App\Models\PointTransaction::where('user_id', $user->id)
            ->where('type', 'spent')
            ->sum('points');

        // Also count job-posting deductions (type = deducted)
        $pointsSpentFromDeductions = \App\Models\PointTransaction::where('user_id', $user->id)
            ->whereIn('type', ['spent', 'deducted', 'used'])
            ->sum('points');

        $clientStats = [
            'activeJobs'           => Job::where('client_id', $user->id)->where('status', 'open')->count(),
            'activeContracts'      => Contract::where('client_id', $user->id)->where('status', 'active')->count(),
            'totalContractedValue' => $totalContractedValue,
            'pointsSpent'          => abs((int) $pointsSpentFromDeductions),
            'receivedProposals'    => Proposal::whereHas('job', fn($q) => $q->where('client_id', $user->id))->where('status', 'pending')->count(),
            'currency'             => $userCurrencyModel->currency ?? null,
            'symbol'               => $userCurrencyModel->symbol ?? null,
            'string_format'        => $userCurrencyModel->string_format ?? null,
            'isFiat'               => $hasAmountCol,
        ];


        // 5. Client activities
        $clientActivitiesList = [];

        $recentClientJobs = Job::where('client_id', $user->id)->latest()->take(3)->get();
        foreach ($recentClientJobs as $job) {
            $clientActivitiesList[] = [
                'id'          => 'j_' . $job->id,
                'type'        => 'job',
                'description' => __('freelance.posted_job', ['title' => $job->title]),
                'created_at'  => $job->created_at->toISOString(),
                'timestamp'   => $job->created_at->timestamp,
                'color'       => 'indigo',
            ];
        }

        $recentReceivedProposals = Proposal::whereHas('job', fn($q) => $q->where('client_id', $user->id))
            ->with(['job', 'freelancer'])
            ->latest()
            ->take(3)
            ->get();
        foreach ($recentReceivedProposals as $prop) {
            $clientActivitiesList[] = [
                'id'          => 'pr_' . $prop->id,
                'type'        => 'proposal_received',
                'description' => __('freelance.freelancer_bid_on', [
                    'freelancer' => $prop->freelancer->name ?? __('freelance.unknown_freelancer'),
                    'job'        => $prop->job->title ?? __('freelance.unknown_job'),
                ]),
                'created_at'  => $prop->created_at->toISOString(),
                'timestamp'   => $prop->created_at->timestamp,
                'color'       => 'blue',
            ];
        }

        $recentClientContracts = Contract::where('client_id', $user->id)->with('job')->latest()->take(3)->get();
        foreach ($recentClientContracts as $contract) {
            $clientActivitiesList[] = [
                'id'          => 'cc_' . $contract->id,
                'type'        => 'contract',
                'description' => __('freelance.contract_update_for', ['job' => $contract->job->title ?? __('freelance.unknown_job')]),
                'created_at'  => $contract->updated_at->toISOString(),
                'timestamp'   => $contract->updated_at->timestamp,
                'color'       => 'emerald',
            ];
        }

        usort($clientActivitiesList, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        $clientActivities = array_slice($clientActivitiesList, 0, 5);

        return Inertia::render('Freelance/Dashboard', [
            'activeProposals'  => $activeProposals,
            'activeContracts'  => $activeContracts,
            'stats'            => $stats,
            'recentActivities' => $recentActivities,
            'upcomingBookings' => $upcomingBookings,
            'clientData'       => [
                'activeJobs'         => $clientJobs,
                'activeContracts'    => $clientContracts,
                'receivedProposals'  => $clientProposals,
                'stats'              => $clientStats,
                'recentActivities'   => $clientActivities,
            ],
            'userCurrency'     => $this->currencyForFrontend($userCurrencyId),
        ]);
    }

    private function calculateContractProgress($contract): int
    {
        if ($contract->status === 'completed') return 100;
        if (in_array($contract->status, ['terminated', 'canceled'])) return 0;
        return 25;
    }
}

