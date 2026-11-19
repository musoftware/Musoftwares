<?php

declare(strict_types=1);

namespace App\Support;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The one page shown after any payment (invoice, payment link, quotation deposit).
 * Each caller only says what happened, which buttons to show and, if it has them, a few detail rows.
 */
final class PaymentResultPage
{
    /**
     * @param  list<array{label: string, href: string, external?: bool}>  $actions  the first one is the main button
     * @param  list<array{label: string, value: string}>  $details
     */
    public static function render(
        bool $paid,
        string $message,
        array $actions = [],
        array $details = [],
        ?string $note = null,
    ): Response {
        return Inertia::render('Payment/Result', [
            'status' => $paid ? 'success' : 'error',
            'message' => $message,
            'actions' => $actions === [] ? [self::homeAction()] : $actions,
            'details' => $details,
            'note' => $note,
        ]);
    }

    /** @return array{label: string, href: string} */
    public static function homeAction(): array
    {
        return ['label' => __('client_money.result.home'), 'href' => '/'];
    }
}
