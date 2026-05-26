---
name: Contract iSAAS Transformation
description: Guidelines and architecture for decoupling the Contract and Proposal system into a standalone, 100% free Independent SaaS (iSAAS) for freelancers.
---

# Contract & Proposal iSAAS System

This skill defines the strategic and architectural rules for transforming the existing internal Contract & Proposal system into a standalone, **100% free iSAAS (Independent SaaS)** targeted at freelancers.

## 1. Product Strategy & Philosophy

The Contract iSAAS serves as a high-value **lead magnet and ecosystem entry point**.
- **The Problem:** Freelancers struggle to create professional proposals, estimate costs accurately, and generate binding contracts.
- **The Solution:** We provide an enterprise-grade AI estimator, proposal generator, and contract management system **entirely for free**.
- **The Catch (The Business Model):** There is no catch for the feature itself. However, by using it, freelancers enter the Musoftware ecosystem. Once they trust the platform, they become prime candidates for our premium marketplace plugins, automation tools, and the Musoftware Runtime.

## 2. Architectural Decoupling

Historically, contracts and proposals might have been built as "Admin" tools. To function as an iSAAS, they must be completely decoupled from the core administrative backend.

### User Roles & Routing
- **Freelancer Portal:** The UI must be accessible to standard registered users (freelancers), not just platform admins.
- **Dedicated Namespace:** Route namespaces should reflect the iSAAS nature, e.g., `/app/contracts`, `/app/proposals`, or a dedicated subdomain/module.
- **Client Facing Pages:** The clients of the freelancers need public, signed URLs to view and accept proposals/contracts without creating an account on Musoftware.

### Data Isolation (Multi-tenancy)
- Every `ProjectProposal` and `PlatformContract` must strictly belong to a `user_id` (the freelancer).
- Queries MUST ALWAYS be scoped to the authenticated user. Never allow `ProjectProposal::find($id)` without verifying ownership (`where('user_id', Auth::id())`).

## 3. Core Features of the iSAAS

### A. The AI Price Calculator
- Freelancers input client requirements in plain text.
- The system calls the OpenAI integration (via `PriceCalculatorService`) to generate a structured, professional cost breakdown and timeline.
- *Rule:* Ensure rate limiting is applied so free users do not exhaust our OpenAI API credits maliciously.

### B. Proposal Management
- Freelancers can save AI estimates as Draft Proposals.
- They can edit line items, modify durations, and adjust costs.
- They can generate a public link to send to their client.

### C. 1-Click Contract Generation
- Once a proposal is approved by the client (or verbally agreed), the freelancer clicks "Convert to Contract".
- The system maps `ProjectProposal` data directly into a `PlatformContract` using the unified billing models.

### D. Digital Signatures & Client Portal
- Contracts must have a public view (e.g., `/contracts/{uuid}/view`).
- Clients can type their name to electronically sign the contract.
- Once signed, the contract becomes immutable and a PDF can be generated.

## 4. UI/UX Rules (Apple-Level Simplicity)

- **Premium Black & White Aesthetic:** Follow the standard Musoftware design system. The interface must look expensive and trustworthy, which elevates the freelancer's brand in the eyes of their client.
- **Mobile First:** Freelancers often generate or check proposals on the go. The UI must utilize the project's automatic mobile-responsive card system.
- **No Complex Dashboards:** Focus on a linear, clean workflow: `Idea -> AI Estimate -> Proposal -> Contract -> Signed`.

## 5. Implementation Checklist

When working on this iSAAS transformation, ensure the following:
- [ ] Models (`ProjectProposal`, `PlatformContract`) are correctly related to the `User` model.
- [ ] Controllers enforcing data isolation (`where('user_id', auth()->id())`) are implemented.
- [ ] Public-facing routes for Clients (viewing/signing contracts) use secure UUIDs or signed URLs, NOT sequential IDs.
- [ ] The feature is completely removed from the restricted `Admin` namespace and made available in the freelancer's main dashboard.
- [ ] Onboarding flow is smooth: A new user can sign up and generate an AI proposal in under 60 seconds.
