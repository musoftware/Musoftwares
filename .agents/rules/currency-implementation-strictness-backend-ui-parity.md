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
