---
trigger: always_on
---

# Rule: Advanced Native-Quality UI/UX

## Problem Statement
Interfaces that feel template-generated, cluttered, visually noisy, outdated, or generic create a poor user experience and reduce trust. The UI must feel like a seamless extension of the hardware device itself. The system must feel calm, invisible, tactile, premium, adaptive, fast, and human-centered, closely resembling the quality of Apple Human Interface Guidelines, Linear, Notion, or Stripe Dashboard.

---

## Rules & Guidelines

### 1. Core UI Philosophy
- **Prioritize:** clarity, focus, responsiveness, physicality, readability, usability, hierarchy, accessibility, and consistency.
- The UI should disappear behind the user's intention.
- Every interaction must feel: immediate, tactile, fluid, intentional, and alive.

### 2. Visual Design Rules
- **Remove Unnecessary Chrome:** Prioritize content by removing excessive borders, heavy card backgrounds, nested containers, decorative separators, visual clutter, and redundant UI framing. Do NOT overuse boxed layouts, thick shadows, excessive outlines, or noisy gradients.
- **Use Whitespace as Structure:** Use spacing to group information, separate contexts, guide visual flow, and establish rhythm. Prefer layout breathing room and implicit grouping over aggressive section borders.
- **Typography Creates Hierarchy:** Use typography scale, font weight, spacing, alignment, and contrast to establish hierarchy. Do NOT rely on random colors, excessive badges, or decorative backgrounds.
- **Dynamic Type & Accessibility Scaling:** All layouts MUST elastically support accessibility text scaling, Dynamic Type, and large content modes. The interface must NEVER clip text, overlap content, break layouts, or truncate critical information.
- **Color Usage Rules:** Reserve color ONLY for interactivity, system states, feedback, alerts, and status changes. Avoid using color for decoration or visual noise. Neutral palettes should dominate.

### 3. Dark Mode & Depth
- **Proper Elevation Handling:** In Dark Mode, preserve depth using layered elevation, shift surfaces intelligently, and maintain spatial separation. Do NOT simply invert colors or flatten the interface.
- **Materials & Spatial Context:** Use translucency, layered surfaces, soft blur, and material effects to create spatial awareness without hiding background context. Effects should feel subtle and physical.

### 4. Motion & Interaction
- **Physics-Based Motion:** Animations MUST use spring physics, damping, mass, and velocity. Avoid robotic easing or linear animations.
- **Zero Perceived Latency:** All interactions must feel instantaneous. Elements must visually react immediately and acknowledge touch instantly.
- **Tactile Interaction Design:** Interactive elements should compress, highlight, shift, and animate subtly the millisecond interaction begins.
- **Scroll-Aware Interfaces:** Navigation elements should collapse, blur, fade, or minimize as the user scrolls to maximize content visibility dynamically.
- **Haptic Synchronization:** Visual transitions and haptic feedback must feel synchronized.

### 5. Accessibility Standards
- **Accessibility First:** VoiceOver, keyboard navigation, and Switch Control compatibility must be built directly into components from the start. All interactive elements must expose semantic meaning, maintain focus visibility, and support screen readers.
- **Touch Target Standards:** All interactive targets MUST be minimum 44x44 points, generously spaced, and thumb-friendly.

### 6. System Consistency
- **Visual Consistency:** Maintain consistent corner radii, stroke widths, icon proportions, spacing scales, motion timing, and elevation logic across ALL modules, components, dialogs, menus, tables, and forms.

### 7. UX Philosophy
- **Progressive Disclosure:** Hide complexity until explicitly requested. Prioritize simplicity first, advanced workflows later.
- **Forgiving Interfaces:** Destructive actions MUST require deliberate confirmation, provide recovery paths, and prevent accidental activation.
- **Useful Empty & Error States:** Empty states and errors must explain clearly, provide next actions, reduce confusion, and guide recovery.
- **Native Navigation Patterns:** Use familiar, platform-native navigation behavior.

