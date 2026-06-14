# Rule: CRM Strict Shadcn & CrmLayout Enforcement

## Problem Statement
The CRM module requires a highly dense, operational, and premium SaaS interface. Reverting to basic `AppLayout` or `CrmLayout` templates with generic HTML tables, unstyled forms, or non-Shadcn components completely breaks the UX. Future agents MUST strictly enforce the new Enterprise Architecture to keep the UI feeling "alive".

## Rules & Guidelines

### 1. Mandatory CrmLayout Wrapper
- **Never** use `AppLayout` or generic `div` wrappers as the root for any page in the CRM (`Modules/CRM` or `resources/js/Pages/CRM`).
- **Always** wrap all CRM pages in the `CrmLayout` component (`import CrmLayout from '@/Layouts/CrmLayout';`).

### 2. Strict Shadcn UI Enforcement
- **Never** write raw Tailwind CSS form inputs, buttons, tables, dropdowns, or modals from scratch.
- **Always** use the standard Shadcn UI components located in `resources/js/Components/ui/`.
- For example, use `<Button>`, `<Input>`, `<Select>`, `<Dialog>`, `<DropdownMenu>`, and `<Card>` directly.

### 3. Operational Density (No Empty White Space)
- The CRM is designed for fast, repetitive operational work (Telesales, Management, Collections).
- Never design a "sparse" page. Utilize screen real estate efficiently.
- Use `KPICard` for metrics and `PipelineBoard` (Zustand + hello-pangea/dnd) for Kanban views.

### 4. Zero Hardcoded Data / Always API Driven
- The frontend Kanban board (`PipelineBoard.tsx`) uses `axios` and a Zustand store (`usePipelineStore.ts`) to fetch real data from `/crm/api/kanban`.
- Never hardcode mock leads or fallback JSON into the components. Ensure the `fetchPipeline` method is invoked inside a `useEffect` to retrieve data live.

### 5. Summary Checklist
- [ ] Is the page wrapped in `<CrmLayout>`?
- [ ] Are all buttons, inputs, and modals using Shadcn components (`@/Components/ui/...`)?
- [ ] Does the UI match the premium SaaS design language (dense, actionable, icon-heavy using `lucide-react`)?



---
