---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Always use the correct properties for the Currency model when rendering dropdowns or displaying currency data.
---

# Rule: Correct Currency Model Property Access

## Problem Statement
When mapping over an array of currencies (e.g., `currencies.map()`) in a React/TSX frontend dropdown, developers often try to access `c.code` instead of the actual property `c.currency`. This results in empty or blank values in dropdown menus (like `<Select>`) because the `code` property does not exist on the `App\Models\Currency` model.

## Rules & Guidelines

### 1. The Currency Model Structure
The `currencies` database table and Eloquent model have specific column names. The primary attributes are:
- `id`: The primary key.
- `currency`: The 3-letter currency name (e.g., "USD", "EGP", "SAR"). **Do not confuse this with `code`.**
- `symbol`: The currency symbol (e.g., "$", "£").

### 2. Rendering Dropdowns
When mapping over a list of currencies in the frontend, you **must** use `c.currency` to access the 3-letter currency code.

**Example of Failure**:
```tsx
// ❌ INCORRECT (c.code does not exist, results in empty dropdown option)
{currencies.map(c => (
    <SelectItem key={c.id} value={String(c.id)}>{c.code}</SelectItem>
))}
```

**Example of Correct Pattern**:
```tsx
// ✅ CORRECT (Uses the actual database property c.currency)
{currencies.map(c => (
    <SelectItem key={c.id} value={String(c.id)}>{c.currency}</SelectItem>
))}
```

### 3. Formatting Fallbacks
If providing a hardcoded fallback when calling `formatCurrency()`, ensure you are accessing `currency` on the relation if the relation is present.

```tsx
// ❌ INCORRECT
formatCurrency(amount, invoice.currency?.code || 'EGP')

// ✅ CORRECT
formatCurrency(amount, invoice.currency?.currency || 'EGP')
```

### 4. Summary Checklist
- [ ] Are you accessing `c.currency` instead of `c.code` when rendering dropdown items?
- [ ] Are you passing the correct `currency` string to formatting helpers?



---


---
description: "Mandates that any currency-related changes must simultaneously handle backend and frontend (UI) without hardcoded fallbacks."
---

# Rule: Currency Implementation Strictness (Backend + UI Parity)

## Problem Statement
When dealing with currency modifications, changes made to the backend models or controllers (such as removing hardcoded fallbacks and strictly expecting currency objects/IDs) often break the frontend UI if it is not updated in parallel. The UI must always accurately reflect the dynamic currency properties returned from the backend, and never rely on hardcoded symbols (like `$`) or default currency codes (like `USD` or `EGP`).

## Rules & Guidelines

### 1. Simultaneous Backend and UI Updates
- **Never update backend currency logic without checking the frontend.**
- If you modify an Eloquent model, Transformer, Resource, or Controller to change how currencies are formatted or retrieved, you **MUST** simultaneously review and update the React/TSX views (e.g. `Show.tsx`, `Index.tsx`, `Create.tsx`) that consume that endpoint.

### 2. No Fallbacks in the Frontend
- The frontend must never use silent fallbacks for currencies (e.g., `project.currency ?? 'USD'`).
- The backend must provide the precise currency data structure (typically passing the currency object or fetching it directly via relation).
- If the backend returns `null` or a missing currency, the application should fail loudly on the backend rather than attempting to mask the error with a hardcoded `USD` or `EGP` on the frontend.

### 3. Always Pass Currency Objects to Inertia (Not Just IDs)
- **Never** just pass `currency_id` down to the frontend and expect the frontend to guess the currency.
- **Always** eagerly load the currency relation on the backend (e.g., `$invoice->load('currency')`) and pass the full `currency` object (which includes `currency` code and `symbol`) via Inertia props or API resources.
- The frontend must parse and format the currency using the passed object directly.

### 4. Read All Related Currency Rules
- Any task involving currency modification **MUST** cross-reference and comply with existing currency rules:
  - `no-hardcoded-currency.md`
  - `currency-dropdown-property.md`
  - `erp-financial-rules.md`