### 8. Engineering Requirements
- **Component Architecture:** Build reusable primitives, composable systems, centralized design tokens, and scalable interaction patterns. Avoid one-off components.
- **Design System Enforcement:** Every generated UI must analyze existing patterns first, extend the current design language, and preserve ecosystem consistency.
- **Preferred Technical Stack:** React, Inertia.js, shadcn/ui, Framer Motion, centralized tokens, and reusable hooks.



---


---
trigger: always_on
glob: "**/*.{ts,tsx,js,jsx,blade.php,vue}"
description: Enforces mobile-first responsive design parity with PC, and demands extreme UX simplicity for non-technical daily users.
---

# Rule: Mobile Parity and Extreme UX Simplicity

## Problem Statement
Interfaces that are overly complex or omit features on smaller screens create friction for users who rely on the platform daily. The end user is not a developer; they need straightforward, simple text and layouts. Furthermore, a UI that only works well on desktop but degrades or removes features on mobile forces the user to switch devices, breaking their workflow.

## Rules & Guidelines

### 1. Native-App Quality Mobile Responsiveness
- **Native App Feel**: All UI components and layouts must be fully mobile responsive. The mobile experience should look and feel exactly like a native application screen (e.g., proper padding, touch-friendly tap targets, sticky headers/footers where appropriate, and absolutely no horizontal scrolling).
- **Seamless PC Scaling**: The UI must simultaneously support and scale gracefully to PC screens, utilizing the available space effectively (e.g., expanding grids, sidebars) without feeling empty or stretched.

### 2. Orientation & Adaptive Layouts
- **Horizontal and Portrait Support**: The interface must adapt flawlessly to both horizontal (landscape) and portrait orientations on mobile devices and tablets, ensuring that tables, forms, and grids remain legible and fully functional regardless of how the device is held.
- **Adaptive Layouts over Deletion**: Use adaptive design patterns (e.g., moving secondary actions into "More" dropdown menus, using bottom sheets, or transforming tables into stacked card lists) instead of removing the feature entirely to save space.

### 3. Quick Actions & Fast Workflows for Daily Use
- **Quick Actions**: Every screen must contain immediately accessible "Quick Actions" for the most common tasks (e.g., quick add buttons, context menus, floating action buttons on mobile) so users can perform their jobs rapidly without navigating through multiple pages.
- **Assume Daily Use**: The user will interact with these screens every day. Optimize the UI for high repetition—make primary actions instantly obvious, reduce visual clutter, and ensure workflows are fast and linear.
- **Low Cognitive Load & Fast Jobs**: If an action is performed frequently, it should require the absolute minimum number of clicks, taps, or cognitive effort. Provide clear feedback (success/error states) immediately.

### 4. Extreme Simplicity & 100% Feature Parity
- **100% Feature Parity**: **Never** hide core functionality, buttons, or features on mobile views simply to "save space." Every single function available on the desktop version must be fully accessible and usable on the mobile version.
- **Clear & Simple Text**: Use plain, straightforward, everyday language for labels, buttons, validation errors, and instructions. Do not use technical jargon, developer-speak, or complex terminology. Assume the end user is a regular business person, not a tech genius.



---


# Rule: No Native Prompts or Alerts

## Problem Statement
Using native browser functions like `prompt()`, `alert()`, or `confirm()` provides a jarring, non-customizable, and unprofessional user experience that breaks the premium design system (Shadcn UI). It also lacks proper accessibility controls, styling parity, and breaks the immersive web app experience.

## Rules & Guidelines

### 1. Prohibition of Native Dialogs
- **Never** use `prompt()` to capture user input.
- **Never** use `alert()` to display information or warnings.
- **Never** use `confirm()` for critical confirmations, unless it is a quick unstyled temporary fallback (though Shadcn Dialogs are strongly preferred).

