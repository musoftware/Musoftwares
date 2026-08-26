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
            year: '2021 – Present',
            role: 'Founder & Chief Software Architect',
            organization: 'Musoftware Studio',
            desc: 'Leading the engineering of multi-tenant cloud ERP engines, official Meta Graph & WhatsApp Cloud API integrations, autonomous execution runtimes, and agentic AI workflows.',
        },
        {
            year: '2026',
            role: 'FinTech & Algorithmic Trading Architect',
            organization: 'High-Frequency Trading & Custom Payment Systems',
            desc: 'Engineered sub-millisecond double-entry ledger engines, high-speed Python Sniper Bots for gold and crypto markets, and automated SMS-to-Wallet payment bridges for WordPress & WooCommerce.',
        },
        {
            year: '2021 – 2022',
            role: 'Lead Cloud & Systems Architect',
            organization: 'AmcTasks & AmcTurbo Platforms',
            desc: 'Architected the unified marketing automation cloud ecosystem combining full academy capabilities and multi-channel bots, alongside the lightweight high-velocity AmcTurbo package.',
        },
        {
            year: '2020 – 2021',
            role: 'Bots & Workflow Automation Architect',
            organization: 'AmcFounder Platform',
            desc: 'Engineered interactive visual chatbot flow builders, multi-channel automated messaging engines across Telegram, Facebook Messenger, Viber, and external webhook bridges.',
        },
        {
            year: '2018 – 2020',
            role: 'SaaS & Platform Architect',
            organization: 'AMC Academy Platform',
            desc: 'Built comprehensive CodeIgniter SaaS platform featuring automated Android APK builders, real-time push notification managers, custom automation script runners, and audience search engines.',
        },
        {
            year: '2016 – 2018',
            role: 'Multi-Platform Desktop & RPA Lead',
            organization: 'AM Internet Control',
            desc: 'Developed multi-threaded cross-platform desktop automation and scraping suite across Facebook, Instagram, YouTube, and digital media channels with resilient session managers.',
        },
        {
            year: '2014 – 2016',
            role: 'Core Automation & Reverse Engineering Developer',
            organization: 'Facebook Control Project',
            desc: 'Engineered full Facebook API scrapers, automated authentication and login handlers, and high-concurrency data streaming pipelines handling large-scale continuous extraction.',
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

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge="Leadership & Engineering Direction"
                    title={
                        <>
                            Mahmoud Amin <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">Founder &amp; Chief Software Architect</span>
                        </>
                    }
                    subtitle="10+ years architecting mission-critical ERP platforms, real-time financial systems, and high-throughput Meta API integrations."
                />

                {/* Direct Action Bar */}
                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 px-6">
                    <button 
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to book an executive architectural consultation regarding my project.")}
                        className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer flex items-center gap-2"
                    >
                        <MessageSquare className="w-4 h-4 text-white" />
                        <span>Consult Mahmoud Directly</span>
                    </button>
                    <Link href="/portfolio">
                        <button className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm cursor-pointer">
                            View Shipped Case Studies ➔
                        </button>
                    </Link>
                </div>

                <div className="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-24">
                    
                    {/* Executive Bio Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                        
                        {/* Profile Summary Card (4 cols) */}
                        <div className="lg:col-span-4 bg-white border border-black/5 rounded-[24px] p-8 space-y-6 shadow-sm">
                            <div className="w-24 h-24 rounded-2xl bg-[#0071e3]/10 border border-[#0071e3]/20 flex items-center justify-center text-3xl font-bold text-[#0071e3]">
                                MA
                            </div>
                            
                            <div className="space-y-1">
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">Mahmoud Amin</h3>
                                <p className="text-xs text-[#0071e3] font-semibold uppercase tracking-wider">Chief Software Architect</p>
                                <p className="text-xs text-[#1d1d1f]/60 font-sans">Suez, Egypt • Worldwide Delivery</p>
                            </div>

                            <div className="pt-4 border-t border-black/5 space-y-3 text-xs text-[#1d1d1f]/80">
                                <div className="flex justify-between">
                                    <span className="text-[#1d1d1f]/50 font-medium">Experience:</span>
                                    <span className="font-semibold">10+ Years</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-[#1d1d1f]/50 font-medium">Platforms Shipped:</span>
                                    <span className="font-semibold">30+ Production Systems</span>
                                </div>
                                <div className="space-y-1.5 pt-1">
                                    <span className="text-[#1d1d1f]/50 block font-medium">Core Architectural Domains:</span>
                                    <div className="flex flex-wrap gap-1.5 pt-1 text-[11px]">
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">Cloud SaaS Platforms</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">Desktop Applications (.NET)</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">WhatsApp Business Solutions</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">Meta Graph Cloud APIs</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">Enterprise ERP & Accounting</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">Point of Sale (POS) Systems</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">FinTech & Exchange Engines</span>
                                        <span className="px-2.5 py-1 bg-[#f5f5f7] border border-black/5 rounded-full text-[#1d1d1f]/80 font-medium">RPA & Data Automation</span>
                                    </div>
                                </div>
                                <div className="flex justify-between pt-2 border-t border-black/5">
                                    <span className="text-[#1d1d1f]/50 font-medium">Studio:</span>
                                    <span className="text-[#0071e3] font-semibold">Musoftwares</span>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-black/5 space-y-2">
                                <a 
                                    href="https://wa.me/201015218548" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    className="w-full flex items-center justify-center gap-2 py-3 rounded-[980px] bg-[#25D366] hover:bg-[#20ba59] text-white font-semibold text-xs transition-colors shadow-md shadow-green-500/20"
                                >
                                    <span>Message on WhatsApp</span>
                                </a>

                                <div className="grid grid-cols-2 gap-2 pt-2 text-xs text-center">
                                    <a 
                                        href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-black/10 bg-[#f5f5f7] hover:bg-white rounded-xl text-[#1d1d1f]/80 hover:text-[#1d1d1f] font-medium transition-colors"
                                    >
                                        LinkedIn ↗
                                    </a>
                                    <a 
                                        href="https://github.com/musoftware" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-black/10 bg-[#f5f5f7] hover:bg-white rounded-xl text-[#1d1d1f]/80 hover:text-[#1d1d1f] font-medium transition-colors"
                                    >
                                        GitHub ↗
                                    </a>
                                    <a 
                                        href="https://x.com/MusoftwareUno" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-black/10 bg-[#f5f5f7] hover:bg-white rounded-xl text-[#1d1d1f]/80 hover:text-[#1d1d1f] font-medium transition-colors"
                                    >
                                        X (Twitter) ↗
                                    </a>
                                    <a 
                                        href="https://www.facebook.com/musoftwares.com.page/" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="p-2 border border-black/10 bg-[#f5f5f7] hover:bg-white rounded-xl text-[#1d1d1f]/80 hover:text-[#1d1d1f] font-medium transition-colors"
                                    >
                                        Facebook ↗
                                    </a>
                                </div>
                            </div>
                        </div>

                        {/* Bio Narrative (8 cols) */}
                        <div className="lg:col-span-8 space-y-8">
                            <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 space-y-6 shadow-sm">
                                <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                    Architectural Statement
                                </span>
                                <h3 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                    "We engineer software as durable infrastructure, not disposable consumer prototypes."
                                </h3>
                                
                                <div className="space-y-4 text-sm sm:text-base text-[#1d1d1f]/70 font-sans leading-relaxed">
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
                                <h4 className="text-xs uppercase tracking-wider text-[#1d1d1f]/60 font-semibold">
                                    Technical Domains of Authority
                                </h4>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {coreCompetencies.map((comp, idx) => {
                                        const IconComp = comp.icon;
                                        return (
                                            <div key={idx} className="bg-white border border-black/5 rounded-[20px] p-5 space-y-2 shadow-sm hover:shadow-md transition-all">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 rounded-xl bg-[#0071e3]/10 text-[#0071e3]">
                                                        <IconComp className="h-4 w-4" />
                                                    </div>
                                                    <h5 className="font-semibold text-[#1d1d1f] text-sm">{comp.title}</h5>
                                                </div>
                                                <p className="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
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
                            <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                Proven Track Record
                            </span>
                            <h3 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                Engineering Journey &amp; Milestones
                            </h3>
                        </div>

                        <div className="grid grid-cols-1 gap-4">
                            {milestones.map((m, idx) => (
                                <div key={idx} className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 flex flex-col sm:flex-row items-start gap-6 shadow-sm">
                                    <div className="sm:w-32 shrink-0">
                                        <span className="text-xl font-bold text-[#0071e3]">{m.year}</span>
                                    </div>
                                    <div className="space-y-1 flex-1">
                                        <h4 className="text-base font-semibold text-[#1d1d1f]">{m.role}</h4>
                                        <div className="text-xs text-[#1d1d1f]/50 font-medium">{m.organization}</div>
                                        <p className="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans leading-relaxed pt-2">
                                            {m.desc}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Bottom Consultation Banner */}
                    <div className="bg-[#f5f5f7] p-8 sm:p-12 border border-black/5 rounded-[28px] text-center space-y-6">
                        <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold block">
                            Direct Architecture Engagement
                        </span>
                        <h3 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                            Have a Complex System That Demands Senior Architecture?
                        </h3>
                        <p className="text-sm text-[#1d1d1f]/60 max-w-2xl mx-auto font-sans leading-relaxed">
                            Connect directly with Mahmoud Amin to review your system schema, API contracts, or high-throughput requirements.
                        </p>
                        <div className="pt-2 flex items-center justify-center gap-4 flex-wrap text-xs">
                            <button 
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to book an architecture review for my enterprise system.")}
                                className="px-8 py-3.5 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold tracking-wide transition-colors cursor-pointer shadow-md shadow-blue-500/20"
                            >
                                Discuss on WhatsApp Directly
                            </button>
                            <Link href="/company/contact">
                                <button className="px-8 py-3.5 rounded-[980px] bg-white hover:bg-[#e5e5ea] text-[#1d1d1f] border border-black/10 font-semibold tracking-wide transition-colors cursor-pointer shadow-sm">
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
