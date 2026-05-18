import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from "@/Layouts/AdminLayout";
import { DollarSign, Building2, Users, ArrowDownCircle, Inbox, Plus, BarChart3, Settings } from 'lucide-react';
import { LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer } from 'recharts';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { AppPage } from '@/Components/ui/AppPage';
import { PageHeader } from '@/Components/ui/PageHeader';
import { StatCard } from '@/Components/ui/StatCard';
import { SectionCard } from '@/Components/ui/SectionCard';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { EmptyState } from '@/Components/ui/EmptyState';

function formatCurrency(amount: number, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
}

const statusMap: Record<string, string> = {
    'paid': 'success',
    'sent': 'pending',
    'partial': 'pending',
    'draft': 'neutral',
    'pending': 'pending',
    'approved': 'success',
    'cancelled': 'danger',
    'rejected': 'danger',
};

export default function Dashboard({ stats, revenueChartData, moduleBreakdown, recentInvoices, recentWithdrawals, newTenants, auth }: any) {
    const hasData = stats && (stats.totalClients > 0 || stats.activeTenants > 0 || (stats.revenueThisMonth || 0) > 0);

    const chartData = revenueChartData || [];
    const pieData = moduleBreakdown || [{ name: 'No data', value: 0, color: '#94a3b8' }];
    const tenants = newTenants || [];

    const invoiceColumns = [
        { key: 'client_name', label: 'Client', render: (row: any) => <span className="font-medium text-sm">{row.client_name}</span> },
        { key: 'amount', label: 'Amount', render: (row: any) => <span className="font-mono text-sm">{formatCurrency(row.amount, row.currency)}</span> },
        { key: 'status', label: 'Status', render: (row: any) => <StatusBadge status={statusMap[row.status] || 'neutral'} label={row.status} size="sm" /> },
    ];

    const withdrawalColumns = [
        { key: 'user_name', label: 'User', render: (row: any) => <span className="font-medium text-sm">{row.user_name}</span> },
        { key: 'amount', label: 'Amount', render: (row: any) => <span className="font-mono text-sm">{formatCurrency(row.amount, row.currency)}</span> },
        { key: 'status', label: 'Status', render: (row: any) => (
            <div className="flex items-center gap-2">
                <StatusBadge status={statusMap[row.status] || 'neutral'} label={row.status} size="sm" />
                {row.status === 'pending' && (
                    <Button variant="ghost" size="sm" className="h-6 text-[10px] px-2" asChild>
                        <Link href="#">Review</Link>
                    </Button>
                )}
            </div>
        ) },
    ];

    return (
        <AdminLayout user={auth?.user}>
            <Head title="Admin Platform" />
            
            <AppPage>
                <PageHeader 
                    title="Platform Administration"
                    subtitle="Monitor global platform revenue, tenants, and infrastructure."
                    actions={
                        <div className="flex items-center gap-2">
                            <Link 
                                href={route().has('erp.invoices.create') ? route('erp.invoices.create') : '#'}
                                className="inline-flex items-center justify-center rounded-lg px-3.5 py-2 text-xs font-semibold text-text-primary bg-surface border border-border hover:bg-surface-raised transition-colors shadow-sm"
                            >
                                <Plus className="w-3.5 h-3.5 mr-1.5" /> Invoice
                            </Link>
                            <Link 
                                href="/admin/clients"
                                className="inline-flex items-center justify-center rounded-lg px-3.5 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover transition-colors shadow-sm"
                            >
                                <Plus className="w-3.5 h-3.5 mr-1.5" /> Client
                            </Link>
                        </div>
                    }
                />

                {!hasData ? (
                    <SectionCard>
                        <EmptyState 
                            icon={Building2}
                            title="Welcome to your ERP, Admin!"
                            description="Get started by setting up the platform with your first client, invoice, and bank account."
                            action="/admin/clients"
                            actionLabel="Add First Client"
                        />
                    </SectionCard>
                ) : (
                    <>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <StatCard
                                label="Revenue This Month"
                                value={formatCurrency(stats.revenueThisMonth)}
                                change={stats.revenueGrowth ? `${Math.abs(stats.revenueGrowth)}%` : undefined}
                                changeType={stats.revenueGrowth > 0 ? 'up' : stats.revenueGrowth < 0 ? 'down' : 'neutral'}
                                icon={DollarSign}
                            />
                            <StatCard
                                label="Active Tenants"
                                value={stats.activeTenants}
                                description="Live Workspaces"
                                icon={Building2}
                            />
                            <StatCard
                                label="Active Clients"
                                value={stats.totalClients}
                                change={stats.recentClients > 0 ? `+${stats.recentClients}` : undefined}
                                changeType={stats.recentClients > 0 ? 'up' : 'neutral'}
                                icon={Users}
                            />
                            <StatCard
                                label="Pending Withdrawals"
                                value={stats.pendingWithdrawals}
                                description={stats.pendingWithdrawalAmount > 0 ? `${formatCurrency(stats.pendingWithdrawalAmount)} pending` : 'All clear'}
                                icon={ArrowDownCircle}
                            />
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                            <SectionCard title="Revenue Trajectory (12 Months)" className="lg:col-span-3">
                                <div className="h-[300px] w-full">
                                    {chartData.length > 0 ? (
                                        <ResponsiveContainer width="100%" height="100%">
                                            <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                                <YAxis axisLine={false} tickLine={false} tickFormatter={(value) => `$${value}`} tick={{ fontSize: 12, fill: '#64748b' }} dx={-10} />
                                                <RechartsTooltip formatter={(value: any) => [`$${value}`, undefined]} contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 2px 0 rgb(0 0 0 / 0.05)' }} />
                                                <Legend wrapperStyle={{ paddingTop: '20px' }} />
                                                <Line type="monotone" dataKey="income" name="Income" stroke="#4f46e5" strokeWidth={2} activeDot={{ r: 6 }} dot={false} />
                                                <Line type="monotone" dataKey="expenses" name="Expenses" stroke="#ef4444" strokeWidth={2} dot={false} />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <EmptyState icon={BarChart3} title="No revenue data yet" />
                                    )}
                                </div>
                            </SectionCard>

                            <SectionCard title="Module Breakdown" className="lg:col-span-2">
                                <div className="h-[300px] w-full">
                                    {pieData.some((d: any) => d.value > 0) ? (
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChart>
                                                <Pie
                                                    data={pieData}
                                                    cx="50%"
                                                    cy="50%"
                                                    innerRadius={70}
                                                    outerRadius={90}
                                                    paddingAngle={5}
                                                    dataKey="value"
                                                    stroke="none"
                                                >
                                                    {pieData.map((entry: any, index: number) => (
                                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <RechartsTooltip formatter={(value: any) => [formatCurrency(value), undefined]} contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 2px 0 rgb(0 0 0 / 0.05)' }} />
                                                <Legend verticalAlign="bottom" height={36} formatter={(value, entry: any) => <span className="text-xs font-medium text-slate-600">{value} ({formatCurrency(entry.payload.value)})</span>}/>
                                            </PieChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <EmptyState icon={BarChart3} title="No module revenue yet" />
                                    )}
                                </div>
                            </SectionCard>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <SectionCard 
                                title="Recent Invoices" 
                                action={<Link href={route().has('erp.invoices.index') ? route('erp.invoices.index') : '#'} className="text-[11px] font-bold uppercase tracking-wider text-primary hover:underline transition-colors">View All</Link>}
                                noPadding
                            >
                                <DataTable 
                                    columns={invoiceColumns}
                                    data={recentInvoices || []}
                                    emptyState={<EmptyState icon={Inbox} title="No recent invoices" />}
                                    className="border-0 shadow-none rounded-none"
                                />
                            </SectionCard>

                            <SectionCard 
                                title="Recent Withdrawals" 
                                noPadding
                            >
                                <DataTable 
                                    columns={withdrawalColumns}
                                    data={recentWithdrawals || []}
                                    emptyState={<EmptyState icon={ArrowDownCircle} title="No recent withdrawals" />}
                                    className="border-0 shadow-none rounded-none"
                                />
                            </SectionCard>

                            <SectionCard title="New Tenants" noPadding>
                                <div className="divide-y divide-border/40">
                                    {tenants.length > 0 ? tenants.map((tenant: any) => (
                                        <div key={tenant.id} className="p-4 flex items-center justify-between hover:bg-surface-raised transition-colors">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-9 w-9">
                                                    <AvatarFallback className="font-sans font-bold bg-surface-raised text-primary text-xs">{tenant.initials}</AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="text-sm font-semibold leading-none text-text-primary">{tenant.name}</p>
                                                    <p className="text-xs text-text-muted mt-1">{tenant.created_at}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <StatusBadge status={tenant.status === 'active' ? 'success' : 'neutral'} label={tenant.status} size="sm" />
                                                <Link href={`/admin/clients/${tenant.id}`} className="text-xs font-semibold text-primary hover:underline">View</Link>
                                            </div>
                                        </div>
                                    )) : (
                                        <EmptyState icon={Building2} title="No tenants yet" />
                                    )}
                                </div>
                            </SectionCard>
                        </div>
                    </>
                )}
            </AppPage>

            {/* QUICK ACTIONS */}
            <div className="fixed bottom-0 left-0 right-0 md:left-64 bg-surface/80 backdrop-blur-md border-t border-border p-4 z-50 flex justify-center shadow-lg">
                <div className="flex items-center space-x-3">
                    <Button className="h-9 text-xs font-semibold shadow-sm" asChild>
                        <Link href={route().has('erp.invoices.create') ? route('erp.invoices.create') : '#'}><Plus className="w-3.5 h-3.5 mr-1.5" /> New Invoice</Link>
                    </Button>
                    <Button variant="secondary" className="h-9 text-xs font-semibold shadow-sm bg-surface hover:bg-surface-raised border border-border" asChild>
                        <Link href="/admin/clients"><Plus className="w-3.5 h-3.5 mr-1.5" /> New Client</Link>
                    </Button>
                    <Button variant="outline" className="h-9 text-xs font-semibold shadow-sm border border-border bg-surface hover:bg-surface-raised" asChild>
                        <Link href="/admin/reports/pnl"><BarChart3 className="w-3.5 h-3.5 mr-1.5" /> Run Report</Link>
                    </Button>
                    <Button variant="ghost" className="h-9 text-xs font-semibold" asChild>
                        <Link href="#"><Settings className="w-3.5 h-3.5 mr-1.5" /> Settings</Link>
                    </Button>
                </div>
            </div>
        </AdminLayout>
    );
}