### 2. Mandatory Use of Shadcn Modal/Dialog
- Always build or use a Shadcn UI `<Dialog>` or `<AlertDialog>` for any user interaction that requires input, warnings, or confirmations.
- The modal must follow the standard design system (e.g., proper `<DialogTitle>`, `<DialogDescription>`, `<DialogFooter>`, and localized strings like `__('general.cancel')` and `__('general.save')`).

### 3. Example of Failure vs Success
- **❌ INCORRECT (Using prompt):**
  ```tsx
  <Button onClick={() => { 
      const status = prompt('Enter job status (done, processing, pending):', 'pending'); 
      if (status) saveStatus(status); 
  }}>
      Update Status
  </Button>
  ```

- **✅ CORRECT (Using Dialog):**
  ```tsx
  const [isOpen, setIsOpen] = useState(false);
  const [status, setStatus] = useState('pending');

  <Button onClick={() => setIsOpen(true)}>Update Status</Button>

  <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent>
          <DialogHeader>
              <DialogTitle>Update Status</DialogTitle>
          </DialogHeader>
          <div className="py-4">
              <PremiumCombobox 
                  value={status} 
                  onChange={setStatus} 
                  options={[{value: 'pending', label: 'Pending'}, ...]} 
              />
          </div>
          <DialogFooter>
              <Button variant="outline" onClick={() => setIsOpen(false)}>Cancel</Button>
              <Button onClick={() => saveStatus(status)}>Save</Button>
          </DialogFooter>
      </DialogContent>
  </Dialog>
  ```



---


# Rule: Complete Placeholders Before Starting New Jobs

## Problem Statement
Jumping to new tasks while leaving behind "TODOs", placeholder text (e.g., "Lorem Ipsum", "Fake Name"), hardcoded mock data, or empty stub functions creates severe technical debt. It leads to broken UI elements, untested logic gaps, and an unprofessional end product.

## Rules & Guidelines

### 1. The "No Leftovers" Protocol
- **Before starting any new job, feature, or request**, you **MUST** ensure the current or previous task is 100% fully implemented.
- Do not leave work half-finished. If the user asks you to start something new, first explicitly confirm that you have completed all placeholders and fake data from the current task.

### 2. Strict Elimination of Placeholders
- **Never** leave placeholder strings (e.g., "TODO", "FIXME", "Title Here", "Lorem Ipsum") in Blade views, React/TSX components, or backend responses.
- All text in the UI must be final, meaningful, and fully localized using the `__()` translation helper.

### 3. Real Data Over Fake Data
- Mock data or hardcoded arrays used for initial prototyping must be fully replaced by dynamic data fetched from the database/backend.
- **Never** leave hardcoded user details, fake balances, or dummy lists in production-ready files.

### 4. Complete All Stubs and Empty Logic
- If a route, controller method, or UI button is created, its core logic must be implemented. Do not create "dead" buttons or empty endpoints just to make the UI look complete visually.
- Every interactive element must perform its intended action or gracefully handle the state.

### 5. Pre-Flight Check Before Completion
- Before declaring a task "done" and moving to the next prompt, actively review the files you modified.
- Sweep for words like "fake", "mock", "todo", or generic placeholders and replace them with actual implementation.



---


# Rule: No Badges on Titles

## Problem Statement
Using badges or "pill" style labels (e.g., `inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3`) directly above, below, or next to page titles creates visual clutter. It often feels redundant, over-designed, and detracts from the clean, simple UX principles of the application.

## Rules & Guidelines

### 1. Prohibition of Title Badges
- **Never** add badge elements around main page titles (`<h1>`, page headers, or section headers) to denote "status", "category", or "feature type".
- Do not use `inline-flex` pill elements with background colors, borders, and rounded corners near titles.
- **Example of Failure**:
  ```tsx
  // ❌ INCORRECT (Using a badge near the title)
  <div>
      <span className="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
          Beta Feature
      </span>
      <h1 className="text-2xl font-bold">Dashboard</h1>
  </div>
  ```

