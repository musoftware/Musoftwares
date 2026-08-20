import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    ChevronLeft, ChevronRight, ArrowRight, ArrowUpRight, 
    Server, Zap, Code2, Globe2, Mail, CheckCircle2, 
    Calculator, Sparkles, Download, X 
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import ProjectEstimator from '@/Components/Estimator/ProjectEstimator';

const FEATURED_HERO_SLIDES = [
    {
        id: 'erp-core',
        category: 'Architecture Case Study',
        title: 'The Future of Cloud ERP: 2026 Core Architecture',
        description: 'Next-generation double-entry financial ledger, multi-tenant isolation, and sub-millisecond database pipelines.',
        image: '/images/hero/hero_erp.jpg',
        linkText: 'Read Case Study',
        link: '/portfolio/revflow',
        slideNum: '1 / 3'
    },
    {
        id: 'meta-cloud',
        category: 'Platform Architecture',
        title: 'High-Throughput Meta Graph & WhatsApp Cloud Engine',
        description: 'Distributed webhook dispatching and verified Meta API pipelines handling 1M+ daily transactions without dropped packets.',
        image: '/images/hero/hero_meta.jpg',
        linkText: 'Explore Platform',
        link: '/platforms/crm',
        slideNum: '2 / 3'
    },
    {
        id: 'fintech-pos',
        category: 'Marketplace Solutions',
        title: 'Commodity Exchange & Real-Time Gold POS Terminal',
        description: 'Sub-second market price streaming, hardware printer synchronizers, and offline-first edge resilience.',
        image: '/images/hero/hero_fintech.jpg',
        linkText: 'Explore Marketplace',
        link: '/marketplace/services',
        slideNum: '3 / 3'
    }
];

const GALLERY_TABS = [
    { id: 'all', label: 'All Projects' },
    { id: 'erp', label: 'Enterprise ERP & Ledgers' },
    { id: 'meta', label: 'Meta & WhatsApp Cloud' },
    { id: 'automation', label: 'Desktop RPA & Tools' },
    { id: 'fintech', label: 'FinTech & Real-Time' }
];

