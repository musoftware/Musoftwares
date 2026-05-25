<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KycUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'kyc_status'   => $this->kyc_verified ? 'verified' : ($this->kyc_notes ? 'pending_review' : 'unverified'),
            'submitted_at' => clone $this->updated_at,
            'documents'    => $this->kycDocuments->map(function ($doc) {
                return [
                    'id'       => $doc->id,
                    'type'     => $doc->document_type,
                    'status'   => $doc->status,
                    'filename' => $doc->original_filename,
                ];
            }),
        ];
    }
}