- Make sure that both `business_currency` and `client_currency` (or the specific record's currency) are preserved and correctly displayed.

### 5. Dynamic Currency Component Usage
- Always utilize the dynamic `<CurrencyDisplay />` component or `formatMoney()` helper in the UI.
- Ensure the prop passed is the actual currency object or the explicit currency string returned from the API, without local frontend overrides.



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Enforce strict ERP guidelines for base currency, client currency overrides, transaction-based income metrics, and expense summation.
---

# Rule: ERP Currency and Transaction Rules

## Problem Statement
Calculating financial performance or configuring currencies incorrectly leads to data inconsistency, wrong profit-and-loss reports, and issues with user billing. In the ERP system, we must enforce a strict, client-centric currency flow and compute monthly income directly from ledger transactions using clear type exclusions.

---

## Rules & Guidelines

### 1. ERP Base Currency vs. User Currency
- The ERP has a default system-wide base currency.
- **User Default**: Every ERP User (Tenant/Business Owner) inherits this base currency by default.
- **Multi-Currency Addon Requirement**: A User can only change their default user currency if they have an active subscription/purchase of the `multi-currency` addon.
- **Code Check**: Before allowing a User to change their default currency, always check addon access:
  ```php
  if (!$user->hasModuleSubscription('multi-currency')) {
      throw new \Exception(__('errors.multi_currency_addon_required'));
  }
  ```

---

### 2. Client-Centric Currency Flow
In the ERP system, the **Client** (the client of the User, not the User/Tenant themselves) defines the currency boundary.
- **Projects**: Every project must use the client's currency.
- **Invoices**: Every invoice must use the client's currency.
- **Transactions**: Every client transaction (wallet payments, ledger adjustments) must use the client's currency.
- **Constraint**: Do not fallback to the User's default currency or the base currency if a Client currency is configured. Ensure the relationship is loaded and validated:
  ```php
  // Project creation example
  $project->currency_id = $client->currency_id;
  
  // Invoice creation example
  $invoice->currency_id = $client->currency_id;
  ```

#### Multi-Currency Transaction Conversions (Exchange Rates)
When the ERP base business currency is different from the Client's currency (e.g., ERP base currency is `EGP` and the Client's currency is `USD`):
- The transaction amount must be recorded in the Client's currency (`amount` in `USD`).
- The `business_amount` must be converted to the ERP system's base currency using the exchange rate corresponding to the **date of the transaction**.
- **Exchange Rates Table**: The system uses the `currencies_exchanges` table (represented by the `App\Models\CurrenciesExchange` model) to store daily historical rates.
- **Lookup Method**: Always fetch the exchange rate using `\App\Models\CurrenciesExchange::RateByDate($date, $amount, $fromCurrencyId, $toCurrencyId)`. This ensures that we lock in the rate at the time/date of the transaction rather than using a dynamic real-time live rate that would retroactively alter past accounting metrics.

```php
// Example: Converting transaction to business currency on save/boot event
$date = $transaction->created_at ?? now();
$transaction->business_amount = \App\Models\CurrenciesExchange::RateByDate(
    $date,
    $transaction->amount,
    $transaction->currency_id,
    \App\Models\AdminSettings::business_currency()
);
```

---

### 3. Transactions as the Single Source of Truth for Income
- **Metric Source**: Monthly income and overall income metrics must be compiled solely from the **transactions** system, never directly from invoices.
- **Transaction Types**: Transactions must belong to one of these types:
  - `received`
  - `refunded`
  - `sent`
  - `used`
  - `earned`
- **Income Formula**:
  - If `refunded` and `sent` transactions are stored as negative values:
    $$\text{Income} = \sum \text{Transactions(received).business\_amount} + \sum (\text{Transactions(refunded).business\_amount} + \text{Transactions(sent).business\_amount})$$
  - **Multi-Currency Normalization**: You **MUST** sum the `business_amount` (normalized base currency amount) rather than the local/client `amount`. Summing different raw currencies (e.g. adding EGP directly to USD) is strictly forbidden.
  - **Caution**: Since `refunded` and `sent` amounts are stored as negative values, adding them to the `received` sum correctly decreases the income. Do not subtract them directly (i.e. `received - (refunded + sent)`) because subtracting a negative value results in addition (`minus minus is plus`).
- **Exclusion of `used`**: Do NOT sum or include transactions of type `used` in the monthly/profit calculations. The `used` type tracks internal wallet utilization (e.g. paying invoices using wallet balance) and including it would cause double-counting.
- **Inclusion of `earned`**: Transactions of type `earned` should also be added to income.

