---
name: testing-and-comprehensive-edits
description: Enforces complete cross-stack sweeps for every change, ensuring frontend/backend sync, and requires comprehensive unit, feature, and Playwright E2E tests for stability. Also enforces strict git branching workflows for agents.
---

# Testing and Comprehensive Edits Skill

This skill provides strict guidelines for implementing comprehensive edits across the stack and ensuring code stability through testing. It also dictates the mandatory Git workflow for all AI agents.

## 1. Comprehensive Cross-Stack Sweeps
When making any change, do not stop at a single file or function. 
- **Frontend/Backend Synchronization**: Trace the data flow from backend to frontend. If you modify a Laravel model, controller, or API resource, ensure the corresponding React/Inertia component, Typescript types, and forms are updated to handle the new structure.
- **Side-Effects**: Check for related code, missing logic, and how the changes affect database migrations, seeders, or translatable strings.
- **Bug & Security Sweeps**: Perform a final review of your changes to catch logical errors, missing authorizations, or unhandled exceptions.

## 2. Mandatory Testing (Stability First)
Every modification or new feature must be backed by appropriate tests.
- **Unit & Feature Tests**: Write PHPUnit/Pest tests for all backend logic, specifically covering the tri-path validation (Happy path, Edge cases, Security limits).
- **Playwright E2E Tests**: Critical UI workflows and cross-stack interactions must have Playwright End-to-End tests ensuring the actual rendered application works as expected.
- Never consider a task "done" without confirming the related tests pass and provide adequate coverage.

## 3. Strict Git Workflow for Agents
> **CRITICAL RULE FOR ALL FUTURE AI AGENTS**: You MUST follow this strict Git workflow for all tasks.

- **Branching**: Every feature, bugfix, or isolated task must be developed in its own dedicated branch. Do not work directly on `main` or `master`.
- **Atomic Commits**: Commit changes immediately after every logical modification or step in your plan. Do not wait until the entire feature is finished to make a single massive commit.
- **Commit Messages**: Write clear, descriptive commit messages outlining what changed and why.
