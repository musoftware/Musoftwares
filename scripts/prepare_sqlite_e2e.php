<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
\Illuminate\Support\Facades\DB::purge();

echo "Verifying SQLite Schema and seeding baseline currencies & settings...\n";

// Get all user tables in sqlite database
$allTables = \Illuminate\Support\Facades\DB::connection('sqlite')
    ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'");

foreach ($allTables as $tableObj) {
    $tableName = $tableObj->name;
    if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'deleted_at')) {
        try {
            \Illuminate\Support\Facades\Schema::table($tableName, function ($t) {
                $t->softDeletes();
            });
        } catch (\Throwable $e) {}
    }
}

// Ensure columns on projects
if (\Illuminate\Support\Facades\Schema::hasTable('projects')) {
    $projCols = [
        'share_token' => 'string',
        'budget' => 'float',
        'ai_summary' => 'json',
        'ai_enabled' => 'boolean',
        'ai_workspace' => 'json',
        'ai_stage' => 'string',
        'archived' => 'boolean',
        'status' => 'string',
    ];
    foreach ($projCols as $col => $type) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('projects', $col)) {
            try {
                \Illuminate\Support\Facades\Schema::table('projects', function ($t) use ($col, $type) {
                    if ($type === 'boolean') $t->boolean($col)->default(false);
                    elseif ($type === 'float') $t->decimal($col, 15, 2)->nullable();
                    elseif ($type === 'json') $t->json($col)->nullable();
                    else $t->string($col)->nullable();
                });
            } catch (\Throwable $e) {}
        }
    }
}

// Ensure columns on tasks
if (\Illuminate\Support\Facades\Schema::hasTable('tasks')) {
    $taskCols = ['priority' => 'string', 'due_date' => 'date', 'task_description' => 'text', 'start_at' => 'datetime'];
    foreach ($taskCols as $col => $type) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('tasks', $col)) {
            try {
                \Illuminate\Support\Facades\Schema::table('tasks', function ($t) use ($col, $type) {
                    if ($type === 'text') $t->text($col)->nullable();
                    elseif ($type === 'date') $t->date($col)->nullable();
                    elseif ($type === 'datetime') $t->dateTime($col)->nullable();
                    else $t->string($col)->nullable();
                });
            } catch (\Throwable $e) {}
        }
    }
}

// Ensure columns on project_board_items
if (\Illuminate\Support\Facades\Schema::hasTable('project_board_items')) {
    $boardCols = [
        'itemable_type' => 'string',
        'itemable_id' => 'unsignedBigInteger',
        'for_date' => 'date',
        'lane' => 'string',
        'client_approval_status' => 'string',
        'client_feedback' => 'text',
        'published_at' => 'datetime',
        'invoice_id' => 'unsignedBigInteger',
    ];
    foreach ($boardCols as $col => $type) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('project_board_items', $col)) {
            try {
                \Illuminate\Support\Facades\Schema::table('project_board_items', function ($t) use ($col, $type) {
                    if ($type === 'text') $t->text($col)->nullable();
                    elseif ($type === 'date') $t->date($col)->nullable();
                    elseif ($type === 'datetime') $t->dateTime($col)->nullable();
                    elseif ($type === 'unsignedBigInteger') $t->unsignedBigInteger($col)->nullable();
                    else $t->string($col)->nullable();
                });
            } catch (\Throwable $e) {}
        }
    }
}

// Ensure columns on tickets
if (\Illuminate\Support\Facades\Schema::hasTable('tickets')) {
    $ticketCols = ['ticket_subject' => 'string', 'ticket_message' => 'text', 'ticket_status' => 'string', 'priority' => 'string'];
    foreach ($ticketCols as $col => $type) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('tickets', $col)) {
            try {
                \Illuminate\Support\Facades\Schema::table('tickets', function ($t) use ($col, $type) {
                    if ($type === 'text') $t->text($col)->nullable();
                    else $t->string($col)->nullable();
                });
            } catch (\Throwable $e) {}
        }
    }
}

// Check transactions columns
if (\Illuminate\Support\Facades\Schema::hasTable('transactions') && !\Illuminate\Support\Facades\Schema::hasColumn('transactions', 'category')) {
    try {
        \Illuminate\Support\Facades\Schema::table('transactions', function ($t) {
            $t->string('category')->nullable()->default('other');
        });
    } catch (\Throwable $e) {}
}

// Check users columns
if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
    $userCols = ['onboarding_completed' => 'boolean', 'currency_id' => 'unsignedBigInteger'];
    foreach ($userCols as $col => $type) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', $col)) {
            try {
                \Illuminate\Support\Facades\Schema::table('users', function ($t) use ($col, $type) {
                    if ($type === 'boolean') $t->boolean($col)->default(true);
                    else $t->$type($col)->nullable();
                });
            } catch (\Throwable $e) {}
        }
    }
}

// Ensure columns on currencies
if (\Illuminate\Support\Facades\Schema::hasTable('currencies')) {
    if (!\Illuminate\Support\Facades\Schema::hasColumn('currencies', 'is_default')) {
        try {
            \Illuminate\Support\Facades\Schema::table('currencies', function ($t) {
                $t->boolean('is_default')->default(false);
            });
        } catch (\Throwable $e) {}
    }
    if (!\Illuminate\Support\Facades\Schema::hasColumn('currencies', 'country_codes')) {
        try {
            \Illuminate\Support\Facades\Schema::table('currencies', function ($t) {
                $t->json('country_codes')->nullable();
            });
        } catch (\Throwable $e) {}
    }

    $egp = \App\Models\Currency::where('currency', 'EGP')->first();
    if (!$egp) {
        \App\Models\Currency::create([
            'currency' => 'EGP',
            'symbol' => 'EGP',
            'string_format' => '{amount} EGP',
            'is_default' => 1
        ]);
        echo "Created default EGP currency.\n";
    }

    $usd = \App\Models\Currency::where('currency', 'USD')->first();
    if (!$usd) {
        \App\Models\Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '${amount}',
            'is_default' => 0
        ]);
        echo "Created USD currency.\n";
    }
}

echo "SQLite E2E Database is ready and verified!\n";
