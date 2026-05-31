<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$todos = \App\Models\Todo::where('completed', false)->get();
echo "Incomplete: " . $todos->count() . "\n";

foreach ($todos as $todo) {
    echo "Todo ID: {$todo->id}, paused: {$todo->paused}, parent_id: {$todo->parent_id}, task_id: {$todo->task_id}\n";
    if ($todo->task) {
        echo "   Task archived: {$todo->task->archived}\n";
    } else {
        echo "   Task: NULL\n";
    }
}
