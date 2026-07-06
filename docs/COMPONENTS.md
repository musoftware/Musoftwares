# Component Library Reference

## Overview

The ERP System UI is constructed using **shadcn/ui** design tokens and headless accessible primitives from **Radix UI**, augmented with bespoke domain-specific components located in `resources/js/Components/`. All components are fully typed in **TypeScript (`.tsx`)**.

---

## 1. Core shadcn/ui Primitives (`resources/js/Components/ui/`)

The `ui/` directory encapsulates core design system elements:
- `Button`: Standard interactive buttons (`PrimaryButton.tsx`, `SecondaryButton.tsx`, `DangerButton.tsx`)
- `TextInput` & `InputLabel`: Form inputs with automatic focus states.
- `Modal` & `Dialog`: Accessible modals supporting backdrop isolation and focus trapping.
- `Dropdown` & `DropdownMenu`: Accessible menus for user navigation and table row actions.
- `Toast` & `Toaster`: Flash notifications via Radix UI Toast.

---

## 2. Advanced Domain Components

### `StatCard` (`resources/js/Components/StatCard.tsx`)
Displays financial aggregates, active client counters, or point balances on dashboard screens.

```tsx
import StatCard from '@/Components/StatCard';
import { Wallet, ArrowUpRight } from 'lucide-react';

<StatCard
  title="Available Escrow Funds"
  value="$12,450.00 USD"
  icon={<Wallet className="w-6 h-6 text-primary" />}
  trend={{ value: "+14.2%", isPositive: true }}
/>
```

### `ContextualPanels` (`resources/js/Components/ContextualPanels.tsx`)
Tabbed contextual drawers and slide-out side panels for inspecting complex database records (e.g., viewing invoice items or escrow milestone history without leaving the page).

```tsx
import { ContextualPanels } from '@/Components/ContextualPanels';

<ContextualPanels
  isOpen={panelOpen}
  onClose={() => setPanelOpen(false)}
  title="Invoice Details #INV-2024-01"
>
  {invoiceContent}
</ContextualPanels>
```

### `CommandPalette` (`resources/js/Components/CommandPalette.tsx`)
Accessible keyboard-driven command center (activated via `Cmd + K` or `Ctrl + K`) allowing power users to instantly jump across modules, search client profiles, or trigger timer sessions. Built on `cmdk`.

```tsx
import CommandPalette from '@/Components/CommandPalette';

<CommandPalette />
```

### `AdminNotesPanel` (`resources/js/Pages/Admin/Users/Notes.jsx`)

The legacy `resources/js/Components/AdminNotesPanel.tsx` (with hardcoded `/pin` route and
unencrypted `marked` rendering) was removed in favour of the full-page route
`/admin/users/{id}/notes` served by `Admin/Users/Notes.jsx`. That page drives the
`UserNoteController` end-to-end (E2EE cipher, audit log, edit/bulk/reveal).

```js
import Notes from '@/Pages/Admin/Users/Notes';

// Served via /admin/users/{id}/notes (no manual import required).
<Notes user={user} notes={notes} stats={stats} />
```

### `GlobalErrorHandler` (`resources/js/Components/GlobalErrorHandler.tsx`)
React Error Boundary catching runtime component crashes and displaying a professional fallback UI to prevent white screens of death.

```tsx
import GlobalErrorHandler from '@/Components/GlobalErrorHandler';

<GlobalErrorHandler>
  <AppContent />
</GlobalErrorHandler>
```

---

## 3. Form Utilities

### `Checkbox` (`resources/js/Components/Checkbox.tsx`)
Standard accessible checkbox input.

```tsx
import Checkbox from '@/Components/Checkbox';

<Checkbox 
  checked={data.is_required}
  onChange={(e) => setData('is_required', e.target.checked)}
/>
```

### `InputError` (`resources/js/Components/InputError.tsx`)
Formats server-side form validation messages in accessible red typography.

```tsx
import InputError from '@/Components/InputError';

<InputError message={errors.budget} className="mt-2" />
```
