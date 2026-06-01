<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ticket = \App\Models\Ticket::find(52);
app(\App\Services\SupportDeskService::class)->replyToTicket($ticket, 66, 'Test reply');

echo "Conversations:\n";
print_r(\App\Models\Conversation::all()->toArray());
