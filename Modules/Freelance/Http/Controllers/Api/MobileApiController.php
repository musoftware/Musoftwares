<?php

namespace Modules\Freelance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\ProposalOffer;
use Illuminate\Support\Facades\Auth;

class MobileApiController extends Controller
{
    public function getJobs(Request $request)
    {
        $query = Job::query()->with(['skills', 'client']);

        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Add distance filter if lat/lng are provided
        if ($request->has('latitude') && $request->has('longitude') && $request->has('radius')) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->radius; // in kilometers
            
            // Haversine formula
            $query->selectRaw("*, ( 6371 * acos( cos( radians(?) ) *
                cos( radians( latitude ) ) *
                cos( radians( longitude ) - radians(?) ) +
                sin( radians(?) ) *
                sin( radians( latitude ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<', $radius)
                ->orderBy('distance');
        } else {
            $query->latest();
        }

        return response()->json($query->paginate(20));
    }

    public function storeJob(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'service_type' => 'required|in:visit,remote',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $job = Job::create([
            'client_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'service_type' => $request->service_type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'open',
            // Default currency logic should be handled here based on existing project rules
        ]);

        return response()->json(['message' => 'Job created successfully', 'job' => $job]);
    }

    public function negotiateProposal(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $proposal = Proposal::findOrFail($id);

        $offer = ProposalOffer::create([
            'proposal_id' => $proposal->id,
            'offered_by_user_id' => Auth::id(),
            'amount' => $request->amount,
            'currency_id' => $proposal->currency_id,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Offer submitted successfully', 'offer' => $offer]);
    }

    public function acceptProposal(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);
        
        // Mark proposal as accepted
        $proposal->update(['status' => 'accepted']);
        
        // Reject other offers
        ProposalOffer::where('proposal_id', $proposal->id)
            ->where('status', 'pending')
            ->update(['status' => 'superseded']);

        return response()->json(['message' => 'Proposal accepted successfully']);
    }

    public function rejectProposal(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->update(['status' => 'rejected']);

        return response()->json(['message' => 'Proposal rejected']);
    }

    public function getMyJobs(Request $request)
    {
        $jobs = Job::with(['currency'])
            ->where('client_id', Auth::id())
            ->withCount('proposals')
            ->latest()
            ->paginate(20);

        return response()->json($jobs);
    }

    public function getJobDetail(Request $request, $id)
    {
        $job = Job::with(['client', 'skills', 'currency', 'proposals.freelancer'])
            ->findOrFail($id);

        return response()->json($job);
    }

    public function getNegotiations(Request $request)
    {
        $userId = Auth::id();

        // Get proposals where user is either client (via job) or freelancer
        $proposals = Proposal::with(['job', 'freelancer', 'job.client', 'currency', 'offers'])
            ->where(function ($q) use ($userId) {
                $q->where('freelancer_id', $userId)
                  ->orWhereHas('job', fn($jq) => $jq->where('client_id', $userId));
            })
            ->whereIn('status', ['pending', 'accepted', 'negotiating'])
            ->latest()
            ->get();

        $negotiations = $proposals->map(function ($proposal) use ($userId) {
            $isFreelancer = $proposal->freelancer_id === $userId;
            $otherUser = $isFreelancer ? $proposal->job->client : $proposal->freelancer;
            $lastOffer = $proposal->offers->sortByDesc('created_at')->first();

            return [
                'id'                => $proposal->id,
                'job_title'         => $proposal->job->title ?? '',
                'last_offer_amount' => $lastOffer?->amount ?? $proposal->bid_amount,
                'last_offer_by'     => $lastOffer ? ($lastOffer->offered_by_user_id === $userId ? 'me' : 'other') : 'other',
                'status'            => $proposal->status,
                'updated_at'        => $proposal->updated_at,
                'currency'          => $proposal->currency ? [
                    'symbol'   => $proposal->currency->symbol,
                    'currency' => $proposal->currency->currency,
                ] : null,
                'other_user' => $otherUser ? [
                    'name' => $otherUser->name,
                    'role' => $isFreelancer ? 'client' : 'freelancer',
                ] : ['name' => 'Unknown', 'role' => 'unknown'],
            ];
        });

        return response()->json($negotiations->values());
    }
}
