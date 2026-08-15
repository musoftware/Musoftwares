<?php

try {
    require_once __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Check DB connection
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
    } catch (\Throwable $e) {
        // Fallback to SQLite
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        \Illuminate\Support\Facades\DB::purge();
        \Illuminate\Support\Facades\DB::connection('sqlite')->getPdo();
        echo "Using SQLite database for E2E seeding.\n";

        // Ensure deleted_at column exists on admin_settings
        if (\Illuminate\Support\Facades\Schema::hasTable('admin_settings') && !\Illuminate\Support\Facades\Schema::hasColumn('admin_settings', 'deleted_at')) {
            \Illuminate\Support\Facades\Schema::table('admin_settings', function ($table) {
                $table->softDeletes();
            });
        }
    }
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Skipping E2E user creation.\n";
    exit(0);
}

use App\Models\User;
use Spatie\Permission\Models\Role;

$users = [
    [
        'email' => 'admin@musoftwares.com',
        'name' => 'E2E Admin User',
        'role' => 'admin',
    ],
    [
        'email' => 'tenant@musoftwares.com',
        'name' => 'E2E Tenant User',
        'role' => 'tenant_admin',
    ],
    [
        'email' => 'client@musoftwares.com',
        'name' => 'E2E Client User',
        'role' => 'client',
    ],
];

foreach ($users as $userData) {
    $user = User::where('email', $userData['email'])->first();
    if (!$user) {
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => 'password',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        echo "Created E2E user: {$userData['email']}\n";
    } else {
        $user->update([
            'password' => 'password',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        echo "E2E user refreshed: {$userData['email']}\n";
    }

    // Ensure they have the correct role assigned
    if (class_exists(Role::class)) {
        try {
            Role::findOrCreate($userData['role']);
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
                echo "Assigned role '{$userData['role']}' to {$userData['email']}\n";
            }
        } catch (\Exception $e) {
            echo "Warning: Could not assign role '{$userData['role']}' to {$userData['email']}: " . $e->getMessage() . "\n";
        }
    }
}

// Ensure client project seeding
$clientUser = User::where('email', 'client@musoftwares.com')->first();
if ($clientUser) {
    // Seed wallet balance via transaction to survive balance recalculations
    \App\Models\Transaction::where('user_id', $clientUser->id)->delete();
    \App\Models\Transaction::create([
        'user_id' => $clientUser->id,
        'amount' => 1000.0,
        'reason' => 'E2E Seed Deposit',
        'category' => 'other',
        'type' => 'received',
        'currency_id' => $clientUser->currency_id,
    ]);
    \App\Helpers\BalancesHelper::UpdateBalance($clientUser);
    echo "Ensured client user balance is 1000 EGP via seed transaction.\n";
    // Clean up test-run leftovers so the project list stays small
    \App\Models\Project::where('user_id', $clientUser->id)
        ->where(function ($q) {
            $q->where('project_name', 'Playwright E2E AI Project')
              ->orWhere('project_name', 'Playwright E2E AI Workspace Project')
              ->orWhere('project_name', 'like', 'Gemini Test%');
        })->forceDelete();
    echo "Cleaned up duplicate Playwright test projects.\n";

    $project = \App\Models\Project::updateOrCreate(
        ['user_id' => $clientUser->id, 'project_name' => 'E2E Test Client Project'],
        ['status' => 'open', 'archived' => 0, 'budget' => 0.0, 'ai_summary' => ['project_type' => null, 'features' => [], 'current_goal' => null, 'missing_info' => [], 'complexity' => null]]
    );
    echo "Ensured E2E project exists.\n";

    $task = \App\Models\Task::updateOrCreate(
        ['project_id' => $project->id, 'task_name' => 'E2E Deliverable Task'],
        [
            'user_id' => $clientUser->id,
            'task_description' => 'This is a task deliverables description.',
            'due_date' => now()->toDateString(),
            'priority' => 'low'
        ]
    );
    echo "Ensured E2E task exists.\n";

    if (\Illuminate\Support\Facades\Schema::hasTable('project_board_items')) {
        $boardItem = \App\Models\ProjectBoardItem::updateOrCreate(
            ['project_id' => $project->id, 'itemable_type' => \App\Models\Task::class, 'itemable_id' => $task->id],
            [
                'for_date' => now()->toDateString(),
                'lane' => 'review',
                'client_approval_status' => 'pending',
                'client_feedback' => null
            ]
        );
        echo "Ensured E2E board item pending approval exists.\n";
    }

    \App\Models\Ticket::updateOrCreate(
        ['user_id' => $clientUser->id, 'ticket_subject' => 'E2E Urgent Ticket'],
        [
            'ticket_message' => 'Need help with deployment.',
            'ticket_status' => 'open',
            'priority' => 'low'
        ]
    );
    echo "Ensured E2E ticket exists.\n";
}
