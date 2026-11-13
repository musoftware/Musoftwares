<?php

namespace Modules\AppBuilder\Tests;

use App\Models\User;

/** The AI builder opens only for accounts that came through Google, so each test starts signed in as one. */
trait SignsInWithGoogle
{
    protected User $customer;

    protected function signInWithGoogle(): User
    {
        $this->customer = User::factory()->google()->create(['onboarding_completed' => true]);
        $this->actingAs($this->customer);

        return $this->customer;
    }
}
