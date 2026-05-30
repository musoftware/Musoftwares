<?php

namespace Modules\Freelance\Policies;

use App\Models\User;
use Modules\Freelance\Models\Job;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Job $job)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Job $job)
    {
        return $user->id === $job->client_id;
    }

    public function delete(User $user, Job $job)
    {
        return $user->id === $job->client_id;
    }
}