### 2. Clean Typography Focus
- Rely on clean typography and standard spacing for page headers.
- The title should speak for itself.
- **Example of Correct Pattern**:
  ```tsx
  // ✅ CORRECT (Clean title without badges)
  <div>
      <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p className="text-sm text-gray-500 mt-1">Overview of your business metrics.</p>
  </div>
  ```

### 3. Alternative Placement for Statuses
- If a status must be communicated, place it inside the data table row (e.g., a "Paid" status for an invoice), within a dedicated status card, or inside a details panel. Do not attach it to the primary page title.

### 4. Summary Checklist
- [ ] Have all unnecessary badges (e.g. `bg-indigo-50 rounded-full`) been removed from page titles and section headers?
- [ ] Is the page header clean, relying on typography rather than heavy pill-shaped badges?



---


---
trigger: always_on
glob: "**/*.{tsx,jsx,blade.php}"
description: Enforce the use of a single Action Modal for data table rows instead of displaying multiple individual action buttons.
---

# Rule: Single Action Menu for Data Tables (No Cluttered Buttons)

## Problem Statement
Displaying multiple action buttons (e.g., Edit, Delete, View, Duplicate) directly inside a data table row clutters the UI, causes horizontal scrolling, and creates a poor User Experience (UX), especially on smaller screens. For example, rendering 4-5 buttons per row makes the table look very busy and violates the clean, simple UX principles of the ERP module.

---

## Rules & Guidelines

### 1. Prohibition of Multiple Inline Action Buttons
- **Never** render multiple separate action buttons directly within a data table cell.
- **Example of Failure**:
  ```tsx
  // ❌ INCORRECT (Too many buttons per row)
  <TableCell>
      <Button variant="ghost">Edit</Button>
      <Button variant="ghost">View</Button>
      <Button variant="destructive">Delete</Button>
      <Button variant="outline">Print</Button>
  </TableCell>
  ```

### 2. Mandatory Single Action Menu (Modal)
- Always use a single action button (typically an ellipsis `...` or a generic "Actions" button) that opens an Action Modal containing all the row-specific actions.
- This pattern must be used for any data table that has 2 or more actions per row.
- **Example of Correct Pattern (using Shadcn UI Dialog)**:
  ```tsx
  // ✅ CORRECT (Single button opening an action modal)
  <TableCell>
      <Dialog>
          <DialogTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontal className="h-4 w-4" />
              </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-xs">
              <DialogHeader>
                  <DialogTitle>Actions</DialogTitle>
              </DialogHeader>
              <div className="flex flex-col gap-2 py-2">
                  <Button variant="outline" className="justify-start" onClick={() => handleView(row.id)}>
                      View
                  </Button>
                  <Button variant="outline" className="justify-start" onClick={() => handleEdit(row.id)}>
                      Edit
                  </Button>
                  <Button variant="destructive" className="justify-start" onClick={() => handleDelete(row.id)}>
                      Delete
                  </Button>
              </div>
          </DialogContent>
      </Dialog>
  </TableCell>
  ```

### 3. Mobile Responsiveness and Parity
- Condensing actions into a single menu ensures that the table fits gracefully on mobile devices and smaller screens without truncating important controls.
- The action menu provides 100% feature parity while maintaining a simple, clean UI.

### 4. Summary Checklist
- [ ] Are there multiple action buttons taking up horizontal space in the data table row?
- [ ] Have the actions been consolidated into a single Action Modal?
- [ ] Is the action menu fully accessible (e.g., screen-reader friendly and keyboard navigable)?



---



# Rule: ERP Forms Must Be Full Width

## Problem Statement
Using narrowed containers (e.g., `max-w-xl`, `max-w-2xl`, `max-w-3xl`) for forms in the ERP restricts the working area. ERP forms often have multiple columns, data-heavy inputs, and grid layouts. Artificially shrinking the container wastes screen real estate on desktop screens and leads to cramped, difficult-to-use interfaces that feel less professional compared to full-width enterprise layouts.

