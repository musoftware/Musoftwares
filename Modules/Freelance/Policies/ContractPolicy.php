<?php

namespace Modules\Freelance\Policies;

use App\Models\User;
use Modules\Freelance\Models\Contract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Contract $contract)
    {
        return $user->id === $contract->client_id || $user->id === $contract->freelancer_id;
    }

    public function complete(User $user, Contract $contract)
    {
        return $user->id === $contract->client_id;
    }

    public function dispute(User $user, Contract $contract)
    {
        return $user->id === $contract->client_id || $user->id === $contract->freelancer_id;
    }
}
