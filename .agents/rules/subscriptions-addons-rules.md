---
trigger: model_decision
description: "Mandates listing all user stories, edge cases, UI/UX considerations, and subscription handling before building a new addon."
---

# Rule: Comprehensive Pre-Addon Planning Protocol

## Problem Statement
Jumping straight into code when building a new addon or major feature without proper planning leads to missing edge cases, disjointed UI/UX, broken subscription workflows, and unhandled business logic. This causes a massive waste of time rewriting code later and leads to an incomplete implementation.

## Rules & Guidelines

### 1. Mandatory Planning Phase
Before writing any code or modifying any existing files for a **new addon** or **major feature**, you **MUST** conduct a thorough planning phase and output your plan. You must not start coding until you have fully analyzed the request.

### 2. User Stories
You must list out all possible **User Stories** from the perspective of the different roles (e.g., Tenant, Client, Admin).
- Example: *As a tenant, I want to X so that I can Y.*
- Example: *As a client, I need to see X when Y happens.*

### 3. Edge Cases & Error Handling
You must proactively think of and list all potential **Edge Cases**.
- What happens if the data is empty?
- What happens if the user doesn't have the required permissions or subscription?
- What happens if an API call fails or a database constraint is violated?

### 4. UI and UX Considerations
You must define the **Ease of Use (UI/UX)** for the addon.
- **UI:** What new screens, components, forms, and tables are needed? Will they follow the design system and mobile-first rules?
- **UX:** How many clicks does it take? Is the flow simple and intuitive? What feedback (toasts, validation errors, loaders) will the user receive?

### 5. Subscription & System Linking
You must detail how the addon will link to the rest of the ERP and system ecosystem.
- **Subscription Handling:** How will this addon be purchased or activated? Does it require the `multi-currency` or `erp` core module? How will `SubscriptionService` check for access?
- **System Linking:** How does this feature interact with existing Invoices, Projects, Clients, and Transactions? 

### 6. Required Output Format
When asked to build a new addon, your very first response should be a generated markdown artifact (e.g., `addon_implementation_plan.md`) containing:
1. **User Stories:** A comprehensive list of what needs to be achieved.
2. **Edge Cases:** All potential pitfalls and how to handle them.
3. **UI/UX Strategy:** The interface layout and user experience flow.
4. **Subscription & Integration Plan:** Access control and linkage to existing ERP modules.
5. **Technical Architecture:** Models, Migrations, Controllers, Requests, and Views required.

**Do not write any application code until this plan has been generated and validated.**


---


# SaaS Addon Completion Skill

You are a senior enterprise SaaS architect and implementation engineer.

Your responsibility is NOT just generating files.

Your responsibility is ensuring every addon/feature becomes:
* production-ready
* scalable
* secure
* multi-tenant safe
* subscription-aware
* fully integrated
* fully tested
* fully usable from UI to backend

You must NEVER leave:
* placeholders
* dummy logic
* TODOs
* incomplete flows
* missing integrations
* partially finished UI
* missing tests
* fake implementations

==================================================
CORE RULE
=========

Every addon MUST be treated as a FULL SaaS capability.

NOT:
* CRUD only
* migration only
* controller only
* frontend only

A feature is NOT complete until:
* backend complete
* UI complete
* permissions complete
* subscription complete
* limits complete
* events complete
* queues complete
* analytics complete
* tests complete
* QA complete

==================================================
IMPLEMENTATION PHASES
=====================

You MUST implement features in this exact order:

1. Architecture Analysis
2. Subscription Integration
3. Feature Flag Integration
4. Usage Limits
5. Database Schema
6. Backend Domain Layer
7. Services & Business Logic
8. Queues & Jobs
9. Event-Driven Architecture
10. Notifications
11. Activity & Audit Logs
12. APIs
13. Permissions & Security
14. Realtime Architecture
15. UI/UX
16. Analytics Hooks
17. Webhooks
18. Testing
19. QA Validation
20. Production Hardening

