<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Users\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KycController extends Controller
{
    /**
     * Show the KYC verification page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's uploaded documents
        $documents = $user->kycDocuments()->latest()->get()->map(function($doc) {
            return [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'original_filename' => $doc->original_filename,
                'file_size' => $doc->file_size,
                'status' => $doc->status,
                'rejection_reason' => $doc->rejection_reason,
                'created_at' => $doc->created_at->diffForHumans(),
            ];
        });
        
        // Check if all required documents are uploaded
        $requiredDocs = ['id_front', 'selfie'];
        $uploadedTypes = $documents->pluck('document_type')->toArray();
        $missingDocs = array_values(array_diff($requiredDocs, $uploadedTypes));
        
        return Inertia::render('Client/Kyc/Index', [
            'kycStatus' => [
                'isVerified' => (bool) $user->kyc_verified,
                'verifiedAt' => $user->kyc_verified_at,
                'provider' => $user->kyc_provider,
                'notes' => $user->kyc_notes,
            ],
            'documents' => $documents,
            'missingDocs' => $missingDocs,
            'requiredDocs' => $requiredDocs,
        ]);
    }
    
    /**
     * Upload KYC document
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:id_front,id_back,selfie,proof_of_address',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        $user = Auth::user();

        if ($user->kyc_verified) {
            return back()->with('error', 'You are already KYC verified.');
        }

        // Check if document type already exists
        $existing = KycDocument::where('user_id', $user->id)
            ->where('document_type', $request->document_type)
            ->first();

        if ($existing) {
            // Delete old file
            Storage::disk('private')->delete($existing->file_path);
            $existing->delete();
        }

        // Store the file
        $file = $request->file('document');
        $filename = $user->id . '_' . $request->document_type . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('kyc_documents', $filename, 'private');

        // Create document record
        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Document uploaded successfully. Please submit for review when ready.');
    }

    /**
     * Submit KYC for review
     */
    public function submit(Request $request)
    {
        $user = Auth::user();

        if ($user->kyc_verified) {
            return back()->with('error', 'You are already KYC verified.');
        }

        // Check if all required documents are uploaded
        $requiredDocs = ['id_front', 'selfie'];
        $uploadedDocs = $user->kycDocuments()->pluck('document_type')->toArray();
        $missingDocs = array_diff($requiredDocs, $uploadedDocs);

        if (!empty($missingDocs)) {
            return back()->withErrors(['kyc' => 'You must upload all required documents (ID Front, Selfie) before submitting.']);
        }

        // Update user to indicate KYC submission
        $user->update([
            'kyc_provider' => 'Self-Hosted',
            'kyc_notes' => 'KYC documents submitted for review on ' . now()->format('Y-m-d H:i:s'),
        ]);

        return back()->with('success', 'KYC application submitted successfully. We will review it shortly.');
    }

    /**
     * Delete a document
     */
    public function deleteDocument($id)
    {
        $document = KycDocument::findOrFail($id);
        
        // Security check
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        if ($document->status === 'approved') {
            return back()->with('error', 'Cannot delete an approved document.');
        }

        // Delete file from storage
        Storage::disk('private')->delete($document->file_path);
        
        // Delete record
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Download document (for users and admins)
     */
    public function downloadDocument($id)
    {
        $document = KycDocument::findOrFail($id);

        // Security check (only owner or admin)
        if ($document->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        return Storage::disk('private')->download($document->file_path, $document->original_filename);
    }
}
