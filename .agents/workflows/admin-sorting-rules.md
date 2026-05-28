---
description: Defines the default sorting rules and listing behaviors for Admin resource index pages, specifically for Users.
---


# Admin Listing & Sorting Rules

## Users Default Sort (ASC)

The old system established a specific workflow where the default listing for Users must always begin with **Ascending (ASC)** sorting by `id`.

**Why?**
- This ensures consistency with legacy views and data processing workflows where older accounts (or sequential progression) are prioritized in the default view.
- When sorting users, unless explicitly overridden by an `$request->get('direction')` parameter, the direction must resolve to `asc`.

### Implementation Example

In `UsersController::index()`, the logic must explicitly prefer `asc`:

```php
// Correct
$direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
$query->orderBy($sort, $direction);

// Incorrect (DO NOT USE)
$direction = $request->get('direction') === 'asc' ? 'asc' : 'desc'; // Defaults to desc
```

Always double-check that sorting defaults to `ASC` for Users.
