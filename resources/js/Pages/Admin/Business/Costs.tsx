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
import { Checkbox } from '@/Components/ui/checkbox';
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
    Download,
    Copy,
    Eye,
    RotateCcw,
    Trash2,
    TrendingUp,
    TrendingDown,
    Receipt,
    PieChart as PieIcon,
    X,
    Plus,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
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

export default function Costs() {
    const { entries, stats, filters, options } = usePage<any>().props;

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selected, setSelected] = useState<number[]>([]);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleFilterChange = (key: string, value: string | boolean) => {
        const finalValue = value === ALL_VALUE || value === '' ? '' : value;
        router.get(route('admin.costs.index'), {
            ...(filters || {}),
            [key]: finalValue,
        }, { preserveState: true, preserveScroll: true });
    };

    const clearFilter = (key: string) => {
        const next = { ...(filters || {}) };
        delete next[key];
        router.get(route('admin.costs.index'), next, { preserveState: true, preserveScroll: true });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.costs.index'), {
            ...(filters || {}),
            search: searchTerm,
        }, { preserveState: true });
    };

    const handleSort = (field: string) => {
        const newDir = filters?.sort_by === field
            ? (filters.sort_dir === 'asc' ? 'desc' : 'asc')
            : 'desc';
        router.get(route('admin.costs.index'), {
            ...(filters || {}),
            sort_by: field,
            sort_dir: newDir,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSelectAll = (checked: boolean) => {
        setSelected(checked ? (entries?.data?.map((e: any) => e.id) ?? []) : []);
    };

    const handleSelectOne = (id: number, checked: boolean) => {
        setSelected((prev) => checked ? Array.from(new Set([...prev, id])) : prev.filter((x) => x !== id));
    };

    const handleDelete = () => {
        if (!deleteId) return;
        setIsDeleting(true);
        router.delete(route('admin.costs.delete', deleteId), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteId(null);
                setIsDeleting(false);
            },
            onError: () => setIsDeleting(false),
        });
    };

    const handleBulkDelete = () => {
        if (selected.length === 0) return;
        setIsDeleting(true);
        router.post(route('admin.costs.bulk_delete'), { ids: selected }, {
            preserveScroll: true,
            onSuccess: () => {
                setSelected([]);
                setBulkDeleteOpen(false);
                setIsDeleting(false);
            },
            onError: () => setIsDeleting(false),
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        Object.entries(filters || {}).forEach(([k, v]) => {
            if (v !== '' && v !== null && v !== undefined && k !== 'available_years' && k !== 'available_months') {
                params.set(k, String(v));
            }
        });
        window.location.href = route('admin.costs.export') + '?' + params.toString();
    };

    const handleRestore = (id: number) => {
        router.post(route('admin.costs.restore', id), {}, { preserveScroll: true });
    };

    const formatYAxis = (value: number): string => {
        if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
        if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
        return String(value);
    };

    const currencyFormatter = useMemo(
        () => new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }),
        []
    );

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
    if (filters?.recurring_only) activeFilterPills.push({ key: 'recurring_only', label: 'Type', value: 'Recurring' });
    if (filters?.min_amount) activeFilterPills.push({ key: 'min_amount', label: 'Min', value: filters.min_amount });
    if (filters?.max_amount) activeFilterPills.push({ key: 'max_amount', label: 'Max', value: filters.max_amount });
    if (filters?.with_trashed) activeFilterPills.push({ key: 'with_trashed', label: 'View', value: 'With deleted' });

    const allSelected = entries?.data?.length > 0 && selected.length === entries.data.length;

    return (
        <AdminSidebarLayout
            title={__('general.business_costs')}
            header={__('general.business_costs')}
        >
            <Head title={__('general.business_costs')} />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.monthly_costs')}</p>
                            <div className="p-2 bg-rose-50 rounded-lg">
                                <ArrowDownRight className="h-4 w-4 text-rose-600" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_monthly_costs || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">
                            {stats.entry_count || 0} {__('general.entries_count')} · {__('general.avg')}: {formatCurrency(stats.average_cost || 0, stats.business_currency_code)}
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.ytd_costs')}</p>
                            <div className="p-2 bg-slate-50 rounded-lg">
                                <Receipt className="h-4 w-4 text-slate-700" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.ytd_costs || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">{__('general.year_to_date')}</p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.vs_last_month')}</p>
                            <div className={`p-2 rounded-lg ${(stats.change_percent || 0) <= 0 ? 'bg-emerald-50' : 'bg-rose-50'}`}>
                                {(stats.change_percent || 0) <= 0
                                    ? <TrendingDown className="h-4 w-4 text-emerald-600" />
                                    : <TrendingUp className="h-4 w-4 text-rose-600" />}
                            </div>
                        </div>
                        <div className={`text-2xl font-bold tracking-tight ${(stats.change_percent || 0) <= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                            {(stats.change_percent || 0) > 0 ? '+' : ''}{stats.change_percent || 0}%
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">
                            {__('general.previous')}: {formatCurrency(stats.previous_month_costs || 0, stats.business_currency_code)}
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{__('general.largest_cost')}</p>
                            <div className="p-2 bg-amber-50 rounded-lg">
                                <ArrowUpRight className="h-4 w-4 text-amber-600" />
                            </div>
                        </div>
                        {stats.largest_cost ? (
                            <Link href={route('admin.costs.show', stats.largest_cost.id)} className="block">
                                <div className="text-2xl font-bold text-slate-900 tracking-tight">
                                    {formatCurrency(stats.largest_cost.business_amount, stats.business_currency_code)}
                                </div>
                                <p className="text-xs text-slate-500 mt-1.5 truncate" title={stats.largest_cost.reason}>
                                    {stats.largest_cost.reason}
                                </p>
                            </Link>
                        ) : (
                            <div className="text-2xl font-bold text-slate-300 tracking-tight">—</div>
                        )}
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
                                    router.get(route('admin.costs.index'), { ...(filters || {}), preset: '' }, { preserveState: true, preserveScroll: true });
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
                        <Button
                            type="button"
                            variant={filters?.recurring_only ? 'default' : 'outline'}
                            size="sm"
                            className="h-9"
                            onClick={() => handleFilterChange('recurring_only', !filters?.recurring_only)}
                        >
                            {__('general.recurring')}
                        </Button>
                        <div className="flex items-center gap-1">
                            <Input
                                type="number"
                                placeholder={__('general.min')}
                                className="h-9 w-24 text-sm"
                                value={filters?.min_amount || ''}
                                onChange={(e) => handleFilterChange('min_amount', e.target.value)}
                            />
                            <span className="text-slate-400 text-xs">—</span>
                            <Input
                                type="number"
                                placeholder={__('general.max')}
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
                                onClick={() => router.get(route('admin.costs.index'), { preset: '' }, { preserveState: true, preserveScroll: true })}
                            >
                                <X className="h-3 w-3 me-1" /> Clear all
                            </Button>
                        )}
                    </div>
                    {activeFilterPills.length > 0 && (
                        <div className="flex items-center gap-2 mt-3 flex-wrap">
                            {activeFilterPills.map((p) => (
                                <span key={p.key} className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    <span className="text-slate-500">{p.label}:</span> {p.value}
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
                            {__('general.cost_trends')}
                        </CardTitle>
                        <CardDescription>{__('general.last_12_months')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[260px] mt-2">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={stats.monthly_trends}>
                                    <defs>
                                        <linearGradient id="colorCosts" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#e11d48" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#e11d48" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                    <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                                    <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={formatYAxis} dx={-10} />
                                    <RechartsTooltip content={<CustomTooltip />} />
                                    <Area type="monotone" dataKey="costs" name="Costs" stroke="#e11d48" strokeWidth={3} fillOpacity={1} fill="url(#colorCosts)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <PieIcon className="w-4 h-4 text-slate-900" />
                            {__('general.by_category')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[260px] mt-2">
                            {(stats.category_breakdown?.length ?? 0) > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={stats.category_breakdown} cx="50%" cy="50%" innerRadius={45} outerRadius={80} paddingAngle={2} dataKey="value">
                                            {stats.category_breakdown.map((_: any, idx: number) => (
                                                <Cell key={idx} fill={PIE_COLORS[idx % PIE_COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <RechartsTooltip content={<CustomTooltip />} />
                                        <Legend wrapperStyle={{ fontSize: 11 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-sm text-slate-500">
                                    {__('general.no_data_available')}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {(stats.project_breakdown?.length > 0 || stats.client_breakdown?.length > 0) && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {stats.project_breakdown?.length > 0 && (
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    {__('general.by_project')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[260px] mt-2">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={stats.project_breakdown} layout="vertical" margin={{ left: 60 }}>
                                            <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#f1f5f9" />
                                            <XAxis type="number" hide />
                                            <YAxis type="category" dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} width={120} />
                                            <RechartsTooltip content={<CustomTooltip />} />
                                            <Bar dataKey="value" fill="#3b82f6" radius={[0, 6, 6, 0]} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                    {stats.client_breakdown?.length > 0 && (
                        <Card className="border-none shadow-sm shadow-slate-200/50">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    {__('general.by_client')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[260px] mt-2">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={stats.client_breakdown} layout="vertical" margin={{ left: 60 }}>
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
                    )}
                </div>
            )}

            <Card className="border-none shadow-sm shadow-slate-200/50">
                <CardHeader className="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex-1">
                        <CardTitle className="text-lg font-semibold text-slate-900">{__('general.cost_entries')}</CardTitle>
                        <CardDescription>{__('general.recent_expense_transactions')}</CardDescription>
                    </div>
                    <div className="flex flex-col sm:flex-row items-center w-full sm:w-auto gap-2">
                        {selected.length > 0 && (
                            <Button
                                type="button"
                                size="sm"
                                variant="destructive"
                                className="h-9"
                                onClick={() => setBulkDeleteOpen(true)}
                            >
                                <Trash2 className="h-4 w-4 me-1" />
                                {selected.length} {__('general.selected')}
                            </Button>
                        )}
                        <form onSubmit={handleSearch} className="flex items-center w-full sm:w-auto gap-2">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder={__('general.search_reason')}
                                    className="ps-9 h-9 border-slate-200 focus-visible:ring-rose-500 rounded-lg w-full text-sm"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <Button type="submit" size="sm" variant="secondary" className="h-9">{__('general.search')}</Button>
                        </form>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            className="h-9 w-full sm:w-auto"
                            onClick={handleExport}
                        >
                            <Download className="h-4 w-4 me-1" /> CSV
                        </Button>
                        <Button
                            size="sm"
                            onClick={() => router.visit(route('admin.costs.create'))}
                            className="bg-slate-900 hover:bg-slate-900 text-white w-full sm:w-auto h-9"
                        >
                            <Plus className="h-4 w-4 me-1" /> {__('general.add_cost')}
                        </Button>
                    </div>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50/50 hover:bg-slate-50/50">
                                <TableHead className="w-[40px]">
                                    <Checkbox
                                        checked={allSelected}
                                        onCheckedChange={(v) => handleSelectAll(!!v)}
                                        aria-label="Select all"
                                    />
                                </TableHead>
                                <TableHead className="w-[120px] font-semibold">
                                    <SortHeader field="created_at">{__('general.date')}</SortHeader>
                                </TableHead>
                                <TableHead className="font-semibold">
                                    <SortHeader field="reason">{__('general.reason')}</SortHeader>
                                </TableHead>
                                <TableHead className="font-semibold">{__('general.category')}</TableHead>
                                <TableHead className="font-semibold">{__('general.project_client')}</TableHead>
                                <TableHead className="text-end font-semibold">
                                    <SortHeader field="amount" align="end">{__('general.amount')}</SortHeader>
                                </TableHead>
                                <TableHead className="w-[60px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries?.data?.map((entry: any) => (
                                <TableRow key={entry.id} className={`group hover:bg-slate-50/80 transition-colors ${entry.deleted_at ? 'opacity-60' : ''}`}>
                                    <TableCell>
                                        <Checkbox
                                            checked={selected.includes(entry.id)}
                                            onCheckedChange={(v) => handleSelectOne(entry.id, !!v)}
                                            aria-label={`Select row ${entry.id}`}
                                        />
                                    </TableCell>
                                    <TableCell className="text-xs text-slate-500 whitespace-nowrap">
                                        {new Date(entry.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium text-slate-900 truncate max-w-[260px]" title={entry.reason}>
                                            {entry.title}
                                        </div>
                                        <div className="flex items-center gap-1 mt-1">
                                            {entry.is_recurring && (
                                                entry.recurring_cost_id ? (
                                                    <Link
                                                        href={route('admin.recurring_costs.view', entry.recurring_cost_id)}
                                                        className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                                        title={__('general.view_recurring_source')}
                                                    >
                                                        {__('general.recurring')}
                                                    </Link>
                                                ) : (
                                                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                                        {__('general.recurring')}
                                                    </span>
                                                )
                                            )}
                                            {entry.deleted_at && (
                                                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-50 text-rose-700">
                                                    {__('general.deleted')}
                                                </span>
                                            )}
                                            {entry.is_billable && (
                                                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700">
                                                    {__('general.billable')}
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
                                            <span className="text-slate-400 text-xs">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <div className="font-semibold font-mono tabular-nums text-rose-700">
                                            -{formatCurrency(Math.abs(entry.amount), entry.currency)}
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
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4 text-slate-500" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-[180px]">
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('admin.costs.show', entry.id)} className="flex items-center gap-2 cursor-pointer">
                                                        <Eye className="h-4 w-4" /> {__('general.view')}
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('admin.costs.edit', entry.id)} className="flex items-center gap-2 cursor-pointer">
                                                        {__('general.edit')}
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => router.post(route('admin.costs.duplicate', entry.id))}
                                                    className="flex items-center gap-2 cursor-pointer"
                                                >
                                                    <Copy className="h-4 w-4" /> {__('general.duplicate')}
                                                </DropdownMenuItem>
                                                {entry.deleted_at && (
                                                    <DropdownMenuItem onClick={() => handleRestore(entry.id)} className="flex items-center gap-2">
                                                        <RotateCcw className="h-4 w-4" /> {__('general.restore')}
                                                    </DropdownMenuItem>
                                                )}
                                                {entry.deleted_at ? (
                                                    <DropdownMenuItem
                                                        onClick={() => router.delete(route('admin.costs.delete', entry.id) + '?force=1')}
                                                        className="text-red-600 focus:text-red-600 focus:bg-red-50"
                                                    >
                                                        {__('general.force_delete')}
                                                    </DropdownMenuItem>
                                                ) : (
                                                    <DropdownMenuItem
                                                        onClick={() => setDeleteId(entry.id)}
                                                        className="text-red-600 focus:text-red-600 focus:bg-red-50"
                                                    >
                                                        <Trash2 className="h-4 w-4 me-2" /> {__('general.delete')}
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {entries?.data?.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-32 text-center text-slate-500">
                                        {__('general.no_cost_records_found_for_this_period')}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                {entries?.links && entries.links.length > 3 && (
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3 border-t border-slate-100">
                        <div className="text-xs text-slate-500">
                            {__('general.showing')} {entries.from}–{entries.to} {__('general.of')} {entries.total} {__('general.entries')}
                        </div>
                        <div className="flex items-center gap-1">
                            {entries.links.map((link: any, idx: number) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    preserveScroll
                                    className={`px-3 py-1.5 rounded border text-xs ${link.active ? 'bg-slate-900 text-white border-slate-900 font-semibold' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-200'} ${!link.url ? 'opacity-50 pointer-events-none' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </Card>

            <ConfirmModal
                isOpen={deleteId !== null}
                title={__('general.delete_cost_transaction')}
                description={__('general.delete_cost_transaction_description')}
                confirmLabel={__('general.delete_cost')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteId(null)}
                loading={isDeleting}
            />

            <ConfirmModal
                isOpen={bulkDeleteOpen}
                title={__('general.bulk_delete_costs')}
                description={__('general.bulk_delete_costs_description', { count: selected.length })}
                confirmLabel={__('general.delete')}
                variant="danger"
                onConfirm={handleBulkDelete}
                onCancel={() => setBulkDeleteOpen(false)}
                loading={isDeleting}
            />
        </AdminSidebarLayout>
    );
}
