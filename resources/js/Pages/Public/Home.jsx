import { useState, useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    Wallet, ShieldCheck, Clock, Check, ChevronDown, Sparkles, 
    ArrowRight, DollarSign, Activity, FileText, Send, UserCheck, 
    Layers, Star, HelpCircle, ArrowUpRight, TrendingUp, AlertTriangle, 
    Users, Briefcase, Plus, Lock, CheckCircle, RefreshCw,
    Terminal, Play, Pause, ChevronRight, Server, Shield, Laptop, BarChart2,
    CheckCircle2, Globe, SendHorizontal, Zap
} from 'lucide-react';

// ============================================================================
// MOTION VARIANCE CONFIGURATIONS (SaaS Motion System)
// ============================================================================
const fReveal = {
    hidden: { opacity: 0, y: 15 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.45, ease: [0.16, 1, 0.3, 1] } }
};

const fStagger = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { staggerChildren: 0.08 }
    }
};

const fFloat = (delay = 0) => ({
    animate: {
        y: [0, -8, 0],
        transition: {
            duration: 6,
            repeat: Infinity,
            repeatType: "reverse",
            ease: "easeInOut",
            delay: delay
        }
    }
});

export default function Home({ canLogin, canRegister }) {
    // ------------------------------------------------------------------------
    // GLOBAL STATE MANAGEMENT FOR LANDING PAGE INTERACTIONS
    // ------------------------------------------------------------------------
    const [billingPeriod, setBillingPeriod] = useState('monthly'); // 'monthly' | 'yearly'
    const [activeLedgerFlow, setActiveLedgerFlow] = useState('payment'); // 'stripe' | 'payment' | 'escrow' | 'tax'
    const [adminOpsTab, setAdminOpsTab] = useState('withdrawals'); // 'withdrawals' | 'moderation' | 'fraud'
    
    // Live Ticking Billing Timer State
    const [timerSeconds, setTimerSeconds] = useState(2572); // starts at 42m 52s
    const [isTimerRunning, setIsTimerRunning] = useState(true);

    // Dynamic FAQ state
    const [faqExpanded, setFaqExpanded] = useState({
        0: true, // first open by default
        1: false,
        2: false,
        3: false,
        4: false,
        5: false
    });

    // Chat Interactive State
    const [chatInput, setChatInput] = useState('');
    const [chatMessages, setChatMessages] = useState([
        { sender: 'client', time: '10:42 AM', text: "Just funded the milestone escrow for the API integration ($4,500.00)." },
        { sender: 'system', time: '10:42 AM', text: "[Escrow Locked] $4,500.00 secured in TLS Vault. Work authorized." },
        { sender: 'developer', time: '10:43 AM', text: "Received notification. Running integration test scripts on sandbox env..." }
    ]);

    // Admin Withdrawals List State (Interactive approval queue)
    const [withdrawalQueue, setWithdrawalQueue] = useState([
        { id: 'W-9024', entity: 'PixelCraft Studio', amount: '$4,850.00', status: 'pending', method: 'Stripe Instant' },
        { id: 'W-9025', entity: 'Apex Logistics Inc.', amount: '$12,400.00', status: 'pending', method: 'ACH Transfer' },
        { id: 'W-9026', entity: 'DevFlow LLC', amount: '$1,950.00', status: 'approved', method: 'Stripe Instant' }
    ]);

    // Client Dashboard checkout modal trigger
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);
    const [paymentSuccess, setPaymentSuccess] = useState(false);

    // Timer Tick-up Effect
    useEffect(() => {
        let interval = null;
        if (isTimerRunning) {
            interval = setInterval(() => {
                setTimerSeconds(prev => prev + 1);
            }, 1000);
        }
        return () => clearInterval(interval);
    }, [isTimerRunning]);

    // Format timer to HH:MM:SS
    const formatTimer = (totalSeconds) => {
        const hrs = Math.floor(totalSeconds / 3600);
        const mins = Math.floor((totalSeconds % 3600) / 60);
        const secs = totalSeconds % 60;
        return [
            hrs.toString().padStart(2, '0'),
            mins.toString().padStart(2, '0'),
            secs.toString().padStart(2, '0')
        ].join(':');
    };

    // FAQ Toggle Handler
    const toggleFaq = (idx) => {
        setFaqExpanded(prev => ({ ...prev, [idx]: !prev[idx] }));
    };

    // Chat Send message handler
    const sendChatMessage = (e) => {
        e.preventDefault();
        if (!chatInput.trim()) return;
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        setChatMessages(prev => [
            ...prev,
            { sender: 'developer', time: timeStr, text: chatInput }
        ]);
        setChatInput('');
    };

    // Admin approval toggle action
    const approveWithdrawal = (id) => {
        setWithdrawalQueue(prev => prev.map(item => {
            if (item.id === id) {
                return { ...item, status: 'approved' };
            }
            return item;
        }));
    };

    // Client checkout handler
    const triggerClientCheckout = () => {
        setPaymentSuccess(true);
        setTimeout(() => {
            setPaymentModalOpen(false);
            setPaymentSuccess(false);
        }, 1800);
    };

    // Ledgers Dynamic Data corresponding to states
    const ledgersData = {
        stripe: [
            { label: 'Merchant Escrow Locked', account: 'Wallet 1092', amount: '+$14,250.00', type: 'credit' },
            { label: 'Stripe Settlement Charge (1.8%)', account: 'Fee Ledger', amount: '-$256.50', type: 'debit' },
            { label: 'Developer Net Escrow Alloc', account: 'Vault Reserve', amount: '+$13,993.50', type: 'credit' }
        ],
        payment: [
            { label: 'Invoice #1094 Settle (Acme)', account: 'Receivables', amount: '+$3,200.00', type: 'credit' },
            { label: 'Internal Partner Tax Reserve (10%)', account: 'Tax Reserve', amount: '-$320.00', type: 'debit' },
            { label: 'Client General Balance Deposit', account: 'Wallet 2240', amount: '+$2,880.00', type: 'credit' }
        ],
        escrow: [
            { label: 'Marketplace Escrow Hold (Contract 84)', account: 'TLS Escrow Chamber', amount: '+$8,500.00', type: 'credit' },
            { label: 'Milestone 1 Release Verification', account: 'TLS Escrow Chamber', amount: '-$2,500.00', type: 'debit' },
            { label: 'Escrow Released to Developer Wallet', account: 'Wallet 4820', amount: '+$2,500.00', type: 'credit' }
        ],
        tax: [
            { label: 'Contract Automated Tax Split', account: 'Inbound Revenue', amount: '+$6,000.00', type: 'credit' },
            { label: 'IRS W-8BEN Automatic Holdback (20%)', account: 'Federal Tax Reserve', amount: '-$1,200.00', type: 'debit' },
            { label: 'Net Disbursable Balance Allocation', account: 'General Ledger', amount: '+$4,800.00', type: 'credit' }
        ]
    };

    return (
        <PublicLayout>
            <Head>
                <title>musoftware — Unified ERP, Wallets, Escrows & Client Portals</title>
                <meta name="description" content="Stop forcing detached ERPs, wallets, billing tools, and freelance boards to sync. Manage invoices, client portals, escrows, timelogs, and wallets inside a unified ecosystem." />
            </Head>

            {/* ====================================================
                SECTION 1: HERO SECTION (Premium Layered React UI)
               ==================================================== */}
            <section id="hero" className="relative min-h-[95vh] flex items-center justify-center overflow-hidden bg-slate-50 pt-24 pb-20 border-b border-slate-200/80">
                <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none">
                    <div className="absolute top-[-15%] left-[-15%] w-[60%] aspect-square bg-radial from-indigo-500/8 to-transparent rounded-full blur-3xl" />
                    <div className="absolute bottom-[5%] right-[-15%] w-[60%] aspect-square bg-radial from-purple-500/6 to-transparent rounded-full blur-3xl" />
                </div>

                <div className="relative z-10 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    {/* Left Column: Headline, Description & CTAs */}
                    <div className="lg:col-span-6 flex flex-col space-y-8 text-left max-w-2xl mx-auto lg:mx-0">
                        {/* Premium SaaS Tagline */}
                        <div className="inline-flex items-center gap-2 w-fit rounded-full bg-indigo-50 border border-indigo-100 px-3.5 py-1.5 text-xs text-indigo-900 backdrop-blur-sm shadow-xs">
                            <span className="flex h-2.5 w-2.5 rounded-full bg-indigo-600 animate-pulse" />
                            <span className="font-bold">Enterprise Engine</span>
                            <span className="text-indigo-200">|</span>
                            <span className="text-indigo-750 font-medium">Continuous Ledger Balance v4.0</span>
                        </div>

                        <h1 className="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.08] font-sans">
                            Run your clients, billing, services, and operations from <span className="bg-gradient-to-r from-indigo-650 via-purple-650 to-indigo-650 bg-clip-text text-transparent">one workspace.</span>
                        </h1>

                        <p className="text-base sm:text-lg text-slate-600 leading-relaxed font-normal">
                            Stop forcing detached ERPs, client portals, freelance boards, and billing tools to talk. Unify deep ledger accounting, escrowed transactions, custom client spaces, and live operations in a highly structured, enterprise-grade business environment.
                        </p>

                        {/* Professional CTA Action Buttons */}
                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link href="/register?trial=true" className="w-full sm:w-auto">
                                <Button size="lg" className="w-full bg-gradient-to-r from-indigo-500 to-indigo-650 hover:opacity-95 text-white rounded-lg shadow-lg shadow-indigo-500/10 border-0 px-8 h-12.5 text-sm font-semibold flex items-center justify-center gap-2 group cursor-pointer">
                                    Start Free 14-Day Trial
                                    <ArrowRight className="h-4.5 w-4.5 group-hover:translate-x-1 transition-transform" />
                                </Button>
                            </Link>
                            <a href="#workspace" className="w-full sm:w-auto">
                                <Button size="lg" variant="outline" className="w-full bg-white hover:bg-slate-50 text-slate-700 border-slate-200 rounded-lg px-8 h-12.5 text-sm font-semibold shadow-xs cursor-pointer">
                                    Explore Core Workspace
                                </Button>
                            </a>
                        </div>

                        {/* Trust Badges */}
                        <div className="pt-6 border-t border-slate-200 flex flex-wrap items-center gap-6 text-slate-500 text-xs">
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5 text-emerald-600 shrink-0" />
                                <span className="text-slate-650 font-bold">Secured double-entry audit protection</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Check className="h-5 w-5 text-indigo-650 shrink-0" />
                                <span className="text-slate-655 font-semibold">No initial setup fees or lock-ins</span>
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Premium High-Density Layered React UI Composition */}
                    <div className="lg:col-span-6 relative w-full h-[540px] flex items-center justify-center">
                        <div className="relative w-full max-w-lg h-full">
                            
                            {/* Layer 1: Core Financial Dashboard Panel (Base Layer) */}
                            <motion.div 
                                variants={fFloat(0)}
                                animate="animate"
                                className="absolute top-[8%] left-[2%] w-[88%] bg-white rounded-xl border border-slate-200 shadow-xl p-5 z-10"
                            >
                                <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                                    <div className="flex items-center gap-2">
                                        <div className="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                                        <span className="text-xs font-bold text-slate-900">SYSTEM LEDGER ACTIVE</span>
                                    </div>
                                    <span className="text-[10px] font-mono text-slate-400 bg-slate-50 px-2 py-0.5 rounded border">ID: L-98242</span>
                                </div>
                                <div className="grid grid-cols-2 gap-4 py-4">
                                    <div>
                                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Wallet Balance</span>
                                        <span className="text-2xl font-extrabold text-slate-900 font-mono">$184,950.00</span>
                                        <span className="text-[9px] text-emerald-600 font-semibold flex items-center gap-0.5 mt-0.5">
                                            <TrendingUp className="h-3 w-3" /> +12.4% this month
                                        </span>
                                    </div>
                                    <div className="border-l border-slate-100 pl-4">
                                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">TLS Escrow Locked</span>
                                        <span className="text-2xl font-extrabold text-indigo-600 font-mono">$52,800.00</span>
                                        <span className="text-[9px] text-slate-500 font-medium block mt-0.5">Across 8 active contracts</span>
                                    </div>
                                </div>
                            </motion.div>

                            {/* Layer 2: Invoice Settled Panel (Floating Right) */}
                            <motion.div 
                                variants={fFloat(1.2)}
                                animate="animate"
                                className="absolute bottom-[36%] right-[2%] w-[72%] bg-slate-950 text-white rounded-xl border border-slate-800 shadow-2xl p-4 z-20"
                            >
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-2">
                                        <div className="h-6 w-6 rounded-md bg-indigo-500 flex items-center justify-center">
                                            <FileText className="h-3.5 w-3.5 text-white" />
                                        </div>
                                        <div>
                                            <h4 className="text-[11px] font-extrabold block">Invoice Settled</h4>
                                            <span className="text-[8px] text-slate-450 block font-mono">INV-1092 • Vercel Inc.</span>
                                        </div>
                                    </div>
                                    <span className="text-[10px] font-bold text-emerald-450 bg-emerald-500/10 border border-emerald-500/25 px-2 py-0.5 rounded-full">
                                        +$12,450.00
                                    </span>
                                </div>
                                <div className="bg-slate-900 rounded-lg p-2.5 text-[9px] font-mono text-slate-350 space-y-1">
                                    <div className="flex justify-between">
                                        <span>Subtotal:</span>
                                        <span>$12,450.00</span>
                                    </div>
                                    <div className="flex justify-between text-indigo-400">
                                        <span>Wallet Dep (90%):</span>
                                        <span>$11,205.00</span>
                                    </div>
                                    <div className="flex justify-between text-purple-400">
                                        <span>Escrow Comm (10%):</span>
                                        <span>$1,245.00</span>
                                    </div>
                                </div>
                            </motion.div>

                            {/* Layer 3: Live Realtime Chat notification (Floating Left Bottom) */}
                            <motion.div 
                                variants={fFloat(2.5)}
                                animate="animate"
                                className="absolute bottom-[8%] left-[4%] w-[68%] bg-white rounded-xl border border-slate-200 shadow-lg p-3.5 z-30"
                            >
                                <div className="flex items-center gap-2 mb-2">
                                    <div className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                                    <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">REALTIME CONTRACT EVENTS</span>
                                </div>
                                <div className="flex gap-2 items-start text-xs">
                                    <div className="h-6.5 w-6.5 rounded-full bg-purple-100 flex items-center justify-center font-bold text-[10px] text-purple-650 shrink-0">
                                        PC
                                    </div>
                                    <div className="space-y-0.5">
                                        <p className="text-[11px] font-semibold text-slate-900 leading-tight">PixelCraft proposals accepted by Client</p>
                                        <span className="text-[9px] text-slate-400 font-mono block">$8,500.00 escrow secured inside vault</span>
                                    </div>
                                </div>
                            </motion.div>

                            {/* Layer 4: Floating Secure Lock Badge */}
                            <motion.div 
                                variants={fFloat(0.6)}
                                animate="animate"
                                className="absolute top-[48%] right-[8%] bg-white border border-slate-200 rounded-lg p-2.5 shadow-md flex items-center gap-2 z-25"
                            >
                                <div className="h-7 w-7 rounded-md bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                    <Lock className="h-4 w-4 text-emerald-600" />
                                </div>
                                <div>
                                    <span className="text-[9px] text-slate-400 font-bold block">TLS VAULT LOCK</span>
                                    <span className="text-[10px] text-slate-900 font-bold block">Secure Escrow Shield</span>
                                </div>
                            </motion.div>
                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                SECTION 2: COMPACT SOCIAL PROOF SECTION (Hard Metrics Strip)
               ==================================================== */}
            <section id="proof" className="bg-slate-900 py-8 border-y border-slate-800 text-white relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8 divide-y md:divide-y-0 md:divide-x divide-slate-800 text-center">
                        <div className="flex flex-col items-center justify-center p-3">
                            <span className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white font-mono">14,500+</span>
                            <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1.5">Active Corporate Entities</span>
                        </div>
                        <div className="flex flex-col items-center justify-center p-3 pt-6 md:pt-3">
                            <span className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white font-mono">$184.2M+</span>
                            <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1.5">Ledger Invoices Settled</span>
                        </div>
                        <div className="flex flex-col items-center justify-center p-3 pt-6 md:pt-3">
                            <span className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white font-mono">112,400+</span>
                            <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1.5">Completed Escrow Milestones</span>
                        </div>
                        <div className="flex flex-col items-center justify-center p-3 pt-6 md:pt-3">
                            <span className="text-3xl sm:text-4xl font-extrabold tracking-tight text-emerald-400 font-mono">$52.8M+</span>
                            <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1.5">Escrow Locked Volume</span>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 3: PLATFORM OVERVIEW (4 Pillars with Mini Mocks)
               ==================================================== */}
            <section id="overview" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-20">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-600 block">Ecosystem Architecture</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            The Four Pillars of the Unified Workspace
                        </h2>
                        <p className="mt-4 text-sm sm:text-base text-slate-550 leading-relaxed font-normal">
                            Stop running multi-thousand dollar business operations across loose configurations of generic SaaS products. Bind core functional frameworks under a single ledger-secure state database.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                        
                        {/* Pillar 1: Financial Infrastructure */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-all duration-300 shadow-xs group">
                            <div className="space-y-5">
                                <div className="h-12 w-12 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center mb-6">
                                    <Wallet className="h-6 w-6 text-indigo-600" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900">1. Financial Ledger & Wallet Operations</h3>
                                <p className="text-slate-600 text-sm leading-relaxed font-normal">
                                    Automate client billing, double-entry ledger balancing, multi-currency wallets, automatic tax withholdings, and Stripe-precision deposits.
                                </p>
                                <ul className="space-y-2 pt-2">
                                    {['Double-entry ledger tracks every cent', 'TLS-encrypted Escrow Vault locks funds', 'Instant settlement via card, wire, and ACH'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700 font-semibold">
                                            <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            
                            {/* Mini UI Mock */}
                            <div className="mt-8 bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
                                <div className="flex items-center justify-between mb-3 text-[10px] text-slate-450 font-bold font-mono">
                                    <span>LEDGER SPLIT CONTROL</span>
                                    <span className="text-emerald-600">ACTIVE</span>
                                </div>
                                <div className="space-y-2">
                                    <div className="flex justify-between items-center text-xs">
                                        <span className="text-slate-600 font-medium">Project Inbound Invoice #1024</span>
                                        <span className="font-mono text-slate-900 font-bold">$10,000.00</span>
                                    </div>
                                    <div className="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden flex">
                                        <div className="h-full bg-indigo-500" style={{ width: '70%' }} />
                                        <div className="h-full bg-purple-500" style={{ width: '20%' }} />
                                        <div className="h-full bg-emerald-500" style={{ width: '10%' }} />
                                    </div>
                                    <div className="flex justify-between text-[9px] text-slate-450 font-mono">
                                        <span>70% Wallet ($7.0k)</span>
                                        <span>20% Partner ($2.0k)</span>
                                        <span>10% Tax Reserve ($1.0k)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Pillar 2: Client Workspace */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-all duration-300 shadow-xs group">
                            <div className="space-y-5">
                                <div className="h-12 w-12 rounded-lg bg-purple-50 border border-purple-100 flex items-center justify-center mb-6">
                                    <UserCheck className="h-6 w-6 text-purple-650" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900">2. White-Labeled Client Portals</h3>
                                <p className="text-slate-600 text-sm leading-relaxed font-normal">
                                    Provide clients with premium, high-density workspaces. Clients fund wallets, pay invoices, direct contracts, and track ongoing operational schedules under your brand.
                                </p>
                                <ul className="space-y-2 pt-2">
                                    {['Custom domain mapping with auto-SSL', 'Real-time billing timecard verification', 'Centralized ticketing and active milestones'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700 font-semibold">
                                            <Check className="h-4.5 w-4.5 text-purple-650 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Mini UI Mock */}
                            <div className="mt-8 bg-white border border-slate-200 rounded-lg p-4 shadow-sm text-xs space-y-2.5">
                                <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                    <span className="font-bold text-slate-900">Acme Corp Portal</span>
                                    <span className="text-[10px] text-indigo-650 bg-indigo-50 px-2 py-0.5 rounded font-bold font-mono">portal.acme.com</span>
                                </div>
                                <div className="flex justify-between items-center text-[11px]">
                                    <span className="text-slate-655 font-medium">Pending Timecard Invoice</span>
                                    <button onClick={() => setPaymentModalOpen(true)} className="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] cursor-pointer">
                                        Pay $3,200.00
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Pillar 3: Marketplace & Freelance */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-all duration-300 shadow-xs group">
                            <div className="space-y-5">
                                <div className="h-12 w-12 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-6">
                                    <Layers className="h-6 w-6 text-emerald-600" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900">3. Built-In Marketplace Infrastructure</h3>
                                <p className="text-slate-600 text-sm leading-relaxed font-normal">
                                    Launch fixed-price services (Starter, Pro, Elite) or pitch detailed bids on custom RFPs. Secure contracts automatically using our native TLS-encrypted escrow vault.
                                </p>
                                <ul className="space-y-2 pt-2">
                                    {['Productized services list structure', 'Flexible RFP bidding and proposals engine', 'Automated milestone-based release steps'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700 font-semibold">
                                            <Check className="h-4.5 w-4.5 text-emerald-600 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Mini UI Mock */}
                            <div className="mt-8 bg-white border border-slate-200 rounded-lg p-4 shadow-sm text-xs space-y-2.5">
                                <div className="flex justify-between items-center">
                                    <span className="font-bold text-slate-900">Fixed-Price Service: API Setup</span>
                                    <span className="font-mono font-bold text-slate-700">$4,500</span>
                                </div>
                                <div className="flex justify-between items-center text-[10px] text-slate-500 font-semibold bg-slate-50 p-2 rounded border border-dashed">
                                    <span className="flex items-center gap-1.5"><Lock className="h-3 w-3 text-emerald-600" /> Escrow Status: Secured in Vault</span>
                                    <span>Milestone 1/2</span>
                                </div>
                            </div>
                        </div>

                        {/* Pillar 4: Timers & Communications */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-all duration-300 shadow-xs group">
                            <div className="space-y-5">
                                <div className="h-12 w-12 rounded-lg bg-orange-50 border border-orange-100 flex items-center justify-center mb-6">
                                    <Activity className="h-6 w-6 text-orange-650" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900">4. Precise Timers & Audit Logs</h3>
                                <p className="text-slate-600 text-sm leading-relaxed font-normal">
                                    Track timed client operations directly from your toolbar. Paused logs immediately compile into ledger invoice drafts, removing administrative lag.
                                </p>
                                <ul className="space-y-2 pt-2">
                                    {['Chronological timer ticks directly to invoice', 'Detailed, non-editable client timecard logs', 'Linked workspace chat for quick audits'].map((item) => (
                                        <li key={item} className="flex items-center gap-2.5 text-xs text-slate-700 font-semibold">
                                            <Check className="h-4.5 w-4.5 text-orange-650 shrink-0" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Mini UI Mock */}
                            <div className="mt-8 bg-slate-950 text-white border border-slate-900 rounded-lg p-4 shadow-sm text-xs flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-2.5 w-2.5 rounded-full bg-orange-500 animate-pulse" />
                                    <div>
                                        <span className="text-[9px] text-slate-450 block uppercase font-bold tracking-wider">ACTIVE TIMECARD</span>
                                        <span className="text-xs font-mono font-bold">API INTEGRATION BILLING</span>
                                    </div>
                                </div>
                                <span className="font-mono text-sm font-bold text-orange-400 bg-orange-500/10 border border-orange-500/25 px-2.5 py-1 rounded">
                                    {formatTimer(timerSeconds)}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 4: FINANCIAL WORKFLOWS (Interactive Ledger)
               ==================================================== */}
            <section id="financials" className="bg-slate-50/50 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Financial Copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-bold uppercase tracking-widest text-emerald-600 block">Unified Ledgers</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Stripe-precision wallets, contract deposits, and automated splits
                        </h2>
                        <p className="text-slate-600 text-sm sm:text-base leading-relaxed font-normal">
                            Stop exposing your accounting to manual input errors. Track financial pipelines with extreme precision. Define split structures to automatically route taxes, referral commisions, and partner fees immediately when an invoice is settled.
                        </p>
                        
                        {/* Interactive flow navigation tabs */}
                        <div className="grid grid-cols-2 gap-2 bg-slate-100 p-1.5 rounded-lg border border-slate-200">
                            {[
                                { id: 'stripe', label: 'Stripe Settlement' },
                                { id: 'payment', label: 'Invoice Payment' },
                                { id: 'escrow', label: 'Escrow Release' },
                                { id: 'tax', label: 'Automated Tax Split' }
                            ].map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveLedgerFlow(tab.id)}
                                    className={`py-2 text-center text-[11px] font-bold rounded-md transition-all cursor-pointer ${
                                        activeLedgerFlow === tab.id
                                            ? 'bg-white text-slate-900 shadow-sm border border-slate-200'
                                            : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Right: Interactive Ledger Panel */}
                    <div className="lg:col-span-7 bg-white border border-slate-200 rounded-xl shadow-xl p-6 relative overflow-hidden">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                            <div>
                                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">LEDGER AUDIT STREAM</h4>
                                <span className="text-sm font-extrabold text-slate-900">Double-Entry Journal Entry</span>
                            </div>
                            <span className="text-[10px] font-mono text-indigo-650 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded font-bold uppercase">
                                STATUS: BALANCED
                            </span>
                        </div>

                        {/* Interactive Ledger list view */}
                        <div className="space-y-3.5 relative min-h-[176px]">
                            <AnimatePresence mode="wait">
                                <motion.div
                                    key={activeLedgerFlow}
                                    initial={{ opacity: 0, x: 10 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    exit={{ opacity: 0, x: -10 }}
                                    transition={{ duration: 0.25 }}
                                    className="space-y-3"
                                >
                                    {ledgersData[activeLedgerFlow].map((log, idx) => (
                                        <div key={idx} className="flex justify-between items-center border border-slate-100 rounded-lg p-3 hover:bg-slate-50/50 transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className={`h-8 w-8 rounded-md flex items-center justify-center shrink-0 font-extrabold text-xs font-mono border ${
                                                    log.type === 'credit'
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                                        : 'bg-rose-50 text-rose-700 border-rose-100'
                                                }`}>
                                                    {log.type === 'credit' ? 'DR' : 'CR'}
                                                </div>
                                                <div>
                                                    <span className="text-xs font-bold text-slate-900 block leading-snug">{log.label}</span>
                                                    <span className="text-[9px] text-slate-450 block font-mono">Target: {log.account}</span>
                                                </div>
                                            </div>
                                            <span className={`text-xs font-bold font-mono ${
                                                log.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'
                                            }`}>
                                                {log.amount}
                                            </span>
                                        </div>
                                    ))}
                                </motion.div>
                            </AnimatePresence>
                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                SECTION 5: MARKETPLACE & FREELANCE (Operational Kanban Board)
               ==================================================== */}
            <section id="marketplace" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div id="freelance" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-bold uppercase tracking-widest text-purple-650 block">Marketplace Board</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Operational freelance pipelines and active escrow tracking
                        </h2>
                        <p className="mt-4 text-sm text-slate-550 leading-relaxed font-normal">
                            Skip basic, static listings. Engage with an absolute operational board depicting active proposals, funded escrows, and timed work items.
                        </p>
                    </div>

                    {/* Operational Kanban Board Layout */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-start max-w-6xl mx-auto">
                        
                        {/* Column 1: RFP & Proposals (Proposed) */}
                        <div className="bg-slate-50/70 border border-slate-200/80 rounded-xl p-4">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                                <div className="flex items-center gap-2">
                                    <span className="h-2 w-2 rounded-full bg-amber-500" />
                                    <span className="text-xs font-extrabold text-slate-900">PROPOSALS REVIEW</span>
                                </div>
                                <span className="text-[10px] font-mono font-bold text-slate-450 bg-white border px-2 py-0.5 rounded">2 Active</span>
                            </div>
                            <div className="space-y-3">
                                <div className="bg-white border border-slate-200 rounded-lg p-3.5 shadow-xs space-y-3">
                                    <span className="text-[9px] font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded font-mono uppercase">CUSTOM RFP</span>
                                    <h4 className="text-xs font-bold text-slate-900 mt-1">E-Commerce Portal Sync</h4>
                                    <div className="flex justify-between items-center text-[10px] text-slate-500 mt-2 font-semibold">
                                        <span>Bid Total: $8,500</span>
                                        <span>2 Bids Recv</span>
                                    </div>
                                    <div className="pt-2.5 border-t border-slate-100 flex justify-between items-center">
                                        <div className="flex -space-x-1.5">
                                            <div className="h-5.5 w-5.5 rounded-full bg-indigo-100 border border-white text-[8px] font-bold flex items-center justify-center text-indigo-700">A</div>
                                            <div className="h-5.5 w-5.5 rounded-full bg-purple-100 border border-white text-[8px] font-bold flex items-center justify-center text-purple-700">M</div>
                                        </div>
                                        <span className="text-[9px] text-slate-400 font-mono">Updated 10m ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Column 2: Escrow Secured (Vault Lock) */}
                        <div className="bg-slate-50/70 border border-slate-200/80 rounded-xl p-4">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                                <div className="flex items-center gap-2">
                                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                    <span className="text-xs font-extrabold text-slate-900">ESCROW VAULT SECURED</span>
                                </div>
                                <span className="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded">1 Locked</span>
                            </div>
                            <div className="space-y-3">
                                <div className="bg-white border-2 border-emerald-500 rounded-lg p-3.5 shadow-sm space-y-3 relative overflow-hidden">
                                    <div className="absolute top-0 right-0 h-12 w-12 bg-emerald-500/5 rounded-bl-full flex items-center justify-center pointer-events-none">
                                        <Lock className="h-4 w-4 text-emerald-600 opacity-60" />
                                    </div>
                                    <span className="text-[9px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded font-mono uppercase">TLS LOCKED</span>
                                    <h4 className="text-xs font-bold text-slate-900 mt-1">API Webhook Handler</h4>
                                    <div className="flex justify-between items-center text-[10px] text-slate-500 mt-2 font-semibold">
                                        <span className="text-emerald-700 font-bold">Escrowed: $4,500</span>
                                        <span>Milestone 1/2</span>
                                    </div>
                                    <div className="pt-2.5 border-t border-slate-100 flex justify-between items-center">
                                        <span className="text-[9px] font-mono text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded font-bold uppercase">WORK AUTHORIZED</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Column 3: Completed & Released (Disbursed) */}
                        <div className="bg-slate-50/70 border border-slate-200/80 rounded-xl p-4">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                                <div className="flex items-center gap-2">
                                    <span className="h-2 w-2 rounded-full bg-indigo-500" />
                                    <span className="text-xs font-extrabold text-slate-900">DISBURSED & CLEAR</span>
                                </div>
                                <span className="text-[10px] font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded">Completed</span>
                            </div>
                            <div className="space-y-3">
                                <div className="bg-white border border-slate-200 rounded-lg p-3.5 shadow-xs space-y-3">
                                    <span className="text-[9px] font-bold text-slate-500 bg-slate-50 border px-2 py-0.5 rounded font-mono uppercase">RELEASED</span>
                                    <h4 className="text-xs font-bold text-slate-900 mt-1">Database Migrations setup</h4>
                                    <div className="flex justify-between items-center text-[10px] text-slate-500 mt-2 font-semibold">
                                        <span>Total Cleared: $2,500</span>
                                        <span>Settled</span>
                                    </div>
                                    <div className="pt-2.5 border-t border-slate-100 flex justify-between items-center">
                                        <span className="text-[9px] text-indigo-600 font-semibold flex items-center gap-1">
                                            <CheckCircle className="h-3.5 w-3.5" /> Wallet Credited
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 6: UNIFIED WORKSPACE ("One Customer" Core Dashboard)
               ==================================================== */}
            <section id="workspace" className="bg-slate-50/50 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Section Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-650 block">Unified Workspace</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            One Customer. One Operational Workspace.
                        </h2>
                        <p className="mt-4 text-sm text-slate-550 leading-relaxed font-normal">
                            Unify client relations, financial balances, active support logs, and private internal notes under a single customer profile directory.
                        </p>
                    </div>

                    {/* Central Large Mock Dashboard */}
                    <div className="bg-white border border-slate-200 rounded-xl shadow-2xl max-w-6xl mx-auto overflow-hidden">
                        
                        {/* Mock App Header */}
                        <div className="bg-slate-900 text-white px-6 py-4 flex items-center justify-between border-b border-slate-800">
                            <div className="flex items-center gap-3">
                                <div className="h-8 w-8 rounded-lg bg-indigo-500 flex items-center justify-center">
                                    <Users className="h-4.5 w-4.5 text-white" />
                                </div>
                                <div>
                                    <h3 className="text-xs font-extrabold block uppercase tracking-wide">Musoftware Core Workspace</h3>
                                    <span className="text-[10px] text-slate-450 block font-mono">Directory / Client Profiles / Acme Corporation</span>
                                </div>
                            </div>
                            <span className="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/25 px-2.5 py-1 rounded">
                                SYSTEM CONNECTION SECURED (TLS)
                            </span>
                        </div>

                        {/* Mock Main Dashboard Layout Grid */}
                        <div className="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
                            
                            {/* Left Panel: Profile Detail Column (3/12 width) */}
                            <div className="lg:col-span-3 p-5 space-y-6">
                                <div>
                                    <div className="h-16 w-16 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center font-extrabold text-xl text-indigo-700 shadow-xs mb-3">
                                        AC
                                    </div>
                                    <h4 className="text-sm font-bold text-slate-900 block leading-tight">Acme Corporation</h4>
                                    <span className="text-[10px] text-slate-450 block font-mono mt-0.5">client_id: C-984242</span>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <span className="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Wallet Currency</span>
                                        <span className="text-xs font-bold text-slate-900 mt-0.5 block">USD / Multicurrency enabled</span>
                                    </div>
                                    <div>
                                        <span className="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Billing Status</span>
                                        <span className="text-xs font-semibold text-emerald-650 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded w-fit block mt-0.5 font-mono">
                                            ACTIVE PLAN: BUSINESS
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Escrows Closed</span>
                                        <span className="text-xs font-bold text-slate-900 mt-0.5 block font-mono">14 Completed ($128.4k volume)</span>
                                    </div>
                                </div>
                            </div>

                            {/* Center Panel: Sub-metrics & Transaction Records (5/12 width) */}
                            <div className="lg:col-span-5 p-5 space-y-6">
                                <div>
                                    <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wide mb-3">CONNECTED INVOICES & ESCROWS</h4>
                                    <div className="space-y-2.5">
                                        <div className="flex justify-between items-center border border-slate-100 rounded-lg p-3 hover:bg-slate-50/50 transition-colors">
                                            <div>
                                                <span className="text-xs font-bold text-slate-900 block">INV-1094 • Timecard Settle</span>
                                                <span className="text-[9px] text-slate-450 font-mono block">Milestone 1 Release</span>
                                            </div>
                                            <span className="text-xs font-mono font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">$3,200.00</span>
                                        </div>
                                        <div className="flex justify-between items-center border border-slate-100 rounded-lg p-3 hover:bg-slate-50/50 transition-colors">
                                            <div>
                                                <span className="text-xs font-bold text-slate-900 block">INV-1092 • Corporate Consultation</span>
                                                <span className="text-[9px] text-slate-450 font-mono block">Paid via Stripe CC</span>
                                            </div>
                                            <span className="text-xs font-mono font-bold text-slate-700 bg-slate-50 px-2 py-0.5 rounded border">$12,450.00</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-5">
                                    <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wide mb-3">ACTIVE SUPPORT DISPUTES</h4>
                                    <div className="flex items-center gap-3 border border-indigo-100 bg-indigo-50/30 rounded-lg p-3">
                                        <div className="h-6.5 w-6.5 rounded-full bg-indigo-50 border border-indigo-200 flex items-center justify-center shrink-0">
                                            <CheckCircle2 className="h-4.5 w-4.5 text-indigo-650" />
                                        </div>
                                        <div>
                                            <span className="text-xs font-bold text-slate-900 block">All Tickets Cleared</span>
                                            <span className="text-[9px] text-slate-500 font-medium block leading-tight">No open disputes or pending escrow lock issues.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Right Panel: Operations Logs & Timelines (4/12 width) */}
                            <div className="lg:col-span-4 p-5 space-y-6">
                                <div>
                                    <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wide mb-3">ADMINISTRATIVE ACTION LOG</h4>
                                    <div className="bg-slate-50 border border-slate-200 rounded-lg p-3 space-y-2">
                                        <div className="flex items-start gap-2 text-[10px] text-slate-655">
                                            <Terminal className="h-3.5 w-3.5 text-indigo-600 shrink-0 mt-0.5" />
                                            <p className="font-mono">
                                                <span className="text-indigo-650 font-bold">[10:42 AM]</span> Escrow Vault locked $4,500.00 for Milestone 1.
                                            </p>
                                        </div>
                                        <div className="flex items-start gap-2 text-[10px] text-slate-655">
                                            <Terminal className="h-3.5 w-3.5 text-indigo-600 shrink-0 mt-0.5" />
                                            <p className="font-mono">
                                                <span className="text-indigo-650 font-bold">[09:12 AM]</span> Client mapping portal: portal.acme.com verified and SSL active.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-5">
                                    <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wide mb-3">AUDIT TIMELINE</h4>
                                    <div className="space-y-4 text-xs pl-3 border-l-2 border-slate-100">
                                        <div className="relative">
                                            <span className="absolute -left-[17px] top-[3px] h-2.5 w-2.5 rounded-full bg-emerald-500 ring-4 ring-white" />
                                            <span className="font-bold text-slate-900 block leading-none">Invoice #1094 Paid</span>
                                            <span className="text-[9px] text-slate-400 block font-mono">Today, 10:44 AM</span>
                                        </div>
                                        <div className="relative">
                                            <span className="absolute -left-[17px] top-[3px] h-2.5 w-2.5 rounded-full bg-indigo-500 ring-4 ring-white" />
                                            <span className="font-bold text-slate-900 block leading-none">Escrow Vault Milestone Locked</span>
                                            <span className="text-[9px] text-slate-400 block font-mono">Today, 10:42 AM</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 7: REALTIME COMMUNICATION (Interactive Slack Feed & Live Timer)
               ==================================================== */}
            <section id="communication" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Chat Illustration/Copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-bold uppercase tracking-widest text-orange-650 block">Realtime Heartbeat</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Live system streams, communications, and chronological billing
                        </h2>
                        <p className="text-slate-600 text-sm sm:text-base leading-relaxed font-normal">
                            Unify your active workspace communications with functional notifications. Watch system automated alerts log timer activities, document deposits, and milestone settlements directly to client threads.
                        </p>
                        
                        {/* High fidelity Timer Controller Panel */}
                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold text-slate-900 uppercase tracking-wide">ACTIVE INTEGRATION TIMECARD</span>
                                <span className="text-[10px] font-mono font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded border border-orange-100">
                                    STATE: BILLING ON
                                </span>
                            </div>
                            <div className="flex justify-between items-center bg-slate-950 text-white rounded-lg px-4 py-3 border border-slate-900">
                                <div className="flex items-center gap-2">
                                    <span className={`h-2.5 w-2.5 rounded-full bg-orange-500 ${isTimerRunning ? 'animate-pulse' : ''}`} />
                                    <span className="text-xs font-mono font-bold">API Integrations</span>
                                </div>
                                <span className="text-lg font-mono font-extrabold tracking-widest text-orange-400">
                                    {formatTimer(timerSeconds)}
                                </span>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    onClick={() => setIsTimerRunning(!isTimerRunning)}
                                    className="flex-1 py-2 px-3 rounded-lg bg-white border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs hover:bg-slate-50 transition-colors cursor-pointer"
                                >
                                    {isTimerRunning ? (
                                        <>
                                            <Pause className="h-3.5 w-3.5" /> Pause Billing Timecard
                                        </>
                                    ) : (
                                        <>
                                            <Play className="h-3.5 w-3.5" /> Resume Billing Timecard
                                        </>
                                    )}
                                </button>
                                <button
                                    onClick={() => setTimerSeconds(0)}
                                    className="py-2 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200 font-bold text-xs cursor-pointer"
                                    title="Reset Timecard"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Right: Interactive Chat window */}
                    <div className="lg:col-span-7 bg-slate-900 text-white rounded-xl shadow-2xl overflow-hidden border border-slate-800 flex flex-col h-[480px]">
                        
                        {/* Chat header */}
                        <div className="bg-slate-950 px-5 py-4 border-b border-slate-800 flex items-center justify-between shrink-0">
                            <div className="flex items-center gap-2.5">
                                <div className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                                <h4 className="text-xs font-bold uppercase tracking-wider block">CONTRACT FEED: WORKSPACE-748</h4>
                            </div>
                            <span className="text-[9px] font-mono text-slate-400">Escrow Secure Chamber</span>
                        </div>

                        {/* Interactive message window */}
                        <div className="flex-1 p-5 overflow-y-auto space-y-4 scrollbar-thin">
                            {chatMessages.map((msg, idx) => (
                                <div key={idx} className="flex gap-3 text-xs items-start leading-relaxed">
                                    {msg.sender === 'client' && (
                                        <div className="h-7 w-7 rounded bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center font-bold text-[10px] text-indigo-400 shrink-0">
                                            CL
                                        </div>
                                    )}
                                    {msg.sender === 'developer' && (
                                        <div className="h-7 w-7 rounded bg-purple-500/20 border border-purple-500/30 flex items-center justify-center font-bold text-[10px] text-purple-400 shrink-0">
                                            DV
                                        </div>
                                    )}
                                    {msg.sender === 'system' && (
                                        <div className="h-7 w-7 rounded bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center shrink-0">
                                            <Lock className="h-3.5 w-3.5 text-emerald-450" />
                                        </div>
                                    )}
                                    
                                    <div className="space-y-1 w-full">
                                        <div className="flex justify-between items-baseline">
                                            <span className={`text-[11px] font-bold ${
                                                msg.sender === 'system' ? 'text-emerald-400' : 'text-slate-200'
                                            }`}>
                                                {msg.sender === 'client' ? 'Client (Acme Corp)' : msg.sender === 'developer' ? 'Developer (You)' : '[SYSTEM AUTOMATION]'}
                                            </span>
                                            <span className="text-[8px] font-mono text-slate-500">{msg.time}</span>
                                        </div>
                                        <p className={`text-[11px] ${msg.sender === 'system' ? 'text-slate-350 font-mono bg-slate-950 p-2 rounded border border-slate-800' : 'text-slate-300'}`}>
                                            {msg.text}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Interactive chat form */}
                        <form onSubmit={sendChatMessage} className="p-4 bg-slate-950 border-t border-slate-800 shrink-0 flex gap-2">
                            <input
                                type="text"
                                value={chatInput}
                                onChange={(e) => setChatInput(e.target.value)}
                                placeholder="Type a message to post in workspace..."
                                className="flex-1 bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 transition-colors"
                            />
                            <button
                                type="submit"
                                className="h-8.5 w-8.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 flex items-center justify-center shrink-0 cursor-pointer shadow"
                            >
                                <SendHorizontal className="h-4 w-4 text-white" />
                            </button>
                        </form>
                    </div>

                </div>
            </section>

            {/* ====================================================
                SECTION 8: ADMIN OPERATIONS (Operations Command Center)
               ==================================================== */}
            <section id="admin-ops" className="bg-slate-50/50 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-650 block">Operations Center</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Admin Operations & Governance Queue
                        </h2>
                        <p className="mt-4 text-sm text-slate-550 leading-relaxed font-normal">
                            Unveil the backend operations. Monitor instant AML fraud audits, custom cash-out approvals, moderation listings, and complete system ledgers.
                        </p>
                    </div>

                    {/* Operations Console Block */}
                    <div className="bg-white border border-slate-200 rounded-xl shadow-xl max-w-5xl mx-auto overflow-hidden">
                        
                        {/* Admin Tab Controls */}
                        <div className="bg-slate-100 px-5 py-3 border-b border-slate-200 flex flex-wrap gap-2">
                            {[
                                { id: 'withdrawals', label: 'Withdrawal Cash-Outs Approval Queue' },
                                { id: 'moderation', label: 'Disputes & Support Moderation' },
                                { id: 'fraud', label: 'Security & AML Fraud Flags' }
                            ].map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setAdminOpsTab(tab.id)}
                                    className={`py-1.5 px-3 rounded text-[11px] font-bold transition-all cursor-pointer ${
                                        adminOpsTab === tab.id
                                            ? 'bg-slate-900 text-white shadow-xs'
                                            : 'text-slate-550 hover:text-slate-800'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        {/* Interactive Queue Content */}
                        <div className="p-5 min-h-[220px]">
                            <AnimatePresence mode="wait">
                                {adminOpsTab === 'withdrawals' && (
                                    <motion.div
                                        key="withdrawals"
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        exit={{ opacity: 0 }}
                                        className="space-y-3"
                                    >
                                        <div className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2 flex items-center justify-between">
                                            <span>PENDING CASH OUT CLEARANCES</span>
                                            <span>LEDGER BALANCES MUST RESOLVE</span>
                                        </div>
                                        <div className="space-y-2">
                                            {withdrawalQueue.map((item) => (
                                                <div key={item.id} className="flex justify-between items-center border border-slate-100 rounded-lg p-3 hover:bg-slate-50/50 transition-colors">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-8 w-8 rounded-md bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0 text-indigo-700 font-bold text-xs font-mono">
                                                            {item.id}
                                                        </div>
                                                        <div>
                                                            <span className="text-xs font-bold text-slate-900 block leading-tight">{item.entity}</span>
                                                            <span className="text-[9px] text-slate-450 block font-mono">Method: {item.method}</span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-3">
                                                        <span className="text-xs font-mono font-bold text-slate-900">{item.amount}</span>
                                                        
                                                        {item.status === 'pending' ? (
                                                            <button
                                                                onClick={() => approveWithdrawal(item.id)}
                                                                className="px-2.5 py-1 text-[10px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded cursor-pointer transition-colors shadow-xs"
                                                            >
                                                                Approve Ledger Cashout
                                                            </button>
                                                        ) : (
                                                            <span className="px-2.5 py-1 text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded flex items-center gap-1 font-mono">
                                                                <Check className="h-3 w-3" /> APPROVED & BALANCED
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </motion.div>
                                )}

                                {adminOpsTab === 'moderation' && (
                                    <motion.div
                                        key="moderation"
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        exit={{ opacity: 0 }}
                                        className="space-y-3"
                                    >
                                        <div className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2">ACTIVE MODERATION DISPUTE RESOLUTION</div>
                                        <div className="border border-slate-100 rounded-lg p-3 flex justify-between items-center hover:bg-slate-50/50 transition-colors">
                                            <div className="flex items-start gap-3 text-xs">
                                                <div className="h-8 w-8 rounded-md bg-purple-50 border border-purple-100 flex items-center justify-center shrink-0 text-purple-750 font-bold">
                                                    M-8
                                                </div>
                                                <div>
                                                    <span className="font-bold text-slate-900 block leading-tight">Milestone 2 Deliverables dispute</span>
                                                    <span className="text-[9px] text-slate-450 block font-mono">Contract: Apex Systems LLC</span>
                                                </div>
                                            </div>
                                            <span className="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-100 text-[10px] font-bold font-mono">
                                                PENDING ADMIN REVIEW
                                            </span>
                                        </div>
                                        <div className="border border-slate-100 rounded-lg p-3 flex justify-between items-center hover:bg-slate-50/50 transition-colors">
                                            <div className="flex items-start gap-3 text-xs">
                                                <div className="h-8 w-8 rounded-md bg-slate-50 border flex items-center justify-center shrink-0 text-slate-655 font-bold">
                                                    M-7
                                                </div>
                                                <div>
                                                    <span className="font-bold text-slate-900 block leading-tight">Milestone 1 Deliverables release dispute</span>
                                                    <span className="text-[9px] text-slate-450 block font-mono">Contract: PixelCraft Studio</span>
                                                </div>
                                            </div>
                                            <span className="px-2 py-0.5 rounded bg-slate-50 text-slate-600 border border-slate-200 text-[10px] font-bold font-mono">
                                                DISMISSED & RELEASED
                                            </span>
                                        </div>
                                    </motion.div>
                                )}

                                {adminOpsTab === 'fraud' && (
                                    <motion.div
                                        key="fraud"
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        exit={{ opacity: 0 }}
                                        className="space-y-3"
                                    >
                                        <div className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2">AML AUDITING ALERTS</div>
                                        <div className="bg-rose-50 border border-rose-100 rounded-lg p-4 flex gap-3 text-xs">
                                            <AlertTriangle className="h-5 w-5 text-rose-600 shrink-0 mt-0.5 animate-pulse" />
                                            <div>
                                                <span className="font-bold text-rose-800 block">AML Withdrawal Hold Alert (User U-902)</span>
                                                <span className="text-[10px] text-rose-700 block mt-1 font-semibold leading-relaxed">
                                                    Withdrawal request W-9024 flagged by automated compliance module due to velocity change in standard multi-currency wallet transfers. Manual verify required before ledger release.
                                                </span>
                                            </div>
                                        </div>
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 9: CUSTOMER EXPERIENCE (Client Portal & Checkout Flow)
               ==================================================== */}
            <section id="customer-exp" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left side: Premium Portal mock */}
                    <div className="lg:col-span-7 bg-slate-50 border border-slate-200 rounded-xl p-6 shadow-xl relative overflow-hidden order-last lg:order-first">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-200 mb-6">
                            <span className="text-xs font-bold text-slate-900 font-mono">PORTAL: PORTAL.ACME.COM</span>
                            <span className="text-[9px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded">
                                WHITE-LABEL BRAND ACTIVE
                            </span>
                        </div>

                        {/* Customer Dashboard representation */}
                        <div className="bg-white border border-slate-200 rounded-lg p-4 shadow-sm space-y-4">
                            <div>
                                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">OUTSTANDING BILLS</span>
                                <h3 className="text-sm font-bold text-slate-900 mt-1">Invoice #1094 for API Development Services</h3>
                            </div>
                            
                            <div className="flex justify-between items-center text-xs bg-slate-50 border border-slate-200 p-3 rounded-lg">
                                <div className="space-y-0.5">
                                    <span className="text-[9px] text-slate-450 block font-mono">DUE IN 5 DAYS</span>
                                    <span className="font-mono font-bold text-slate-900">$3,200.00</span>
                                </div>
                                <button
                                    onClick={() => setPaymentModalOpen(true)}
                                    className="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold text-xs cursor-pointer shadow-xs"
                                >
                                    Proceed to payment checkout
                                </button>
                            </div>
                        </div>

                        {/* Interactive Client checkout Portal Modal Simulation */}
                        <AnimatePresence>
                            {paymentModalOpen && (
                                <motion.div
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 1 }}
                                    exit={{ opacity: 0 }}
                                    className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-40"
                                >
                                    <motion.div
                                        initial={{ scale: 0.95 }}
                                        animate={{ scale: 1 }}
                                        exit={{ scale: 0.95 }}
                                        className="bg-white rounded-lg max-w-sm w-full border border-slate-200 shadow-2xl p-5"
                                    >
                                        <h4 className="text-sm font-extrabold text-slate-900 block border-b pb-2 mb-4">
                                            Secure Stripe checkout Settle
                                        </h4>
                                        <div className="space-y-3.5">
                                            <div className="flex justify-between text-xs font-semibold text-slate-700">
                                                <span>Settlement for Invoice #1094:</span>
                                                <span className="font-mono text-slate-900 font-bold">$3,200.00</span>
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Card Details</label>
                                                <div className="border rounded-md px-3 py-2 text-xs font-mono bg-slate-50 text-slate-655 flex justify-between items-center">
                                                    <span>••••  ••••  ••••  4242</span>
                                                    <span>04/28</span>
                                                </div>
                                            </div>
                                            
                                            {paymentSuccess ? (
                                                <div className="bg-emerald-50 border border-emerald-100 text-emerald-800 p-3 rounded-md text-center text-xs font-bold flex items-center justify-center gap-1.5">
                                                    <CheckCircle className="h-4.5 w-4.5 animate-pulse" /> Settle Succeeded! Ledgers updating...
                                                </div>
                                            ) : (
                                                <div className="flex gap-2 pt-2">
                                                    <button
                                                        onClick={() => setPaymentModalOpen(false)}
                                                        className="flex-1 py-2 text-center border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg cursor-pointer"
                                                    >
                                                        Cancel Settle
                                                    </button>
                                                    <button
                                                        onClick={triggerClientCheckout}
                                                        className="flex-1 py-2 text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg cursor-pointer shadow-sm"
                                                    >
                                                        Authorize $3,200.00
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </motion.div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>

                    {/* Right side: Copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-650 block">Customer Experience</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Simple for your clients, robust for you
                        </h2>
                        <p className="text-slate-600 text-sm sm:text-base leading-relaxed font-normal">
                            Separate platform complexity. While you audit double-entry ledger items, custom tax holdbacks, and withdrawal logs, your client accesses a white-labeled dashboard designed for fast payment settlement.
                        </p>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="flex items-start gap-2 text-xs text-slate-700 font-semibold">
                                <Check className="h-5 w-5 text-indigo-600 shrink-0 mt-0.5" />
                                <span>Complete domain white-labeling</span>
                            </div>
                            <div className="flex items-start gap-2 text-xs text-slate-700 font-semibold">
                                <Check className="h-5 w-5 text-indigo-600 shrink-0 mt-0.5" />
                                <span>Secure integrated CC card checkout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 10: INTEGRATIONS & AUTOMATION (Interactive Node Map)
               ==================================================== */}
            <section id="integrations" className="bg-slate-50/50 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-650 block">Automations & Webhooks</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Automate workflows with event-driven webhooks
                        </h2>
                        <p className="mt-4 text-sm text-slate-550 leading-relaxed font-normal">
                            Unify external systems effortlessly. Connect invoices, wallets, and escrow status steps directly to webhooks and automation nodes.
                        </p>
                    </div>

                    {/* Automation Webhook Node Flow */}
                    <div className="bg-white border border-slate-200 rounded-xl shadow-xl max-w-4xl mx-auto p-6 relative overflow-hidden">
                        
                        {/* Soft visual connections nodes */}
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 items-stretch relative">
                            
                            {/* Node 1: Event Trigger */}
                            <div className="border border-slate-100 bg-slate-50 p-4 rounded-lg flex flex-col justify-between items-center text-center text-xs space-y-4">
                                <div className="h-10 w-10 bg-indigo-50 border border-indigo-100 rounded-full flex items-center justify-center">
                                    <Zap className="h-5 w-5 text-indigo-600" />
                                </div>
                                <div>
                                    <span className="text-[9px] text-slate-450 block uppercase font-bold tracking-wider">EVENT TRIGGER</span>
                                    <span className="font-bold text-slate-900 block mt-1">Invoice Settled</span>
                                </div>
                                <span className="text-[9px] font-mono text-indigo-655 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                                    API: inv.paid
                                </span>
                            </div>

                            {/* Node 2: Action 1 */}
                            <div className="border border-slate-100 bg-slate-50 p-4 rounded-lg flex flex-col justify-between items-center text-center text-xs space-y-4">
                                <div className="h-10 w-10 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center">
                                    <Lock className="h-5 w-5 text-emerald-600" />
                                </div>
                                <div>
                                    <span className="text-[9px] text-slate-450 block uppercase font-bold tracking-wider">ACTION NODE 1</span>
                                    <span className="font-bold text-slate-900 block mt-1">Release Escrow Vault</span>
                                </div>
                                <span className="text-[9px] font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                                    Status: Succeeded
                                </span>
                            </div>

                            {/* Node 3: Action 2 */}
                            <div className="border border-slate-100 bg-slate-50 p-4 rounded-lg flex flex-col justify-between items-center text-center text-xs space-y-4">
                                <div className="h-10 w-10 bg-purple-50 border border-purple-100 rounded-full flex items-center justify-center">
                                    <Globe className="h-5 w-5 text-purple-650" />
                                </div>
                                <div>
                                    <span className="text-[9px] text-slate-450 block uppercase font-bold tracking-wider">ACTION NODE 2</span>
                                    <span className="font-bold text-slate-900 block mt-1">Trigger Webhook URL</span>
                                </div>
                                <span className="text-[9px] font-mono text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">
                                    200 OK Response
                                </span>
                            </div>

                            {/* Node 4: Action 3 */}
                            <div className="border border-slate-100 bg-slate-50 p-4 rounded-lg flex flex-col justify-between items-center text-center text-xs space-y-4">
                                <div className="h-10 w-10 bg-slate-950 flex items-center justify-center rounded-full shrink-0">
                                    <Server className="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <span className="text-[9px] text-slate-450 block uppercase font-bold tracking-wider">INTEGRATION END</span>
                                    <span className="font-bold text-slate-900 block mt-1">Recalculate Ledgers</span>
                                </div>
                                <span className="text-[9px] font-mono text-slate-600 bg-white px-2 py-0.5 rounded border">
                                    Journal Settle
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 11: ANALYTICS & REPORTING (Custom SVG Charts)
               ==================================================== */}
            <section id="analytics" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left side: Analytics Copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-bold uppercase tracking-widest text-emerald-600 block">Business Intelligence</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Double-Entry Financial Analytics & Wallet trends
                        </h2>
                        <p className="text-slate-600 text-sm sm:text-base leading-relaxed font-normal">
                            Monitor revenue growth velocity with enterprise-level financial metrics. Swap balance configurations, track escrow lock ratios, and project network growth on a high-density, custom SVG reporting grid.
                        </p>
                    </div>

                    {/* Right side: High-density SVG Revenue Chart Card */}
                    <div className="lg:col-span-7 bg-slate-50 border border-slate-200 rounded-xl shadow-xl p-6 relative overflow-hidden">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-250 mb-6">
                            <div>
                                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">BUSINESS METRICS CONSOLE</h4>
                                <span className="text-sm font-extrabold text-slate-900">Wallet cash flow Velocity</span>
                            </div>
                            <span className="text-[10px] font-mono text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded font-bold">
                                REVENUE GAIN: +24% YTD
                            </span>
                        </div>

                        {/* Custom High-Fidelity SVG Chart representation */}
                        <div className="w-full h-[180px] bg-white border border-slate-200 rounded-lg p-4 relative flex flex-col justify-end">
                            <svg viewBox="0 0 500 120" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                                {/* Chart gridlines */}
                                <line x1="0" y1="30" x2="500" y2="30" stroke="#f1f5f9" strokeWidth="1" />
                                <line x1="0" y1="60" x2="500" y2="60" stroke="#f1f5f9" strokeWidth="1" />
                                <line x1="0" y1="90" x2="500" y2="90" stroke="#f1f5f9" strokeWidth="1" />
                                
                                {/* Hemi-gradient Area under graph */}
                                <path d="M 0 110 L 0 95 C 40 95, 80 80, 100 85 C 140 95, 180 50, 200 45 C 240 35, 280 65, 300 50 C 340 30, 380 20, 420 15 C 460 10, 480 5, 500 5 L 500 110 Z" fill="url(#chartGrad)" opacity="0.15" />
                                
                                {/* Smooth Trend Curve line */}
                                <path d="M 0 95 C 40 95, 80 80, 100 85 C 140 95, 180 50, 200 45 C 240 35, 280 65, 300 50 C 340 30, 380 20, 420 15 C 460 10, 480 5, 500 5" stroke="#6366f1" strokeWidth="3" strokeLinecap="round" />
                                
                                {/* Definitions of HSL hemi-gradients */}
                                <defs>
                                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor="#6366f1" />
                                        <stop offset="100%" stopColor="#ffffff" />
                                    </linearGradient>
                                </defs>
                            </svg>
                            
                            {/* Grid labels */}
                            <div className="flex justify-between text-[9px] text-slate-400 font-mono mt-2 pt-2 border-t border-slate-100">
                                <span>JAN</span>
                                <span>FEB</span>
                                <span>MAR</span>
                                <span>APR</span>
                                <span>MAY</span>
                                <span>JUN</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 12: SUBSCRIPTION PRICING SECTION & MATRIX
               ==================================================== */}
            <section id="pricing" className="bg-slate-50/50 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-650 block">Pricing Matrix</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Clear, corporate-scale plans. Zero tricks.
                        </h2>
                        <p className="mt-4 text-sm text-slate-550 leading-relaxed font-normal">
                            Select the operational tier corresponding to your transaction volume. Toggle billing periods to secure platform discounts.
                        </p>
                        
                        {/* Interactive monthly/yearly toggle */}
                        <div className="inline-flex items-center gap-3 bg-white border border-slate-200 rounded-full p-1 mt-8 shadow-xs">
                            <button
                                onClick={() => setBillingPeriod('monthly')}
                                className={`py-1.5 px-4 text-xs font-bold rounded-full transition-all cursor-pointer ${
                                    billingPeriod === 'monthly'
                                        ? 'bg-slate-900 text-white shadow-sm'
                                        : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                Monthly Billing
                            </button>
                            <button
                                onClick={() => setBillingPeriod('yearly')}
                                className={`py-1.5 px-4 text-xs font-bold rounded-full transition-all cursor-pointer flex items-center gap-1.5 ${
                                    billingPeriod === 'yearly'
                                        ? 'bg-slate-900 text-white shadow-sm'
                                        : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                Yearly Billing
                                <span className="bg-emerald-500 text-white text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full">
                                    SAVE 20%
                                </span>
                            </button>
                        </div>
                    </div>

                    {/* Subscription Cards Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto">
                        
                        {/* Starter Tier */}
                        <div className="bg-white border border-slate-200 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-all shadow-sm">
                            <div className="space-y-6">
                                <div>
                                    <span className="text-[10px] font-bold text-slate-450 uppercase tracking-widest block font-mono">STARTER TIER</span>
                                    <h3 className="text-xl font-extrabold text-slate-900 mt-1">Individual Operator</h3>
                                    <p className="text-slate-500 text-xs mt-1">Deploy single projects and manage personal client escrows.</p>
                                </div>
                                <div className="flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">
                                        ${billingPeriod === 'monthly' ? '49' : '39'}
                                    </span>
                                    <span className="text-slate-400 text-xs ml-1.5">/ month</span>
                                </div>
                                <ul className="space-y-3.5 text-xs text-slate-700 font-semibold border-t border-slate-100 pt-6">
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                        <span>Up to 5 Active Client Portals</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                        <span>Standard Escrow System Vault</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                        <span>Developer API Sandbox Access</span>
                                    </li>
                                </ul>
                            </div>
                            <Link href="/register?plan=starter" className="mt-8">
                                <Button className="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-lg py-2.5 font-bold shadow-xs cursor-pointer h-10">
                                    Choose Starter
                                </Button>
                            </Link>
                        </div>

                        {/* Business Tier (Recommended) */}
                        <div className="bg-white border-2 border-indigo-600 rounded-xl p-8 flex flex-col justify-between relative shadow-xl shadow-indigo-500/5 group">
                            <span className="absolute top-0 right-6 -translate-y-1/2 bg-indigo-600 text-white text-[9px] font-bold uppercase tracking-widest px-3 py-1 rounded-full shadow-md">
                                RECOMMENDED TIER
                            </span>
                            <div className="space-y-6">
                                <div>
                                    <span className="text-[10px] font-bold text-indigo-650 uppercase tracking-widest block font-mono">BUSINESS TIER</span>
                                    <h3 className="text-xl font-extrabold text-slate-900 mt-1">Agency & Studio</h3>
                                    <p className="text-slate-500 text-xs mt-1">Unified operational workspace for team billing, domains, and splits.</p>
                                </div>
                                <div className="flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">
                                        ${billingPeriod === 'monthly' ? '149' : '119'}
                                    </span>
                                    <span className="text-slate-400 text-xs ml-1.5">/ month</span>
                                </div>
                                <ul className="space-y-3.5 text-xs text-slate-700 font-semibold border-t border-slate-100 pt-6">
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-650 shrink-0" />
                                        <span className="font-bold text-slate-900">Unlimited Client Portals</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-655 shrink-0" />
                                        <span>White-labeled custom domains & SSL</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-655 shrink-0" />
                                        <span>Automated Tax Split Reserve ledgers</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-655 shrink-0" />
                                        <span>Priority Dispute Governance</span>
                                    </li>
                                </ul>
                            </div>
                            <Link href="/register?plan=pro" className="mt-8">
                                <Button className="w-full bg-gradient-to-r from-indigo-500 to-indigo-650 text-white rounded-lg py-2.5 font-bold shadow-md shadow-indigo-500/10 cursor-pointer h-10">
                                    Start Professional Trial
                                </Button>
                            </Link>
                        </div>

                        {/* Enterprise Tier */}
                        <div className="bg-white border border-slate-200 rounded-xl p-8 flex flex-col justify-between hover:border-slate-300 transition-all shadow-sm">
                            <div className="space-y-6">
                                <div>
                                    <span className="text-[10px] font-bold text-slate-450 uppercase tracking-widest block font-mono">ENTERPRISE TIER</span>
                                    <h3 className="text-xl font-extrabold text-slate-900 mt-1">Custom Corporate</h3>
                                    <p className="text-slate-500 text-xs mt-1">Dedicated double-entry audit ledgers, compliance, and custom API scales.</p>
                                </div>
                                <div className="flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">Custom</span>
                                </div>
                                <ul className="space-y-3.5 text-xs text-slate-700 font-semibold border-t border-slate-100 pt-6">
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                        <span>Custom double-entry journal reserves</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                        <span>Dedicated AML compliance audit support</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4.5 w-4.5 text-indigo-600 shrink-0" />
                                        <span>Rate Limit scale support & high SLAs</span>
                                    </li>
                                </ul>
                            </div>
                            <a href="mailto:hello@musoftwares.com?subject=Enterprise Inquiry" className="mt-8">
                                <Button variant="outline" className="w-full bg-white hover:bg-slate-50 text-slate-700 border-slate-250 rounded-lg py-2.5 font-bold shadow-xs cursor-pointer h-10">
                                    Contact Accounts Dept
                                </Button>
                            </a>
                        </div>

                    </div>

                    {/* Features Comparison Matrix */}
                    <div className="mt-20 max-w-5xl mx-auto border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden hidden md:block">
                        <div className="bg-slate-100 px-6 py-4 border-b border-slate-200 text-xs font-bold text-slate-900 tracking-wider font-mono">
                            COMPREHENSIVE PLAN FEATURE MATRIX
                        </div>
                        <table className="w-full text-left text-xs divide-y divide-slate-100">
                            <thead>
                                <tr className="bg-slate-50/50 font-bold text-slate-400 font-mono text-[10px] tracking-wide">
                                    <th className="px-6 py-4">OPERATIONAL CRITERIA</th>
                                    <th className="px-6 py-4">STARTER</th>
                                    <th className="px-6 py-4">BUSINESS</th>
                                    <th className="px-6 py-4">ENTERPRISE</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 font-semibold text-slate-700">
                                <tr>
                                    <td className="px-6 py-4 font-bold text-slate-900">ERP & Ledger Audit Log</td>
                                    <td className="px-6 py-4">Standard</td>
                                    <td className="px-6 py-4 text-indigo-655 font-bold">Advanced (Realtime)</td>
                                    <td className="px-6 py-4">Custom Configured</td>
                                </tr>
                                <tr>
                                    <td className="px-6 py-4 font-bold text-slate-900">White-Label Domains</td>
                                    <td className="px-6 py-4 text-slate-400">—</td>
                                    <td className="px-6 py-4 text-emerald-600 font-bold">Unlimited domains</td>
                                    <td className="px-6 py-4">Custom Configured</td>
                                </tr>
                                <tr>
                                    <td className="px-6 py-4 font-bold text-slate-900">Escrow Vault Chambers</td>
                                    <td className="px-6 py-4">Standard TLS</td>
                                    <td className="px-6 py-4">Dedicated Escrow</td>
                                    <td className="px-6 py-4">Multi-Sign Secure Vault</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 13: FAQ SECTION (Collapsible Accordion)
               ==================================================== */}
            <section id="faq" className="bg-white py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Header */}
                    <div className="text-center mb-16">
                        <span className="text-xs font-bold uppercase tracking-widest text-indigo-650 block">System Inquiries</span>
                        <h2 className="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900">
                            Frequently Answered System Inquiries
                        </h2>
                    </div>

                    {/* Accordion FAQ List */}
                    <div className="space-y-4">
                        {[
                            {
                                q: "How is the Escrow system secured and monitored?",
                                a: "Every escrow contract locks payments inside a secure transactional bank vault using advanced TLS verification parameters. Released milestone payments execute within seconds to designated beneficiary wallets under strict double-entry ledger audits, completely eliminating merchant fraud risks."
                            },
                            {
                                q: "How does the running timecard billing tracker update invoices?",
                                a: "The billable timecard timer logs chronological integration events in real-time. Pausing or completing a tracker compiles invoice draft line items directly at your predefined rate. Clients receive detailed timecard logs alongside the final itemized payment dispatch sheet."
                            },
                            {
                                q: "Are client portals white-labeled under our company domains?",
                                a: "Yes. The Business Tier allows configuring custom domain layouts (e.g., portal.yourfirm.com) complete with automatic SSL provisioning. Clients experience an absolute white-label design environment reflecting your corporate branding, colors, and logos."
                            },
                            {
                                q: "What multi-currency payment gateways do you support?",
                                a: "Musoftware supports 130+ regional currencies natively, backed by standard integrations including Stripe Payouts, Bank Wire transfers, ACH transfers, and PayPal channels, managed directly within the client wallet ledger."
                            },
                            {
                                q: "How does musoftware prevent database merge conflicts on concurrent updates?",
                                a: "We run a robust transactional queue framework built directly on database isolation levels (serializable states). Parallel actions (timer logs, invoice payments, and escrow releases) resolve sequentially to maintain precise transaction safety."
                            },
                            {
                                q: "Can we configure custom automated ledger splits for partner payouts?",
                                a: "Yes. Within the Business and Enterprise tiers, you can define automated percentage-based splits at the project or contract level. When invoices are paid, our system automatically routes the defined splits (e.g. 70% to main wallet, 20% to subcontractor, 10% to federal tax reserve) immediately."
                            }
                        ].map((faq, idx) => (
                            <div key={idx} className="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden transition-all shadow-xs">
                                <button
                                    onClick={() => toggleFaq(idx)}
                                    className="w-full px-6 py-5 text-left flex justify-between items-center gap-4 hover:bg-slate-100/50 focus:outline-none transition-colors cursor-pointer"
                                >
                                    <span className="text-sm font-bold text-slate-900">{faq.q}</span>
                                    <ChevronDown className={`h-4.5 w-4.5 text-slate-400 transition-transform duration-300 ${
                                        faqExpanded[idx] ? 'rotate-180 text-slate-800' : 'rotate-0'
                                    }`} />
                                </button>
                                
                                <div className={`transition-all duration-300 overflow-hidden ${
                                    faqExpanded[idx] ? 'max-h-[200px] border-t border-slate-200' : 'max-h-0'
                                }`}>
                                    <p className="px-6 py-5 text-xs text-slate-655 leading-relaxed bg-white font-medium">
                                        {faq.a}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ====================================================
                SECTION 14: FINAL CTA SECTION
               ==================================================== */}
            <section id="cta" className="bg-white py-24 relative z-10">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="relative rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 text-white border border-slate-800 p-8 sm:p-12 md:p-16 overflow-hidden shadow-2xl text-center">
                        
                        {/* Soft light glows */}
                        <div className="absolute top-0 right-0 h-48 w-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
                        <div className="absolute bottom-0 left-0 h-48 w-48 bg-purple-500/8 rounded-full blur-3xl pointer-events-none" />

                        <div className="relative z-10 flex flex-col items-center max-w-2xl mx-auto space-y-6">
                            <span className="text-[10px] font-bold text-indigo-400 uppercase tracking-widest block font-mono">
                                UNIFIED OPERATIONAL HUB
                            </span>
                            <h2 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                                Ready to run your entire operational workspace?
                            </h2>
                            <p className="text-xs sm:text-sm text-slate-350 leading-relaxed font-normal">
                                Join thousands of scaling firms, freelance studios, and corporate enterprises running double-entry ledger wallets, protected escrows, and robust whitelabeled portals inside musoftware.
                            </p>
                            
                            <div className="pt-4 flex flex-col sm:flex-row gap-4 w-full justify-center">
                                <Link href="/register?trial=true" className="w-full sm:w-auto">
                                    <Button size="lg" className="w-full bg-gradient-to-r from-indigo-500 to-indigo-650 hover:opacity-95 text-white rounded-lg shadow-lg border-0 h-12 text-sm font-semibold px-8 flex items-center justify-center gap-2 group cursor-pointer">
                                        Start Your Free Trial
                                        <ArrowRight className="h-4.5 w-4.5 group-hover:translate-x-1 transition-transform" />
                                    </Button>
                                </Link>
                                <a href="mailto:hello@musoftwares.com?subject=Demo Inquiry" className="w-full sm:w-auto">
                                    <Button size="lg" variant="outline" className="w-full bg-slate-900 hover:bg-slate-800 text-slate-200 border-slate-700 rounded-lg px-8 h-12 text-sm font-semibold shadow-xs cursor-pointer">
                                        Book Corporate Demo
                                    </Button>
                                </a>
                            </div>
                            <span className="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">
                                Free 14-day trial • Cancel anytime • Zero credit card required
                            </span>
                        </div>
                    </div>
                </div>
            </section>

        </PublicLayout>
    );
}