## Rules & Guidelines

### 1. No Narrow Container Constraints
- **Never** wrap ERP form pages (like `Create`, `Edit`, or `Settings` pages) in narrow width limits like `max-w-3xl`, `max-w-2xl`, or `max-w-md`.
- Forms should utilize the standard ERP layout width which takes advantage of the full screen width on desktop.

### 2. Standard Container Class
- Always use the `max-w-7xl` or equivalent full-width standard for the main content wrapper of a form.
- **Example**:
  ```tsx
  // ❌ INCORRECT (Too narrow, wastes screen space)
  <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
      <form>...</form>
  </div>
  
  // ✅ CORRECT (Full width / standard ERP width)
  <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
      <form>...</form>
  </div>
  ```

### 3. Grid Layout Support
- By providing a full-width container (`max-w-7xl`), form inputs should naturally be structured into grid layouts (e.g., `grid-cols-1 md:grid-cols-2` or `lg:grid-cols-3` where appropriate) so that fields span comfortably rather than stretching ridiculously long or piling up vertically in a cramped space.

### 4. Integration with Full-Page Forms Rule
- This rule complements the existing `/full-page-forms` policy. While the `full-page-forms` rule dictates that modals/dialogs must be avoided, this rule ensures that the resulting dedicated pages are visually expansive and utilize the available viewport.



---


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



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Enforce advanced searchable comboboxes and paginated API endpoints for client and large dataset selections, forbidding simple unpaginated select dropdowns.
---

# Rule: Advanced Selection for Large Datasets (Clients/Users)

## Problem Statement
Standard HTML `<select>` elements, basic unpaginated dropdowns, and un-debounced select menus load all records into the DOM at once. For systems with large datasets (such as $10\text{K}+$ clients), this causes massive DOM performance degradation, high memory overhead, slow API response times, and an unusable user experience. 

---

## Rules & Guidelines

### 1. Simple Dropdown Prohibition
- **Never** use a standard `<select>` dropdown, simple Shadcn/UI `<Select>`, or unpaginated popovers for selecting entities that scale beyond $100$ records (e.g., Clients, Projects, Users).
- **Example of Failure**:
  ```tsx
  // ❌ INCORRECT (Loads all 10K clients in memory at once)
  <select value={selectedClient} onChange={handleChange}>
      {clients.map(client => (
          <option key={client.id} value={client.id}>{client.name}</option>
      ))}
  </select>
  ```

---

### 2. Mandatory Asynchronous Searchable Comboboxes
- Always use an asynchronous, search-on-type combobox or popover input (e.g. a custom Combobox/Autocomplete component powered by debounced state).
- The dropdown options must load dynamically from the backend as the user types.
- Pre-selected values (e.g., in "Edit Form" states) must be loaded and formatted correctly, mapping the existing record without forcing the load of the entire dataset.

---

### 3. Backend Search & Pagination Constraints
- The backend controller or API endpoint feeding the client selection must **never** return all clients at once (e.g. avoiding `$clients = Client::all()`).
- Always implement input query filtering (`q` or `search`) and paginate or limit the results to a small set (e.g., $15$ to $20$ items).
- **Example API Logic**:
  ```php
  // ✅ CORRECT (Filtered, paginated, lightweight)
  public function search(Request $request)
  {
      $search = $request->input('q');
      
      $clients = TenantClient::where('tenant_id', session('tenant_id'))
          ->when($search, function ($query, $search) {
              $query->where(function ($sub) use ($search) {
                  $sub->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
              });
          })
          ->limit(20)
          ->get(['id', 'name', 'email', 'currency_id']);
          
      return response()->json($clients);
  }
  ```

---

