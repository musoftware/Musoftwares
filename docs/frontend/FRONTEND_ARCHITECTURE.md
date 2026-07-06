# Musoftware Platform — Frontend Architecture

> **Stack**: React 18 + TypeScript + Inertia.js v2 + TailwindCSS v4 + Framer Motion  
> **Build**: Vite 7 + laravel-vite-plugin  
> **State**: Inertia page props + React local state (no Redux/Zustand)

---

## 1. Frontend Architecture Overview

```
resources/js/
  ├── app.tsx              Entry point (InertiaApp mount)
  ├── bootstrap.ts         Axios defaults, CSRF, Laravel Echo
  ├── echo.ts              WebSocket connection (Laravel Echo + Pusher)
  ├── global.d.ts          Global TypeScript declarations
  │
  ├── Pages/               All Inertia page components (route → component mapping)
  │   ├── Auth/            Login, Register, ForgotPassword, ResetPassword, Verify
  │   ├── Dashboard.tsx    Main iSAAS dashboard (complex - 22KB)
  │   ├── ERP/             ERP module pages
  │   ├── Financial/       Wallet, transfers, withdrawals
  │   ├── Freelance/       Freelance module pages
  │   ├── Marketplace/     Marketplace pages
  │   ├── Booking/         Booking management
  │   ├── Intelligence/    Ad intel, competitors, swipe vault
  │   ├── Tools/           Tool marketplace, runtime
  │   ├── Admin/           All admin panel pages
  │   ├── Client/          Client-facing pages (invoices, tasks)
  │   ├── Chat/            Messaging interface
  │   ├── Messages/        Message center
  │   ├── Notifications/   Notification center
  │   ├── Profile/         User profile, KYC
  │   ├── Subscriptions/   Plans, manage subscriptions
  │   ├── Support/         Support tickets
  │   ├── Activity/        Activity feed
  │   ├── Public/          Public pages (landing)
  │   └── Welcome.tsx      Public landing page (32KB - marketing page)
  │
  ├── Layouts/             Layout wrappers (AppLayout, AuthLayout, etc.)
  ├── Components/          Reusable shared components
  │   ├── ui/              Base UI primitives (shadcn-inspired)
  │   ├── Chat/            Chat UI components
  │   ├── Freelance/       Freelance-specific components
  │   ├── Tools/           Runtime-connected tool components
  │   ├── ContextualPanels.tsx (32KB - major side panel system)
  │   ├── ProductTourModal.tsx
  │   ├── CommandPalette.tsx
  │   └── [shared primitives: Button, Input, Modal, etc.]
  │
  ├── hooks/               Custom React hooks
  ├── lib/                 Utility functions (cn, date formatting, etc.)
  └── types/               TypeScript type definitions
```

---

## 2. Inertia.js Integration

### How Pages Work
- No traditional API endpoints for page data — **Inertia renders PHP data directly as React props**
- Controller returns `Inertia::render('PageName', $props)` → React receives as `props`
- Navigation uses `router.visit()`, `Link` component (no full page reload)
- Form submissions use `useForm()` hook from `@inertiajs/react`

### Page Structure Pattern
```tsx
// Standard page component structure
import { PageProps } from '@/types';
import AppLayout from '@/Layouts/AppLayout';

interface Props extends PageProps {
  clients: PaginatedResponse<Client>;
  // ...
}

export default function ClientsIndex({ clients, auth }: Props) {
  return (
    <AppLayout title="Clients">
      {/* page content */}
    </AppLayout>
  );
}
```

### Form Handling Pattern
```tsx
const { data, setData, post, processing, errors } = useForm({
  name: '',
  email: '',
});

const handleSubmit = (e: FormEvent) => {
  e.preventDefault();
  post(route('erp.clients.store'));
};
```

---

## 3. Component System

### UI Primitives (`Components/ui/`)
Based on shadcn/ui pattern with Radix UI primitives:
- Built with: `@radix-ui/react-*` + `class-variance-authority` + `clsx` + `tailwind-merge`
- Pattern: `cn()` utility for conditional class merging

### Key Shared Components

