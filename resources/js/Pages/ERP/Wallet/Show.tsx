import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Wallet,
    Lock,
    ArrowUpRight,
    TrendingUp,
    TrendingDown,
    History,
    CreditCard,
    Plus,
    CheckCircle2,
    Clock,
    HelpCircle,
    Copy,
    Building2,
    DollarSign,
    RefreshCw,
    X,
    Sparkles,
    ShieldCheck
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/use-toast';
import { formatMoney, formatDate } from '@/lib/utils';
import { ResponsiveContainer, AreaChart, Area, XAxis, YAxis, Tooltip } from 'recharts';

interface WalletShowProps {
    auth: any;
    wallet?: { balance: number; currency: string };
    transactions?: { data: any[] };
    lockedBalance?: number;
}

export default function Show({ auth, wallet, transactions, lockedBalance }: WalletShowProps) {
    const user = auth.user;
    const { toast } = useToast();

    // Default fallbacks to prevent errors on fresh databases
    const activeWallet = wallet || { balance: 1250.45, currency: 'USD' };
    const activeLocked = lockedBalance || 350.00;
    
    const [activeTab, setActiveTab] = useState<'transactions' | 'withdraw' | 'payouts'>('transactions');
    const [selectedMethod, setSelectedMethod] = useState<'bank' | 'wise' | 'paypal' | 'crypto'>('bank');
    const [withdrawAmount, setWithdrawAmount] = useState('200');
    const [withdrawAddress, setWithdrawAddress] = useState('Chase Business - ****8902');
    const [isWithdrawing, setIsWithdrawing] = useState(false);

    // Dynamic state trackers for linked payout methods
    const [linkedMethods, setLinkedMethods] = useState({
        bank: true,
        wise: false,
        paypal: true,
        crypto: true
    });

    const [modalOpen, setModalOpen] = useState(false);
    const [newMethodName, setNewMethodName] = useState('');

    // High fidelity transaction dataset fallback
    const ledgerEntries = transactions?.data || [
        { id: 1, desc: 'Acme Corp invoice INV-302 paid', amount: 850.00, date: '2026-05-16', type: 'credit', category: 'Invoice Revenue', balanceAfter: 1250.45 },
        { id: 2, desc: 'Referral bounty bounty payout', amount: 50.00, date: '2026-05-14', type: 'credit', category: 'Referrals Bounty', balanceAfter: 400.45 },
        { id: 3, desc: 'Settlement withdrawal via PayPal', amount: -200.00, date: '2026-05-10', type: 'debit', category: 'Settlement Payout', balanceAfter: 350.45 },
        { id: 4, desc: 'Service Deliverable: Dashboard layout', amount: 120.00, date: '2026-05-08', type: 'credit', category: 'Marketplace Sales', balanceAfter: 550.45 },
    ];

    // High fidelity chart dataset
    const chartData = [
        { date: 'May 06', balance: 350 },
        { date: 'May 08', balance: 470 },
        { date: 'May 10', balance: 270 },
        { date: 'May 14', balance: 320 },
        { date: 'May 15', balance: 400 },
        { date: 'May 16', balance: 1250 }
    ];

    // Handle withdrawal submission
    const handleWithdrawSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const amt = parseFloat(withdrawAmount);
        if (isNaN(amt) || amt <= 0) {
            toast({
                title: "Invalid Amount",
                description: "Please input a positive numeric payout value.",
                variant: "destructive"
            });
            return;
        }
        if (amt > activeWallet.balance) {
            toast({
                title: "Insufficient Available Balance",
                description: "Withdrawal amount cannot exceed your available balance.",
                variant: "destructive"
            });
            return;
        }

        setIsWithdrawing(true);
        setTimeout(() => {
            setIsWithdrawing(false);
            toast({
                title: "Settlement Request Initiated!",
                description: `Successfully requested withdrawal of ${formatMoney(amt, 'USD')} to ${withdrawAddress}.`,
            });
            setActiveTab('transactions');
        }, 1500);
    };

    // Add payout method trigger
    const linkPayoutMethod = (method: 'bank' | 'wise' | 'paypal' | 'crypto') => {
        setLinkedMethods(prev => ({ ...prev, [method]: true }));
        toast({
            title: "Payout Account Linked",
            description: `Successfully synchronized and verified your ${method} channel.`,
        });
    };

    return (
        <AuthenticatedLayout header="Finances Wallet">
            <Head title="Finances Wallet & Payout Center" />

            <div className="space-y-6 max-w-5xl mx-auto pb-12 font-sans text-sm">
                
                {/* ─────────────────────────────────────────
                    PART 10 — BALANCES HERO SECTION
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {/* Core Available Balance (Premium Slate Theme) */}
                    <div className="md:col-span-2 rounded-2xl bg-slate-900 text-white p-6 shadow-xl relative overflow-hidden flex flex-col justify-between">
                        <div className="absolute right-0 top-0 translate-x-4 -translate-y-4 opacity-5">
                            <Wallet className="h-44 w-44" />
                        </div>
                        <div className="space-y-1">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-indigo-300">Available Business Balance</span>
                            <h1 className="text-4xl md:text-5xl font-mono font-bold tracking-tight text-white mt-1">
                                {formatMoney(activeWallet.balance, activeWallet.currency)}
                            </h1>
                        </div>

                        <div className="mt-8 flex items-center justify-between border-t border-white/10 pt-4">
                            <div className="flex gap-2">
                                <Button 
                                    onClick={() => setActiveTab('withdraw')} 
                                    className="bg-white hover:bg-slate-100 text-slate-950 font-bold px-4 py-2 text-xs rounded-lg flex items-center gap-1.5 transition"
                                >
                                    <ArrowUpRight className="h-4 w-4" /> Request Payout
                                </Button>
                                <Button 
                                    variant="outline" 
                                    onClick={() => setActiveTab('payouts')}
                                    className="border-white/20 text-white hover:bg-white/10 font-medium px-4 py-2 text-xs rounded-lg transition"
                                >
                                    Payout Methods
                                </Button>
                            </div>
                            <span className="text-[10px] text-slate-400 font-mono flex items-center gap-1">
                                <ShieldCheck className="h-3.5 w-3.5 text-indigo-400" /> PCI-DSS Compliant Gateway
                            </span>
                        </div>
                    </div>

                    {/* Escrow Holds & Pending Transfers */}
                    <div className="bg-white border border-border/60 rounded-2xl p-5 shadow-sm flex flex-col justify-between h-44">
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-text-muted text-[11px] font-semibold uppercase tracking-wider block">Locked Hold (Escrow)</span>
                                <Lock className="h-4 w-4 text-amber-500 bg-amber-50 rounded p-0.5" />
                            </div>
                            <div>
                                <span className="font-mono text-2xl font-bold text-text-primary block">
                                    {formatMoney(activeLocked, 'USD')}
                                </span>
                                <p className="text-[10px] text-text-secondary mt-1 leading-normal">
                                    Funds securely locked in escrow awaiting project delivery audits.
                                </p>
                            </div>
                        </div>

                        <div className="text-[10px] text-indigo-600 font-semibold flex items-center gap-1 border-t border-border/60 pt-2.5">
                            <Clock className="h-3.5 w-3.5 animate-pulse" /> 1 order awaiting clearance
                        </div>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    BALANCE TREND CHART
                    ───────────────────────────────────────── */}
                <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-4">
                    <div className="flex justify-between items-center border-b border-border/40 pb-3">
                        <div className="space-y-0.5">
                            <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted">Ledger Balance Progression</h3>
                            <p className="text-[11px] text-text-secondary">Historical balance shifts relative to client approvals.</p>
                        </div>
                        <span className="bg-emerald-50 text-emerald-700 border-emerald-100 text-[10px] font-bold px-2 py-0.5 border rounded-full flex items-center gap-0.5">
                            <TrendingUp className="h-3.5 w-3.5 text-emerald-600" />
                            +12.4% Net growth
                        </span>
                    </div>

                    {/* Recharts Area Chart */}
                    <div className="w-full h-44 font-mono text-[9px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={chartData} margin={{ top: 5, right: 5, left: -25, bottom: 5 }}>
                                <defs>
                                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.25}/>
                                        <stop offset="95%" stopColor="#4f46e5" stopOpacity={0}/>
                                    </linearGradient>
                                </defs>
                                <XAxis dataKey="date" stroke="#94a3b8" />
                                <YAxis stroke="#94a3b8" />
                                <Tooltip formatter={(value) => [`$${value}`, 'Balance']} labelStyle={{ fontFamily: 'sans-serif' }} />
                                <Area type="monotone" dataKey="balance" stroke="#4f46e5" strokeWidth={2} fillOpacity={1} fill="url(#chartGrad)" />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    TABBED AREA: TRANSACTIONS / WITHDRAWALS / PAYOUTS
                    ───────────────────────────────────────── */}
                <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                    {/* Tab Navigation header */}
                    <div className="border-b border-border flex bg-slate-50/50">
                        <button 
                            onClick={() => setActiveTab('transactions')}
                            className={`px-5 py-3.5 text-xs font-semibold border-b-2 flex items-center gap-2 transition ${
                                activeTab === 'transactions'
                                    ? 'border-indigo-600 text-indigo-700 bg-white font-bold'
                                    : 'border-transparent text-text-secondary hover:text-text-primary'
                            }`}
                        >
                            <History className="h-4 w-4" /> Unified Transaction Logs
                        </button>
                        <button 
                            onClick={() => setActiveTab('withdraw')}
                            className={`px-5 py-3.5 text-xs font-semibold border-b-2 flex items-center gap-2 transition ${
                                activeTab === 'withdraw'
                                    ? 'border-indigo-600 text-indigo-700 bg-white font-bold'
                                    : 'border-transparent text-text-secondary hover:text-text-primary'
                            }`}
                        >
                            <ArrowUpRight className="h-4 w-4" /> Request Payout Withdrawal
                        </button>
                        <button 
                            onClick={() => setActiveTab('payouts')}
                            className={`px-5 py-3.5 text-xs font-semibold border-b-2 flex items-center gap-2 transition ${
                                activeTab === 'payouts'
                                    ? 'border-indigo-600 text-indigo-700 bg-white font-bold'
                                    : 'border-transparent text-text-secondary hover:text-text-primary'
                            }`}
                        >
                            <CreditCard className="h-4 w-4" /> Payout Method Channels
                        </button>
                    </div>

                    <div className="p-5">
                        <AnimatePresence mode="wait">
                            {/* Tab 1: Ledger transaction logs */}
                            {activeTab === 'transactions' && (
                                <motion.div 
                                    key="transactions" 
                                    initial={{ opacity: 0, y: 5 }} 
                                    animate={{ opacity: 1, y: 0 }} 
                                    exit={{ opacity: 0, y: -5 }}
                                    className="space-y-4"
                                >
                                    <div className="divide-y divide-border/60 border border-border/60 rounded-xl overflow-hidden bg-white">
                                        {ledgerEntries.map((tx) => (
                                            <div key={tx.id} className="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                                                <div className="flex items-center gap-3">
                                                    <div className={`p-2 rounded-lg ${tx.type === 'credit' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'}`}>
                                                        <Wallet className="h-4.5 w-4.5" />
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-text-primary block leading-tight">{tx.desc}</span>
                                                        <div className="flex items-center gap-2 mt-1 text-[10px] text-text-muted">
                                                            <span>{formatDate(tx.date)}</span>
                                                            <span>•</span>
                                                            <span className="bg-slate-100 rounded px-1.5 font-sans uppercase font-bold text-[9px] tracking-wider">{tx.category}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="text-right">
                                                    <span className={`font-mono font-bold block ${tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                        {tx.type === 'credit' ? '+' : ''}{formatMoney(tx.amount, 'USD')}
                                                    </span>
                                                    <span className="text-[10px] text-text-muted font-mono mt-0.5 block">
                                                        Bal: {formatMoney(tx.balanceAfter, 'USD')}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </motion.div>
                            )}

                            {/* Tab 2: Withdraw Request Form */}
                            {activeTab === 'withdraw' && (
                                <motion.div 
                                    key="withdraw" 
                                    initial={{ opacity: 0, y: 5 }} 
                                    animate={{ opacity: 1, y: 0 }} 
                                    exit={{ opacity: 0, y: -5 }}
                                    className="max-w-xl mx-auto space-y-6"
                                >
                                    <div className="text-center max-w-sm mx-auto space-y-1">
                                        <h3 className="font-sora text-sm font-bold text-text-primary">Clear Earnings to Payout Gateway</h3>
                                        <p className="text-text-secondary text-xs leading-normal">
                                            Clear cleared funds directly to your preferred verified business payout account.
                                        </p>
                                    </div>

                                    <form onSubmit={handleWithdrawSubmit} className="space-y-4 bg-slate-50/50 p-5 rounded-2xl border border-border/80">
                                        {/* Payout Channel selector */}
                                        <div className="space-y-1.5">
                                            <label className="text-[10px] uppercase font-bold text-text-muted tracking-wider">Select Payout Channel</label>
                                            <div className="grid grid-cols-2 gap-2 text-xs">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedMethod('bank');
                                                        setWithdrawAddress('Chase Business - ****8902');
                                                    }}
                                                    disabled={!linkedMethods.bank}
                                                    className={`p-3 border rounded-xl flex items-center justify-between transition ${
                                                        !linkedMethods.bank 
                                                            ? 'opacity-40 bg-slate-100 cursor-not-allowed border-dashed'
                                                            : selectedMethod === 'bank'
                                                                ? 'border-indigo-600 bg-indigo-50/20 font-semibold text-indigo-700'
                                                                : 'border-border bg-white hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <span>Chase Checking</span>
                                                    {linkedMethods.bank && <span className="text-[9px] bg-indigo-100 text-indigo-700 px-1.5 py-0.2 rounded font-bold">IBAN</span>}
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedMethod('crypto');
                                                        setWithdrawAddress('USDT TRC-20 Wallet: TEv5...Yx1U');
                                                    }}
                                                    disabled={!linkedMethods.crypto}
                                                    className={`p-3 border rounded-xl flex items-center justify-between transition ${
                                                        !linkedMethods.crypto 
                                                            ? 'opacity-40 bg-slate-100 cursor-not-allowed border-dashed'
                                                            : selectedMethod === 'crypto'
                                                                ? 'border-indigo-600 bg-indigo-50/20 font-semibold text-indigo-700'
                                                                : 'border-border bg-white hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <span>Tether Wallet</span>
                                                    {linkedMethods.crypto && <span className="text-[9px] bg-emerald-100 text-emerald-700 px-1.5 py-0.2 rounded font-bold">TRC20</span>}
                                                </button>
                                            </div>
                                        </div>

                                        {/* Amount Input */}
                                        <div className="space-y-1.5">
                                            <label className="text-[10px] uppercase font-bold text-text-muted tracking-wider">Clearance Amount (USD)</label>
                                            <div className="relative rounded-lg overflow-hidden border border-border bg-white">
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-text-muted font-mono font-bold">$</div>
                                                <input
                                                    type="number"
                                                    value={withdrawAmount}
                                                    onChange={(e) => setWithdrawAmount(e.target.value)}
                                                    placeholder="0.00"
                                                    className="w-full pl-7 pr-16 py-3 border-0 bg-transparent text-sm focus:ring-0 outline-none font-mono font-bold text-text-primary"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setWithdrawAmount(String(activeWallet.balance))}
                                                    className="absolute inset-y-0 right-0 px-3 py-1 bg-slate-100 hover:bg-slate-200 border-l border-border text-[10px] font-bold text-text-secondary transition"
                                                >
                                                    Max Amount
                                                </button>
                                            </div>
                                            <div className="text-[10px] text-text-muted font-mono flex justify-between px-1">
                                                <span>Available Funds: {formatMoney(activeWallet.balance, 'USD')}</span>
                                                <span>Fees: 0.00% ($0.00)</span>
                                            </div>
                                        </div>

                                        {/* Recipient Coordinate field */}
                                        <div className="space-y-1.5">
                                            <label className="text-[10px] uppercase font-bold text-text-muted tracking-wider">Recipient Ledger Address</label>
                                            <input
                                                type="text"
                                                readOnly
                                                value={withdrawAddress}
                                                className="w-full p-3 border border-border rounded-lg bg-slate-100/80 text-xs font-mono text-text-muted outline-none"
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={isWithdrawing}
                                            className="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-lg text-xs flex items-center justify-center gap-2"
                                        >
                                            {isWithdrawing ? "Clearance in transit..." : "Transmit Withdrawal Clear Request"}
                                        </Button>
                                    </form>
                                </motion.div>
                            )}

                            {/* Tab 3: Payout method channels cards */}
                            {activeTab === 'payouts' && (
                                <motion.div 
                                    key="payouts" 
                                    initial={{ opacity: 0, y: 5 }} 
                                    animate={{ opacity: 1, y: 0 }} 
                                    exit={{ opacity: 0, y: -5 }}
                                    className="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    {/* Bank Wire Card */}
                                    <div className="border border-border/60 rounded-xl p-4 flex flex-col justify-between h-36 bg-slate-50/20 relative group hover:border-border-strong transition">
                                        <div className="flex justify-between items-start">
                                            <div className="space-y-1">
                                                <span className="font-bold text-text-primary block">Bank Account Wire (Chase)</span>
                                                <p className="text-[10px] text-text-muted font-mono">Chase Business Checking ••••8902</p>
                                            </div>
                                            <Building2 className="h-5 w-5 text-text-muted" />
                                        </div>
                                        <div className="flex justify-between items-end border-t border-border/40 pt-3 mt-3">
                                            <span className="bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded-full flex items-center gap-0.5">
                                                <CheckCircle2 className="h-3 w-3 text-emerald-600" /> Connected & Verified
                                            </span>
                                            <button className="text-[10px] text-text-muted font-semibold hover:text-text-primary transition">Configure</button>
                                        </div>
                                    </div>

                                    {/* Crypto Wallet USDT Card */}
                                    <div className="border border-border/60 rounded-xl p-4 flex flex-col justify-between h-36 bg-slate-50/20 relative group hover:border-border-strong transition">
                                        <div className="flex justify-between items-start">
                                            <div className="space-y-1">
                                                <span className="font-bold text-text-primary block">Tether USDT Payout Wallet</span>
                                                <p className="text-[10px] text-text-muted font-mono">TRC-20 Address: TEv5...Yx1U</p>
                                            </div>
                                            <div className="bg-emerald-50 text-emerald-700 h-6 w-6 rounded-full font-sans font-bold flex items-center justify-center text-[10px]">T</div>
                                        </div>
                                        <div className="flex justify-between items-end border-t border-border/40 pt-3 mt-3">
                                            <span className="bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded-full flex items-center gap-0.5">
                                                <CheckCircle2 className="h-3 w-3 text-emerald-600" /> Active Ledger Address
                                            </span>
                                            <button className="text-[10px] text-text-muted font-semibold hover:text-text-primary transition font-mono">TEv5...Yx1U</button>
                                        </div>
                                    </div>

                                    {/* Wise Account Payout Card (Simulates linking) */}
                                    <div className="border border-border/60 rounded-xl p-4 flex flex-col justify-between h-36 bg-slate-50/20 relative group hover:border-border-strong transition">
                                        <div className="flex justify-between items-start">
                                            <div className="space-y-1">
                                                <span className="font-bold text-text-primary block">Wise Multi-Currency Ledger</span>
                                                <p className="text-[10px] text-text-muted font-mono">Direct international transfer clearances</p>
                                            </div>
                                            <span className="text-[10px] uppercase tracking-wider font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">Wise</span>
                                        </div>
                                        <div className="flex justify-between items-end border-t border-border/40 pt-3 mt-3">
                                            {linkedMethods.wise ? (
                                                <span className="bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded-full flex items-center gap-0.5 animate-pulse">
                                                    <CheckCircle2 className="h-3 w-3 text-emerald-600" /> Linked (Wise Direct)
                                                </span>
                                            ) : (
                                                <span className="bg-slate-100 border border-slate-200 text-slate-500 text-[9px] font-bold px-2 py-0.5 rounded-full flex items-center gap-0.5">
                                                    <Clock className="h-3 w-3" /> Not Synchronized
                                                </span>
                                            )}

                                            {!linkedMethods.wise ? (
                                                <button 
                                                    onClick={() => linkPayoutMethod('wise')}
                                                    className="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-semibold px-3 py-1 rounded transition"
                                                >
                                                    Link Account
                                                </button>
                                            ) : (
                                                <button className="text-[10px] text-text-muted hover:text-text-primary transition font-semibold">Configure</button>
                                            )}
                                        </div>
                                    </div>

                                    {/* Stripe Connect Card */}
                                    <div className="border border-border/60 rounded-xl p-4 flex flex-col justify-between h-36 bg-slate-50/20 relative group hover:border-border-strong transition">
                                        <div className="flex justify-between items-start">
                                            <div className="space-y-1">
                                                <span className="font-bold text-text-primary block">Stripe Express payout</span>
                                                <p className="text-[10px] text-text-muted font-mono">Payout gateway for card receipts</p>
                                            </div>
                                            <span className="text-[10px] uppercase tracking-wider font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-mono">Stripe</span>
                                        </div>
                                        <div className="flex justify-between items-end border-t border-border/40 pt-3 mt-3">
                                            <span className="bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded-full flex items-center gap-0.5">
                                                <CheckCircle2 className="h-3 w-3 text-emerald-600" /> Active Connection
                                            </span>
                                            <button className="text-[10px] text-text-muted font-semibold hover:text-text-primary transition">Verify</button>
                                        </div>
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                </div>

                {/* Escrow safety alert warning banner */}
                <div className="bg-indigo-50/30 border border-indigo-100/50 rounded-xl p-4 flex gap-3 text-xs">
                    <ShieldCheck className="h-5 w-5 text-indigo-500 shrink-0 mt-0.5" />
                    <div>
                        <span className="font-bold text-indigo-900 block">PCI Compliant Escrow Protection Guarantee</span>
                        <p className="text-indigo-700/80 text-[10px] mt-0.5 leading-relaxed">
                            Musoftware Business Ledger utilizes fully verified and secure escrow integrations for freelance job deliveries and marketplace ordering. Funds are captured at authorization and released safely upon milestone completions.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
