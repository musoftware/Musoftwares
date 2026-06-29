import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, usePage } from '@inertiajs/react';
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
import {
    ArrowUpRight,
    ArrowDownRight,
    ArrowRight,
    Calendar as CalendarIcon,
    Search,
    ChevronUp,
    ChevronDown,
    MoreHorizontal,
    TrendingUp,
    TrendingDown,
    Filter,
    BarChart3,
    PieChart as PieIcon
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
    BarChart,
    Bar,
    ComposedChart,
    Line,
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
import { __ } from '@/lib/i18n';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';

const COLORS = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#64748b', '#14b8a6', '#6366f1'];
const MONTH_NAMES = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

export default function Income() {
    const { entries, stats, filters } = usePage<any>().props;

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [chartType, setChartType] = useState<'combined' | 'income'>('combined');
    const [breakdownView, setBreakdownView] = useState<'client' | 'category'>('client');

    const handleFilterChange = (key: string, value: string) => {
        router.get(route('admin.income.index'), {
            ...(filters || {}),
            [key]: value,
        }, { preserveState: true });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.income.index'), { ...(filters || {}), search: searchTerm }, { preserveState: true });
    };

    const handleSort = (field: string) => {
        let newDir = 'desc';
        if (filters?.sort_by === field) {
            newDir = filters.sort_dir === 'asc' ? 'desc' : 'asc';
        } else {
            newDir = 'asc';
        }
        router.get(route('admin.income.index'), {
            ...(filters || {}),
            sort_by: field,
            sort_dir: newDir
        }, { preserveState: true });
    };

    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const [reverseId, setReverseId] = useState<number | null>(null);
    const [isReversing, setIsReversing] = useState(false);

    const handleDelete = () => {
        if (!deleteId) return;
        setIsDeleting(true);
        router.delete(route('admin.income.delete', deleteId), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteId(null);
                setIsDeleting(false);
            },
            onError: () => {
                setIsDeleting(false);
            }
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
            onError: () => {
                setIsReversing(false);
            }
        });
    };

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
                            <span className="font-mono font-semibold">
                                {formatCurrency(entry.value, stats.business_currency_code)}
                            </span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    const monthlyData = breakdownView === 'client' ? stats.monthly_client_breakdown : stats.monthly_category_breakdown;
    const annualData = breakdownView === 'client' ? stats.annual_client_breakdown : stats.annual_category_breakdown;

    return (
        <AdminSidebarLayout
            title={__('general.business_income')}
            header="Business Income"
        >
            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardContent className="p-4">
                    <div className="flex items-center gap-3 flex-wrap">
                        <div className="flex items-center gap-2 text-slate-500">
                            <Filter className="h-4 w-4" />
                            <span className="text-sm font-medium">Filters:</span>
                        </div>
                        <Select
                            value={String(filters?.year || new Date().getFullYear())}
                            onValueChange={(val) => handleFilterChange('year', val)}
                        >
                            <SelectTrigger className="w-[120px] bg-white h-9 rounded-lg">
                                <SelectValue placeholder="Year" />
                            </SelectTrigger>
                            <SelectContent>
                                {(filters?.available_years || [new Date().getFullYear()]).map((y: number) => (
                                    <SelectItem key={y} value={String(y)}>{y}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={String(filters?.month || new Date().getMonth() + 1)}
                            onValueChange={(val) => handleFilterChange('month', val)}
                        >
                            <SelectTrigger className="w-[140px] bg-white h-9 rounded-lg">
                                <SelectValue placeholder="Month" />
                            </SelectTrigger>
                            <SelectContent>
                                {(filters?.available_months || [1,2,3,4,5,6,7,8,9,10,11,12]).map((m: number) => (
                                    <SelectItem key={m} value={String(m)}>{MONTH_NAMES[m]}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {filters?.year && (
                            <span className="text-xs text-slate-500 ml-auto">
                                Showing: <span className="font-semibold text-slate-700">{MONTH_NAMES[Number(filters.month)]} {filters.year}</span>
                            </span>
                        )}
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Received</p>
                            <div className="p-2 bg-emerald-50 rounded-lg">
                                <ArrowUpRight className="h-4 w-4 text-emerald-600" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_received || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">Gross income for the period</p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Refunded</p>
                            <div className="p-2 bg-rose-50 rounded-lg">
                                <ArrowDownRight className="h-4 w-4 text-rose-600" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_refunded || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">Refunds for the period</p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Net Income</p>
                            <div className="p-2 bg-green-50 rounded-xl">
                                <ArrowUpRight className="h-4 w-4 text-slate-900" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-slate-900 tracking-tight">
                            {formatCurrency(stats.total_monthly_income || 0, stats.business_currency_code)}
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5 font-medium">
                            <span className="text-slate-900 font-semibold bg-green-50 px-1.5 py-0.5 rounded me-1">Current</span>
                            net income
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardContent className="p-5">
                        <div className="flex items-center justify-between pb-2">
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">vs Last Month</p>
                            <div className={`p-2 rounded-lg ${(stats.income_change_percent || 0) >= 0 ? 'bg-emerald-50' : 'bg-rose-50'}`}>
                                {(stats.income_change_percent || 0) >= 0 ? (
                                    <TrendingUp className="h-4 w-4 text-emerald-600" />
                                ) : (
                                    <TrendingDown className="h-4 w-4 text-rose-600" />
                                )}
                            </div>
                        </div>
                        <div className={`text-2xl font-bold tracking-tight ${(stats.income_change_percent || 0) >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                            {(stats.income_change_percent || 0) >= 0 ? '+' : ''}{stats.income_change_percent || 0}%
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5">
                            Previous: {formatCurrency(stats.previous_month_income || 0, stats.business_currency_code)}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                <CardHeader className="pb-2 flex flex-row items-center justify-between">
                    <CardTitle className="text-lg font-semibold flex items-center gap-2">
                        <CalendarIcon className="w-5 h-5 text-slate-900" />
                        {__('general.income_trends')}
                    </CardTitle>
                    <div className="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                        <button
                            onClick={() => setChartType('combined')}
                            className={`text-xs font-medium px-3 py-1.5 rounded-md transition ${chartType === 'combined' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'}`}
                        >
                            Income vs Expenses
                        </button>
                        <button
                            onClick={() => setChartType('income')}
                            className={`text-xs font-medium px-3 py-1.5 rounded-md transition ${chartType === 'income' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'}`}
                        >
                            Income Only
                        </button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="h-[280px] mt-4">
                        <ResponsiveContainer width="100%" height="100%">
                            {chartType === 'combined' ? (
                                <ComposedChart data={stats.monthly_trends}>
                                    <defs>
                                        <linearGradient id="colorIncome" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                    <XAxis
                                        dataKey="name"
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 12, fill: '#64748b' }}
                                        dy={10}
                                    />
                                    <YAxis
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 12, fill: '#64748b' }}
                                        tickFormatter={formatYAxis}
                                        dx={-10}
                                    />
                                    <RechartsTooltip content={<CustomTooltip />} />
                                    <Legend />
                                    <Bar dataKey="income" name="Income" fill="#10b981" radius={[6, 6, 0, 0]} />
                                    <Bar dataKey="expenses" name="Expenses" fill="#ef4444" radius={[6, 6, 0, 0]} />
                                </ComposedChart>
                            ) : (
                                <AreaChart data={stats.monthly_trends}>
                                    <defs>
                                        <linearGradient id="colorIncome" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                                    <XAxis
                                        dataKey="name"
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 12, fill: '#64748b' }}
                                        dy={10}
                                    />
                                    <YAxis
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 12, fill: '#64748b' }}
                                        tickFormatter={formatYAxis}
                                        dx={-10}
                                    />
                                    <RechartsTooltip content={<CustomTooltip />} />
                                    <Area
                                        type="monotone"
                                        dataKey="income"
                                        name="Income"
                                        stroke="#10b981"
                                        strokeWidth={3}
                                        fillOpacity={1}
                                        fill="url(#colorIncome)"
                                    />
                                </AreaChart>
                            )}
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2 flex flex-row items-center justify-between">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <CalendarIcon className="w-4 h-4 text-slate-900" />
                            {breakdownView === 'client' ? __('general.current_month_income_by_client') : 'Current Month by Category'}
                        </CardTitle>
                        <div className="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5">
                            <button
                                onClick={() => setBreakdownView('client')}
                                className={`text-xs font-medium px-2.5 py-1 rounded transition ${breakdownView === 'client' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'}`}
                            >
                                Client
                            </button>
                            <button
                                onClick={() => setBreakdownView('category')}
                                className={`text-xs font-medium px-2.5 py-1 rounded transition ${breakdownView === 'category' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'}`}
                            >
                                Category
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[350px] mt-4">
                            {monthlyData?.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={monthlyData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={50}
                                            outerRadius={90}
                                            paddingAngle={3}
                                            dataKey="value"
                                        >
                                            {monthlyData.map((entry: any, index: number) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <RechartsTooltip content={<CustomTooltip />} />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-sm text-slate-500">
                                    {__('general.no_income_data_for_current_month')}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <CalendarIcon className="w-4 h-4 text-slate-900" />
                            {breakdownView === 'client' ? __('general.current_year_income_by_client') : 'Current Year by Category'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[350px] mt-4">
                            {annualData?.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={annualData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={50}
                                            outerRadius={90}
                                            paddingAngle={3}
                                            dataKey="value"
                                        >
                                            {annualData.map((entry: any, index: number) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <RechartsTooltip content={<CustomTooltip />} />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-full flex items-center justify-center text-sm text-slate-500">
                                    {__('general.no_income_data_for_current_year')}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-none shadow-sm shadow-slate-200/50">
                <CardHeader className="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4">
                    <div className="flex-1">
                        <CardTitle className="text-lg font-semibold text-slate-900">{__('general.income_entries')}</CardTitle>
                        <CardDescription>{__('general.recent_income_transactions')}</CardDescription>
                    </div>
                    <form onSubmit={handleSearch} className="flex items-center w-full sm:w-auto gap-2">
                        <div className="relative w-full sm:w-64">
                            <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-slate-400" />
                            <Input
                                type="text"
                                placeholder={__('general.search_reason')}
                                className="ps-9 h-9 border-slate-200 focus-visible:ring-green-500 rounded-lg w-full text-sm"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">{__('general.search')}</Button>
                    </form>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50/50 hover:bg-slate-50/50">
                                <TableHead className="w-[120px] font-semibold">{__('general.date')}</TableHead>
                                <TableHead className="font-semibold">{__('general.reason')}</TableHead>
                                <TableHead className="font-semibold">Project/Client</TableHead>
                                <TableHead className="text-end font-semibold">{__('general.amount')}</TableHead>
                                <TableHead className="w-[80px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries?.data?.map((entry: any) => (
                                <TableRow key={entry.id} className="group hover:bg-slate-50/80 transition-colors">
                                    <TableCell className="text-xs text-slate-500 whitespace-nowrap">
                                        {new Date(entry.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium text-slate-900">{entry.title}</div>
                                        {entry.is_recurring && (
                                            <span className="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-900">
                                                {__('general.recurring')}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {entry.project ? (
                                            <div className="text-sm font-medium text-slate-900">{entry.project.name}</div>
                                        ) : entry.user ? (
                                            <div className="text-sm text-slate-600">{entry.user.name}</div>
                                        ) : (
                                            <span className="text-slate-400 text-xs">--</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <div className={`font-semibold font-mono tabular-nums ${entry.type === 'received' ? 'text-slate-900' : 'text-slate-900'}`}>
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
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4 text-slate-500" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-[180px]">
                                                <DropdownMenuItem
                                                    onClick={() => setReverseId(entry.id)}
                                                    className="focus:bg-slate-50"
                                                >
                                                    {__('admin.reverse_transaction') || "Reverse Transaction"}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => setDeleteId(entry.id)}
                                                    className="text-red-600 focus:text-red-600 focus:bg-red-50"
                                                >
                                                    {__('general.delete') || "Delete Transaction"}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {entries?.data?.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-32 text-center text-slate-500">
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
                confirmLabel="Delete Transaction"
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
