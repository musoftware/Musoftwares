# API Response Format Rules

## Rules
1. Never leak Laravel database exception messages or stack traces.
2. Return standard JSON structures: `{ "status": "success|error", "message": "...", "data": {} }`.
3. Catch all model not found exceptions gracefully.
