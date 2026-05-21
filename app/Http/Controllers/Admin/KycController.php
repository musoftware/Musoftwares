<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Core\Models\KycDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KycController extends Controller
{
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
            ->through(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'kyc_status' => $user->kyc_verified ? 'verified' : ($user->kyc_notes ? 'pending_review' : 'unverified'),
                    'submitted_at' => clone $user->updated_at,
                    'documents' => $user->kycDocuments->map(function($doc) {
                        return [
                            'id' => $doc->id,
                            'type' => $doc->document_type,
                            'status' => $doc->status,
                            'filename' => $doc->original_filename,
                        ];
                    }),
                ];
            });

        return Inertia::render('Admin/Kyc/Index', [
            'users' => $users,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'kyc_verified' => true,
            'kyc_verified_at' => now(),
            'kyc_verified_by' => $request->user()->id,
            'kyc_notes' => 'KYC approved by Admin on ' . now()->format('Y-m-d H:i:s'),
        ]);

        // Mark all pending docs as approved
        $user->kycDocuments()->where('status', 'pending')->update(['status' => 'approved']);

        return back()->with('success', "User {$user->name} has been KYC verified successfully.");
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($id);
        
        $user->update([
            'kyc_verified' => false,
            'kyc_verified_at' => null,
            'kyc_notes' => 'KYC rejected: ' . $request->reason,
        ]);

        // Mark all pending docs as rejected
        $user->kycDocuments()->where('status', 'pending')->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        return back()->with('success', "User {$user->name} KYC has been rejected.");
    }
}
