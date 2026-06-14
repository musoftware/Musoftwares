# Platform Admin Wallet Adjustments

## Conceptual Difference: Platform Admin vs. ERP Module

When migrating legacy URL structures (such as `http://127.0.0.1:8000/admin/transactions/create?type=receive&user=69`) from the `musoftwares.com` monolith, it is critical to distinguish between **two completely separate financial systems** in the new modular architecture:

### 1. ERP Module (Tenant System)
- **Scope:** Used by the ERP User (the Tenant) to manage their *own* clients.
- **URLs:** Start with `erp/clients/...` (e.g., `erp/clients/69/wallet/adjust`).
- **Controller:** `Modules\ERP\Http\Controllers\WalletController`.
- **Purpose:** Handling money received from a tenant's end-client, or refunds to the end-client.

### 2. Main System (Platform Admin System)
- **Scope:** Used by the Super Admin to manage the wallets of *Platform Users* (like the Tenants themselves, or Freelancers).
- **URLs:** Start with `admin/transactions/...` (e.g., `admin/transactions/create?type=receive&user=69`).
- **Controller:** `App\Http\Controllers\Admin\AdminTransactionController`.
- **Purpose:** Charging a tenant for a subscription, adding funds to a freelancer's wallet, refunding a platform user.

## Migration & UI Implementation

The old Blade UI (`admin.transactions.create.blade.php`) heavily utilized Alpine.js components (`x-alpine-timer-balance`, `x-alpine-out-timer`) mapping to a specific `type` parameter (`receive`, `refund`, `send-money`, `charge`, `earn`).

### Backend Support
The backend for this was successfully ported to `App\Http\Controllers\Admin\AdminTransactionController@store`. It expects:
- `user`: The Platform User ID.
- `project`: (Optional) The Platform User's project ID.
- `type`: Enum `['timer-received', 'timer-due', 'out-timer-received', 'refund', 'earned', 'send']`.
- `data`: An array of transaction objects containing `amount`, `reason`, and `currency`.

### Frontend React Implementation
The missing frontend was implemented in `resources/js/Pages/Admin/Transactions/Create.tsx`.
- The URL query parameter `type` is mapped to the internal forms using a visual tab system.
- Legacy `type` URLs (e.g., `charge`, `send-money`) are automatically normalized in the component's state to match the strict backend validation rules (`timer-due`, `send`).
- The component uses the new `useForm` hook from Inertia to submit a batch array of transactions, maintaining parity with the legacy bulk-add feature.

## Lessons Learned
- **Do not blindly map `admin` routes to `erp` routes.** The `admin` prefix in legacy usually correlates to the Platform Admin, not the ERP Tenant Dashboard.
- When an `Index` or `Store` method exists in a Controller without a corresponding `Create` method or React View, it indicates a partially migrated feature that requires front-end reconstruction.



---
