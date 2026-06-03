<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\Invoice;
use Illuminate\Foundation\Auth\User;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class InvoicePolicy
{
    use HandlesAuthorization;

    protected function getTenant(User $user)
    {
        return $user->tenant;
    }

    public function viewAny(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function view(User $user, Invoice $invoice)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $invoice->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Invoice $invoice)
    {
        $tenant = $this->getTenant($user);
        if (!$tenant || $invoice->tenant_id !== $tenant->id) {
            return false;
        }
        
        // Cannot edit finalized invoices
        if (in_array($invoice->status, ['paid', 'cancelled', 'refunded'])) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Invoice $invoice)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $invoice->tenant_id === $tenant->id;
    }
}
