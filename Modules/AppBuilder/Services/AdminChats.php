<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Splits the builder chats into the team's own (tests and trials) and real customers', so the owner reads customer
 * chats without his tests in between. A chat is the team's when an admin account started it, or when a guest
 * started it from an address an admin's own chat came from (the owner trying the site signed out). The paid tab
 * holds the real work, whoever started it: projects with money paid in, and the projects the owner imported.
 */
class AdminChats
{
    /** The same roles as User::isAdmin(). */
    private const ADMIN_ROLES = ['super_admin', 'admin', 'superadmin', 'Admin'];

    /** @var array{0: list<int>, 1: list<string>}|null */
    private ?array $team = null;

    /** Only the team's chats ($team true) or only customers' chats ($team false). */
    public function only(Builder $query, bool $team): Builder
    {
        [$admins, $addresses] = $this->team();

        return $team
            ? $query->where(fn (Builder $any) => $any->whereIn('user_id', $admins)
                ->orWhere(fn (Builder $guest) => $guest->whereNull('user_id')->whereIn('ip', $addresses)))
            // written without NOT (...), which drops rows where user_id or ip is NULL
            : $query->where(fn (Builder $account) => $account->whereNull('user_id')->orWhereNotIn('user_id', $admins))
                ->where(fn (Builder $guest) => $guest->whereNotNull('user_id')->orWhereNull('ip')->orWhereNotIn('ip', $addresses));
    }

    public const TABS = ['customers', 'paid', 'team'];

    /** The chats of one tab of the admin list. */
    public function tab(Builder $query, string $tab): Builder
    {
        return $tab === 'paid' ? $this->paid($query) : $this->only($query, $tab === 'team');
    }

    /** Projects with money paid in, and the owner's imported projects (their work is real though nothing is paid here). */
    public function paid(Builder $query): Builder
    {
        return $query->where(fn (Builder $work) => $work->where('paid_total', '>', 0)
            ->orWhere(fn (Builder $import) => $import->whereNotNull('import_url')->where('customer_project', false)));
    }

    /** @return array{customers: int, paid: int, team: int} */
    public function counts(): array
    {
        return collect(self::TABS)->mapWithKeys(fn (string $tab) => [$tab => $this->tab(BuilderProject::query(), $tab)->count()])->all();
    }

    /** @return array{0: list<int>, 1: list<string>} the admins' ids and the addresses their own chats came from */
    private function team(): array
    {
        if ($this->team === null) {
            // whereHas, not User::role(), because role() throws when a role does not exist yet
            $admins = User::whereHas('roles', fn ($roles) => $roles->whereIn('name', self::ADMIN_ROLES))->pluck('id')->all();
            $addresses = BuilderProject::whereIn('user_id', $admins)->whereNotNull('ip')->distinct()->pluck('ip')->all();
            $this->team = [$admins, $addresses];
        }

        return $this->team;
    }
}