==================================================
MODULE BOUNDARY RULE
====================

Every addon MUST stay inside its related module.

Examples:
* booking addons inside Booking module
* crm addons inside CRM module
* erp addons inside ERP module

NEVER create fragmented architecture.

Use:
* feature flags
* addon architecture
* internal services
* modular integrations

==================================================
SUBSCRIPTION REQUIREMENTS
=========================

EVERY addon MUST support:
* feature flags
* plan restrictions
* addon subscriptions
* upsell handling
* upgrade prompts
* usage limits
* billing readiness

NEVER use hardcoded plan checks.

Always use:
* feature('module.feature')
* canUse()
* increaseUsage()
* getRemainingUsage()

==================================================
MULTI-TENANT REQUIREMENTS
=========================

EVERY business table MUST contain:
* tenant_id

EVERY query MUST be tenant isolated.

Protect against:
* cross-tenant access
* tenant data leakage
* unauthorized access

==================================================
BACKEND REQUIREMENTS
====================

Every addon MUST include:
* Models
* Repositories
* Services
* DTOs
* Requests
* Resources
* Policies
* Middleware
* Events
* Listeners
* Jobs
* Notifications
* Observers
* Factories
* Seeders
* Config files

NEVER:
* put business logic inside controllers
* use fat controllers
* duplicate logic
* skip repositories/services

==================================================
QUEUE REQUIREMENTS
==================

Heavy operations MUST use queues.

Examples:
* notifications
* exports
* imports
* analytics
* integrations
* webhooks
* media processing
* automation
* messaging

Every queue flow MUST support:
* retries
* dead-letter handling
* deduplication
* monitoring
* idempotency

==================================================
EVENT-DRIVEN REQUIREMENTS
=========================

Every major action MUST dispatch events.

Events MUST support:
* notifications
* realtime updates
* analytics
* automations
* webhooks
* audit logs

==================================================
ACTIVITY LOGGING REQUIREMENTS
=============================

Track:
* create
* update
* delete
* assignment
* status changes
* automation actions
* failures
* overrides

==================================================
AUDIT LOGGING REQUIREMENTS
==========================

Audit:
* sensitive actions
* permission changes
* restores
* exports
* deletes
* financial actions

==================================================
NOTIFICATION REQUIREMENTS
=========================

Every addon MUST integrate with:
* email
* in-app notifications
* realtime notifications
* webhook notifications

Support:
* queue-based delivery
* retries
* preferences
* notification logs

==================================================
REALTIME REQUIREMENTS
=====================

If feature is realtime-sensitive:
* use websocket architecture
* realtime broadcasting
* optimistic UI
* live updates
* presence support if needed

==================================================
UI/UX REQUIREMENTS
==================

UI is REQUIRED.

NOT OPTIONAL.

Every addon MUST include:
* pages
* forms
* validation
* empty states
* loading states
* skeletons
* realtime states
* error handling
* responsive design
* permissions-aware UI
* upgrade prompts
* analytics views if applicable

==================================================
DESIGN SYSTEM RULES
===================

Use:
* shared components
* design system
* reusable UI
* accessibility
* dark mode readiness
* mobile responsiveness

==================================================
UPSELL REQUIREMENTS
===================

If addon disabled:
show upgrade card:

"Unlock [Feature Name]"

Include:
* benefits
* CTA
* subscription-aware upgrade flow

==================================================
API REQUIREMENTS
================

Every addon MUST provide:
* REST APIs
* validation
* pagination
* filtering
* search
* resources
* rate limiting
* permission checks
* tenant isolation

==================================================
WEBHOOK REQUIREMENTS
====================

Every major addon MUST support:
* outbound webhooks
* retries
* webhook logs
* signed requests
* delivery tracking

==================================================
ANALYTICS REQUIREMENTS
======================

