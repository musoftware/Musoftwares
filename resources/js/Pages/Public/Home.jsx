import { Button } from '@/Components/ui/button';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Wallet, ShieldCheck, Clock, Check, ChevronDown, Sparkles, 
    ArrowRight, DollarSign, Activity, FileText, Send, UserCheck, 
    Layers, Star, HelpCircle 
} from 'lucide-react';

// ============================================================================
// PURE VECTOR ILLUSTRATIONS: FLAT EDITORIAL, SIMPLIFIED HUMAN SHAPES, FLAT GEOMETRY
// ============================================================================

// 1. Financial Workflows Illustration (Vault, Coins, Transaction splits)
function FinancialIllustration() {
    return (
        <svg viewBox="0 0 500 400" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
            {/* Background elements */}
            <rect width="500" height="400" rx="16" fill="#f8fafc" />
            <circle cx="250" cy="200" r="160" fill="#e0e7ff" opacity="0.4" />
            
            {/* Flat Geometry: Vault and concentric circles */}
            <circle cx="250" cy="200" r="110" fill="none" stroke="#6366f1" strokeWidth="6" />
            <circle cx="250" cy="200" r="90" fill="none" stroke="#a855f7" strokeWidth="2" strokeDasharray="6 6" />
            <circle cx="250" cy="200" r="40" fill="#ffffff" stroke="#6366f1" strokeWidth="4" />
            
            {/* Vault lock handle bars */}
            <rect x="245" y="120" width="10" height="160" rx="5" fill="#6366f1" transform="rotate(45 250 200)" />
            <rect x="245" y="120" width="10" height="160" rx="5" fill="#6366f1" transform="rotate(-45 250 200)" />
            
            {/* Floating Flat Geometric Coins */}
            <g transform="translate(100, 110)">
                <ellipse cx="20" cy="20" rx="25" ry="12" fill="#10b981" />
                <ellipse cx="20" cy="15" rx="25" ry="12" fill="#34d399" />
                <path d="M -5 15 L -5 20 A 25 12 0 0 0 45 20 L 45 15 Z" fill="#10b981" />
            </g>
            <g transform="translate(360, 240)">
                <ellipse cx="20" cy="20" rx="25" ry="12" fill="#6366f1" />
                <ellipse cx="20" cy="15" rx="25" ry="12" fill="#818cf8" />
                <path d="M -5 15 L -5 20 A 25 12 0 0 0 45 20 L 45 15 Z" fill="#6366f1" />
            </g>
            
            {/* Simplified Human Shape 1: Left */}
            <g transform="translate(110, 200)">
                {/* Body/Torso - simplified editorial geometry */}
                <path d="M 20 80 Q 20 40 40 40 L 60 40 Q 80 40 80 80 Z" fill="#4f46e5" />
                {/* Head */}
                <circle cx="50" cy="20" r="16" fill="#fbcfe8" />
                {/* Arm reaching for the vault */}
                <path d="M 75 48 Q 110 40 130 55" stroke="#fbcfe8" strokeWidth="8" strokeLinecap="round" />
            </g>

            {/* Simplified Human Shape 2: Right */}
            <g transform="translate(310, 100)">
                {/* Body/Torso */}
                <path d="M 20 80 Q 20 35 45 35 L 55 35 Q 80 35 80 80 Z" fill="#10b981" />
                {/* Head */}
                <circle cx="50" cy="15" r="15" fill="#fcd34d" />
                {/* Arm holding a geometric coin */}
                <path d="M 25 45 Q -10 60 -15 80" stroke="#fcd34d" strokeWidth="8" strokeLinecap="round" />
            </g>

            {/* Flow line connection arrows */}
            <path d="M 120 180 C 120 140 170 120 210 125" stroke="#6366f1" strokeWidth="3" strokeLinecap="round" strokeDasharray="4 4" />
            <polygon points="215,125 205,120 208,130" fill="#6366f1" />
        </svg>
    );
}

