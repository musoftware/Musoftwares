---
name: financial_and_currency_handling
description: Enforces dynamic formatting of monetary values and prohibits hardcoded currencies. Also enforces strict git workflow rules for feature development.
---

# Financial and Currency Handling

This skill governs the display and handling of monetary values and currencies within the Musoftwares ecosystem.

## 1. Strict Multi-Currency System
- **No Hardcoded Currencies**: Never use hardcoded currency symbols (e.g., `$`, `€`, `£`) anywhere in the application, including UI components, invoices, expenses, or transactions.
- **Dynamic Formatting**: The UI must dynamically format and respect the business's or client's active currency.
- **Inertia Shared Props**: The system must pass the user's/tenant's active currency ISO or symbol via Inertia's shared props.
- **Global Formatter Utility**: Every monetary value (e.g., in components like `Admin/Transactions/TransactionUserCard.tsx` or `Client/Financial/Transactions.jsx`) MUST pipe through a global currency formatter utility.

## 2. Git Workflow Rules (CRITICAL FOR FUTURE AGENTS)
Whenever you are writing code or implementing features under this skill, you must adhere to the following strict git workflow:
- **Dedicated Branches**: Every new feature or bugfix MUST be placed in its own dedicated branch (e.g., `feature/new-currency-formatter`). Do not commit directly to the main branch.
- **Frequent Commits**: Commit your changes after every logical modification. Do not wait until the entire feature is done to make a single massive commit.
- **Descriptive Commits**: Use clear and descriptive commit messages explaining what was changed and why.

*(Note: If your current immediate task specifically instructs you NOT to create branches, you must follow that instruction for the current invocation only. Otherwise, the above branching rule is absolute.)*
