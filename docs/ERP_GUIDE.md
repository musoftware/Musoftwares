# ERP Invoicing & Accounting Guide

## 1. Creating Multi-Currency Invoices

The ERP module allows you to bill clients anywhere in the world using their local currency while automatically converting and tracking your revenue in your base business currency (USD).

```text
┌────────────────────────────────────────────────────────┐
│ New Invoice                  Currency: [EGP - Egyptian]│
├────────────────────────────────────────────────────────┤
│ Client: [Ahmed Mohamed ▾]    Due Date: [2024-01-31 ▾]  │
├────────────────────────────────────────────────────────┤
│ Line Items                                             │
│ 1. [Simple ▾] [Consultation]        $500.00 x 1 = $500 │
│ 2. [Timer  ▾] [Architecture Dev]    $50/hr (Running)   │
└────────────────────────────────────────────────────────┘
```

### Step-by-Step Invoice Creation
1. Navigate to **ERP Dashboard** > **Invoices** and click **[+ New Invoice]**.
2. Select your client from the dropdown menu. If billing a new client, click **[+ Add Client]** to register their profile.
3. Select the invoice billing currency (e.g., `EUR`, `EGP`, `GBP`). The platform automatically locks in the live daily exchange rate against USD.
4. Add line items:
   - **Simple Item:** A flat fee service (e.g., "Server Setup").
   - **Quantity Item:** Hourly or unit-based billing (e.g., "5 API Endpoints @ $200 each").
   - **Timer Item:** Live tracked billing. You can start and stop an interactive timer directly from your dashboard to record exact billable seconds.
5. Click **[Save Draft]** to inspect the invoice or **[Send to Client]** to dispatch a formatted PDF billing notification via email.

---

## 2. Managing Tenant Clients

Each ERP tenant maintains a private roster of clients isolated from the rest of the platform.

### Client Management Capabilities
- **Default Currency Assignment:** Assign default billing currencies and country codes to individual clients.
- **Client Wallets:** Every client profile contains a dedicated stored value wallet. Clients can prepay funds into their wallet or maintain a positive balance from overpayments.
- **Affiliate Referrals:** You can assign referral codes to clients. When a referred client pays an invoice, automated commission splits are calculated and awarded across up to 2 affiliate levels.

---

## 3. Wallet Ledgers & Payment Processing

When a client pays an invoice or adds funds to their account, the system writes an immutable audit record to `client_wallet_transactions`.

```text
┌──────────────────────────────────────────────────────────┐
│ Client Wallet Standing: Ahmed Mohamed                    │
│ Available Balance: $125.50 USD  (Locked Escrow: $100.00) │
├──────────────────────────────────────────────────────────┤
│ Recent Ledger History                                    │
│ [2024-01-15] Invoice #INV-001 Paid       +$1,749.90 EGP  │
│ [2024-01-10] Marketplace Escrow Hold     -$100.00 USD    │
└──────────────────────────────────────────────────────────┘
```

### Recording Invoice Payments
When you receive an offline wire transfer or check:
1. Open the invoice details screen and click **[Mark as Paid]**.
2. Record the payment date and reference number.
3. If the client has sufficient funds in their client wallet, you can toggle **[Deduct from Wallet Balance]** to settle the invoice instantly.

---

## 4. Setting Up Automated Recurring Invoices

For retainer agreements or software maintenance contracts, use **Recurring Entries** to eliminate manual monthly billing.

### Creating a Recurring Rule
1. Navigate to **ERP Dashboard** > **Recurring Invoices** and click **[+ New Rule]**.
2. Select the client, billing currency, and monthly amount.
3. Choose the frequency (`Weekly`, `Monthly`, `Annually`) and the exact billing day (e.g., 1st of each month).
4. Our automated daily system scheduler will generate a fresh draft invoice on the designated date and log the execution outcome.

---

## 5. Requesting Payout Withdrawals

If your client wallet or affiliate earnings balance accumulates positive funds, you can request a cash payout directly to your bank account.

### Withdrawal Request Steps
1. Navigate to **Wallet** > **Withdrawals** and verify your registered **Payment Method** (Bank IBAN, Swift code, Account Name).
2. Click **[Request Payout]** and enter the desired withdrawal amount.
3. Your request enters `Pending Review` status. Once verified and processed by platform accounting staff, you will receive an email confirmation along with a bank wire reference receipt.
