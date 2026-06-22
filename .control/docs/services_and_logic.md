# Services and Business Logic

Musoftwares centralizes its core business rules in the `app/Services/` directory. This pattern ensures that controllers remain "thin" and focused solely on HTTP request/response handling.

## Key Service Domains

### 1. Financial & Billing Services
**Responsibilities:**
- Wallet management
- Subscription handling
- Multi-currency conversions
- Dynamic pricing logic

### 2. Operations & Tenant Services
**Responsibilities:**
- Workspace data isolation (multi-tenancy)
- Activity logging
- System-wide configurations

### 3. Marketplace & Product Services
**Responsibilities:**
- Order fulfillment processes
- Serial keys generation and management
