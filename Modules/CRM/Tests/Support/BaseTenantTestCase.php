<?php

namespace Modules\CRM\Tests\Support;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CRM\Models\Workspace;
use App\Models\User;
use Modules\CRM\Infrastructure\Context\TenantContext;

abstract class BaseTenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected Workspace $workspace;
    protected User $adminUser;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTenant();
    }

    /**
     * Create a default tenant/workspace and acting user.
     */
    protected function setUpTenant(): void
    {
        $this->adminUser = User::factory()->create();
        
        $this->workspace = Workspace::factory()->create([
            'user_id' => $this->adminUser->id,
            'name' => 'Test Enterprise Corp'
        ]);

        // Attach user to workspace
        $this->workspace->users()->attach($this->adminUser->id);

        // Authenticate the user
        $this->actingAs($this->adminUser);

        // Inject the tenant context mimicking the WorkspaceMiddleware
        $this->setTenantContext($this->workspace->id);
    }

    /**
     * Safely inject the Tenant Context for the duration of the test.
     */
    protected function setTenantContext(int $workspaceId): void
    {
        // For Web requests, the middleware will set it, but we set it globally
        // so that factories and models created in the test body automatically pick it up.
        app(TenantContext::class)->setWorkspaceId($workspaceId);
        
        // Also simulate session state for Web requests that rely on WorkspaceMiddleware
        session(['crm_workspace_id' => $workspaceId]);
    }

    /**
     * Clear the tenant context.
     */
    protected function clearTenantContext(): void
    {
        app(TenantContext::class)->clear();
        session()->forget('crm_workspace_id');
    }
}