const GALLERY_ITEMS = [
    {
        id: 'trenz-whatscrm',
        categoryKey: 'meta',
        category: 'Meta & WhatsApp',
        badge: 'Featured Suite',
        title: 'Meta Cloud API & WhatsApp Enterprise Suite',
        description: 'Multi-agent live desk, automated webhook pipelines, and omnichannel CRM integration.',
        image: '/images/portfolio/trenz-whatscrm.png',
        link: '/portfolio/trenz-whatscrm',
        cardType: 'sage'
    },
    {
        id: 'stockmanager',
        categoryKey: 'erp',
        category: 'Enterprise ERP',
        badge: 'Production ERP',
        title: 'Multi-Branch Inventory & Specimen Tracking Platform',
        description: 'Double-entry ledger architectures, barcode synchronization, and zero-loss financial auditing.',
        image: '/images/portfolio/stockmanager.png',
        link: '/portfolio/stock-manager'
    },
    {
        id: 'qcoin',
        categoryKey: 'fintech',
        category: 'FinTech & Trading',
        badge: 'Real-Time Gold',
        title: 'Real-Time Gold & Commodity POS Terminal',
        description: 'Sub-second market price streaming, hardware printer synchronizers, and offline-first edge resilience.',
        image: '/images/portfolio/qcoin-website.jpg',
        link: '/portfolio/forex-app'
    },
    {
        id: 'emailsender',
        categoryKey: 'meta',
        category: 'High-Volume Infrastructure',
        badge: '10M+ Messages',
        title: 'Omnichannel Dispatch Pipeline: Guaranteed Delivery',
        description: 'Distributed webhook dispatching, multi-provider SMTP & Cloud API rotators, and zero-drop queues.',
        image: '/images/portfolio/emailsender.png',
        link: '/portfolio/email-sender',
        isWide: true
    },
    {
        id: 'revflow',
        categoryKey: 'erp',
        category: 'Autonomous Core',
        badge: 'Report',
        title: 'The 2026 Core: Autonomous ERP Architecture',
        description: 'Next-generation double-entry financial ledger, multi-tenant isolation, and sub-millisecond database pipelines.',
        image: '/images/portfolio/revFlow.png',
        link: '/portfolio/revflow',
        cardType: 'sage'
    },
    {
        id: 'map-extractor',
        categoryKey: 'automation',
        category: 'Desktop RPA',
        badge: 'B2B Lead Scraper',
        title: 'Google Maps B2B Lead Extractor & Parser',
        description: 'High-speed automated data scraping, business contact enrichment, and CSV/Excel batch exports.',
        image: '/images/portfolio/map-extractor.jpg',
        link: '/portfolio/map-extractor'
    },
    {
        id: 'amcacademy',
        categoryKey: 'fintech',
        category: 'E-Learning Platform',
        badge: 'DRM Protection',
        title: 'DRM Video Academy & Automated Assessment Engine',
        description: 'Custom video stream encryption, student grading pipelines, and interactive exam infrastructure.',
        image: '/images/portfolio/amcacademy.jpg',
        link: '/portfolio/amc-academy'
    },
    {
        id: 'whatsapp-sender',
        categoryKey: 'automation',
        category: 'Desktop RPA & Tools',
        badge: 'Windows .NET App',
        title: 'High-Speed Windows Dispatcher & Daemon',
        description: 'C# / .NET desktop tool with background workers, anti-ban rotations, and message templates.',
        image: '/images/portfolio/whatsapp-sender.png',
        link: '/portfolio/whatsapp-sender'
    },
    {
        id: 'minifatora',
        categoryKey: 'erp',
        category: 'Enterprise ERP',
        badge: 'E-Invoicing',
        title: 'Electronic Invoicing & Tax Compliance Suite',
        description: 'Instant QR code generation, itemized tax computations, and customer PDF export.',
        image: '/images/portfolio/minifatora.png',
        link: '/portfolio/mini-fatora'
    },
    {
        id: 'stocktalk',
        categoryKey: 'fintech',
        category: 'AI & Bot',
        badge: 'Trading Bot',
        title: 'StockTalk AI & Market Sentiment Engine',
        description: 'Conversational algorithmic analysis, automated trading notifications, and signal feeds.',
        image: '/images/portfolio/stocktalk.png',
        link: '/portfolio/stocktalk-ai'
    },
    {
        id: 'telecom-system',
        categoryKey: 'erp',
        category: 'Platform Architecture',
        badge: 'Telecom Suite',
        title: 'Distributed Telecom System & Multi-Tenant Infrastructure',
        description: 'High-concurrency subscriber balance management, transaction ledgers, and real-time routing.',
        image: '/images/portfolio/telecom-system.png',
        link: '/portfolio/telecom-system'
    },
    {
        id: 'duplicate-finder',
        categoryKey: 'automation',
        category: 'Desktop RPA & Tools',
        badge: 'C# .NET Utility',
        title: 'High-Speed Multi-Thread File Deduplicator',
        description: 'Deep checksum hashing, byte-level file comparator, and storage recovery tool.',
        image: '/images/portfolio/duplicate-finder.jpg',
        link: '/portfolio/duplicate-finder'
    }
];

