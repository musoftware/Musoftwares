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

$conv = $ticket->conversation;
echo "Conversation:\n";
print_r($conv ? $conv->toArray() : null);

if ($conv) {
    echo "\nMessages:\n";
    print_r($conv->messages()->get()->toArray());
}
