import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { 
    Wallet, FileText, ArrowUpRight, Clock, CheckCircle2, 
    AlertCircle, Sparkles, Building2, Briefcase, Plus, ArrowRightLeft,
    CreditCard, Inbox, Settings, Wrench, Download, Key, ShoppingBag
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { cn } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { ActivityFeed } from '@/Components/ui/ActivityFeed';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';

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

interface ActiveToolLicense {
    license_key: string;
    tool: { slug: string; title: string; icon_url: string | null };
    active_devices: number;
    max_devices: number;
    expires_at: string | null;
}

interface DashboardProps {
    stats?: DashboardStats;
    pendingInvoices?: PendingInvoice[];
    recentTransactions?: RecentTransaction[];
    subscribedModules?: Record<string, boolean>;
    activeToolLicenses?: ActiveToolLicense[];
}

export default function Dashboard({ 
    stats: serverStats, 
    pendingInvoices: serverInvoices, 
    recentTransactions: serverTransactions,
    subscribedModules = { erp: false, freelance: true, marketing: false },
    activeToolLicenses = []
}: DashboardProps) {
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

    const activityFeedItems = recentTransactions.map((txn, index) => ({
        id: Number(txn.id),
        user_id: null,
        subject_type: 'transaction',
        subject_id: Number(txn.id),
        event: txn.type,
        description: `${txn.type.charAt(0).toUpperCase() + txn.type.slice(1)} of ${stats.currency} ${txn.amount.toLocaleString(undefined, {minimumFractionDigits: 2})} via ${txn.method}`,
        properties: null,
        workspace: 'system',
        created_at: txn.date,
        icon: txn.type === 'deposit' ? 'wallet' : 'receipt',
        color: txn.type === 'deposit' ? 'emerald' : txn.type === 'withdrawal' ? 'amber' : 'slate',
        user: null
    }));

    const menuItems = [
        { id: 'dashboard', label: 'Overview', icon: Building2, href: '/dashboard', isActive: true },
        { id: 'wallet', label: 'Wallet', icon: Wallet, href: safeRoute('financial.add-balance'), isActive: false },
        { id: 'subscriptions', label: 'Subscriptions', icon: Sparkles, href: '/subscriptions/plans', isActive: false },
        { id: 'settings', label: 'Settings', icon: Settings, href: '/profile', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title="Customer Dashboard"
            workspaceName="Musoftware Portal"
            tenantId="CUST-PORTAL"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title={`Good morning, ${user?.name || 'Customer'}`}
                    description="Here is what requires your attention today across all your workspaces."
                    actions={
                        <Link 
                            href={safeRoute('financial.add-balance')} 
                            className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                        >
                            <Plus className="w-4 h-4 mr-2 stroke-[1.5]" /> Add Balance
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <MetricCard 
                        label="Wallet Balance"
                        value={stats.walletBalance}
                        icon={Wallet}
                    />
                    <MetricCard 
                        label={`Unpaid Invoices (${stats.unpaidInvoices})`}
                        value={stats.unpaidAmount}
                        icon={FileText}
                    />
                    <MetricCard 
                        label="Subscribed Systems"
                        value={stats.activeSubscriptions}
                        icon={Sparkles}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        
                        <OperationalCard 
                            title="Pending Invoices" 
                            action={
                                <Link href={safeRoute('erp.invoices.index', undefined, '/erp/invoices')} className="text-[11px] font-bold uppercase tracking-wider text-primary hover:underline transition-colors">
                                    View All
                                </Link>
                            }
                            noPadding
                        >
                            <div className="divide-y divide-border/40">
                                {pendingInvoices.length === 0 ? (
                                    <EmptyState 
                                        icon={CheckCircle2}
                                        title="All caught up!"
                                        description="You have no pending invoices at this time."
                                    />
                                ) : pendingInvoices.map((invoice) => (
                                    <div key={invoice.id} className="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:bg-surface-raised transition-colors">
                                        <div className="flex items-start gap-4">
                                            <div className="w-10 h-10 rounded-xl bg-surface-raised flex items-center justify-center shrink-0">
                                                <FileText className="w-5 h-5 text-text-muted" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-sm text-text-primary">{invoice.description}</p>
                                                <div className="flex items-center gap-3 mt-1 text-xs text-text-muted">
                                                    <span className="font-mono">{invoice.id}</span>
                                                    <span>•</span>
                                                    <span className="flex items-center gap-1"><Clock className="w-3 h-3" /> Due {invoice.date}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-6 w-full sm:w-auto justify-between sm:justify-end">
                                            <div className="text-left sm:text-right">
                                                <CurrencyDisplay amount={invoice.amount} currency={invoice.currency || stats.currency} className="font-semibold block" />
                                                <span className={cn('text-[10px] font-bold uppercase tracking-wider', invoice.status === 'overdue' ? 'text-danger' : 'text-amber-600')}>
                                                    {invoice.status === 'overdue' ? 'Overdue' : 'Due Soon'}
                                                </span>
                                            </div>
                                            <Link 
                                                href={safeRoute('erp.invoices.show', invoice.dbId, `/erp/invoices/${invoice.dbId}`)}
                                                className="inline-flex items-center justify-center h-8 px-4 rounded-lg bg-primary text-white text-xs font-medium hover:bg-primary-hover transition-colors"
                                            >
                                                Pay Now
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </OperationalCard>

                        <OperationalCard title="Your Workspaces">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <Link 
                                    href={subscribedModules.erp ? '/erp/dashboard' : '/subscriptions/plans?module=erp'} 
                                    className="group p-5 rounded-xl border border-border hover:border-primary/50 hover:shadow-sm transition-all bg-surface relative overflow-hidden block"
                                >
                                    <div className="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Building2 className="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <h4 className="font-bold text-sm mb-1 text-text-primary">Business OS (ERP)</h4>
                                    <p className="text-xs text-text-muted leading-relaxed">Manage your clients, generate invoices, and log project timers.</p>
                                    {subscribedModules.erp ? (
                                        <div className="absolute top-4 right-4 text-emerald-600 text-[9px] uppercase font-bold bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-full">Active</div>
                                    ) : (
                                        <div className="absolute top-4 right-4 text-amber-600 text-[9px] uppercase font-bold bg-amber-50 border border-amber-100 px-2.5 py-0.5 rounded-full">Subscribe</div>
                                    )}
                                </Link>

                                <Link 
                                    href="/freelance/dashboard" 
                                    className="group p-5 rounded-xl border border-border hover:border-emerald-200 hover:shadow-sm transition-all bg-surface relative overflow-hidden block"
                                >
                                    <div className="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Briefcase className="w-5 h-5 text-emerald-600" />
                                    </div>
                                    <h4 className="font-bold text-sm mb-1 text-text-primary">Freelance Hub</h4>
                                    <p className="text-xs text-text-muted leading-relaxed">Browse jobs, submit proposals, manage contracts and earn real money.</p>
                                    <div className="absolute top-4 right-4 text-emerald-600 text-[9px] uppercase font-bold bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-full">Free</div>
                                </Link>

                                <Link 
                                    href="/marketplace/dashboard"
                                    className="group p-5 rounded-xl border border-border hover:border-violet-200 hover:shadow-sm transition-all bg-surface relative overflow-hidden block"
                                >
                                    <div className="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Sparkles className="w-5 h-5 text-violet-600" />
                                    </div>
                                    <h4 className="font-bold text-sm mb-1 text-text-primary">Marketing Suite</h4>
                                    <p className="text-xs text-text-muted leading-relaxed">List services, reach clients, and grow your business on the marketplace.</p>
                                    <div className="absolute top-4 right-4 text-violet-600 text-[9px] uppercase font-bold bg-violet-50 border border-violet-100 px-2.5 py-0.5 rounded-full">Free</div>
                                </Link>

                                <Link 
                                    href="/intelligence"
                                    className="group p-5 rounded-xl border border-border hover:border-rose-200 hover:shadow-sm transition-all bg-surface relative overflow-hidden block"
                                >
                                    <div className="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <AlertCircle className="w-5 h-5 text-rose-600" />
                                    </div>
                                    <h4 className="font-bold text-sm mb-1 text-text-primary">Intelligence</h4>
                                    <p className="text-xs text-text-muted leading-relaxed">Track competitors, monitor ad feeds, and save winning creatives.</p>
                                    <div className="absolute top-4 right-4 text-rose-600 text-[9px] uppercase font-bold bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-full">Beta</div>
                                </Link>

                                <Link 
                                    href="/tools"
                                    className="group p-5 rounded-xl border border-border hover:border-fuchsia-200 hover:shadow-sm transition-all bg-surface relative overflow-hidden block"
                                >
                                    <div className="w-10 h-10 rounded-lg bg-fuchsia-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <Wrench className="w-5 h-5 text-fuchsia-600" />
                                    </div>
                                    <h4 className="font-bold text-sm mb-1 text-text-primary">Tools Marketplace</h4>
                                    <p className="text-xs text-text-muted leading-relaxed">Download desktop tools, activate licenses, and receive auto-updates.</p>
                                    <div className="absolute top-4 right-4 text-fuchsia-600 text-[9px] uppercase font-bold bg-fuchsia-50 border border-fuchsia-100 px-2.5 py-0.5 rounded-full">
                                        {activeToolLicenses.length > 0 ? `${activeToolLicenses.length} Active` : 'Explore'}
                                    </div>
                                </Link>
                            </div>
                        </OperationalCard>

                    </div>

                    <div className="space-y-6">
                        
                        <OperationalCard noPadding>
                            <h4 className="px-4 pt-5 pb-2 text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                Quick Actions
                            </h4>
                            <div className="flex flex-col p-2">
                                <Link href={safeRoute('financial.add-balance')} className="flex items-center px-3 py-2.5 text-sm font-medium text-text-primary hover:bg-surface-raised rounded-lg transition-colors">
                                    <Plus className="w-4 h-4 mr-3 text-text-muted" /> Add Funds to Wallet
                                </Link>
                                <Link href={safeRoute('financial.withdrawals')} className="flex items-center px-3 py-2.5 text-sm font-medium text-text-primary hover:bg-surface-raised rounded-lg transition-colors">
                                    <ArrowUpRight className="w-4 h-4 mr-3 text-text-muted" /> Request Withdrawal
                                </Link>
                                <Link href={safeRoute('financial.payout-methods.index')} className="flex items-center px-3 py-2.5 text-sm font-medium text-text-primary hover:bg-surface-raised rounded-lg transition-colors">
                                    <CreditCard className="w-4 h-4 mr-3 text-text-muted" /> Manage Payment Methods
                                </Link>
                                <Link href={safeRoute('tools.my-licenses')} className="flex items-center px-3 py-2.5 text-sm font-medium text-text-primary hover:bg-surface-raised rounded-lg transition-colors">
                                    <Key className="w-4 h-4 mr-3 text-text-muted" /> My Tool Licenses
                                </Link>
                            </div>
                        </OperationalCard>

                        {/* My Tools Widget */}
                        <OperationalCard
                            title="My Tools"
                            action={
                                <Link href={safeRoute('tools.explore')} className="text-[11px] font-bold uppercase tracking-wider text-primary hover:underline transition-colors">
                                    Browse All
                                </Link>
                            }
                            noPadding
                        >
                            {activeToolLicenses.length === 0 ? (
                                <div className="px-4 py-6 text-center">
                                    <Wrench className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                    <p className="text-xs text-text-muted font-medium">No tools yet</p>
                                    <Link
                                        href={safeRoute('tools.explore')}
                                        className="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-fuchsia-600 hover:text-fuchsia-700"
                                    >
                                        <ShoppingBag className="w-3.5 h-3.5" /> Explore Marketplace
                                    </Link>
                                </div>
                            ) : (
                                <div className="divide-y divide-border/40">
                                    {activeToolLicenses.slice(0, 4).map((lic) => (
                                        <div key={lic.license_key} className="flex items-center gap-3 px-4 py-3 hover:bg-surface-raised transition-colors">
                                            <div className="w-8 h-8 rounded-lg bg-fuchsia-50 flex items-center justify-center flex-shrink-0">
                                                {lic.tool.icon_url
                                                    ? <img src={lic.tool.icon_url} alt="" className="w-5 h-5 object-contain" />
                                                    : <Wrench className="w-4 h-4 text-fuchsia-500" />
                                                }
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-text-primary truncate">{lic.tool.title}</p>
                                                <p className="text-[10px] text-text-muted">{lic.active_devices}/{lic.max_devices} devices</p>
                                            </div>
                                            <Link
                                                href={safeRoute('tools.download.generate', lic.tool.slug)}
                                                className="flex-shrink-0 w-7 h-7 rounded-lg bg-slate-100 hover:bg-fuchsia-100 flex items-center justify-center transition-colors"
                                                title="Download"
                                            >
                                                <Download className="w-3.5 h-3.5 text-slate-500 hover:text-fuchsia-600" />
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </OperationalCard>

                        <OperationalCard 
                            title="Recent Transactions" 
                            action={
                                <Link href={safeRoute('erp.wallet.show', user?.id || 1, `/erp/clients/${user?.id || 1}/wallet`)} className="text-[11px] font-bold uppercase tracking-wider text-primary hover:underline transition-colors">
                                    History
                                </Link>
                            }
                        >
                            {activityFeedItems.length === 0 ? (
                                <EmptyState 
                                    icon={Inbox}
                                    title="No transactions"
                                    description="Transactions will appear here when you add funds or pay invoices."
                                />
                            ) : (
                                <ActivityFeed items={activityFeedItems} />
                            )}
                        </OperationalCard>

                    </div>
                </div>
            </div>
        </WorkspaceLayout>
    );
}
