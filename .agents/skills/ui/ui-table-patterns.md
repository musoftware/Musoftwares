# UI Table Patterns

## Rules
1. **Single Action Menu**: NEVER place multiple action buttons inline. Always use a single `...` (ellipsis) DropdownMenu for row actions to preserve horizontal space.
2. Support mobile responsive stacking.
3. Avoid loading >100 rows; use Laravel pagination (`paginate(15)`).
