import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Users,
    Briefcase,
    FileText,
    Activity,
    TrendingDown,
    TrendingUp,
    ArrowUpRight,
    ArrowDownRight,
    Calendar,
    Tag
} from 'lucide-react';
import { MetricCard } from '@/Components/ui/MetricCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Button } from '@/Components/ui/button';
import { formatMoney, cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartsTooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Legend
} from 'recharts';

const PIE_COLORS = ['#10b981', '#ef4444', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#64748b'];

function StatCard({ title, value, icon: Icon, drillTo, colorize }: {
    title: string;
    value: number | string;
    icon: React.ElementType;
    drillTo?: string;
    colorize?: boolean;
}) {
    const numeric = typeof value === 'number' ? value : parseFloat(String(value));
    const colorClass = colorize
        ? isNaN(numeric)
            ? 'text-slate-900'
            : numeric >= 0
            ? 'text-green-600'
            : 'text-red-600'
        : 'text-slate-900';

    const body = (
        <Card className="border-none shadow-sm shadow-slate-200/50 hover:shadow-md transition-shadow">
            <CardContent className="p-6">
                <div className="flex items-center justify-between space-y-0 pb-2">
                    <p className="text-sm font-medium text-slate-500">{title}</p>
                    <div className="p-2 bg-slate-50 rounded-xl">
                        <Icon className="h-4 w-4 text-slate-800" />
                    </div>
                </div>
                <div className={cn('text-3xl font-bold tracking-tight font-mono', colorClass)}>
                    {value}
                </div>
            </CardContent>
        </Card>
    );

    return drillTo ? (
        <Link href={drillTo} className="block focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 rounded-xl">
            {body}
        </Link>
    ) : (
        body
    );
}

function CategoryPieChart({ title, data, currency }: { title: string; data: any[]; currency: string }) {
    const hasData = data && data.length > 0;
    return (
        <Card className="border-none shadow-sm shadow-slate-200/50">
            <CardContent className="p-6">
                <h4 className="text-sm font-semibold text-slate-700 mb-4">{title}</h4>
                <div className="h-[280px] flex items-center justify-center">
                    {hasData ? (
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={data}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={55}
                                    outerRadius={75}
                                    paddingAngle={3}
                                    dataKey="value"
                                >
                                    {data.map((_, index) => (
                                        <Cell key={`cell-${index}`} fill={PIE_COLORS[index % PIE_COLORS.length]} />
                                    ))}
                                </Pie>
                                <RechartsTooltip formatter={(val: any) => [formatMoney(val, currency), '']} />
                                <Legend 
                                    verticalAlign="bottom" 
                                    iconType="circle" 
                                    iconSize={8}
                                    formatter={(value, entry: any) => (
                                        <span className="text-xs text-slate-600 font-mono">
                                            {value}: {formatMoney(entry.payload.value, currency)}
                                        </span>
                                    )} 
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="text-center">
                            <Tag className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                            <p className="text-xs text-slate-400 font-medium">{__('general.no_data_yet') || 'No data recorded'}</p>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Reports({ 
    stats, 
    charts, 
    projects = [], 
    categories = [], 
    filters = {}, 
    hasData = true 
}: { 
    stats: any; 
    charts: any; 
    projects?: any[]; 
    categories?: string[]; 
    filters?: any; 
    hasData?: boolean 
}) {
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [projectId, setProjectId] = useState(filters.project_id || '');
    const [category, setCategory] = useState(filters.category || '');

    const handleApplyFilters = () => {
        router.get(route('admin.business.reports'), {
            from,
            to,
            project_id: projectId,
            category
        }, { preserveState: true });
    };

    const handleResetFilters = () => {
        setFrom('');
        setTo('');
        setProjectId('');
        setCategory('');
        router.get(route('admin.business.reports'), {}, { preserveState: true });
    };

    if (!stats || !hasData) {
        return (
            <AdminSidebarLayout title={__('general.system_reports')} header={__('general.system_reports')}>
                <Head title={__('general.system_reports')} />
                <EmptyState
                    icon={Activity}
                    title={__('general.no_reports_data_yet') || 'No data yet'}
                    description={__('general.reports_will_populate_as_data_is_created') || 'Reports will populate as soon as activity is recorded.'}
                />
            </AdminSidebarLayout>
        );
    }

    const businessCurrency = stats.business_currency_code || 'USD';
    const isPositiveProfit = (stats.net_profit ?? 0) >= 0;

    const formatYAxis = (value: number): string => {
        if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
        if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
        return String(value);
    };

    const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: any[]; label?: any }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-black text-white p-3 rounded-lg border border-slate-850 shadow-xl text-xs">
                    <p className="font-semibold mb-2 border-b border-slate-800 pb-1">{label}</p>
                    {payload.map((entry: any, idx: number) => (
                        <div key={idx} className="flex justify-between items-center gap-4 py-0.5">
                            <span className="text-slate-400 capitalize">{entry.name}:</span>
                            <span className={cn('font-mono font-semibold', entry.name === __('general.profit') && entry.value < 0 ? 'text-red-500' : entry.name === __('general.profit') ? 'text-green-500' : 'text-white')}>
                                {formatMoney(entry.value, businessCurrency)}
                            </span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    const hasFiltersActive = from || to || projectId || category;

    return (
        <AdminSidebarLayout 
            title={__('general.system_reports')} 
            header={__('general.system_reports')}
            actions={
                <Link href={route('admin.reports.balance')}>
                    <Button variant="outline" size="sm" className="gap-2 hover:bg-slate-50 hover:text-black">
                        <Activity className="h-4 w-4 text-slate-800" />
                        <span>{__('general.balance_report') || 'Balance Report'}</span>
                    </Button>
                </Link>
            }
        >
            <Head title={__('general.system_reports')} />

            {/* Filter Bar */}
            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardContent className="p-4 flex flex-col md:flex-row md:items-end gap-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 flex-1">
                        <div>
                            <label className="text-xs font-semibold text-slate-500 block mb-1.5 flex items-center gap-1">
                                <Calendar className="w-3.5 h-3.5" />
                                {__('general.from_date') || 'Start Date'}
                            </label>
                            <input 
                                type="date" 
                                value={from} 
                                onChange={e => setFrom(e.target.value)} 
                                className="w-full text-slate-800 text-sm border border-slate-200 rounded-lg p-2 bg-white focus:outline-none focus:ring-1 focus:ring-slate-900"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold text-slate-500 block mb-1.5 flex items-center gap-1">
                                <Calendar className="w-3.5 h-3.5" />
                                {__('general.to_date') || 'End Date'}
                            </label>
                            <input 
                                type="date" 
                                value={to} 
                                onChange={e => setTo(e.target.value)} 
                                className="w-full text-slate-800 text-sm border border-slate-200 rounded-lg p-2 bg-white focus:outline-none focus:ring-1 focus:ring-slate-900"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold text-slate-500 block mb-1.5">
                                {__('general.project') || 'Project'}
                            </label>
                            <select 
                                value={projectId} 
                                onChange={e => setProjectId(e.target.value)} 
                                className="w-full text-slate-800 text-sm border border-slate-200 rounded-lg p-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-900"
                            >
                                <option value="">{__('general.all_projects') || 'All Projects'}</option>
                                {projects.map((p: any) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="text-xs font-semibold text-slate-500 block mb-1.5">
                                {__('general.category') || 'Category'}
                            </label>
                            <select 
                                value={category} 
                                onChange={e => setCategory(e.target.value)} 
                                className="w-full text-slate-800 text-sm border border-slate-200 rounded-lg p-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-900"
                            >
                                <option value="">{__('general.all_categories') || 'All Categories'}</option>
                                {categories.map((c: string) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 justify-end">
                        <Button onClick={handleApplyFilters} className="bg-slate-900 text-white hover:bg-black h-10 px-4">
                            {__('general.filter') || 'Filter'}
                        </Button>
                        {hasFiltersActive && (
                            <Button variant="outline" onClick={handleResetFilters} className="h-10 px-4">
                                {__('general.reset') || 'Reset'}
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Metrics Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <StatCard 
                    title={__('general.total_users')} 
                    value={stats.total_users ?? 0} 
                    icon={Users} 
                    drillTo={route('admin.users.index', { from: from || undefined, to: to || undefined })} 
                />
                <StatCard 
                    title={__('general.total_projects')} 
                    value={stats.total_projects ?? 0} 
                    icon={Briefcase} 
                    drillTo={route('admin.projects.index', { from: from || undefined, to: to || undefined })} 
                />
                <StatCard 
                    title={__('general.total_invoices')} 
                    value={stats.total_invoices ?? 0} 
                    icon={FileText} 
                    drillTo={route('admin.invoices.index', { project_id: projectId || undefined, from: from || undefined, to: to || undefined })} 
                />
                <StatCard 
                    title={__('general.total_transactions')} 
                    value={stats.total_transactions ?? 0} 
                    icon={Activity} 
                    drillTo={route('admin.finance.index', { project_id: projectId || undefined, category: category || undefined, from: from || undefined, to: to || undefined })} 
                />
            </div>

            {/* Overview Section */}
            <div className="flex items-end justify-between mb-4 mt-8">
                <div>
                    <h3 className="text-lg font-semibold text-slate-900">{__('general.financial_overview') || 'Financial Overview'}</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                        {__('general.all_amounts_in') || 'All amounts in'} {businessCurrency} {hasFiltersActive && `(${__('general.filtered') || 'Filtered'})`}
                    </p>
                </div>
                <Link
                    href={route('admin.finance.report.export', { 
                        type: 'pnl',
                        from: from || undefined,
                        to: to || undefined,
                        project_id: projectId || undefined,
                        category: category || undefined
                    })}
                    target="_blank"
                    rel="noreferrer"
                    className="text-sm text-slate-600 hover:text-black flex items-center gap-1 font-medium"
                >
                    {__('general.export_csv') || 'Export CSV'}
                    <ArrowUpRight className="w-3.5 h-3.5" />
                </Link>
            </div>

            {/* Financial Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <MetricCard
                    label={__('general.lifetime_income') || 'Lifetime Income'}
                    value={formatMoney(stats.lifetime_income ?? 0, businessCurrency)}
                    icon={TrendingUp}
                />
                <MetricCard
                    label={__('general.lifetime_expenses') || 'Lifetime Expenses'}
                    value={formatMoney(stats.lifetime_expenses ?? 0, businessCurrency)}
                    icon={TrendingDown}
                />
                <div className="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-slate-500">{__('general.net_profit') || 'Net Profit'}</p>
                        {isPositiveProfit ? (
                            <ArrowUpRight className="h-4 w-4 text-emerald-500" />
                        ) : (
                            <ArrowDownRight className="h-4 w-4 text-rose-500" />
                        )}
                    </div>
                    <div className={cn(
                        'mt-2 text-2xl font-semibold tracking-tight font-mono',
                        isPositiveProfit ? 'text-green-600' : 'text-red-600',
                    )}>
                        {formatMoney(stats.net_profit ?? 0, businessCurrency)}
                    </div>
                </div>
            </div>

            {/* Charts Section */}
            {charts?.monthly_trends && charts.monthly_trends.length > 0 && (
                <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                    <CardContent className="p-6">
                        <h4 className="text-sm font-semibold text-slate-900 mb-4">{__('general.profit_loss_trends') || 'Income & Expense Trends'}</h4>
                        <div className="h-[350px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={charts.monthly_trends}>
                                    <defs>
                                        <linearGradient id="colorIncome" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                                        </linearGradient>
                                        <linearGradient id="colorCosts" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#ef4444" stopOpacity={0.3}/>
                                            <stop offset="95%" stopColor="#ef4444" stopOpacity={0}/>
                                        </linearGradient>
                                        <linearGradient id="colorProfit" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#6366f1" stopOpacity={0.3}/>
                                            <stop offset="95%" stopColor="#6366f1" stopOpacity={0}/>
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                    <XAxis 
                                        dataKey="name" 
                                        axisLine={false} 
                                        tickLine={false} 
                                        tick={{fontSize: 11, fill: '#64748b'}}
                                        dy={10}
                                    />
                                    <YAxis 
                                        axisLine={false} 
                                        tickLine={false} 
                                        tick={{fontSize: 11, fill: '#64748b'}}
                                        tickFormatter={formatYAxis}
                                        dx={-10}
                                    />
                                    <RechartsTooltip content={<CustomTooltip />} />
                                    <Area 
                                        type="monotone" 
                                        dataKey="income" 
                                        name={__('general.income')}
                                        stroke="#10b981" 
                                        strokeWidth={2}
                                        fillOpacity={1} 
                                        fill="url(#colorIncome)" 
                                    />
                                    <Area 
                                        type="monotone" 
                                        dataKey="costs" 
                                        name={__('general.costs')}
                                        stroke="#ef4444" 
                                        strokeWidth={2}
                                        fillOpacity={1} 
                                        fill="url(#colorCosts)" 
                                    />
                                    <Area 
                                        type="monotone" 
                                        dataKey="profit" 
                                        name={__('general.profit')}
                                        stroke="#6366f1" 
                                        strokeWidth={2.5}
                                        fillOpacity={1} 
                                        fill="url(#colorProfit)" 
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Category Breakdown Charts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <CategoryPieChart 
                    title={__('general.income_by_category') || 'Income by Category'} 
                    data={charts?.income_by_category} 
                    currency={businessCurrency} 
                />
                <CategoryPieChart 
                    title={__('general.expense_by_category') || 'Expenses by Category'} 
                    data={charts?.expenses_by_category} 
                    currency={businessCurrency} 
                />
            </div>
        </AdminSidebarLayout>
    );
}