<?php

namespace App\Policies;

use App\Models\PaymentLink;
use App\Models\User;

class PaymentLinkPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'superadmin', 'Admin', 'accountant']);
    }

    public function view(User $user, PaymentLink $paymentLink): bool
    {
        return $paymentLink->user_id === $user->id || $paymentLink->client_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, PaymentLink $paymentLink): bool
    {
        if ($paymentLink->status === PaymentLink::STATUS_PAID) {
            return false;
        }

        return $paymentLink->user_id === $user->id;
    }

    public function delete(User $user, PaymentLink $paymentLink): bool
    {
        return $paymentLink->user_id === $user->id;
    }

    public function cancel(User $user, PaymentLink $paymentLink): bool
    {
        return $paymentLink->user_id === $user->id && $paymentLink->status === PaymentLink::STATUS_PENDING;
    }

    public function forceMarkPaid(User $user, PaymentLink $paymentLink): bool
    {
        return $user->hasAnyRole(['super_admin', 'superadmin']);
    }
}