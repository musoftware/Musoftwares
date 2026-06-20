---
name: frontend-architecture
description: Guidelines and strict rules for React 18.2, Inertia.js 2.0, TypeScript 5.0, Zustand, and Real-time updates in Musoftwares.
---

# Frontend Architecture Skill

This document defines the core frontend architectural guidelines, strict coding rules, and development workflows for the Musoftwares ecosystem. Adhere to these principles whenever modifying or generating frontend code.

## 1. Core Technologies
- **React 18.2**: Utilize modern React features like Concurrent Mode, Suspense, and Hooks. Keep components functional and avoid class components.
- **Inertia.js 2.0**: Use Inertia to bridge Laravel and React seamlessly. Avoid traditional API endpoints for page loads; rely on Inertia's routing and shared props.
- **TypeScript 5.0**: Ensure strict typings across all files. Define precise interfaces for Inertia page props and component props. Avoid `any` types.
- **Zustand**: Use Zustand for global state management. Keep stores modular and minimal. Do not use Redux or Context API for complex global state.

## 2. Real-time Updates & Graceful Degradation
- **WebSockets / SSE**: Implement real-time features (e.g., chat, tool runners, notifications) using WebSockets or Server-Sent Events.
- **Reconnection Strategy**: Implement a robust reconnect backoff strategy for WebSockets/SSE.
- **Graceful Degradation**: If socket connections fail, do not fail silently. Provide a clear, non-intrusive banner indicating "Reconnecting...". If real-time fails completely, degrade to graceful polling where appropriate to keep data relatively fresh without overloading the server.

## 3. Strict Git Workflow Rules (CRITICAL FOR AI AGENTS)
Future AI agents MUST follow this strict git workflow:
1. **Branching**: EVERY feature, bug fix, or refactor MUST be done in its own dedicated branch off the main branch (e.g., `feature/add-zustand-store`, `bugfix/fix-websocket-reconnect`). NEVER work directly on the `main` or `master` branch.
2. **Atomic Commits**: Commit changes after EVERY logical modification. Do not bundle multiple unrelated changes into a single commit.
3. **Commit Messages**: Use clear, descriptive commit messages following conventional commits (e.g., `feat: ...`, `fix: ...`, `refactor: ...`).
4. **Push & Review**: Once modifications are fully committed on the branch, push the branch and create a PR/MR if applicable. Do not merge directly unless instructed.

## 4. UI/UX Principles (from PRD)
- **Premium UI**: Use Shadcn UI. Enforce extreme UX simplicity and strict layout adherence.
- **Forms**: ERP Add/Edit forms must be full-width, dedicated pages. No modals or sliding sheets.
- **i18n Localization**: Zero hardcoded strings. Use translatable text via modular PHP arrays (no global JSON).
- **Multi-Currency**: No hardcoded currencies. Always format monetary values dynamically based on active currency.

By following this skill, you ensure that the frontend architecture remains scalable, strictly typed, highly responsive, and compliant with the Musoftwares project standards.
