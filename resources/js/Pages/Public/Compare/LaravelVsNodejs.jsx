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
            color: 'border-[#748660] bg-[#1A2215]',
        },
        {
            title: 'When to Choose Node.js / Fastify',
            points: [
                'Real-time IoT Telemetry and Sensor Ingestion Hubs',
                'High-Concurrency Edge Web Scrapers & Browser Automation',
                'Lightweight micro-gateways routing raw binary packets',
                'Pure GraphQL streaming proxy layers',
            ],
            color: 'border-[#2B2B2B] bg-black',
        },
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Laravel vs Node.js: 2026 Enterprise Architecture Benchmark | Musoftwares</title>
                <meta name="description" content="Technical comparison and performance benchmark: Laravel 12 + Inertia vs Node.js for Enterprise ERP, SaaS platforms, and real-time APIs." />
            </Head>

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Tech Architecture & Benchmarks"
                    title={
                        <>
                            Laravel vs Node.js <br className="hidden sm:inline" />
                            <span className="text-[#748660]">Enterprise Architecture Benchmark</span>
                        </>
                    }
                    subtitle="A data-driven engineering comparison between monolithic Laravel 12 + Inertia vs Node.js microservices for mission-critical business platforms."
                />

                {/* Quick Actions */}
                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 px-6">
                    <Link href="/estimator">
                        <button className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all cursor-pointer">
                            Calculate Project Scope ➔
                        </button>
                    </Link>
                    <button 
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to consult on selecting the right tech stack for my enterprise project (Laravel vs Node.js).")}
                        className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all cursor-pointer flex items-center gap-2"
                    >
                        <MessageSquare className="w-4 h-4 text-[#748660]" />
                        <span>Consult Lead Architect</span>
                    </button>
                </div>

                <div className="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-20">
                    
                    {/* Executive Summary Card */}
                    <div className="p-8 sm:p-12 bg-[#161616] border border-[#2B2B2B] relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-96 h-96 bg-[#748660]/10 rounded-full blur-3xl pointer-events-none" />
                        <div className="relative z-10 space-y-4 max-w-4xl">
                            <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                                Executive Summary
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                                The Modern "Majestic Monolith" Outperforms Fragmented Microservices
                            </h2>
                            <p className="text-sm text-zinc-300 leading-relaxed font-sans">
                                In 2026, building enterprise SaaS with a modern <strong className="text-white">Laravel 12 + Inertia.js + PostgreSQL</strong> stack delivers up to <strong className="text-[#748660]">60% faster development cycles</strong> and zero schema-drift bugs compared to disjointed Node.js microservices. Node.js excels at raw async network streaming, but Laravel dominates in business logic, financial ledgers, and operational durability.
                            </p>
                        </div>
                    </div>

                    {/* Detailed Comparison Matrix */}
                    <div className="space-y-8">
                        <div className="text-center max-w-3xl mx-auto space-y-2">
                            <span className="text-xs font-mono uppercase tracking-widest text-[#748660] font-bold">
                                Architectural Showdown
                            </span>
                            <h3 className="text-2xl sm:text-4xl font-bold text-white tracking-tight">
                                Head-to-Head Technical Matrix
                            </h3>
                        </div>

                        <div className="grid grid-cols-1 gap-6">
                            {comparisonMetrics.map((metric, idx) => {
                                const IconComp = metric.icon;
                                return (
                                    <div key={idx} className="bg-[#161616] border border-[#2B2B2B] p-6 sm:p-8 space-y-6">
                                        <div className="flex items-center gap-3 border-b border-[#222222] pb-4">
                                            <div className="p-2.5 bg-black border border-[#2B2B2B] text-[#748660]">
                                                <IconComp className="h-5 w-5" />
                                            </div>
                                            <h4 className="font-bold text-lg text-white font-sans">
                                                {metric.category}
                                            </h4>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* Laravel Column */}
                                            <div className="p-5 bg-black/60 border border-[#748660]/30 space-y-2 font-mono text-xs">
                                                <div className="flex items-center justify-between text-white font-bold text-sm">
                                                    <span className="text-[#748660] flex items-center gap-1.5">
                                                        <CheckCircle2 className="h-4 w-4" />
                                                        Laravel 12 Architecture
                                                    </span>
                                                    <span className="text-[10px] uppercase px-2 py-0.5 bg-[#1E2619] text-[#748660] border border-[#748660]/40">
                                                        Recommended
                                                    </span>
                                                </div>
                                                <div className="font-bold text-zinc-200">{metric.laravel.title}</div>
                                                <p className="text-zinc-400 font-sans text-xs leading-relaxed">
                                                    {metric.laravel.desc}
                                                </p>
                                            </div>

                                            {/* Node.js Column */}
                                            <div className="p-5 bg-black/60 border border-[#2B2B2B] space-y-2 font-mono text-xs">
                                                <div className="flex items-center justify-between text-zinc-300 font-bold text-sm">
                                                    <span className="text-zinc-400 flex items-center gap-1.5">
                                                        <Server className="h-4 w-4" />
                                                        Node.js / Express Stack
                                                    </span>
                                                    <span className="text-[10px] uppercase px-2 py-0.5 bg-[#1C1C1C] text-zinc-400 border border-[#333333]">
                                                        Specialized
                                                    </span>
                                                </div>
                                                <div className="font-bold text-zinc-300">{metric.nodejs.title}</div>
                                                <p className="text-zinc-400 font-sans text-xs leading-relaxed">
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
                            <div key={i} className={`p-8 border ${verdict.color} space-y-6`}>
                                <h4 className="text-xl font-bold text-white font-sans flex items-center gap-2">
                                    <Sparkles className="w-5 h-5 text-[#748660]" />
                                    {verdict.title}
                                </h4>
                                <ul className="space-y-3 font-sans text-sm text-zinc-300">
                                    {verdict.points.map((pt, idx) => (
                                        <li key={idx} className="flex items-start gap-2.5">
                                            <CheckCircle2 className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                            <span>{pt}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>

                    {/* Studio Call To Action */}
                    <div className="bg-[#161616] p-8 sm:p-12 border border-[#2B2B2B] text-center space-y-6">
                        <span className="text-xs font-mono uppercase tracking-widest text-[#748660] font-bold block">
                            Our Studio Advantage
                        </span>
                        <h3 className="text-2xl sm:text-4xl font-bold text-white tracking-tight">
                            We Engineer Both Stacks with Production Mastery
                        </h3>
                        <p className="text-sm text-zinc-400 max-w-2xl mx-auto font-sans leading-relaxed">
                            Musoftwares engineers multi-tenant SaaS platforms, real-time Meta bots, and high-performance desktop tools tailored precisely to your operational requirements.
                        </p>
                        <div className="pt-2 flex items-center justify-center gap-4 flex-wrap font-mono text-xs">
                            <Link href="/estimator">
                                <button className="px-8 py-3.5 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-wider transition-colors cursor-pointer">
                                    Launch Architecture Estimator
                                </button>
                            </Link>
                            <Link href="/portfolio">
                                <button className="px-8 py-3.5 bg-black hover:bg-[#222222] text-zinc-300 hover:text-white border border-[#333333] font-bold uppercase tracking-wider transition-colors cursor-pointer">
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