// 2. Marketplace Illustration (Exchanging gears, gears represent services/contracts)
function MarketplaceIllustration() {
    return (
        <svg viewBox="0 0 500 400" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="500" height="400" rx="16" fill="#f8fafc" />
            
            {/* Background geometric connection grids */}
            <path d="M 50 200 L 450 200" stroke="#cbd5e1" strokeWidth="2" strokeDasharray="8 8" />
            <circle cx="250" cy="200" r="120" fill="none" stroke="#e2e8f0" strokeWidth="4" />
            
            {/* Central Flat Gear shapes (representing marketplace connections) */}
            <g transform="translate(250, 180) rotate(15)">
                <circle cx="0" cy="0" r="45" fill="#a855f7" />
                <rect x="-10" y="-55" width="20" height="110" rx="4" fill="#a855f7" />
                <rect x="-10" y="-55" width="20" height="110" rx="4" fill="#a855f7" transform="rotate(45)" />
                <rect x="-10" y="-55" width="20" height="110" rx="4" fill="#a855f7" transform="rotate(90)" />
                <rect x="-10" y="-55" width="20" height="110" rx="4" fill="#a855f7" transform="rotate(135)" />
                <circle cx="0" cy="0" r="25" fill="#f8fafc" />
            </g>
            <g transform="translate(190, 240) rotate(-10)">
                <circle cx="0" cy="0" r="30" fill="#6366f1" />
                <rect x="-6" y="-38" width="12" height="76" rx="3" fill="#6366f1" />
                <rect x="-6" y="-38" width="12" height="76" rx="3" fill="#6366f1" transform="rotate(45)" />
                <rect x="-6" y="-38" width="12" height="76" rx="3" fill="#6366f1" transform="rotate(90)" />
                <rect x="-6" y="-38" width="12" height="76" rx="3" fill="#6366f1" transform="rotate(135)" />
                <circle cx="0" cy="0" r="15" fill="#f8fafc" />
            </g>

            {/* Simplified Human Shape: Left (Freelancer offering services) */}
            <g transform="translate(60, 160)">
                {/* Body */}
                <path d="M 10 90 L 30 40 L 50 40 L 70 90 Z" fill="#6366f1" />
                {/* Head */}
                <circle cx="40" cy="15" r="15" fill="#fed7aa" />
                {/* Offering arm */}
                <path d="M 55 45 Q 90 40 110 65" stroke="#fed7aa" strokeWidth="7" strokeLinecap="round" />
            </g>

            {/* Simplified Human Shape: Right (Client buying services) */}
            <g transform="translate(360, 160)">
                {/* Body */}
                <path d="M 10 90 L 30 35 L 50 35 L 70 90 Z" fill="#db2777" />
                {/* Head */}
                <circle cx="40" cy="10" r="15" fill="#fbcfe8" />
                {/* Reaching arm */}
                <path d="M 25 40 Q -10 35 -30 60" stroke="#fbcfe8" strokeWidth="7" strokeLinecap="round" />
            </g>

            {/* Contract shield overlay (Flat Geometry) */}
            <g transform="translate(250, 70)">
                <path d="M 0 -25 L 20 -15 L 20 10 C 20 25 0 35 0 35 C 0 35 -20 25 -20 10 L -20 -15 Z" fill="#10b981" />
                <path d="M -8 2 L -2 8 L 8 -4" stroke="#ffffff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
            </g>
        </svg>
    );
}

