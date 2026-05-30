# Queue & Job Rules

## When to Use
Sending emails, generating large PDFs, communicating with external third-party APIs.

## Rules
1. Implement `ShouldQueue`.
2. Use `InteractsWithQueue`, `Queueable`, `SerializesModels`.
3. Handle failures via `failed(\Throwable $exception)`.
