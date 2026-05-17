import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { 
    Wallet, FileText, ArrowUpRight, ArrowRight, Clock, CheckCircle2, 
    AlertCircle, Sparkles, Building2, Briefcase, Megaphone, Plus, ArrowRightLeft,
    CreditCard
} from 'lucide-react';
import { Button } from '@/Components/ui/button';

export default function Dashboard() {
    const { auth } = usePage().props as any;
    const user = auth?.user;

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

    // Simulated data for operational view
    const stats = {
        walletBalance: 1250.45,
        pointsBalance: 1450,
        unpaidInvoices: 2,
        unpaidAmount: 3450.00,
        activeSubscriptions: 1
    };

    const pendingInvoices = [
        { id: 'INV-2024-001', date: 'Oct 12, 2024', amount: 2500.00, status: 'due', description: 'ERP Custom Implementation', dbId: 1 },
        { id: 'INV-2024-002', date: 'Oct 15, 2024', amount: 950.00, status: 'overdue', description: 'Monthly Retainer - Oct', dbId: 2 },
    ];

    const recentTransactions = [
        { id: 'TXN-001', date: 'Oct 10, 2024', type: 'deposit', amount: 5000.00, method: 'Stripe' },
        { id: 'TXN-002', date: 'Oct 08, 2024', type: 'payment', amount: -1500.00, method: 'Wallet Balance' },
        { id: 'TXN-003', date: 'Oct 05, 2024', type: 'withdrawal', amount: -2000.00, method: 'Bank Transfer' },
    ];

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Customer Dashboard" />

            <div className="max-w-[1200px] mx-auto space-y-8 pb-12">
                
                {/* Compact Welcome Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">Good morning, {user?.name || 'Customer'}</h1>
                        <p className="text-sm text-slate-500 mt-1">Here is what requires your attention today.</p>
                    </div>
                    <div className="flex gap-3">
                        <Button className="bg-slate-900 hover:bg-slate-800 text-white rounded-full" asChild>
                            <Link href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)}>
                                <Plus className="w-4 h-4 mr-2" /> Add Balance
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Financial Summary Row */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div className="flex justify-between items-start mb-4">
                            <div className="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                                <Wallet className="w-5 h-5 text-indigo-600" />
                            </div>
                            <span className="text-xs font-medium px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100">
                                Active
                            </span>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-slate-500 mb-1">Wallet Balance</p>
                            <div className="flex items-baseline gap-2">
                                <h2 className="text-3xl font-semibold text-slate-900">${stats.walletBalance.toLocaleString(undefined, {minimumFractionDigits: 2})}</h2>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-2xl border border-rose-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-rose-50 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none" />
                        <div className="relative">
                            <div className="flex justify-between items-start mb-4">
                                <div className="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-rose-600" />
                                </div>
                                {stats.unpaidInvoices > 0 && (
                                    <span className="flex items-center gap-1.5 text-xs font-medium px-2 py-1 bg-rose-100 text-rose-700 rounded-full border border-rose-200">
                                        <AlertCircle className="w-3.5 h-3.5" /> Action Required
                                    </span>
                                )}
                            </div>
                            <div>
                                <p className="text-sm font-medium text-slate-500 mb-1">Unpaid Invoices ({stats.unpaidInvoices})</p>
                                <div className="flex items-baseline gap-2">
                                    <h2 className="text-3xl font-semibold text-rose-600">${stats.unpaidAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div className="flex justify-between items-start mb-4">
                            <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                <Sparkles className="w-5 h-5 text-slate-600" />
                            </div>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-slate-500 mb-1">Subscribed Systems</p>
                            <div className="flex items-baseline gap-2">
                                <h2 className="text-3xl font-semibold text-slate-900">{stats.activeSubscriptions} Module</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* LEFT COLUMN: Urgent & Workflows */}
                    <div className="lg:col-span-2 space-y-8">
                        
                        {/* PENDING INVOICES (Most Important) */}
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div className="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <h3 className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-rose-500" /> Pending Invoices
                                </h3>
                                <Link href={safeRoute('erp.invoices.index', undefined, '/erp/invoices')} className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    View All
                                </Link>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {pendingInvoices.map((invoice) => (
                                    <div key={invoice.id} className="p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:bg-slate-50 transition-colors">
                                        <div className="flex items-start gap-4">
                                            <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                                                <FileText className="w-5 h-5 text-slate-500" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-slate-900 text-sm">{invoice.description}</p>
                                                <div className="flex items-center gap-3 mt-1 text-xs text-slate-500">
                                                    <span className="font-mono">{invoice.id}</span>
                                                    <span>•</span>
                                                    <span className="flex items-center gap-1"><Clock className="w-3 h-3" /> Due {invoice.date}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-6 w-full sm:w-auto justify-between sm:justify-end">
                                            <div className="text-left sm:text-right">
                                                <p className="font-semibold text-slate-900">${invoice.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                                                <p className={`text-xs font-medium mt-0.5 ${invoice.status === 'overdue' ? 'text-rose-600' : 'text-amber-600'}`}>
                                                    {invoice.status === 'overdue' ? 'Overdue' : 'Due Soon'}
                                                </p>
                                            </div>
                                            <Button size="sm" className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full px-5" asChild>
                                                <Link href={safeRoute('erp.invoices.show', invoice.dbId, `/erp/invoices/${invoice.dbId}`)}>
                                                    Pay Now
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* SUBSCRIPTION / MODULE ACCESS */}
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div className="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                                <h3 className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                    <Sparkles className="w-4 h-4 text-indigo-500" /> Subscribed Workspaces
                                </h3>
                            </div>
                            <div className="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Link href={safeRoute('erp.dashboard', undefined, '/erp/dashboard')} className="group p-5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-50 transition-all bg-white relative overflow-hidden block">
                                    <div className="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Building2 className="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <h4 className="font-semibold text-slate-900 mb-1">Business OS (ERP)</h4>
                                    <p className="text-sm text-slate-500 leading-relaxed">Manage your clients, generate invoices, and log project timers.</p>
                                    <div className="absolute top-4 right-4 text-emerald-600 text-xs font-medium bg-emerald-50 px-2 py-0.5 rounded-full">Active</div>
                                </Link>

                                <Link href={safeRoute('freelance.dashboard', undefined, '/freelance/dashboard')} className="group p-5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-50 transition-all bg-white relative overflow-hidden block">
                                    <div className="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Briefcase className="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <h4 className="font-semibold text-slate-900 mb-1">Freelance Hub</h4>
                                    <p className="text-sm text-slate-500 leading-relaxed">Accept contracts, manage milestones, and submit deliverables.</p>
                                    <div className="absolute top-4 right-4 text-emerald-600 text-xs font-medium bg-emerald-50 px-2 py-0.5 rounded-full">Active</div>
                                </Link>
                            </div>
                        </div>

                    </div>

                    {/* RIGHT COLUMN: Transactions & Wallet Actions */}
                    <div className="space-y-8">
                        
                        {/* QUICK ACTIONS */}
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-1">
                            <div className="flex flex-col gap-1 p-3">
                                <Button variant="ghost" className="w-full justify-start text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium h-11" asChild>
                                    <Link href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)}>
                                        <Plus className="w-4 h-4 mr-3 text-slate-400" /> Add Funds to Wallet
                                    </Link>
                                </Button>
                                <Button variant="ghost" className="w-full justify-start text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium h-11" asChild>
                                    <Link href={safeRoute('erp.withdrawals.index', undefined, '/erp/withdrawals')}>
                                        <ArrowUpRight className="w-4 h-4 mr-3 text-slate-400" /> Request Withdrawal
                                    </Link>
                                </Button>
                                <Button variant="ghost" className="w-full justify-start text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium h-11" asChild>
                                    <Link href={safeRoute('erp.payment-methods.index', undefined, '/erp/payment-methods')}>
                                        <CreditCard className="w-4 h-4 mr-3 text-slate-400" /> Manage Payment Methods
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        {/* RECENT TRANSACTIONS */}
                        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div className="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                                <h3 className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                    <ArrowRightLeft className="w-4 h-4 text-slate-500" /> Recent Transactions
                                </h3>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {recentTransactions.map((txn) => (
                                    <div key={txn.id} className="p-5 flex justify-between items-center hover:bg-slate-50 transition-colors">
                                        <div className="flex items-center gap-3">
                                            <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${
                                                txn.type === 'deposit' ? 'bg-emerald-50' : 
                                                txn.type === 'withdrawal' ? 'bg-amber-50' : 'bg-slate-100'
                                            }`}>
                                                {txn.type === 'deposit' && <ArrowRightLeft className="w-3.5 h-3.5 text-emerald-600" />}
                                                {txn.type === 'withdrawal' && <ArrowUpRight className="w-3.5 h-3.5 text-amber-600" />}
                                                {txn.type === 'payment' && <CreditCard className="w-3.5 h-3.5 text-slate-600" />}
                                            </div>
                                            <div>
                                                <p className="font-medium text-slate-900 text-sm capitalize">{txn.type}</p>
                                                <p className="text-xs text-slate-500">{txn.method} • {txn.date}</p>
                                            </div>
                                        </div>
                                        <div className={`font-medium text-sm ${txn.amount > 0 ? 'text-emerald-600' : 'text-slate-900'}`}>
                                            {txn.amount > 0 ? '+' : ''}{txn.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="p-4 bg-slate-50 border-t border-slate-100 text-center">
                                <Link href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)} className="text-xs font-medium text-indigo-600 hover:text-indigo-700">
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
