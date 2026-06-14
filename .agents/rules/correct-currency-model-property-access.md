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
