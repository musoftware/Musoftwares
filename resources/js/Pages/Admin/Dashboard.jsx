import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from "@/Layouts/AdminLayout";
import { StatCard } from '@/Components/StatCard';
import { DollarSign, Building2, Users, ArrowDownCircle, TrendingUp, TrendingDown, Inbox } from 'lucide-react';
import { LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer } from 'recharts';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
}

function GrowthBadge({ value }) {
    if (value === null || value === undefined) return <span className="text-xs text-muted-foreground">No prior data</span>;
    const isUp = value >= 0;
    return (
        <span className={`text-xs font-medium flex items-center gap-0.5 ${isUp ? 'text-emerald-600' : 'text-rose-600'}`}>
            {isUp ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
            {isUp ? '+' : ''}{value}%
        </span>
    );
}

const statusVariant = (status) => {
    switch (status) {
        case 'paid': return 'default';
        case 'sent': case 'partial': return 'secondary';
        case 'draft': return 'outline';
        case 'pending': return 'secondary';
        case 'approved': return 'default';
        case 'cancelled': case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

export default function Dashboard({ stats, revenueChartData, moduleBreakdown, recentInvoices, recentWithdrawals, newTenants, auth }) {
    const hasData = stats && (stats.totalClients > 0 || stats.activeTenants > 0 || (stats.revenueThisMonth || 0) > 0);

    // Use server data for charts (no more hardcoded mock data)
    const chartData = revenueChartData || [];
    const pieData = moduleBreakdown || [{ name: 'No data', value: 0, color: '#94a3b8' }];
    const tenants = newTenants || [];

    return (
        <AdminLayout user={auth?.user}>
            <Head title="Admin Dashboard" />
            <div className="space-y-6 pb-20 p-6">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-3xl font-bold font-sora tracking-tight">Admin Dashboard</h1>
                </div>

                {!hasData ? (
                    /* Welcome State (No Data) */
                    <div className="bg-white p-8 rounded-[12px] shadow-lg border border-gray-100 max-w-3xl mx-auto text-center mt-12">
                        <h3 className="text-[24px] font-bold font-sora mb-6">Welcome to your ERP, Admin! 👋</h3>
                        <p className="text-gray-600 mb-8">Get started by setting up the platform:</p>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 text-left max-w-md mx-auto">
                            <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px]">
                                <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold font-jetbrains">1</div>
                                <span className="font-medium">Add your first client</span>
                            </div>
                            <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px]">
                                <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold font-jetbrains">2</div>
                                <span className="font-medium">Create your first invoice</span>
                            </div>
                            <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-[8px] md:col-span-2">
                                <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold font-jetbrains">3</div>
                                <span className="font-medium">Set up your bank account</span>
                            </div>
                        </div>

                        <div className="flex justify-center space-x-4">
                            <Link href="/admin/clients" className="bg-indigo-600 text-white px-6 py-2 rounded-[8px] hover:bg-indigo-700 transition font-medium shadow-sm">
                                Add Client
                            </Link>
                            <Link href={route().has('erp.invoices.create') ? route('erp.invoices.create') : '#'} className="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-[8px] hover:bg-gray-50 transition font-medium">
                                Create Invoice
                            </Link>
                        </div>
                    </div>
                ) : (
                    <>
                        {/* TOP STATS ROW (4 cards) — all real data */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <StatCard
                                title="Revenue This Month"
                                value={formatCurrency(stats.revenueThisMonth)}
                                subtitle={<GrowthBadge value={stats.revenueGrowth} />}
                                icon={DollarSign}
                                trend={stats.revenueGrowth >= 0 ? 'up' : 'down'}
                            />
                            <StatCard
                                title="Active Tenants"
                                value={`${stats.activeTenants} tenant${stats.activeTenants !== 1 ? 's' : ''}`}
                                subtitle={`${stats.activeTenants} active workspace${stats.activeTenants !== 1 ? 's' : ''}`}
                                icon={Building2}
                                trend="neutral"
                            />
                            <StatCard
                                title="Active Clients"
                                value={`${stats.totalClients} client${stats.totalClients !== 1 ? 's' : ''}`}
                                subtitle={stats.recentClients > 0 ? <span className="text-emerald-600">+{stats.recentClients} last 30d</span> : 'No new clients'}
                                icon={Users}
                                trend={stats.recentClients > 0 ? 'up' : 'neutral'}
                            />
                            <StatCard
                                title="Pending Withdrawals"
                                value={`${stats.pendingWithdrawals} request${stats.pendingWithdrawals !== 1 ? 's' : ''}`}
                                subtitle={stats.pendingWithdrawalAmount > 0 ? formatCurrency(stats.pendingWithdrawalAmount) + ' pending' : 'All clear'}
                                icon={ArrowDownCircle}
                                trend={stats.pendingWithdrawals > 0 ? 'neutral' : 'up'}
                            />
                        </div>

                        {/* SECOND ROW — Charts (real data from server) */}
                        <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                            {/* Revenue Chart (60% -> 3/5 cols) */}
                            <Card className="lg:col-span-3">
                                <CardHeader>
                                    <CardTitle>Revenue Chart (12 months)</CardTitle>
                                </CardHeader>
                                <CardContent className="pl-0">
                                    <div className="h-[300px] w-full">
                                        {chartData.length > 0 ? (
                                            <ResponsiveContainer width="100%" height="100%">
                                                <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                                    <XAxis dataKey="name" axisLine={false} tickLine={false} />
                                                    <YAxis axisLine={false} tickLine={false} tickFormatter={(value) => `$${value}`} />
                                                    <RechartsTooltip formatter={(value) => [`$${value}`, undefined]} />
                                                    <Legend />
                                                    <Line type="monotone" dataKey="income" name="Income" stroke="#4f46e5" strokeWidth={2} activeDot={{ r: 8 }} />
                                                    <Line type="monotone" dataKey="expenses" name="Expenses" stroke="#ef4444" strokeWidth={2} />
                                                </LineChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <div className="flex items-center justify-center h-full text-muted-foreground">
                                                <Inbox className="w-6 h-6 mr-2" /> No revenue data yet
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Module Breakdown (40% -> 2/5 cols) */}
                            <Card className="lg:col-span-2">
                                <CardHeader>
                                    <CardTitle>Module Breakdown</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="h-[300px] w-full">
                                        {pieData.some(d => d.value > 0) ? (
                                            <ResponsiveContainer width="100%" height="100%">
                                                <PieChart>
                                                    <Pie
                                                        data={pieData}
                                                        cx="50%"
                                                        cy="50%"
                                                        innerRadius={60}
                                                        outerRadius={80}
                                                        paddingAngle={5}
                                                        dataKey="value"
                                                    >
                                                        {pieData.map((entry, index) => (
                                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                                        ))}
                                                    </Pie>
                                                    <RechartsTooltip formatter={(value) => [formatCurrency(value), undefined]} />
                                                    <Legend verticalAlign="bottom" height={36} formatter={(value, entry) => <span className="text-sm text-gray-700">{value} ({formatCurrency(entry.payload.value)})</span>}/>
                                                </PieChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <div className="flex items-center justify-center h-full text-muted-foreground">
                                                <Inbox className="w-6 h-6 mr-2" /> No module revenue yet
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* THIRD ROW — 3 columns (all real data) */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Col 1: Recent Invoices */}
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle className="text-lg font-sora">Recent Invoices</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Client</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(recentInvoices || []).map((invoice) => (
                                                <TableRow key={invoice.id}>
                                                    <TableCell className="font-medium">{invoice.client_name}</TableCell>
                                                    <TableCell className="font-jetbrains">
                                                        {formatCurrency(invoice.amount, invoice.currency)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={statusVariant(invoice.status)}>
                                                            {invoice.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            {(!recentInvoices || recentInvoices.length === 0) && (
                                                <TableRow>
                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No recent invoices</TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                    <div className="mt-4">
                                        <Link href={route().has('erp.invoices.index') ? route('erp.invoices.index') : '#'} className="text-sm text-indigo-600 hover:underline font-medium">View all →</Link>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Col 2: Recent Withdrawals */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg font-sora">Recent Withdrawals</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>User</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(recentWithdrawals || []).map((withdrawal) => (
                                                <TableRow key={withdrawal.id}>
                                                    <TableCell className="font-medium">{withdrawal.user_name}</TableCell>
                                                    <TableCell className="font-jetbrains">
                                                        {formatCurrency(withdrawal.amount, withdrawal.currency)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Badge variant={statusVariant(withdrawal.status)}>
                                                                {withdrawal.status}
                                                            </Badge>
                                                            {withdrawal.status === 'pending' && (
                                                                <Button variant="outline" size="sm" className="h-6 text-xs px-2" asChild>
                                                                    <Link href="#">Review →</Link>
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            {(!recentWithdrawals || recentWithdrawals.length === 0) && (
                                                <TableRow>
                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No recent withdrawals</TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>

                            {/* Col 3: New Tenants (real data from server) */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg font-sora">New Tenants</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {tenants.length > 0 ? tenants.map((tenant) => (
                                            <div key={tenant.id} className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <Avatar>
                                                        <AvatarFallback className="font-jetbrains font-bold bg-indigo-100 text-indigo-600">{tenant.initials}</AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p className="text-sm font-medium leading-none">{tenant.name}</p>
                                                        <p className="text-xs text-muted-foreground">{tenant.created_at}</p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <Badge variant={tenant.status === 'active' ? 'default' : 'outline'}>{tenant.status}</Badge>
                                                    <Link href={`/admin/clients/${tenant.id}`} className="text-sm text-indigo-600 hover:underline font-medium">View →</Link>
                                                </div>
                                            </div>
                                        )) : (
                                            <div className="text-center text-muted-foreground py-4">
                                                <Inbox className="w-5 h-5 mx-auto mb-2" />
                                                <p className="text-sm">No tenants yet</p>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </>
                )}
            </div>

            {/* QUICK ACTIONS (floating bar at bottom) */}
            <div className="fixed bottom-0 left-0 right-0 md:left-64 bg-white/80 backdrop-blur-md border-t border-gray-200 p-4 z-10 flex justify-center shadow-lg">
                <div className="flex space-x-4">
                    <Button variant="default" asChild>
                        <Link href={route().has('erp.invoices.create') ? route('erp.invoices.create') : '#'}>+ New Invoice</Link>
                    </Button>
                    <Button variant="secondary" asChild>
                        <Link href="/admin/clients">+ New Client</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/admin/reports/pnl">📊 Run Report</Link>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href="#">⚙️ Settings</Link>
                    </Button>
                </div>
            </div>
        </AdminLayout>
    );
}
