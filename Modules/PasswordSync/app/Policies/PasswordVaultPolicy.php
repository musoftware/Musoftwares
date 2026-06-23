<?php

namespace Modules\PasswordSync\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class PasswordVaultPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
}