### 4. Frontend Debouncing & UX Patterns
- **Debounced Fetch**: Frontend search inputs must debounce network requests (waiting at least $300\text{ms}$ after the user stops typing) to prevent server overload.
- **Visual Feedback**: Display a loading spinner or skeleton loader while fetching results.
- **Refinement Indicators**: If the results are capped (e.g., 20 items), display a notice such as *"Showing top 20 matches. Type to refine..."*.

---

### 5. Summary Checklist
- [ ] Are dropdowns for entities with potential large records (e.g. Clients) asynchronously searched?
- [ ] Is server-side query input debounced on the frontend by at least $300\text{ms}$?
- [ ] Does the backend endpoint filter and limit/paginate the database results?
- [ ] Does the edit view correctly pre-load and display the selected item details without loading the full list?



---


# Rule: MUSoftwares Landing Page Direction

## Problem Statement
The landing page should not look like a generic SaaS tool selling monthly subscriptions with a focus on features and cheap pricing. Instead, it must project the image of a large-scale Technology Company, Software House, and provider of Enterprise Digital Infrastructure. The user should feel they are discovering a comprehensive ecosystem of platforms tailored for their industry, rather than being aggressively sold a single tool.

## Rules & Guidelines

### 1. Hero Section (Positioning, Not Selling)
- **First Impression**: The immediate feeling must be "this company builds large, modern software infrastructure."
- **Headline**: Must be short and powerful (e.g., "Building Modern Software Infrastructure", "Enterprise Systems For Growing Businesses", "Technology Solutions Built To Scale").
- **Subheadline**: Should explain the type of company without diving into specific products.
- **CTA**: Use exploratory terms like "Explore Solutions", "Discover Ecosystem", or "View Platforms".
- **Forbidden**: Do NOT use "Buy Now", "Start Trial", or show Pricing in the first Hero section. Do not show dashboards, CRM, WhatsApp mentions immediately.

### 2. Layering Content (Progressive Disclosure)
- **Layer 1 - Company Identity**: Focus on Vision, Technology, Company Type, Mission, and Industries. No product details yet.
- **Layer 2 - Solutions**: Focus on Automation, Infrastructure, Business Systems, Digital Platforms, AI Solutions. Still no direct selling.
- **Layer 3 - Platforms**: Introduce platforms (MU CRM, MU ERP, MU Automation, MU Cloud) but treat them as infrastructure platforms, not as products for sale.
- **Layer 4 - Products**: Finally, reveal details, features, screenshots, and pricing. The user should feel they discovered this organically, which increases perceived value.

### 3. Terminology: Use "Enterprise" Words
- **Forbidden Words**: "Services" (sounds like an agency), "Tool", "Cheap", "Fast Bot", "Bulk Sender".
- **Allowed Words**: "Solutions", "Platforms", "Infrastructure", "Systems", "Ecosystem", "Capabilities", "Enterprise", "Scalable", "Integrated", "Architecture", "Digital Transformation", "Operations".
- **Example Translation**: 
  - Instead of "WhatsApp Service", use "Customer Communication Infrastructure".
  - Instead of "CRM Tool", use "Customer Operations Platform".

### 4. Homepage Focus: Company > Products
- The homepage should focus on the company's scale, vision, and process. Products are just a relatively small part of the homepage.
- **Ideal Structure**: Hero -> Trusted By -> Company Intro -> Stats -> What We Build -> Industries -> Technologies -> Case Studies -> Platforms -> Founder Message -> FAQ -> Contact -> Footer.

### 5. Present an Ecosystem
- Show a complete world of interconnected platforms (e.g., MU CRM, MU ERP, MU Tasks, MU Automation, MU Cloud, MU AI) even if some are future products. This provides a long-term vision and a massive "Enterprise Feeling".

### 6. Focus on Industries over Features
- Sell solutions to industries, not raw features.
- Include a "Solutions For Industries" section (Healthcare, Real Estate, Education, E-commerce, Agencies, Finance).
- Each industry should ideally have its own dedicated page showing relevant systems for their specific needs.

