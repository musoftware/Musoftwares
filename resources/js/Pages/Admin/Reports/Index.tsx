import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { 
    DollarSign, Users, Database, Server, HardDrive, Activity, 
    BarChart3, Briefcase, Ticket, UserPlus, ArrowUpRight, ArrowDownRight, Clock
} from 'lucide-react';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { 
    LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, 
    CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer 
} from 'recharts';
import { Button } from '@/Components/ui/button';

export default function ReportsIndex({
    pnl,
    stats, 
    operationalStats, 
    systemHealth, 
    revenueChartData, 
    moduleBreakdown, 
    auth 
}: any) {
    const businessCurrency = stats?.businessCurrency || 'USD';
    const [showValues, setShowValues] = useState(true);
    
    // P&L form state
    const { data: pnlData, setData: setPnlData, get: pnlGet } = useForm({
        from: pnl?.filters?.from || '',
        to: pnl?.filters?.to || '',
    });

    const handlePnlFilter = (e: React.FormEvent) => {
        e.preventDefault();
        pnlGet('/admin/reports');
    };

    const chartData = revenueChartData || [];
    const pieData = moduleBreakdown || [{ name: 'No data', value: 0, color: '#94a3b8' }];
    
    const toggleValues = () => setShowValues(!showValues);
    const maskValue = (val: string | number) => showValues ? val : '****';

    return (
        <AdminSidebarLayout 
            title="Reports & Analytics"
            header="System Reports"
            user={auth?.user}
        >
            <div className="space-y-6 pb-10">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider">Analytics</span>
                        </div>
                        <h1 className="text-2xl font-bold text-slate-900">System Reports</h1>
                        <p className="text-sm text-slate-500 mt-1">Comprehensive details about the managed system and financials.</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" onClick={toggleValues}>
                            {showValues ? 'Hide Values' : 'Show Values'}
                        </Button>
                    </div>
                </div>

                <Tabs defaultValue="overview" className="w-full space-y-6">
                    <TabsList className="bg-white border border-slate-200 p-1 rounded-lg w-full justify-start h-auto overflow-x-auto">
                        <TabsTrigger value="overview" className="px-4 py-2 text-sm font-medium">System Overview</TabsTrigger>
                        <TabsTrigger value="pnl" className="px-4 py-2 text-sm font-medium">Profit & Loss (P&L)</TabsTrigger>
                        <TabsTrigger value="health" className="px-4 py-2 text-sm font-medium">System Health</TabsTrigger>
                        <TabsTrigger value="ops" className="px-4 py-2 text-sm font-medium">Operational Metrics</TabsTrigger>
                    </TabsList>

                    {/* OVERVIEW TAB */}
                    <TabsContent value="overview" className="space-y-6 outline-none">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                            <MetricCard label="Monthly Revenue" value={maskValue(formatCurrency(stats?.revenueThisMonth, businessCurrency))} icon={DollarSign} />
                            <MetricCard label="Monthly Expenses" value={maskValue(formatCurrency(stats?.monthlyExpenses, businessCurrency))} icon={ArrowDownRight} />
                            <MetricCard label="Booking Price" value={maskValue(formatCurrency(stats?.bookingPrice, businessCurrency))} icon={Activity} />
                            <MetricCard label="Hourly Rate" value={maskValue(formatCurrency(stats?.bookingRatePerHour, businessCurrency))} icon={Clock} />
                            <MetricCard label="Pending Payments" value={maskValue(formatCurrency(stats?.pendingPayments, businessCurrency))} icon={ArrowUpRight} />
                            <MetricCard label="Total Users" value={maskValue(stats?.totalUsers)} icon={Users} />
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                            <OperationalCard title="Revenue Trajectory (12 Months)" className="lg:col-span-3">
                                <div className="h-[300px] w-full">
                                    {chartData.length > 0 ? (
                                        <ResponsiveContainer width="100%" height="100%">
                                            <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                                <YAxis axisLine={false} tickLine={false} tickFormatter={(value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: businessCurrency, maximumFractionDigits: 0 }).format(value)} tick={{ fontSize: 12, fill: '#64748b' }} dx={-10} />
                                                <RechartsTooltip formatter={(value: any) => [formatCurrency(value, businessCurrency), undefined]} contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 2px 0 rgb(0 0 0 / 0.05)' }} />
                                                <Legend wrapperStyle={{ paddingTop: '20px' }} />
                                                <Line type="monotone" dataKey="income" name="Income" stroke="#10b981" strokeWidth={2} activeDot={{ r: 6 }} dot={false} />
                                                <Line type="monotone" dataKey="expenses" name="Expenses" stroke="#ef4444" strokeWidth={2} dot={false} />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <EmptyState icon={BarChart3} title="No revenue data yet" />
                                    )}
                                </div>
                            </OperationalCard>

                            <OperationalCard title="Module Breakdown" className="lg:col-span-2">
                                <div className="h-[300px] w-full">
                                    {pieData.some((d: any) => d.value > 0) ? (
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChart>
                                                <Pie data={pieData} cx="50%" cy="50%" innerRadius={70} outerRadius={90} paddingAngle={5} dataKey="value" stroke="none">
                                                    {pieData.map((entry: any, index: number) => (
                                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <RechartsTooltip formatter={(value: any) => [formatCurrency(value, businessCurrency), undefined]} contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 2px 0 rgb(0 0 0 / 0.05)' }} />
                                                <Legend verticalAlign="bottom" height={36} formatter={(value, entry: any) => <span className="text-xs font-medium text-slate-600">{value} ({formatCurrency(entry.payload.value, businessCurrency)})</span>}/>
                                            </PieChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <EmptyState icon={BarChart3} title="No module revenue yet" />
                                    )}
                                </div>
                            </OperationalCard>
                        </div>
                    </TabsContent>

                    {/* P&L TAB */}
                    <TabsContent value="pnl" className="space-y-6 outline-none">
                        <form onSubmit={handlePnlFilter} className="flex flex-col sm:flex-row items-end gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <div className="w-full sm:w-auto">
                                <label className="mb-1 block text-sm font-medium text-slate-700">From Date</label>
                                <input
                                    type="date"
                                    value={pnlData.from}
                                    onChange={(e) => setPnlData('from', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                                />
                            </div>
                            <div className="w-full sm:w-auto">
                                <label className="mb-1 block text-sm font-medium text-slate-700">To Date</label>
                                <input
                                    type="date"
                                    value={pnlData.to}
                                    onChange={(e) => setPnlData('to', e.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                                />
                            </div>
                            <Button type="submit" className="w-full sm:w-auto">
                                Apply Filter
                            </Button>
                        </form>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <OperationalCard title="Income Breakdown">
                                <ul className="space-y-3 font-mono text-sm">
                                    {Object.entries(pnl?.incomeBreakdown || {}).map(([source, amount]: any) => (
                                        <li key={source} className="flex justify-between items-center border-b border-slate-100 pb-2">
                                            <span className="text-slate-600">{source}</span>
                                            <span className="font-semibold">{maskValue(formatCurrency(amount, businessCurrency))}</span>
                                        </li>
                                    ))}
                                    <li className="flex justify-between items-center pt-2 text-base font-bold text-slate-900">
                                        <span>Total Income</span>
                                        <span className="text-green-600">{maskValue(formatCurrency(pnl?.totalIncome || 0, businessCurrency))}</span>
                                    </li>
                                </ul>
                            </OperationalCard>

                            <OperationalCard title="Expense Breakdown">
                                <ul className="space-y-3 font-mono text-sm">
                                    {Object.entries(pnl?.expenseBreakdown || {}).map(([type, amount]: any) => (
                                        <li key={type} className="flex justify-between items-center border-b border-slate-100 pb-2">
                                            <span className="text-slate-600">{type}</span>
                                            <span className="font-semibold">{maskValue(formatCurrency(amount, businessCurrency))}</span>
                                        </li>
                                    ))}
                                    <li className="flex justify-between items-center pt-2 text-base font-bold text-slate-900">
                                        <span>Total Expenses</span>
                                        <span className="text-red-600">{maskValue(formatCurrency(pnl?.totalExpenses || 0, businessCurrency))}</span>
                                    </li>
                                </ul>
                            </OperationalCard>
                        </div>

                        <div className="bg-slate-900 text-white p-6 rounded-xl shadow flex items-center justify-between">
                            <span className="text-xl font-bold">Net Profit</span>
                            <span className={`text-2xl font-extrabold ${(pnl?.netProfit || 0) >= 0 ? 'text-green-400' : 'text-red-400'}`}>
                                {maskValue(formatCurrency(pnl?.netProfit || 0, businessCurrency))}
                            </span>
                        </div>

                        <OperationalCard title="Tenant Revenue Stats">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="text-slate-500 border-b border-slate-200">
                                        <tr>
                                            <th className="pb-3 font-medium">Tenant Name</th>
                                            <th className="pb-3 text-right font-medium">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {(pnl?.tenantStats || []).map((tenant: any, idx: number) => (
                                            <tr key={idx}>
                                                <td className="py-3 font-medium text-slate-700">{tenant.tenant_name}</td>
                                                <td className="py-3 text-right font-mono text-slate-900">
                                                    {maskValue(formatCurrency(tenant.revenue, businessCurrency))}
                                                </td>
                                            </tr>
                                        ))}
                                        {(!pnl?.tenantStats || pnl.tenantStats.length === 0) && (
                                            <tr>
                                                <td colSpan={2} className="py-8 text-center text-slate-500">
                                                    No tenant stats found for this period.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </OperationalCard>
                    </TabsContent>

                    {/* SYSTEM HEALTH TAB */}
                    <TabsContent value="health" className="space-y-6 outline-none">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                                    <Database className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">Database</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.database}</p>
                                </div>
                            </div>
                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                    <Server className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">Server Load</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.serverLoad}</p>
                                </div>
                            </div>
                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center text-orange-600">
                                    <HardDrive className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">Disk Usage</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.diskUsage}</p>
                                </div>
                            </div>
                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                                <div className="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                                    <Activity className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">Memory</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.memoryUsage}</p>
                                </div>
                            </div>
                        </div>

                        <OperationalCard title="WhatsApp Services">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 h-full">
                                <div className="flex flex-col items-center justify-center p-8 bg-green-50 rounded-xl border border-green-100">
                                    <h3 className="text-4xl font-bold text-green-600 mb-2">{maskValue(systemHealth?.whatsappUsers)}</h3>
                                    <p className="text-slate-700 font-medium">Active WhatsApp Users</p>
                                </div>
                                <div className="flex flex-col items-center justify-center p-8 bg-blue-50 rounded-xl border border-blue-100">
                                    <h3 className="text-4xl font-bold text-blue-600 mb-2">{maskValue(formatCurrency(systemHealth?.totalWhatsappBalance, businessCurrency))}</h3>
                                    <p className="text-slate-700 font-medium">Total WhatsApp Balance</p>
                                </div>
                            </div>
                        </OperationalCard>
                    </TabsContent>

                    {/* OPERATIONAL METRICS TAB */}
                    <TabsContent value="ops" className="space-y-6 outline-none">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <MetricCard label="Total Projects" value={maskValue(operationalStats?.totalProjects)} icon={Briefcase} />
                            <MetricCard label="Active Projects" value={maskValue(operationalStats?.activeProjects)} icon={Briefcase} />
                            <MetricCard label="Total Tasks" value={maskValue(operationalStats?.totalTasks)} icon={Activity} />
                            <MetricCard label="Pending Tasks" value={maskValue(operationalStats?.pendingTasks)} icon={Clock} />
                            
                            <MetricCard label="Open Tickets" value={maskValue(operationalStats?.openTickets)} icon={Ticket} />
                            <MetricCard label="Urgent Tickets" value={maskValue(operationalStats?.urgentTickets)} icon={Ticket} />
                            <MetricCard label="Premium Users" value={maskValue(operationalStats?.premiumUsers)} icon={UserPlus} />
                            <MetricCard label="Active Users (30d)" value={maskValue(operationalStats?.activeUsers30d)} icon={Users} />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600"><Briefcase className="w-5 h-5"/></div>
                                    <h3 className="font-bold text-slate-800">Project Completion</h3>
                                </div>
                                <div className="flex items-end gap-2">
                                    <span className="text-4xl font-bold text-slate-900">
                                        {operationalStats?.totalProjects > 0 ? maskValue(Math.round((operationalStats?.completedProjects / operationalStats?.totalProjects) * 100)) : 0}%
                                    </span>
                                    <span className="text-sm text-slate-500 mb-1">completed</span>
                                </div>
                                <div className="mt-4 w-full bg-slate-100 rounded-full h-2">
                                    <div 
                                        className="bg-orange-500 h-2 rounded-full" 
                                        style={{ width: `${operationalStats?.totalProjects > 0 ? Math.round((operationalStats?.completedProjects / operationalStats?.totalProjects) * 100) : 0}%` }}
                                    ></div>
                                </div>
                            </div>

                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600"><Activity className="w-5 h-5"/></div>
                                    <h3 className="font-bold text-slate-800">Task Completion</h3>
                                </div>
                                <div className="flex items-end gap-2">
                                    <span className="text-4xl font-bold text-slate-900">
                                        {operationalStats?.totalTasks > 0 ? maskValue(Math.round((operationalStats?.completedTasks / operationalStats?.totalTasks) * 100)) : 0}%
                                    </span>
                                    <span className="text-sm text-slate-500 mb-1">completed</span>
                                </div>
                                <div className="mt-4 w-full bg-slate-100 rounded-full h-2">
                                    <div 
                                        className="bg-blue-500 h-2 rounded-full" 
                                        style={{ width: `${operationalStats?.totalTasks > 0 ? Math.round((operationalStats?.completedTasks / operationalStats?.totalTasks) * 100) : 0}%` }}
                                    ></div>
                                </div>
                            </div>
                            
                            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600"><Users className="w-5 h-5"/></div>
                                    <h3 className="font-bold text-slate-800">User Engagement</h3>
                                </div>
                                <div className="flex items-end gap-2">
                                    <span className="text-4xl font-bold text-slate-900">
                                        {stats?.totalUsers > 0 ? maskValue(Math.round((operationalStats?.activeUsers30d / stats?.totalUsers) * 100)) : 0}%
                                    </span>
                                    <span className="text-sm text-slate-500 mb-1">active (30d)</span>
                                </div>
                                <div className="mt-4 w-full bg-slate-100 rounded-full h-2">
                                    <div 
                                        className="bg-green-500 h-2 rounded-full" 
                                        style={{ width: `${stats?.totalUsers > 0 ? Math.round((operationalStats?.activeUsers30d / stats?.totalUsers) * 100) : 0}%` }}
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AdminSidebarLayout>
    );
}
