import { Button } from '@/Components/ui/button';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';
import { 
    Wallet, ShieldCheck, Clock, Check, ChevronDown, Sparkles, 
    ArrowRight, DollarSign, Activity, FileText, Send, UserCheck, 
    TrendingUp, Terminal, ShieldAlert, Layers, Laptop, Code2, 
    Play, Pause, RefreshCw, Star, CheckCircle, BarChart3, HelpCircle 
} from 'lucide-react';

export default function Home({ canLogin, canRegister }) {
    // ----------------------------------------------------
    // 1. Live Interactive States & Simulation Data
    // ----------------------------------------------------
    
    // Sim 1: Billing & Wallet Integrator
    const [invoiceStep, setInvoiceStep] = useState(0); // 0: Draft, 1: Sent, 2: Approved, 3: Paid
    const [walletBalance, setWalletBalance] = useState(14820.00);
    const [invoiceValue] = useState(2450.00);
    const [recentTransactions, setRecentTransactions] = useState([
        { id: 'TX-901', desc: 'Marketplace Service: Logo Brand Pack', amount: 450.00, type: 'credit', status: 'completed' },
        { id: 'TX-902', desc: 'Contract Milestone 2: Escrow Released', amount: 1800.00, type: 'credit', status: 'completed' },
        { id: 'TX-903', desc: 'System Fee Protection', amount: -22.50, type: 'debit', status: 'completed' }
    ]);

    const advanceInvoiceStep = () => {
        if (invoiceStep < 3) {
            const nextStep = invoiceStep + 1;
            setInvoiceStep(nextStep);
            
            // If transitioning to "Paid", credit the wallet and append to transactions!
            if (nextStep === 3) {
                setWalletBalance(prev => prev + invoiceValue);
                setRecentTransactions(prev => [
                    { id: `TX-${Math.floor(100 + Math.random() * 900)}`, desc: 'Invoice #INV-2026-88 Paid', amount: invoiceValue, type: 'credit', status: 'completed' },
                    ...prev
                ]);
            }
        }
    };

    const resetInvoiceSimulator = () => {
        setInvoiceStep(0);
        setWalletBalance(14820.00);
        setRecentTransactions([
            { id: 'TX-901', desc: 'Marketplace Service: Logo Brand Pack', amount: 450.00, type: 'credit', status: 'completed' },
            { id: 'TX-902', desc: 'Contract Milestone 2: Escrow Released', amount: 1800.00, type: 'credit', status: 'completed' },
            { id: 'TX-903', desc: 'System Fee Protection', amount: -22.50, type: 'debit', status: 'completed' }
        ]);
    };

    // Sim 2: Active Timed Billing Tracker
    const [timerActive, setTimerActive] = useState(false);
    const [timerSeconds, setTimerSeconds] = useState(144); // Start at 2m 24s for impact
    const [billableAmount, setBillableAmount] = useState(180.00); // Start showing money earned
    const ratePerHour = 75.00;

    useEffect(() => {
        let interval = null;
        if (timerActive) {
            interval = setInterval(() => {
                setTimerSeconds(sec => {
                    const nextSec = sec + 1;
                    // $75 / 3600 seconds = $0.0208 per second
                    const earned = (nextSec * ratePerHour) / 3600;
                    setBillableAmount(earned);
                    return nextSec;
                });
            }, 1000);
        } else {
            clearInterval(interval);
        }
        return () => clearInterval(interval);
    }, [timerActive]);

    const formatTimer = (totalSeconds) => {
        const hrs = Math.floor(totalSeconds / 3600);
        const mins = Math.floor((totalSeconds % 3600) / 60);
        const secs = totalSeconds % 60;
        return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    // Sim 3: Marketplace Escrow Simulator
    const [escrowFunded, setEscrowFunded] = useState(false);
    const [escrowStatus, setEscrowStatus] = useState('unfunded'); // unfunded -> holding -> released

    const triggerEscrowFunding = () => {
        if (!escrowFunded) {
            setEscrowFunded(true);
            setEscrowStatus('holding');
        } else if (escrowStatus === 'holding') {
            setEscrowStatus('released');
            setWalletBalance(prev => prev + 3500.00);
        }
    };

    // Sim 4: API & Webhook Code Tabs
    const [activeApiTab, setActiveApiTab] = useState('curl');
    const apiSnippets = {
        curl: `curl -X POST https://api.musoftware.com/v1/invoices \\
  -H "Authorization: Bearer ms_live_88f921a9c" \\
  -H "Content-Type: application/json" \\
  -d '{
    "client_id": "cli_9921820a",
    "amount": 2450.00,
    "currency": "USD",
    "escrow_enabled": true,
    "line_items": [
      { "description": "SaaS Platform UX Architecture Refactor", "cost": 2450.00 }
    ]
  }'`,
        nodejs: `const musoftware = require('@musoftware/node')('ms_live_88f921a9c');

await musoftware.invoices.create({
  client_id: 'cli_9921820a',
  amount: 2450.00,
  currency: 'USD',
  escrow_enabled: true,
  line_items: [{
    description: 'SaaS Platform UX Architecture Refactor',
    cost: 2450.00
  }]
});`,
        python: `import musoftware

musoftware.api_key = "ms_live_88f921a9c"

invoice = musoftware.Invoice.create(
  client_id="cli_9921820a",
  amount=2450.00,
  currency="USD",
  escrow_enabled=True,
  line_items=[
    { "description": "SaaS Platform UX Architecture Refactor", "cost": 2450.00 }
  ]
)`
    };

    // Sim 5: Analytics Toggles
    const [activeChartTab, setActiveChartTab] = useState('revenue');
    
    // Sim 6: Admin Ops Console Simulator
    const [adminRequests, setAdminRequests] = useState([
        { id: 'WD-8812', user: 'David K. (Freelancer)', amount: 4850.00, type: 'Bank Wire', status: 'pending' },
        { id: 'WD-8813', user: 'Sophia L. (Agency)', amount: 12500.00, type: 'Stripe Instant', status: 'pending' },
        { id: 'WD-8814', user: 'Alex M. (Individual)', amount: 320.00, type: 'PayPal', status: 'pending' }
    ]);
    
    const approveWithdrawal = (id, amount) => {
        setAdminRequests(prev => prev.map(req => req.id === id ? { ...req, status: 'approved' } : req));
    };

    // Sim 7: Client Dashboard vs Freelancer View Perspective
    const [portalView, setPortalView] = useState('freelancer'); // 'freelancer' or 'client'

    // Sim 8: Pricing Toggle
    const [billingCycle, setBillingCycle] = useState('monthly'); // 'monthly' or 'yearly'

    // Sim 9: FAQ Accordions
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

    // ----------------------------------------------------
    // Ref-based scroll reveals / triggers
    // ----------------------------------------------------
    const statsRef = useRef(null);
    const [statsVisible, setStatsVisible] = useState(false);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setStatsVisible(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.1 }
        );

        if (statsRef.current) {
            observer.observe(statsRef.current);
        }
        return () => observer.disconnect();
    }, []);

    // Animated counter sub-component
    const DynamicStat = ({ value, label, isVisible, prefix = '', suffix = '' }) => {
        const [num, setNum] = useState(0);

        useEffect(() => {
            if (!isVisible) return;
            const target = parseFloat(value.replace(/[^0-9.]/g, ''));
            let current = 0;
            const duration = 1500;
            const steps = 60;
            const stepVal = target / steps;
            const timer = setInterval(() => {
                current += stepVal;
                if (current >= target) {
                    setNum(target);
                    clearInterval(timer);
                } else {
                    setNum(current);
                }
            }, duration / steps);

            return () => clearInterval(timer);
        }, [isVisible, value]);

        return (
            <div className="flex flex-col items-center md:items-start p-6 bg-white border border-slate-200/80 rounded-xl shadow-sm">
                <span className="text-3xl font-bold tracking-tight text-slate-900">
                    {prefix}
                    {value.includes('M') || value.includes('K')
                        ? num.toFixed(1) + suffix
                        : Math.floor(num).toLocaleString()}
                </span>
                <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5">{label}</span>
            </div>
        );
    };

    return (
        <PublicLayout>
            <Head title="musoftware — Unified Client Operations, ERP & Wallet Infrastructure" />

            {/* Custom Light Mode Style Overrides */}
            <style dangerouslySetInnerHTML={{__html: `
                @keyframes grid-pulse {
                    0%, 100% { opacity: 0.2; }
                    50% { opacity: 0.4; }
                }
                .glow-card::before {
                    content: '';
                    position: absolute;
                    inset: -1px;
                    background: linear-gradient(35deg, rgba(99,102,241,0.2), rgba(168,85,247,0.2), transparent);
                    border-radius: inherit;
                    z-index: -1;
                }
                .mesh-circle {
                    background: radial-gradient(circle, rgba(99,102,241,0.05) 0%, transparent 70%);
                }
                .glow-green {
                    box-shadow: 0 0 15px rgba(16,185,129,0.15);
                }
                .glow-indigo {
                    box-shadow: 0 0 15px rgba(99,102,241,0.15);
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
                        {/* Glow Platform Tag */}
                        <div className="inline-flex items-center gap-2 w-fit rounded-full bg-indigo-50/50 border border-indigo-100 px-3.5 py-1.5 text-xs text-indigo-950 backdrop-blur-sm">
                            <span className="flex h-2 w-2 rounded-full bg-indigo-600 animate-pulse" />
                            <span className="font-bold text-indigo-900">Version 4.0 Live</span>
                            <span className="text-indigo-200">|</span>
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

                    {/* Right Column: Dynamic Layered Visual Interactive Composition */}
                    <div className="lg:col-span-6 relative w-full flex items-center justify-center">
                        <div className="relative w-full max-w-md md:max-w-xl aspect-[1.1] flex flex-col justify-between">
                            
                            {/* Layer 1: Core Wallet Dashboard (Back layer) */}
                            <div className="absolute top-0 right-4 w-[85%] bg-white border border-slate-200/80 rounded-xl p-5 shadow-xl z-10 transition-all hover:translate-y-[-2px] duration-300">
                                <div className="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                                    <div className="flex items-center gap-2">
                                        <div className="h-8 w-8 rounded bg-indigo-50 flex items-center justify-center">
                                            <Wallet className="h-4 w-4 text-indigo-600" />
                                        </div>
                                        <div>
                                            <div className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Wallet Ledger</div>
                                            <div className="text-xs font-bold text-slate-900">Main Account balance</div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-lg font-mono font-bold text-emerald-650">${walletBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                        <div className="text-[10px] text-slate-400 font-bold">USD Available</div>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <div className="text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Live Transactions</div>
                                    {recentTransactions.slice(0, 2).map((tx, idx) => (
                                        <div key={tx.id} className="flex justify-between items-center text-xs p-2 bg-slate-50/50 border border-slate-100 rounded">
                                            <span className="text-slate-600 truncate max-w-[150px] font-semibold">{tx.desc}</span>
                                            <span className={`font-mono font-bold ${tx.amount > 0 ? 'text-emerald-650' : 'text-slate-500'}`}>
                                                {tx.amount > 0 ? '+' : ''}${Math.abs(tx.amount).toFixed(2)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Layer 2: Interactive Invoice Progression Simulator (Centerpiece layer) */}
                            <div className="absolute bottom-6 left-0 w-[90%] bg-white border border-slate-250 rounded-xl p-5 shadow-2xl z-20 glow-card transform hover:scale-[1.01] transition-transform duration-300">
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <span className="text-[10px] bg-indigo-50 border border-indigo-200 text-indigo-700 rounded px-2 py-0.5 font-bold uppercase tracking-wider">
                                            Interactive Widget
                                        </span>
                                        <h4 className="text-sm font-bold text-slate-900 mt-1.5">Simulation: Automated Invoice Billing</h4>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-[10px] text-slate-400 font-bold">Value</div>
                                        <div className="text-sm font-mono font-bold text-indigo-650">${invoiceValue.toFixed(2)}</div>
                                    </div>
                                </div>

                                {/* Flow Progress Timeline */}
                                <div className="grid grid-cols-4 gap-2 relative mb-6">
                                    {['Draft', 'Sent', 'Approved', 'Paid'].map((stepName, stepIndex) => (
                                        <div key={stepName} className="flex flex-col items-center">
                                            <div className={`w-full h-1.5 rounded-full mb-2 transition-colors duration-500 ${
                                                invoiceStep >= stepIndex ? 'bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.2)]' : 'bg-slate-200'
                                            }`} />
                                            <span className={`text-[10px] font-semibold tracking-tight ${
                                                invoiceStep === stepIndex ? 'text-indigo-600 font-bold' : invoiceStep > stepIndex ? 'text-slate-800' : 'text-slate-400'
                                            }`}>{stepName}</span>
                                        </div>
                                    ))}
                                </div>

                                {/* Simulator Control Button */}
                                <div className="flex gap-2">
                                    {invoiceStep < 3 ? (
                                        <Button 
                                            size="sm" 
                                            onClick={advanceInvoiceStep}
                                            className="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs py-2 font-semibold h-auto"
                                        >
                                            {invoiceStep === 0 && 'Send Invoice to Client'}
                                            {invoiceStep === 1 && 'Approve Client Invoice'}
                                            {invoiceStep === 2 && 'Process & Authorize Payment'}
                                        </Button>
                                    ) : (
                                        <Button 
                                            size="sm" 
                                            onClick={resetInvoiceSimulator}
                                            className="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs py-2 font-semibold h-auto border border-slate-250"
                                        >
                                            Reset Interactive Simulation
                                        </Button>
                                    )}
                                </div>
                            </div>

                            {/* Layer 3: Dynamic Realtime Timer Widget (Floating front layer) */}
                            <div className="absolute bottom-1/2 -right-8 w-[50%] bg-white border border-slate-200 shadow-xl z-30 transition-all hover:-translate-x-1 duration-300 flex flex-col gap-2">
                                <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Active billing timer</span>
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping" />
                                </div>
                                <div className="font-mono text-base font-bold text-slate-900 text-center leading-none mt-1">
                                    {formatTimer(timerSeconds)}
                                </div>
                                <div className="text-[10px] text-slate-500 text-center leading-none">
                                    Earned: <span className="text-emerald-650 font-bold">${billableAmount.toFixed(2)}</span>
                                </div>
                                <button
                                    onClick={() => setTimerActive(!timerActive)}
                                    className={`w-full py-1 rounded text-[10px] font-bold transition-all text-center flex items-center justify-center gap-1 ${
                                        timerActive 
                                            ? 'bg-rose-50 border border-rose-150 text-rose-600' 
                                            : 'bg-emerald-50 border border-emerald-150 text-emerald-600'
                                    }`}
                                >
                                    {timerActive ? <Pause className="h-2.5 w-2.5" /> : <Play className="h-2.5 w-2.5" />}
                                    {timerActive ? 'Pause Tracker' : 'Resume Timer'}
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                2. COMPACT SOCIAL PROOF SECTION
               ==================================================== */}
            <section id="proof" className="bg-slate-50 py-10 border-b border-slate-200/80 relative z-10" ref={statsRef}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                        <DynamicStat value="12,480" label="Active Corporate Entities" isVisible={statsVisible} />
                        <DynamicStat value="$128.4M" label="Ledger Invoices Cleared" isVisible={statsVisible} prefix="$" suffix="M" />
                        <DynamicStat value="94,200" label="Marketplace Orders Released" isVisible={statsVisible} />
                        <DynamicStat value="$42.5M" label="Escrow System Locked Volume" isVisible={statsVisible} prefix="$" suffix="M" />
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
                        <p className="mt-4 text-base text-slate-600">
                            Stop running multi-thousand dollar business operations across loosely joined apps. Unify core components under one database structure.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                        
                        {/* Pillar 1: Financial Operations */}
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-colors duration-300 shadow-sm">
                            <div>
                                <div className="h-12 w-12 rounded-lg bg-indigo-50 flex items-center justify-center mb-6">
                                    <DollarSign className="h-6 w-6 text-indigo-600" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-2">1. Financial Ledger & Wallet Operations</h3>
                                <p className="text-slate-600 text-sm leading-relaxed mb-6 font-normal">
                                    Automate account withdrawals, ledger balancing, multi-currency wallets, automatic tax splits, and instant Stripe-backed deposits.
                                </p>
                            </div>
                            <div>
                                {/* Mini UI Block */}
                                <div className="bg-white p-4 border border-slate-200 rounded-lg mb-6 flex items-center justify-between text-xs shadow-sm">
                                    <div>
                                        <div className="text-slate-400 uppercase text-[9px] font-bold">Withdrawal Approval Queue</div>
                                        <div className="text-slate-800 font-bold mt-1">Stripe Payout • David K.</div>
                                    </div>
                                    <span className="bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold px-2 py-0.5 rounded text-[10px]">Approved</span>
                                </div>
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
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-colors duration-300 shadow-sm">
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
                                {/* Mini UI Block */}
                                <div className="bg-white p-4 border border-slate-200 rounded-lg mb-6 flex items-center justify-between text-xs shadow-sm">
                                    <div className="flex items-center gap-2">
                                        <div className="h-6 w-6 rounded-full bg-indigo-500 flex items-center justify-center font-bold text-[10px] text-white">AC</div>
                                        <div className="font-bold text-slate-850">Acme Corp Portal</div>
                                    </div>
                                    <div className="text-[10px] text-slate-400 font-semibold">Balance: <span className="text-emerald-650 font-bold">$14,500</span></div>
                                </div>
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
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-colors duration-300 shadow-sm">
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
                                {/* Mini UI Block */}
                                <div className="bg-white p-4 border border-slate-200 rounded-lg mb-6 flex items-center justify-between text-xs shadow-sm">
                                    <div>
                                        <div className="font-bold text-slate-850">SaaS UI Refactor Proposal</div>
                                        <div className="text-[10px] text-slate-400 mt-0.5">Freelancer: Sophia L.</div>
                                    </div>
                                    <span className="bg-indigo-50 border border-indigo-150 text-indigo-750 font-bold px-2 py-0.5 rounded text-[10px]">$3,500 Escrow Locked</span>
                                </div>
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
                        <div className="bg-slate-50/50 border border-slate-200/80 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-colors duration-300 shadow-sm">
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
                                {/* Mini UI Block */}
                                <div className="bg-white p-4 border border-slate-200 rounded-lg mb-6 flex items-center gap-3 text-xs shadow-sm">
                                    <div className="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center font-bold">💬</div>
                                    <div className="flex-1">
                                        <div className="font-bold text-slate-850">Milestone 1 Complete</div>
                                        <p className="text-[10px] text-slate-400 truncate mt-0.5">Automated release payload processed.</p>
                                    </div>
                                </div>
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
                4. FINANCIAL WORKFLOWS SECTION (Stripe-Like)
               ==================================================== */}
            <section id="financials" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Financial copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-emerald-600">Financial Infrastructure</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Stripe-precision wallets, contract deposits, and recurring billing logs
                        </h2>
                        <p className="text-slate-650 text-sm leading-relaxed font-normal">
                            Stop exposing your enterprise billing to human error. Track financial pipelines with extreme precision. Establish automated multi-currency contracts, hold marketplace funds securely in integrated escrow chambers, and manage instantaneous bank withdrawals in a fully auditable double-entry accounting ledger.
                        </p>
                        
                        <div className="grid grid-cols-2 gap-4">
                            <div className="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
                                <div className="text-emerald-600 font-bold font-mono text-lg">0%</div>
                                <div className="text-xs text-slate-500 mt-1 font-semibold">Escrow Fraud Guarantee</div>
                            </div>
                            <div className="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
                                <div className="text-indigo-650 font-bold font-mono text-lg">&lt;3s</div>
                                <div className="text-xs text-slate-500 mt-1 font-semibold">Wallet Payout Processing</div>
                            </div>
                        </div>
                    </div>

                    {/* Right: Stacked accounting cards (Interactive) */}
                    <div className="lg:col-span-7 space-y-4">
                        
                        {/* Financial Card 1: Invoice Action Card */}
                        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-lg relative overflow-hidden group">
                            <div className="absolute top-0 right-0 h-16 w-16 bg-indigo-500/5 rounded-full blur-xl pointer-events-none" />
                            <div className="flex justify-between items-start">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 bg-indigo-50 rounded flex items-center justify-center">
                                        <FileText className="h-5 w-5 text-indigo-600" />
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-bold text-slate-900">Invoice #INV-2026-904</h4>
                                        <p className="text-xs text-slate-400 font-medium">Draft state. Waiting for payload.</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-xs text-slate-400 font-semibold">Total Billable</div>
                                    <div className="text-sm font-mono font-bold text-slate-900">$4,850.00</div>
                                </div>
                            </div>
                            <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-medium">Customer: Apex Corp Inc.</span>
                                <Button 
                                    size="sm" 
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[11px] px-3.5 py-1.5 h-auto font-semibold"
                                    onClick={() => alert("Simulating Instant Invoice Deployment: API request sent to Acme Portal queue.")}
                                >
                                    Authorize & Send
                                </Button>
                            </div>
                        </div>

                        {/* Financial Card 2: Wallet Payout Card */}
                        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-lg relative overflow-hidden">
                            <div className="flex justify-between items-start">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 bg-emerald-50 rounded flex items-center justify-center">
                                        <Wallet className="h-5 w-5 text-emerald-650" />
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-bold text-slate-900">Available Wallet Balance</h4>
                                        <p className="text-xs text-slate-400 font-medium">Secured in transactional bank vault.</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-sm font-mono font-bold text-emerald-650">$14,820.00</div>
                                    <div className="text-[10px] text-slate-400 font-bold">USD Available</div>
                                </div>
                            </div>
                            <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-medium">Auto-split configured: 15% Tax Lock</span>
                                <span className="text-[11px] text-slate-700 font-semibold bg-slate-100 border border-slate-200/60 px-2 py-1 rounded">
                                    Multi-currency Enabled (USD / EUR / GBP)
                                </span>
                            </div>
                        </div>

                        {/* Financial Card 3: Recurring Ledger Stream */}
                        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-lg">
                            <div className="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                <span>Recurring Subscription Ledger Logs</span>
                                <span className="flex items-center gap-1.5 text-indigo-650 font-bold lowercase">
                                    <span className="h-1.5 w-1.5 bg-indigo-500 rounded-full animate-ping" />
                                    live log feed
                                </span>
                            </div>
                            <div className="space-y-2 font-mono text-[10px] text-slate-650">
                                <div className="flex justify-between p-1.5 bg-slate-50 rounded">
                                    <span className="text-slate-400">[03:42:01]</span>
                                    <span>Syncing Stripe API Payment Intents...</span>
                                    <span className="text-emerald-600 font-bold">SUCCESS</span>
                                </div>
                                <div className="flex justify-between p-1.5 bg-slate-50 rounded">
                                    <span className="text-slate-400">[03:42:15]</span>
                                    <span>Triggering Wallet Commission Split for Client U-881</span>
                                    <span className="text-indigo-600 font-bold">PROCESSED (+$42.50)</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                5. MARKETPLACE & FREELANCE SECTION (Kanban Board)
               ==================================================== */}
            <section id="marketplace" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row md:items-end justify-between mb-16">
                        <div className="max-w-2xl text-left">
                            <span className="text-xs font-semibold uppercase tracking-wider text-purple-650">Ecosystem Workflows</span>
                            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                                Clean, operational freelance contracts and escrow trackers
                            </h2>
                            <p className="text-slate-600 text-sm leading-relaxed mt-4">
                                Skip the basic marketing descriptions of freelance websites. We give you a fully functional project ecosystem showing exactly how proposals shift into active escrowed contracts.
                            </p>
                        </div>
                        {/* Simulation trigger */}
                        <div className="mt-6 md:mt-0 flex gap-2">
                            <Button 
                                size="sm" 
                                onClick={triggerEscrowFunding}
                                className={`rounded px-4 py-2 font-bold text-xs ${
                                    escrowStatus === 'unfunded' 
                                        ? 'bg-indigo-600 hover:bg-indigo-500 text-white' 
                                        : escrowStatus === 'holding' 
                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white' 
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200'
                                }`}
                                disabled={escrowStatus === 'released'}
                            >
                                {escrowStatus === 'unfunded' && 'Fund Milestones ($3,500)'}
                                {escrowStatus === 'holding' && 'Authorize Milestone Release'}
                                {escrowStatus === 'released' && 'Milestone Funds Released to Wallet'}
                            </Button>
                        </div>
                    </div>

                    {/* Operational Kanban Board Layout */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {/* Column 1: Proposals Received */}
                        <div className="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col gap-4">
                            <div className="flex justify-between items-center border-b border-slate-200 pb-2">
                                <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">Proposals Received</span>
                                <span className="bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded text-[10px]">3 Active</span>
                            </div>

                            {/* Card 1 */}
                            <div className="bg-white border border-slate-200 p-4 rounded-lg flex flex-col gap-3 shadow-sm hover:border-slate-300 transition-all">
                                <div className="flex justify-between items-start">
                                    <span className="text-[10px] bg-purple-50 border border-purple-200 text-purple-700 px-2 py-0.5 rounded font-bold uppercase">Web Refactor</span>
                                    <span className="text-xs font-mono font-bold text-slate-900">$3,500.00</span>
                                </div>
                                <h4 className="text-xs font-bold text-slate-800">SaaS Landing Page Design Refactor</h4>
                                <p className="text-[10px] text-slate-550 leading-relaxed line-clamp-2">Complete landing page overhaul matching Linear styling and high-density SaaS structures...</p>
                                <div className="border-t border-slate-100 pt-2 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                                    <span>Freelancer: Sophia L.</span>
                                    <span className="text-emerald-600 font-semibold">★ 5.0 (42 reviews)</span>
                                </div>
                            </div>

                            {/* Card 2 */}
                            <div className="bg-white border border-slate-200 p-4 rounded-lg flex flex-col gap-3 shadow-sm opacity-60">
                                <div className="flex justify-between items-start">
                                    <span className="text-[10px] bg-slate-100 border border-slate-200 text-slate-600 px-2 py-0.5 rounded font-bold uppercase">Full Stack API</span>
                                    <span className="text-xs font-mono font-bold text-slate-900">$6,800.00</span>
                                </div>
                                <h4 className="text-xs font-bold text-slate-800">Node API & Webhook Infrastructure</h4>
                                <p className="text-[10px] text-slate-550 leading-relaxed line-clamp-2">Design real-time webhook endpoints, escrow locks, and wallet integration hooks...</p>
                                <div className="border-t border-slate-100 pt-2 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                                    <span>Freelancer: Alex K.</span>
                                    <span className="text-slate-400">★ 4.9 (12 reviews)</span>
                                </div>
                            </div>
                        </div>

                        {/* Column 2: Escrow System Locked (Milestones) */}
                        <div className="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col gap-4">
                            <div className="flex justify-between items-center border-b border-slate-200 pb-2">
                                <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">Escrow System Protected</span>
                                <span className="bg-indigo-50 border border-indigo-150 text-indigo-650 font-bold px-2 py-0.5 rounded text-[10px]">1 Locked</span>
                            </div>

                            {/* Active Escrow Simulator Card */}
                            <div className={`p-4 rounded-lg flex flex-col gap-4 transition-all duration-500 shadow-sm ${
                                escrowStatus === 'unfunded' 
                                    ? 'bg-white border border-slate-200' 
                                    : escrowStatus === 'holding' 
                                        ? 'bg-indigo-50/40 border-2 border-indigo-400 glow-indigo' 
                                        : 'bg-emerald-50/30 border-2 border-emerald-450 glow-green'
                            }`}>
                                <div className="flex justify-between items-start">
                                    <span className={`text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider border ${
                                        escrowStatus === 'unfunded' 
                                            ? 'bg-slate-100 text-slate-500 border-slate-200' 
                                            : escrowStatus === 'holding' 
                                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 animate-pulse' 
                                                : 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                    }`}>
                                        {escrowStatus === 'unfunded' && 'UNFUNDED'}
                                        {escrowStatus === 'holding' && 'SECURED IN ESCROW'}
                                        {escrowStatus === 'released' && 'RELEASED TO WALLET'}
                                    </span>
                                    <span className="text-xs font-mono font-bold text-slate-900">$3,500.00</span>
                                </div>

                                <div className="flex flex-col gap-1">
                                    <h4 className="text-xs font-bold text-slate-800">SaaS Landing Page Refactor</h4>
                                    <p className="text-[10px] text-slate-400 font-semibold">Contract ID: CON-2026-8812</p>
                                </div>

                                <div className="text-xs p-2.5 bg-slate-50 rounded border border-slate-150 space-y-1">
                                    <div className="flex justify-between text-[10px] font-medium">
                                        <span className="text-slate-450">Escrow Account:</span>
                                        <span className="text-slate-700 font-mono">escrow_292a188f9</span>
                                    </div>
                                    <div className="flex justify-between text-[10px] font-medium">
                                        <span className="text-slate-450">Payment Status:</span>
                                        <span className={`font-bold ${
                                            escrowStatus === 'unfunded' 
                                                ? 'text-slate-400' 
                                                : escrowStatus === 'holding' 
                                                    ? 'text-indigo-600' 
                                                    : 'text-emerald-600'
                                        }`}>
                                            {escrowStatus === 'unfunded' && 'Unfunded'}
                                            {escrowStatus === 'holding' && 'Held & Secured'}
                                            {escrowStatus === 'released' && 'Released'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Column 3: Completed & Transferred */}
                        <div className="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col gap-4">
                            <div className="flex justify-between items-center border-b border-slate-200 pb-2">
                                <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">Completed Transactions</span>
                                <span className="bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded text-[10px]">Archive</span>
                            </div>

                            {/* Static completed contracts */}
                            <div className="bg-white border border-slate-200 p-4 rounded-lg flex flex-col gap-3 opacity-60 shadow-sm">
                                <div className="flex justify-between items-start">
                                    <span className="text-[10px] bg-emerald-50 border border-emerald-250 text-emerald-700 px-2 py-0.5 rounded font-bold uppercase">Brand Design</span>
                                    <span className="text-xs font-mono font-bold text-slate-900">$450.00</span>
                                </div>
                                <h4 className="text-xs font-bold text-slate-800">Musoftware Brand Pack Logo</h4>
                                <div className="text-[10px] p-2 bg-slate-50 rounded border border-slate-150 space-y-1 font-medium">
                                    <div className="flex justify-between">
                                        <span className="text-slate-450">Escrow ID:</span>
                                        <span className="text-slate-700">escrow_292a188f1</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-450">Transferred On:</span>
                                        <span className="text-slate-700">2026-05-16</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                6. UNIFIED WORKSPACE SECTION (Centered Epic Dashboard)
               ==================================================== */}
            <section id="workspace" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div className="max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-650 font-bold">Unified Architecture</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                            One Customer. One Operational Workspace.
                        </h2>
                        <p className="text-slate-600 text-sm leading-relaxed mt-4">
                            Tear down the walls between client relations and operational tracking. Our unified customer dashboard combines transaction audits, invoices, escrow logs, and private internal admin notes under a single user session profile.
                        </p>
                    </div>

                    {/* Centered Large Visual Dashboard Epic */}
                    <div className="relative w-full max-w-5xl mx-auto rounded-xl border border-slate-205 bg-white overflow-hidden shadow-2xl shadow-slate-200/80">
                        
                        {/* Chrome bar */}
                        <div className="h-10 bg-slate-50 border-b border-slate-200 flex items-center px-4 justify-between">
                            <div className="flex space-x-1.5">
                                <div className="w-3 h-3 rounded-full bg-rose-500" />
                                <div className="w-3 h-3 rounded-full bg-amber-500" />
                                <div className="w-3 h-3 rounded-full bg-emerald-500" />
                            </div>
                            <span className="text-xs text-slate-400 font-mono font-bold">dashboard.musoftware.com/admin/clients/acme-corp</span>
                            <div className="w-12 h-2 rounded bg-slate-200" />
                        </div>

                        {/* Dashboard layout */}
                        <div className="grid grid-cols-1 md:grid-cols-12 text-left h-[500px]">
                            
                            {/* Left panel: Customer Overview */}
                            <div className="md:col-span-4 border-r border-slate-200 p-6 flex flex-col justify-between bg-slate-50/40">
                                <div>
                                    <div className="flex items-center gap-3 mb-6">
                                        <div className="h-12 w-12 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white text-lg shadow-md shadow-indigo-500/20">
                                            AC
                                        </div>
                                        <div>
                                            <h4 className="text-sm font-bold text-slate-900">Acme Corporation</h4>
                                            <p className="text-xs text-indigo-600 font-bold">Verified Enterprise Client</p>
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Total Invoice Billings</span>
                                            <div className="text-lg font-mono font-bold text-slate-900">${(42950.00).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                        </div>
                                        <div>
                                            <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Active Escrows Locked</span>
                                            <div className="text-sm font-mono font-bold text-indigo-600">$3,500.00</div>
                                        </div>
                                        <div>
                                            <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Client Wallet balance</span>
                                            <div className="text-sm font-mono font-bold text-emerald-600">$1,450.00</div>
                                        </div>
                                    </div>
                                </div>

                                <div className="pt-6 border-t border-slate-200 space-y-2">
                                    <div className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Workspace Core Actions</div>
                                    <div className="flex gap-2">
                                        <Button size="sm" className="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[10px] py-2 h-auto font-semibold">
                                            Deploy Invoice
                                        </Button>
                                        <Button size="sm" variant="outline" className="flex-1 bg-white hover:bg-slate-50 text-slate-700 border-slate-200 rounded text-[10px] py-2 h-auto">
                                            Private Note
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* Right panel: Active Client Log & Timeline */}
                            <div className="md:col-span-8 p-6 flex flex-col justify-between overflow-y-auto bg-white">
                                <div>
                                    <div className="flex justify-between items-center border-b border-slate-100 pb-3 mb-6">
                                        <h5 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Client Timeline & Transaction Trail</h5>
                                        <span className="bg-indigo-50 border border-indigo-150 text-indigo-700 font-bold px-2 py-0.5 rounded text-[10px]">Updated Live</span>
                                    </div>

                                    {/* Action Logs */}
                                    <div className="space-y-4 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-[1px] before:bg-slate-200">
                                        
                                        <div className="flex items-start gap-4 relative">
                                            <div className="h-6 w-6 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center text-[10px] text-emerald-700 font-bold shrink-0 relative z-10 shadow-sm">✓</div>
                                            <div>
                                                <div className="text-xs font-bold text-slate-900">Milestone 2 payment released from Escrow</div>
                                                <p className="text-[10px] text-slate-500 mt-0.5">Authorization ID: release_8812a. Main wallet balance credited (+$1,800.00).</p>
                                                <span className="text-[9px] text-slate-400 font-semibold mt-1 block">12 hours ago • Automated</span>
                                            </div>
                                        </div>

                                        <div className="flex items-start gap-4 relative">
                                            <div className="h-6 w-6 rounded-full bg-indigo-50 border border-indigo-200 flex items-center justify-center text-[10px] text-indigo-700 font-bold shrink-0 relative z-10 shadow-sm">i</div>
                                            <div>
                                                <div className="text-xs font-bold text-slate-900">Invoice #INV-2026-88 Deploy Success</div>
                                                <p className="text-[10px] text-slate-500 mt-0.5">Dispatched directly to Client Acme Corp Email Queue. Value: $2,450.00.</p>
                                                <span className="text-[9px] text-slate-400 font-semibold mt-1 block">1 day ago • Dispatched</span>
                                            </div>
                                        </div>

                                        <div className="flex items-start gap-4 relative opacity-60">
                                            <div className="h-6 w-6 rounded-full bg-slate-100 border border-slate-250 flex items-center justify-center text-[10px] text-slate-500 font-bold shrink-0 relative z-10 shadow-sm">n</div>
                                            <div>
                                                <div className="text-xs font-bold text-slate-800">System Log: Private internal notes processed</div>
                                                <p className="text-[10px] text-slate-500 mt-0.5">"Client requested advanced modular setup on API. Verify skills match on freelance board."</p>
                                                <span className="text-[9px] text-slate-400 font-semibold mt-1 block">3 days ago • Admin: Mahmo</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-4 text-xs text-slate-400 flex justify-between items-center font-mono font-medium">
                                    <span>Client Profile UUID: usr_99a8820a2e</span>
                                    <span>Sync Status: SECURE CONFLICT PARITY</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                7. REALTIME COMMUNICATION SECTION
               ==================================================== */}
            <section id="realtime" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Chat visualization card */}
                    <div className="lg:col-span-7 bg-slate-50 border border-slate-200 rounded-xl p-5 shadow-lg relative">
                        <div className="flex justify-between items-center border-b border-slate-200 pb-3 mb-4">
                            <div className="flex items-center gap-2">
                                <div className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                                <span className="text-xs font-bold text-slate-800 uppercase tracking-wider">Live System Support Pipeline</span>
                            </div>
                            <span className="text-[10px] text-slate-400 font-mono font-bold">channel: #api-operations-refactor</span>
                        </div>

                        {/* Real-time Chats */}
                        <div className="space-y-4 mb-6">
                            
                            <div className="flex gap-3">
                                <div className="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center font-bold text-xs shrink-0 text-slate-650">SL</div>
                                <div className="flex-1 bg-white p-3 rounded-lg border border-slate-200">
                                    <div className="flex justify-between items-center mb-1 font-semibold">
                                        <span className="text-xs font-bold text-slate-800">Sophia L. (Freelancer)</span>
                                        <span className="text-[9px] text-slate-400">03:41 AM</span>
                                    </div>
                                    <p className="text-xs text-slate-600 leading-relaxed font-medium">I have successfully implemented the Webhook security verification. Can you check the transaction queue in the dashboard?</p>
                                </div>
                            </div>

                            <div className="flex gap-3 justify-end">
                                <div className="flex-1 bg-indigo-50/50 p-3 rounded-lg border border-indigo-150 max-w-md">
                                    <div className="flex justify-between items-center mb-1 font-semibold">
                                        <span className="text-xs font-bold text-indigo-900">David K. (Client Manager)</span>
                                        <span className="text-[9px] text-indigo-650">03:42 AM</span>
                                    </div>
                                    <p className="text-xs text-indigo-850 leading-relaxed font-medium">Awesome! Yes, I see the milestone is fully funded in Escrow. Releasing payments now. Great work!</p>
                                </div>
                                <div className="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-xs shrink-0 text-white shadow-sm">DK</div>
                            </div>

                        </div>

                        {/* Interactive message bar */}
                        <div className="flex gap-2">
                            <input 
                                type="text" 
                                placeholder="Type real-time message to team..." 
                                className="flex-1 bg-white border border-slate-250 rounded px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 font-medium shadow-inner"
                                readOnly
                            />
                            <Button size="sm" className="bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs px-4 font-semibold h-auto">
                                <Send className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    {/* Right: Realtime copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-orange-655 font-bold">Live Infrastructure</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Fast, reactive feedback streams with no latency
                        </h2>
                        <p className="text-slate-605 text-sm leading-relaxed font-normal">
                            Stop guessing if tasks have progressed. Unify real-time chat platforms directly into operational logs. Freelancers and client managers receive instant chat updates, live system notifications, and running timer updates the second events happen.
                        </p>
                        
                        <div className="space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="h-8 w-8 bg-white border border-slate-200 rounded-lg flex items-center justify-center shadow-sm">
                                    <Clock className="h-4.5 w-4.5 text-orange-600" />
                                </div>
                                <span className="text-slate-700 text-xs font-bold">Automatic billable timer synchronizers</span>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="h-8 w-8 bg-white border border-slate-200 rounded-lg flex items-center justify-center shadow-sm">
                                    <Send className="h-4.5 w-4.5 text-indigo-600" />
                                </div>
                                <span className="text-slate-700 text-xs font-bold">Interactive direct chat contract bindings</span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                8. ADMIN OPERATIONS SECTION
               ==================================================== */}
            <section id="admin-ops" className="bg-slate-50/30 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-semibold uppercase tracking-wider text-rose-600 font-bold">Administrative Power</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                            Admin Operations Control Center
                        </h2>
                        <p className="text-slate-600 text-sm leading-relaxed mt-4">
                            The difference between a basic SaaS landing page and an enterprise business engine is operational management. Leverage robust backend tools for dispute resolution, direct ledger moderation, fraud tracking, and bank transfer authorizations.
                        </p>
                    </div>

                    {/* Operations Console Table */}
                    <div className="w-full rounded-xl border border-slate-200 bg-white overflow-hidden shadow-xl">
                        
                        {/* Chrome bar */}
                        <div className="bg-slate-50 px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h4 className="text-sm font-bold text-slate-900">System Withdrawal Approval Console</h4>
                                <p className="text-xs text-slate-400 font-medium mt-0.5">Authorizations queue for outbound wallet transfers.</p>
                            </div>
                            <div className="inline-flex items-center gap-2 bg-rose-50 border border-rose-200 rounded-full px-3 py-1 text-xs text-rose-700 font-semibold">
                                <ShieldAlert className="h-3.5 w-3.5" />
                                High Security Authorization Mode Active
                            </div>
                        </div>

                        {/* Interactive Admin Queue Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr className="bg-slate-50/40 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                        <th className="px-6 py-3.5">Request ID</th>
                                        <th className="px-6 py-3.5">User</th>
                                        <th className="px-6 py-3.5">Amount</th>
                                        <th className="px-6 py-3.5">Method</th>
                                        <th className="px-6 py-3.5">Status</th>
                                        <th className="px-6 py-3.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 font-medium text-slate-700">
                                    {adminRequests.map((req) => (
                                        <tr key={req.id} className="hover:bg-slate-50/40 transition-colors">
                                            <td className="px-6 py-4 font-mono text-slate-400">{req.id}</td>
                                            <td className="px-6 py-4 text-slate-950 font-bold">{req.user}</td>
                                            <td className="px-6 py-4 font-mono font-bold text-slate-950">${req.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                            <td className="px-6 py-4 text-slate-500">{req.type}</td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase border ${
                                                    req.status === 'pending' 
                                                        ? 'bg-amber-50 border-amber-200 text-amber-700' 
                                                        : 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                                }`}>
                                                    {req.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                {req.status === 'pending' ? (
                                                    <Button 
                                                        size="sm" 
                                                        onClick={() => approveWithdrawal(req.id, req.amount)}
                                                        className="bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] px-3.5 py-1.5 h-auto font-semibold shadow-sm"
                                                    >
                                                        Authorize payout
                                                    </Button>
                                                ) : (
                                                    <span className="text-slate-400 text-[10px] font-mono font-semibold">TX-SECURE-RELEASE</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                9. CUSTOMER EXPERIENCE SECTION
               ==================================================== */}
            <section id="customer-exp" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-650 font-bold">Client Experience Portal</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Stunningly simple for your clients, robust for you
                        </h2>
                        <p className="text-slate-655 text-sm leading-relaxed font-normal">
                            Stop training your clients to use confusing system frameworks. We separate complexity seamlessly. While you navigate dense ERP tables, contract pipelines, and audit trails, your client accesses a white-labeled dashboard designed for extreme clarity and fast payments.
                        </p>
                        
                        {/* Selector tabs to toggle visual simulator */}
                        <div className="flex gap-2 p-1 bg-slate-100 border border-slate-200 rounded-lg w-fit">
                            <button
                                onClick={() => setPortalView('freelancer')}
                                className={`px-4 py-2 rounded text-xs font-bold transition-all ${
                                    portalView === 'freelancer' 
                                        ? 'bg-indigo-600 text-white shadow-sm' 
                                        : 'text-slate-500 hover:text-slate-900'
                                }`}
                            >
                                Manager View
                            </button>
                            <button
                                onClick={() => setPortalView('client')}
                                className={`px-4 py-2 rounded text-xs font-bold transition-all ${
                                    portalView === 'client' 
                                        ? 'bg-indigo-600 text-white shadow-sm' 
                                        : 'text-slate-500 hover:text-slate-900'
                                }`}
                            >
                                Client view
                            </button>
                        </div>
                    </div>

                    {/* Right: Dynamic Interface Previews */}
                    <div className="lg:col-span-7">
                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-5 shadow-xl min-h-[300px] flex flex-col justify-between transition-all duration-300 transform">
                            {portalView === 'freelancer' ? (
                                <div className="space-y-6 animate-fade-in">
                                    <div className="flex justify-between items-center border-b border-slate-200 pb-3">
                                        <div>
                                            <h4 className="text-sm font-bold text-slate-900">Corporate Management Workspace</h4>
                                            <p className="text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-wider">Advanced developer controls & ledger tracking</p>
                                        </div>
                                        <span className="bg-indigo-50 border border-indigo-200 text-indigo-750 font-bold px-2 py-0.5 rounded text-[10px]">Manager Access</span>
                                    </div>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div className="p-3 bg-white rounded border border-slate-200 shadow-sm">
                                            <div className="text-[10px] text-slate-405 font-bold uppercase tracking-wider">Gross P&L Margin</div>
                                            <div className="text-sm font-mono font-bold text-slate-900 mt-1">94.2%</div>
                                        </div>
                                        <div className="p-3 bg-white rounded border border-slate-200 shadow-sm">
                                            <div className="text-[10px] text-slate-405 font-bold uppercase tracking-wider">Escrow Release rate</div>
                                            <div className="text-sm font-mono font-bold text-indigo-600 mt-1">100.0%</div>
                                        </div>
                                        <div className="p-3 bg-white rounded border border-slate-200 shadow-sm">
                                            <div className="text-[10px] text-slate-405 font-bold uppercase tracking-wider">System tax lock</div>
                                            <div className="text-sm font-mono font-bold text-emerald-650 mt-1">15% Hold</div>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Active Ledger Invoices</span>
                                        <div className="flex justify-between text-xs p-2 bg-white rounded border border-slate-200 font-mono font-medium shadow-sm">
                                            <span className="text-slate-600">#INV-2026-881</span>
                                            <span className="text-emerald-650 font-bold">$3,500.00 (Cleared)</span>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-6 animate-fade-in">
                                    <div className="flex justify-between items-center border-b border-slate-200 pb-3">
                                        <div>
                                            <h4 className="text-sm font-bold text-slate-900">Client Billing Hub</h4>
                                            <p className="text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-wider">Minimalist portal designed for immediate approvals</p>
                                        </div>
                                        <span className="bg-emerald-50 border border-emerald-255 text-emerald-700 font-bold px-2 py-0.5 rounded text-[10px]">Client Access</span>
                                    </div>
                                    
                                    <div className="p-4 bg-emerald-50 border-2 border-emerald-200 rounded-lg flex justify-between items-center glow-green">
                                        <div>
                                            <h5 className="text-xs font-bold text-slate-900">Invoice #INV-2026-904 Due</h5>
                                            <p className="text-[10px] text-slate-500 font-medium mt-0.5">Milestone 2 Refactor. Balance: $2,450.00.</p>
                                        </div>
                                        <Button 
                                            size="sm" 
                                            onClick={() => alert("Simulating instantaneous secure client invoice payment via Stripe Gateway.")}
                                            className="bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[11px] px-4 py-2 font-bold h-auto border-0 shadow-sm"
                                        >
                                            Authorize Payment ($2,450)
                                        </Button>
                                    </div>

                                    <div className="space-y-2">
                                        <span className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Recent Invoice Payments Cleared</span>
                                        <div className="flex justify-between text-xs p-2.5 bg-white rounded border border-slate-200 shadow-sm">
                                            <span className="text-slate-700 font-bold">#INV-2026-881 Brand Design Pack</span>
                                            <span className="text-slate-400 font-mono font-bold">$450.00 • Paid</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="border-t border-slate-200 pt-4 text-xs text-slate-400 font-mono text-center font-semibold">
                                White-label Domain Configured: portal.acme-corp.com
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            {/* ====================================================
                10. INTEGRATIONS / AUTOMATION
               ==================================================== */}
            <section id="automation" className="bg-slate-50/30 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                    
                    {/* Left: Code Sandbox Console (Dark themed inside light page for high contrast coding feel) */}
                    <div className="lg:col-span-7 bg-zinc-900 border border-zinc-950 rounded-xl p-5 shadow-2xl flex flex-col justify-between min-h-[350px]">
                        <div>
                            <div className="flex justify-between items-center border-b border-zinc-800 pb-3 mb-4">
                                <div className="flex items-center gap-2">
                                    <Terminal className="h-4 w-4 text-indigo-400" />
                                    <span className="text-xs font-bold text-white uppercase tracking-wider font-mono">Developer API Sandbox</span>
                                </div>
                                <div className="flex gap-1.5 bg-zinc-950 p-1 border border-zinc-850 rounded">
                                    <button
                                        onClick={() => setActiveApiTab('curl')}
                                        className={`px-2.5 py-1 rounded text-[10px] font-mono font-bold transition-all ${
                                            activeApiTab === 'curl' ? 'bg-indigo-600 text-white' : 'text-zinc-500 hover:text-zinc-300'
                                        }`}
                                    >
                                        cURL
                                    </button>
                                    <button
                                        onClick={() => setActiveApiTab('nodejs')}
                                        className={`px-2.5 py-1 rounded text-[10px] font-mono font-bold transition-all ${
                                            activeApiTab === 'nodejs' ? 'bg-indigo-600 text-white' : 'text-zinc-500 hover:text-zinc-300'
                                        }`}
                                    >
                                        Node.js
                                    </button>
                                    <button
                                        onClick={() => setActiveApiTab('python')}
                                        className={`px-2.5 py-1 rounded text-[10px] font-mono font-bold transition-all ${
                                            activeApiTab === 'python' ? 'bg-indigo-600 text-white' : 'text-zinc-500 hover:text-zinc-300'
                                        }`}
                                    >
                                        Python
                                    </button>
                                </div>
                            </div>

                            {/* Fenced glowing code block */}
                            <pre className="p-4 bg-zinc-950 rounded border border-zinc-900 overflow-x-auto text-[10px] font-mono text-zinc-300 leading-relaxed max-h-[220px]">
                                {apiSnippets[activeApiTab]}
                            </pre>
                        </div>
                        <div className="border-t border-zinc-800 pt-4 text-xs text-zinc-500 font-mono flex justify-between items-center font-semibold">
                            <span>Request Rate Limit: 100 req/sec</span>
                            <span>SDK V4.1 Stable</span>
                        </div>
                    </div>

                    {/* Right: Integration copy */}
                    <div className="lg:col-span-5 flex flex-col space-y-6">
                        <span className="text-xs font-semibold uppercase tracking-wider text-purple-650 font-bold">Developer Automation</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Scale via raw API access, webhooks, and SaaS SDKs
                        </h2>
                        <p className="text-slate-605 text-sm leading-relaxed font-normal">
                            Developer experience is key. Programmatically dispatch platform invoices, query escrows, split balances, check freelancer skills directories, and trigger webhook payloads the exact second invoice balances change.
                        </p>
                        
                        <ul className="space-y-3">
                            <li className="flex items-center gap-2 text-xs text-slate-700">
                                <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                <span className="font-semibold">Zero-latency webhook event streams</span>
                            </li>
                            <li className="flex items-center gap-2 text-xs text-slate-700">
                                <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                <span className="font-semibold">Full language SDK wrappers (JS, Python, Go)</span>
                            </li>
                        </ul>
                    </div>

                </div>
            </section>

            {/* ====================================================
                11. ANALYTICS & REPORTING
               ==================================================== */}
            <section id="analytics" className="bg-white py-24 border-b border-slate-100 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-semibold uppercase tracking-wider text-emerald-600">Business Intelligence</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                            High-Fidelity BI Reporting & Wallet Analytics
                        </h2>
                        <p className="text-slate-600 text-sm leading-relaxed mt-4">
                            Monitor cash flow velocity with realistic corporate metrics. Swap perspectives instantly to audit earnings growth, locked escrow volumes, and client ledger activities.
                        </p>
                    </div>

                    {/* BI Panel Dashboard Card */}
                    <div className="relative w-full max-w-4xl mx-auto rounded-xl border border-slate-200 bg-white overflow-hidden shadow-2xl shadow-slate-100/60">
                        
                        {/* Chrome header with tab switcher */}
                        <div className="bg-slate-50 px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h4 className="text-sm font-bold text-slate-900">Earnings Analytics Engine</h4>
                                <p className="text-xs text-slate-400 mt-0.5">Real-time ledger audit trends</p>
                            </div>
                            <div className="flex gap-1.5 p-1 bg-slate-100 border border-slate-200 rounded-lg">
                                <button
                                    onClick={() => setActiveChartTab('revenue')}
                                    className={`px-3 py-1.5 rounded text-xs font-bold transition-all ${
                                        activeChartTab === 'revenue' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                >
                                    Revenue Growth
                                </button>
                                <button
                                    onClick={() => setActiveChartTab('wallet')}
                                    className={`px-3 py-1.5 rounded text-xs font-bold transition-all ${
                                        activeChartTab === 'wallet' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                >
                                    Wallet Trends
                                </button>
                            </div>
                        </div>

                        {/* Gorgeous vector SVG Chart block */}
                        <div className="p-6 bg-white relative border-b border-slate-200">
                            <div className="absolute top-4 right-6 flex items-center gap-4 text-xs font-bold">
                                <div className="flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-indigo-500" />
                                    <span className="text-slate-700">Earnings Current Year</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-slate-350" />
                                    <span className="text-slate-400">Benchmark Target</span>
                                </div>
                            </div>

                            {/* Responsive vector graph viewport */}
                            <div className="w-full h-[240px] mt-4">
                                <svg className="w-full h-full" viewBox="0 0 800 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    {/* Grid Lines */}
                                    <line x1="0" y1="40" x2="800" y2="40" stroke="#e2e8f0" strokeWidth="1" strokeDasharray="4 4" />
                                    <line x1="0" y1="100" x2="800" y2="100" stroke="#e2e8f0" strokeWidth="1" strokeDasharray="4 4" />
                                    <line x1="0" y1="160" x2="800" y2="160" stroke="#e2e8f0" strokeWidth="1" strokeDasharray="4 4" />
                                    <line x1="0" y1="220" x2="800" y2="220" stroke="#cbd5e1" strokeWidth="1" />

                                    {/* Month Coordinates: Jan (40) Feb (160) Mar (280) Apr (400) May (520) Jun (640) Jul (760) */}
                                    {/* Dynamic Path 1: Active Revenue Curve */}
                                    {activeChartTab === 'revenue' ? (
                                        <path 
                                            d="M 40 200 Q 160 180 280 120 T 520 60 T 760 30" 
                                            fill="none" 
                                            stroke="url(#indigo-grad)" 
                                            strokeWidth="3.5" 
                                            strokeLinecap="round"
                                            className="animate-chart-path"
                                        />
                                    ) : (
                                        <path 
                                            d="M 40 180 Q 160 140 280 160 T 520 100 T 760 60" 
                                            fill="none" 
                                            stroke="url(#emerald-grad)" 
                                            strokeWidth="3.5" 
                                            strokeLinecap="round"
                                        />
                                    )}

                                    {/* Path 2: Target Benchmark Path */}
                                    <path d="M 40 210 L 760 100" fill="none" stroke="#94a3b8" strokeWidth="1.5" strokeDasharray="6 6" />

                                    {/* Dynamic path gradients */}
                                    <defs>
                                        <linearGradient id="indigo-grad" x1="0" y1="0" x2="1" y2="0">
                                            <stop offset="0%" stopColor="#6366f1" />
                                            <stop offset="100%" stopColor="#a855f7" />
                                        </linearGradient>
                                        <linearGradient id="emerald-grad" x1="0" y1="0" x2="1" y2="0">
                                            <stop offset="0%" stopColor="#10b981" />
                                            <stop offset="100%" stopColor="#06b6d4" />
                                        </linearGradient>
                                    </defs>

                                    {/* Data Labels */}
                                    <text x="40" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">JAN</text>
                                    <text x="160" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">FEB</text>
                                    <text x="280" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">MAR</text>
                                    <text x="400" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">APR</text>
                                    <text x="520" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">MAY</text>
                                    <text x="640" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">JUN</text>
                                    <text x="760" y="235" fill="#64748b" fontSize="9" textAnchor="middle" fontWeight="bold">JUL</text>
                                </svg>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                12. PRICING SECTION
               ==================================================== */}
            <section id="pricing" className="bg-slate-50/40 py-24 border-b border-slate-200/80 relative z-10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-650 font-bold">Subscription Plans</span>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">
                            Clean, corporate scale pricing. No tricks.
                        </h2>
                        <p className="text-slate-600 text-sm leading-relaxed mt-4 font-normal">
                            Select the operational tier matching your transaction velocity. Every plan includes deep escrow system protection, client communication modules, and standard wallet ledgers.
                        </p>
                        
                        {/* Billing Switcher Toggle */}
                        <div className="mt-8 flex items-center justify-center gap-4 font-bold">
                            <span className={`text-sm ${billingCycle === 'monthly' ? 'text-slate-900 font-bold' : 'text-slate-400'}`}>Monthly Billing</span>
                            <button
                                onClick={() => setBillingCycle(billingCycle === 'monthly' ? 'yearly' : 'monthly')}
                                className="relative h-6 w-11 rounded-full bg-slate-200 border border-slate-350 transition-colors focus:outline-none flex items-center p-0.5"
                                aria-label="Toggle Billing Cycle"
                            >
                                <span className={`h-4.5 w-4.5 rounded-full bg-indigo-600 transition-transform ${
                                    billingCycle === 'yearly' ? 'translate-x-5' : 'translate-x-0'
                                }`} />
                            </button>
                            <span className={`text-sm flex items-center gap-1.5 ${billingCycle === 'yearly' ? 'text-slate-900 font-bold' : 'text-slate-400'}`}>
                                Yearly Billing
                                <span className="bg-emerald-50 border border-emerald-250 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded">
                                    Save 20%
                                </span>
                            </span>
                        </div>
                    </div>

                    {/* Pricing Cards Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto">
                        
                        {/* Plan 1: Starter Developer */}
                        <div className="bg-white border border-slate-200 rounded-xl p-8 flex flex-col justify-between hover:border-slate-350 transition-all shadow-md">
                            <div>
                                <div className="text-xs font-bold text-indigo-650 uppercase tracking-widest">Starter Tier</div>
                                <h3 className="text-xl font-extrabold text-slate-900 mt-1">Individual Freelancer</h3>
                                <p className="text-slate-500 text-xs mt-2 font-medium">Deploy single projects and manage client wallets easily.</p>
                                
                                <div className="mt-6 flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">${billingCycle === 'monthly' ? '49' : '39'}</span>
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
                                <div className="text-xs font-bold text-indigo-650 uppercase tracking-widest">Business Tier</div>
                                <h3 className="text-xl font-extrabold text-slate-900 mt-1">Agency & Studio</h3>
                                <p className="text-slate-500 text-xs mt-2 font-medium">Unified operational workspace for team billing and escrows.</p>
                                
                                <div className="mt-6 flex items-baseline text-slate-900">
                                    <span className="text-4xl font-extrabold font-mono">${billingCycle === 'monthly' ? '149' : '119'}</span>
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
                                        <span>Priority Dispute Arbitration support</span>
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
                        <div className="bg-white border border-slate-200 rounded-xl p-8 flex flex-col justify-between hover:border-slate-355 transition-all shadow-md">
                            <div>
                                <div className="text-xs font-bold text-indigo-650 uppercase tracking-widest">Enterprise Tier</div>
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
                                        <span>Dedicated Legal Account Representative</span>
                                    </li>
                                    <li className="flex items-center gap-2.5">
                                        <Check className="h-4 w-4 text-indigo-600 shrink-0" />
                                        <span>Enterprise Rate Limit Sandbox</span>
                                    </li>
                                </ul>
                            </div>
                            <a href="mailto:hello@musoftwares.com?subject=Enterprise Query" className="mt-8">
                                <Button variant="outline" className="w-full bg-white hover:bg-slate-50 text-slate-700 border-slate-200 rounded-lg py-2.5 font-bold">
                                    Contact Accounts Department
                                </Button>
                            </a>
                        </div>

                    </div>
                </div>
            </section>

            {/* ====================================================
                13. FAQ SECTION (Accordion)
               ==================================================== */}
            <section id="faq" className="bg-white py-24 border-b border-slate-100 relative z-10">
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
                            <div key={idx} className="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden transition-all shadow-sm">
                                <button
                                    onClick={() => toggleFaq(idx)}
                                    className="w-full px-6 py-5 text-left flex justify-between items-center gap-4 hover:bg-slate-100/50 focus:outline-none transition-colors"
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
                                    <p className="px-6 py-5 text-xs text-slate-600 leading-relaxed bg-slate-100/20">
                                        {faq.a}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ====================================================
                14. FINAL CTA SECTION
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
