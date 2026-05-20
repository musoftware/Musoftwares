---
name: CRM System Architecture
description: Customer Relationship Management design, lead tracking, and interaction continuity.
---

# CRM System Architecture

This skill defines how the Customer Relationship Management (CRM) module is structured within Musoftware. It emphasizes lead intelligence, communication tracking, and actionable UI.

## Activation Conditions
This skill automatically applies when you are:
- Working on Lead Management or Contact features.
- Implementing communication tracking (Emails, Notes, Calls).
- Designing the sales pipeline or deal stages.
- Building UI components that manage user interactions.

## 1. CRM Philosophy
A CRM is useless if it's just an address book. It must be an engine for action.
- **Intelligence over Data Entry**: The system should automatically log activities, track deal staleness, and highlight next steps.
- **Relationship Context**: When viewing a lead or contact, the user must instantly see the full history of interactions (emails, notes, active deals, invoices).

## 2. Core Components

### Lead Intelligence
- Leads are not just names and emails. They have associated data: Lead Source, Temperature (Cold/Warm/Hot), and Last Contacted Date.
- Backend services should calculate and update deal probability and staleness automatically via scheduled jobs or event listeners.

### The Activity Stream (Notes & Interactions)
- Every interaction must be logged into an Activity Stream.
- Do not build separate, disconnected pages for "Notes", "Calls", and "Emails". These should be unified components attached polymorphic-ally to the Lead or Client entity.

## 3. UI/UX Standards for CRM
- **The 360-Degree View**: The primary CRM view for a contact should be a split layout: left side for static details (name, company, tags), right side for the chronological activity stream and actionable input forms (e.g., "Add Note").
- **Pipeline Visuals**: Deal stages are best represented as Kanban boards or clear progress wizards, built entirely with Shadcn components.

## 4. Implementation Constraints
> [!IMPORTANT]
> **Polymorphic Relations.** Activities (Notes, Calls, Tasks) should be designed polymorphically so they can be attached to Leads, Deals, or existing Clients without duplicating database tables.

## Summary Checklist
- [ ] Are new interactions added to a unified Activity Stream?
- [ ] Is the CRM UI providing actionable context rather than just displaying static text fields?
- [ ] Are deal stage transitions handled by backend services that fire appropriate events (e.g., `DealWon`)?