| Component | Purpose | Size |
|-----------|---------|------|
| `ContextualPanels.tsx` | Major side panel system (client details, notes, tasks) | 32KB |
| `Admin/Users/Notes.jsx` | Full-page admin secure notes UI (E2EE + audit + bulk) | 26KB |
| `ProductTourModal.tsx` | Onboarding tour guide | 9.7KB |
| `CommandPalette.tsx` | Global search/command palette (cmdk) | 4.7KB |
| `ApplicationLogo.tsx` | Brand logo component | 5KB |
| `AuthIllustration.tsx` | Auth page illustrations (undraw) | 3.7KB |
| `GlobalErrorHandler.tsx` | Error boundary + 500 page handling | 3.1KB |
| `StatCard.tsx` | KPI/metric display card | 1.6KB |
| `Pagination.tsx` | Data table pagination | 1KB |

### Design System
- **Color**: Tailwind CSS v4 (CSS variables based theming)
- **Icons**: Lucide React (`lucide-react` v1.16)
- **Charts**: Recharts v3
- **Graphs**: ReactFlow (for workflow visualization)
- **Animation**: Framer Motion v12
- **Tables**: Custom (no React Table)
- **Date**: date-fns v4
- **Modals**: Radix UI Dialog + custom Modal.tsx
- **Dropdowns**: Radix UI Dropdown Menu + custom Dropdown.tsx
- **Toast**: Radix UI Toast
- **Confetti**: canvas-confetti (celebrations on milestone events)

---

## 4. Routing Architecture

### Inertia + Ziggy Routing
- Backend route names exposed to frontend via `tightenco/ziggy`
- Frontend uses `route('route.name', params)` helper
- All route names defined in PHP routes files

### Page ↔ Route Mapping (Key Routes)

| URL | Page Component |
|-----|---------------|
| `/` | `Pages/Public/Home` |
| `/dashboard` | `Pages/Dashboard.tsx` |
| `/erp/dashboard` | `Pages/ERP/Dashboard` |
| `/erp/invoices` | `Pages/ERP/Invoices/Index` |
| `/erp/clients/{client}` | `Pages/Client/Show` |
| `/erp/tasks` | `Pages/ERP/Tasks/Index` |
| `/marketplace/services` | `Pages/Marketplace/Services/Index` |
| `/marketplace/orders` | `Pages/Marketplace/Orders/Index` |
| `/freelance/jobs` | `Pages/Freelance/Jobs/Index` |
| `/tools` | `Pages/Tools/Index` |
| `/financial/transactions` | `Pages/Financial/Transactions` |
| `/admin/dashboard` | `Pages/Admin/Dashboard` |
| `/admin/users` | `Pages/Admin/Users/Index` |
| `/subscriptions/plans` | `Pages/Subscriptions/Plans` |
| `/intelligence` | `Pages/Intelligence/Dashboard` |
| `/booking` | `Pages/Booking/Index` |

---

## 5. State Management

### No Global State Manager
The platform deliberately avoids Redux/Zustand/Jotai. State is managed via:

1. **Inertia Props** → PHP controller data passed as component props (read-only)
2. **React `useState`** → Local component state
3. **React `useReducer`** → Complex local state (forms with many fields)
4. **Inertia `useForm()`** → Form state with error handling
5. **Custom Hooks** → Shared stateful logic (`hooks/`)

### Real-Time Updates Pattern
```tsx
// echo.ts sets up Laravel Echo globally
// Components listen to channels:
useEffect(() => {
  const channel = Echo.private(`notifications.${auth.user.id}`)
    .listen('.notification.created', (e) => {
      // update local state
    });
  return () => channel.stopListening();
}, []);
```

---

## 6. Dashboard Architecture

### Main Dashboard (`Pages/Dashboard.tsx` — 22KB)
The central hub aggregating all active modules:

**Sections**:
- Header: Stats row (KPIs from all modules)
- Quick Actions: Module launch cards
- Recent Activity: ActivityEvent feed from `/api/activity`
- Pending Items: Invoices due, tasks due, pending tickets
- Module Status: Active subscriptions display
- Runtime Status: Connection to musoftware-runtime

**Data Sources** (all passed as Inertia props):
- `pendingInvoices` — ERP invoices needing attention
- `recentActivity` — Core ActivityEvents
- `subscriptions` — Active module subscriptions
- `walletBalance` — Platform wallet balance
- `pendingTickets` — Support ticket count
- `runtimeStatus` — Runtime agent connection info

