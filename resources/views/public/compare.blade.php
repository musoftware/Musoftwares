@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            Technical Architecture Benchmark
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            Laravel 12 vs Node.js (2026 Enterprise Evaluation)
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
            Architectural comparison for mission-critical ERP engines, multi-tenant SaaS, and real-time event-driven infrastructure.
        </p>
    </div>

    <!-- Comparison Table & Breakdown -->
    <div class="max-w-4xl mx-auto px-6 sm:px-12 space-y-12">
        
        <div class="bg-[#161616] border border-[#262626] overflow-hidden">
            <div class="grid grid-cols-3 bg-[#1F1F1F] p-4 font-mono text-xs text-white font-bold border-b border-[#2B2B2B]">
                <div>Evaluation Metric</div>
                <div class="text-[#748660]">Laravel 12 + Octane</div>
                <div class="text-zinc-400">Node.js (Express/Nest)</div>
            </div>

            <div class="grid grid-cols-3 p-4 font-mono text-xs border-b border-[#222222]">
                <div class="text-white font-bold">Financial Ledgers & DB Transactions</div>
                <div class="text-[#748660]">Native Eloquent / PDO Lock &bull; 100% ACID</div>
                <div class="text-zinc-400">Prisma / TypeORM &bull; Manual Locks</div>
            </div>

            <div class="grid grid-cols-3 p-4 font-mono text-xs border-b border-[#222222]">
                <div class="text-white font-bold">Queue Processing & Horizon</div>
                <div class="text-[#748660]">Redis + Horizon GUI &bull; Zero Config</div>
                <div class="text-zinc-400">BullMQ &bull; Custom Setup</div>
            </div>

            <div class="grid grid-cols-3 p-4 font-mono text-xs border-b border-[#222222]">
                <div class="text-white font-bold">Single-Page App Integration</div>
                <div class="text-[#748660]">Inertia.js &bull; Zero API Glue Code</div>
                <div class="text-zinc-400">REST / GraphQL &bull; High Maintenance</div>
            </div>

            <div class="grid grid-cols-3 p-4 font-mono text-xs">
                <div class="text-white font-bold">Authentication & RBAC</div>
                <div class="text-[#748660]">Sanctum + Spatie Permission</div>
                <div class="text-zinc-400">Passport / Custom Middleware</div>
            </div>
        </div>

        <!-- Summary Analysis -->
        <div class="bg-[#161616] border border-[#262626] p-8 sm:p-10 space-y-4">
            <h2 class="text-xl font-bold text-white font-sans">Our Architectural Verdict</h2>
            <p class="text-xs sm:text-sm text-zinc-300 font-sans leading-relaxed">
                For high-integrity business applications, double-entry financial accounting, and enterprise SaaS, Laravel 12 paired with Inertia.js delivers unparalleled developer velocity, hardened security, and sub-millisecond query execution.
            </p>
        </div>

        <div class="text-center pt-8">
            <a href="/start-project" class="px-8 py-3.5 bg-[#748660] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-widest hover:bg-[#60704E] transition-all">
                {{ __('general.start_a_project') }} ➔
            </a>
        </div>

    </div>

</div>
@endsection
