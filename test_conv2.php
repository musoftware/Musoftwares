<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "conversable_type for any Conversation:\n";
print_r(\App\Models\Conversation::select('conversable_type')->distinct()->pluck('conversable_type')->toArray());

echo "\nConversations for ticket 52:\n";
$conv = \App\Models\Conversation::where('conversable_id', 52)->where('conversable_type', 'App\Models\Ticket')->first();
print_r($conv ? $conv->toArray() : 'None');

if (\Illuminate\Support\Facades\Schema::hasColumn('messages', 'conversation_id')) {
    echo "\nTotal messages in DB: " . \App\Models\Message::count() . "\n";
}
