---
name: Deployment & Infrastructure
description: The deployment rules and infrastructure requirements for Musoftware.
---

# Deployment Architecture

This skill defines how Musoftware is deployed to production, ensuring zero-downtime updates and secure runtime environments.

## Activation Conditions
This skill automatically applies when you are:
- Modifying Dockerfiles or `docker-compose.yml`.
- Configuring CI/CD pipelines (GitHub Actions, etc.).
- Modifying the environment variables (`.env`) requirements.
- Writing deployment scripts.

## 1. Multi-Environment Deployment
- **Cloud Plane (Laravel + React)**: Deployed to standard web infrastructure (e.g., AWS, DigitalOcean, Vercel for frontend if decoupled, though Inertia binds them).
- **Local Runtime (NodeJS + Python)**: Packaged as an installable binary (via tools like pkg or electron-builder) or provided as a Docker image for advanced users.

## 2. Zero-Downtime Philosophy
- Database migrations must be non-destructive. Do not drop columns if an older version of the Runtime might still be attempting to write to them.
- Always add columns first, migrate data in a background job, and drop old columns in a subsequent release.

## 3. Worker Node Scaling
- The queue system must be horizontally scalable.
- Use Redis as the queue driver in production. Do not use the `database` driver for heavy workloads as it causes deadlocks.

## Summary Checklist
- [ ] Are migrations written in a non-destructive manner?
- [ ] Is Redis configured for production queues?
- [ ] Are environment variables documented in `.env.example`?
