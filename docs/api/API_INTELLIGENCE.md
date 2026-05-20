# Musoftware Platform — Complete API Intelligence

> **Type**: All REST Endpoints + WebSocket Events + Runtime Local API  
> **Format**: Method | Path | Auth | Params | Response

---

## 1. Platform Web Routes (Inertia/Full-Page)

### Authentication (`routes/auth.php`)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /login | guest | Login page |
| POST | /login | guest | Authenticate |
| POST | /logout | auth | Logout |
| GET | /register | guest | Registration page |
| POST | /register | guest | Create account |
| GET | /forgot-password | guest | Forgot password page |
| POST | /forgot-password | guest | Send reset link |
| GET | /reset-password/{token} | guest | Reset password form |
| POST | /reset-password | guest | Update password |
| GET | /verify-email | auth | Email verification notice |
| POST | /email/verification-notification | auth | Resend verification |

### Core (Authenticated)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | / | none | Public home (redirects to login/register) |
| GET | /dashboard | auth+verified+onboarding | Main dashboard |
| GET | /profile | auth | Profile edit |
| PATCH | /profile | auth | Update profile |
| DELETE | /profile | auth | Delete account |
| GET | /notifications | auth | Notification center |
| POST | /notifications/{id}/mark-read | auth | Mark notification read |
| POST | /notifications/mark-all-read | auth | Mark all read |
| GET | /onboarding-wizard | auth+verified | Onboarding form |
| POST | /onboarding-wizard | auth+verified | Save onboarding |
| POST | /product-tour/status | auth+verified | Update tour state |
| GET | /search | auth+verified | Global search |
| GET | /messages | auth+verified | Message center |
| POST | /messages/direct | auth+verified | Send direct message |
| GET | /activity | auth+verified | Activity feed page |

### KYC
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /kyc | auth+verified | KYC submission page |
| POST | /kyc/upload | auth+verified | Upload document |
| POST | /kyc/submit | auth+verified | Submit for review |
| DELETE | /kyc/{id} | auth+verified | Delete document |
| GET | /kyc/{id}/download | auth+verified | Download document |

### Financial
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /financial/transactions | auth+verified | Transaction history |
| GET | /financial/withdrawals | auth+verified | My withdrawal requests |
| POST | /financial/withdrawals | auth+verified | Request withdrawal |
| GET | /financial/payout-methods | auth+verified | Payout methods list |
| POST | /financial/payout-methods | auth+verified | Add payout method |
| PATCH | /financial/payout-methods/{id} | auth+verified | Update payout method |
| DELETE | /financial/payout-methods/{id} | auth+verified | Remove payout method |
| GET | /financial/add-balance | auth+verified | Top-up wallet page |
| POST | /financial/add-balance/kashier | auth+verified | Initiate Kashier deposit |
| GET | /financial/add-balance/success | auth+verified | Deposit success redirect |
| GET | /financial/add-balance/failure | auth+verified | Deposit failure redirect |
| POST | /financial/add-balance/webhook | none | Kashier deposit webhook |
| GET | /financial/transfer | auth+verified | P2P transfer form |
| POST | /financial/transfer | auth+verified | Execute transfer |
| GET | /financial/transfer/history | auth+verified | Transfer history |
| GET | /financial/transfer/{id} | auth+verified | Single transfer |
| GET | /financial/transfer-api/calculate-fee | auth+verified | Fee calculation |
| GET | /financial/transfer-api/search-users | auth+verified | User search |

### Subscriptions
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /subscriptions/plans | auth+verified | Plans page (module=erp\|freelance\|...) |
| POST | /subscriptions/subscribe | auth+verified | Subscribe via wallet |
| GET | /subscriptions/manage | auth+verified | Manage subscriptions |
| POST | /subscriptions/cancel | auth+verified | Cancel subscription |
| POST | /subscriptions/renew | auth+verified | Renew via wallet |
| POST | /subscriptions/kashier/checkout | auth+verified | Initiate Kashier checkout |
| GET | /subscriptions/kashier/success | auth+verified | Payment success redirect |
| GET | /subscriptions/kashier/failure | auth+verified | Payment failure redirect |
| POST | /subscriptions/kashier/webhook | none | Kashier subscription webhook |

### Support Tickets
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /tickets | auth+verified | My tickets |
| POST | /tickets | auth+verified | Create ticket |
| POST | /tickets/{id}/resolve | auth+verified | Mark resolved |

---

