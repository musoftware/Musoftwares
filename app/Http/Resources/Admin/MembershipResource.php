<?php

namespace App\Http\Resources\Admin;

use App\Models\MembershipProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'currency_id' => $this->currency,
            'color_hue_degree' => $this->color_hue_degree,
            'is_active' => (bool) $this->is_active,
            'software' => $this->whenLoaded('programs', function () {
                return $this->programs->map(function (MembershipProgram $program) {
                    return [
                        'id' => $program->software_program_id,
                        'name' => $program->program?->name,
                    ];
                });
            }),
            'users_count' => $this->users_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
