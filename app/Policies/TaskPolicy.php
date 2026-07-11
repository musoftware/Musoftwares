<?php

namespace App\Policies;

use App\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Task $task)
    {
        // Simple check for project access or similar. For now return true to allow access control at controller level
        // if relations exist. Assuming task visibility can be complex.
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Task $task)
    {
        return true;
    }

    public function delete(User $user, Task $task)
    {
        return true; // Simplified for now.
    }
}
