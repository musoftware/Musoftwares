# Permissions & Roles Rules

## Rules
1. Use Spatie Permission.
2. Guard backend routes using middleware: `middleware('permission:edit_invoices')`.
3. Pass `can` or `permissions` array to frontend Inertia props to conditionally hide UI buttons.
