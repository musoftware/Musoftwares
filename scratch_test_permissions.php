<?php

use Modules\ERP\Models\TeamMember;
use Illuminate\Http\Request;
use Modules\ERP\Http\Middleware\EnforceTeamMemberPermissions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = ['admin', 'manager', 'sales_agent', 'cashier', 'accountant', 'staff'];
$routesToTest = [
    'erp.team-members.index', // admin only
    'erp.settings.smtp.edit', // admin only
    'erp.backup.index',       // admin only
    'erp.clients.index',      // all
    'erp.projects.index',     // staff, manager, admin
    'erp.tasks.index',        // staff, manager, admin
    'erp.pos.index',          // cashier, manager, admin
    'erp.inventory.index',    // cashier, manager, admin
    'erp.invoices.index',     // accountant, sales, cashier, manager, admin
    'erp.expenses.index',     // accountant, manager, admin
];

echo "Testing Middleware Permissions\n";
echo str_repeat("-", 80) . "\n";
printf("%-20s | %-25s | %s\n", "Role", "Route", "Result");
echo str_repeat("-", 80) . "\n";

foreach ($roles as $role) {
    $member = new TeamMember(['role' => $role]);
    
    foreach ($routesToTest as $routeName) {
        $request = Request::create('/fake', 'GET');
        $route = new \Illuminate\Routing\Route('GET', '/fake', ['as' => $routeName]);
        $request->setRouteResolver(function() use ($route) {
            return $route;
        });

        Auth::shouldReceive('guard')->with('erp_team')->andReturnSelf();
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($member);

        $middleware = new EnforceTeamMemberPermissions();
        $result = 'Allowed';
        try {
            $middleware->handle($request, function($req) {
                return new \Illuminate\Http\Response('Next');
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 403) {
                $result = 'Blocked (403)';
            } else {
                $result = 'Error: ' . $e->getStatusCode();
            }
        } catch (\Exception $e) {
            $result = 'Exception: ' . $e->getMessage();
        }

        printf("%-20s | %-25s | %s\n", $role, $routeName, $result);
    }
}
