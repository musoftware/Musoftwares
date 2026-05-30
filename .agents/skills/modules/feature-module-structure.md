# Feature Module Structure

## Best Practices
1. **Keep Controllers Thin**: Delegate complex business logic to `Services/` or `Actions/`.
2. **Form Requests**: Always use FormRequests in `Http/Requests/` for validation.
3. **API Resources**: Use `Transformers/` or `Http/Resources/` to format JSON responses for Inertia or APIs.
