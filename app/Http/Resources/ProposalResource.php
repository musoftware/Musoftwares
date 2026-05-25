<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'project_name'        => $this->project_name,
            'project_details'     => $this->project_details,
            'total_cost_egp'      => (float) $this->total_cost_egp,
            'total_duration_days' => $this->total_duration_days,
            'cost_breakdown'      => $this->cost_breakdown,
            'proposal_data'       => $this->proposal_data,
            'ascii_table'         => $this->ascii_table,
            'status'              => $this->status,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
