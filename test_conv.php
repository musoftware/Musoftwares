<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ticket = \App\Models\Ticket::find(52);
if (!$ticket) {
    echo "Ticket 52 not found\n";
    exit;
}

// See if conversation relation works
$conv = $ticket->conversation;

echo "conversable_id = 52 from Conversation model:\n";
$all = \App\Models\Conversation::where('conversable_id', 52)->get();
print_r($all->toArray());

echo "\nLegacy replies?\n";
$replies = \Illuminate\Support\Facades\DB::table('ticket_replies')->where('ticket_id', 52)->get();
print_r($replies->toArray());

echo "\nOr any messages with ticket_id?\n";
if (\Illuminate\Support\Facades\Schema::hasColumn('messages', 'ticket_id')) {
    $msgs = \Illuminate\Support\Facades\DB::table('messages')->where('ticket_id', 52)->get();
    print_r($msgs->toArray());
}

