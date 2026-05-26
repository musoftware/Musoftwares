<?php

namespace App\Modules\CRMWhatsAppCampaigns\Services;

use Modules\CRM\Models\Lead;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Modules\CRM\Models\WhatsAppCampaignAudienceMember;
use Illuminate\Support\Facades\DB;

class CampaignAudienceResolver
{
    /**
     * Resolve audience members based on audience filters.
     *
     * @return int Number of resolved members
     */
    public function resolve(WhatsAppCampaignAudience $audience): int
    {
        // Clear existing members if dynamic
        if ($audience->is_dynamic) {
            $audience->members()->delete();
        }

        $count = match ($audience->source_type) {
            'leads'     => $this->resolveFromLeads($audience),
            'customers' => $this->resolveFromCustomers($audience),
            'manual'    => 0, // Manual audiences are pre-populated
            default     => 0,
        };

        $audience->update([
            'resolved_size'    => $count,
            'estimated_size'   => $count,
            'last_resolved_at' => now(),
        ]);

        return $count;
    }

    /**
     * Resolve audience members from leads.
     */
    protected function resolveFromLeads(WhatsAppCampaignAudience $audience): int
    {
        $query = Lead::withoutGlobalScopes()
            ->where('workspace_id', $audience->workspace_id)
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        $this->applyFilters($query, $audience->filters ?? []);
        $this->applySuppressionRules($query, $audience);

        $count = 0;

        $query->cursor()->each(function (Lead $lead) use ($audience, &$count) {
            // Deduplicate by phone
            $exists = WhatsAppCampaignAudienceMember::where('audience_id', $audience->id)
                ->where('phone', $lead->phone)->exists();

            if ($exists) return;

            WhatsAppCampaignAudienceMember::create([
                'workspace_id'     => $audience->workspace_id,
                'audience_id'      => $audience->id,
                'phone'            => $lead->phone,
                'name'             => $lead->name,
                'email'            => $lead->email,
                'contactable_type' => Lead::class,
                'contactable_id'   => $lead->id,
                'merge_data'       => $this->buildMergeData($lead),
            ]);

            $count++;
        });

        return $count;
    }

    /**
     * Resolve audience from customers (fallback to leads with status = converted).
     */
    protected function resolveFromCustomers(WhatsAppCampaignAudience $audience): int
    {
        // Use leads with 'converted' or 'won' status as customers
        $filters = array_merge($audience->filters ?? [], [
            ['field' => 'status', 'operator' => 'in', 'value' => ['converted', 'won', 'customer']],
        ]);

        $audience = $audience->replicate();
        $audience->filters = $filters;

        return $this->resolveFromLeads($audience);
    }

    /**
     * Apply filter conditions to the query.
     */
    protected function applyFilters($query, array $filters): void
    {
        foreach ($filters as $filter) {
            $field    = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? 'eq';
            $value    = $filter['value'] ?? null;

            if (!$field || $value === null) continue;

            // Handle custom_data JSON fields
            if (str_starts_with($field, 'custom_data.')) {
                $jsonKey = str_replace('custom_data.', '', $field);
                $field = "custom_data->{$jsonKey}";
            }

            match ($operator) {
                'eq', '='         => $query->where($field, $value),
                'neq', '!='      => $query->where($field, '!=', $value),
                'in'              => $query->whereIn($field, (array) $value),
                'not_in'          => $query->whereNotIn($field, (array) $value),
                'contains', 'like' => $query->where($field, 'like', "%{$value}%"),
                'starts_with'     => $query->where($field, 'like', "{$value}%"),
                'gt', '>'         => $query->where($field, '>', $value),
                'lt', '<'         => $query->where($field, '<', $value),
                'gte', '>='       => $query->where($field, '>=', $value),
                'lte', '<='       => $query->where($field, '<=', $value),
                'is_null'         => $query->whereNull($field),
                'is_not_null'     => $query->whereNotNull($field),
                'between'         => $query->whereBetween($field, (array) $value),
                'date_before'     => $query->where($field, '<', $value),
                'date_after'      => $query->where($field, '>', $value),
                'has_tag'         => $query->whereHas('tags', fn($q) => $q->where('name', $value)),
                'in_lead_set'     => $query->whereHas('leadSets', fn($q) => $q->where('lead_sets.id', $value)),
                default           => null,
            };
        }
    }

    /**
     * Apply suppression rules.
     */
    protected function applySuppressionRules($query, WhatsAppCampaignAudience $audience): void
    {
        $rules = $audience->suppression_rules ?? [];

        foreach ($rules as $rule) {
            $type = $rule['type'] ?? null;

            match ($type) {
                'recently_contacted' => $query->where(function ($q) use ($rule) {
                    $days = $rule['days'] ?? 7;
                    $q->where('updated_at', '<', now()->subDays($days))
                      ->orWhereNull('updated_at');
                }),
                'opted_out' => $query->where(function ($q) {
                    // Exclude leads that opted out via WhatsApp
                    $q->whereDoesntHave('tags', fn($tq) => $tq->where('name', 'wa-opted-out'));
                }),
                'no_phone' => $query->whereNotNull('phone')->where('phone', '!=', ''),
                default => null,
            };
        }
    }

    /**
     * Build merge data from a lead for personalization.
     */
    protected function buildMergeData(Lead $lead): array
    {
        return [
            'customer_name' => $lead->name,
            'lead_name'     => $lead->name,
            'company_name'  => $lead->company,
            'phone'         => $lead->phone,
            'email'         => $lead->email,
            'source'        => $lead->source,
            'status'        => $lead->status,
            'locale'        => $lead->locale,
            'custom_data'   => $lead->custom_data ?? [],
        ];
    }

    /**
     * Preview audience size without actually resolving members.
     */
    public function preview(int $workspaceId, array $filters, string $sourceType = 'leads'): int
    {
        $query = Lead::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Get audience member data for a specific contact.
     */
    public function getMergeDataForPhone(WhatsAppCampaignAudience $audience, string $phone): array
    {
        $member = $audience->members()->where('phone', $phone)->first();
        return $member?->merge_data ?? [];
    }
}
