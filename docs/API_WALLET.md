# Wallet & Transactions API

Base Endpoint: `/erp/api/wallet`

## Get Client Wallet Summary

`GET /erp/api/wallet/{clientId}`

Retrieves the current financial standing, locked escrow funds, and available balance for a specific client within the authenticated tenant's scope.

**Response:** (`200 OK`)
```json
{
  "data": {
    "id": 1,
    "tenant_id": 12,
    "client_id": 5,
    "balance": 125.50,
    "currency": "USD",
    "locked_amount": 100.00,
    "available": 25.50,
    "client": {
      "id": 5,
      "name": "Ahmed Mohamed",
      "email": "ahmed@example.com"
    },
    "updated_at": "2024-01-20T14:12:00Z"
  }
}
```

## List Wallet Transactions

`GET /erp/api/wallet/{clientId}/transactions`

**Query Parameters:**
- `type`: string (`invoice_issued` | `invoice_paid` | `commission_earned` | `withdrawal_requested` | `manual_credit` | `manual_debit`)
- `direction`: string (`debit` | `credit`)
- `from_date`: string (`YYYY-MM-DD`)
- `to_date`: string (`YYYY-MM-DD`)
- `page`: integer
- `per_page`: integer (`15` | `25` | `50`)

**Response:** (`200 OK`)
```json
{
  "data": [
    {
      "id": 100,
      "tenant_id": 12,
      "wallet_id": 1,
      "type": "invoice_paid",
      "direction": "credit",
      "amount": 1749.90,
      "amount_currency": "EGP",
      "business_amount": 36.08,
      "business_currency": "USD",
      "exchange_rate": 48.50,
      "exchange_rate_date": "2024-01-15",
      "balance_before": 89.42,
      "balance_after": 125.50,
      "reference": {
        "type": "Invoice",
        "id": 1,
        "identifier": "INV-2024-0001"
      },
      "note": "Payment received via wire transfer",
      "created_by": {
        "id": 2,
        "name": "Tenant Admin"
      },
      "created_at": "2024-01-15T10:32:00Z"
    }
  ],
  "links": {
    "next": null
  },
  "meta": {
    "total": 1,
    "current_page": 1
  }
}
```

## Manual Balance Credit

`POST /erp/api/wallet/{clientId}/credit`

Manually adds balance to a client's wallet (e.g., promotional credits, manual overpayment refunds).

**Request Body:**
```json
{
  "amount": 50.00,
  "currency": "USD",
  "note": "Promotional bonus credit for annual renewal"
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "transaction_id": 150,
    "wallet_id": 1,
    "new_balance": 175.50,
    "credited_amount": 50.00
  },
  "message": "Manual credit successfully applied to wallet."
}
```

**Validation Error Response:** (`422 Unprocessable Entity`)
```json
{
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount must be a strictly positive number."]
  }
}
```

## Manual Balance Debit

`POST /erp/api/wallet/{clientId}/debit`

Manually deducts funds from a client's wallet (e.g., chargebacks, agreed adjustments).

**Request Body:**
```json
{
  "amount": 25.00,
  "currency": "USD",
  "note": "Agreed refund deduction for service revision"
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "transaction_id": 151,
    "wallet_id": 1,
    "new_balance": 150.50,
    "debited_amount": 25.00
  },
  "message": "Manual debit successfully deducted from wallet."
}
```

**Note on Insufficient Balance:** If the client's available balance is lower than the requested debit amount, the endpoint returns HTTP `422` with a specific balance deficit error message.
