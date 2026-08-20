import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    ShieldCheck, 
    Cpu, 
    Database, 
    Globe, 
    Code2, 
    Zap, 
    ArrowRight, 
    MessageSquare, 
    CheckCircle2, 
    Award, 
    Terminal, 
    Layers,
    Share2
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { openWhatsAppChat } from '@/lib/whatsapp';
import StudioHeader from '@/Components/Studio/StudioHeader';

export default function LeadershipBio() {
    const milestones = [
        {
            year: '2026',
            role: 'Founder & Chief Software Architect',
            organization: 'Musoftwares Studio',
            desc: 'Leading engineering for next-generation multi-tenant cloud ERP engines, Meta API cloud integrations, and the autonomous runtime ecosystem.',
        },
        {
            year: '2023 - 2025',
            role: 'Enterprise Systems Architect',
            organization: 'High-Scale SaaS & FinTech',
            desc: 'Engineered sub-millisecond double-entry ledger engines, real-time gold trading terminals, and distributed WhatsApp multi-agent dispatchers handling 1M+ daily transactions.',
        },
        {
            year: '2019 - 2022',
            role: 'Senior Full-Stack & Desktop Engineer',
            organization: 'Bespoke Business Solutions',
            desc: 'Developed 20+ specialized POS systems, custom hardware thermal printer synchronizers, and cross-platform desktop scrapers in C# and Laravel.',
        },
        {
            year: '2015 - 2018',
            role: 'Core Systems Developer',
            organization: 'Enterprise IT & Accounting Software',
            desc: 'Architected offline-first relational databases, tax-compliant invoice engines (ETA/ZATCA), and inventory tracking platforms.',
        },
    ];

    const coreCompetencies = [
        {
            title: 'Multi-Tenant Cloud SaaS Platforms',
            icon: Layers,
            desc: 'Isolated tenant schemas, automated billing & wallet credits, white-label custom domains, and zero-friction client onboarding.',
        },
        {
            title: 'Enterprise ERP & Financial Ledgers',
            icon: Database,
            desc: 'Double-entry accounting kernels, multi-currency ledger conversions, tax compliance (ZATCA / ETA), and immutable audit logs.',
        },
        {
            title: 'Meta Graph & WhatsApp Cloud API',
            icon: Zap,
            desc: 'High-throughput webhook consumers, automated OTP dispatching, multi-agent CRM routing, and 24/7 business communication bots.',
        },
        {
            title: 'Desktop Software, RPA & Native Tools',
            icon: Terminal,
            desc: 'High-performance C# / .NET Windows utilities, offline-first SQLite/SQL Server engines, thermal POS drivers, and B2B web scrapers.',
        },
        {
            title: 'Point of Sale (POS) & FinTech Engines',
            icon: Share2,
            desc: 'Real-time WebSocket market streaming, gold/commodity exchanges, barcode and receipt printer synchronizers, and edge resilience.',
        },
        {
            title: 'Cloud Infrastructure & High-Availability',
            icon: Cpu,
            desc: 'Dockerized microservices, PostgreSQL/MySQL query optimization, Redis queue pipelines, and zero-downtime deployments.',
        },
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Mahmoud Amin — Founder & Chief Software Architect | Musoftwares</title>
                <meta name="description" content="Biography and engineering profile of Mahmoud Amin, Founder and Chief Software Architect at Musoftwares." />
            </Head>

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Leadership & Engineering Direction"
                    title={
                        <>
                            Mahmoud Amin <br className="hidden sm:inline" />
                            <span className="text-[#748660]">Founder &amp; Chief Software Architect</span>
                        </>
                    }
                    subtitle="10+ years architecting mission-critical ERP platforms, real-time financial systems, and high-throughput Meta API integrations."
                />

                {/* Direct Action Bar */}
                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 px-6">
                    <button 
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to book an executive architectural consultation regarding my project.")}
                        className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all cursor-pointer flex items-center gap-2"
                    >
                        <MessageSquare className="w-4 h-4 text-[#748660]" />
                        <span>Consult Mahmoud Directly</span>
                    </button>
                    <Link href="/portfolio">
                        <button className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all cursor-pointer">
                            View Shipped Case Studies ➔
                        </button>
                    </Link>
                </div>

                <div className="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
                    
                    {/* Executive Bio Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                        
                        {/* Profile Summary Card (4 cols) */}
                        <div className="lg:col-span-4 bg-[#161616] border border-[#2B2B2B] p-8 space-y-6">
                            <div className="w-24 h-24 bg-[#1E2619] border-2 border-[#748660] flex items-center justify-center font-mono text-3xl font-black text-[#748660]">
                                MA
                            </div>
                            
                            <div className="space-y-1">
                                <h3 className="text-xl font-bold text-white font-sans">Mahmoud Amin</h3>
                                <p className="text-xs font-mono text-[#748660] uppercase tracking-wider">Chief Software Architect</p>
                                <p className="text-xs text-zinc-400 font-sans">Suez, Egypt • Worldwide Delivery</p>
                            </div>

                            <div className="pt-4 border-t border-[#222222] space-y-3 text-xs font-mono text-zinc-300">
                                <div className="flex justify-between">
                                    <span className="text-zinc-500">Experience:</span>
                                    <span>10+ Years</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-zinc-500">Platforms Shipped:</span>
                                    <span>30+ Production Systems</span>
                                </div>
                                <div className="space-y-1.5 pt-1">
                                    <span className="text-zinc-500 block">Core Architectural Domains:</span>
                                    <div className="flex flex-wrap gap-1.5 pt-1 text-[10px]">
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Cloud SaaS Platforms</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Desktop Applications (.NET)</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">WhatsApp Business Solutions</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Meta Graph Cloud APIs</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Enterprise ERP & Accounting</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">Point of Sale (POS) Systems</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">FinTech & Exchange Engines</span>
                                        <span className="px-2 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#A2B889] font-bold">RPA & Data Automation</span>
                                    </div>
                                </div>
                                <div className="flex justify-between pt-1 border-t border-[#1C1C1C]">
                                    <span className="text-zinc-500">Studio:</span>
                                    <span className="text-[#748660] font-bold">Musoftwares</span>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-[#222222] space-y-2">
                                <a 
                                    href="https://wa.me/201015218548" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    className="w-full flex items-center justify-center gap-2 py-3 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-wider transition-colors"
                                >
                                    <span>Message on WhatsApp</span>
                                </a>

                                <div className="grid grid-cols-2 gap-2 pt-2 text-[11px] font-mono text-center">
                                    <a 
                                        href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors"
                                    >
                                        LinkedIn ↗
                                    </a>
                                    <a 
                                        href="https://github.com/musoftware" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors"
                                    >
                                        GitHub ↗
                                    </a>
                                    <a 
                                        href="https://x.com/MusoftwareUno" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors"
                                    >
                                        X (Twitter) ↗
                                    </a>
                                    <a 
                                        href="https://www.facebook.com/musoftwares.com.page/" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-[#2B2B2B] bg-black hover:border-zinc-500 text-zinc-300 hover:text-white transition-colors"
                                    >
                                        Facebook ↗
                                    </a>
                                </div>
                            </div>
                        </div>

                        {/* Bio Narrative (8 cols) */}
                        <div className="lg:col-span-8 space-y-8">
                            <div className="bg-[#161616] border border-[#2B2B2B] p-8 sm:p-10 space-y-6">
                                <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                                    Architectural Statement
                                </span>
                                <h3 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                                    "We engineer software as durable infrastructure, not disposable consumer prototypes."
                                </h3>
                                
                                <div className="space-y-4 text-sm text-zinc-300 font-sans leading-relaxed">
                                    <p>
                                        Mahmoud Amin is the founder and lead architect behind Musoftwares. Over the past decade, he has engineered and delivered enterprise-grade applications ranging from multi-branch ERP ledgers and automated commodity exchanges to verified Meta Graph API engines.
                                    </p>
                                    <p>
                                        His engineering philosophy centers around architectural minimalism, strict data sovereignty, sub-second latency, and total code ownership for enterprise clients.
                                    </p>
                                </div>
                            </div>

                            {/* Core Competencies Grid */}
                            <div className="space-y-4">
                                <h4 className="text-xs font-mono uppercase tracking-wider text-zinc-400 font-bold">
                                    Technical Domains of Authority
                                </h4>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {coreCompetencies.map((comp, idx) => {
                                        const IconComp = comp.icon;
                                        return (
                                            <div key={idx} className="bg-[#161616] border border-[#2B2B2B] p-5 space-y-2 font-mono">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-black border border-[#2B2B2B] text-[#748660]">
                                                        <IconComp className="h-4 w-4" />
                                                    </div>
                                                    <h5 className="font-bold text-white text-xs">{comp.title}</h5>
                                                </div>
                                                <p className="text-[11px] text-zinc-400 font-sans leading-relaxed">
                                                    {comp.desc}
                                                </p>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                    </div>

                    {/* Timeline Milestones */}
                    <div className="space-y-8">
                        <div className="text-center max-w-3xl mx-auto space-y-2">
                            <span className="text-xs font-mono uppercase tracking-widest text-[#748660] font-bold">
                                Proven Track Record
                            </span>
                            <h3 className="text-2xl sm:text-4xl font-bold text-white tracking-tight">
                                Engineering Journey &amp; Milestones
                            </h3>
                        </div>

                        <div className="grid grid-cols-1 gap-4 font-mono">
                            {milestones.map((m, idx) => (
                                <div key={idx} className="bg-[#161616] border border-[#2B2B2B] p-6 sm:p-8 flex flex-col sm:flex-row items-start gap-6">
                                    <div className="sm:w-32 shrink-0">
                                        <span className="text-lg font-bold text-[#748660]">{m.year}</span>
                                    </div>
                                    <div className="space-y-1 flex-1">
                                        <h4 className="text-base font-bold text-white">{m.role}</h4>
                                        <div className="text-xs text-zinc-400">{m.organization}</div>
                                        <p className="text-xs text-zinc-300 font-sans leading-relaxed pt-2">
                                            {m.desc}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Bottom Consultation Banner */}
                    <div className="bg-[#161616] p-8 sm:p-12 border border-[#2B2B2B] text-center space-y-6">
                        <span className="text-xs font-mono uppercase tracking-widest text-[#748660] font-bold block">
                            Direct Architecture Engagement
                        </span>
                        <h3 className="text-2xl sm:text-4xl font-bold text-white tracking-tight">
                            Have a Complex System That Demands Senior Architecture?
                        </h3>
                        <p className="text-sm text-zinc-400 max-w-2xl mx-auto font-sans leading-relaxed">
                            Connect directly with Mahmoud Amin to review your system schema, API contracts, or high-throughput requirements.
                        </p>
                        <div className="pt-2 flex items-center justify-center gap-4 flex-wrap font-mono text-xs">
                            <button 
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to book an architecture review for my enterprise system.")}
                                className="px-8 py-3.5 bg-[#748660] hover:bg-[#60704E] text-[#0F140A] font-bold uppercase tracking-wider transition-colors cursor-pointer"
                            >
                                Discuss on WhatsApp Directly
                            </button>
                            <Link href="/company/contact">
                                <button className="px-8 py-3.5 bg-black hover:bg-[#222222] text-zinc-300 hover:text-white border border-[#333333] font-bold uppercase tracking-wider transition-colors cursor-pointer">
                                    Submit Engineering Brief
                                </button>
                            </Link>
                        </div>
                    </div>

                </div>

            </div>
        </PublicLayout>
    );
}