## 2. ERP Module Routes (`/erp/*`)

**Middleware**: auth + verified + onboarding + subscription:erp

| Method | Path | Description |
|--------|------|-------------|
| GET | /erp/dashboard | ERP workspace dashboard |
| GET | /erp/onboarding | Tenant setup wizard |
| POST | /erp/onboarding | Complete tenant setup |

### Clients
| Method | Path | Description |
|--------|------|-------------|
| POST | /erp/clients | Create client |
| PUT | /erp/clients/{client} | Update client |
| DELETE | /erp/clients/{client} | Delete client |
| GET | /erp/clients/{client} | Client profile |
| PUT | /erp/clients/{client}/status | Update status |

### Invoices
| Method | Path | Description |
|--------|------|-------------|
| GET | /erp/invoices | Invoice list |
| GET | /erp/invoices/create | Create form |
| POST | /erp/invoices | Store invoice |
| GET | /erp/invoices/{invoice} | View invoice |
| GET | /erp/invoices/{invoice}/edit | Edit form |
| PUT | /erp/invoices/{invoice} | Update invoice |
| DELETE | /erp/invoices/{invoice} | Delete invoice |
| POST | /erp/invoices/{invoice}/send | Send to client |
| POST | /erp/invoices/{invoice}/mark-paid | Mark fully paid |
| POST | /erp/invoices/{invoice}/partial-payment | Record partial |
| POST | /erp/invoices/{invoice}/cancel | Cancel + refund |
| POST | /erp/invoices/{invoice}/duplicate | Clone invoice |
| GET | /erp/invoices/{invoice}/pdf | Download PDF |

### Client-Facing Invoice Routes (`/my/*`)
| Method | Path | Description |
|--------|------|-------------|
| GET | /my/invoices | My invoices list |
| GET | /my/invoices/{uuid}/pay | Payment page |
| POST | /my/invoices/{uuid}/pay/wallet | Pay via wallet |

### Client Wallet
| Method | Path | Description |
|--------|------|-------------|
| GET | /erp/clients/{client}/wallet | Wallet overview |
| GET | /erp/clients/{client}/wallet/transactions | Transactions |
| POST | /erp/clients/{client}/wallet/credit | Manual credit |
| POST | /erp/clients/{client}/wallet/debit | Manual debit |
| POST | /erp/clients/{client}/wallet/lock | Lock funds |
| POST | /erp/clients/{client}/wallet/unlock | Unlock funds |

### ERP Tasks & Todos
| Method | Path | Description |
|--------|------|-------------|
| GET | /erp/tasks | Tasks list |
| POST | /erp/tasks | Create task |
| GET | /erp/tasks/{task} | Task detail |
| PUT | /erp/tasks/{task} | Update task |
| DELETE | /erp/tasks/{task} | Delete task |
| POST | /erp/tasks/{task}/archive | Archive |
| POST | /erp/tasks/{task}/unarchive | Unarchive |
| POST | /erp/tasks/{task}/items | Add todo item |
| PUT | /erp/tasks/{task}/items/{item} | Update item |
| POST | /erp/tasks/{task}/items/{item}/complete | Complete item |
| POST | /erp/tasks/{task}/items/sort | Reorder items |
| POST | /erp/tasks/{task}/items/{item}/pause | Pause item |
| POST | /erp/tasks/{task}/items/{item}/resume | Resume item |
| DELETE | /erp/tasks/{task}/items/{item} | Delete item |

