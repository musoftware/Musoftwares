# Reusable Components Index

## React UI (Shadcn & Custom)
Located in `resources/js/Components/`:
- `ui/*`: Shadcn UI primitives (Dialog, DropdownMenu, Avatar, Tooltip, ScrollArea, Tabs, Toast).
- `FlashHandler.tsx`: Global toast notifications capturing Laravel session flashes.
- `GlobalErrorHandler.tsx`: Catches and renders Inertia errors.
- `ClientAutocomplete.tsx`: Async searchable combobox for large datasets (Clients).
- `ContextualPanels.tsx`: Slide-over side panels for details without leaving the page.
- `ProductTourModal.tsx`: User onboarding modals.

## Backend Traits & Services
- `SubscriptionService`: Validates addon/module access (e.g., `erp-backup`).
- `CurrenciesExchange`: Calculates daily exchange rates.
- `AdminSettings`: Fetches base business currency and global config.
