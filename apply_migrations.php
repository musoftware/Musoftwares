<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Add is_internal
if (!Schema::hasColumn('messages', 'is_internal')) {
    Schema::table('messages', function (Blueprint $table) {
        $table->boolean('is_internal')->default(false)->after('is_system');
    });
    echo "Added is_internal to messages.\n";
} else {
    echo "is_internal already exists.\n";
}

// Create ticket_canned_responses
if (!Schema::hasTable('ticket_canned_responses')) {
    Schema::create('ticket_canned_responses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('body');
        $table->timestamps();
    });
    echo "Created ticket_canned_responses.\n";
} else {
    echo "ticket_canned_responses already exists.\n";
}
