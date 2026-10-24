<?php

namespace App\Support;

use App\Models\User;

/** Who gets the owner's notification mails: every admin, or the site's from-address when there is none. */
class AdminRecipients
{
    /** @return array<int, string> */
    public static function emails(): array
    {
        // whereHas, not User::role(), because role() throws when the "admin" role does not exist yet
        $emails = User::whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))->pluck('email')->filter()->unique()->values()->all();

        return $emails ?: array_filter([(string) config('mail.from.address')]);
    }
}
