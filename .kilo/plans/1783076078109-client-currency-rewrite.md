# Plan: Auto-convert client balances on default-currency change

## Goal
When a client's `users.currency_id` is changed, physically rewrite every stored monetary amount owned by that client to the new currency using the historical exchange rate at each record's `created_at`, then recompute the denormalized balance columns from source.

## Key decisions (locked)
- **Approach:** Full physical rewrite of stored amounts + currency_id. (Display already converts on-the-fly; the real bug is denormalized balances going stale.)
- **Rate:** `CurrenciesExchange::RateByDate($row->created_at, $amount, $oldId, $newId)` per row.
- **Safety:** single `DB::transaction`; **raw `DB::table()->update()`** to fully bypass `Transaction`/`CostTransaction` `saving` observers (they would otherwise re-convert and recompute business_amount).
- **Double-conversion guard:** convert only rows `WHERE currency_id = $oldId`. After rewrite they carry the new currency, so a later change won't touch them again.
- **Aggregates:** recompute from source via `BalancesHelper` (not direct conversion) — avoids the "which date" problem for user/project-level columns.
- **`business_amount`:** intentionally NOT recalculated (stale accepted). Flagged as a known limitation.
- **Out of scope (leave untouched):** `hour_rate`/`hour_rate_currency_id`, `booking_rate`/`booking_rate_currency_id`, `salary`, `postpaid_limit`, Marketplace module (own exchange_rate snapshots), `affiliate_commission_percentage`, `add_commission_to_total`.

## Context (verified in code)
- Only site that changes an EXISTING client's currency: `app/Services/AdminUserService.php:52` (`applyFields`, then `$user->save()` at line 130). `SocialLoginController:115` and `RegisteredUserController:72` set currency at user creation (no data to convert).
- Trap: `app/Models/Transaction.php:41` `saving` observer auto-converts `amount` to the user's currency and recomputes `business_amount`. `app/Models/CostTransaction.php:19` recomputes `business_amount`.
- `Invoice::total()` is COMPUTED = items + tax − discounts (Invoice.php:345). So `invoice_items.amount` + `invoice_item_timers.amount` MUST be scaled by the same rate as `invoices.paid/unpaid/discount/...` or totals break.
- `BalancesHelper::CalcBalance/CalcTotalSpend/CalcCostBalance` (app/Helpers/BalancesHelper.php) recompute denormalized balances from transactions grouped by currency_id. After rewrite all rows are in the new currency → 1:1 → correct.
- Tables/columns (confirmed via migrations): `transactions(amount,currency_id,business_amount)`, `invoices(paid,unpaid,discount,second_discount,tax_value,cost,currency_id)`, `invoice_items(amount)`, `invoice_item_timers(amount,currency_id)`, `cost_transactions(amount,currency_id,business_amount)`, `recurring_invoices(amount,currency_id)`, `user_referral_request_withdraws(amount,currency_id)`, `projects(project_balance,total_paid,user_id)`, `users(user_balance,total_paid,total_cost,withdrawing_commission,withdrawn_commission,pending_commission)`.

## Implementation tasks

### 1. New service `app/Services/ClientCurrencyConverterService.php`
Public method: `convert(User $user, int $oldId, int $newId): array`
- Bail early if `$oldId <= 0` or `$oldId === $newId`.
- Wrap everything in `DB::transaction(function () use (...) { ... })`.
- Helper inside: `$rate = fn($date, $amount) => (float) CurrenciesExchange::RateByDate($date, $amount, $oldId, $newId);`
- For each affected row, SELECT id, created_at, amount(+cols), then raw UPDATE with converted value. Process in chunks (e.g. 500) to bound memory for large clients. Do raw updates via `DB::table($table)->where('id', $id)->update([...])` (or batched WHERE IN) so NO model `saving` observer fires.

Rewrite groups (only rows whose `currency_id = $oldId`, scoped by user where applicable):
1. `transactions` — `WHERE user_id = $user->id AND currency_id = $oldId` → `amount = rate(created_at, amount)`; set `currency_id = $newId`. Leave `business_amount` untouched.
2. `invoices` — `WHERE user_id = $user->id AND currency_id = $oldId` (all statuses) → scale `paid, unpaid, discount, second_discount, tax_value, cost` by `rate(created_at, ·)`; set `currency_id = $newId`.
3. `invoice_items` — join `invoices` on `invoice_id` for this user where `invoices.currency_id = $newId` (already flipped in step 2 — capture the invoice ids before flip, or run this BEFORE flipping invoice currency, or filter by the set of converted invoice ids). Set `amount = rate(invoice_items.created_at, amount)`.
4. `invoice_item_timers` — same invoice-id set → `amount = rate(invoice_item_timers.created_at, amount)`; (currency_id column exists but timers have no per-row currency semantic that affects totals; still set `currency_id = $newId` for consistency).
5. `cost_transactions` — `WHERE user_id = $user->id AND currency_id = $oldId` → `amount = rate(created_at, amount)`; `currency_id = $newId`. Leave `business_amount`.
6. `recurring_invoices` — `WHERE user_id = $user->id AND currency_id = $oldId` → `amount = rate(created_at, amount)`; `currency_id = $newId`.
7. `user_referral_request_withdraws` — `WHERE user_id = $user->id AND currency_id = $oldId` → `amount = rate(created_at, amount)`; `currency_id = $newId`.

