<?php

namespace Modules\AppBuilder\Services;

use App\Builders\KashierCheckoutBuilder;
use App\Models\Currency;
use App\Support\AdminRecipients;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\AppBuilder\Models\BuilderProject;
use RuntimeException;

/** Kashier checkout for the current quote, and moving a paid project into the worker's queue. */
class ProjectPayment
{
    public const ORDER_PREFIX = 'bld_';

    /** @param array{client_name: string, client_email: string, client_whatsapp: string, share?: int|null} $client */
    public function paymentUrl(BuilderProject $project, array $client): string
    {
        $due = $this->amountDue($project, (int) ($client['share'] ?? 100));
        unset($client['share']);
        // the gateway converts with our own rates; an unknown currency would be charged as EGP
        if ($project->currency !== 'EGP' && ! Currency::where('currency', $project->currency)->exists()) {
            throw new RuntimeException("No exchange rate for {$project->currency}");
        }

        $project->update($client + ['payment_due' => $due]);

        return KashierCheckoutBuilder::make()
            ->forAmount($due, $project->currency)
            ->forGuest($project->client_name, $project->client_email, self::ORDER_PREFIX.$project->id)
            ->withSource('app-builder', self::ORDER_PREFIX)
            // the quote version rides in the order id, so paying an old price never starts a build
            ->withMetadata(['invoice_id' => $project->id.'-'.$project->quote_version])
            ->withRoutes(
                success: route('builder.show', $project),
                failure: route('builder.show', $project),
                webhook: route('builder.webhook'),
            )
            ->build();
    }

    /** @return array{0: int, 1: int}|null project id and quote version */
    public function parseOrderId(string $merchantOrderId): ?array
    {
        return preg_match('/^'.self::ORDER_PREFIX.'(\d+)-(\d+)_/', $merchantOrderId, $m) ? [(int) $m[1], (int) $m[2]] : null;
    }

    public function __construct(private readonly BuilderReferral $referral) {}

    public function markPaid(BuilderProject $project, int $quoteVersion, ?string $transactionId): bool
    {
        // the gateway may send the same payment twice; a known transaction is never counted again
        if ($transactionId !== null && $transactionId === $project->payment_reference) {
            return false;
        }
        if ($project->quote_version === $quoteVersion && $project->hasBalanceDue()) {
            return $this->markBalancePaid($project, $transactionId);
        }
        if ($project->status !== BuilderProject::QUOTED || $project->quote_version !== $quoteVersion) {
            Log::warning('App builder: payment for a quote that is not open', ['project_id' => $project->id, 'quote_version' => $quoteVersion, 'transaction' => $transactionId]);

            return false;
        }

        $paid = $this->paidNow($project);
        $this->queue($project, [
            'paid_total' => (float) $project->paid_total + $paid,
            'quote_paid' => $paid,
            'payment_reference' => $transactionId,
        ]);
        $this->referral->recordEarning($project, $paid);
        $this->tellOwner($project, 'Paid: '.$project->title.' ('.$paid.' of '.$project->amount.' '.$project->currency.')');
        if ($project->needsDesign()) {
            $this->tellOwner($project, 'Design needed: '.$project->title.'. Add the Figma link on the admin page to start the build.');
        }

        return true;
    }

    /** The rest of a quote after a deposit: the files are handed over once it is paid. */
    private function markBalancePaid(BuilderProject $project, ?string $transactionId): bool
    {
        $paid = min($this->paidNow($project), $project->balanceDue());
        $project->update([
            'paid_total' => (float) $project->paid_total + $paid,
            'quote_paid' => (float) $project->quote_paid + $paid,
            'payment_reference' => $transactionId,
            'payment_due' => 0,
        ]);
        $this->referral->recordEarning($project, $paid);
        $this->tellOwner($project, 'Balance paid: '.$project->title.' ('.$paid.' '.$project->currency.')');

        return true;
    }

    /** The first payment of a quote may be a share of it; later payments pay what is left. */
    private function amountDue(BuilderProject $project, int $share): float
    {
        if ($project->hasBalanceDue()) {
            return $project->balanceDue();
        }

        return $share >= 100 ? (float) $project->amount : max(1.0, ceil((float) $project->amount * $share / 100));
    }

    /** What the customer's payment link was for; an old link without one paid the whole quote. */
    private function paidNow(BuilderProject $project): float
    {
        return (float) $project->payment_due > 0 ? (float) $project->payment_due : (float) $project->amount;
    }

    /** A free bug fix skips the gateway. The owner hears about every one, so a change passed off as a fix is seen. */
    public function startFreeFix(BuilderProject $project): bool
    {
        if ($project->status !== BuilderProject::QUOTED || ! $project->free_fix) {
            return false;
        }

        $this->queue($project, ['free_fixes_used' => $project->free_fixes_used + 1]);
        $this->tellOwner($project, 'Free fix started: '.$project->title.' (fix '.$project->free_fixes_used.')');

        return true;
    }

    private function queue(BuilderProject $project, array $extra): void
    {
        $project->update($extra + [
            // a project with a paid design waits for the designer before it goes to the worker
            'status' => $project->needsDesign() ? BuilderProject::DESIGNING : BuilderProject::QUEUED,
            'paid_at' => now(),
            'attempts' => 0,
            'progress_step' => null,
            'progress_file' => null,
            'error' => null,
        ]);
    }

    /** A mail problem must never undo a payment or a delivery. */
    public function tellOwner(BuilderProject $project, string $line): void
    {
        try {
            Mail::raw($line."\n".route('builder.show', $project), fn ($mail) => $mail->to(AdminRecipients::emails())->subject('App builder: '.$line));
        } catch (\Throwable $e) {
            Log::warning('App builder: owner mail failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }
}