### Other ERP
| Method | Path | Description |
|--------|------|-------------|
| GET/POST | /erp/recurring* | Recurring entries (resource) |
| POST | /erp/recurring/{id}/pause | Pause entry |
| POST | /erp/recurring/{id}/resume | Resume entry |
| GET | /erp/recurring/{id}/logs | Execution logs |
| GET | /erp/withdrawals | Withdrawal list |
| POST | /erp/withdrawals | Request withdrawal |
| POST | /erp/withdrawals/{id}/approve | Approve |
| POST | /erp/withdrawals/{id}/mark-paid | Mark paid |
| POST | /erp/withdrawals/{id}/reject | Reject |
| POST | /erp/withdrawals/{id}/cancel | Cancel |
| GET/POST/PUT/DELETE | /erp/payment-methods* | Payment methods |
| POST | /erp/payment-methods/{id}/approve | Approve |
| POST | /erp/payment-methods/{id}/reject | Reject |
| GET | /erp/referrals | Referral list |
| GET | /erp/referrals/tree/{client} | Referral tree |
| GET | /erp/referrals/earnings | Earnings |
| POST | /erp/clients/{client}/notes | Add client note |
| DELETE | /erp/clients/{client}/notes/{note} | Delete note |
| POST | /erp/clients/{client}/notes/{note}/archive | Archive |
| POST | /erp/tickets | Create support ticket |
| POST | /erp/tickets/{ticket}/resolve | Resolve |
| POST | /erp/tickets/{ticket}/close | Close |
| DELETE | /erp/tickets/{ticket} | Delete |
| POST | /erp/notes | Create workspace note |
| PUT | /erp/notes/{note} | Update note |
| POST | /erp/notes/{note}/toggle-pin | Pin/unpin |
| DELETE | /erp/notes/{note} | Delete note |
| GET | /erp/files* | File management |
| GET | /erp/referrals/* | Referral management |

---

## 3. Marketplace Routes (`/marketplace/*`)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /marketplace/services | none | Browse services |
| GET | /marketplace/services/{id} | none | Service detail |
| GET | /marketplace/services/create | auth | Create listing |
| POST | /marketplace/services | auth | Submit listing |
| POST | /marketplace/services/{id}/packages | auth | Add package |
| PUT | /marketplace/services/{id}/packages/{pkg} | auth | Update package |
| DELETE | /marketplace/services/{id}/packages/{pkg} | auth | Delete package |
| GET | /marketplace/dashboard | auth | Seller dashboard |
| GET | /marketplace/orders | auth | My orders |
| GET | /marketplace/orders/{order} | auth | Order detail |
| POST | /marketplace/orders | auth | Place order |
| POST | /marketplace/orders/{order}/deliver | auth | Deliver order |
| POST | /marketplace/orders/{order}/complete | auth | Complete order |
| POST | /marketplace/orders/{order}/dispute | auth | Dispute order |
| POST | /marketplace/orders/{order}/messages | auth | Send message |
| POST | /marketplace/orders/{order}/review | auth | Leave review |
| DELETE | /marketplace/reviews/{review} | auth | Remove review |

---

## 4. Admin API Routes (`/admin/*`)

All require: auth + verified + onboarding

| Method | Path | Role | Description |
|--------|------|------|-------------|
| GET | /admin/dashboard | any | Admin dashboard |
| GET | /admin/reports/pnl | any | P&L report |
| GET | /admin/clients | any | User list (ERP view) |
| GET | /admin/clients/{id} | any | Client detail |
| GET | /admin/kyc | any | KYC review queue |
| POST | /admin/kyc/{id}/approve | any | Approve KYC |
| POST | /admin/kyc/{id}/reject | any | Reject KYC |
| GET | /admin/users | any | All platform users |
| GET | /admin/users/create | any | Create user form |
| POST | /admin/users | any | Create user |
| GET | /admin/users/problematic | any | Flagged users |
| GET | /admin/users/{id} | any | User profile |
| GET | /admin/users/{id}/edit | any | Edit user |
| PUT | /admin/users/{id} | any | Update user |
| DELETE | /admin/users/{id} | any | Delete user |
| POST | /admin/users/{id}/toggle-block | any | Block/unblock |
| GET | /admin/users/{id}/login-as | any | Impersonate user |
| GET | /admin/users/{userId}/notes | any | User notes |
| POST | /admin/users/{userId}/notes | any | Add note |
| DELETE | /admin/users/{userId}/notes/{id} | any | Delete note |
| POST | /admin/users/{userId}/notes/{id}/archive | any | Archive note |
| GET | /admin/users/{userId}/files | any | User files |
| POST | /admin/users/{userId}/files/upload | any | Upload file |
| POST | /admin/users/{userId}/files/folder | any | Create folder |
| GET | /admin/users/{userId}/files/download | any | Download file |
| POST | /admin/users/{userId}/files/rename | any | Rename file |
| POST | /admin/users/{userId}/files/move | any | Move file |
| DELETE | /admin/users/{userId}/files | any | Delete file |
| GET/POST/PATCH/DELETE | /admin/serial-softwares | any | Serial software mgmt |
| GET/PATCH/DELETE | /admin/serial-devices | any | Device registry |
| GET/POST/PATCH/DELETE | /admin/serial-user-devices | any | User-device assignment |
| GET/POST/PUT/DELETE | /admin/tools | any | Tool catalog |
| POST | /admin/tools/{tool}/upload-version | any | Upload tool version |

---

## 5. Stateless API Routes (`/api/*`)

### External API (routes/api.php — no session auth)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | /api/serial/device | none (throttle 60/min) | Serial license check-in |
| GET | /api/runtime/version | none | Runtime update manifest |
| GET | /api/runtime/plugins | none | Public plugin list |

### Internal API (routes/web.php — session auth via /api prefix)
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /api/conversations | auth+verified | List conversations |
| GET | /api/conversations/{id} | auth+verified | Conversation detail |
| GET | /api/conversations/{id}/messages | auth+verified | Message history |
| POST | /api/conversations/{id}/read | auth+verified | Mark messages read |
| POST | /api/conversations/{id}/messages | auth+verified | Send message |
| GET | /api/admin-notes | auth+verified | List admin notes |
| POST | /api/admin-notes | auth+verified | Create admin note |
| PATCH | /api/admin-notes/{note}/pin | auth+verified | Pin/unpin note |
| DELETE | /api/admin-notes/{note} | auth+verified | Delete note |
| GET | /api/timer/{id} | auth+verified | Timer state |
| GET | /api/activity | auth+verified | Activity feed (JSON) |

---

## 6. Serialized License Check-In API

```
POST /api/serial/device

Request:
{
  "device_id": "HARDWARE-FINGERPRINT-STRING",
  "software_slug": "my-software",
  "hostname": "DESKTOP-XYZ",
  "version": "2.1.0"
}

Response:
{
  "status": "active"  // or "inactive" | "suspended" | "not_found"
}

Rate limit: 60 requests/minute per IP
```

---

## 7. Runtime Local API (http://127.0.0.1:18400)

See `docs/runtime/RUNTIME_ARCHITECTURE.md` for full reference.

### Key Endpoints Summary
| Method | Path | Description |
|--------|------|-------------|
| GET | /status | Runtime status + loaded plugins |
| POST | /plugins/{slug}/run | Execute plugin task |
| GET | /tasks/{taskId} | Task status + logs |
| POST | /auth/start | Begin device login |
| POST | /auth/callback | Token handshake from platform |
| GET | /health | Health check |
| GET | /whatsapp/inbox | WhatsApp conversation inbox |

---

## 8. WebSocket Channels (Laravel Echo)

### Platform WebSocket Events

```javascript
// Private channel: per-user notifications
Echo.private(`notifications.${userId}`)
  .listen('.notification.created', callback)

// Private channel: per-conversation messaging
Echo.private(`conversation.${conversationId}`)
  .listen('.message.sent', callback)
```

### Runtime WebSocket Events

```javascript
// Runtime WS at ws://127.0.0.1:18401/ws
// Events received from runtime:
'runtime.ready'      → initial state
'plugins.reloaded'   → after reload
'plugin.updated'     → plugin update
'plugin.installed'   → new install
'plugin.installing'  → install in progress
'auth.connected'     → device linked to account
'auth.disconnected'  → device unlinked
'auth.required'      → no token, login needed
'wa.event.*'         → WhatsApp event passthrough
'task.started'       → task began
'task.completed'     → task done
'task.failed'        → task errored
'plugin.quarantined' → crash limit reached
```

---

## 9. API Security Analysis

### ✅ Secure Patterns
- CSRF token on all web forms (Inertia handles automatically)
- Session authentication via Sanctum (no JWT leakage)
- Kashier webhook signature validation
- Runtime CORS restricted to musoftware.com + localhost
- Device auth uses one-time codes (10 minute TTL)
- Rate limiting on serial device API (60/min)

### ⚠️ Security Gaps

1. **Admin routes not role-restricted**: Many `/admin/*` routes only require `auth + verified + onboarding` but NOT `role:admin`. The comment says ERP IS the admin's tool, but `/admin/users` and related endpoints should explicitly enforce `role:admin`.

2. **KashierHelper::validatePayload()** — No request object injection visible. If this reads from globals, it's potentially bypassable.

3. **Runtime admin auth** — `SecurityManager.adminAuth()` middleware protects admin endpoints, but the implementation isn't visible. May be IP-based only (localhost trust).

4. **P2P transfer recipient lookup** — `searchUsers` endpoint returns user search results. Ensure it doesn't expose sensitive user data (email, balance).

5. **Impersonation audit** — ImpersonationLog records exist but need to verify all impersonation actions are audited.

6. **No rate limiting on subscription webhook** — Kashier webhooks should be rate-limited or IP-whitelisted to Kashier's IP ranges.
