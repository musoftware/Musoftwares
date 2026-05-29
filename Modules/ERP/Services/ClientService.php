<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use App\Models\Currency;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\Activity;
use Illuminate\Database\Eloquent\Collection;

class ClientService
{
    public function createClient(array $validated, Tenant $tenant): TenantClient
    {
        $currencyCode = $validated['currency'] ?? null;
        if (!$currencyCode) {
            $currencyCode = $tenant->baseCurrency?->currency;
        }
        $currencyId = Currency::where('currency', $currencyCode)->value('id');

        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'],
            'email'     => $validated['email'] ?? null,
            'phone'     => $validated['phone'] ?? null,
            'address'   => $validated['address'] ?? null,
            'currency_id' => $currencyId,
        ]);

        ActivityLogger::log(
            'client_created',
            "Client '{$client->name}' was added.",
            $client,
            $client->id
        );

        return $client;
    }

    public function updateClient(TenantClient $client, array $validated): TenantClient
    {
        $validated['currency_id'] = Currency::where('currency', $validated['currency'])->value('id');
        unset($validated['currency']);

        $client->update($validated);

        ActivityLogger::log(
            'client_updated',
            "Client '{$client->name}' profile was updated.",
            $client,
            $client->id
        );

        return $client;
    }

    public function updateStatus(TenantClient $client, string $status): TenantClient
    {
        $oldStatus = $client->status;
        $client->update(['status' => $status]);

        ActivityLogger::log(
            'client_status_changed',
            "Client status changed from {$oldStatus} to {$client->status}.",
            $client,
            $client->id
        );

        return $client;
    }

    public function deleteClient(TenantClient $client, int $tenantId): void
    {
        $hasOpenInvoices = $client->invoices()->whereNotIn('status', ['cancelled', 'paid'])->exists();
        if ($hasOpenInvoices) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'client' => __('erp.cannot_delete_client_open_invoices')
            ]);
        }

        $clientName = $client->name;
        $client->delete();

        ActivityLogger::log(
            'client_deleted',
            "Client '{$clientName}' was deleted.",
            null,
            null,
            ['tenant_id' => $tenantId]
        );
    }

    public function getOperationalData(TenantClient $client, bool $hasTickets): array
    {
        $client->load(['projects', 'currency']);

        if ($hasTickets) {
            $client->load('tickets');
        }

        $invoices = Invoice::where('client_id', $client->id)->latest()->get();
        
        $activities = Activity::where('subject_type', TenantClient::class)
            ->where('subject_id', $client->id)
            ->with('causer')
            ->latest()
            ->get();

        return [
            'client' => $client,
            'projects' => $client->projects,
            'tickets' => $hasTickets ? $client->tickets : [],
            'invoices' => $invoices,
            'activities' => $activities,
            'balance' => $client->balance(),
            'lockedBalance' => $client->lockedBalance(),
        ];
    }

    public function searchClients(int $tenantId, ?string $term, int $limit): Collection
    {
        $query = TenantClient::with('currency')
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'email', 'currency_id');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('name')->limit($limit)->get();
    }
}
