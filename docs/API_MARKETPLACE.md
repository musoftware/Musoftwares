# Service Marketplace API

The Marketplace module provides a Fiverr-style productized service catalog, supporting multi-tier service packages (Basic, Standard, Premium), seller portfolios, and escrow-backed order lifecycles.

Base Endpoint: `/marketplace/api`

## Services Catalog API

### Browse Published Services
`GET /marketplace/api/services`

**Query Parameters:**
- `category_id`: integer
- `search`: string (Matches against title, description, and JSON tags)
- `min_price`: decimal
- `max_price`: decimal
- `sort`: string (`-rating_avg`, `-total_orders`, `-created_at`, `price_asc`)
- `page`: integer
- `per_page`: integer (`16` | `32` | `64`)

**Response:** (`200 OK`)
```json
{
  "data": [
    {
      "id": 100,
      "title": "Full Stack SaaS Development in Laravel & React",
      "slug": "full-stack-saas-development-laravel-react",
      "seller": {
        "id": 5,
        "name": "Mahmoud Ahmed",
        "avatar": "https://api.dicebear.com/7.x/avataaars/svg?seed=Mahmoud",
        "rating_avg": 4.95,
        "total_reviews": 48
      },
      "cover_image": "/storage/services/covers/saas-architecture.jpg",
      "starting_price": 499.00,
      "amount_currency": "USD",
      "is_featured": true,
      "category_name": "Web Development",
      "tags": ["laravel", "react", "inertia", "saas"]
    }
  ],
  "meta": {
    "total": 542,
    "per_page": 16
  }
}
```

### Get Service Specification & Packages
`GET /marketplace/api/services/{slug}`

Retrieves complete service detail including all active tier packages (Basic, Standard, Premium) and seller portfolio highlights.

**Response:** (`200 OK`)
```json
{
  "data": {
    "id": 100,
    "title": "Full Stack SaaS Development in Laravel & React",
    "slug": "full-stack-saas-development-laravel-react",
    "description": "I will construct a highly scalable modular monolith...",
    "images": [
      "/storage/services/gallery/screen1.jpg",
      "/storage/services/gallery/screen2.jpg"
    ],
    "packages": [
      {
        "id": 301,
        "tier": "basic",
        "title": "MVP Architecture",
        "description": "Basic authentication, 2 database tables, standard UI styling.",
        "price": 499.00,
        "amount_currency": "USD",
        "delivery_days": 7,
        "revisions": 2,
        "features": ["Source Code", "Database Migration", "1 Month Support"]
      },
      {
        "id": 302,
        "tier": "premium",
        "title": "Enterprise Multi-Tenant SaaS",
        "description": "Complete multi-tenancy, real-time chat, wallet escrow, recurring cron billing.",
        "price": 2499.00,
        "amount_currency": "USD",
        "delivery_days": 21,
        "revisions": 5,
        "features": ["Source Code", "Multi-tenancy", "WebSockets", "Priority 24/7 Support"]
      }
    ]
  }
}
```

### Create Service Offering
`POST /marketplace/api/services`

**Request Body:**
```json
{
  "category_id": 1,
  "title": "Professional Laravel Reverb Real-Time Setup",
  "description": "Complete setup of WebSocket channels, event broadcasting, and React hooks...",
  "tags": ["laravel", "websockets", "reverb"],
  "cover_image_path": "/storage/tmp/reverb-banner.png",
  "packages": [
    {
      "tier": "basic",
      "title": "Single Channel Echo Setup",
      "description": "Configuration of Reverb server and a single private chat channel.",
      "price": 250.00,
      "amount_currency": "USD",
      "delivery_days": 3,
      "revisions": 1,
      "features": ["Reverb Config", "Echo.js Hook"]
    }
  ]
}
```

**Response:** (`201 Created`)
- Note for Client/Freelancer submissions: Status defaults to `pending` awaiting manual moderation.

### Update Service Offering
`PATCH /marketplace/api/services/{id}`

### Delete Service Offering
`DELETE /marketplace/api/services/{id}`

---

## Admin Moderation Endpoints

### Approve Service
`POST /marketplace/api/services/{id}/approve`

**Response:** (`200 OK` - Status updates to `active`).

### Reject Service
`POST /marketplace/api/services/{id}/reject`

**Request Body:** `{"rejection_note": "Cover image contains copyrighted branding material."}`

### Feature Service
`PATCH /marketplace/api/services/{id}/feature`

---

## Orders & Escrow API

### Purchase a Service Package (Escrow Checkout)
`POST /marketplace/api/orders`

**Execution Flow & Verification:**
1. Validates package existence and pricing.
2. Checks buyer's wallet for available funds matching the required `price`.
3. Deducts funds from buyer's wallet and records an immutable debit transaction.
4. Escrows funds inside the system ledger.
5. Instantiates an order active record in `marketplace_orders`.
6. Creates an isolated polymorphic chat conversation channel between buyer and seller.

**Request Body:**
```json
{
  "service_id": 100,
  "package_id": 302,
  "buyer_note": "Please find attached our UI mockups and database schema requirements."
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "order_id": 5001,
    "status": "active",
    "delivery_due_at": "2024-02-10T15:00:00Z",
    "conversation_id": 18
  },
  "message": "Order placed successfully. Funds have been secured in escrow."
}
```

### List User Orders
`GET /marketplace/api/orders`

**Query Parameters:**
- `role`: string (`buyer` | `seller`)
- `status`: string (`active` | `delivered` | `completed` | `disputed` | `cancelled`)

### Get Order Details
`GET /marketplace/api/orders/{id}`

### Deliver Order (Seller Action)
`POST /marketplace/api/orders/{id}/deliver`

**Request Body:**
```json
{
  "delivery_note": "Here is the complete source code bundle and deployment instructions.",
  "attachments": ["/storage/deliveries/final-build-v1.zip"]
}
```

**Response:** (`200 OK` - Status transitions to `delivered`).

### Complete & Sign Off Order (Buyer Action)
`POST /marketplace/api/orders/{id}/complete`

Releases escrow funds to the seller's wallet after calculating platform commission (`commission_rate`).

### Open Dispute
`POST /marketplace/api/orders/{id}/dispute`

**Request Body:** `{"reason": "Seller unresponsive and did not deliver requested features."}`
Transitions order status to `disputed` and alerts platform administrators.

---

## Categories & Portfolio API

### List Service Categories
`GET /marketplace/api/categories`

### List Portfolio Categories
`GET /marketplace/api/portfolio-categories`

### Create Portfolio Showcase Item
`POST /marketplace/api/portfolio-items`
