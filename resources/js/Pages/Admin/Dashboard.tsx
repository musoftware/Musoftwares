import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { 
    DollarSign, Building2, Users, ArrowDownCircle, Inbox, Plus, 
    BarChart3, Settings, Briefcase, Calculator, MessageSquare, 
    Calendar, UserPlus, Database, Clock, Activity, HardDrive,
    Server, Wifi, ArrowUpRight, ArrowDownRight, Ticket
} from 'lucide-react';
import { 
    LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, 
    CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer 
} from 'recharts';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Dashboard({ 
    stats, operationalStats, systemHealth, revenueChartData, 
    moduleBreakdown, recentActivities, auth 
}: any) {
    const businessCurrency = stats?.businessCurrency || 'USD';
    const [currentTime, setCurrentTime] = useState(new Date().toLocaleTimeString());
    const [showValues, setShowValues] = useState(true);

    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date().toLocaleTimeString()), 1000);
        return () => clearInterval(timer);
    }, []);

    const chartData = revenueChartData || [];
    const pieData = moduleBreakdown || [{ name: 'No data', value: 0, color: '#94a3b8' }];

    const recentTransactionsColumns = [
        { key: 'user_name', label: 'User', render: (row: any) => <span className="font-medium text-sm">{row.user_name}</span> },
        { key: 'amount', label: 'Amount', render: (row: any) => <span className={`font-mono text-sm ${row.type === 'received' ? 'text-green-600' : 'text-red-600'}`}>{row.type === 'received' ? '+' : '-'}{formatCurrency(row.amount, businessCurrency)}</span> },
        { key: 'created_at', label: 'Time', render: (row: any) => <span className="text-xs text-slate-500">{row.created_at}</span> },
    ];

    const recentUsersColumns = [
        { key: 'name', label: 'Name', render: (row: any) => <span className="font-medium text-sm">{row.name}</span> },
        { key: 'email', label: 'Email', render: (row: any) => <span className="text-sm text-slate-600">{row.email}</span> },
        { key: 'created_at', label: 'Joined', render: (row: any) => <span className="text-xs text-slate-500">{row.created_at}</span> },
    ];

    const recentTicketsColumns = [
        { key: 'user_name', label: 'User', render: (row: any) => <span className="font-medium text-sm">{row.user_name}</span> },
        { key: 'subject', label: 'Subject', render: (row: any) => <span className="text-sm truncate max-w-[150px] inline-block">{row.subject}</span> },
        { key: 'status', label: 'Status', render: (row: any) => <StatusBadge status={row.status === 'closed' ? 'success' : 'warning'} label={row.status} size="sm" /> },
    ];

    const toggleValues = () => setShowValues(!showValues);

    const maskValue = (val: string | number) => showValues ? val : '****';

    return (
        <AdminSidebarLayout 
            title={__('general.admin_platform')}
            header="Admin Dashboard"
            user={auth?.user}
        >
            <div className="space-y-8 pb-10">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider">Overview</span>
                        </div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.admin_dashboard')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.monitor_global_platform_revenue_tenants_and_infrastructure')}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="outline" size="sm" onClick={toggleValues}>
                            {showValues ? 'Hide Values' : 'Show Values'}
                        </Button>
                        <div className="hidden md:flex items-center gap-2">
                            <span className="bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-md border border-blue-100 flex items-center gap-1.5">
                                <DollarSign className="w-3.5 h-3.5" />
                                {businessCurrency}
                            </span>
                            <span className="bg-green-50 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-md border border-green-100 flex items-center gap-1.5">
                                <Database className="w-3.5 h-3.5" />
                                {systemHealth?.database}
                            </span>
                            <span className="bg-slate-100 text-slate-700 text-xs font-semibold px-3 py-1.5 rounded-md border border-slate-200 flex items-center gap-1.5">
                                <Clock className="w-3.5 h-3.5" />
                                {currentTime}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Quick Links */}
                <OperationalCard title={__('general.quick_actions')}>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Link href="/admin/users/create" className="flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100 text-slate-700 p-3 rounded-lg border border-slate-200 transition-colors">
                            <UserPlus className="w-4 h-4" /> <span className="font-medium text-sm">{__('general.add_user')}</span>
                        </Link>
                        <Link href="/admin/projects/create" className="flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100 text-slate-700 p-3 rounded-lg border border-slate-200 transition-colors">
                            <Briefcase className="w-4 h-4" /> <span className="font-medium text-sm">{__('general.new_project')}</span>
                        </Link>
                        <Link href="/admin/calculator" className="flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100 text-slate-700 p-3 rounded-lg border border-slate-200 transition-colors">
                            <Calculator className="w-4 h-4" /> <span className="font-medium text-sm">Calculator</span>
                        </Link>
                        <Link href="/admin/settings" className="flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100 text-slate-700 p-3 rounded-lg border border-slate-200 transition-colors">
                            <Settings className="w-4 h-4" /> <span className="font-medium text-sm">Settings</span>
                        </Link>
                    </div>
                </OperationalCard>

                {/* Performance Overview (Financial) */}
                <div>
                    <h2 className="text-lg font-bold text-slate-900 mb-4">{__('general.performance_overview')}</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        <MetricCard label={__('general.monthly_revenue')} value={maskValue(formatCurrency(stats?.revenueThisMonth, businessCurrency))} icon={DollarSign} />
                        <MetricCard label={__('general.monthly_expenses')} value={maskValue(formatCurrency(stats?.monthlyExpenses, businessCurrency))} icon={ArrowDownRight} />
                        <MetricCard label={__('general.booking_price')} value={maskValue(formatCurrency(stats?.bookingPrice, businessCurrency))} icon={Activity} />
                        <MetricCard label={__('general.hourly_rate')} value={maskValue(formatCurrency(stats?.bookingRatePerHour, businessCurrency))} icon={Clock} />
                        <MetricCard label={__('general.pending_payments')} value={maskValue(formatCurrency(stats?.pendingPayments, businessCurrency))} icon={ArrowUpRight} />
                        <MetricCard label={__('general.total_users')} value={maskValue(stats?.totalUsers)} icon={Users} />
                    </div>
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <OperationalCard title={__('general.revenue_trajectory_12_months')} className="lg:col-span-3">
                        <div className="h-[300px] w-full">
                            {chartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%" minWidth={0} minHeight={300}>
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
                                <EmptyState icon={BarChart3} title={__('general.no_revenue_data_yet')} />
                            )}
                        </div>
                    </OperationalCard>

                    <OperationalCard title={__('general.module_breakdown')} className="lg:col-span-2">
                        <div className="h-[300px] w-full">
                            {pieData.some((d: any) => d.value > 0) ? (
                                <ResponsiveContainer width="100%" height="100%" minWidth={0} minHeight={300}>
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
                                <EmptyState icon={BarChart3} title={__('general.no_module_revenue_yet')} />
                            )}
                        </div>
                    </OperationalCard>
                </div>

                {/* Operational Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <MetricCard label={__('general.total_projects')} value={maskValue(operationalStats?.totalProjects)} icon={Briefcase} />
                    <MetricCard label={__('general.total_tasks')} value={maskValue(operationalStats?.totalTasks)} icon={Activity} />
                    <MetricCard label={__('general.open_tickets')} value={maskValue(operationalStats?.openTickets)} icon={Ticket} />
                    <MetricCard label={__('general.premium_users')} value={maskValue(operationalStats?.premiumUsers)} icon={UserPlus} />
                </div>

                {/* System Health & WhatsApp */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <OperationalCard title={__('general.system_health')}>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 flex items-center gap-3">
                                <Database className="w-8 h-8 text-green-500" />
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">Database</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.database}</p>
                                </div>
                            </div>
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 flex items-center gap-3">
                                <Server className="w-8 h-8 text-blue-500" />
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">{__('general.server_load')}</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.serverLoad}</p>
                                </div>
                            </div>
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 flex items-center gap-3">
                                <HardDrive className="w-8 h-8 text-orange-500" />
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">{__('general.disk_usage')}</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.diskUsage}</p>
                                </div>
                            </div>
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 flex items-center gap-3">
                                <Activity className="w-8 h-8 text-indigo-500" />
                                <div>
                                    <p className="text-xs text-slate-500 font-semibold uppercase">Memory</p>
                                    <p className="text-lg font-bold text-slate-900">{systemHealth?.memoryUsage}</p>
                                </div>
                            </div>
                        </div>
                    </OperationalCard>
                    
                    <OperationalCard title={__('general.whatsapp_services')}>
                        <div className="grid grid-cols-2 gap-4 h-full">
                            <div className="flex flex-col items-center justify-center p-6 bg-green-50 rounded-lg border border-green-100">
                                <h3 className="text-3xl font-bold text-green-600 mb-1">{maskValue(systemHealth?.whatsappUsers)}</h3>
                                <p className="text-sm text-green-800 font-medium">{__('general.active_users')}</p>
                            </div>
                            <div className="flex flex-col items-center justify-center p-6 bg-blue-50 rounded-lg border border-blue-100">
                                <h3 className="text-3xl font-bold text-blue-600 mb-1">{maskValue(formatCurrency(systemHealth?.totalWhatsappBalance, businessCurrency))}</h3>
                                <p className="text-sm text-blue-800 font-medium">{__('general.total_balance')}</p>
                            </div>
                        </div>
                    </OperationalCard>
                </div>

                {/* Recent Activities */}
                <div>
                    <h2 className="text-lg font-bold text-slate-900 mb-4 mt-8">{__('general.recent_activities')}</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <OperationalCard title={__('general.recent_transactions')} noPadding>
                            <DataTable 
                                columns={recentTransactionsColumns}
                                data={recentActivities?.transactions || []}
                                emptyState={<EmptyState icon={Inbox} title={__('general.no_transactions')} />}
                                className="border-0 shadow-none rounded-none"
                            />
                        </OperationalCard>
                        <OperationalCard title={__('general.recent_users')} noPadding>
                            <DataTable 
                                columns={recentUsersColumns}
                                data={recentActivities?.users || []}
                                emptyState={<EmptyState icon={Users} title={__('general.no_users')} />}
                                className="border-0 shadow-none rounded-none"
                            />
                        </OperationalCard>
                        <OperationalCard title={__('general.recent_tickets')} noPadding>
                            <DataTable 
                                columns={recentTicketsColumns}
                                data={recentActivities?.tickets || []}
                                emptyState={<EmptyState icon={Ticket} title={__('general.no_tickets')} />}
                                className="border-0 shadow-none rounded-none"
                            />
                        </OperationalCard>
                    </div>
                </div>

                {/* Business Intelligence */}
                <div>
                    <h2 className="text-lg font-bold text-slate-900 mb-4 mt-8">{__('general.business_intelligence')}</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600"><DollarSign className="w-5 h-5"/></div>
                                <h3 className="font-bold text-slate-800">Financials</h3>
                            </div>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">{__('general.net_profit')}</span>
                                    <span className="font-bold text-green-600">{maskValue(formatCurrency(stats?.revenueThisMonth - stats?.monthlyExpenses, businessCurrency))}</span>
                                </div>
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">{__('general.profit_margin')}</span>
                                    <span className="font-bold text-slate-900">{stats?.monthlyExpenses > 0 ? maskValue(Math.round((stats?.revenueThisMonth / stats?.monthlyExpenses) * 100)) : 0}%</span>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600"><Users className="w-5 h-5"/></div>
                                <h3 className="font-bold text-slate-800">{__('general.user_analytics')}</h3>
                            </div>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">Active (30d)</span>
                                    <span className="font-bold text-slate-900">{maskValue(operationalStats?.activeUsers30d)}</span>
                                </div>
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">Engagement</span>
                                    <span className="font-bold text-slate-900">{stats?.totalUsers > 0 ? maskValue(Math.round((operationalStats?.activeUsers30d / stats?.totalUsers) * 100)) : 0}%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600"><Briefcase className="w-5 h-5"/></div>
                                <h3 className="font-bold text-slate-800">{__('general.project_task')}</h3>
                            </div>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">{__('general.project_completion')}</span>
                                    <span className="font-bold text-slate-900">{operationalStats?.totalProjects > 0 ? maskValue(Math.round((operationalStats?.completedProjects / operationalStats?.totalProjects) * 100)) : 0}%</span>
                                </div>
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">{__('general.task_completion')}</span>
                                    <span className="font-bold text-slate-900">{operationalStats?.totalTasks > 0 ? maskValue(Math.round((operationalStats?.completedTasks / operationalStats?.totalTasks) * 100)) : 0}%</span>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600"><Ticket className="w-5 h-5"/></div>
                                <h3 className="font-bold text-slate-800">{__('general.support_ops')}</h3>
                            </div>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">{__('general.urgent_tickets')}</span>
                                    <span className="font-bold text-red-600">{maskValue(operationalStats?.urgentTickets)}</span>
                                </div>
                                <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span className="text-sm text-slate-600">{__('general.premium_rate')}</span>
                                    <span className="font-bold text-slate-900">{stats?.totalUsers > 0 ? maskValue(Math.round((operationalStats?.premiumUsers / stats?.totalUsers) * 100)) : 0}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