#### Income Calculation Example:
```php
// ✅ CORRECT: Summing business_amount (normalized base currency)
$credits = Transaction::whereIn('type', ['received', 'earned'])
    ->whereMonth('created_at', $month)
    ->sum('business_amount');

// Note: refunded and sent transactions have negative amounts (e.g., -150.00 in business currency)
$deductions = Transaction::whereIn('type', ['refunded', 'sent'])
    ->whereMonth('created_at', $month)
    ->sum('business_amount');

// ✅ CORRECT: Adding the negative deductions correctly subtracts them
$monthlyIncome = $credits + $deductions;
```

---

### 4. Expense & Cost Summation
- **No Type or Direction**: Cost transactions (expenses) must have no `type` and no `direction` columns or properties. They do not have sub-type classifications in the same transaction sense.
- **Cost Calculation**: Simply sum all entries using `business_amount` to compute the total cost/expenditures:
  ```php
  // ✅ CORRECT: Summing business_amount for currency consistency
  $totalExpenses = Expense::whereMonth('created_at', $month)->sum('business_amount');
  ```
- **Net Profit**:
  $$\text{Net Profit} = \text{Monthly Income} - \text{Total Expenses}$$

### 5. Client & Project Direct Ledger Linking
- **No Client Wallet Layer**: Do not use an intermediate client wallet model or table (such as `ClientWallet` or `erp_client_wallets`) to track funds or intermediate states.
- **Direct Foreign Keys**:
  - Every transaction (`Transaction`) and cost transaction (`CostTransaction` / expense) must be linked directly to the **Client** (using `client_id` referencing `erp_tenant_clients`) and/or **Project** (using `project_id` referencing `projects`).
  - Models must define direct relationships to the client:
    ```php
    public function client(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\Client::class, 'client_id');
    }
    ```
