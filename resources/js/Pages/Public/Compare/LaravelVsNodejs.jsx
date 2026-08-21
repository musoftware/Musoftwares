import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    CheckCircle2, 
    XCircle, 
    Zap, 
    Shield, 
    Database, 
    Server, 
    Cpu, 
    Layers, 
    ArrowRight, 
    MessageSquare,
    Calculator,
    Sparkles,
    Scale
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { openWhatsAppChat } from '@/lib/whatsapp';
import StudioHeader from '@/Components/Studio/StudioHeader';

export default function LaravelVsNodejs() {
    const comparisonMetrics = [
        {
            category: 'Financial Ledgers & Data Integrity',
            icon: Database,
            laravel: {
                title: 'ACID-Compliant Monolith (Superior)',
                desc: 'Native database transactions, BCMath arbitrary precision, and Eloquent state machines guarantee zero fractional currency loss and absolute ledger consistency.',
                status: 'winner',
            },
            nodejs: {
                title: 'Distributed Transaction Complexity',
                desc: 'Prone to floating-point rounding issues unless custom decimal libraries are enforced. Requires 2PC (Two-Phase Commit) or Saga patterns across microservices.',
                status: 'draw',
            },
        },
        {
            category: 'Real-Time & High-Throughput WebSockets',
            icon: Zap,
            laravel: {
                title: 'Laravel Reverb & Event Broadcasting',
                desc: 'Sub-millisecond real-time event broadcasting native to Laravel 12. Handles 100k+ concurrent connections with zero node process orchestration overhead.',
                status: 'winner',
            },
            nodejs: {
                title: 'Native Event-Driven Async I/O',
                desc: 'Exceptional I/O concurrency on the V8 engine using Fastify/uWebSockets.js. Ideal for raw telemetry, streaming chats, and IoT sensor ingest.',
                status: 'winner',
            },
        },
        {
            category: 'Queue Workflows & Background Workers',
            icon: Cpu,
            laravel: {
                title: 'Laravel Horizon & Native Queues',
                desc: 'Built-in Redis job batching, retries, exponential backoff, rate limiting, and real-time dashboard observability out of the box with zero third-party boilerplates.',
                status: 'winner',
            },
            nodejs: {
                title: 'BullMQ & Custom Micro-Workers',
                desc: 'Requires separate worker cluster setups (BullMQ, Redis, PM2). Highly performant but demands significant devops maintenance and glue code.',
                status: 'draw',
            },
        },
        {
            category: 'Security, Auth & Compliance',
            icon: Shield,
            laravel: {
                title: 'Hardened Tri-Path Defense',
                desc: 'Strict CSRF protection, AES-256 session encryption, Spatie RBAC permissions, Sanctum API tokens, and automatic SQL injection sanitization.',
                status: 'winner',
            },
            nodejs: {
                title: 'Fragmented Package Ecosystem',
                desc: 'Relies on disparate npm packages (Passport, Express-Validator, Helmet, CORS) which introduces supply-chain security risks and maintenance overhead.',
                status: 'draw',
            },
        },
        {
            category: 'Time-to-Market & TCO (Total Cost of Ownership)',
            icon: Scale,
            laravel: {
                title: '60% Faster Delivery Time',
                desc: 'Inertia.js bridges modern React directly to server models without maintaining double API schemas, saving hundreds of engineering hours.',
                status: 'winner',
            },
            nodejs: {
                title: 'Higher Architecture Overhead',
                desc: 'Requires duplicate TypeScript type declarations, redundant API endpoints, and orchestrating multiple front/back repositories.',
                status: 'draw',
            },
        },
    ];

    const verdictHighlights = [
        {
            title: 'When to Choose Laravel 12',
            points: [
                'Enterprise ERP, CRM, and Multi-Branch POS Systems',
                'Multi-Currency Financial Ledgers and Double-Entry Accounting',
                'Business-Critical SaaS with strict RBAC and billing flows',
                'Fast execution speed with full React UI fidelity (Inertia.js)',
            ],
            color: 'border-[#0071e3]/30 bg-[#0071e3]/5',
        },
        {
            title: 'When to Choose Node.js / Fastify',
            points: [
                'Real-time IoT Telemetry and Sensor Ingestion Hubs',
                'High-Concurrency Edge Web Scrapers & Browser Automation',
                'Lightweight micro-gateways routing raw binary packets',
                'Pure GraphQL streaming proxy layers',
            ],
            color: 'border-black/10 bg-[#f5f5f7]',
        },
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Laravel vs Node.js: 2026 Enterprise Architecture Benchmark | Musoftwares</title>
                <meta name="description" content="Technical comparison and performance benchmark: Laravel 12 + Inertia vs Node.js for Enterprise ERP, SaaS platforms, and real-time APIs." />
            </Head>

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Tech Architecture & Benchmarks"
                    title={
                        <>
                            Laravel vs Node.js <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">Enterprise Architecture Benchmark</span>
                        </>
                    }
                    subtitle="A data-driven engineering comparison between monolithic Laravel 12 + Inertia vs Node.js microservices for mission-critical business platforms."
                />

                {/* Quick Actions */}
                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 px-6">
                    <Link href="/estimator">
                        <button className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer">
                            Calculate Project Scope ➔
                        </button>
                    </Link>
                    <button 
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to consult on selecting the right tech stack for my enterprise project (Laravel vs Node.js).")}
                        className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm cursor-pointer flex items-center gap-2"
                    >
                        <MessageSquare className="w-4 h-4 text-[#0071e3]" />
                        <span>Consult Lead Architect</span>
                    </button>
                </div>

                <div className="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-20">
                    
                    {/* Executive Summary Card */}
                    <div className="p-8 sm:p-12 bg-white border border-black/5 rounded-[24px] shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-96 h-96 bg-[#0071e3]/5 rounded-full blur-3xl pointer-events-none" />
                        <div className="relative z-10 space-y-4 max-w-4xl">
                            <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                Executive Summary
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                The Modern "Majestic Monolith" Outperforms Fragmented Microservices
                            </h2>
                            <p className="text-sm sm:text-base text-[#1d1d1f]/70 leading-relaxed font-sans">
                                In 2026, building enterprise SaaS with a modern <strong className="text-[#1d1d1f]">Laravel 12 + Inertia.js + PostgreSQL</strong> stack delivers up to <strong className="text-[#0071e3]">60% faster development cycles</strong> and zero schema-drift bugs compared to disjointed Node.js microservices. Node.js excels at raw async network streaming, but Laravel dominates in business logic, financial ledgers, and operational durability.
                            </p>
                        </div>
                    </div>

                    {/* Detailed Comparison Matrix */}
                    <div className="space-y-8">
                        <div className="text-center max-w-3xl mx-auto space-y-2">
                            <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                Architectural Showdown
                            </span>
                            <h3 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                Head-to-Head Technical Matrix
                            </h3>
                        </div>

                        <div className="grid grid-cols-1 gap-6">
                            {comparisonMetrics.map((metric, idx) => {
                                const IconComp = metric.icon;
                                return (
                                    <div key={idx} className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 space-y-6 shadow-sm">
                                        <div className="flex items-center gap-3 border-b border-black/5 pb-4">
                                            <div className="p-2.5 rounded-xl bg-[#0071e3]/10 text-[#0071e3]">
                                                <IconComp className="h-5 w-5" />
                                            </div>
                                            <h4 className="font-semibold text-lg text-[#1d1d1f] font-sans">
                                                {metric.category}
                                            </h4>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* Laravel Column */}
                                            <div className="p-5 rounded-[20px] bg-[#0071e3]/5 border border-[#0071e3]/20 space-y-2 text-xs">
                                                <div className="flex items-center justify-between text-[#1d1d1f] font-semibold text-sm">
                                                    <span className="text-[#0071e3] flex items-center gap-1.5">
                                                        <CheckCircle2 className="h-4 w-4" />
                                                        Laravel 12 Architecture
                                                    </span>
                                                    <span className="text-[10px] uppercase px-2.5 py-0.5 rounded-full bg-[#0071e3]/10 text-[#0071e3] font-semibold">
                                                        Recommended
                                                    </span>
                                                </div>
                                                <div className="font-semibold text-[#1d1d1f]">{metric.laravel.title}</div>
                                                <p className="text-[#1d1d1f]/70 font-sans text-xs leading-relaxed">
                                                    {metric.laravel.desc}
                                                </p>
                                            </div>

                                            {/* Node.js Column */}
                                            <div className="p-5 rounded-[20px] bg-[#f5f5f7] border border-black/5 space-y-2 text-xs">
                                                <div className="flex items-center justify-between text-[#1d1d1f] font-semibold text-sm">
                                                    <span className="text-[#1d1d1f]/70 flex items-center gap-1.5">
                                                        <Server className="h-4 w-4" />
                                                        Node.js / Express Stack
                                                    </span>
                                                    <span className="text-[10px] uppercase px-2.5 py-0.5 rounded-full bg-black/5 text-[#1d1d1f]/60 font-semibold">
                                                        Specialized
                                                    </span>
                                                </div>
                                                <div className="font-semibold text-[#1d1d1f]/80">{metric.nodejs.title}</div>
                                                <p className="text-[#1d1d1f]/60 font-sans text-xs leading-relaxed">
                                                    {metric.nodejs.desc}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Verdict Columns */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {verdictHighlights.map((verdict, i) => (
                            <div key={i} className={`p-8 rounded-[24px] border ${verdict.color} space-y-6 shadow-sm`}>
                                <h4 className="text-xl font-semibold text-[#1d1d1f] font-sans flex items-center gap-2">
                                    <Sparkles className="w-5 h-5 text-[#0071e3]" />
                                    {verdict.title}
                                </h4>
                                <ul className="space-y-3 font-sans text-sm text-[#1d1d1f]/80">
                                    {verdict.points.map((pt, idx) => (
                                        <li key={idx} className="flex items-start gap-2.5">
                                            <CheckCircle2 className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                            <span>{pt}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>

                    {/* Studio Call To Action */}
                    <div className="bg-[#f5f5f7] p-8 sm:p-12 border border-black/5 rounded-[28px] text-center space-y-6">
                        <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold block">
                            Our Studio Advantage
                        </span>
                        <h3 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                            We Engineer Both Stacks with Production Mastery
                        </h3>
                        <p className="text-sm text-[#1d1d1f]/60 max-w-2xl mx-auto font-sans leading-relaxed">
                            Musoftwares engineers multi-tenant SaaS platforms, real-time Meta bots, and high-performance desktop tools tailored precisely to your operational requirements.
                        </p>
                        <div className="pt-2 flex items-center justify-center gap-4 flex-wrap text-xs">
                            <Link href="/estimator">
                                <button className="px-8 py-3.5 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold tracking-wide transition-colors cursor-pointer shadow-md shadow-blue-500/20">
                                    Launch Architecture Estimator
                                </button>
                            </Link>
                            <Link href="/portfolio">
                                <button className="px-8 py-3.5 rounded-[980px] bg-white hover:bg-[#e5e5ea] text-[#1d1d1f] border border-black/10 font-semibold tracking-wide transition-colors cursor-pointer shadow-sm">
                                    Explore Studio Case Studies
                                </button>
                            </Link>
                        </div>
                    </div>

                </div>

            </div>
        </PublicLayout>
    );
}
