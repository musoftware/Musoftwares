<?php

try {
    require_once __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Check DB connection
    \Illuminate\Support\Facades\DB::connection()->getPdo();
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
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        echo "Created E2E user: {$userData['email']}\n";
    } else {
        echo "E2E user already exists: {$userData['email']}\n";
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
    $project = \App\Models\Project::updateOrCreate(
        ['user_id' => $clientUser->id, 'project_name' => 'E2E Test Client Project'],
        ['status' => 'open', 'archived' => 0]
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
