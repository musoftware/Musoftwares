
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



---


---
trigger: model_decision
description: "Rules for the Freelance module: enforces a points-based bidding and posting system, ranking mechanics, and skill-based matching."
---

# Rule: Freelance Module Points & Skill Matching

## Problem Statement
Implementing traditional escrow mechanics (holding and releasing fiat currency) introduces unnecessary financial complexity, regulatory overhead, and potential vulnerabilities into the Freelance module. Furthermore, displaying unstructured or generalized job feeds leads to poor user experiences and inefficient hiring. The Freelance module must be strictly points-based for job postings and proposals, and leverage a robust skill-matching algorithm to connect freelancers with jobs.

## Rules & Guidelines

### 1. Client Job Posting (Points Cost & Refund)
- **Base Job Posting Cost:** To add a job, a client must have purchased points. Posting a new job costs a base of **25 points**.
- **Minimum Bid Requirement (Premium Feature):** A client can optionally set a minimum points requirement for freelancers to apply (e.g., minimum 100 points per proposal).
- **Premium Cost Formula:** If the client sets a minimum bid, they must pay an equivalent amount on top of the base cost. (e.g., Base 25 points + 100 points minimum bid = **125 points total cost** to post the job).
- **Inactivity Refund Rule:** If a client posts a job and a full week (7 days) passes with **zero** freelancers applying, the system must automatically refund the total points spent (base + any premium minimum bid points) back to the client.

### 2. Freelancer Bidding System (Points Bidding & Ranking)
- **Proposal Cost:** To apply for a job, a freelancer must also buy points. Applying is not free.
- **Variable Bidding:** The freelancer can choose/specify how many points they want to spend when submitting their proposal, provided it meets the client's minimum bid requirement (if any).
- **Rank by Points:** The proposals list presented to the client must be ranked or sorted based on the number of points the freelancer spent. Higher points spent equals a higher ranking/position in the list.
- **Rejection Refund Rule:** If a freelancer applies but does not get selected (i.e., someone else is hired or the job is closed without hiring them), the points they spent on that proposal must be refunded to their balance.

### 3. Strict Points-Based Architecture (No Escrow)
- **Never implement an escrow system.** The module must not lock, hold, or transfer real-world currencies (e.g., USD, EGP) between clients and freelancers for job completion.
- **Code Check:** Ensure no references to `escrow`, `locked_fiat`, or `fiat_transfer` exist in the Freelance transaction logic.

### 4. Mandatory Skill-Based Matching
- **Freelancer Profiles:** Every Freelancer model/profile must have an explicit relationship with predefined **Skills**.
- **Job Postings:** Every Job model must define its **Required Skills**.
- **Matching Algorithm:** The primary feed and recommendation engine for freelancers must prioritize and match jobs based strictly on the intersection of their possessed skills and the job's required skills.
- **No Generic Feeds:** Do not build a feed that simply lists all jobs chronologically without prioritizing or filtering by the freelancer's specific skill set.

### 5. Summary Checklist
- [ ] Are clients charged a base of 25 points when posting a job?
- [ ] Are clients charged extra equivalent points if they set a minimum bid threshold?
- [ ] Are clients fully refunded if no one applies within 7 days?
- [ ] Do freelancers specify a custom amount of points to bid (respecting any minimums)?
- [ ] Are proposals ranked by the number of points spent by the freelancer?
- [ ] Are freelancers refunded their points if they are not selected for the job?
- [ ] Has the concept of "Escrow" been completely removed?
- [ ] Is the job discovery process driven by skill matching?



---


