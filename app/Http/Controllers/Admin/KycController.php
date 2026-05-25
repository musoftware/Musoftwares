<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\KycDocument;
use App\Services\KycService;
use App\Http\Requests\Admin\Kyc\RejectKycRequest;
use App\Http\Resources\KycUserResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}
    public function index()
    {
        // Get users with pending KYC documents or pending review note
        $users = User::whereHas('kycDocuments', function($q) {
                $q->where('status', 'pending');
            })
            ->orWhere(function($q) {
                $q->where('kyc_verified', false)
                  ->whereNotNull('kyc_notes');
            })
            ->with(['kycDocuments' => function($q) {
                $q->latest();
            }])
            ->paginate(15)
            ->through(fn($u) => clone (new KycUserResource($u))->resolve());

        return Inertia::render('Admin/Kyc/Index', [
            'users' => $users,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $this->kycService->approveKyc($user, $request->user()->id);

        return back()->with('success', "User {$user->name} has been KYC verified successfully.");
    }

    public function reject(RejectKycRequest $request, $id)
    {
        $user = User::findOrFail($id);
        
        $this->kycService->rejectKyc($user, $request->validated('reason'));

        return back()->with('success', "User {$user->name} KYC has been rejected.");
    }
}