---

## 7. Module-Level Frontend Structure

### ERP Module Pages
```
Pages/ERP/
  ├── Dashboard/    Main ERP workspace with client list + stats
  ├── Invoices/     Invoice list, create, edit, show, PDF
  ├── Tasks/        ERP task list + Kanban-style view
  ├── Recurring/    Recurring entry management
  ├── Wallet/       Client wallet management
  └── Onboarding/  ERP workspace setup wizard
```

### Tools Module Pages
```
Pages/Tools/
  ├── Index.tsx     Tool catalog browser
  ├── Show.tsx      Individual tool page + install/run CTA
  └── Runner.tsx    Runtime-connected tool execution UI
       └── Uses: runner_component field from Tool model
           to dynamically render tool-specific UI
```

### Intelligence Module Pages
```
Pages/Intelligence/
  ├── Dashboard.tsx    Overview: tracked assets, recent activity
  ├── Competitors/     Competitor tracking
  ├── AdFeed/          Swipe/ad collection
  ├── SwipeVault/      Organized ad collections
  └── UgcCreators/     UGC creator tracking
```

---

## 8. Runtime Integration (Frontend → Runtime)

The browser communicates with the local Runtime Agent:

```typescript
// Pattern: direct HTTP to localhost runtime
const RUNTIME_URL = 'http://127.0.0.1:18400';

// Check runtime status
const status = await fetch(`${RUNTIME_URL}/status`).then(r => r.json());

// Run a plugin
const { taskId } = await fetch(`${RUNTIME_URL}/plugins/${slug}/run`, {
  method: 'POST',
  body: JSON.stringify({ params }),
}).then(r => r.json());

// Monitor via WebSocket
const ws = new WebSocket('ws://127.0.0.1:18401/ws');
ws.onmessage = ({ data }) => {
  const { event, data: payload } = JSON.parse(data);
  if (event === 'task.completed') { /* handle */ }
};
```

**Components using runtime**:
- `Components/Tools/RuntimeStatus.tsx` — connection indicator
- `Pages/Tools/Runner.tsx` — task execution UI
- `Pages/Intelligence/*.tsx` — extraction task runners

---

## 9. Frontend Tech Debt & Gaps

### Issues Found

1. **ContextualPanels.tsx is 32KB** — Single massive component managing all side panels. Should be split by domain (ClientPanel, TaskPanel, NotePanel, etc.)

2. **Welcome.tsx is 32KB** — The entire landing page in one file. No component extraction.

3. **No dedicated data-fetching layer** — Mix of Inertia props + direct fetch calls. Should standardize.

4. **No global error boundary** — `GlobalErrorHandler.tsx` exists but may not cover all cases.

5. **runner_component pattern is undocumented** — Tools have a `runner_component` field that maps to a React component name, but the dynamic loading mechanism is not obvious.

6. **Dashboard.tsx references 'runtimeStatus'** — Implies there's a runtime status component in the dashboard, but the actual runtime health check polling interval/mechanism is unclear.

7. **Echo channels unclear** — `channels.php` exists but the specific channel names used by frontend components are not centrally documented.

---

## 10. Build System

### Vite Configuration
```javascript
// vite.config.js
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default {
  plugins: [
    laravel({ input: ['resources/css/app.css', 'resources/js/app.tsx'] }),
    react(),
    tailwindcss(),
  ]
};
```

### Commands
```bash
npm run dev      # Vite dev server (HMR)
npm run build    # TypeScript check + Vite prod build
npm run lint     # ESLint with auto-fix
```

### Module Vite Configs
Some modules have their own `vite.config.js`:
- `Modules/Intelligence/` — separate Vite build
- `Modules/Booking/` — separate Vite build
- `Modules/Shared/` — shared bundle

This `vite-module-loader.js` at root handles multi-module Vite integration.

---

## 11. TypeScript Configuration

```json
// tsconfig.json (simplified)
{
  "compilerOptions": {
    "target": "ES2020",
    "jsx": "react-jsx",
    "paths": { "@/*": ["./resources/js/*"] },
    "strict": true
  }
}
```

- Path alias: `@/` → `resources/js/`
- Strict mode enabled
- Types for: React, Radix UI, Lucide, DOMPurify, canvas-confetti, marked
