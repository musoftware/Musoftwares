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