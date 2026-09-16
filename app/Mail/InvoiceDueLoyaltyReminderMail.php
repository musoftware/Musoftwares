<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\LoyaltyRule;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class InvoiceDueLoyaltyReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientFirstName;
    public int $basePoints;
    public float $earlyMultiplier;
    public int $potentialPoints;
    public string $dueDate;
    public mixed $invoiceTotal;

    public function __construct(
        private readonly User $user,
        private readonly Invoice $invoice,
    ) {
        $this->clientFirstName = explode(' ', $user->name)[0];
        $this->dueDate = $invoice->due_date
            ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y')
            : 'soon';
        $this->invoiceTotal = $invoice->total();

        $rule = LoyaltyRule::forEvent('invoice_payment');
        $this->basePoints = $rule?->base_points ?? 200;

        // Calculate today's multiplier (paying today = 5 days early)
        $conditions = $rule?->conditions_payload ?? [];
        $multipliers = collect($conditions['early_payment_multipliers'] ?? [])->sortByDesc('days_early_min');
        $this->earlyMultiplier = 1.0;

        foreach ($multipliers as $step) {
            if (5 >= (int) $step['days_early_min']) {
                $this->earlyMultiplier = (float) $step['multiplier'];
                break;
            }
        }

        $this->potentialPoints = (int) round($this->basePoints * $this->earlyMultiplier);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your invoice is due in 5 days — pay now to earn {$this->potentialPoints} bonus points",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loyalty.invoice_due_reminder',
            with: [
                'clientFirstName' => $this->clientFirstName,
                'dueDate'         => $this->dueDate,
                'invoiceTotal'    => $this->invoiceTotal,
                'basePoints'      => $this->basePoints,
                'earlyMultiplier' => $this->earlyMultiplier,
                'potentialPoints' => $this->potentialPoints,
                'invoiceUrl'      => config('app.url') . '/client/invoices/' . $this->invoice->uuid,
            ],
        );
    }

    public static function sendToUser(User $user, Invoice $invoice): void
    {
        if (empty($user->email)) {
            return;
        }

        Mail::to($user->email)->queue(new self($user, $invoice));
    }
}
