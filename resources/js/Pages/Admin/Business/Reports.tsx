import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
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
    Tag,
    BarChart2,
    Percent,
    DollarSign,
    Clock,
    Scale,
    Target,
    AlertCircle,
    CheckCircle2,
    Layers,
    Receipt,
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
    Legend,
    BarChart,
    Bar,
    ReferenceLine,
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
            ? 'text-emerald-600'
            : 'text-rose-600'
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
    const [activeTab, setActiveTab] = useState<'overview' | 'hourly' | 'invoices'>('overview');

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
                            <span className={cn('font-mono font-semibold', entry.name === __('general.profit') && entry.value < 0 ? 'text-rose-500' : entry.name === __('general.profit') ? 'text-emerald-500' : 'text-white')}>
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

    // Accounts Receivable Aging chart data
    const arAgingData = [
        { name: __('general.ar_0_30') || '0–30 Days', amount: stats.ar_aging?.['0_30'] || 0, color: '#10b981' },
        { name: __('general.ar_31_60') || '31–60 Days', amount: stats.ar_aging?.['31_60'] || 0, color: '#f59e0b' },
        { name: __('general.ar_61_90') || '61–90 Days', amount: stats.ar_aging?.['61_90'] || 0, color: '#f97316' },
        { name: __('general.ar_90_plus') || '90+ Days', amount: stats.ar_aging?.['90_plus'] || 0, color: '#ef4444' },
    ];

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

            {/* General Metrics Row */}
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

            {/* Navigation Tabs Header */}
            <div className="flex items-center gap-2 border-b border-slate-200 pb-1 mb-6">
                <button
                    onClick={() => setActiveTab('overview')}
                    className={cn(
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-t-lg transition-colors border-b-2 -mb-1',
                        activeTab === 'overview'
                            ? 'border-slate-900 text-slate-900 bg-slate-50/80'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/50'
                    )}
                >
                    <Layers className="w-4 h-4" />
                    <span>{__('general.financial_overview_tab') || 'Financial Overview & Margins'}</span>
                </button>
                <button
                    onClick={() => setActiveTab('hourly')}
                    className={cn(
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-t-lg transition-colors border-b-2 -mb-1',
                        activeTab === 'hourly'
                            ? 'border-slate-900 text-slate-900 bg-slate-50/80'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/50'
                    )}
                >
                    <Clock className="w-4 h-4" />
                    <span>{__('general.hourly_yield_tab') || 'Hourly Yield & Rates'}</span>
                </button>
                <button
                    onClick={() => setActiveTab('invoices')}
                    className={cn(
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-t-lg transition-colors border-b-2 -mb-1',
                        activeTab === 'invoices'
                            ? 'border-slate-900 text-slate-900 bg-slate-50/80'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/50'
                    )}
                >
                    <Receipt className="w-4 h-4" />
                    <span>{__('general.invoices_dso_tab') || 'Invoices & Collection (DSO)'}</span>
                </button>
            </div>

            {/* TAB 1: FINANCIAL OVERVIEW & MARGINS */}
            {activeTab === 'overview' && (
                <div>
                    <div className="flex items-end justify-between mb-4">
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

                    {/* Financial Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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
                                isPositiveProfit ? 'text-emerald-600' : 'text-rose-600',
                            )}>
                                {formatMoney(stats.net_profit ?? 0, businessCurrency)}
                            </div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-slate-500">{__('general.operating_margin') || 'Operating Margin %'}</p>
                                <Percent className="h-4 w-4 text-slate-400" />
                            </div>
                            <div className={cn(
                                'mt-2 text-2xl font-semibold tracking-tight font-mono',
                                (stats.operating_margin_percent ?? 0) >= 0 ? 'text-indigo-600' : 'text-rose-600'
                            )}>
                                {(stats.operating_margin_percent ?? 0) >= 0 ? '+' : ''}{stats.operating_margin_percent ?? 0}%
                            </div>
                            <p className="text-xs text-slate-400 mt-1.5">{__('general.profit_over_income') || 'Net Profit ÷ Income'}</p>
                        </div>
                    </div>

                    {/* Monthly Averages Stat Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <div className="flex items-center justify-between pb-2">
                                    <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.avg_monthly_income') || 'Avg. Monthly Income'}</p>
                                    <div className="p-2 bg-emerald-50 rounded-lg">
                                        <TrendingUp className="h-4 w-4 text-emerald-600" />
                                    </div>
                                </div>
                                <div className="text-2xl font-bold text-slate-900 tracking-tight font-mono">
                                    {formatMoney(stats.avg_monthly_income ?? 0, businessCurrency)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1.5">{__('general.avg_per_month') || 'Average per month'}</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <div className="flex items-center justify-between pb-2">
                                    <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.avg_monthly_costs') || 'Avg. Monthly Costs'}</p>
                                    <div className="p-2 bg-rose-50 rounded-lg">
                                        <TrendingDown className="h-4 w-4 text-rose-600" />
                                    </div>
                                </div>
                                <div className="text-2xl font-bold text-slate-900 tracking-tight font-mono">
                                    {formatMoney(stats.avg_monthly_costs ?? 0, businessCurrency)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1.5">{__('general.avg_per_month') || 'Average per month'}</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <div className="flex items-center justify-between pb-2">
                                    <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.avg_monthly_profit') || 'Avg. Monthly Profit'}</p>
                                    <div className={`p-2 rounded-lg ${(stats.avg_monthly_profit ?? 0) >= 0 ? 'bg-indigo-50' : 'bg-rose-50'}`}>
                                        {(stats.avg_monthly_profit ?? 0) >= 0
                                            ? <ArrowUpRight className="h-4 w-4 text-indigo-600" />
                                            : <ArrowDownRight className="h-4 w-4 text-rose-600" />}
                                    </div>
                                </div>
                                <div className={cn('text-2xl font-bold tracking-tight font-mono', (stats.avg_monthly_profit ?? 0) >= 0 ? 'text-indigo-600' : 'text-rose-600')}>
                                    {formatMoney(stats.avg_monthly_profit ?? 0, businessCurrency)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1.5">{__('general.avg_per_month') || 'Average per month'}</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <div className="flex items-center justify-between pb-2">
                                    <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.monthly_break_even') || 'Monthly Break-Even'}</p>
                                    <div className="p-2 bg-amber-50 rounded-lg">
                                        <Scale className="h-4 w-4 text-amber-600" />
                                    </div>
                                </div>
                                <div className="text-2xl font-bold text-amber-600 tracking-tight font-mono">
                                    {formatMoney(stats.monthly_break_even_revenue ?? 0, businessCurrency)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1.5">{__('general.min_monthly_revenue') || 'Required monthly revenue'}</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Combined Trends Area Chart */}
                    {charts?.monthly_trends && charts.monthly_trends.length > 0 && (
                        <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                            <CardContent className="p-6">
                                <h4 className="text-sm font-semibold text-slate-900 mb-1">{__('general.profit_loss_trends') || 'Income & Expense Trends'}</h4>
                                <p className="text-xs text-slate-500 mb-4">{__('general.dashed_lines_show_averages') || 'Dashed lines show monthly averages'}</p>
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
                                            <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fontSize: 11, fill: '#64748b'}} dy={10} />
                                            <YAxis axisLine={false} tickLine={false} tick={{fontSize: 11, fill: '#64748b'}} tickFormatter={formatYAxis} dx={-10} />
                                            <RechartsTooltip content={<CustomTooltip />} />
                                            {(stats.avg_monthly_income ?? 0) > 0 && (
                                                <ReferenceLine y={stats.avg_monthly_income} stroke="#10b981" strokeDasharray="6 3" strokeWidth={1.5} label={{ value: `Avg Income`, position: 'insideTopRight', fontSize: 10, fill: '#10b981' }} />
                                            )}
                                            {(stats.avg_monthly_costs ?? 0) > 0 && (
                                                <ReferenceLine y={stats.avg_monthly_costs} stroke="#ef4444" strokeDasharray="6 3" strokeWidth={1.5} label={{ value: `Avg Costs`, position: 'insideTopRight', fontSize: 10, fill: '#ef4444' }} />
                                            )}
                                            <ReferenceLine y={stats.avg_monthly_profit ?? 0} stroke="#6366f1" strokeDasharray="6 3" strokeWidth={1.5} label={{ value: `Avg Profit`, position: 'insideTopRight', fontSize: 10, fill: '#6366f1' }} />
                                            <Area type="monotone" dataKey="income" name={__('general.income')} stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#colorIncome)" />
                                            <Area type="monotone" dataKey="costs" name={__('general.costs')} stroke="#ef4444" strokeWidth={2} fillOpacity={1} fill="url(#colorCosts)" />
                                            <Area type="monotone" dataKey="profit" name={__('general.profit')} stroke="#6366f1" strokeWidth={2.5} fillOpacity={1} fill="url(#colorProfit)" />
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
                </div>
            )}

            {/* TAB 2: HOURLY YIELD & RATE ANALYTICS (EHR) */}
            {activeTab === 'hourly' && (
                <div>
                    <div className="flex items-end justify-between mb-4">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                                <Clock className="w-5 h-5 text-indigo-600" />
                                {__('general.hourly_yield_tab') || 'Hourly Yield & Rates (EHR)'}
                            </h3>
                            <p className="text-xs text-slate-500 mt-0.5">
                                {__('general.academic_hourly_rate_calculations') || 'Academic yield analysis based on recorded timer sessions'}
                            </p>
                        </div>
                    </div>

                    {/* Hourly Yield Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.total_worked_hours') || 'Worked Hours'}</p>
                                <div className="text-2xl font-bold text-slate-900 tracking-tight font-mono mt-2">
                                    {stats.total_worked_hours ?? 0} <span className="text-xs text-slate-400 font-normal">hrs</span>
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.from_timer_logs') || 'Logged in timers'}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50 border-s-4 border-s-emerald-500">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-emerald-700 uppercase tracking-wide">{__('general.effective_hourly_rate') || 'Effective Rate (EHR)'}</p>
                                <div className="text-2xl font-bold text-emerald-700 tracking-tight font-mono mt-2">
                                    {formatMoney(stats.effective_hourly_rate ?? 0, businessCurrency)}<span className="text-xs text-slate-500 font-normal">/hr</span>
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.realized_income_per_hr') || 'Net Income ÷ Worked Hrs'}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50 border-s-4 border-s-rose-500">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-rose-700 uppercase tracking-wide">{__('general.cost_per_worked_hour') || 'Cost Per Hour'}</p>
                                <div className="text-2xl font-bold text-rose-700 tracking-tight font-mono mt-2">
                                    {formatMoney(stats.cost_per_worked_hour ?? 0, businessCurrency)}<span className="text-xs text-slate-500 font-normal">/hr</span>
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.operating_cost_per_hr') || 'Operating Costs ÷ Worked Hrs'}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50 border-s-4 border-s-blue-500">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-blue-700 uppercase tracking-wide">{__('general.market_hourly_rate') || 'Market Rate'}</p>
                                <div className="text-2xl font-bold text-blue-700 tracking-tight font-mono mt-2">
                                    {formatMoney(stats.market_hourly_rate ?? 0, businessCurrency)}<span className="text-xs text-slate-500 font-normal">/hr</span>
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.system_benchmark_rate') || 'System standard benchmark'}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.rate_variance') || 'Rate Variance'}</p>
                                <div className={cn(
                                    'text-2xl font-bold tracking-tight font-mono mt-2',
                                    (stats.rate_variance ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600'
                                )}>
                                    {(stats.rate_variance ?? 0) >= 0 ? '+' : ''}{formatMoney(stats.rate_variance ?? 0, businessCurrency)}<span className="text-xs font-normal text-slate-500">/hr</span>
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.ehr_vs_market') || 'EHR vs Market Rate'}</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Monthly Profit Margin Bar Chart */}
                    <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                        <CardContent className="p-6">
                            <div className="flex items-start justify-between mb-1">
                                <div>
                                    <h4 className="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                        <BarChart2 className="w-4 h-4 text-slate-500" />
                                        {__('general.monthly_profit_margin') || 'Monthly Profit Margin %'}
                                    </h4>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        {__('general.profit_margin_formula') || 'Profit ÷ Income × 100'}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <div className={cn(
                                        'text-sm font-semibold font-mono',
                                        (stats.avg_profit_margin ?? 0) >= 0 ? 'text-amber-600' : 'text-rose-600'
                                    )}>
                                        {__('general.avg') || 'Avg'}: {stats.avg_profit_margin ?? 0}%
                                    </div>
                                </div>
                            </div>
                            <div className="h-[280px] mt-4">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={charts.monthly_trends} barSize={28}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                        <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} dy={8} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} tickFormatter={(v) => `${v}%`} dx={-10} />
                                        <RechartsTooltip
                                            content={({ active, payload, label }) => {
                                                if (active && payload && payload.length) {
                                                    const val = payload[0].value as number;
                                                    return (
                                                        <div className="bg-black text-white p-3 rounded-lg border border-slate-800 shadow-xl text-xs">
                                                            <p className="font-semibold mb-1 border-b border-slate-700 pb-1">{label}</p>
                                                            <div className="flex justify-between items-center gap-4">
                                                                <span className="text-slate-400">{__('general.profit_margin') || 'Profit Margin'}:</span>
                                                                <span className={cn('font-mono font-semibold', val >= 0 ? 'text-amber-400' : 'text-rose-400')}>
                                                                    {val}%
                                                                </span>
                                                            </div>
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            }}
                                        />
                                        <ReferenceLine y={stats.avg_profit_margin ?? 0} stroke="#f59e0b" strokeDasharray="6 3" strokeWidth={1.5} label={{ value: `Avg ${stats.avg_profit_margin ?? 0}%`, position: 'insideTopRight', fontSize: 10, fill: '#f59e0b' }} />
                                        <ReferenceLine y={0} stroke="#e2e8f0" strokeWidth={1} />
                                        <Bar dataKey="profit_margin" name={__('general.profit_margin') || 'Profit Margin %'} radius={[4, 4, 0, 0]}>
                                            {charts.monthly_trends.map((entry: any, index: number) => (
                                                <Cell key={`cell-${index}`} fill={entry.profit_margin >= 0 ? '#10b981' : '#ef4444'} fillOpacity={0.85} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* TAB 3: INVOICES & DSO COLLECTION */}
            {activeTab === 'invoices' && (
                <div>
                    <div className="flex items-end justify-between mb-4">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                                <Receipt className="w-5 h-5 text-emerald-600" />
                                {__('general.invoices_dso_tab') || 'Invoices & Collection Analytics (DSO)'}
                            </h3>
                            <p className="text-xs text-slate-500 mt-0.5">
                                {__('general.accounts_receivable_and_dso_metrics') || 'Days Sales Outstanding and Accounts Receivable Aging analysis'}
                            </p>
                        </div>
                    </div>

                    {/* DSO & Invoice Summary Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <Card className="border-none shadow-sm shadow-slate-200/50 border-s-4 border-s-indigo-500">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-indigo-700 uppercase tracking-wide">{__('general.days_sales_outstanding') || 'DSO (Collection Speed)'}</p>
                                <div className="text-3xl font-bold text-indigo-700 tracking-tight font-mono mt-2">
                                    {stats.dso_days ?? 0} <span className="text-xs text-slate-400 font-normal">days</span>
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.avg_days_to_collect') || 'Average days to collect invoice payment'}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50 border-s-4 border-s-emerald-500">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-emerald-700 uppercase tracking-wide">{__('general.collection_realization_rate') || 'Collection Rate %'}</p>
                                <div className="text-3xl font-bold text-emerald-700 tracking-tight font-mono mt-2">
                                    {stats.collection_rate_percent ?? 0}%
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.paid_vs_invoiced') || 'Paid Invoices ÷ Total Invoiced Amount'}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.total_invoiced') || 'Total Invoiced'}</p>
                                <div className="text-2xl font-bold text-slate-900 tracking-tight font-mono mt-2">
                                    {formatMoney(stats.total_invoiced_amount ?? 0, businessCurrency)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.paid_so_far') || 'Paid'}: {formatMoney(stats.total_paid_invoices ?? 0, businessCurrency)}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm shadow-slate-200/50 border-s-4 border-s-rose-500">
                            <CardContent className="p-5">
                                <p className="text-xs font-medium text-rose-700 uppercase tracking-wide">{__('general.total_unpaid') || 'Unpaid Receivables'}</p>
                                <div className="text-2xl font-bold text-rose-700 tracking-tight font-mono mt-2">
                                    {formatMoney(stats.total_unpaid_invoices ?? 0, businessCurrency)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1">{__('general.awaiting_collection') || 'Awaiting collection'}</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Accounts Receivable Aging Breakdown */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <Card className="border-none shadow-sm shadow-slate-200/50 lg:col-span-2">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 text-amber-500" />
                                    {__('general.ar_aging_breakdown') || 'Accounts Receivable Aging Breakdown'}
                                </CardTitle>
                                <CardDescription>{__('general.unpaid_invoices_grouped_by_days') || 'Unpaid balance categorized by days overdue'}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[260px] mt-2">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={arAgingData} barSize={36}>
                                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                            <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} />
                                            <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} tickFormatter={formatYAxis} />
                                            <RechartsTooltip formatter={(val: any) => [formatMoney(val, businessCurrency), __('general.amount') || 'Amount']} />
                                            <Bar dataKey="amount" radius={[4, 4, 0, 0]}>
                                                {arAgingData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Bar>
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        {/* AR Aging Summary Cards */}
                        <Card className="border-none shadow-sm shadow-slate-200/50 flex flex-col justify-between">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base font-semibold text-slate-900">{__('general.aging_categories') || 'Aging Details'}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 flex-1 flex flex-col justify-center">
                                <div className="flex items-center justify-between p-3 rounded-lg bg-emerald-50/50 border border-emerald-100">
                                    <span className="text-xs font-medium text-emerald-800">{__('general.ar_0_30') || '0–30 Days'}</span>
                                    <span className="text-sm font-bold font-mono text-emerald-700">{formatMoney(stats.ar_aging?.['0_30'] || 0, businessCurrency)}</span>
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg bg-amber-50/50 border border-amber-100">
                                    <span className="text-xs font-medium text-amber-800">{__('general.ar_31_60') || '31–60 Days'}</span>
                                    <span className="text-sm font-bold font-mono text-amber-700">{formatMoney(stats.ar_aging?.['31_60'] || 0, businessCurrency)}</span>
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg bg-orange-50/50 border border-orange-100">
                                    <span className="text-xs font-medium text-orange-800">{__('general.ar_61_90') || '61–90 Days'}</span>
                                    <span className="text-sm font-bold font-mono text-orange-700">{formatMoney(stats.ar_aging?.['61_90'] || 0, businessCurrency)}</span>
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg bg-rose-50/50 border border-rose-100">
                                    <span className="text-xs font-medium text-rose-800">{__('general.ar_90_plus') || '90+ Days'}</span>
                                    <span className="text-sm font-bold font-mono text-rose-700">{formatMoney(stats.ar_aging?.['90_plus'] || 0, businessCurrency)}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}