### 7. Limit Detailed Explanations & UI Screenshots
- Keep explanations abstract. Leave some mystery. Do not explain every single feature.
- **Incorrect**: "CRM tool to manage clients, messages, and campaigns."
- **Correct**: "Customer Relationship Infrastructure Designed For Scalable Operations."
- **Dashboards**: Don't flood the page with full dashboard screenshots (makes it feel too SaaS). Use abstract graphics, architecture visuals, clean UI snippets, and partial previews.

### 8. Typography and Layout over Heavy Graphics
- Rely on strong typography, large headings, white space, alignment, and calm sections to convey quality.
- Avoid excessive animations and heavy effects.
- **Design Style**: Clean, spacious, corporate, minimal, structured (white backgrounds, few dark sections, soft gradients, thin borders, subtle shadows). Use strong font weights.

### 9. Mega Menu and Footer Structure
- **Mega Menu**: Should imply a large company structure (Solutions, Industries, Platforms, Company).
- **Footer**: Needs to be massive, containing links to Solutions, Industries, Platforms, Technologies, Company, Resources, and Contact.

### 10. Authority Elements
- **Founder Message**: Include a section with a highly professional photo and a short message focusing on the *vision* (not personal).
- **Case Studies**: Use "Case Studies" instead of "Portfolio" (Portfolio gives a Freelance vibe). Structure them by Problem, Solution, Result, Industry.
- **Many Pages**: Having many pages (even if simple) gives the impression of authority and scale.



---


---
description: "Rules for designing and fixing DomPDF Blade templates to prevent horizontal overflow and text cutoff."
---

# Rule: DomPDF Layout & Overflow Prevention

## Problem Statement
When designing PDF templates (like Invoices or Receipts) using `barryvdh/laravel-dompdf` (DomPDF engine), applying standard CSS properties like `width: 100%` alongside `padding` inside the main `.container` or `body` elements causes severe horizontal overflow. DomPDF does not fully respect `box-sizing: border-box`, meaning the total width becomes `100% + padding_left + padding_right`. This results in the right side of the invoice (e.g., total amounts, right-aligned text) being completely cut off from the final generated PDF.

## Rules & Guidelines

### 1. Prohibition of `width: 100%` on Padded Containers
- **Never** apply `width: 100%` to a main wrapping container (like `.container`, `.wrapper`, or `body`) if that container also has `padding`.
- **Example of Failure:**
  ```css
  /* ❌ INCORRECT (Will push right-side content off the page in DomPDF) */
  .container {
      width: 100%;
      padding: 48px;
      box-sizing: border-box; /* Ignored or poorly supported by DomPDF */
  }
  ```

### 2. Correct Approach for Spacing and Width
- Let block-level elements naturally fill the space without an explicit width, or rely entirely on `@page` margins instead of container padding.
- **Example of Success:**
  ```css
  /* ✅ CORRECT (Block elements natively adapt to the page bounds without overflow) */
  .container {
      padding: 48px; /* Natural block expansion prevents overflow */
  }
  ```

### 3. Full-Width Tables
- For tables (e.g., `.items-table`, `.header-table`), you may use `width: 100%;` because tables correctly calculate column widths within the bounds of their parent container.
- Do not apply horizontal padding directly to a table that has `width: 100%`. Apply padding to the `th` and `td` elements instead.

### 4. Floating Elements
- When using `float: right;` (e.g., for the totals section), ensure it has a fixed width or `width: auto;` to prevent layout collapse. It will align correctly against the right edge of the parent container as long as the parent is not overflowing.

### 5. Summary Checklist
- [ ] Have I ensured that no `.container` combines `width: 100%` with horizontal `padding`?
- [ ] Is `box-sizing: border-box;` avoided or not relied upon for core structural elements in DomPDF views?



---


