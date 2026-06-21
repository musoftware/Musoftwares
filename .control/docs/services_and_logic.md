# Services & Business Logic

To maintain clean and manageable code, the application enforces the concept of **Thin Controllers**.

## Centralized Business Rules
The `app/Services/` directory (and `Modules/{ModuleName}/Services/` for modules) is the definitive home for core business logic.

### Key Domains
1. **Financial & Billing Services:**
   - Management of internal wallets.
   - Handling of SaaS subscription logic.
   - Processing multi-currency conversions and dynamic pricing mechanisms.
2. **Operations & Tenant Services:**
   - Enforcing workspace data isolation for multi-tenancy.
   - Global activity logging.
   - Managing system-wide and tenant-specific configurations.
3. **Marketplace & Product Services:**
   - Managing the order fulfillment lifecycle.
   - Handling the generation, distribution, and validation of software serial keys.

By keeping business logic inside dedicated Service classes, controllers are restricted solely to HTTP request handling, validation mapping, and returning Inertia responses.
