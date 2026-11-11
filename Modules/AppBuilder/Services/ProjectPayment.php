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

    public function __construct(private readonly BuilderReferral $referral, private readonly OriginalFiles $originals) {}

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
        // the project joins the payer's account (opened for them if needed) and the link goes to their email
        app(ProjectAccount::class)->afterPayment($project->refresh());

        return true;
    }

    /**
     * The owner marks the open price as paid (paid by hand, or a test): the whole quote, or the rest after a deposit.
     * It then runs the same steps as a gateway payment.
     */
    public function markPaidByAdmin(BuilderProject $project, int $adminId): bool
    {
        $due = $this->openAmount($project);
        if ($due === null) {
            return false;
        }

        Log::info('App builder: marked paid by an admin', ['project_id' => $project->id, 'admin_id' => $adminId]);

        return $this->payOutsideGateway($project, $due, 'admin-'.$adminId.'-'.now()->timestamp);
    }

    /** What can be paid now in full: the open quote, or the rest after a deposit. Null when nothing is open. */
    public function openAmount(BuilderProject $project): ?float
    {
        if ($project->hasBalanceDue()) {
            return $project->balanceDue();
        }

        return $project->status === BuilderProject::QUOTED && ! $project->free_fix ? (float) $project->amount : null;
    }

    /** A payment made without the card gateway (the wallet, or the owner by hand) runs the same steps as a card payment. */
    public function payOutsideGateway(BuilderProject $project, float $due, string $reference): bool
    {
        $project->update(['payment_due' => $due]);

        return $this->markPaid($project, $project->quote_version, $reference);
    }

    /**
     * The work turned out bigger than the price: the owner asks for more, up to the top of the range the customer saw.
     * It becomes the rest to pay, and the files open once it is paid. Returns what was added (0 when nothing can be).
     */
    public function askForMore(BuilderProject $project, float $extra): float
    {
        $room = round((float) $project->price_max - (float) $project->amount, 2);
        if ($project->status === BuilderProject::QUOTED || (float) $project->quote_paid <= 0 || $room <= 0 || $extra <= 0) {
            return 0.0;
        }

        $added = min($extra, $room);
        $project->update(['amount' => (float) $project->amount + $added, 'payment_due' => 0]);
        $project->messages()->create([
            'role' => \Modules\AppBuilder\Models\BuilderMessage::TEAM,
            'content' => __('builder.extra_asked', ['amount' => number_format($added, 2).' '.$project->currency], $project->customerLanguage()),
        ]);

        return $added;
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

    /** A signed-in customer's first simple page: no payment, and the build may spend only the free cap. */
    public function startFreeBuild(BuilderProject $project, \App\Models\User $user): bool
    {
        if (! app(FreeBuild::class)->availableTo($project, $user)) {
            return false;
        }

        $cap = (float) config('builder.free_build.budget_usd');
        $this->queue($project, [
            'free_build' => true,
            'user_id' => $project->user_id ?? $user->id,
            'budget_usd' => min((float) $project->budget_usd, $cap),
        ]);
        $this->tellOwner($project, 'Free first build started: '.$project->title);

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
            'progress_percent' => 0,
            'job_cost_usd' => 0,
            'progress_note' => null,
            'error' => null,
            // design pictures were sent as smaller previews: the build waits a while for the originals
            'originals_due_at' => $this->originals->dueAt($project),
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
