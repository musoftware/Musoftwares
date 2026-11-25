<?php

namespace Tests\Feature;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Opens every page of the site (every GET address without a {parameter}) as a visitor, a client and an admin, and
 * fails on any server error. A page may answer 200, a redirect, 403 or 404, but never 5xx. New pages are covered
 * automatically. Outside services are never called: HTTP, mail, notifications and the queue are stopped here.
 */
class EveryPageOpensTest extends TestCase
{
    use RefreshDatabase;

    /** Addresses that leave the site or end the session, so opening them tells nothing about a page. */
    private const SKIPPED = [
        '#^logout$#', '#(^|/)auth/[a-z]+/redirect$#', '#^auth/google#', '#^sanctum/csrf-cookie$#', '#^_ignition#',
        '#^up$#', '#^horizon#', '#^telescope#', '#^__clockwork#', '#^_debugbar#', '#^storage/#', '#^broadcasting/#',
    ];

    /** Pages whose reports use MySQL date functions; the live site runs MySQL, the tests run SQLite. */
    private const MYSQL_ONLY = ['#^admin/users/earning-analyze$#'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTodaysRates();
    }

    public function test_every_page_opens_for_a_visitor(): void
    {
        $this->assertNoServerErrors(null);
    }

    public function test_every_page_opens_for_a_client(): void
    {
        $this->assertNoServerErrors($this->userWithRole('client'));
    }

    public function test_every_page_opens_for_an_admin(): void
    {
        $this->assertNoServerErrors($this->userWithRole('admin'));
    }

    private function assertNoServerErrors(?User $user): void
    {
        Http::preventStrayRequests();
        Mail::fake();
        Notification::fake();
        Queue::fake();

        $failures = [];
        foreach ($this->pageAddresses() as $uri) {
            $status = $this->open($uri, $user);
            if ($status >= 500) {
                $failures[] = "{$status} /{$uri}";
            }
        }

        $this->assertSame([], $failures, 'These pages answer with a server error');
    }

    private function open(string $uri, ?User $user): int
    {
        if ($user) {
            $this->actingAs($user);
        }
        $status = $this->get('/'.ltrim($uri, '/'))->getStatusCode();
        $this->flushSession();
        app('auth')->forgetGuards();

        return $status;
    }

    /** @return list<string> */
    private function pageAddresses(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteItem $route) => in_array('GET', $route->methods(), true))
            ->filter(fn (RouteItem $route) => $route->getDomain() === null && ! str_contains($route->uri(), '{'))
            ->map(fn (RouteItem $route) => $route->uri())
            ->reject(fn (string $uri) => $this->matchesAny($this->skippedPatterns(), $uri))
            ->unique()->sort()->values()->all();
    }

    /** @return list<string> */
    private function skippedPatterns(): array
    {
        return DB::getDriverName() === 'mysql' ? self::SKIPPED : [...self::SKIPPED, ...self::MYSQL_ONLY];
    }

    private function matchesAny(array $patterns, string $uri): bool
    {
        return collect($patterns)->contains(fn (string $pattern) => preg_match($pattern, $uri) === 1);
    }

    /** The live site fetches rates every day; pages that show prices need one for each pair. */
    private function seedTodaysRates(): void
    {
        $ids = Currency::pluck('id')->all();
        foreach ($ids as $from) {
            foreach (array_diff($ids, [$from]) as $to) {
                CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $from, 'currency2' => $to, 'rate' => 1.0]);
            }
        }
    }

    private function userWithRole(string $role): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $currency = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP', 'string_format' => '%.2f EGP']);
        $user = User::factory()->create(['currency_id' => $currency->id, 'onboarding_completed' => true]);
        $user->assignRole($role);

        return $user;
    }
}
