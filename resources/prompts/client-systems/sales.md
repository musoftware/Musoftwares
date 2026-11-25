## 7. Reporting sales

Musoftwares takes {{SALES_PERCENT}}% of this system's sales, so every sale must be reported.

`POST {{API_BASE}}/sales` (signed as in section 1) with body:

```json
{"sales": [{"id": "order-1042", "amount": 350.00, "currency": "{{CURRENCY}}", "sold_at": "2026-11-25T14:30:00+02:00", "status": "paid"}]}
```

- Report a sale when it is fully paid (`status: "paid"`). Report it again with the same `id` and `status: "refunded"` if it is refunded.
- `id` is the system's own order or invoice id, unique and never reused. Sending the same sale twice is safe; the newest data wins until the sale is billed.
- `amount` is the paid total of the sale in `{{CURRENCY}}`, a number with up to 2 decimals. `sold_at` is ISO 8601 with a time zone.
- Send only these five fields. Never send customer names, phones, emails or addresses.
- Save each sale to a local "to report" queue in the same database transaction that marks it paid, then send the queue in batches of at most {{MAX_SALES}}. Mark a sale sent only after the answer has `"ok": true`. Retry failures later with a growing delay. Ids listed in `already_billed` are done.
- Reporting runs from the server side, together with the scheduled check-in or right after each payment.

