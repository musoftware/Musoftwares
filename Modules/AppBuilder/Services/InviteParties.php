<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AppBuilder\Models\BuilderProject;

/** The two people behind an invite, and where each one has been seen: addresses, browsers. */
final class InviteParties
{
    public readonly ?User $inviter;

    private ?Collection $inviterAddresses = null;

    public function __construct(public readonly BuilderProject $project, public readonly User $invitee, public readonly string $ip, public readonly string $userAgent)
    {
        $this->inviter = $project->referrer_user_id ? User::find($project->referrer_user_id) : null;
    }

    /** @return Collection<int, string> */
    public function inviteeAddresses(): Collection
    {
        return BuilderProject::where('user_id', $this->invitee->id)->pluck('ip')
            ->push($this->ip, (string) $this->project->ip)->filter()->unique()->values();
    }

    /** @return Collection<int, string> where the inviter chatted from and where their sign-ins are open now */
    public function inviterAddresses(): Collection
    {
        if ($this->inviter === null) {
            return collect();
        }

        return $this->inviterAddresses ??= BuilderProject::where('user_id', $this->inviter->id)->pluck('ip')
            ->merge(DB::table('sessions')->where('user_id', $this->inviter->id)->pluck('ip_address'))
            ->filter()->unique()->values();
    }

    /** @return Collection<int, string> */
    public function inviterBrowsers(): Collection
    {
        if ($this->inviter === null) {
            return collect();
        }

        return BuilderProject::where('user_id', $this->inviter->id)->pluck('user_agent')
            ->merge(DB::table('sessions')->where('user_id', $this->inviter->id)->pluck('user_agent'))
            ->filter()->unique()->values();
    }

    /** The home or office network of an address: the first three parts of IPv4, the first four groups of IPv6. */
    public static function network(string $ip): string
    {
        return str_contains($ip, ':')
            ? implode(':', array_slice(explode(':', strtolower($ip)), 0, 4))
            : implode('.', array_slice(explode('.', $ip), 0, 3));
    }

    /** One mailbox, however it is written: case, and for Gmail the dots and any +tag. */
    public static function sameMailbox(?string $email): string
    {
        [$name, $domain] = array_pad(explode('@', strtolower(trim((string) $email)), 2), 2, '');
        $domain = $domain === 'googlemail.com' ? 'gmail.com' : $domain;
        $name = explode('+', $name)[0];

        return ($domain === 'gmail.com' ? str_replace('.', '', $name) : $name).'@'.$domain;
    }
}