- **Dynamic Balance & Computational Locked Balance**:
  - Compute client or project financial health directly by summing their associated transactions rather than pulling a static `balance` from a wallet model.
  - **Locked Balance is purely computational**: Do not use manual lock/unlock transaction structures or persist locked funds in the database. Instead, calculate the locked balance dynamically as the sum of `unpaidAmount()` for all outstanding/pending invoices (status is `sent` or `partial`) for that client.
  - **Locked Balance Formula**:
    $$\text{Locked Balance} = \sum \text{Client's Invoices(Unpaid).unpaidAmount()}$$

---

### 6. Summary Checklist
- [ ] Does the Project/Invoice/Transaction use the `client->currency_id`?
- [ ] Is the User currency modification guarded by a `multi-currency` addon check?
- [ ] Are dashboard income metrics sourced directly from transaction sums?
- [ ] Are transactions of type `Used` excluded from profit/income calculations?
- [ ] Are expenses summed entirely without sub-type classification checks?
- [ ] Are all transactions and cost transactions linked directly to the client/project (without an intermediate wallet model)?
- [ ] Is the client's locked balance calculated dynamically as the sum of unpaid amounts on their pending invoices?




---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Never use hardcoded currency in invoices, expenses, or transactions. Always use dynamic currency models or configuration.
---

# Rule: Never Use Hardcoded Currency in Invoices, Expenses, or Transactions

## Problem Statement
Hardcoding currency codes (e.g., `'USD'`, `'EGP'`, `'SAR'`) or symbols (e.g., `'$'`, `'£'`) in invoices, expenses, or transaction logic violates the multi-currency architecture. It leads to incorrect dashboard calculations, broken formatting when local currencies change, and hard-to-maintain code.

## Rules & Guidelines

### 1. Database & Model Level
- **Do NOT hardcode currency codes** as fallback values in queries, migrations, or database updates.
- **Always use `currency_id`** referencing the `currencies` table instead of raw currency strings.
- Rely on the `currency` relationship of the model (`$invoice->currency`, `$transaction->currency`, etc.).

### 2. Controller & Backend Calculations
- When computing business-level aggregates (e.g., total expenses, total sales), never assume a default currency is `'USD'` or `'EGP'`.
- Retrieve the configured business currency using:
  ```php
  \App\Models\AdminSettings::business_currency()
  ```
  or the appropriate settings helper.
- For local transactions, use the transaction's specific `currency_id` or the user's associated wallet/client currency.

### 3. Frontend & UI Display
- **Never** prefix or suffix amounts with hardcoded symbols or codes (e.g., `value + " EGP"` or `"$ " + value`).
- Always pass the currency object (containing `currency` and `symbol`) from the backend to the frontend.
- Format all monetary values dynamically using currency formatters/helpers that accept the currency model/property as an argument:
  ```jsx
  // ❌ INCORRECT (Hardcoded)
  <span>$ {amount}</span>
  <span>{amount} EGP</span>

  // ✅ CORRECT (Dynamic)
  <span>{formatCurrency(amount, currency)}</span>
  ```

### 4. Transactions, Invoices, and Expenses Normalization
- Always map both layers (Client currency and Business currency) to the frontend:
  ```php
  [
      'amount' => $this->amount,
      'currency' => $this->currency_id ?? $this->currency,
      'business_amount' => $this->business_amount ?? $this->business_total(),
      'business_currency' => \App\Models\AdminSettings::business_currency(),
  ]
  ```

### 5. Never Use Fallback Currencies (No Fallbacks Policy)
- **Do NOT provide silent default fallbacks** (e.g. `?? 'USD'` or `?? 'EGP'`) in controllers, models, resources, database migrations, or helper functions.
- If a currency configuration or model association is missing or null, the application **MUST NOT** silently fall back to a hardcoded code/symbol. Instead, let the operation fail loudly (throw an exception or return a validation error) so the configuration issue is identified and resolved immediately.
- Never write code like:
  ```php
  // ❌ INCORRECT (Silent Fallback)
  $currencyName = $transaction->currency?->currency ?? 'USD';
  ```
  Instead, ensure the relation is required or explicitly handle the missing state:
  ```php
  // ✅ CORRECT (No Fallback / Fail Loud)
  if (!$transaction->currency) {
      throw new \Exception("Transaction {$transaction->id} is missing an associated currency relation.");
  }
  $currencyName = $transaction->currency->currency;
  ```

### 6. NEVER Fallback to Global System Currency (`AdminSettings`)
- **CRITICAL**: The ERP and Freelance modules are multi-tenant. Each user/tenant has their own isolated data and base currency.
- `\App\Models\AdminSettings::business_currency()` represents the **Super Admin / Global System Owner's** currency (e.g., the platform's native currency).
- **NEVER** use `\App\Models\AdminSettings::business_currency()` as a fallback or default when a User, Client, or Tenant's `currency_id` is null or missing.
- Doing so violates tenant isolation and mixes the platform's global currency with the user's local accounting. 
- **Example of Failure**:
  ```php
  // ❌ INCORRECT (Violates tenant isolation)
  $userCurrencyId = $user->currency_id ? $user->currency_id : \App\Models\AdminSettings::business_currency()->id;
  ```
- **Example of Correct Pattern**:
  ```php
  // ✅ CORRECT (Respects tenant boundaries, fails loudly or expects the user to have a currency)
  $userCurrencyId = (int) $user->currency_id;
  ```

 # # #   7 .   N E V E R   F o r m a t   C u r r e n c y   i n   B a c k e n d   M o d e l s   ( N o   \  o r m a t t e d _ \   G e t t e r s ) 
 -   * * C R I T I C A L * * :   D o   N O T   c r e a t e   L a r a v e l   a c c e s s o r   m e t h o d s   l i k e   \ g e t F o r m a t t e d A m o u n t A t t r i b u t e ( ) \   o r   a p p e n d   f i e l d s   l i k e   \  o r m a t t e d _ a m o u n t \   i n   m o d e l s   o r   A P I   r e s o u r c e s . 
 -   C u r r e n c y   f o r m a t t i n g   i s   s t r i c t l y   a   * * f r o n t e n d   p r e s e n t a t i o n   c o n c e r n * * . 
 -   * * W h y * * :   F o r m a t t i n g   i n   t h e   b a c k e n d   c o u p l e s   t h e   m o d e l   t o   g l o b a l / s t a t i c   f o r m a t t i n g   r u l e s ,   b r e a k i n g   m u l t i - c u r r e n c y   s u p p o r t ,   l o c a l e   d e t e c t i o n ,   a n d   c l i e n t - s i d e   t e n a n t   i s o l a t i o n . 
 -   * * E x a m p l e   o f   F a i l u r e * * : 
     \ \ \ p h p 
     / /   L'  I N C O R R E C T   ( B a c k e n d   f o r m a t t i n g ) 
     p u b l i c   f u n c t i o n   g e t F o r m a t t e d A m o u n t A t t r i b u t e ( )   { 
             r e t u r n   \ - > a m o u n t   .   '   '   .   \ - > c u r r e n c y - > c u r r e n c y ; 
     } 
     \ \ \ 
 -   * * E x a m p l e   o f   C o r r e c t   P a t t e r n * * : 
     P a s s   t h e   r a w   n u m e r i c   \  m o u n t \   a n d   t h e   \ c u r r e n c y \   r e l a t i o n   o b j e c t   d i r e c t l y   t o   t h e   f r o n t e n d .   T h e n   f o r m a t   i t   i n   R e a c t / I n e r t i a : 
     \ \ \ 	 s x 
     / /   '  C O R R E C T   ( F r o n t e n d   f o r m a t t i n g ) 
     i m p o r t   {   f o r m a t M o n e y   }   f r o m   ' @ / l i b / u t i l s ' ; 
     
     < s p a n > { f o r m a t M o n e y ( i n v o i c e . a m o u n t ,   i n v o i c e . c u r r e n c y ) } < / s p a n > 
     \ \ \ 
  
 


