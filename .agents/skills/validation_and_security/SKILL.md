---
name: validation_and_security
description: Enforces tri-path validation, strict subscription gating, data leak prevention, security rules, and a strict git workflow for the Musoftwares ecosystem.
---

# Validation and Security Guidelines

This skill provides comprehensive instructions for implementing validation, ensuring security, and adhering to strict development workflows within the Musoftwares project. All code generation, modification, and feature implementations MUST follow these guidelines.

## 1. Tri-Path Validation

Every interactive route, form submission, and API endpoint must account for the following three paths:

1.  **Happy Path**: 
    - Ensure successful execution for valid data and authorized users.
    - Return clear success indicators (e.g., HTTP 200/201, success flash messages).
2.  **Edge Cases**:
    - Handle invalid data, partial data, and network drops gracefully.
    - Protect against unauthorized tenant access (ensure users can only interact with their own workspace/tenant data).
    - Provide user-friendly validation errors without exposing internal architecture.
3.  **Security Limits**:
    - Implement rate limiting (e.g., using Laravel's `throttle` middleware) on sensitive endpoints.
    - Enforce brute force protection on authentication and data-mutating routes.
    - Strictly enforce module and addon subscriptions (see below).

## 2. Strict Subscription Gating

- **Enforcement**: Access to specific modules (e.g., ERP, CRM), features, or routes must be strictly gated based on the active subscription, points, or purchased addons.
- **Instant Feedback**: Do not load partial content or API data if a subscription is missing or expired. Instead, intercept the request via middleware and instantly render the upgrade preview component (e.g., `UpgradePreview.tsx`) or a Shadcn UI native `403 Forbidden` view.
- **Backend Protection**: Never rely solely on frontend gating. The backend must reject unauthorized requests with a `403` status.

## 3. Data Leak Prevention & Hiding Laravel Footprint

- **No Data Leaks**: Never return raw database exceptions, stack traces, or unfiltered models to the frontend. Always use Eloquent API Resources to strictly control exactly what fields are serialized and sent to the client. Partial content loads must be strictly blocked if authorization fails.
- **Hide Laravel Footprint**: 
    - Ensure default error pages are replaced with custom, premium UI error pages (e.g., `Error.tsx`).
    - Never expose framework internals, configuration details, or the `.env` state.
    - Standardize API error responses into generic JSON structures.

## 4. Strict Git Workflow (CRITICAL INSTRUCTION FOR AI AGENTS)

When implementing features, fixing bugs, or making modifications in this repository, you MUST adhere to the following strict git workflow:

1.  **Isolated Branches**: Every new feature, bug fix, or isolated task MUST be developed in its own dedicated branch. Do not make changes directly on the main branch.
    - Branch naming convention: `feature/name-of-feature`, `fix/bug-description`, `chore/task-name`.
2.  **Atomic Commits**: Commit changes immediately after every logical modification. Do not bundle massive, unrelated changes into a single, monolithic commit.
3.  **Descriptive Messages**: Use clear, descriptive commit messages outlining what changed and the rationale.
4.  **Review Readiness**: All code modifications must be committed properly to their respective branches so they can be reviewed before merging into the main branch.

*(Self-correction for the current task: As per the user's immediate instructions, do NOT create branches while initially generating this SKILL.md file. This rule applies to all future development tasks.)*
