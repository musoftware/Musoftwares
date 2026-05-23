<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Proposal;
use App\Models\PointTransaction;
use Modules\Freelance\Models\Job;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Fetch Real Active Proposals
        $activeProposals = Proposal::where('freelancer_id', $user->id)
            ->whereIn('status', ['pending'])
            ->with('job:id,title')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($proposal) {
                return [
                    'id' => $proposal->id,
                    'title' => $proposal->job->title ?? 'Unknown Job',
                    'status' => $proposal->status,
                    'budget' => $proposal->bid_amount ?? 0,
                    'submittedAt' => $proposal->created_at->format('Y-m-d'),
                    'connectsUsed' => 2, // Default or fetch from transaction if linked
                ];
            });

        // 2. Fetch Real Active Contracts
        $activeContracts = Contract::where('freelancer_id', $user->id)
            ->where('status', 'active')
            ->with(['client:id,name', 'job:id,title'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'title' => $contract->job->title ?? 'Unknown Job',
                    'clientName' => $contract->client->name ?? 'Unknown Client',
                    'startDate' => $contract->created_at->format('Y-m-d'),
                    'value' => $contract->amount ?? 0,
                    'progress' => $this->calculateContractProgress($contract),
                    'status' => $contract->status,
                ];
            });

        // 3. Compute Real Stats
        $stats = [
            'pointsBalance' => $user->points_balance ?? 0,
            'activeProposals' => Proposal::where('freelancer_id', $user->id)->whereIn('status', ['pending'])->count(),
            'activeContracts' => Contract::where('freelancer_id', $user->id)->where('status', 'active')->count(),
            'totalEarnings' => Contract::where('freelancer_id', $user->id)->where('status', 'completed')->sum('amount'),
            'currency' => $user->preferred_currency ?? 'USD',
        ];

        // 4. Compute Real Recent Activities
        $activities = [];

        $recentProposals = Proposal::where('freelancer_id', $user->id)->with('job')->latest()->take(3)->get();
        foreach ($recentProposals as $prop) {
            $activities[] = [
                'id' => 'p_'.$prop->id,
                'type' => 'proposal',
                'text' => 'Submitted bid for "' . ($prop->job->title ?? 'Unknown') . '"',
                'time' => $prop->created_at->diffForHumans(),
                'timestamp' => $prop->created_at->timestamp,
                'color' => 'text-blue-500 bg-blue-50'
            ];
        }

        $recentContracts = Contract::where('freelancer_id', $user->id)->with('job')->latest()->take(3)->get();
        foreach ($recentContracts as $contract) {
            $statusText = $contract->status === 'active' ? 'Started contract for' : 'Contract update for';
            $activities[] = [
                'id' => 'c_'.$contract->id,
                'type' => 'contract',
                'text' => $statusText . ' "' . ($contract->job->title ?? 'Unknown') . '"',
                'time' => $contract->updated_at->diffForHumans(),
                'timestamp' => $contract->updated_at->timestamp,
                'color' => 'text-emerald-500 bg-emerald-50'
            ];
        }

        $recentPoints = PointTransaction::where('user_id', $user->id)->latest()->take(3)->get();
        foreach ($recentPoints as $pt) {
            $activities[] = [
                'id' => 'pt_'.$pt->id,
                'type' => 'connects',
                'text' => $pt->description ?? 'Connects transaction',
                'time' => $pt->created_at->diffForHumans(),
                'timestamp' => $pt->created_at->timestamp,
                'color' => 'text-amber-500 bg-amber-50'
            ];
        }

        // Sort activities by timestamp descending
        usort($activities, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $recentActivities = array_slice($activities, 0, 5);

        // 5. Fetch upcoming bookings for Dashboard widgets
        $upcomingBookings = \Modules\Booking\Models\Booking::with('eventType')
            ->whereHas('eventType', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('starts_at', '>=', now())
            ->whereIn('status', ['confirmed', 'paid'])
            ->orderBy('starts_at', 'asc')
            ->take(5)
            ->get();

        // --- CLIENT DATA FETCHING ---
        // 1. Fetch posted jobs by this client (open ones)
        $clientJobs = Job::where('client_id', $user->id)
            ->whereIn('status', ['open'])
            ->withCount('proposals')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                    'budget' => $job->budget ?? 0,
                    'currency' => $job->currency_code ?? 'USD',
                    'proposalsCount' => $job->proposals_count ?? 0,
                    'createdAt' => $job->created_at->format('Y-m-d'),
                ];
            });

        // 2. Fetch contracts where this user is the client
        $clientContracts = Contract::where('client_id', $user->id)
            ->where('status', 'active')
            ->with(['freelancer:id,name', 'job:id,title'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'title' => $contract->job->title ?? 'Unknown Job',
                    'freelancerName' => $contract->freelancer->name ?? 'Unknown Freelancer',
                    'startDate' => $contract->created_at->format('Y-m-d'),
                    'value' => $contract->amount ?? 0,
                    'progress' => $this->calculateContractProgress($contract),
                    'status' => $contract->status,
                ];
            });

        // 3. Fetch proposals received on jobs posted by this client
        $clientProposals = Proposal::whereHas('job', function($q) use ($user) {
                $q->where('client_id', $user->id);
            })
            ->whereIn('status', ['pending'])
            ->with(['job:id,title', 'freelancer:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($proposal) {
                return [
                    'id' => $proposal->id,
                    'title' => $proposal->job->title ?? 'Unknown Job',
                    'freelancerName' => $proposal->freelancer->name ?? 'Unknown Freelancer',
                    'status' => $proposal->status,
                    'budget' => $proposal->bid_amount ?? 0,
                    'submittedAt' => $proposal->created_at->format('Y-m-d'),
                ];
            });

        // 4. Compute Client-specific Stats
        $clientStats = [
            'activeJobs' => Job::where('client_id', $user->id)->where('status', 'open')->count(),
            'activeContracts' => Contract::where('client_id', $user->id)->where('status', 'active')->count(),
            'totalSpend' => Contract::where('client_id', $user->id)->where('status', 'completed')->sum('amount'),
            'receivedProposals' => Proposal::whereHas('job', function($q) use ($user) {
                    $q->where('client_id', $user->id);
                })->where('status', 'pending')->count(),
            'currency' => $user->preferred_currency ?? 'USD',
        ];

        // 5. Compute Client-specific recent activities
        $clientActivitiesList = [];

        $recentClientJobs = Job::where('client_id', $user->id)->latest()->take(3)->get();
        foreach ($recentClientJobs as $job) {
            $clientActivitiesList[] = [
                'id' => 'j_'.$job->id,
                'type' => 'job',
                'text' => 'Posted job "' . $job->title . '"',
                'time' => $job->created_at->diffForHumans(),
                'timestamp' => $job->created_at->timestamp,
                'color' => 'text-indigo-500 bg-indigo-50'
            ];
        }

        $recentReceivedProposals = Proposal::whereHas('job', function($q) use ($user) {
                $q->where('client_id', $user->id);
            })->with(['job', 'freelancer'])->latest()->take(3)->get();
        foreach ($recentReceivedProposals as $prop) {
            $clientActivitiesList[] = [
                'id' => 'pr_'.$prop->id,
                'type' => 'proposal_received',
                'text' => ($prop->freelancer->name ?? 'A freelancer') . ' bid on "' . ($prop->job->title ?? 'Unknown') . '"',
                'time' => $prop->created_at->diffForHumans(),
                'timestamp' => $prop->created_at->timestamp,
                'color' => 'text-blue-500 bg-blue-50'
            ];
        }

        $recentClientContracts = Contract::where('client_id', $user->id)->with('job')->latest()->take(3)->get();
        foreach ($recentClientContracts as $contract) {
            $statusText = $contract->status === 'active' ? 'Started contract for' : 'Contract update for';
            $clientActivitiesList[] = [
                'id' => 'cc_'.$contract->id,
                'type' => 'contract',
                'text' => $statusText . ' "' . ($contract->job->title ?? 'Unknown') . '"',
                'time' => $contract->updated_at->diffForHumans(),
                'timestamp' => $contract->updated_at->timestamp,
                'color' => 'text-emerald-500 bg-emerald-50'
            ];
        }

        usort($clientActivitiesList, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $clientActivities = array_slice($clientActivitiesList, 0, 5);

        return Inertia::render('Freelance/Dashboard', [
            'activeProposals' => $activeProposals,
            'activeContracts' => $activeContracts,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'upcomingBookings' => $upcomingBookings,
            
            // Client data
            'clientData' => [
                'activeJobs' => $clientJobs,
                'activeContracts' => $clientContracts,
                'receivedProposals' => $clientProposals,
                'stats' => $clientStats,
                'recentActivities' => $clientActivities,
            ]
        ]);
    }

    private function calculateContractProgress($contract)
    {
        if ($contract->status === 'completed') {
            return 100;
        }
        if (in_array($contract->status, ['terminated', 'canceled'])) {
            return 0;
        }
        return 25; // Default for in-progress contract
    }
}
