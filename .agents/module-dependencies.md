# Module Dependencies

## Core Philosophy
Modules in `Modules/` are meant to be isolated business contexts. 

## Known Modules
- **Core**: Global settings, base tenant configuration.
- **ERP**: Invoicing, ledger, clients, expenses, projects. The financial heart of the system.
- **CRM**: Lead tracking, pipelines.
- **GoldSavers**: Specialized module for gold investments/installments.
- **Marketplace**: Addon/subscription purchasing engine.
- **PaymentGateway / SmsPaymentGateway**: Abstraction layers for third-party billing.

## Dependency Rules
- Modules should not tightly couple their database relationships. Use interfaces or events when cross-module communication is needed.
- `ERP` relies heavily on `Marketplace` for multi-currency addon checks.
