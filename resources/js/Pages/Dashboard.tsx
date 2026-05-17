import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { 
    Wallet, FileText, ArrowUpRight, Clock, CheckCircle2, 
    AlertCircle, Sparkles, Building2, Briefcase, Plus, ArrowRightLeft,
    CreditCard, Inbox
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

interface DashboardStats {
    walletBalance: number;
    earnedBalance: number;
    pointsBalance: number;
    unpaidInvoices: number;
    unpaidAmount: number;
    activeSubscriptions: number;
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
}

export default function Dashboard({ stats: serverStats, pendingInvoices: serverInvoices, recentTransactions: serverTransactions }: DashboardProps) {
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

    // Use server-passed data (real DB queries), with safe fallbacks for zero-state
    const stats = serverStats || {
        walletBalance: 0,
        earnedBalance: 0,
        pointsBalance: 0,
        unpaidInvoices: 0,
        unpaidAmount: 0,
        activeSubscriptions: 0,
        openTickets: 0,
        pendingWithdrawals: 0,
        currency: 'USD',
    };

    const pendingInvoices = serverInvoices || [];
    const recentTransactions = serverTransactions || [];
    const currencySymbol = stats.currency === 'EGP' ? 'EGP ' : '$';

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Customer Dashboard" />

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8">
                
                {/* Compact Welcome Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-background p-6 rounded-2xl border shadow-none">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Good morning, {user?.name || 'Customer'}</h1>
                        <p className="text-sm text-muted-foreground mt-1">Here is what requires your attention today.</p>
                    </div>
                    <div className="flex gap-3">
                        <Link 
                            href={safeRoute('financial.add-balance')} 
                            className={cn(buttonVariants({ variant: 'default', size: 'default' }), "shadow-none rounded-full px-5")}
                        >
                            <Plus className="w-4 h-4 mr-2" /> Add Balance
                        </Link>
                    </div>
                </div>

                {/* Financial Summary Row */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card className="shadow-none flex flex-col justify-between">
                        <CardHeader className="pb-2">
                            <div className="flex justify-between items-start">
                                <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <Wallet className="w-5 h-5 text-primary" />
                                </div>
                                <Badge variant="secondary" className="bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-50 font-normal">
                                    Active
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm font-medium text-muted-foreground mb-1">Wallet Balance</p>
                            <div className="flex items-baseline gap-2">
                                <h2 className="text-3xl font-semibold">{currencySymbol}{stats.walletBalance.toLocaleString(undefined, {minimumFractionDigits: 2})}</h2>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-none border-destructive/20 flex flex-col justify-between relative overflow-hidden bg-destructive/5">
                        <CardHeader className="pb-2 relative z-10">
                            <div className="flex justify-between items-start">
                                <div className="w-10 h-10 rounded-xl bg-destructive/10 flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-destructive" />
                                </div>
                                {stats.unpaidInvoices > 0 && (
                                    <Badge variant="destructive" className="font-normal gap-1">
                                        <AlertCircle className="w-3.5 h-3.5" /> Action Required
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="relative z-10">
                            <p className="text-sm font-medium text-muted-foreground mb-1">Unpaid Invoices ({stats.unpaidInvoices})</p>
                            <div className="flex items-baseline gap-2">
                                <h2 className="text-3xl font-semibold text-destructive">{currencySymbol}{stats.unpaidAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}</h2>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-none flex flex-col justify-between">
                        <CardHeader className="pb-2">
                            <div className="flex justify-between items-start">
                                <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center">
                                    <Sparkles className="w-5 h-5 text-muted-foreground" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm font-medium text-muted-foreground mb-1">Subscribed Systems</p>
                            <div className="flex items-baseline gap-2">
                                <h2 className="text-3xl font-semibold">{stats.activeSubscriptions} Module{stats.activeSubscriptions !== 1 ? 's' : ''}</h2>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* LEFT COLUMN: Urgent & Workflows */}
                    <div className="lg:col-span-2 space-y-6">
                        
                        {/* PENDING INVOICES (Most Important) */}
                        <Card className="shadow-none overflow-hidden">
                            <CardHeader className="bg-muted/30 border-b flex flex-row items-center justify-between py-4">
                                <CardTitle className="text-base font-semibold flex items-center gap-2 m-0">
                                    <FileText className="w-4 h-4 text-destructive" /> Pending Invoices
                                </CardTitle>
                                <Link 
                                    href={safeRoute('erp.invoices.index', undefined, '/erp/invoices')}
                                    className={cn(buttonVariants({ variant: 'link', size: 'sm' }), "h-auto p-0")}
                                >
                                    View All
                                </Link>
                            </CardHeader>
                            <div className="divide-y divide-border">
                                {pendingInvoices.length === 0 ? (
                                    <div className="p-8 text-center">
                                        <div className="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-3">
                                            <CheckCircle2 className="w-6 h-6 text-emerald-500" />
                                        </div>
                                        <p className="text-sm font-medium">All caught up!</p>
                                        <p className="text-xs text-muted-foreground mt-1">You have no pending invoices at this time.</p>
                                    </div>
                                ) : pendingInvoices.map((invoice) => (
                                    <div key={invoice.id} className="p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:bg-muted/30 transition-colors">
                                        <div className="flex items-start gap-4">
                                            <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center shrink-0">
                                                <FileText className="w-5 h-5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-sm">{invoice.description}</p>
                                                <div className="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
                                                    <span className="font-mono">{invoice.id}</span>
                                                    <span>•</span>
                                                    <span className="flex items-center gap-1"><Clock className="w-3 h-3" /> Due {invoice.date}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-6 w-full sm:w-auto justify-between sm:justify-end">
                                            <div className="text-left sm:text-right">
                                                <p className="font-semibold">{currencySymbol}{invoice.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                                                <p className={`text-xs font-medium mt-0.5 ${invoice.status === 'overdue' ? 'text-destructive' : 'text-amber-600'}`}>
                                                    {invoice.status === 'overdue' ? 'Overdue' : 'Due Soon'}
                                                </p>
                                            </div>
                                            <Link 
                                                href={safeRoute('erp.invoices.show', invoice.dbId, `/erp/invoices/${invoice.dbId}`)}
                                                className={cn(buttonVariants({ variant: 'default', size: 'sm' }), "shadow-none rounded-full px-5")}
                                            >
                                                Pay Now
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        {/* SUBSCRIPTION / MODULE ACCESS */}
                        <Card className="shadow-none overflow-hidden">
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base font-semibold flex items-center gap-2 m-0">
                                    <Sparkles className="w-4 h-4 text-primary" /> Subscribed Workspaces
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Link href={safeRoute('erp.dashboard', undefined, '/erp/dashboard')} className="group p-5 rounded-xl border hover:border-primary/50 hover:shadow-sm transition-all bg-background relative overflow-hidden block">
                                    <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Building2 className="w-5 h-5 text-primary" />
                                    </div>
                                    <h4 className="font-semibold mb-1">Business OS (ERP)</h4>
                                    <p className="text-sm text-muted-foreground leading-relaxed">Manage your clients, generate invoices, and log project timers.</p>
                                    <div className="absolute top-4 right-4 text-emerald-600 text-[10px] uppercase font-bold bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">Active</div>
                                </Link>

                                <Link href={safeRoute('freelance.dashboard', undefined, '/freelance/dashboard')} className="group p-5 rounded-xl border hover:border-primary/50 hover:shadow-sm transition-all bg-background relative overflow-hidden block">
                                    <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Briefcase className="w-5 h-5 text-primary" />
                                    </div>
                                    <h4 className="font-semibold mb-1">Freelance Hub</h4>
                                    <p className="text-sm text-muted-foreground leading-relaxed">Accept contracts, manage milestones, and submit deliverables.</p>
                                    <div className="absolute top-4 right-4 text-emerald-600 text-[10px] uppercase font-bold bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">Active</div>
                                </Link>
                            </CardContent>
                        </Card>

                    </div>

                    {/* RIGHT COLUMN: Transactions & Wallet Actions */}
                    <div className="space-y-6">
                        
                        {/* QUICK ACTIONS */}
                        <Card className="shadow-none p-1">
                            <div className="flex flex-col gap-1 p-3">
                                <Link href={safeRoute('financial.add-balance')} className="flex items-center px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50 rounded-xl transition-colors">
                                    <Plus className="w-4 h-4 mr-3 text-muted-foreground" /> Add Funds to Wallet
                                </Link>
                                <Link href={safeRoute('financial.withdrawals')} className="flex items-center px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50 rounded-xl transition-colors">
                                    <ArrowUpRight className="w-4 h-4 mr-3 text-muted-foreground" /> Request Withdrawal
                                </Link>
                                <Link href={safeRoute('financial.payout-methods.index')} className="flex items-center px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50 rounded-xl transition-colors">
                                    <CreditCard className="w-4 h-4 mr-3 text-muted-foreground" /> Manage Payment Methods
                                </Link>
                            </div>
                        </Card>

                        {/* RECENT TRANSACTIONS */}
                        <Card className="shadow-none overflow-hidden">
                            <CardHeader className="py-4 border-b">
                                <CardTitle className="text-base font-semibold flex items-center gap-2 m-0">
                                    <ArrowRightLeft className="w-4 h-4 text-muted-foreground" /> Recent Transactions
                                </CardTitle>
                            </CardHeader>
                            <div className="divide-y divide-border">
                                {recentTransactions.length === 0 ? (
                                    <div className="p-8 text-center">
                                        <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center mx-auto mb-3">
                                            <Inbox className="w-5 h-5 text-muted-foreground" />
                                        </div>
                                        <p className="text-sm text-muted-foreground">No transactions yet.</p>
                                        <p className="text-xs text-muted-foreground/70 mt-1">Transactions will appear here when you add funds or pay invoices.</p>
                                    </div>
                                ) : recentTransactions.map((txn) => (
                                    <div key={txn.id} className="p-5 flex justify-between items-center hover:bg-muted/30 transition-colors">
                                        <div className="flex items-center gap-3">
                                            <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${
                                                txn.type === 'deposit' ? 'bg-emerald-50' : 
                                                txn.type === 'withdrawal' ? 'bg-amber-50' : 'bg-muted'
                                            }`}>
                                                {txn.type === 'deposit' && <ArrowRightLeft className="w-3.5 h-3.5 text-emerald-600" />}
                                                {txn.type === 'withdrawal' && <ArrowUpRight className="w-3.5 h-3.5 text-amber-600" />}
                                                {txn.type === 'payment' && <CreditCard className="w-3.5 h-3.5 text-muted-foreground" />}
                                            </div>
                                            <div>
                                                <p className="font-medium text-sm capitalize">{txn.type}</p>
                                                <p className="text-xs text-muted-foreground">{txn.method} • {txn.date}</p>
                                            </div>
                                        </div>
                                        <div className={`font-medium text-sm ${txn.amount > 0 ? 'text-emerald-600' : 'text-foreground'}`}>
                                            {txn.amount > 0 ? '+' : ''}{txn.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="p-4 bg-muted/30 border-t text-center">
                                <Link 
                                    href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)}
                                    className={cn(buttonVariants({ variant: 'link', size: 'sm' }), "h-auto p-0")}
                                >
                                    View Full History
                                </Link>
                            </div>
                        </Card>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