IMPORTANT ordering: capture the set of converted invoice ids first, run items+timers conversion using that set, and keep all inside the same transaction so a failure rolls back the invoice flip too. (Equivalent: do invoice column scaling but defer the `currency_id = $newId` flip until after items/timers are scaled — either approach is fine as long as it's atomic.)

### 2. Recompute denormalized balances (still inside the transaction, after rewrites)
- `BalancesHelper::CalcBalance($user);` → fixes `user_balance`.
- `BalancesHelper::CalcTotalSpend($user);` → fixes `total_paid`.
- `BalancesHelper::CalcCostBalance($user);` → fixes `total_cost`.
- `BalancesHelper::CalcWithdrawingCommission($user);` and `CalcWithdrawnCommission($user);` → fix commission columns.
- For each of the user's projects (`$user->projects`): `BalancesHelper::CalcBalance($user, $project);` and `CalcTotalSpend($user, $project);` → fix `project_balance`, `total_paid`.

### 3. Trigger in `AdminUserService::applyFields`
After the existing `$user->save();` (line 130), add:
```php
if ($user->wasChanged('currency_id')) {
    $oldId = (int) $user->getOriginal('currency_id');
    $newId = (int) $user->currency_id;
    if ($oldId > 0 && $oldId !== $newId) {
        app(\App\Services\ClientCurrencyConverterService::class)
            ->convert($user, $oldId, $newId);
    }
}
```
- `wasChanged('currency_id')` is true only when currency actually changed; saves with unchanged currency are a no-op.
- `$user->fresh()` after conversion if downstream code in the request needs refreshed balances.

### 4. Audit log
At the end of `convert()`, `\Log::info('Client currency converted', ['user_id', 'old', 'new', counts per table, 'completed_at'])`. Lightweight, aids rollback forensics.

## Risks / known limitations
- **business_amount goes stale** on `transactions` and `cost_transactions` (explicitly accepted). `Transaction::get_sum_balance()` and any business-currency reports will be inconsistent until those rows are recomputed manually. Document in code comment.
- **Missing historical rate:** `RateByDate` falls back to latest available, then fires `currency:fetch-rates`, then `1.0`. If a `created_at` predates all stored rates, that row converts 1:1 (silent). Consider logging rows where the resolved rate equals `1.0` AND `$oldId != $newId` as a warning.
- **Irreversible:** physical rewrite cannot be undone from the data alone (the audit log captures old currency but not per-row pre-images). If reversibility is later required, add a snapshot table before implementing.
- **Large clients:** chunked processing inside one transaction; very large histories could hold locks long. Acceptable for now; revisit if needed (queued job is the upgrade path).
- **Concurrent writes** during conversion are protected by the DB transaction/locks; new transactions created mid-convert will use the new currency already (user row updated first).

## Validation
- Unit/feature test `tests/Feature/ClientCurrencyConversionTest.php`:
  - Seed a client (USD) with invoices (paid/unpaid/partially_paid/cancelled), invoice_items, timers, transactions, cost_transactions, recurring_invoice, withdrawal, a project.
  - Flip currency to EGP via the admin update path.
  - Assert each row's amount ≈ oldAmount × RateByDate(created_at, USD→EGP) within 0.01, and `currency_id` = EGP.
  - Assert `user_balance/total_paid/total_cost` and `project_balance/total_paid` match a freshly summed expectation.
  - Assert `Invoice::total()` still equals `paid + unpaid` for paid/partial invoices (consistency).
  - **Idempotency:** flip again USD→EGP on already-EGP data with a third currency; confirm rows already in EGP are NOT re-converted (double-conversion guard).
  - Assert editing the user WITHOUT changing currency performs no conversion (balance unchanged).
- Manual: `php artisan test --filter=ClientCurrencyConversionTest`.
- Lint/typecheck: run the project's configured `php artisan lint`/Pint and static analysis per AGENTS.md before handing off.

## Out of scope
- Recalculating `business_amount`.
- Converting hour/booking rates, salary, postpaid_limit.
- Marketplace module.
- Building an admin UI/preview or undo mechanism (could be a follow-up).
- Queued/async execution.
