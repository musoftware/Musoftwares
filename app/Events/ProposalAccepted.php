<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class ProposalAccepted {
    use Dispatchable, SerializesModels;
    public $proposal;
    public function __construct($proposal) { $this->proposal = $proposal; }
}
