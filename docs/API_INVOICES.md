# Invoices API

Base Endpoint: `/erp/api/invoices`

## List Invoices

`GET /erp/api/invoices`

**Query Parameters:**
- `status`: string (`draft` | `sent` | `partial` | `paid` | `cancelled` | `refunded`)
- `client_id`: integer (Filter by specific tenant client)
- `from_date`: string (`YYYY-MM-DD`, filters `issued_at` or `created_at`)
- `to_date`: string (`YYYY-MM-DD`)
- `sort`: string (`issued_at`, `due_date`, `total`, `-issued_at`)
- `page`: integer (default: 1)
- `per_page`: integer (`15` | `25` | `50`)

**Response:** (`200 OK`)
```json
{
  "data": [
    {
      "id": 1,
      "invoice_number": "INV-2024-0001",
      "client_id": 5,
      "client": {
        "id": 5,
        "name": "Ahmed Mohamed",
        "email": "ahmed@example.com"
      },
      "status": "paid",
      "amount": 1749.90,
      "amount_currency": "EGP",
      "business_amount": 36.08,
      "business_currency": "USD",
      "exchange_rate": 48.50,
      "exchange_rate_date": "2024-01-15",
      "total": 1749.90,
      "due_date": "2024-01-31",
      "issued_at": "2024-01-15T10:32:00Z",
      "paid_at": "2024-01-20T14:12:00Z",
      "items_count": 3,
      "created_at": "2024-01-15T10:32:00Z"
    }
  ],
  "links": {
    "next": "/erp/api/invoices?page=2"
  },
  "meta": {
    "total": 45
  }
}
```

## Create Invoice

`POST /erp/api/invoices`

**Request Body:**
```json
{
  "client_id": 5,
  "issued_at": "2024-01-15",
  "due_date": "2024-01-31",
  "currency": "EGP",
  "discount_amount": 0.00,
  "tax_rate": 14.00,
  "notes": "Thank you for your business.",
  "items": [
    {
      "type": "simple",
      "title": "Initial Setup & Configuration",
      "unit_price": 500.00,
      "quantity": 1
    },
    {
      "type": "quantity",
      "title": "API Endpoint Integration",
      "unit_price": 200.00,
      "quantity": 3
    }
  ],
  "costs": [
    {
      "title": "Subcontractor UI Development",
      "amount": 300.00
    }
  ]
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "id": 1,
    "invoice_number": "INV-2024-0001",
    "total": 1254.00,
    "status": "draft"
  },
  "message": "Invoice created successfully"
}
```

**Validation Error Response:** (`422 Unprocessable Entity`)
```json
{
  "message": "Validation failed",
  "errors": {
    "client_id": ["The selected client is invalid."],
    "items": ["At least one line item is required."]
  }
}
```

## Get Invoice Details

`GET /erp/api/invoices/{id}`

**Response:** (`200 OK`)
```json
{
  "data": {
    "id": 1,
    "invoice_number": "INV-2024-0001",
    "tenant_id": 12,
    "client_id": 5,
    "status": "draft",
    "amount": 1100.00,
    "amount_currency": "EGP",
    "tax_rate": 14.00,
    "tax_amount": 154.00,
    "total": 1254.00,
    "items": [
      {
        "id": 10,
        "type": "timer",
        "title": "Consultation & Architecture",
        "unit_price": 50.00,
        "quantity": 4.5,
        "total": 225.00,
        "timer_sessions": [
          {
            "id": 20,
            "started_at": "2024-01-15T10:00:00Z",
            "stopped_at": "2024-01-15T10:45:00Z",
            "duration_seconds": 2700
          }
        ]
      }
    ],
    "costs": [
      {
        "id": 15,
        "title": "Subcontractor UI Development",
        "amount": 300.00,
        "payment_status": "unpaid"
      }
    ]
  }
}
```

## Update Invoice

`PATCH /erp/api/invoices/{id}`

**Request Body:** Same schema as `POST /erp/api/invoices` (Partial payload updates allowed).
**Note:** Only invoices with a status of `draft` can be updated. Attempts to edit `sent` or `paid` invoices will return HTTP `403 Forbidden` or `422 Unprocessable Entity`.

## Mark Invoice as Paid

`POST /erp/api/invoices/{id}/mark-paid`

**Request Body:**
```json
{
  "paid_at": "2024-01-20T14:12:00Z",
  "payment_method_note": "Direct Wire Transfer #99812",
  "deduct_from_wallet": false
}
```

**Response:** (`200 OK`)
```json
{
  "data": {
    "id": 1,
    "status": "paid",
    "paid_at": "2024-01-20T14:12:00Z"
  },
  "message": "Invoice successfully marked as paid"
}
```

**Platform Side-Effects Triggered:**
1. A permanent credit transaction is written to the tenant's `client_wallet_transactions` ledger.
2. If the client was referred, multi-level affiliate commission calculations run and write pending awards to `client_referral_earnings`.
3. If `deduct_from_wallet` is true, the client's wallet balance is checked and debited accordingly.

## Delete / Cancel Invoice

`DELETE /erp/api/invoices/{id}`

**Response:** (`204 No Content`)
- Only `draft` invoices can be deleted permanently from the database.
- `sent` or `partial` invoices cannot be deleted; they must be marked as `cancelled` via `POST /erp/api/invoices/{id}/cancel`.

## Live Timer Operations

### Start Timer Session
`POST /erp/api/invoices/{invoiceId}/items/{itemId}/timer/start`

**Response:** (`200 OK`)
```json
{
  "data": {
    "session_id": 20,
    "started_at": "2024-01-15T10:00:00Z",
    "currently_running": true
  }
}
```

### Stop Timer Session
`POST /erp/api/invoices/{invoiceId}/items/{itemId}/timer/stop`

**Response:** (`200 OK`)
```json
{
  "data": {
    "session_id": 20,
    "stopped_at": "2024-01-15T10:45:00Z",
    "duration_seconds": 2700,
    "total_minutes_billed": 45,
    "current_item_total": 225.00
  }
}
```

**Real-time Broadcast Triggered:**
- **Event:** `TimerUpdated`
- **WebSocket Channel:** `private-timer.{itemId}`
- **Payload:** `{"duration_seconds": 2700, "running": false}`
