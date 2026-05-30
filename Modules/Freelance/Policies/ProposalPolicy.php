<?php

namespace Modules\Freelance\Policies;

use App\Models\User;
use Modules\Freelance\Models\Proposal;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProposalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Proposal $proposal)
    {
        return $user->id === $proposal->freelancer_id || $user->id === $proposal->job->client_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Proposal $proposal)
    {
        return $user->id === $proposal->freelancer_id;
    }

    public function delete(User $user, Proposal $proposal)
    {
        return $user->id === $proposal->freelancer_id;
    }

    public function accept(User $user, Proposal $proposal)
    {
        return $user->id === $proposal->job->client_id;
    }

    public function reject(User $user, Proposal $proposal)
    {
        return $user->id === $proposal->job->client_id;
    }

    public function withdraw(User $user, Proposal $proposal)
    {
        return $user->id === $proposal->freelancer_id;
    }
}
