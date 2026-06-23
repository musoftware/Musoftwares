<?php

namespace App\Policies;

use App\Models\Ticket;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin'])) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user)
    {
        return false; // Users shouldn't view ANY tickets unless they are admin (handled by before)
    }

    public function view(User $user, Ticket $ticket)
    {
        return isset($ticket->user_id) && $user->id === $ticket->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Ticket $ticket)
    {
        return isset($ticket->user_id) && $user->id === $ticket->user_id;
    }

    public function delete(User $user, Ticket $ticket)
    {
        return isset($ticket->user_id) && $user->id === $ticket->user_id;
    }
}
