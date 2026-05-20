# Database Schema Rules (AI Skill)

When generating Laravel migrations or interacting with the database, enforce these invariants.

## 1. Tenant Scoping
- **Mandatory Columns:** Any table storing tenant-specific data (clients, invoices, projects, transactions) must have `tenant_id`.
- **Constraint Definition:** Use `$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();`.
- **Performance:** For high-volume tables, combine `tenant_id` with frequently queried columns in composite indexes: `$table->index(['tenant_id', 'status']);`.

## 2. Multi-Currency Schema
MuSoftware handles global invoicing and multi-currency ledgers.
- **Amounts:** Always store the nominal amount and the standardized "business" amount.
- **Precision:** Financial columns must use `decimal('...', 15, 2)` or `(20, 8)` for ledgers.
- **Standard Columns:**
  ```php
  $table->decimal('amount', 15, 2)->default(0);
  $table->string('amount_currency', 3);
  $table->decimal('business_amount', 15, 2)->default(0);
  $table->string('business_currency', 3);
  $table->decimal('exchange_rate', 15, 6)->default(1);
  $table->date('exchange_rate_date')->nullable();
  ```

## 3. Polymorphic Relations
- Utilize Laravel's `morphTo()` and `morphMany()` for global entities like `Wallets` and `Activities`.
- `Modules\Core\Models\Wallet` maps to `owner_type` and `owner_id`. A wallet could be owned by a `User` (platform), a `Tenant` (business), or a `TenantClient` (customer).

## 4. Idempotency & Tracking
- Migrations must be defensively coded. Wrap `Schema::table` index additions in `DB::getDriverName() === 'mysql'` existence checks, or use try-catch to prevent crash loops when deploying over existing data.
- Never hard-delete financial records. Use state columns (e.g., `status = 'cancelled'`) or soft-deletes.
