<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutLinksTest extends TestCase
{
    /**
     * Test that all primary admin layout links return a successful response (no 500s or crashes).
     */
    public function test_admin_layout_links_are_accessible_without_errors(): void
    {
        // Find an admin user to act as
        $user = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        if (!$user) {
            $this->markTestSkipped('No admin user found to run this test.');
        }

        // List of routes we have refactored or built in the new system
        $urls = [
            route('admin.dashboard'),
            route('admin.reports.pnl'),
            route('admin.financial-operations.index'),
            route('admin.users.index'),
            route('admin.leads.index'),
            route('admin.campaigns.index'),
            route('admin.sequences.index'),
            route('admin.projects.index'),
            route('admin.invoices.index'),
            route('admin.contracts.index'),
            route('admin.plans.index'),
            route('admin.kyc.index'),
            route('admin.blog-articles.index'),
            route('admin.serial-devices.index'),
            route('admin.serial-softwares.index'),
            route('admin.serial-user-devices.index'),
            route('admin.tools.reseller.index'),
            route('admin.marketplace.orders.index'),
            route('admin.services.index'),
            route('admin.memberships.index'),
        ];

        $failedUrls = [];

        foreach ($urls as $url) {
            $response = $this->actingAs($user, 'web')->get($url);
            
            // Accept 200 OK, 3xx Redirects, or 403/404 if the route requires specific state. 
            // The main goal is to catch 500 internal server errors (crashes).
            if (!in_array($response->status(), [200, 301, 302, 303, 307, 308, 403, 404])) {
                $failedUrls[] = "URL {$url} failed with status {$response->status()} - " . $response->exception?->getMessage();
            }
        }

        $this->assertEmpty($failedUrls, "The following Admin URLs crashed or failed:\n" . implode("\n", $failedUrls));
    }
}
