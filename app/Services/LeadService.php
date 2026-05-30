<?php

namespace App\Services;

use Modules\CRM\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function updateStatus(Lead $lead, string $status): void
    {
        DB::transaction(function () use ($lead, $status) {
            $lead->status = $status;
            $lead->save();
        });
    }

    public function deleteLead(Lead $lead): void
    {
        DB::transaction(fn() => $lead->delete());
    }
}
