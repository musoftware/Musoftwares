# Frequently Asked Questions & Troubleshooting

## 1. General & Account Security Questions

### How is my financial data protected?
All financial ledgers across our ERP and Marketplace modules utilize an **Immutable Append-Only Transaction Ledger** architecture. Records cannot be altered, edited, or deleted once committed to the database. All sensitive data transmission is encrypted via SSL/TLS protocols.

### What should I do if I forget my password?
Click the **[Forgot Password]** link on the login page. Enter your registered email address to receive a secure password reset link. Ensure you check your spam or promotional folders if the email does not appear within 2 minutes.

### Can I change my account role from Client to Freelancer?
Yes. You can toggle your active workspace view or register verified skills inside your profile settings to unlock freelancer bidding and selling capabilities without creating a separate user account.

---

## 2. ERP & Accounting Questions

### How do multi-currency exchange rates work when billing overseas clients?
When you generate an invoice in a foreign currency (e.g., `EUR` or `GBP`), our platform queries live interbank exchange rates and locks in the conversion rate against your business base currency (`USD`) at that exact timestamp. This guarantees that historical accounting reports remain perfectly accurate even if exchange rates fluctuate drastically months later.

### Why can't I edit an invoice after sending it to a client?
To maintain strict accounting compliance and prevent fraudulent billing disputes, invoices transition to read-only status once marked as `sent` or `paid`. If you made an error on an issued invoice, you must mark it as `cancelled` and issue a new corrected invoice.

### How do affiliate referral commission splits work?
If you assign referral codes to your clients, our platform automatically tracks when they pay invoices. Commission awards are calculated based on your configured percentage rates and credited to affiliate balances across up to 2 tier levels upon invoice payment.

---

## 3. Marketplace & Freelance Bidding Questions

### Why do I need points to submit freelance proposals?
Our point-based bidding system ensures that job postings are not spammed with low-quality or automated AI proposals. Requiring bidding points encourages freelancers to bid selectively on projects where their skills perfectly match client requirements.

### How does Escrow protection guarantee my payment?
When a client accepts a freelance proposal or purchases a marketplace service package, our system automatically verifies their wallet balance and locks the total project funds inside a secure vault. Freelancers work with complete peace of mind knowing funds are secured, and clients know their money will not be released until successful delivery is verified.

### What happens if a client or freelancer stops responding during an active order?
If an order exceeds its delivery deadline or communication breaks down, you can open an official dispute from your order dashboard. Our platform administrative team will step in, review chat transcripts and milestone records, and issue an equitable refund or payout resolution.
