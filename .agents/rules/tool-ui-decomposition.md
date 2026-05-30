# Rule: Tool UI Component Decomposition

## Problem Statement
Building complex tool UIs (such as data extractors, automation runners, or campaign managers) as a single massive file or a poorly structured component tree leads to unmaintainable code, difficult debugging, and poor reusability. A monolithic approach violates the principles of React/Inertia component design and makes extending the tool's capabilities highly error-prone.

## Rules & Guidelines

### 1. Mandatory Component Decomposition Plan
- Before implementing the UI for any major tool, runner, or dashboard, you **MUST** create a full component decomposition tree.
- Do not build monolithic page components. Break down the UI into logical, focused sub-components.

### 2. Standardized Directory Structure
- The decomposition tree must follow a standardized directory structure within the tool's folder, typically organizing files into logical domains:
  - `pages/`: Top-level Inertia pages or main tabs (e.g., `ExtractPage.tsx`, `CampaignsPage.tsx`).
  - `components/`: Granular UI components grouped by feature domain (e.g., `leads/`, `extraction/`, `campaigns/`, `shared/`).
  - `hooks/`: Custom React hooks for separating business logic, state, and side-effects from UI rendering (e.g., `useCampaigns.ts`, `useRealtimeLeads.ts`, `useRpcClient.ts`).
  - `services/`: API calls, WebSocket handling, export logic, or RPC client wrappers (e.g., `rpc.service.ts`, `export.service.ts`).
  - `stores/`: State management definitions if required (e.g., `extraction.store.ts`).
  - `types/`: TypeScript interfaces and type definitions (e.g., `lead.types.ts`).
  - `constants/` & `utils/`: Reusable helper functions and static data.

### 3. Example Decomposition Tree
When planning or generating a tool UI, output a tree resembling the following structure (adapted for the specific tool):

```text
PropertyFinderRunner/
├── pages/
│   ├── ExtractPage.tsx
│   └── CampaignsPage.tsx
│
├── components/
│   ├── leads/
│   │   ├── LeadCard.tsx
│   │   ├── LeadsTable.tsx
│   │   ├── LeadStats.tsx
│   │   └── LeadFilters.tsx
│   │
│   ├── extraction/
│   │   ├── ExtractionForm.tsx
│   │   ├── ExtractionProgress.tsx
│   │   ├── RuntimeStatus.tsx
│   │   └── ExtractionToolbar.tsx
│   │
│   ├── campaigns/
│   │   ├── CampaignList.tsx
│   │   ├── CampaignCard.tsx
│   │   ├── CampaignDetail.tsx
│   │   └── CampaignStats.tsx
│   │
│   └── shared/
│       ├── StatCard.tsx
│       ├── EmptyState.tsx
│       └── LoadingState.tsx
│
├── hooks/
│   ├── usePropertyFinderSocket.ts
│   ├── useCampaigns.ts
│   ├── useLeadExtraction.ts
│   ├── useRealtimeLeads.ts
│   └── useRpcClient.ts
│
├── services/
│   ├── rpc.service.ts
│   ├── websocket.service.ts
│   ├── campaign.service.ts
│   └── export.service.ts
│
├── stores/
│   ├── extraction.store.ts
│   └── campaign.store.ts
│
├── types/
│   ├── lead.types.ts
│   ├── campaign.types.ts
│   └── websocket.types.ts
│
├── constants/
│   └── countries.ts
│
└── utils/
    ├── csv.ts
    ├── stats.ts
    └── formatters.ts
```

### 4. Implementation Requirements
- **Single Responsibility**: Each component should do one thing well. For instance, a `LeadCard.tsx` should only display lead data, while `LeadsTable.tsx` manages the list layout.
- **Hook Extraction**: Complex state or side-effects (like WebSockets or RPC clients) must be extracted into custom hooks inside the `hooks/` directory to keep components clean.
- **Type Safety**: All props, network payloads, and data models must be strongly typed using interfaces defined in the `types/` directory.

### 5. Summary Checklist
- [ ] Has a FULL decomposition tree been outlined before starting UI development?
- [ ] Are monolithic pages broken down into smaller, focused domain components?
- [ ] Is complex business logic or state management extracted into custom hooks?
- [ ] Are shared UI elements (Empty States, Loaders, Stat Cards) moved to a `shared/` components directory?
- [ ] Are TypeScript types strictly defined and separated into a `types/` folder?