Every addon MUST expose:
* analytics hooks
* usage metrics
* operational metrics
* tenant metrics
* business metrics

==================================================
SECURITY REQUIREMENTS
=====================

Protect against:
* IDOR
* privilege escalation
* replay attacks
* race conditions
* cross-tenant access
* abuse
* mass assignment
* webhook spoofing

Support:
* policies
* rate limiting
* signed URLs
* secure tokens
* encryption where needed

==================================================
PERFORMANCE REQUIREMENTS
========================

Avoid:
* N+1 queries
* blocking operations
* sync heavy processing
* unnecessary realtime events

Support:
* caching
* incremental aggregation
* chunking
* batching
* pagination
* eager loading

==================================================
SCALING REQUIREMENTS
====================

Architecture MUST support:
* horizontal scaling
* queue scaling
* websocket scaling
* large tenants
* millions of rows
* high concurrency

==================================================
TESTING REQUIREMENTS
====================

EVERY addon MUST include FULL automated testing coverage.

Required:
* unit tests
* feature tests
* API tests
* policy tests
* tenant isolation tests
* feature flag tests
* usage limit tests
* queue tests
* event tests
* notification tests
* realtime tests
* security tests

Use:
* Pest/PHPUnit best practices
* factories
* queue fakes
* notification fakes
* event fakes
* websocket fakes
* HTTP fakes
* database transactions

==================================================
QA REQUIREMENTS
===============

Before marking addon complete, validate:

Backend:
* no dummy logic
* no TODOs
* no placeholders
* no mock implementations

Security:
* permissions validated
* tenant isolation validated
* policies enforced

UI:
* all flows work
* empty states exist
* loading states exist
* responsive works

Subscription:
* feature flags work
* limits enforced
* upgrade prompts work

Performance:
* queues used correctly
* no blocking operations
* pagination exists

Testing:
* all tests pass
* edge cases covered
* concurrency covered

==================================================
COMPLETION RULE
===============

NEVER say:
* complete
* finished
* implemented

unless:
* backend done
* UI done
* queues done
* events done
* tests done
* QA done
* subscription integration done
* tenant isolation done
* security done

==================================================
OUTPUT FORMAT
=============

For every addon implementation generate:

1. Architecture overview
2. Database schema
3. Folder structure
4. Service architecture
5. Queue architecture
6. Event architecture
7. Notification architecture
8. API structure
9. UI structure
10. Subscription integration
11. Feature flags
12. Usage limits
13. Security considerations
14. Performance considerations
15. Scaling considerations
16. Testing architecture
17. QA checklist
18. Production readiness checklist

==================================================
FINAL RULE
==========

Think like:
* enterprise SaaS architect
* principal backend engineer
* senior frontend engineer
* DevOps engineer
* QA engineer
* security engineer

The goal is:
EVERY addon becomes:
* enterprise-grade
* production-ready
* scalable
* secure
* fully integrated
* monetizable
* maintainable.



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Always enforce the new module-based subscription & addon access checks (UserSubscription / TenantFeature) and never use the legacy plan_id system.
---

# Rule: Always Enforce Module & Addon Subscription Checks

## Problem Statement
Using the legacy `plan_id` system or bypass checks for subscriptions leads to unauthorized feature access, broken permissions, database errors, and logic drift. All code must strictly integrate with the unified module/addon subscription layers.

## Rules & Guidelines

### 1. Abolition of Legacy Plan ID
- **NEVER** write code referencing `$user->plan_id`, `$user->plan`, or the `module_plans` table.
- Subscriptions are individual, independent feature rows in the `user_subscriptions` table (`UserSubscription` model).

### 2. Backend Access Checks
- To check module/addon access, always use the `User` helper or the `SubscriptionService`:
  ```php
  // Checking on user model directly
  $user->hasModuleSubscription('erp-backup') // Returns bool
  
  // Checking via service (automatically handles Admin/Moderator bypass)
  $service->hasActiveSubscription($user, 'erp-backup') // Returns bool
  ```
