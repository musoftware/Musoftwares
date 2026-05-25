<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'amount'          => (float) $this->amount,
            'currency_id'     => $this->currency,
            'business_amount'      => (float) $this->business_amount,
            'business_currency_id' => 1,
            'reason'          => $this->reason,
            'status'          => $this->status ?? 'completed', // fallback if status doesn't exist
            'is_reversed'     => $this->is_reversed ?? false,
            'user'            => $this->whenLoaded('user', function () {
                return [
                    'id'    => $this->user->id,
                    'name'  => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'project'         => $this->whenLoaded('project', function () {
                return [
                    'id'           => $this->project->id,
                    'project_name' => $this->project->project_name,
                ];
            }),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
