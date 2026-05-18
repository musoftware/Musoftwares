<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\PointTransaction;
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

        return Inertia::render('Freelance/Dashboard', [
            'activeProposals' => $activeProposals,
            'activeContracts' => $activeContracts,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'upcomingBookings' => $upcomingBookings
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
