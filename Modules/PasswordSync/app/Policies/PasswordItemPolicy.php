<?php

namespace Modules\PasswordSync\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class PasswordItemPolicy
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