export default function Home({ dbProjects = [], newsFeed = [] }) {
    const slides = newsFeed && newsFeed.length > 0 ? newsFeed : FEATURED_HERO_SLIDES;
    const [currentSlide, setCurrentSlide] = useState(0);
    const [activeGalleryTab, setActiveGalleryTab] = useState('all');

    const filteredGalleryItems = activeGalleryTab === 'all'
        ? GALLERY_ITEMS
        : GALLERY_ITEMS.filter((item) => item.categoryKey === activeGalleryTab);

    const nextSlide = () => {
        setCurrentSlide((prev) => (prev + 1) % slides.length);
    };

    const prevSlide = () => {
        setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
    };

    const slide = slides[currentSlide] || slides[0];

    return (
        <PublicLayout>
            <Head title="Musoftwares | Boutique Software Engineering Studio">
                <meta name="description" content="Musoftwares is an elite software engineering studio crafting bespoke Enterprise ERP systems, Meta API cloud integrations, and high-performance business platforms." />
            </Head>

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans antialiased selection:bg-[#748660] selection:text-white">
                
                {/* 1. HERO SHOWCASE SECTION (PANORAMIC ARTWORK + SAGE GREEN LOWER BAR - DYNAMIC NEWS ENGINE) */}
                <section id="hero" className="w-full">
                    {/* Top Panoramic Visual Artwork Container */}
                    <div className="w-full h-[380px] sm:h-[480px] lg:h-[540px] bg-black overflow-hidden relative">
                        <AnimatePresence mode="wait">
                            <motion.div
                                key={slide.id || currentSlide}
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                transition={{ duration: 0.7 }}
                                className="w-full h-full relative"
                            >
                                <img 
                                    src={slide.image} 
                                    alt={slide.title} 
                                    className="w-full h-full object-cover object-center filter brightness-[0.9] contrast-[1.05]"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/20 pointer-events-none" />
                            </motion.div>
                        </AnimatePresence>
                    </div>

                    {/* Exact Sage / Moss Olive Green Accent Bar (#748660) */}
                    <div className="w-full bg-[#748660] text-[#111111] py-6 sm:py-8 px-6 sm:px-12 border-b border-[#5E6D4E]">
                        <div className="max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                            
                            {/* Title and Category */}
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-mono uppercase tracking-[0.2em] rtl:tracking-normal text-[#28321E] font-bold">
                                        {slide.category || 'News & Architecture'}
                                    </span>
                                    {slide.badge && (
                                        <span className="text-[10px] font-mono font-bold px-2 py-0.5 bg-[#28321E]/15 border border-[#28321E]/30 uppercase text-[#141A0E]">
                                            {slide.badge}
                                        </span>
                                    )}
                                </div>
                                <h2 className="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-[#0F140A] font-sans">
                                    {slide.title}
                                </h2>
                            </div>

                            {/* Actions Right: Read Article Button + < 1/3 > Arrows */}
                            <div className="flex items-center space-x-6 rtl:space-x-reverse shrink-0">
                                <Link href={slide.link}>
                                    <button className="px-6 py-2.5 bg-transparent border border-[#0F140A] text-[#0F140A] hover:bg-[#0F140A] hover:text-[#748660] text-xs font-bold font-mono tracking-widest rtl:tracking-normal uppercase transition-colors">
                                        {slide.linkText || 'Read More ➔'}
                                    </button>
                                </Link>

                                {/* Pagination Arrows (< 1 / N >) */}
                                <div className="flex items-center space-x-3 rtl:space-x-reverse text-[#0F140A] font-mono text-sm font-bold">
                                    <button 
                                        onClick={prevSlide}
                                        aria-label="Previous Release"
                                        className="p-1 hover:opacity-60 transition-opacity cursor-pointer"
                                    >
                                        <ChevronLeft className="w-5 h-5 rtl:rotate-180" />
                                    </button>
                                    <span className="tracking-widest rtl:tracking-normal">
                                        {currentSlide + 1} / {slides.length}
                                    </span>
                                    <button 
                                        onClick={nextSlide}
                                        aria-label="Next Release"
                                        className="p-1 hover:opacity-60 transition-opacity cursor-pointer"
                                    >
                                        <ChevronRight className="w-5 h-5 rtl:rotate-180" />
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>


                {/* 2. EDITORIAL MANIFESTO INTRO */}
                <section className="py-28 sm:py-36 px-6 max-w-4xl mx-auto text-center">
                    <div className="space-y-8">
                        <p className="text-2xl sm:text-3xl md:text-4xl font-medium tracking-tight text-[#EDEDED] leading-[1.4] text-center max-w-3xl mx-auto font-sans">
                            Combining <span className="text-white font-bold">creative heart</span> with <span className="text-white font-bold">technical scale</span> and <span className="text-white font-bold">human intelligence</span>, we build meaningful software connections that resonate with users and help global businesses thrive.
                        </p>
                        
                        <p className="text-base sm:text-lg text-zinc-400 font-sans font-normal">
                            <a href="#expertise" className="underline font-bold text-white decoration-zinc-500 underline-offset-4 hover:decoration-white transition-all">
                                Discover more
                            </a>
                            {' '}or{' '}
                            <a href="#contact" className="underline font-bold text-white decoration-zinc-500 underline-offset-4 hover:decoration-white transition-all">
                                connect with us
                            </a>
                            {' '}to explore what’s possible.
                        </p>

                        {/* Reference Signature Wave Line Motif */}
                        <div className="pt-6 flex justify-center items-center">
                            <svg width="240" height="28" viewBox="0 0 240 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 14C35 14 45 4 70 14C95 24 115 4 140 14C165 24 185 14 238 14" stroke="#888888" strokeWidth="1.5" strokeLinecap="round" />
                            </svg>
                        </div>
                    </div>
                </section>


                {/* 3. OUR CORE EXPERTISE (4-PILLAR ARCHITECTURAL SHOWCASE) */}
                <section id="expertise" className="py-24 sm:py-32 px-6 relative border-y border-[#222222]">
                    <div 
                        className="absolute inset-0 opacity-[0.08] pointer-events-none" 
                        style={{
                            backgroundImage: `radial-gradient(#FFFFFF 1px, transparent 1px)`,
                            backgroundSize: '16px 16px'
                        }} 
                    />

                    <div className="max-w-[1400px] mx-auto relative z-10">
                        <div className="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                            <div>
                                <span className="text-xs font-mono uppercase tracking-[0.25em] text-[#748660] font-semibold block mb-2">
                                    Engineering Capabilities
                                </span>
                                <h3 className="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-white font-sans">
                                    Our Core Expertise
                                </h3>
                            </div>
                            <p className="text-sm text-zinc-400 font-sans max-w-lg leading-relaxed">
                                Proven production systems engineered from the ground up: from mission-critical ERP ledgers and official Meta Cloud pipelines to high-speed Windows desktop automation and real-time FinTech engines.
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                            
                            {/* Pillar 1: Enterprise ERP & Accounting */}
                            <div className="bg-[#161616] border border-[#262626] p-8 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                                <div className="space-y-5">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-mono text-[#748660] font-bold">01 / BACKBONE</span>
                                        <div className="w-2 h-2 rounded-full bg-[#748660]/40 group-hover:bg-[#748660] transition-colors" />
                                    </div>
                                    <h4 className="text-lg sm:text-xl font-bold text-white tracking-tight font-sans">
                                        Enterprise ERP &amp; Financial Ledgers
                                    </h4>
                                    <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                        Bespoke double-entry ledger engines, multi-tenant databases, multi-branch inventory with barcode sync, compliant electronic invoicing (Fatora), and zero-loss financial auditing.
                                    </p>
                                </div>
                                <div className="mt-8 pt-6 border-t border-[#262626] flex flex-wrap gap-2">
                                    {['Double-Entry Ledgers', 'Multi-Branch POS', 'E-Invoicing & Tax QR', 'Multi-Tenant SaaS'].map((tag, idx) => (
                                        <span key={idx} className="text-[10px] font-mono uppercase px-2 py-1 bg-black text-zinc-300 border border-[#333333]">
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* Pillar 2: Meta API, WhatsApp & CRM */}
                            <div className="bg-[#161616] border border-[#262626] p-8 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                                <div className="space-y-5">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-mono text-[#748660] font-bold">02 / INTEGRATION</span>
                                        <div className="w-2 h-2 rounded-full bg-[#748660]/40 group-hover:bg-[#748660] transition-colors" />
                                    </div>
                                    <h4 className="text-lg sm:text-xl font-bold text-white tracking-tight font-sans">
                                        Meta Cloud &amp; WhatsApp Automation
                                    </h4>
                                    <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                        Verified Meta Graph and WhatsApp Cloud API pipelines, multi-agent live support desks, high-throughput webhook queues, automated campaign triggers, and custom CRM systems.
                                    </p>
                                </div>
                                <div className="mt-8 pt-6 border-t border-[#262626] flex flex-wrap gap-2">
                                    {['WhatsApp Cloud API', 'Multi-Agent CRM', 'Webhook Queues', 'Omnichannel Broadcast'].map((tag, idx) => (
                                        <span key={idx} className="text-[10px] font-mono uppercase px-2 py-1 bg-black text-zinc-300 border border-[#333333]">
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* Pillar 3: Desktop RPA, Scrapers & Daemons */}
                            <div className="bg-[#161616] border border-[#262626] p-8 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                                <div className="space-y-5">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-mono text-[#748660] font-bold">03 / AUTOMATION</span>
                                        <div className="w-2 h-2 rounded-full bg-[#748660]/40 group-hover:bg-[#748660] transition-colors" />
                                    </div>
                                    <h4 className="text-lg sm:text-xl font-bold text-white tracking-tight font-sans">
                                        Desktop Apps, RPA &amp; Web Scrapers
                                    </h4>
                                    <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                        High-performance C# / .NET Windows utilities, background worker services, automated lead &amp; map extractors, bulk messaging engines (Telegram/WhatsApp/Email), and computer vision RPA.
                                    </p>
                                </div>
                                <div className="mt-8 pt-6 border-t border-[#262626] flex flex-wrap gap-2">
                                    {['C# / .NET Windows Tools', 'B2B Lead Scrapers', 'Bulk Dispatchers', 'Background Workers'].map((tag, idx) => (
                                        <span key={idx} className="text-[10px] font-mono uppercase px-2 py-1 bg-black text-zinc-300 border border-[#333333]">
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* Pillar 4: FinTech, Trading & Custom Platforms */}
                            <div className="bg-[#161616] border border-[#262626] p-8 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                                <div className="space-y-5">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-mono text-[#748660] font-bold">04 / REAL-TIME</span>
                                        <div className="w-2 h-2 rounded-full bg-[#748660]/40 group-hover:bg-[#748660] transition-colors" />
                                    </div>
                                    <h4 className="text-lg sm:text-xl font-bold text-white tracking-tight font-sans">
                                        Real-Time FinTech &amp; Custom SaaS
                                    </h4>
                                    <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                        Sub-second Gold &amp; Commodity POS exchange terminals with live price streaming, automated Forex trading bots, DRM-protected video academies, and high-concurrency custom platforms.
                                    </p>
                                </div>
                                <div className="mt-8 pt-6 border-t border-[#262626] flex flex-wrap gap-2">
                                    {['Gold & Commodity POS', 'Live Price Tickers', 'Forex Trading Bots', 'DRM Academies'].map((tag, idx) => (
                                        <span key={idx} className="text-[10px] font-mono uppercase px-2 py-1 bg-black text-zinc-300 border border-[#333333]">
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            </div>

                        </div>

                        <div className="mt-14 pt-8 border-t border-[#222222] flex items-center justify-between">
                            <a href="#work" className="text-xs font-bold font-mono tracking-widest rtl:tracking-normal uppercase text-zinc-300 hover:text-white underline underline-offset-4">
                                Explore Production Case Studies ➔
                            </a>
                            <span className="text-xs font-mono text-zinc-500 uppercase">
                                30+ Shipped Solutions
                            </span>
                        </div>
                    </div>
                </section>


                {/* 4. OUR WORK, NEWS AND INSIGHTS (TABBED GALLERY) */}
                <section id="work" className="py-24 sm:py-32 px-6 max-w-[1400px] mx-auto">
                    <div className="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                        <div>
                            <span className="text-xs font-mono uppercase tracking-[0.25em] text-[#748660] font-semibold block mb-2">
                                Curated Production Archive
                            </span>
                            <h3 className="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-white font-sans">
                                Our Work, News &amp; Insights
                            </h3>
                        </div>
                        <p className="text-sm text-zinc-400 font-sans max-w-md leading-relaxed">
                            Filter our real-world engineered platforms by domain to explore architecture blueprints, live systems, and technical case studies.
                        </p>
                    </div>

                    {/* Tab Navigation Filter Bar */}
                    <div className="flex items-center gap-2 overflow-x-auto pb-4 mb-12 border-b border-[#222222] scrollbar-none">
                        {GALLERY_TABS.map((tab) => {
                            const isActive = activeGalleryTab === tab.id;
                            const count = tab.id === 'all' 
                                ? GALLERY_ITEMS.length 
                                : GALLERY_ITEMS.filter(i => i.categoryKey === tab.id).length;

                            return (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveGalleryTab(tab.id)}
                                    className={`px-4 sm:px-5 py-2.5 text-xs font-mono font-bold tracking-wider uppercase whitespace-nowrap transition-all cursor-pointer flex items-center gap-2 ${
                                        isActive
                                            ? 'bg-[#748660] text-[#0F140A] shadow-lg shadow-[#748660]/20'
                                            : 'bg-[#161616] text-zinc-400 hover:text-white hover:bg-[#222222] border border-[#2B2B2B]'
                                    }`}
                                >
                                    <span>{tab.label}</span>
                                    <span className={`text-[10px] px-1.5 py-0.5 rounded-sm ${
                                        isActive ? 'bg-[#0F140A] text-[#748660]' : 'bg-[#111111] text-zinc-500'
                                    }`}>
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    {/* Dynamic Animated Gallery Grid */}
                    <motion.div layout className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <AnimatePresence mode="popLayout">
                            {filteredGalleryItems.map((item) => {
                                const isSage = item.cardType === 'sage';
                                
                                return (
                                    <motion.div
                                        key={item.id}
                                        layout
                                        initial={{ opacity: 0, scale: 0.96 }}
                                        animate={{ opacity: 1, scale: 1 }}
                                        exit={{ opacity: 0, scale: 0.94 }}
                                        transition={{ duration: 0.35, ease: 'easeOut' }}
                                        className={`overflow-hidden flex flex-col justify-between group transition-all duration-300 ${
                                            isSage
                                                ? 'bg-[#748660] text-[#111111] border border-[#5E6D4E] p-6 sm:p-8'
                                                : 'bg-[#161616] text-[#E5E5E5] border border-[#262626] hover:border-[#748660]'
                                        }`}
                                    >
                                        <div>
                                            {/* Media Container */}
                                            <div className={`relative w-full overflow-hidden bg-black ${
                                                isSage 
                                                    ? 'h-56 sm:h-64 rounded-sm mb-6 bg-[#657652]/40' 
                                                    : 'h-64 sm:h-72 border-b border-[#222222]'
                                            }`}>
                                                <img 
                                                    src={item.image} 
                                                    alt={item.title} 
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 filter brightness-90 contrast-[1.05]"
                                                    loading="lazy"
                                                />
                                                <div className="absolute top-3 end-3">
                                                    <span className={`text-[10px] font-mono font-bold uppercase tracking-widest px-2.5 py-1 backdrop-blur-md border ${
                                                        isSage 
                                                            ? 'bg-[#0F140A]/90 text-[#748660] border-[#0F140A]' 
                                                            : 'bg-black/85 text-white border-[#333333]'
                                                    }`}>
                                                        {item.badge}
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Content Header & Body */}
                                            <div className={isSage ? 'space-y-2' : 'p-6 sm:p-8 space-y-2.5'}>
                                                <span className={`text-[11px] font-mono uppercase tracking-widest block font-semibold ${
                                                    isSage ? 'text-[#242E1B]' : 'text-[#748660]'
                                                }`}>
                                                    {item.category}
                                                </span>
                                                <h4 className={`text-lg sm:text-xl font-bold tracking-tight font-sans transition-colors ${
                                                    isSage ? 'text-[#0F140A]' : 'text-white group-hover:text-[#748660]'
                                                }`}>
                                                    {item.title}
                                                </h4>
                                                <p className={`text-sm font-sans leading-relaxed line-clamp-2 ${
                                                    isSage ? 'text-[#242E1B]' : 'text-zinc-400'
                                                }`}>
                                                    {item.description}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Action Link Footer */}
                                        <div className={`mt-6 pt-4 border-t flex items-center justify-between text-xs font-mono font-bold transition-colors ${
                                            isSage 
                                                ? 'border-[#5E6D4E] text-[#0F140A]' 
                                                : 'px-6 sm:px-8 pb-6 pt-4 border-[#222222] text-zinc-400 group-hover:text-white'
                                        }`}>
                                            <Link 
                                                href={item.link}
                                                className="flex items-center justify-between w-full hover:underline underline-offset-4"
                                            >
                                                <span className="tracking-wider uppercase">EXPLORE CASE STUDY</span>
                                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                            </Link>
                                        </div>
                                    </motion.div>
                                );
                            })}
                        </AnimatePresence>
                    </motion.div>

                    {/* Footer Explore Link */}
                    <div className="mt-16 flex flex-col items-center space-y-4">
                        <Link href="/portfolio">
                            <button className="px-8 py-3.5 border border-white text-white hover:bg-[#748660] hover:text-[#0F140A] hover:border-[#748660] text-xs font-bold font-mono tracking-widest rtl:tracking-normal uppercase transition-all cursor-pointer">
                                EXPLORE ALL PORTFOLIO ARCHIVES ➔
                            </button>
                        </Link>
                    </div>
                </section>


                {/* 5. GLOBAL INFRASTRUCTURE PERSPECTIVE (3D DOT GLOBE ARC) */}
                <section id="network" className="py-28 sm:py-36 px-6 bg-black border-t border-[#222222] relative overflow-hidden text-center">
                    <div className="max-w-4xl mx-auto relative mb-12">
                        <div className="relative w-full h-48 sm:h-64 flex items-center justify-center">
                            <svg viewBox="0 0 800 300" className="w-full h-full opacity-60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="400" cy="300" rx="380" ry="180" stroke="#333333" strokeWidth="1" strokeDasharray="3 3" />
                                <ellipse cx="400" cy="300" rx="320" ry="140" stroke="#444444" strokeWidth="1" />
                                <ellipse cx="400" cy="300" rx="240" ry="100" stroke="#333333" strokeWidth="1" strokeDasharray="2 2" />
                                <ellipse cx="400" cy="300" rx="140" ry="60" stroke="#555555" strokeWidth="1" />
                                
                                <circle cx="210" cy="220" r="4" fill="#FF5722" className="animate-pulse" />
                                <circle cx="320" cy="180" r="4" fill="#748660" className="animate-pulse" />
                                <circle cx="400" cy="160" r="5" fill="#FFFFFF" className="animate-pulse" />
                                <circle cx="480" cy="185" r="4" fill="#B2831B" className="animate-pulse" />
                                <circle cx="590" cy="225" r="4" fill="#00E5FF" className="animate-pulse" />
                            </svg>
                        </div>
                    </div>

                    <div className="max-w-4xl mx-auto space-y-8 relative z-10">
                        <h2 className="text-2xl sm:text-4xl md:text-5xl font-medium tracking-tight text-[#EDEDED] leading-[1.35] font-sans">
                            Global perspective, local heartbeat. 120+ enterprise deployments, 14 cloud nodes connected worldwide.
                        </h2>

                        <div className="flex items-center justify-center space-x-10 rtl:space-x-reverse text-xs font-mono uppercase tracking-[0.2em] rtl:tracking-normal text-zinc-400 pt-4">
                            <a href="#expertise" className="hover:text-white underline underline-offset-4">
                                Our Stack
                            </a>
                            <Link href="/about/mahmoud-amin" className="hover:text-white underline underline-offset-4">
                                About Us
                            </Link>
                        </div>
                    </div>
                </section>


                {/* 6. DISCOVERY BUDGET & ARCHITECTURE ESTIMATOR */}
                <section id="contact" className="py-24 sm:py-32 px-6 border-t border-[#222222] bg-[#141414]">
                    <ProjectEstimator />
                </section>

            </div>
        </PublicLayout>
    );
}
