@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-20">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            Technical Architecture Benchmark
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            Laravel 12 vs Node.js (2026 Enterprise Evaluation)
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            Architectural comparison for mission-critical ERP engines, multi-tenant SaaS, and real-time event-driven infrastructure.
        </p>
    </div>

    <!-- Comparison Table & Breakdown -->
    <div class="max-w-4xl mx-auto px-6 sm:px-12 space-y-12">
        
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm overflow-hidden">
            <div class="grid grid-cols-3 bg-[#f5f5f7] p-4 text-xs text-[#1d1d1f] font-bold border-b border-black/5">
                <div>Evaluation Metric</div>
                <div class="text-[#0071e3]">Laravel 12 + Octane</div>
                <div class="text-[#1d1d1f]/60">Node.js (Express/Nest)</div>
            </div>

            <div class="grid grid-cols-3 p-4 text-xs border-b border-black/5">
                <div class="text-[#1d1d1f] font-semibold">Financial Ledgers & DB Transactions</div>
                <div class="text-[#0071e3] font-medium">Native Eloquent / PDO Lock &bull; 100% ACID</div>
                <div class="text-[#1d1d1f]/60">Prisma / TypeORM &bull; Manual Locks</div>
            </div>

            <div class="grid grid-cols-3 p-4 text-xs border-b border-black/5">
                <div class="text-[#1d1d1f] font-semibold">Queue Processing & Horizon</div>
                <div class="text-[#0071e3] font-medium">Redis + Horizon GUI &bull; Zero Config</div>
                <div class="text-[#1d1d1f]/60">BullMQ &bull; Custom Setup</div>
            </div>

            <div class="grid grid-cols-3 p-4 text-xs border-b border-black/5">
                <div class="text-[#1d1d1f] font-semibold">Single-Page App Integration</div>
                <div class="text-[#0071e3] font-medium">Inertia.js &bull; Zero API Glue Code</div>
                <div class="text-[#1d1d1f]/60">REST / GraphQL &bull; High Maintenance</div>
            </div>

            <div class="grid grid-cols-3 p-4 text-xs">
                <div class="text-[#1d1d1f] font-semibold">Authentication & RBAC</div>
                <div class="text-[#0071e3] font-medium">Sanctum + Spatie Permission</div>
                <div class="text-[#1d1d1f]/60">Passport / Custom Middleware</div>
            </div>
        </div>

        <!-- Summary Analysis -->
        <div class="bg-white border border-black/5 rounded-[24px] shadow-sm p-8 sm:p-10 space-y-4">
            <h2 class="text-xl font-bold text-[#1d1d1f] font-sans">Our Architectural Verdict</h2>
            <p class="text-sm text-[#1d1d1f]/70 font-sans leading-relaxed">
                For high-integrity business applications, double-entry financial accounting, and enterprise SaaS, Laravel 12 paired with Inertia.js delivers unparalleled developer velocity, hardened security, and sub-millisecond query execution.
            </p>
        </div>

        <div class="text-center pt-4">
            <a href="/start-project" class="px-8 py-3.5 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs uppercase tracking-wider rounded-[980px] shadow-sm transition-all">
                {{ __('general.start_a_project') }} ➔
            </a>
        </div>

    </div>

</div>
@endsection
