<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * The owner between the customer and the engineer: every request waits for the owner's yes before it starts. The
 * owner reads exactly what the engineer will get (the customer's words and the chat's note), can correct it, and
 * approves or turns it down, from the push on their phone.
 */
class OwnerReview
{
    public function __construct(private readonly ProjectPayment $payment, private readonly QuotePricer $pricer) {}

    /** A new request: the owner hears, and the push opens it on the project's admin page. */
    public function ask(BuilderProject $project, BuilderRequest $request): void
    {
        if ($request->isApproved()) {
            return;
        }

        $kind = $request->kind === BuilderRequest::FIX ? 'Fix' : 'New work';
        $price = $request->free ? 'free' : number_format((float) $request->amount, 2).' '.$project->currency;
        $this->payment->tellOwner($project, "{$kind} to approve ({$price}): {$request->title}", $this->url($project, $request));
    }

    /** The page the owner's push opens, scrolled to this request. */
    public function url(BuilderProject $project, BuilderRequest $request): string
    {
        return route('admin.builder.show', $project).'#request-'.$request->id;
    }

    /**
     * The owner's corrected brief is what the engineer reads. A price can change only before the customer pays it,
     * and the build budget follows the new price.
     *
     * @param  array{spec?: string, customer_words?: string, amount?: float|null}  $changes
     */
    public function approve(BuilderProject $project, BuilderRequest $request, array $changes): void
    {
        $request->update(['approved_at' => now()] + $this->edits($request, $changes));
        app(ProjectRequests::class)->startNext($project);
    }

    /** A new project's first build: the owner's corrected brief is what the engineer builds, and it starts now. */
    public function approveProject(BuilderProject $project, ?string $spec): void
    {
        $project->update(array_filter(['owner_approved_at' => now(), 'spec' => $spec !== null && trim($spec) !== '' ? trim($spec) : null]));
    }

    /** Saves the owner's corrections without approving yet. */
    public function save(BuilderRequest $request, array $changes): void
    {
        $request->update($this->edits($request, $changes));
    }

    /**
     * Turned down: the request leaves the list and the customer reads why. A paid one is the owner's to refund,
     * so the owner is reminded.
     */
    public function reject(BuilderProject $project, BuilderRequest $request, string $note): void
    {
        $request->delete();
        $project->messages()->create(['role' => BuilderMessage::TEAM, 'content' => $note !== '' ? $note : __('builder.requests.rejected', ['title' => $request->title], $project->customerLanguage())]);
        if ($request->paid_at !== null && ! $request->free) {
            $this->payment->tellOwner($project, 'Turned down a paid request, refund or credit it: '.$request->title.' '.$request->amount.' '.$project->currency);
        }
    }

    /** @return array<string, mixed> only the fields the owner may change now */
    private function edits(BuilderRequest $request, array $changes): array
    {
        $edits = array_filter([
            'spec' => isset($changes['spec']) ? trim($changes['spec']) : null,
            'customer_words' => isset($changes['customer_words']) ? trim($changes['customer_words']) : null,
        ], fn ($value) => $value !== null && $value !== '');
        $amount = $changes['amount'] ?? null;
        if ($amount === null || $request->status !== BuilderRequest::UNPAID || (float) $amount === (float) $request->amount) {
            return $edits;
        }

        return $edits + ['amount' => round((float) $amount, 2), 'budget_usd' => $this->pricer->budgetFor((float) $amount)];
    }
}
