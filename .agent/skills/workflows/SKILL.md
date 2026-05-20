---
name: Operational Workflows
description: The mindset required to build Musoftware: Apple-level simplicity, guided builders, and operational continuity.
---

# Operational Workflow Philosophy

This skill defines the overarching mindset required to build Musoftware features. It teaches the AI and engineers to build systems that feel simple, calm, guided, and obvious.

## Activation Conditions
This skill automatically applies when you are:
- Scoping a new feature, module, or plugin.
- Deciding between building a simple table vs. a structured wizard.
- Reviewing UX flows for business logic.

## 1. Apple-Level Simplicity Mandate
The platform MUST feel approachable and simple. Even though the system has enterprise-grade automation power, the user should feel: **“This is easy.”**
- **No Fake Dashboards**: Do not build pages with random placeholder charts.
- **No Configuration Engines**: Avoid massive settings pages, config overload, and textarea-heavy interfaces.

## 2. Operational UX Rule (CRITICAL)
Plugins and modules must feel like **operational workflows**, not configuration engines.
- **BAD**: Exposing 50 fields on one page and asking the user to "Trigger Worker".
- **GOOD**: A guided builder with steps:
  1. Select Accounts
  2. Import Audience
  3. Write Message
  4. Review
  5. Launch

## 3. Progressive Disclosure
Simple UI does NOT mean removing functionality. It means hiding system power intelligently.
- **Simple by default**: The primary UI should only ask the user for the absolute essentials.
- **Advanced when needed**: Keep advanced controls powerful and accessible, but hide them behind expandable sections. Never show them first.

## 4. Event-Driven Systems & Continuity
- Users should never hit a "dead end." Every page should logically lead to the next step in the workflow.
- Provide "Next Actions" prominently using calm, direct language (e.g., "Start Campaign" instead of "Initialize Session").

## Summary Checklist
- [ ] Does the UI feel like a guided operational workflow rather than a massive technical form?
- [ ] Are advanced settings hidden behind progressive disclosure?
- [ ] Is the primary call to action obvious, calm, and human-readable?
