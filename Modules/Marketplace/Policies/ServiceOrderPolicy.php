<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\ServiceOrder;

class ServiceOrderPolicy
{
    public function view(User $user, ServiceOrder $order): bool
    {
        return $user->id === $order->buyer_id || $user->id === $order->seller_id;
    }

    public function complete(User $user, ServiceOrder $order): bool
    {
        return $user->id === $order->buyer_id;
    }

    public function deliver(User $user, ServiceOrder $order): bool
    {
        return $user->id === $order->seller_id;
    }

    public function dispute(User $user, ServiceOrder $order): bool
    {
        return $user->id === $order->buyer_id || $user->id === $order->seller_id;
    }
}