---


---
name: Platform Admin Wallet Adjustments
description: Documents how the platform admin system manages wallets and transactions for platform users (tenants, freelancers).
---

# Platform Admin Wallet Adjustments

## Conceptual Difference: Platform Admin vs. ERP Module

When migrating legacy URL structures (such as `http://127.0.0.1:8000/admin/transactions/create?type=receive&user=69`) from the `musoftwares.com` monolith, it is critical to distinguish between **two completely separate financial systems** in the new modular architecture:

### 1. ERP Module (Tenant System)
- **Scope:** Used by the ERP User (the Tenant) to manage their *own* clients.
- **URLs:** Start with `erp/clients/...` (e.g., `erp/clients/69/wallet/adjust`).
- **Controller:** `Modules\ERP\Http\Controllers\WalletController`.
- **Purpose:** Handling money received from a tenant's end-client, or refunds to the end-client.

### 2. Main System (Platform Admin System)
- **Scope:** Used by the Super Admin to manage the wallets of *Platform Users* (like the Tenants themselves, or Freelancers).
- **URLs:** Start with `admin/transactions/...` (e.g., `admin/transactions/create?type=receive&user=69`).
- **Controller:** `App\Http\Controllers\Admin\AdminTransactionController`.
- **Purpose:** Charging a tenant for a subscription, adding funds to a freelancer's wallet, refunding a platform user.

## Migration & UI Implementation

The old Blade UI (`admin.transactions.create.blade.php`) heavily utilized Alpine.js components (`x-alpine-timer-balance`, `x-alpine-out-timer`) mapping to a specific `type` parameter (`receive`, `refund`, `send-money`, `charge`, `earn`).

### Backend Support
The backend for this was successfully ported to `App\Http\Controllers\Admin\AdminTransactionController@store`. It expects:
- `user`: The Platform User ID.
- `project`: (Optional) The Platform User's project ID.
- `type`: Enum `['timer-received', 'timer-due', 'out-timer-received', 'refund', 'earned', 'send']`.
- `data`: An array of transaction objects containing `amount`, `reason`, and `currency`.

### Frontend React Implementation
The missing frontend was implemented in `resources/js/Pages/Admin/Transactions/Create.tsx`.
- The URL query parameter `type` is mapped to the internal forms using a visual tab system.
- Legacy `type` URLs (e.g., `charge`, `send-money`) are automatically normalized in the component's state to match the strict backend validation rules (`timer-due`, `send`).
- The component uses the new `useForm` hook from Inertia to submit a batch array of transactions, maintaining parity with the legacy bulk-add feature.

## Lessons Learned
- **Do not blindly map `admin` routes to `erp` routes.** The `admin` prefix in legacy usually correlates to the Platform Admin, not the ERP Tenant Dashboard.
- When an `Index` or `Store` method exists in a Controller without a corresponding `Create` method or React View, it indicates a partially migrated feature that requires front-end reconstruction.



---


