<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutLinksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all primary admin layout links return a successful response (no 500s or crashes).
     */
    public function test_admin_layout_links_are_accessible_without_errors(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create an admin user to act as
        $user = User::factory()->create();
        $user->assignRole('admin');

        // List of routes we have refactored or built in the new system
        $routeNames = [
            'admin.dashboard',
            'admin.reports.index',
            'admin.finance.index',
            'admin.users.index',
            'crm.leads.index',
            'crm.campaigns.index',
            'crm.sequences.index',
            'admin.projects.index',
            'admin.invoices.index',
            'admin.plans.index',
            'admin.kyc.index',
            'admin.blog-articles.index',
            'admin.serial-devices.index',
            'admin.serial-softwares.index',
            'admin.serial-user-devices.index',
            'admin.marketplace.orders.index',
            'admin.tasks.client-tasks',
        ];

        $urls = [];
        foreach ($routeNames as $routeName) {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                $urls[] = route($routeName);
            }
        }

        $failedUrls = [];

        foreach ($urls as $url) {
            $response = $this->actingAs($user, 'web')->get($url);

            // Accept 200 OK, 3xx Redirects, or 403/404 if the route requires specific state.
            // The main goal is to catch 500 internal server errors (crashes).
            if (! in_array($response->status(), [200, 301, 302, 303, 307, 308, 403, 404])) {
                $failedUrls[] = "URL {$url} failed with status {$response->status()} - ".$response->exception?->getMessage();
            }
        }

        $this->assertEmpty($failedUrls, "The following Admin URLs crashed or failed:\n".implode("\n", $failedUrls));
    }
}
