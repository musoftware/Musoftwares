
# Rule: CRM Addons & Employee Roles Structure

## Problem Statement
When building or extending the CRM module, the AI might forget the specific addon hierarchy and intended workflows for different employee roles. The CRM operations are strictly modularized into distinct Addons to separate operational tiers, from basic data entry to advanced automations and management.

## Rules & Guidelines

### 1. The CRM Addons Architecture
The CRM functionality is split into 4 distinct addons. When building features or checking subscriptions, you **MUST** map the functionality to the correct addon constraint below:

#### Addon 1: `crm-sales-staff` (Basic Operations)
- **Roles Included:** Lead Collector, Telesales
- **Lead Collector Features:**
  - Fast, frictionless data entry forms for adding leads individually.
  - Bulk upload functionality (CSV/Excel import) to import large lists of prospects at once.
- **Telesales Features:**
  - Contacting leads, making calls, and moving leads through the sales pipeline.
  - **Pipeline View (Kanban/Board):** Visual pipeline interaction.
  - **Stages/Statuses:** Tracking intent (e.g., `New`, `Follow-up`, `Interested`, `Not Interested`, `Closed Won`).
  - Action logging (calls, follow-ups, notes).

#### Addon 2: `crm-sales-management` (Oversight & Tracking)
- **Roles Included:** Sales Manager, Team Leader
- **Features Included:** Performance Analytics, KPI Tracking
- **Managerial Requirements:**
  - **KPI Dashboard:** Dedicated views to track employee performance metrics.
  - Tracking metrics: Number of leads added, calls made, conversion rates, and pipeline bottlenecks.
  - Ability to reassign leads between telesales agents and oversee all pipelines.

#### Addon 3: `crm-call-center` (Voice & Quality Assurance)
- **Features Included:** Call Center Integration, Quality Control
- **Requirements:**
  - Built-in call dialing functionality or PBX integrations.
  - Call recording, listening, and quality control scoring.

#### Addon 4: `crm-advanced-operations` (Automation & Enterprise Workflows)
- **Features Included:** Automations, Workflows, SLA, Routing
- **Requirements:**
  - Automatic lead routing and assignment based on rules (e.g., round-robin or skill-based).
  - SLA (Service Level Agreement) enforcement (e.g., escalating untouched leads).
  - Workflow triggers (e.g., auto-sending emails/WhatsApp messages based on pipeline stage changes).

### 2. Implementation Constraints
- **Role Enforcement:** Ensure that users only see the interfaces relevant to their role. A Lead Collector shouldn't see advanced manager analytics unless permitted.
- **Subscription Checking:** Use `$user->hasModuleSubscription('addon-name')` to gate access to the respective dashboards and tools based on the 4 addons listed above.

### 3. Summary Checklist
- [ ] Are the distinct roles (Lead Collector, Telesales, Manager, Team Leader) mapped correctly to their respective addons?
- [ ] Is the `crm-sales-staff` addon strictly used for the core data entry and pipeline execution?
- [ ] Are all analytics and KPI dashboards gated behind the `crm-sales-management` addon?
- [ ] Are automations, SLA tracking, and routing gated behind `crm-advanced-operations`?
- [ ] Is telephony and call quality assurance gated behind `crm-call-center`?
