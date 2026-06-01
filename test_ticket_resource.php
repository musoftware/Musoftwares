<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ticket = \App\Models\Ticket::find(52);
if ($ticket) {
    $ticket->load(['user', 'conversation.messages.sender']);
    try {
        $resource = (new \App\Http\Resources\TicketResource($ticket))->resolve();
        echo json_encode($resource);
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Ticket not found";
}
