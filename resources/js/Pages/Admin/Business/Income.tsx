import React, { useState, useMemo } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, usePage, Link } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import {
    ArrowDownRight,
    ArrowUpRight,
    Calendar as CalendarIcon,
    Search,
    MoreHorizontal,
    Filter,
    TrendingUp,
    TrendingDown,
    PieChart as PieIcon,
    X,
    User,
    Building2,
    Tag,
    Activity,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ConfirmModal, PromptModal } from '@/Components/ui/ConfirmModal';
import { formatCurrency } from '@/lib/utils';
import {
    AreaChart,
    Area,
    PieChart,
    Pie,
    Cell,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartsTooltip,
    ResponsiveContainer,
    Legend
} from 'recharts';
import { __ } from '@/lib/i18n';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';

const MONTH_NAMES = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const PIE_COLORS = ['#0f172a', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#64748b'];

const ALL_VALUE = '__all__';

export default function Income() {
    const { entries, stats, filters, options } = usePage<any>().props;

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [reverseId, setReverseId] = useState<number | null>(null);
    const [isReversing, setIsReversing] = useState(false);

    const handleFilterChange = (key: string, value: string | boolean) => {
        const finalValue = value === ALL_VALUE || value === '' ? '' : value;
        router.get(route('admin.income.index'), {
            ...(filters || {}),
            [key]: finalValue,
        }, { preserveState: true, preserveScroll: true });
    };

    const clearFilter = (key: string) => {
        const next = { ...(filters || {}) };
        delete next[key];
        router.get(route('admin.income.index'), next, { preserveState: true, preserveScroll: true });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.income.index'), {
            ...(filters || {}),
            search: searchTerm,
        }, { preserveState: true });
    };

    const handleSort = (field: string) => {
        const newDir = filters?.sort_by === field
            ? (filters.sort_dir === 'asc' ? 'desc' : 'asc')
            : 'desc';
        router.get(route('admin.income.index'), {
            ...(filters || {}),
            sort_by: field,
            sort_dir: newDir,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleDelete = () => {
        if (!deleteId) return;
        setIsDeleting(true);
        router.delete(route('admin.income.delete', deleteId), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteId(null);
                setIsDeleting(false);
            },
            onError: () => setIsDeleting(false),
        });
    };

    const handleReverse = (reason: string) => {
        if (!reverseId) return;
        setIsReversing(true);
        router.post(route('admin.income.reverse', reverseId), { reason }, {
            preserveScroll: true,
            onSuccess: () => {
                setReverseId(null);
                setIsReversing(false);
            },
            onError: () => setIsReversing(false),
        });
    };

    const formatYAxis = (value: number): string => {
        if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
        if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
        return String(value);
    };

    const tooltipFormatter = (value: number) => formatCurrency(value, stats.business_currency_code);

    const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: any[]; label?: any }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-black text-white p-3 rounded-lg border border-slate-850 shadow-xl text-xs">
                    <p className="font-semibold mb-2 border-b border-slate-800 pb-1">{label}</p>
                    {payload.map((entry: any, idx: number) => (
                        <div key={idx} className="flex justify-between items-center gap-4 py-0.5">
                            <span className="text-slate-400 capitalize">{entry.name || entry.payload?.name}:</span>
                            <span className="font-mono font-semibold">
                                {typeof entry.value === 'number' ? tooltipFormatter(entry.value) : entry.value}
                            </span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    const SortHeader = ({ field, children, align = 'start' }: { field: string; children: React.ReactNode; align?: 'start' | 'end' | 'center' }) => (
        <button
            type="button"
            onClick={() => handleSort(field)}
            className={`inline-flex items-center gap-1 font-semibold text-${align} w-full`}
        >
            {children}
            {filters?.sort_by === field && (
                <span className="text-slate-400">{filters.sort_dir === 'asc' ? '▲' : '▼'}</span>
            )}
        </button>
    );

    const preset = filters?.preset || '';
    const activeFilterPills: { key: string; label: string; value: string }[] = [];
    if (preset && preset !== '') activeFilterPills.push({ key: 'preset', label: 'Preset', value: preset });
    if (filters?.project_id) {
        const p = options?.projects?.find((x: any) => String(x.id) === String(filters.project_id));
        if (p) activeFilterPills.push({ key: 'project_id', label: 'Project', value: p.name });
    }
    if (filters?.user_id) {
        const u = options?.users?.find((x: any) => String(x.id) === String(filters.user_id));
        if (u) activeFilterPills.push({ key: 'user_id', label: 'Client', value: u.name });
    }
    if (filters?.currency_id) {
        const c = options?.currencies?.find((x: any) => String(x.id) === String(filters.currency_id));
        if (c) activeFilterPills.push({ key: 'currency_id', label: 'Currency', value: c.code });
    }
    if (filters?.category) activeFilterPills.push({ key: 'category', label: 'Category', value: filters.category });
    if (filters?.min_amount) activeFilterPills.push({ key: 'min_amount', label: 'Min', value: filters.min_amount });
    if (filters?.max_amount) activeFilterPills.push({ key: 'max_amount', label: 'Max', value: filters.max_amount });

    return (
        <AdminSidebarLayout
            title={__('general.business_income')}
            header={__('general.business_income')}
            actions={
                <Link href={route('admin.business.reports')}>
                    <Button variant="outline" size="sm" className="gap-2 hover:bg-slate-50 hover:text-black">
                        <Activity className="h-4 w-4 text-slate-800" />
                        <span>{__('general.business_reports') || 'Business Reports'}</span>
                    </Button>
                </Link>
            }
        >
            <Head title={__('general.business_income')} />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.total_received') || 'Total Received'}</p>
                            <div className="p-2 bg-emerald-50 rounded-lg">
                                <ArrowUpRight className="h-4 w-4 text-emerald-600" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_received || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">{__('general.gross_received_income') || 'Gross received income'}</p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.total_refunded') || 'Total Refunded'}</p>
                            <div className="p-2 bg-rose-50 rounded-lg">
                                <ArrowDownRight className="h-4 w-4 text-rose-600" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_refunded || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">{__('general.refunds_sent') || 'Refunds for the period'}</p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.net_income') || 'Net Income'}</p>
                            <div className="p-2 bg-emerald-50 rounded-lg">
                                <ArrowUpRight className="h-4 w-4 text-emerald-600" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_monthly_income || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">{__('general.net_income_description') || 'Gross income minus refunds/sent'}</p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.vs_last_month') || 'vs Last Month'}</p>
                            <div className={`p-2 rounded-lg ${(stats.income_change_percent || 0) >= 0 ? 'bg-emerald-50' : 'bg-rose-50'}`}>
                                {(stats.income_change_percent || 0) >= 0
                                    ? <TrendingUp className="h-4 w-4 text-emerald-600" />
                                    : <TrendingDown className="h-4 w-4 text-rose-600" />}
                            </div>
                        </div>
                        <div className={`text-2xl font-bold tracking-tight ${(stats.income_change_percent || 0) >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                            {(stats.income_change_percent || 0) >= 0 ? '+' : ''}{stats.income_change_percent || 0}%
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">
                            {__('general.previous') || 'Previous'}: {formatCurrency(stats.previous_month_income || 0, stats.business_currency_code)}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardContent className="p-4">
                    <div className="flex items-center gap-3 flex-wrap">
                        <div className="flex items-center gap-2 text-slate-500">
                            <Filter className="h-4 w-4" />
                            <span className="text-sm font-medium">Filters:</span>
                        </div>
                        <Select
                            value={preset || 'month'}
                            onValueChange={(val) => {
                                if (val === 'month') {
                                    router.get(route('admin.income.index'), { ...(filters || {}), preset: '' }, { preserveState: true, preserveScroll: true });
                                } else {
                                    handleFilterChange('preset', val);
                                }
                            }}
                        >
                            <SelectTrigger className="w-[140px] bg-white h-9 rounded-lg">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="month">This month</SelectItem>
                                <SelectItem value="last_30">Last 30 days</SelectItem>
                                <SelectItem value="last_90">Last 90 days</SelectItem>
                                <SelectItem value="ytd">Year to date</SelectItem>
                                <SelectItem value="all">All time</SelectItem>
                            </SelectContent>
                        </Select>
                        {(!preset || preset === '') && (
                            <>
                                <Select
                                    value={String(filters?.year ?? new Date().getFullYear())}
                                    onValueChange={(val) => { if (val) handleFilterChange('year', val); }}
                                >
                                    <SelectTrigger className="w-[110px] bg-white h-9 rounded-lg">
                                        <SelectValue placeholder="Year" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(filters?.available_years || [new Date().getFullYear()]).map((y: number) => (
                                            <SelectItem key={y} value={String(y)}>{y}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={String(filters?.month ?? new Date().getMonth() + 1)}
                                    onValueChange={(val) => { if (val) handleFilterChange('month', val); }}
                                >
                                    <SelectTrigger className="w-[130px] bg-white h-9 rounded-lg">
                                        <SelectValue placeholder="Month" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(filters?.available_months || [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]).map((m: number) => (
                                            <SelectItem key={m} value={String(m)}>{MONTH_NAMES[m]}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </>
                        )}
                        <PremiumCombobox
                            value={filters?.project_id ? String(filters.project_id) : ''}
                            onChange={(val) => handleFilterChange('project_id', (val as string) || '')}
                            options={(options?.projects || []).map((p: any) => ({ value: String(p.id), label: p.name }))}
                            placeholder="Project"
                        />
                        <PremiumCombobox
                            value={filters?.user_id ? String(filters.user_id) : ''}
                            onChange={(val) => handleFilterChange('user_id', (val as string) || '')}
                            options={(options?.users || []).map((u: any) => ({ value: String(u.id), label: u.name }))}
                            placeholder="Client"
                        />
                        <PremiumCombobox
                            value={filters?.currency_id ? String(filters.currency_id) : ''}
                            onChange={(val) => handleFilterChange('currency_id', (val as string) || '')}
                            options={(options?.currencies || []).map((c: any) => ({ value: String(c.id), label: `${c.code} (${c.symbol})` }))}
                            placeholder="Currency"
                        />
                        <PremiumCombobox
                            value={filters?.category ? String(filters.category) : ''}
                            onChange={(val) => handleFilterChange('category', (val as string) || '')}
                            options={(options?.categories || []).map((c: any) => ({ value: String(c.value), label: c.label }))}
                            placeholder="Category"
                        />
                        <div className="flex items-center gap-1">
                            <Input
                                type="number"
                                placeholder={__('general.min') || 'Min'}
                                className="h-9 w-24 text-sm"
                                value={filters?.min_amount || ''}
                                onChange={(e) => handleFilterChange('min_amount', e.target.value)}
                            />
                            <span className="text-slate-400 text-xs">—</span>
                            <Input
                                type="number"
                                placeholder={__('general.max') || 'Max'}
                                className="h-9 w-24 text-sm"
                                value={filters?.max_amount || ''}
                                onChange={(e) => handleFilterChange('max_amount', e.target.value)}
                            />
                        </div>
                        {activeFilterPills.length > 0 && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-9 ms-auto text-slate-500"
                                onClick={() => router.get(route('admin.income.index'), { preset: '' }, { preserveState: true, preserveScroll: true })}
                            >
                                <X className="h-3 w-3 me-1" /> Clear all
                            </Button>
                        )}
                    </div>
                    {activeFilterPills.length > 0 && (
                        <div className="flex items-center gap-2 mt-3 flex-wrap">
                            {activeFilterPills.map((p) => (
                                <span key={p.key} className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    <span className="text-slate-500">{p.label}:</span> {typeof p.value === 'object' ? JSON.stringify(p.value) : String(p.value)}
                                    <button onClick={() => clearFilter(p.key)} className="ms-1 text-slate-400 hover:text-slate-700">
                                        <X className="h-3 w-3" />
                                    </button>
                                </span>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50 lg:col-span-2">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <CalendarIcon className="w-4 h-4 text-slate-900" />
                            {__('general.income_trends') || 'Income Trends'}
                        </CardTitle>
                        <CardDescription>{__('general.last_12_months') || 'Last 6 Months Income vs Expenses'}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[260px] mt-2">
                            <ResponsiveContainer width="100%" height="100%" minWidth={1}>
                                <AreaChart data={stats.monthly_trends}>
                                    <defs>
                                        <linearGradient id="colorIncome" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                    <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                    <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={formatYAxis} dx={-10} />
                                    <RechartsTooltip content={<CustomTooltip />} />
                                    <Area type="monotone" dataKey="income" name="Income" stroke="#10b981" strokeWidth={3} fillOpacity={1} fill="url(#colorIncome)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <PieIcon className="w-4 h-4 text-slate-900" />
                            {__('general.by_category') || 'By Category'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[260px] mt-2">
                            {(stats.monthly_category_breakdown?.length ?? 0) > 0 ? (
                                <ResponsiveContainer width="100%" height="100%" minWidth={1}>
                                    <PieChart>
                                        <Pie data={stats.monthly_category_breakdown} cx="50%" cy="50%" innerRadius={45} outerRadius={80} paddingAngle={2} dataKey="value">
                                            {stats.monthly_category_breakdown.map((_: any, idx: number) => (
                                                <Cell key={idx} fill={PIE_COLORS[idx % PIE_COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <RechartsTooltip content={<CustomTooltip />} />
                                        <Legend wrapperStyle={{ fontSize: 11 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-sm text-slate-500">
                                    {__('general.no_data_available') || 'No data available'}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {stats.monthly_client_breakdown?.length > 0 && (
                <div className="grid grid-cols-1 gap-6 mb-6">
                    <Card className="border-none shadow-sm shadow-slate-200/50">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <Building2 className="w-4 h-4 text-slate-400" />
                                {__('general.by_client') || 'By Client'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[260px] mt-2">
                                <ResponsiveContainer width="100%" height="100%" minWidth={1}>
                                    <BarChart data={stats.monthly_client_breakdown} layout="vertical" margin={{ left: 60 }}>
                                        <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#f1f5f9" />
                                        <XAxis type="number" hide />
                                        <YAxis type="category" dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} width={120} />
                                        <RechartsTooltip content={<CustomTooltip />} />
                                        <Bar dataKey="value" fill="#10b981" radius={[0, 6, 6, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            <Card className="border-none shadow-sm shadow-slate-200/50">
                <CardHeader className="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex-1">
                        <CardTitle className="text-lg font-semibold text-slate-900">{__('general.income_entries')}</CardTitle>
                        <CardDescription>{__('general.recent_income_transactions')}</CardDescription>
                    </div>
                    <div className="flex flex-col sm:flex-row items-center w-full sm:w-auto gap-2">
                        <form onSubmit={handleSearch} className="flex items-center w-full sm:w-auto gap-2">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder={__('general.search_reason')}
                                    className="ps-9 h-9 border-slate-200 focus-visible:ring-emerald-500 rounded-lg w-full text-sm"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <Button type="submit" size="sm" variant="secondary" className="h-9">{__('general.search')}</Button>
                        </form>
                    </div>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50/50 hover:bg-slate-50/50">
                                <TableHead className="w-[120px] font-semibold">
                                    <SortHeader field="created_at">{__('general.date')}</SortHeader>
                                </TableHead>
                                <TableHead className="font-semibold">
                                    <SortHeader field="reason">{__('general.reason')}</SortHeader>
                                </TableHead>
                                <TableHead className="font-semibold">{__('general.category')}</TableHead>
                                <TableHead className="font-semibold">{__('general.project_client') || 'Project/Client'}</TableHead>
                                <TableHead className="text-end font-semibold">
                                    <SortHeader field="amount" align="end">{__('general.amount')}</SortHeader>
                                </TableHead>
                                <TableHead className="w-[60px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries?.data?.map((entry: any) => (
                                <TableRow key={entry.id} className={`group hover:bg-slate-50/80 transition-colors ${entry.deleted_at ? 'opacity-60' : ''}`}>
                                    <TableCell className="text-xs text-slate-500 whitespace-nowrap">
                                        {new Date(entry.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium text-slate-900 truncate max-w-[260px]" title={entry.reason}>
                                            {entry.title}
                                        </div>
                                        <div className="flex items-center gap-1 mt-1">
                                            {entry.is_recurring && (
                                                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                                    {__('general.recurring')}
                                                </span>
                                            )}
                                            {entry.deleted_at && (
                                                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-50 text-rose-700">
                                                    {__('general.deleted')}
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {entry.category_raw ? (
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-700">
                                                {entry.category.name}
                                            </span>
                                        ) : (
                                            <span className="text-slate-400 text-xs">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {entry.project ? (
                                            <div className="text-sm font-medium text-slate-900 truncate max-w-[180px]" title={entry.project.name}>{entry.project.name}</div>
                                        ) : entry.user ? (
                                            <div className="text-sm text-slate-600 truncate max-w-[180px]" title={entry.user.name}>{entry.user.name}</div>
                                        ) : (
                                            <span className="text-slate-450 text-xs">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <div className={`font-semibold font-mono tabular-nums ${entry.type === 'received' ? 'text-emerald-700' : 'text-slate-900'}`}>
                                            {entry.type === 'received' ? '+' : '-'}{formatCurrency(Math.abs(entry.amount), entry.currency)}
                                        </div>
                                        {entry.currency !== stats.business_currency_code && (
                                            <div className="text-xs text-slate-400 font-mono mt-0.5">
                                                ~ {formatCurrency(Math.abs(entry.business_amount), stats.business_currency_code)}
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4 text-slate-550" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-[180px]">
                                                {!entry.deleted_at && entry.type !== 'refunded' && entry.type !== 'sent' && (
                                                    <DropdownMenuItem
                                                        onClick={() => setReverseId(entry.id)}
                                                        className="focus:bg-slate-50"
                                                    >
                                                        {__('admin.reverse_transaction') || 'Reverse Transaction'}
                                                    </DropdownMenuItem>
                                                )}
                                                {!entry.deleted_at && (
                                                    <DropdownMenuItem
                                                        onClick={() => setDeleteId(entry.id)}
                                                        className="text-red-650 focus:text-red-650 focus:bg-red-50 font-medium"
                                                    >
                                                        {__('general.delete')}
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {entries?.data?.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-32 text-center text-slate-500">
                                        {__('general.no_income_records_found_for_this_period')}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <ConfirmModal
                isOpen={deleteId !== null}
                title={__('general.delete_income_transaction')}
                description="Are you sure you want to delete this transaction? This will recalculate the associated user's ledger. This action cannot be undone."
                confirmLabel={__('general.delete')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteId(null)}
                loading={isDeleting}
            />

            <PromptModal
                isOpen={reverseId !== null}
                title={__('general.reverse_transaction')}
                description="This will create a negative transaction to nullify this entry in the ledger."
                label={__('general.reversal_reason')}
                placeholder={`Reversal of transaction #${reverseId}`}
                confirmLabel="Reverse Transaction"
                onConfirm={handleReverse}
                onCancel={() => setReverseId(null)}
                loading={isReversing}
            />
        </AdminSidebarLayout>
    );
}
