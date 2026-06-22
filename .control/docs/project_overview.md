# Project Overview

Musoftwares is a comprehensive, modular, multi-tenant Business Management Platform. 

## Core Capabilities
It serves as a SaaS (Software as a Service), ERP (Enterprise Resource Planning), CRM (Customer Relationship Management), and Marketplace.
It is designed to handle core business operations, financial transactions, project management, and specialized domain workflows in a single deployable repository.

## Architectural Paradigm
The application employs a **Modular Monolith** architecture. This ensures cohesion while allowing physical grouping of domain logic.

### Modularity
Modularity is powered by `nwidart/laravel-modules`.
The system is divided into distinct bounded contexts, including but not limited to:
- Core
- ERP
- CRM
- Billing
- Booking
- Marketplace
- Fbmb
- Freelance

### Frontend-Backend Integration
The application operates as a modern Single-Page Application (SPA).
It seamlessly integrates with the Laravel backend using **Inertia.js**, which eliminates the need for a standalone API layer for the core web application.
