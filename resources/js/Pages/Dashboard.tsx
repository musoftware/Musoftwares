import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, useForm } from '@inertiajs/react';
import { 
    Wallet, FileText, ArrowUpRight, Clock, CheckCircle2, 
    Sparkles, Plus, CreditCard, Inbox, Settings, Activity, ArrowRight, LayoutDashboard, History, Calendar, Layers
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { ActivityFeed } from '@/Components/ui/ActivityFeed';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { 
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend
} from 'recharts';

interface DashboardStats {
    walletBalance: number;
    earnedBalance: number;
    pointsBalance: number;
    unpaidInvoices: number;
    unpaidAmount: number;
    activeSubscriptions: number;
    totalMonthlySubscription: number;
    openTickets: number;
    pendingWithdrawals: number;
    currency: string;
}

interface PendingInvoice {
    id: string;
    dbId: number;
    date: string;
    amount: number;
    status: string;
    description: string;
    currency: string;
}

interface RecentTransaction {
    id: string;
    date: string;
    type: string;
    amount: number;
    method: string;
}

interface DashboardProps {
    stats?: DashboardStats;
    pendingInvoices?: PendingInvoice[];
    recentTransactions?: RecentTransaction[];
    chartData?: any[];
}

export default function Dashboard({ 
    stats: serverStats, 
    pendingInvoices: serverInvoices, 
    recentTransactions: serverTransactions,
    chartData = [],
}: DashboardProps) {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    const { post, processing } = useForm({});

    const safeRoute = (name: string, params?: any, fallbackUrl?: string) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined' && route().has(name)) {
                // @ts-ignore
                return route(name, params);
            }
        } catch (e) {}
        return fallbackUrl || '#';
    };

    const stats = serverStats || {
        walletBalance: 0,
        earnedBalance: 0,
        pointsBalance: 0,
        unpaidInvoices: 0,
        unpaidAmount: 0,
        activeSubscriptions: 0,
        totalMonthlySubscription: 0,
        openTickets: 0,
        pendingWithdrawals: 0,
        currency: 'USD',
    };

    const pendingInvoices = serverInvoices || [];
    const recentTransactions = serverTransactions || [];

    const activityFeedItems = recentTransactions.map((txn) => ({
        id: Number(String(txn.id).replace('TXN-', '')),
        user_id: null,
        subject_type: 'transaction',
        subject_id: Number(String(txn.id).replace('TXN-', '')),
        event: txn.type,
        description: `${txn.type.charAt(0).toUpperCase() + txn.type.slice(1)} of ${stats.currency} ${txn.amount.toLocaleString(undefined, {minimumFractionDigits: 2})} via ${txn.method}`,
        properties: null,
        workspace: 'system',
        created_at: txn.date,
        icon: txn.type === 'deposit' ? 'wallet' : 'receipt',
        color: txn.type === 'deposit' ? 'emerald' : txn.type === 'withdrawal' ? 'amber' : 'slate',
        user: null
    }));

    const handleQuickTopup = (amount: number) => {
        // Post directly to the Kashier checkout flow with the preset amount
        post(safeRoute('financial.add-balance.kashier', { amount }, `/financial/add-balance/kashier?amount=${amount}`));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                
                {/* SECTION 1: HEADER & IDENTITY */}
                <div className="mb-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 mb-3 border border-slate-200">
                        Account Overview
                    </span>
                    <div className="flex items-baseline gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
                        <span className="text-slate-500 font-medium">/ {user?.name || 'Customer'}</span>
                    </div>
                </div>

                {/* SECTION 2: FINANCIAL CONSOLIDATION (2 COLS) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <MetricCard 
                        label="Account Balance"
                        value={stats.walletBalance}
                        icon={Wallet}
                    />
                    <MetricCard 
                        label="Monthly Subscription"
                        value={stats.totalMonthlySubscription}
                        icon={Sparkles}
                    />
                </div>

                {/* ALERT: PAYMENT REQUIRED */}
                {stats.unpaidInvoices > 0 && stats.walletBalance < stats.unpaidAmount && (
                    <div className="bg-rose-50 border border-rose-200 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden">
                        <div className="absolute left-0 top-0 bottom-0 w-1 bg-rose-500"></div>
                        <div className="pl-2">
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-900 text-white mb-2">
                                Action Needed
                            </span>
                            <h2 className="text-base font-bold text-rose-900">Critical Payment Required</h2>
                            <p className="text-sm text-rose-700 mt-1">
                                You have an outstanding balance of <span className="font-bold"><CurrencyDisplay amount={stats.unpaidAmount - stats.walletBalance} currency={stats.currency} /></span> that needs to be settled.
                            </p>
                        </div>
                        <Link 
                            href={safeRoute('erp.client-invoices.index', undefined, '/my/invoices')}
                            className="inline-flex items-center justify-center h-10 px-6 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-medium text-sm transition-colors shadow-sm shrink-0 whitespace-nowrap"
                        >
                            Settle Now
                        </Link>
                    </div>
                )}

                {/* SECTION 2.5: PENDING INVOICES (Replaces Active Missions Progress) */}
                <div>
                    <div className="flex justify-between items-end mb-3">
                        <div>
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 mb-2 border border-slate-200">
                                Outstanding Operations
                            </span>
                            <h2 className="text-xl font-bold text-slate-900 mb-0">Pending Invoices</h2>
                        </div>
                        <Link href={safeRoute('erp.client-invoices.index', undefined, '/my/invoices')} className="text-slate-900 font-bold text-xs hover:underline flex items-center">
                            View All <ArrowRight className="w-3 h-3 ml-1" />
                        </Link>
                    </div>
                    
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        {pendingInvoices.length === 0 ? (
                            <div className="text-center py-6">
                                <div className="mb-3">
                                    <CheckCircle2 className="w-10 h-10 text-slate-300 mx-auto opacity-50" />
                                </div>
                                <p className="text-slate-500 mb-2 italic">All caught up! You have no pending invoices at the moment.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {pendingInvoices.slice(0, 3).map((invoice) => (
                                    <div key={invoice.id} className="p-3 rounded-lg bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors">
                                        <div className="flex justify-between items-start mb-2">
                                            <span className="font-bold text-slate-900 text-sm truncate pr-2" title={invoice.description}>{invoice.description}</span>
                                            <span className={cn("px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap", invoice.status === 'overdue' ? "bg-rose-100 text-rose-700" : "bg-amber-100 text-amber-700")}>
                                                {invoice.status === 'overdue' ? 'Overdue' : 'Due Soon'}
                                            </span>
                                        </div>
                                        <div className="h-1.5 w-full bg-slate-200 rounded-full mb-3 overflow-hidden">
                                            <div className={cn("h-full rounded-full", invoice.status === 'overdue' ? "bg-rose-500 w-full" : "bg-amber-500 w-[80%]")}></div>
                                        </div>
                                        <div className="flex items-center justify-between mt-3 pt-3 border-t border-slate-200/60">
                                            <div>
                                                <div className="text-xs text-slate-500 flex items-center"><Clock className="w-3 h-3 mr-1" /> {invoice.date}</div>
                                                <CurrencyDisplay amount={invoice.amount} currency={invoice.currency || stats.currency} className="font-bold text-sm text-slate-900 block mt-0.5" />
                                            </div>
                                            <div className="flex gap-1.5">
                                                <Link 
                                                    href={safeRoute('erp.client-invoices.pay', invoice.id, `/my/invoices/${invoice.id}/pay`)}
                                                    className="inline-flex items-center justify-center h-7 px-2 rounded bg-slate-200 hover:bg-slate-300 text-slate-800 text-[10px] font-bold uppercase tracking-wider transition-colors"
                                                    title="Pay with Wallet"
                                                >
                                                    Wallet
                                                </Link>
                                                <Link 
                                                    href={safeRoute('erp.client-invoices.pay', invoice.id, `/my/invoices/${invoice.id}/pay`)}
                                                    className="inline-flex items-center justify-center h-7 px-2 rounded bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold uppercase tracking-wider transition-colors"
                                                    title="Pay with Kashier"
                                                >
                                                    Kashier
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* SECTION 3: CORE OPERATIONS (3 ACTION CARDS) */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    {/* Action Card 1: Subscriptions & Plans */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mb-4">
                            <Sparkles className="w-5 h-5 text-primary" />
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900 mb-1">Active Subscriptions</h3>
                            <p className="text-sm text-slate-500 leading-relaxed mb-4">Manage your SaaS tools, ERP modules, and recurring billing plans.</p>
                        </div>
                        <div className="mt-auto pt-4">
                            <div className="flex flex-col gap-2">
                                <Link href="/subscriptions/plans" className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-primary hover:bg-slate-50 transition-colors group">
                                    <span className="text-sm font-medium text-slate-700 group-hover:text-primary">View Tool Plans</span>
                                    <ArrowRight className="w-4 h-4 text-slate-400 group-hover:text-primary" />
                                </Link>
                                <Link href={safeRoute('erp.client-invoices.index', undefined, '/my/invoices')} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-primary hover:bg-slate-50 transition-colors group">
                                    <span className="text-sm font-medium text-slate-700 group-hover:text-primary">Subscription History</span>
                                    <History className="w-4 h-4 text-slate-400 group-hover:text-primary" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Action Card 2: Quick Top Up */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center mb-4">
                            <Wallet className="w-5 h-5 text-emerald-700" />
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900 mb-1">Charge Balance</h3>
                            <p className="text-sm text-slate-500 leading-relaxed mb-4">Add credits to your wallet via Kashier for seamless, one-click payments.</p>
                        </div>
                        <div className="mt-auto pt-2">
                            <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Quick Top-up Amounts</p>
                            <div className="grid grid-cols-2 gap-2">
                                {[50, 100, 250, 500].map((amount) => (
                                    <button 
                                        key={amount}
                                        onClick={() => handleQuickTopup(amount)}
                                        disabled={processing}
                                        className="flex flex-col items-center justify-center p-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
                                    >
                                        <span className="font-bold text-sm">${amount}</span>
                                    </button>
                                ))}
                            </div>
                            <div className="mt-3 text-center">
                                <Link href={safeRoute('financial.add-balance', undefined, '/financial/add-balance')} className="text-xs font-medium text-slate-500 hover:text-slate-900 flex items-center justify-center">
                                    Custom Amount <ArrowRight className="w-3 h-3 ml-1" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Action Card 3: Billing Quick Access */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div className="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
                            <FileText className="w-5 h-5 text-slate-700" />
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900 mb-1">Billing & Invoices</h3>
                            <p className="text-sm text-slate-500 leading-relaxed mb-4">Review financials, pending statements, and account activity.</p>
                        </div>
                        <div className="mt-auto pt-4">
                            <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Billing Quick Access</p>
                            <div className="flex flex-col gap-2">
                                <Link href={safeRoute('erp.client-invoices.index', undefined, '/my/invoices')} className="flex items-center justify-between p-2.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                                    <span className="text-sm font-medium">Unpaid Invoices</span>
                                    <span className="bg-white/20 px-2 py-0.5 rounded text-xs font-bold">{stats.unpaidInvoices}</span>
                                </Link>
                                <Link href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)} className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-slate-300 transition-colors group">
                                    <span className="text-sm font-medium text-slate-700">Transactions</span>
                                    <ArrowRight className="w-4 h-4 text-slate-400" />
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>

                {/* SECTION 4: FINANCIAL HISTORY (8/4 SPLIT) */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {/* Financial Chart (8-col equivalent) */}
                    <div className="lg:col-span-2">
                        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full">
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 mb-2 border border-slate-200">
                                Financial History
                            </span>
                            <h2 className="text-xl font-bold text-slate-900 mb-6">Account Activity (Last 6 Months)</h2>
                            
                            <div className="h-[300px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                        <Tooltip 
                                            cursor={{ fill: '#f8fafc' }}
                                            contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                        />
                                        <Legend wrapperStyle={{ paddingTop: '20px' }} />
                                        <Bar dataKey="deposit" name="Deposits" fill="#10b981" radius={[4, 4, 0, 0]} maxBarSize={40} />
                                        <Bar dataKey="withdrawal" name="Withdrawals" fill="#f59e0b" radius={[4, 4, 0, 0]} maxBarSize={40} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>

                    {/* Recent Transactions (4-col equivalent) */}
                    <div className="lg:col-span-1">
                        <div className="bg-slate-900 rounded-xl border border-slate-800 shadow-sm p-6 h-full text-white flex flex-col">
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-white/10 text-white/70 mb-2 w-fit">
                                Recent Ledger
                            </span>
                            <h2 className="text-xl font-bold text-white mb-6">Latest Transactions</h2>
                            
                            <div className="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                                {activityFeedItems.length === 0 ? (
                                    <div className="text-center py-6">
                                        <Inbox className="w-8 h-8 text-white/20 mx-auto mb-2" />
                                        <p className="text-sm text-white/50">No transactions yet.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {activityFeedItems.slice(0, 5).map((txn) => (
                                            <div key={txn.id} className="flex items-start gap-3 pb-4 border-b border-white/10 last:border-0">
                                                <div className={cn("w-8 h-8 rounded-full flex items-center justify-center shrink-0", txn.color === 'emerald' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400')}>
                                                    {txn.icon === 'wallet' ? <Wallet className="w-4 h-4" /> : <History className="w-4 h-4" />}
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-white/90 leading-tight">{txn.description}</p>
                                                    <p className="text-xs text-white/40 mt-1">{txn.created_at}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                            
                            <div className="pt-4 mt-auto border-t border-white/10">
                                <Link 
                                    href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)} 
                                    className="flex items-center justify-center w-full py-2.5 rounded-lg border border-white/20 hover:bg-white/10 transition-colors text-sm font-medium text-white"
                                >
                                    View Full History
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
