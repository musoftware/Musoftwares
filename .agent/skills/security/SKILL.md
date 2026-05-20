---
name: Security & Permissions
description: Authorization, authentication, data isolation, and API security.
---

# Security Architecture

This skill defines the security perimeter for Musoftware, ensuring that client data is isolated and plugins cannot compromise the local machine or cloud environment.

## Activation Conditions
This skill automatically applies when you are:
- Modifying Laravel Policies, Gates, or Middleware.
- Writing API endpoints for the Local Runtime.
- Handling file uploads or user-generated content.
- Managing user roles, teams, and permissions.

## 1. Cloud Authorization (Tenant Isolation)
- Musoftware uses Team/Tenant isolation. Every record in the database must belong to a specific tenant/company.
- **Global Scopes**: Use Laravel Global Scopes to automatically filter queries by the authenticated user's `tenant_id`. Never rely solely on Controller-level `where()` clauses.

## 2. API Security for Local Runtime
- The Local Runtime authenticates to the Cloud using long-lived API Tokens (Personal Access Tokens via Laravel Sanctum).
- These tokens must be scoped strictly. The Runtime should only have permission to pull jobs and push telemetry, not access the core CRM data of the tenant unless explicitly authorized by a plugin's manifest.

## 3. Plugin Security Sandbox
- Plugins downloaded from the Marketplace are executed on the user's local hardware.
- While full virtualization is complex, Python workers should be executed with restricted environment variables and ideally within a chroot/Docker-lite container if supported by the user's OS.
- Never pass the root Cloud database credentials to a local plugin.

## Summary Checklist
- [ ] Are API endpoints protected by appropriate Middleware?
- [ ] Is data retrieval scoped to the active Tenant?
- [ ] Are plugin execution payloads sanitized before being sent to the local shell?
