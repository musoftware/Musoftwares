# API Documentation

All endpoints return standard JSON payloads.
**Authentication:** Inertia Session (Session cookies, no JWT tokens).
**CORS Policy:** Strict Same-Origin / Trusted Proxies only.

## Response Formatting

Success Response (`200 OK` / `201 Created`):
```json
{
  "data": {
    "id": 1,
    "invoice_number": "INV-2024-0001",
    "status": "paid"
  },
  "message": "Resource successfully retrieved"
}
```

Client Validation Error (`422 Unprocessable Entity`):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["The amount must be greater than 0."],
    "client_id": ["The selected client does not exist in your tenant scope."]
  }
}
```

General Error (`400 Bad Request` / `403 Forbidden` / `404 Not Found` / `500 Internal Error`):
```json
{
  "message": "You do not have permission to access this resource."
}
```

## Pagination Architecture

Collection endpoints utilize Cursor-based pagination or standard Length-Aware pagination depending on ordering requirements.

Standard Paginated Response Payload:
```json
{
  "data": [
    {
      "id": 1,
      "title": "Full Stack Dev"
    }
  ],
  "links": {
    "first": "/api/invoices?page=1",
    "last": "/api/invoices?page=10",
    "prev": "/api/invoices?page=2",
    "next": "/api/invoices?page=4"
  },
  "meta": {
    "current_page": 3,
    "from": 31,
    "to": 45,
    "total": 450,
    "per_page": 15
  }
}
```

## Rate Limiting

Standard rate limiting is enforced via Laravel middleware (`throttle:api`):
- **Authenticated Clients:** 100 requests per minute per IP/Session.
- **Exceeded Limit:** Returns HTTP `429 Too Many Requests` with `Retry-After` headers.

## API Versioning

Current active API version: `v1` (internal routes utilize direct module prefixes, e.g., `/erp/invoices`, `/freelance/jobs`, and `/api/conversations`).

## Authentication & Headers

Because the primary frontend consumer is Inertia.js running in the browser, requests rely on standard session cookies (`laravel_session` and `XSRF-TOKEN`).
When making asynchronous HTTP requests via Axios or fetch:
- The `X-Requested-With: XMLHttpRequest` header must be present.
- Axios automatically attaches the `X-XSRF-TOKEN` cookie value.

For third-party or mobile API integrations:
1. `POST /api/login` with email and password.
2. The server returns a session cookie and CSRF token.
3. Attach these cookies to all subsequent HTTP requests.

## Module Specific API Guides

- [Invoices API](./API_INVOICES.md)
- [Wallet & Transactions API](./API_WALLET.md)
- [Real-time Chat API](./API_CHAT.md)
- [Freelance Marketplace API](./API_FREELANCE.md)
- [Service Marketplace API](./API_MARKETPLACE.md)
