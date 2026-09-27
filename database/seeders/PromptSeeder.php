<?php

namespace Database\Seeders;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        $adminId = $adminUser?->id;

        $prompts = [
            [
                'title' => 'Human UI Design & Anti-AI Slop Auditor',
                'category' => 'UI',
                'description' => 'Audits any UI component or landing page to eliminate AI clichés, pill badges, and generic purple gradients.',
                'is_featured' => true,
                'copy_count' => 14,
                'tags' => ['frontend', 'react', 'tailwind', 'design-system', 'wcag'],
                'prompt' => "Act as a World-Class UI/UX Architect. Audit this interface and enforce authentic, human design:
1. Eliminate all floating pill badges above headers, generic ambient blur orbs, and neon gradient buttons.
2. Replace unreadable light-gray text with WCAG AA compliant slate tones (minimum 4.5:1 contrast).
3. Enforce an 8pt spatial grid with generous whitespace and clear typographic focal points.
4. Replace Russian-doll nested cards with seamless canvas transitions and crisp hairline dividers.
5. Provide the exact Tailwind CSS / React component code with accessible keyboard focus states.",
            ],
            [
                'title' => 'Zero-Nesting Defensive Controller Architect',
                'category' => 'Backend',
                'description' => 'Refactors complex Laravel controller methods into flat execution pipelines with early returns and guard clauses.',
                'is_featured' => true,
                'copy_count' => 21,
                'tags' => ['laravel', 'php', 'clean-code', 'solid', 'refactoring'],
                'prompt' => "Refactor the following backend method adhering to strict defensive engineering:
- Maximum 2 levels of indentation. Eliminate all nested if/else ladders.
- Use guard clauses and early returns for all pre-condition validations.
- Extract domain mutations into dedicated service classes or action pipelines.
- Ensure all database queries run inside an atomic DB::transaction with pessimistic locking when modifying financial balances.
- Add strict parameter and return type declarations.",
            ],
            [
                'title' => 'API Security & Injection Hardening Sweep',
                'category' => 'Security',
                'description' => 'Performs a comprehensive vulnerability assessment covering IDOR, mass assignment, SQLi, and SSRF.',
                'is_featured' => true,
                'copy_count' => 9,
                'tags' => ['security', 'owasp', 'laravel', 'auth', 'idor'],
                'prompt' => "Perform a comprehensive security audit on this Laravel endpoint and database query:
1. Check for IDOR vulnerabilities: Verify that the resource belongs to auth()->user() or the current tenant.
2. Inspect for Mass Assignment: Ensure Model::create() uses strictly validated FormRequest fields rather than \$request->all().
3. SQL Injection check: Ensure zero raw DB::raw bindings concatenate user input.
4. Rate limiting: Recommend the exact throttle middleware rate for this sensitivity tier.
5. Audit logging: Ensure all destructive actions write to the admin audit log with actor ID, IP, and timestamp.",
            ],
            [
                'title' => 'High-Performance MySQL Query Optimizer',
                'category' => 'Database',
                'description' => 'Diagnoses N+1 issues, designs composite indexes, and optimizes slow Eloquent queries.',
                'is_featured' => false,
                'copy_count' => 17,
                'tags' => ['mysql', 'indexing', 'eloquent', 'performance'],
                'prompt' => "Analyze this Eloquent query and database schema:
1. Identify all potential N+1 query leaks and specify the exact eager loading relationships needed with only required columns.
2. Recommend the optimal composite index (taking column cardinality and left-prefix rule into account).
3. If this query deals with pagination on millions of rows, convert standard offset pagination into keyset/cursor pagination.
4. Provide the exact Laravel migration code for the suggested index.",
            ],
            [
                'title' => 'Resilient Docker & CI/CD Pipeline Architect',
                'category' => 'DevOps',
                'description' => 'Creates zero-downtime deployment workflows, multi-stage Docker builds, and automated health checks.',
                'is_featured' => false,
                'copy_count' => 6,
                'tags' => ['docker', 'github-actions', 'deployments', 'devops'],
                'prompt' => "Design a production-grade multi-stage Dockerfile and GitHub Actions workflow for Laravel 12 + Vite:
- Stage 1: Build frontend assets using Node 20 alpine with caching.
- Stage 2: Install composer dependencies with --no-dev and optimized autoloader.
- Stage 3: PHP 8.3 FPM production runtime with OPcache preloading, non-root user, and health check endpoint.
- Include automated rollback on failed deployment and zero downtime asset caching.",
            ],
            [
                'title' => 'Modular SaaS Domain Separation Enforcer',
                'category' => 'Architecture',
                'description' => 'Enforces strict bounded contexts and decouples SaaS modules using an event bus pattern.',
                'is_featured' => true,
                'copy_count' => 11,
                'tags' => ['architecture', 'ddd', 'saas', 'event-driven'],
                'prompt' => "Review the following modules and decouple them according to Domain-Driven Design:
1. Prohibit direct model imports across domain boundaries (e.g. Booking module must not import ERP models directly).
2. Define a clean Contract / Interface for inter-module data retrieval.
3. Replace cross-module database writes with asynchronous Domain Events dispatched via Laravel's event bus.
4. Ensure each module can be unit tested and deployed independently.",
            ],
        ];

        foreach ($prompts as $item) {
            Prompt::updateOrCreate(
                ['title' => $item['title']],
                array_merge($item, ['user_id' => $adminId])
            );
        }
    }
}