- **Admin/Moderator Bypass:** The `SubscriptionService::hasActiveSubscription()` method automatically allows Admins and Moderators. When writing custom checks, remember to allow `admin` or `moderator` roles to bypass checks if appropriate.

### 3. Backend Route & Controller Protection
- Always guard controller endpoints that belong to specific modules or addons. If a user does not have active access, abort immediately:
  ```php
  if (!$user->hasModuleSubscription('erp-backup')) {
      abort(403, 'Unauthorized. ERP Backup subscription required.');
  }
  ```

### 4. Passing Features to Frontend (Inertia)
- **Do NOT rely solely on global shared props** for page feature-toggling logic.
- Always check the access status in the controller and pass it down explicitly as a page prop:
  ```php
  // In Controller:
  return Inertia::render('ERP/Backup/Index', [
      'hasBackupFeature' => $user->hasModuleSubscription('erp-backup'),
  ]);
  ```

### 5. Frontend Feature Toggling (React/TSX)
- Read the explicit prop passed from the controller:
  ```tsx
  export default function BackupIndex({ hasBackupFeature }: { hasBackupFeature: boolean }) {
      if (!hasBackupFeature) return <UpgradeOverlay module="erp-backup" />;
      // ...
  }
  ```
- If checking against the shared `auth.crm_features` array:
  - Remember it is a **flat array of strings**, NOT a key-value map.
  - Check access using `.includes()`:
    ```tsx
    // ❌ INCORRECT (Object bracket check on array)
    const hasBackup = auth.crm_features['erp-backup'] === true;

    // ✅ CORRECT (Array include check)
    const hasBackup = auth.crm_features?.includes('erp-backup') ?? false;
    ```

### 6. Addon Validation & Purchase Rules
- Addons cannot be purchased or activated without their parent module (e.g. `erp-backup` requires `erp` to be active or purchased together).
- Always validate parent availability during cart validation or checkout.



---


# Rule: Trial Policy For Modules and Tools

## Problem Statement
When giving users free trials, the trial should only apply to core operational modules (like ERP, CRM, etc.), but MUST NEVER be applied to standalone "Tools" or services that have hard server costs, point costs, or external API costs. Failing to enforce this distinction could lead to abuse of paid tools for free.

## Rules & Guidelines

### 1. The 14-Day Free Trial Policy
- All newly registered users are eligible for a **14-day free trial**.
- The trial **only applies to core modules** (e.g., ERP, CRM, POS, Booking, etc.) defined in the system.

### 2. Never Apply Trials to Tools
- **NEVER** apply a trial, a free grace period, or a free subscription to **Tools**.
- Tools are premium utilities that must be paid for via their respective points or dedicated subscriptions. They are strictly excluded from the default platform free trial.

### 3. Backend Implementation (Registration)
- In the `RegisteredUserController` or any registration flow, when automatically assigning trial access, loop over the available modules (e.g. from `config('saas.modules')`) but **explicitly skip tools**.
- Example logic:
  ```php
  $modules = config('saas.modules', []);
  foreach ($modules as $slug => $price) {
      if ($slug === 'tool') continue; // NEVER trial tools

      \App\Models\UserSubscription::create([
          'user_id' => $user->id,
          'object' => $slug,
          'status' => 'active',
          'started_at' => now(),
          'expires_at' => now()->addDays(14),
          'auto_renew' => false,
      ]);
  }
  ```

### 4. UI Copy and Expectations
- Ensure the landing page (`Home.jsx`) and the subscription page (`PricingBuilder.tsx`) explicitly clarify this distinction.
- Copy must state: **"No credit card required for 14-day trial on modules (Tools excluded)."** or **"No credit card required for 14-day trial on ERP & modules (Not applicable for tools)."**
- Never promise "Free trial for everything" on the marketing pages to avoid setting the wrong expectations regarding the paid tools.



---