// 3. Workspace Illustration (Unified timeline and calendar widget collaboration)
function WorkspaceIllustration() {
    return (
        <svg viewBox="0 0 500 400" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="500" height="400" rx="16" fill="#f8fafc" />
            
            {/* Abstract connected timeline grid */}
            <line x1="100" y1="120" x2="100" y2="300" stroke="#cbd5e1" strokeWidth="3" strokeDasharray="4 4" />
            <circle cx="100" cy="150" r="8" fill="#6366f1" />
            <circle cx="100" cy="220" r="8" fill="#a855f7" />
            <circle cx="100" cy="290" r="8" fill="#10b981" />

            {/* Flat Geometry Widget Cards */}
            <g transform="translate(130, 110)">
                <rect width="180" height="50" rx="8" fill="#ffffff" stroke="#e2e8f0" strokeWidth="2" />
                <rect x="15" y="15" width="20" height="20" rx="4" fill="#e0e7ff" />
                <rect x="50" y="15" width="90" height="8" rx="2" fill="#cbd5e1" />
                <rect x="50" y="27" width="60" height="6" rx="2" fill="#e2e8f0" />
            </g>
            <g transform="translate(130, 180)">
                <rect width="180" height="50" rx="8" fill="#ffffff" stroke="#e2e8f0" strokeWidth="2" />
                <rect x="15" y="15" width="20" height="20" rx="4" fill="#f3e8ff" />
                <rect x="50" y="15" width="100" height="8" rx="2" fill="#cbd5e1" />
                <rect x="50" y="27" width="50" height="6" rx="2" fill="#e2e8f0" />
            </g>
            <g transform="translate(130, 250)">
                <rect width="180" height="50" rx="8" fill="#ffffff" stroke="#e2e8f0" strokeWidth="2" />
                <rect x="15" y="15" width="20" height="20" rx="4" fill="#d1fae5" />
                <rect x="50" y="15" width="80" height="8" rx="2" fill="#cbd5e1" />
                <rect x="50" y="27" width="70" height="6" rx="2" fill="#e2e8f0" />
            </g>

            {/* Simplified Human Shape 1: Left */}
            <g transform="translate(20, 200)">
                <path d="M 15 90 L 30 35 L 50 35 L 65 90 Z" fill="#6366f1" />
                <circle cx="40" cy="12" r="14" fill="#ffedd5" />
                <path d="M 50 45 Q 85 45 95 65" stroke="#ffedd5" strokeWidth="6" strokeLinecap="round" />
            </g>

            {/* Simplified Human Shape 2: Right */}
            <g transform="translate(360, 180)">
                <path d="M 15 95 L 30 40 L 50 40 L 65 95 Z" fill="#a855f7" />
                <circle cx="40" cy="15" r="14" fill="#fce7f3" />
                <path d="M 25 45 Q -10 50 -25 70" stroke="#fce7f3" strokeWidth="6" strokeLinecap="round" />
            </g>

            {/* Floating abstract checkmarks (Flat Editorial Geometry) */}
            <circle cx="400" cy="100" r="22" fill="#10b981" />
            <path d="M 392 100 L 397 105 L 408 94" stroke="#ffffff" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

// 4. Client Portal / Customer Experience Illustration
function ClientPortalIllustration() {
    return (
        <svg viewBox="0 0 500 400" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="500" height="400" rx="16" fill="#f8fafc" />
            
            {/* Huge Abstract Credit Card (Flat Geometry representing simplified billing checkout) */}
            <g transform="translate(100, 100) rotate(-5)">
                <rect width="280" height="170" rx="12" fill="#6366f1" />
                <rect x="25" y="25" width="45" height="32" rx="6" fill="#fcd34d" />
                <rect x="25" y="80" width="130" height="10" rx="2" fill="#818cf8" />
                <rect x="25" y="105" width="200" height="12" rx="3" fill="#ffffff" />
                <circle cx="220" cy="40" r="18" fill="#a855f7" opacity="0.8" />
                <circle cx="240" cy="40" r="18" fill="#db2777" opacity="0.8" />
            </g>

            {/* Simplified Human Shape standing behind / paying */}
            <g transform="translate(320, 170)">
                <path d="M 20 110 L 40 45 L 60 45 L 80 110 Z" fill="#10b981" />
                <circle cx="50" cy="18" r="16" fill="#fcd34d" />
                {/* Arm typing / pushing key */}
                <path d="M 35 55 Q -10 70 -30 90" stroke="#fcd34d" strokeWidth="8" strokeLinecap="round" />
            </g>

            {/* Glowing Shield Checkmark (Flat Geometry representing TLS security) */}
            <g transform="translate(120, 260)">
                <circle cx="25" cy="25" r="35" fill="#ffffff" stroke="#cbd5e1" strokeWidth="2" />
                <path d="M 25 13 L 38 18 L 38 30 C 38 38 25 43 25 43 C 25 43 12 38 12 30 L 12 18 Z" fill="#10b981" />
                <path d="M 20 28 L 24 32 L 31 23" stroke="#ffffff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
            </g>
        </svg>
    );
}

// 5. BI Analytics & Reporting Illustration
function AnalyticsIllustration() {
    return (
        <svg viewBox="0 0 500 400" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="500" height="400" rx="16" fill="#f8fafc" />
            
            {/* Flat Geometric rising bars representing metrics */}
            <rect x="80" y="240" width="30" height="80" rx="4" fill="#cbd5e1" />
            <rect x="140" y="200" width="30" height="120" rx="4" fill="#cbd5e1" />
            <rect x="200" y="160" width="30" height="160" rx="4" fill="#818cf8" />
            <rect x="260" y="100" width="30" height="220" rx="4" fill="#6366f1" />
            <rect x="320" y="60" width="30" height="260" rx="4" fill="#10b981" />
            
            {/* Glowing editorial Trend Curve line */}
            <path d="M 95 230 Q 155 180 215 150 T 335 50" fill="none" stroke="#4f46e5" strokeWidth="5" strokeLinecap="round" />
            <circle cx="335" cy="50" r="8" fill="#4f46e5" />
            <circle cx="335" cy="50" r="16" fill="none" stroke="#6366f1" strokeWidth="2" opacity="0.5" />

            {/* Simplified Human Shape celebrating growth */}
            <g transform="translate(380, 180)">
                <path d="M 20 100 Q 20 40 45 40 L 55 40 Q 80 40 80 100 Z" fill="#6366f1" />
                <circle cx="50" cy="15" r="16" fill="#ffedd5" />
                {/* Arm pointing upward */}
                <path d="M 30 50 Q -5 20 -20 0" stroke="#ffedd5" strokeWidth="7" strokeLinecap="round" />
            </g>

            {/* Decorative flat geometric abstract rings */}
            <circle cx="440" cy="70" r="30" fill="none" stroke="#a855f7" strokeWidth="4" opacity="0.3" />
            <circle cx="450" cy="70" r="15" fill="#f3e8ff" />
        </svg>
    );
}

export default function Home({ canLogin, canRegister }) {
    // FAQ Accordions State
    const [faqExpanded, setFaqExpanded] = useState({
        0: true, // First open by default
        1: false,
        2: false,
        3: false,
        4: false
    });

    const toggleFaq = (idx) => {
        setFaqExpanded(prev => ({ ...prev, [idx]: !prev[idx] }));
    };

    return (
        <PublicLayout>
            <Head title="musoftware — Unified Client Operations, ERP & Wallet Infrastructure" />

            {/* Custom animations styling */}
            <style dangerouslySetInnerHTML={{__html: `
                .mesh-circle {
                    background: radial-gradient(circle, rgba(99,102,241,0.05) 0%, transparent 70%);
                }
            `}} />

            {/* ====================================================
                1. HERO SECTION
               ==================================================== */}
            <section id="hero" className="relative min-h-[92vh] flex items-center justify-center overflow-hidden bg-white pt-20 pb-16 border-b border-slate-100">
                <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none">
                    <div className="absolute top-[-10%] left-[-10%] w-[50%] aspect-square mesh-circle rounded-full blur-3xl" />
                    <div className="absolute bottom-[10%] right-[-10%] w-[50%] aspect-square bg-gradient-to-tr from-purple-500/5 to-indigo-500/5 rounded-full blur-3xl" />
                </div>

                <div className="relative z-10 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    {/* Left Column: Contextual Core Message */}
                    <div className="lg:col-span-6 flex flex-col space-y-8 text-left max-w-2xl mx-auto lg:mx-0">
                        {/* Version Platform Tag */}
                        <div className="inline-flex items-center gap-2 w-fit rounded-full bg-indigo-50/50 border border-indigo-100 px-3.5 py-1.5 text-xs text-indigo-950 backdrop-blur-sm">
                            <span className="flex h-2 w-2 rounded-full bg-indigo-600 animate-pulse" />
                            <span className="font-bold text-indigo-900">Version 4.0 Live</span>
                            <span className="text-indigo-205">|</span>
                            <span className="text-indigo-855 font-semibold">Unified Corporate Platform</span>
                        </div>

                        <h1 className="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight text-slate-900 leading-tight">
                            Run your clients, billing, services, and operations from <span className="bg-gradient-to-r from-indigo-600 via-purple-650 to-indigo-600 bg-clip-text text-transparent">one workspace.</span>
                        </h1>

                        <p className="text-lg text-slate-650 leading-relaxed font-normal">
                            Stop forcing basic ERPs, detached freelance boards, and raw billing tools to talk. Unify deep business ledger automation, escrow transactions, service marketplaces, and live operations in a highly structured, scalable hub.
                        </p>

                        {/* Premium Action Buttons */}
                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link href="/register?trial=true" className="w-full sm:w-auto">
                                <Button size="lg" className="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg shadow-lg shadow-indigo-500/10 border-0 px-8 h-12 text-sm font-semibold flex items-center justify-center gap-2 group">
                                    Start Free 14-Day Trial
                                    <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </Button>
                            </Link>
                            <a href="#workspace" className="w-full sm:w-auto">
                                <Button size="lg" variant="outline" className="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 rounded-lg px-8 h-12 text-sm font-semibold">
                                    Explore Core Workspace
                                </Button>
                            </a>
                        </div>

                        {/* Trust Badge Grid */}
                        <div className="pt-6 border-t border-slate-100 flex flex-wrap items-center gap-6 text-slate-500 text-xs">
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="h-4.5 w-4.5 text-emerald-600" />
                                <span className="text-slate-600 font-semibold">Secured TLS Escrow Accounts</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Check className="h-4.5 w-4.5 text-indigo-650" />
                                <span className="text-slate-600 font-semibold">No Credit Card Needed to Start</span>
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Premium Flat Geometry Hero Image Showcase */}
                    <div className="lg:col-span-6 relative w-full flex items-center justify-center">
                        <div className="relative w-full max-w-lg aspect-square flex items-center justify-center bg-slate-50 border border-slate-200/80 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                            {/* Decorative background glow rings */}
                            <div className="absolute inset-0 bg-gradient-to-tr from-indigo-500/5 to-purple-500/5 pointer-events-none" />
                            <img
                                src="/images/hero_illustration.png"
                                alt="musoftware Operations Collaboration Illustration"
                                className="w-full h-full object-contain rounded-xl select-none pointer-events-none transform group-hover:scale-[1.02] transition-transform duration-500"
                            />
                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                2. COMPACT SOCIAL PROOF SECTION
               ==================================================== */}
            <section id="proof" className="bg-slate-50 py-10 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8 text-center md:text-left">
                        <div className="flex flex-col p-6 bg-white border border-slate-200/80 rounded-xl shadow-sm">
                            <span className="text-3xl font-bold tracking-tight text-slate-900">12,480+</span>
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5">Active Corporate Entities</span>
                        </div>
                        <div className="flex flex-col p-6 bg-white border border-slate-200/80 rounded-xl shadow-sm">
                            <span className="text-3xl font-bold tracking-tight text-slate-900">$128.4M+</span>
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5">Ledger Invoices Cleared</span>
                        </div>
                        <div className="flex flex-col p-6 bg-white border border-slate-200/80 rounded-xl shadow-sm">
                            <span className="text-3xl font-bold tracking-tight text-slate-900">94,200+</span>
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5">Marketplace Orders Released</span>
                        </div>
                        <div className="flex flex-col p-6 bg-white border border-slate-200/80 rounded-xl shadow-sm">
                            <span className="text-3xl font-bold tracking-tight text-slate-900">$42.5M+</span>
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5">Escrow System locked volume</span>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                3. PLATFORM OVERVIEW (4 PILLARS)
               ==================================================== */}
            <section id="overview" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-20">
                        <h2 className="text-xs font-semibold uppercase tracking-wider text-indigo-600">Integrated Architecture</h2>
                        <p className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            The Four Pillars of the Unified Ecosystem
                        </p>
                        <p className="mt-4 text-base text-slate-655 font-normal">
                            Stop running multi-thousand dollar business operations across loosely joined apps. Unify core components under one database structure.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                        
                        {/* Pillar 1: Financial Operations */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-colors duration-300 shadow-sm">
                            <div>
                                <div className="h-12 w-12 rounded-lg bg-indigo-50 flex items-center justify-center mb-6">
                                    <Wallet className="h-6 w-6 text-indigo-600" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-2">1. Financial Ledger & Wallet Operations</h3>
                                <p className="text-slate-600 text-sm leading-relaxed mb-6 font-normal">
                                    Automate account withdrawals, ledger balancing, multi-currency wallets, automatic tax splits, and instant Stripe-backed deposits.
                                </p>
                            </div>
                            <div>
                                <ul className="space-y-2.5">
                                    {['Multi-currency wallet ledger tracking', 'Escrow protection and automatic locks', 'Tax-split configurations'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700">
                                            <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        {/* Pillar 2: Customer Management */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-colors duration-300 shadow-sm">
                            <div>
                                <div className="h-12 w-12 rounded-lg bg-purple-50 flex items-center justify-center mb-6">
                                    <UserCheck className="h-6 w-6 text-purple-650" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-2">2. Unified Client Workspace Portal</h3>
                                <p className="text-slate-600 text-sm leading-relaxed mb-6 font-normal">
                                    Provide clients with transparent portals. Clients pay invoices, deposit wallet funds, manage running projects, and direct proposals inside a white-labeled space.
                                </p>
                            </div>
                            <div>
                                <ul className="space-y-2.5">
                                    {['Shared project dashboards and milestones', 'White-labeled customer billing interfaces', 'Escrow and proposal reviews'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700">
                                            <Check className="h-4 w-4 text-purple-650 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        {/* Pillar 3: Marketplace & Freelance */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-colors duration-300 shadow-sm">
                            <div>
                                <div className="h-12 w-12 rounded-lg bg-emerald-50 flex items-center justify-center mb-6">
                                    <Layers className="h-6 w-6 text-emerald-600" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-2">3. Marketplace & Freelance Board</h3>
                                <p className="text-slate-600 text-sm leading-relaxed mb-6 font-normal">
                                    Sell fixed-price services like Shopify, or pitch contracts on custom RFPs. Backed by fully integrated escrows and verified profile credentials.
                                </p>
                            </div>
                            <div>
                                <ul className="space-y-2.5">
                                    {['Tiered productized services (Starter, Pro, Elite)', 'Contract RFP and proposal processing systems', 'Direct milestone payouts'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700">
                                            <Check className="h-4 w-4 text-emerald-600 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        {/* Pillar 4: Communication & Support */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-colors duration-300 shadow-sm">
                            <div>
                                <div className="h-12 w-12 rounded-lg bg-orange-50 flex items-center justify-center mb-6">
                                    <Activity className="h-6 w-6 text-orange-650" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-2">4. Realtime Timer & System Communications</h3>
                                <p className="text-slate-600 text-sm leading-relaxed mb-6 font-normal">
                                    Unify chat, logs, and timer events. Timers directly update active invoices; chat pipelines directly feed billing contracts, eliminating operational friction.
                                </p>
                            </div>
                            <div>
                                <ul className="space-y-2.5">
                                    {['Contextual real-time chat with invoice linking', 'Precise timed-billing integration logs', 'Automatic system email and notification triggers'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700">
                                            <Check className="h-4 w-4 text-orange-650 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                4. FINANCIAL WORKFLOWS SECTION (Editorial Showcase)
               ==================================================== */}
            <section id="financials" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Financial copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-emerald-600">Financial Infrastructure</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Stripe-precision wallets, contract deposits, and recurring billing logs
                        </h2>
                        <p className="text-slate-655 text-sm leading-relaxed font-normal">
                            Stop exposing your enterprise billing to human error. Track financial pipelines with extreme precision. Establish automated multi-currency contracts, hold marketplace funds securely in integrated escrow chambers, and manage instantaneous bank withdrawals in a fully auditable double-entry accounting ledger.
                        </p>
                        
                        <div className="grid grid-cols-2 gap-4">
                            <div className="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
                                <div className="text-emerald-600 font-bold font-mono text-lg">0%</div>
                                <div className="text-xs text-slate-500 mt-1 font-semibold">Escrow Fraud Guarantee</div>
                            </div>
                            <div className="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
                                <div className="text-indigo-655 font-bold font-mono text-lg">&lt;3s</div>
                                <div className="text-xs text-slate-500 mt-1 font-semibold">Wallet Payout Processing</div>
                            </div>
                        </div>
                    </div>

                    {/* Right: Premium Flat Geometry Vector Illustration */}
                    <div className="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                        <div className="absolute inset-0 bg-gradient-to-tr from-indigo-500/5 to-emerald-500/5 pointer-events-none" />
                        <FinancialIllustration />
                    </div>
                </div>
            </section>

            {/* ====================================================
                5. MARKETPLACE & FREELANCE SECTION (Editorial Showcase)
               ==================================================== */}
            <section id="marketplace" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Premium Flat Geometry Vector Illustration */}
                    <div className="lg:col-span-7 bg-slate-50 border border-slate-200/80 rounded-2xl p-6 shadow-xl relative overflow-hidden order-last lg:order-first">
                        <div className="absolute inset-0 bg-gradient-to-tr from-purple-500/5 to-indigo-500/5 pointer-events-none" />
                        <MarketplaceIllustration />
                    </div>

                    {/* Right: Marketplace copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-purple-650 font-bold">Ecosystem Workflows</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Clean, operational freelance contracts and escrow trackers
                        </h2>
                        <p className="text-slate-655 text-sm leading-relaxed font-normal">
                            Skip the basic marketing descriptions of freelance websites. We give you a fully functional project ecosystem showing exactly how proposals shift into active escrowed contracts. Work with absolute trust using unified milestone release structures and verified reviews.
                        </p>
                        
                        <div className="space-y-3.5">
                            <div className="flex items-center gap-2 text-xs text-slate-700 font-semibold">
                                <Check className="h-4 w-4 text-purple-650 shrink-0" />
                                <span>Tiered productized services (Starter, Pro, Elite)</span>
                            </div>
                            <div className="flex items-center gap-2 text-xs text-slate-700 font-semibold">
                                <Check className="h-4 w-4 text-purple-650 shrink-0" />
                                <span>Contract RFP and proposal processing systems</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                6. UNIFIED WORKSPACE SECTION (Editorial Showcase)
               ==================================================== */}
            <section id="workspace" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Workspace copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-650 font-bold">Unified Architecture</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            One Customer. One Operational Workspace.
                        </h2>
                        <p className="text-slate-655 text-sm leading-relaxed font-normal">
                            Tear down the walls between client relations and operational tracking. Our unified customer dashboard combines transaction audits, invoices, escrow logs, and private internal admin notes under a single user session profile.
                        </p>
                        
                        <div className="space-y-3.5">
                            <div className="flex items-center gap-2 text-xs text-slate-700 font-semibold">
                                <Check className="h-4 w-4 text-indigo-650 shrink-0" />
                                <span>Shared project dashboards and milestones</span>
                            </div>
                            <div className="flex items-center gap-2 text-xs text-slate-700 font-semibold">
                                <Check className="h-4 w-4 text-indigo-650 shrink-0" />
                                <span>White-labeled customer billing interfaces</span>
                            </div>
                        </div>
                    </div>

                    {/* Right: Premium Flat Geometry Vector Illustration */}
                    <div className="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                        <div className="absolute inset-0 bg-gradient-to-tr from-indigo-500/5 to-purple-500/5 pointer-events-none" />
                        <WorkspaceIllustration />
                    </div>
                </div>
            </section>

            {/* ====================================================
                7. CUSTOMER EXPERIENCE PORTAL (Editorial Showcase)
               ==================================================== */}
            <section id="customer-exp" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Premium Flat Geometry Vector Illustration */}
                    <div className="lg:col-span-7 bg-slate-50 border border-slate-200/80 rounded-2xl p-6 shadow-xl relative overflow-hidden order-last lg:order-first">
                        <div className="absolute inset-0 bg-gradient-to-tr from-emerald-500/5 to-indigo-500/5 pointer-events-none" />
                        <ClientPortalIllustration />
                    </div>

                    {/* Right: Copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-655 font-bold">Client Experience Portal</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Stunningly simple for your clients, robust for you
                        </h2>
                        <p className="text-slate-655 text-sm leading-relaxed font-normal">
                            Stop training your clients to use confusing system frameworks. We separate complexity seamlessly. While you navigate dense ERP tables, contract pipelines, and audit trails, your client accesses a white-labeled dashboard designed for extreme clarity and fast payments.
                        </p>
                        
                        <div className="flex gap-4">
                            <div className="flex items-center gap-2">
                                <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                <span className="text-xs text-slate-700 font-bold">White-label Domain Mapping</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="h-2 w-2 rounded-full bg-indigo-500" />
                                <span className="text-xs text-slate-700 font-bold">Instant Stripe Checkout integration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                8. ANALYTICS & REPORTING (Editorial Showcase)
               ==================================================== */}
            <section id="analytics" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Analytics copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-emerald-600 font-bold">Business Intelligence</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            High-Fidelity BI Reporting & Wallet Analytics
                        </h2>
                        <p className="text-slate-655 text-sm leading-relaxed font-normal">
                            Monitor cash flow velocity with realistic corporate metrics. Swap perspectives instantly to audit earnings growth, locked escrow volumes, and client ledger activities. Make decisions using robust financial projection maps.
                        </p>
                    </div>

                    {/* Right: Premium Flat Geometry Vector Illustration */}
                    <div className="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                        <div className="absolute inset-0 bg-gradient-to-tr from-indigo-500/5 to-emerald-500/5 pointer-events-none" />
                        <AnalyticsIllustration />
                    </div>
                </div>
            </section>

            {/* ====================================================
                9. PRICING SECTION
               ==================================================== */}
            <section id="pricing" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-650 font-bold">Subscription Plans</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                            Clean, corporate scale pricing. No tricks.
                        </h2>
                        <p className="text-slate-600 text-sm leading-relaxed mt-4 font-normal">
                            Select the operational tier matching your transaction velocity. Every plan includes deep escrow system protection, client communication modules, and standard wallet ledgers.
                        </p>
                    </div>

                    {/* Pricing Cards Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto">
                        
                        {/* Plan 1: Starter Developer */}
                        <div className="bg-slate-50/50 border border-slate-200 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-all shadow-sm">
                            <div>
                                <div className="text-xs font-bold text-indigo-650 uppercase tracking-widest">Starter Tier</div>
                                <h3 className="text-xl font-extrabold text-slate-900 mt-1">Individual Freelancer</h3>
                                <p className="text-slate-500 text-xs mt-2 font-medium">Deploy single projects and manage client wallets easily.</p>
                                
                                <div className="mt-6 flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">$49</span>
                                    <span className="text-slate-400 text-sm ml-2">/ month</span>
                                </div>

                                <ul className="mt-8 space-y-4 text-xs text-slate-700 font-semibold">
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Up to 5 Active Client Portals</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Standard Escrow System Protection</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Developer API Sandbox Access</span>
                                    </li>
                                </ul>
                            </div>
                            <Link href="/register?plan=starter" className="mt-8">
                                <Button className="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-lg py-2.5 font-bold shadow-sm">
                                    Choose Starter
                                </Button>
                            </Link>
                        </div>

                        {/* Plan 2: Professional Business (Recommended) */}
                        <div className="bg-white border-2 border-indigo-500 rounded-xl p-8 flex flex-col justify-between relative shadow-xl shadow-indigo-500/5 glow-indigo">
                            <span className="absolute top-0 right-6 -translate-y-1/2 bg-indigo-600 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full shadow-md">
                                Recommended Plan
                            </span>
                            <div>
                                <div className="text-xs font-bold text-indigo-655 uppercase tracking-widest">Business Tier</div>
                                <h3 className="text-xl font-extrabold text-slate-900 mt-1">Agency & Studio</h3>
                                <p className="text-slate-500 text-xs mt-2 font-medium">Unified operational workspace for team billing and escrows.</p>
                                
                                <div className="mt-6 flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">$149</span>
                                    <span className="text-slate-400 text-sm ml-2">/ month</span>
                                </div>

                                <ul className="mt-8 space-y-4 text-xs text-slate-700 font-semibold">
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-650 shrink-0" />
                                        <span className="font-bold text-slate-900">Unlimited Client Portals</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-650 shrink-0" />
                                        <span>White-labeled Custom Domain Portals</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-650 shrink-0" />
                                        <span>Automated Tax Split Splitters</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-655 shrink-0" />
                                        <span>Priority Dispute Support</span>
                                    </li>
                                </ul>
                            </div>
                            <Link href="/register?plan=pro" className="mt-8">
                                <Button className="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg py-2.5 font-bold shadow-md shadow-indigo-500/10">
                                    Start Professional Trial
                                </Button>
                            </Link>
                        </div>

                        {/* Plan 3: Enterprise Scale */}
                        <div className="bg-slate-50/50 border border-slate-200 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-all shadow-sm">
                            <div>
                                <div className="text-xs font-bold text-indigo-655 uppercase tracking-widest">Enterprise Tier</div>
                                <h3 className="text-xl font-extrabold text-slate-900 mt-1">Custom Corporate</h3>
                                <p className="text-slate-500 text-xs mt-2 font-medium">Enterprise-grade API limits, compliance, and custom ledgers.</p>
                                
                                <div className="mt-6 flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">Custom</span>
                                </div>

                                <ul className="mt-8 space-y-4 text-xs text-slate-700 font-semibold">
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Custom Double-Entry Audit Logs</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Dedicated Legal Account Support</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Enterprise Rate Limit Sandbox</span>
                                    </li>
                                </ul>
                            </div>
                            <a href="mailto:hello@musoftwares.com?subject=Enterprise Query" className="mt-8">
                                <Button variant="outline" className="w-full bg-white hover:bg-slate-50 text-slate-700 border-slate-250 rounded-lg py-2.5 font-bold shadow-sm">
                                    Contact Accounts Department
                                </Button>
                            </a>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                10. FAQ SECTION (Accordion)
               ==================================================== */}
            <section id="faq" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-655 font-bold">Technical Details</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                            Frequently Answered System Inquiries
                        </h2>
                    </div>

                    {/* FAQ Accordion List */}
                    <div className="space-y-4">
                        {[
                            {
                                q: "How is the Escrow system secured and monitored?",
                                a: "Every escrow contract locks payments inside a secure transactional bank vault using advanced TLS verification parameters. Released milestone payments execute within seconds to designated beneficiary wallets under strict double-entry ledger audits, completely eliminating merchant fraud risks."
                            },
                            {
                                q: "How does the running billable time tracker interact with invoices?",
                                a: "The billable timer logs chronological events in real-time. Pausing or completing a tracker updates the client invoice draft line items directly at your predefined rate. Clients receive detailed timecard logs alongside the final itemized payment dispatch sheet."
                            },
                            {
                                q: "Are client portals white-labeled under our company domains?",
                                a: "Yes. The Business Tier allows configuring custom domain layouts (e.g. portal.yourfirm.com) complete with automatic SSL provisioning. Clients experience an absolute white-label design environment reflecting your corporate branding, colors, and logos."
                            },
                            {
                                q: "What multi-currency payment gateways do you support?",
                                a: "Musoftware supports 130+ regional currencies natively, backed by standard integrations including Stripe Payouts, Bank Wire transfers, ACH transfers, and PayPal channels, managed directly within the client wallet ledger."
                            },
                            {
                                q: "How does musoftware prevent database merge conflicts on concurrent updates?",
                                a: "We run a robust transactional queue framework built directly on database isolation levels (serializable states). Parallel actions (timer logs, invoice payments, and escrow releases) resolve sequentially to maintain precise transaction safety."
                            }
                        ].map((faq, idx) => (
                            <div key={idx} className="bg-white border border-slate-200 rounded-xl overflow-hidden transition-all shadow-sm">
                                <button
                                    onClick={() => toggleFaq(idx)}
                                    className="w-full px-6 py-5 text-left flex justify-between items-center gap-4 hover:bg-slate-50/50 focus:outline-none transition-colors"
                                >
                                    <span className="text-sm font-bold text-slate-900">{faq.q}</span>
                                    <ChevronDown className={`h-4.5 w-4.5 text-slate-400 transition-transform duration-300 ${
                                        faqExpanded[idx] ? 'rotate-185 text-slate-800' : 'rotate-0'
                                    }`} />
                                </button>
                                
                                {/* Smooth height transition */}
                                <div className={`transition-all duration-300 overflow-hidden ${
                                    faqExpanded[idx] ? 'max-h-[200px] border-t border-slate-200' : 'max-h-0'
                                }`}>
                                    <p className="px-6 py-5 text-xs text-slate-655 leading-relaxed bg-slate-50/50">
                                        {faq.a}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ====================================================
                11. FINAL CTA SECTION
               ==================================================== */}
            <section id="cta" className="bg-white py-24 relative z-10">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="relative rounded-2xl bg-gradient-to-r from-indigo-50 via-slate-50 to-indigo-50 border border-indigo-100 p-8 sm:p-12 md:p-16 overflow-hidden shadow-xl text-center">
                        
                        {/* Soft light glows */}
                        <div className="absolute top-0 right-0 h-40 w-40 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none" />
                        <div className="absolute bottom-0 left-0 h-40 w-40 bg-purple-500/5 rounded-full blur-3xl pointer-events-none" />

                        <div className="relative z-10 flex flex-col items-center max-w-2xl mx-auto space-y-6">
                            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                                Ready to run your entire operational workspace?
                            </h2>
                            <p className="text-sm text-slate-600 leading-relaxed font-normal">
                                Join thousands of modern firms, freelance agencies, and developers running precise billing, protected escrows, and robust wallets inside musoftware.
                            </p>
                            
                            <div className="pt-4 flex flex-col sm:flex-row gap-4 w-full justify-center">
                                <Link href="/register?trial=true" className="w-full sm:w-auto">
                                    <Button size="lg" className="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg shadow-lg border-0 h-12 text-sm font-semibold px-8 flex items-center justify-center gap-2 group">
                                        Start Your Free Trial
                                        <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                    </Button>
                                </Link>
                                <a href="mailto:hello@musoftwares.com?subject=Demo Inquiry" className="w-full sm:w-auto">
                                    <Button size="lg" variant="outline" className="w-full bg-white hover:bg-slate-100 text-slate-700 border-slate-250 rounded-lg px-8 h-12 text-sm font-semibold shadow-sm">
                                        Book Corporate Demo
                                    </Button>
                                </a>
                            </div>
                            <span className="text-[10px] text-slate-500 font-semibold">Free 14-day trial • Cancel anytime • Zero installation required</span>
                        </div>
                    </div>
                </div>
            </section>

        </PublicLayout>
    );
}
