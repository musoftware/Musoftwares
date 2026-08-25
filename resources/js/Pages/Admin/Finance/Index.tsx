import React, { useState, useMemo, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
    Trash2, Edit, Plus, DollarSign, TrendingDown, TrendingUp, Users, CheckCircle2, AlertCircle, Clock,
    Search, X, ChevronUp, ChevronDown, Eye, ExternalLink, FileText, Layers, Calendar, RefreshCw,
    ChevronLeft, ChevronRight, CalendarDays, ArrowUpRight, ArrowDownRight, Download, Info,
} from 'lucide-react';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday, addMonths, subMonths, startOfWeek, endOfWeek } from 'date-fns';
import { formatMoney } from '@/lib/utils';
import {
    AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell,
} from 'recharts';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { toast } from 'sonner';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { __ } from '@/lib/i18n';

declare const route: any;

const COLORS = ['#09090b', '#27272a', '#52525b', '#71717a', '#a1a1aa', '#d4d4d8', '#e4e4e7'];

function formatYAxis(value: number): string {
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
    return String(value);
}

interface StatBadgeProps { status: string }
const StatBadge: React.FC<StatBadgeProps> = ({ status }) => {
    switch (status) {
        case 'completed':
            return <span className="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded text-xs font-medium inline-flex items-center w-fit"><CheckCircle2 className="w-3 h-3 me-1" /> {__('general.completed')}</span>;
        case 'pending':
            return <span className="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded text-xs font-medium inline-flex items-center w-fit"><Clock className="w-3 h-3 me-1" /> {__('general.pending')}</span>;
        case 'overdue':
            return <span className="bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded text-xs font-medium inline-flex items-center w-fit"><AlertCircle className="w-3 h-3 me-1" /> {__('general.overdue')}</span>;
        default:
            return null;
    }
};

function getStatusFromDate(due: string | null | undefined, status: string): string {
    if (status && status !== 'pending') return status;
    if (!due) return 'pending';
    return new Date(due) < new Date(new Date().toDateString()) ? 'overdue' : 'pending';
}

export default function Index({
    entries, categories, users, currentTab, stats, all_currencies, filters,
    calendarEvents = {}, year = new Date().getFullYear(), month = new Date().getMonth() + 1,
}: any) {
    const { errors } = usePage().props as any;
    const categoriesList = Array.isArray(categories) ? categories : categories ? Object.values(categories) : [];
    const currenciesList = Array.isArray(all_currencies) ? all_currencies : all_currencies ? Object.values(all_currencies) : [];

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategoryFilter, setSelectedCategoryFilter] = useState(filters.category || '');
    const [selectedStatusFilter, setSelectedStatusFilter] = useState(filters.status || '');
    const [selectedUserFilter, setSelectedUserFilter] = useState(filters.user_id || '');

    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [createCategoryOption, setCreateCategoryOption] = useState(categoriesList[0]?.id || 'custom');

    const defaultCurrencyId = currenciesList.find((c: any) => c.currency === stats.business_currency_code)?.id || currenciesList[0]?.id || '';

    const [newEntry, setNewEntry] = useState({
        title: '',
        amount: '',
        category_id: categoriesList[0]?.id || '',
        user_id: '',
        currency_id: defaultCurrencyId,
        is_recurring: false,
        recurrence_interval: 'month',
        status: 'completed',
        due_date: '',
        transaction_date: new Date().toISOString().slice(0, 10),
        type: currentTab === 'salaries' ? 'salary' : currentTab === 'income' ? 'income' : 'expense',
    });

    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editCategoryOption, setEditCategoryOption] = useState('custom');
    const [editingEntry, setEditingEntry] = useState<any>({
        id: '', title: '', amount: '', category_id: '', user_id: '', currency_id: '',
        is_recurring: false, recurrence_interval: 'month', status: 'completed', due_date: '',
        transaction_date: new Date().toISOString().slice(0, 10), type: '',
    });

    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [selectedDetailEntry, setSelectedDetailEntry] = useState<any>(null);
    const [pendingDelete, setPendingDelete] = useState<any | null>(null);

    const openDetails = (entry: any) => {
        setSelectedDetailEntry(entry);
        setIsDetailOpen(true);
    };

    const handleTabChange = (tab: string) => {
        router.get(route('admin.finance.index'), { tab }, { preserveState: false });
    };

    const handlePrevMonth = () => {
        const d = new Date(year, month - 1, 1);
        const prev = subMonths(d, 1);
        router.get(route('admin.finance.index'), { tab: 'calendar', year: prev.getFullYear(), month: prev.getMonth() + 1 }, { preserveState: true });
    };
    const handleNextMonth = () => {
        const d = new Date(year, month - 1, 1);
        const next = addMonths(d, 1);
        router.get(route('admin.finance.index'), { tab: 'calendar', year: next.getFullYear(), month: next.getMonth() + 1 }, { preserveState: true });
    };
    const handleGoToToday = () => {
        const today = new Date();
        router.get(route('admin.finance.index'), { tab: 'calendar', year: today.getFullYear(), month: today.getMonth() + 1 }, { preserveState: true });
    };
    const handleDayClick = (day: Date) => {
        const formattedDate = format(day, 'yyyy-MM-dd');
        setNewEntry((prev) => ({ ...prev, transaction_date: formattedDate }));
        setIsCreateOpen(true);
    };

    const applyFilters = () => {
        router.get(route('admin.finance.index'), {
            ...filters, tab: currentTab, search: searchTerm, category: selectedCategoryFilter,
            status: selectedStatusFilter, user_id: selectedUserFilter,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setSearchTerm(''); setSelectedCategoryFilter(''); setSelectedStatusFilter(''); setSelectedUserFilter('');
        router.get(route('admin.finance.index'), { tab: currentTab });
    };

    const handleSort = (field: string) => {
        const newDir = filters.sort_by === field ? (filters.sort_dir === 'asc' ? 'desc' : 'asc') : 'asc';
        router.get(route('admin.finance.index'), {
            ...filters, tab: currentTab, sort_by: field, sort_dir: newDir,
        }, { preserveState: true });
    };

    const renderSortIcon = (field: string) => {
        if (filters.sort_by !== field) return null;
        return filters.sort_dir === 'asc'
            ? <ChevronUp className="w-3.5 h-3.5 ms-1 inline" />
            : <ChevronDown className="w-3.5 h-3.5 ms-1 inline" />;
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.finance.store'), {
            ...newEntry,
            type: currentTab === 'salaries' ? 'salary' : currentTab === 'income' ? 'income' : 'expense',
        }, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewEntry({
                    title: '', amount: '', category_id: categoriesList[0]?.id || '', user_id: '',
                    currency_id: defaultCurrencyId, is_recurring: false, recurrence_interval: 'month',
                    status: 'completed', due_date: '', transaction_date: new Date().toISOString().slice(0, 10),
                    type: currentTab === 'salaries' ? 'salary' : currentTab === 'income' ? 'income' : 'expense',
                });
                setCreateCategoryOption(categoriesList[0]?.id || 'custom');
                toast.success(__('general.created') || 'Created');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const openEdit = (entry: any) => {
        const matchingCategory = categoriesList.find((c: any) => c.name.toLowerCase() === entry.category?.name?.toLowerCase());
        setEditCategoryOption(matchingCategory ? matchingCategory.id : 'custom');
        setEditingEntry({
            id: entry.id, title: entry.title, amount: entry.amount,
            category_id: matchingCategory ? matchingCategory.id : entry.category?.name || '',
            user_id: entry.user ? entry.user.id : '',
            currency_id: entry.currency_id || defaultCurrencyId,
            is_recurring: entry.is_recurring, recurrence_interval: 'month',
            status: entry.status || 'completed',
            due_date: entry.next_due_date || '',
            transaction_date: entry.created_at ? new Date(entry.created_at).toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10),
            type: entry.type || (currentTab === 'salaries' ? 'salary' : currentTab === 'income' ? 'income' : 'expense'),
        });
        setIsEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(route('admin.finance.update', editingEntry.id), editingEntry, {
            onSuccess: () => {
                setIsEditOpen(false);
                toast.success(__('general.updated') || 'Updated');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const handleDelete = () => {
        if (!pendingDelete) return;
        const entryType = currentTab === 'income' ? 'income' : currentTab === 'salaries' ? 'salary' : 'expense';
        router.delete(route('admin.finance.destroy', { entry: pendingDelete, type: entryType }), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.deleted') || 'Deleted');
                setPendingDelete(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingDelete(null);
            },
        });
    };

    const handleMarkPaid = (id: any, fromDetails = false) => {
        const entryType = currentTab === 'income' ? 'income' : currentTab === 'salaries' ? 'salary' : 'expense';
        router.post(route('admin.finance.mark-paid', { entry: id, type: entryType }), {}, {
            onSuccess: () => {
                if (fromDetails && selectedDetailEntry) {
                    setSelectedDetailEntry((prev: any) => prev ? { ...prev, status: 'completed' } : null);
                }
                toast.success(__('general.marked_paid') || 'Marked as paid');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const categoryAllocationData = useMemo(() => {
        if (currentTab === 'income') return stats.income_categories || [];
        if (currentTab === 'salaries') {
            const map: Record<string, number> = {};
            (entries.data ?? []).forEach((e: any) => {
                if (e.user) {
                    map[e.user.name] = (map[e.user.name] || 0) + parseFloat(e.amount);
                }
            });
            return Object.keys(map).map((name) => ({ name, value: map[name] })).sort((a, b) => b.value - a.value);
        }
        return stats.expense_categories || [];
    }, [currentTab, stats, entries.data]);

    const statusDistributionData = stats.status_distribution || [];

    // Locale-aware week start (default Monday for en, Saturday for ar)
    const weekStartsOn: 0 | 1 | 2 | 3 | 4 | 5 | 6 = (() => {
        try {
            const locale = typeof document !== 'undefined' ? document.documentElement.lang : 'en';
            if (locale.startsWith('ar')) return 6;
            if (locale.startsWith('en') || locale.startsWith('fr') || locale.startsWith('de') || locale.startsWith('es') || locale.startsWith('it') || locale.startsWith('pt') || locale.startsWith('ru') || locale.startsWith('zh')) return 1;
            return 0;
        } catch {
            return 1;
        }
    })();

    const entriesList = entries?.data ?? [];
    const hasEntries = entriesList.length > 0;

    return (
        <AdminSidebarLayout title={__('general.financial_operations')} header={__('general.financial_operations')}>
            <Head title={__('general.admin_financial_ledger')} />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full me-4 text-slate-800 border">
                        <TrendingUp className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.net_revenue')}</p>
                        <h3 className="text-2xl font-bold text-slate-900 font-mono">{formatMoney(stats.total_monthly_income ?? 0, stats.business_currency_code)}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full me-4 text-slate-800 border">
                        <TrendingDown className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.monthly_expenses')}</p>
                        <h3 className="text-2xl font-bold text-slate-900 font-mono">{formatMoney(stats.total_monthly_expenses ?? 0, stats.business_currency_code)}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full me-4 text-slate-800 border">
                        <Users className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.monthly_payroll')}</p>
                        <h3 className="text-2xl font-bold text-slate-900 font-mono">{formatMoney(stats.total_monthly_salaries ?? 0, stats.business_currency_code)}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className={((stats.total_monthly_net_profit ?? 0) >= 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-rose-50 border-rose-100') + ' p-4 rounded-full me-4 border'}>
                        {((stats.total_monthly_net_profit ?? 0) >= 0)
                            ? <ArrowUpRight className="w-6 h-6 text-emerald-600" />
                            : <ArrowDownRight className="w-6 h-6 text-rose-600" />}
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.net_profit')}</p>
                        <h3 className={'text-2xl font-bold font-mono ' + ((stats.total_monthly_net_profit ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600')}>
                            {formatMoney(stats.total_monthly_net_profit ?? 0, stats.business_currency_code)}
                        </h3>
                    </div>
                </div>
            </div>

            <div className="flex overflow-x-auto whitespace-nowrap border-b border-gray-200 mb-6 pb-1">
                {[
                    { key: 'expenses', label: __('general.costs_expenses') },
                    { key: 'income', label: __('general.income_streams') },
                    { key: 'projects', label: __('general.project_profitability') },
                    { key: 'budgets', label: __('general.budgets') },
                    { key: 'salaries', label: __('general.employee_payroll') },
                    { key: 'calendar', label: __('general.financial_calendar') },
                ].map((t) => (
                    <button
                        key={t.key}
                        onClick={() => handleTabChange(t.key)}
                        className={`py-3 px-6 font-medium text-sm border-b-2 transition-colors ${
                            currentTab === t.key
                                ? 'border-black text-black font-semibold'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-200'
                        }`}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {currentTab !== 'calendar' && (stats.monthly_trends?.length ?? 0) > 0 && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div className="lg:col-span-2 bg-white p-6 rounded-xl border shadow-sm">
                        <div className="mb-4">
                            <h3 className="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-1">
                                {__('general.financial_trends') || 'Financial Trends (Last 6 Months)'}
                            </h3>
                            <p className="text-xs text-gray-500 font-normal">
                                {__('general.historical_comparison') || 'Historical comparison in'} {stats.business_currency_code}
                            </p>
                        </div>
                        <div className="h-[260px] w-full">
                            <ResponsiveContainer width="100%" height="100%" minWidth={1}>
                                <AreaChart data={stats.monthly_trends || []} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="ci" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.1} />
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="ce" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#ef4444" stopOpacity={0.1} />
                                            <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="cn" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#8b5cf6" stopOpacity={0.1} />
                                            <stop offset="95%" stopColor="#8b5cf6" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                    <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fill: '#94a3b8', fontSize: 10 }} />
                                    <YAxis tickFormatter={formatYAxis} axisLine={false} tickLine={false} tick={{ fill: '#94a3b8', fontSize: 10 }} />
                                    <RechartsTooltip
                                        cursor={{ stroke: '#e2e8f0', strokeWidth: 1 }}
                                        contentStyle={{ background: '#000', color: '#fff', border: '1px solid #1e293b', borderRadius: 8, fontSize: 12 }}
                                        formatter={(value: any, name: any) => [formatMoney(value, stats.business_currency_code), name]}
                                    />
                                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '11px', paddingTop: '15px' }} />
                                    <Area type="monotone" dataKey="income" name={__('general.income')} stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#ci)" />
                                    <Area type="monotone" dataKey="expenses" name={__('general.expenses')} stroke="#ef4444" strokeWidth={2} fillOpacity={1} fill="url(#ce)" />
                                    <Area type="monotone" dataKey="net_profit" name={__('general.net_profit')} stroke="#8b5cf6" strokeWidth={2} fillOpacity={1} fill="url(#cn)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-xl border shadow-sm">
                        {categoryAllocationData.length === 0 ? (
                            <div className="flex-1 flex flex-col items-center justify-center py-10 text-slate-400">
                                <DollarSign className="w-10 h-10 mb-2 opacity-50" />
                                <div className="text-xs italic">{__('general.no_allocation_statistics_for_this_month')}</div>
                            </div>
                        ) : (
                            <div>
                                <h3 className="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-1">
                                    {currentTab === 'income' ? __('general.income_stream_breakdown') || 'Income Stream Breakdown' : __('general.cost_expense_allocation') || 'Cost & Expense Allocation'}
                                </h3>
                                <p className="text-xs text-gray-500 mb-4 font-normal">{__('general.highest_spending') || 'Highest categories in'} {stats.business_currency_code}</p>

                                <div className="h-[120px] w-full flex items-center justify-center relative">
                                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                                        <span className="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">{__('general.total')}</span>
                                        <span className="text-sm font-extrabold text-slate-900 font-mono">
                                            {formatMoney(categoryAllocationData.reduce((s: number, i: any) => s + i.value, 0), stats.business_currency_code)}
                                        </span>
                                    </div>
                                    <ResponsiveContainer width="100%" height="100%" minWidth={1}>
                                        <PieChart>
                                            <Pie data={categoryAllocationData} cx="50%" cy="50%" innerRadius={50} outerRadius={70} paddingAngle={3} dataKey="value">
                                                {categoryAllocationData.map((_: any, idx: number) => <Cell key={idx} fill={COLORS[idx % COLORS.length]} />)}
                                            </Pie>
                                            <RechartsTooltip
                                                contentStyle={{ background: '#000', color: '#fff', border: '1px solid #1e293b', borderRadius: 8, fontSize: 12 }}
                                                formatter={(value: any) => formatMoney(value, stats.business_currency_code)}
                                            />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>

                                <div className="mt-2 space-y-1 max-h-[100px] overflow-y-auto pe-1">
                                    {categoryAllocationData.slice(0, 4).map((entry: any, index: number) => {
                                        const total = categoryAllocationData.reduce((s: number, i: any) => s + i.value, 0);
                                        const percentage = total > 0 ? (entry.value / total * 100).toFixed(1) : 0;
                                        return (
                                            <div key={index} className="flex justify-end gap-4 items-center text-[11px] text-slate-700 font-normal">
                                                <div className="me-auto flex items-center gap-1.5 truncate">
                                                    <span className="w-2 h-2 rounded-full shrink-0" style={{ backgroundColor: COLORS[index % COLORS.length] }} />
                                                    <span className="truncate font-medium text-slate-800">{entry.name}</span>
                                                </div>
                                                <span className="font-mono text-slate-500 shrink-0 font-normal">
                                                    {percentage}% ({formatMoney(entry.value, stats.business_currency_code)})
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {statusDistributionData.length > 0 && (
                            <div className="mt-4 pt-4 border-t border-slate-100">
                                <h4 className="text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-2">{__('general.ledger_status_health')}</h4>
                                <div className="space-y-2">
                                    <div className="w-full h-2 rounded-full overflow-hidden flex bg-slate-100">
                                        {statusDistributionData.map((item: any, idx: number) => {
                                            const totalAmount = statusDistributionData.reduce((s: number, x: any) => s + x.amount, 0);
                                            const pct = totalAmount > 0 ? item.amount / totalAmount * 100 : 0;
                                            if (pct === 0) return null;
                                            let bg = 'bg-slate-200';
                                            if (item.status.toLowerCase() === 'completed') bg = 'bg-emerald-600';
                                            if (item.status.toLowerCase() === 'pending') bg = 'bg-amber-500';
                                            if (item.status.toLowerCase() === 'overdue') bg = 'bg-rose-600';
                                            return <div key={idx} className={`${bg} h-full transition-all`} style={{ width: `${pct}%` }} title={`${item.status}: ${pct.toFixed(1)}%`} />;
                                        })}
                                    </div>
                                    <div className="flex flex-wrap gap-x-3 gap-y-1">
                                        {statusDistributionData.map((item: any, idx: number) => {
                                            let dot = 'bg-slate-300';
                                            let txt = 'text-slate-700';
                                            if (item.status.toLowerCase() === 'completed') { dot = 'bg-emerald-600'; txt = 'text-emerald-700'; }
                                            if (item.status.toLowerCase() === 'pending') { dot = 'bg-amber-500'; txt = 'text-amber-700'; }
                                            if (item.status.toLowerCase() === 'overdue') { dot = 'bg-rose-600'; txt = 'text-rose-700'; }
                                            return (
                                                <div key={idx} className="flex items-center gap-1 text-[10px] font-normal">
                                                    <span className={`w-1.5 h-1.5 rounded-full ${dot}`} />
                                                    <span className="font-medium text-slate-800">{item.status}:</span>
                                                    <span className={`font-mono ${txt} font-semibold`}>
                                                        {formatMoney(item.amount, stats.business_currency_code)}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}

            <div className="bg-white border rounded-xl p-4 shadow-sm mb-6 space-y-4">
                <div className="flex flex-wrap gap-3 items-center justify-end">
                    {currentTab !== 'calendar' ? (
                        <div className="flex flex-wrap gap-2 items-center flex-1 min-w-[300px]">
                            <div className="relative flex-1 max-w-[240px]">
                                <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder={__('general.search_by_reason')}
                                    className="ps-8 h-9 text-sm"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                />
                            </div>
                            <select
                                className="rounded-md border border-gray-300 px-2 py-1.5 text-xs bg-white h-9"
                                value={selectedCategoryFilter}
                                onChange={(e) => setSelectedCategoryFilter(e.target.value)}
                            >
                                <option value="">{__('general.all_categories')}</option>
                                {categoriesList.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                            <select
                                className="rounded-md border border-gray-300 px-2 py-1.5 text-xs bg-white h-9"
                                value={selectedStatusFilter}
                                onChange={(e) => setSelectedStatusFilter(e.target.value)}
                            >
                                <option value="">{__('general.all_statuses')}</option>
                                <option value="completed">{__('general.completed')}</option>
                                <option value="pending">{__('general.pending')}</option>
                                <option value="overdue">{__('general.overdue')}</option>
                            </select>
                            <PremiumCombobox
                                className="w-[180px]"
                                value={selectedUserFilter || ''}
                                onChange={(val) => setSelectedUserFilter(val ? String(val) : '')}
                                options={[{ value: '', label: __('general.all_users') }, ...users.map((u: any) => ({ value: String(u.id), label: `${u.name} (${u.email || ''})` }))]}
                                placeholder={__('general.all_users')}
                                searchPlaceholder={__('general.search_users')}
                            />
                            <Button onClick={applyFilters} variant="secondary" size="sm" className="h-9">{__('general.filter')}</Button>
                            {(filters.search || filters.category || filters.status || filters.user_id || searchTerm || selectedCategoryFilter || selectedStatusFilter || selectedUserFilter) && (
                                <Button onClick={clearFilters} variant="ghost" size="sm" className="h-9 text-gray-500 hover:text-black">
                                    <X className="w-4 h-4 me-1" /> {__('general.clear')}
                                </Button>
                            )}
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 flex-1">
                            <span className="text-xs font-semibold text-gray-500 flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
                                <CalendarDays className="w-4 h-4 text-black me-1" />{__('general.click_any_day_on_the_calendar_to_log_a_transaction')}
                            </span>
                        </div>
                    )}

                    <a href={route('admin.finance.report.export', { type: currentTab === 'income' || currentTab === 'expenses' ? 'ledger' : 'pnl' })} target="_blank" rel="noreferrer" className="me-auto">
                        <Button variant="outline" className="h-9 me-2 text-slate-700">
                            <Download className="w-4 h-4 me-2" /> {__('general.export_csv')}
                        </Button>
                    </a>
                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <Button className="bg-black hover:bg-slate-800 text-white h-9" onClick={() => setIsCreateOpen(true)}>
                            <Plus className="w-4 h-4 me-2" /> {currentTab === 'salaries' ? __('general.add_salary') : currentTab === 'expenses' ? __('general.add_expense') : currentTab === 'calendar' ? __('general.add_record') : __('general.add_income')}
                        </Button>
                        <DialogContent className="sm:max-w-[425px]">
                            <form onSubmit={handleCreate}>
                                <DialogHeader>
                                    <DialogTitle>{__('general.add_new_ledger_record')}</DialogTitle>
                                    <DialogDescription>
                                        {currentTab === 'income' ? __('general.create_income_stream_desc') || 'Create a new income stream.' : __('general.create_expense_desc') || 'Create a new expense entry.'}
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    {currentTab === 'calendar' && (
                                        <div className="space-y-2">
                                            <Label htmlFor="create-type">{__('general.transaction_type')}</Label>
                                            <select
                                                id="create-type"
                                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                                                value={newEntry.type}
                                                onChange={(e) => setNewEntry({ ...newEntry, type: e.target.value })}
                                            >
                                                <option value="expense">{__('general.costs_expenses')}</option>
                                                <option value="income">{__('general.income_streams')}</option>
                                                <option value="salary">{__('general.employee_payroll')}</option>
                                            </select>
                                        </div>
                                    )}
                                    <div className="space-y-2">
                                        <Label htmlFor="create-title">{__('general.description_title')}</Label>
                                        <Input id="create-title" required value={newEntry.title} onChange={(e) => setNewEntry({ ...newEntry, title: e.target.value })} placeholder={__('general.e_g_server_hosting')} />
                                        {errors.title && <p className="text-sm text-destructive mt-1">{errors.title}</p>}
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="create-amount">{__('general.amount')}</Label>
                                            <Input id="create-amount" type="number" step="0.01" min="0.01" required value={newEntry.amount} onChange={(e) => setNewEntry({ ...newEntry, amount: e.target.value })} />
                                            {errors.amount && <p className="text-sm text-destructive mt-1">{errors.amount}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="create-currency">{__('general.currency')}</Label>
                                            <select id="create-currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.currency_id} onChange={(e) => setNewEntry({ ...newEntry, currency_id: e.target.value })}>
                                                {currenciesList.map((c: any) => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                                            </select>
                                        </div>
                                    </div>

                                    {currentTab !== 'salaries' && newEntry.type !== 'salary' && (
                                        <div className="space-y-2">
                                            <Label>{__('general.category_reason')}</Label>
                                            <div className="grid grid-cols-2 gap-2">
                                                <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={createCategoryOption} onChange={(e) => {
                                                    setCreateCategoryOption(e.target.value);
                                                    setNewEntry({ ...newEntry, category_id: e.target.value !== 'custom' ? e.target.value : '' });
                                                }}>
                                                    {categoriesList.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                                    <option value="custom">-- Custom Category --</option>
                                                </select>
                                                {createCategoryOption === 'custom' && (
                                                    <Input required placeholder={__('general.enter_category')} value={newEntry.category_id} onChange={(e) => setNewEntry({ ...newEntry, category_id: e.target.value })} />
                                                )}
                                            </div>
                                            {errors.category_id && <p className="text-sm text-destructive mt-1">{errors.category_id}</p>}
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="create-user">
                                            {currentTab === 'salaries' || newEntry.type === 'salary' ? __('general.employee_required') || 'Employee (Required)'
                                                : currentTab === 'income' || newEntry.type === 'income' ? __('general.client_user_optional') || 'Client/User (Optional)'
                                                : __('general.user_vendor_optional') || 'User/Vendor (Optional)'}
                                        </Label>
                                        <PremiumCombobox
                                            value={newEntry.user_id ? String(newEntry.user_id) : ''}
                                            onChange={(val) => setNewEntry({ ...newEntry, user_id: val ? String(val) : '' })}
                                            options={users.map((u: any) => ({ value: String(u.id), label: `${u.name} (${u.email || ''})` }))}
                                            placeholder={__('general.select_user')}
                                            searchPlaceholder={__('general.search_users')}
                                        />
                                        {errors.user_id && <p className="text-sm text-destructive mt-1">{errors.user_id}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="create-status">{__('general.status')}</Label>
                                            <select id="create-status" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.status} onChange={(e) => setNewEntry({ ...newEntry, status: e.target.value })}>
                                                <option value="completed">{__('general.completed_paid')}</option>
                                                <option value="pending">{__('general.pending')}</option>
                                                <option value="overdue">{__('general.overdue')}</option>
                                            </select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="create-due">{__('general.due_date')}</Label>
                                            <Input id="create-due" type="date" value={newEntry.due_date} onChange={(e) => setNewEntry({ ...newEntry, due_date: e.target.value })} />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create-tx-date">{__('general.transaction_date')}</Label>
                                        <Input id="create-tx-date" type="date" required value={newEntry.transaction_date} onChange={(e) => setNewEntry({ ...newEntry, transaction_date: e.target.value })} />
                                        {errors.transaction_date && <p className="text-sm text-destructive mt-1">{errors.transaction_date}</p>}
                                    </div>

                                    {currentTab !== 'income' && (
                                        <div className="pt-2 border-t space-y-3">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    id="create-recurring"
                                                    className="rounded border-gray-300 text-black focus:ring-black h-4 w-4"
                                                    checked={newEntry.is_recurring}
                                                    onChange={(e) => setNewEntry({ ...newEntry, is_recurring: e.target.checked })}
                                                />
                                                <span className="text-sm font-medium text-slate-700">{__('general.recurring_expense')}</span>
                                            </label>
                                            {newEntry.is_recurring && (
                                                <div className="space-y-2">
                                                    <Label htmlFor="create-interval">{__('general.interval')}</Label>
                                                    <select id="create-interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newEntry.recurrence_interval} onChange={(e) => setNewEntry({ ...newEntry, recurrence_interval: e.target.value })}>
                                                        <option value="day">{__('general.daily')}</option>
                                                        <option value="week">{__('general.weekly')}</option>
                                                        <option value="month">{__('general.monthly')}</option>
                                                        <option value="year">{__('general.annually')}</option>
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>{__('general.cancel')}</Button>
                                    <Button type="submit" disabled={Object.keys(errors || {}).length > 0} className="bg-black hover:bg-slate-800 text-white w-full">{__('general.save_record')}</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            {currentTab !== 'calendar' && !hasEntries ? (
                <EmptyState
                    icon={DollarSign}
                    title={__('general.no_records_found') || 'No records found'}
                    description={__('general.create_a_new_entry_to_start_tracking_your_finances')}
                />
            ) : currentTab !== 'calendar' && (
                <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                    {currentTab === 'projects' ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.project_name')}</th>
                                        <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.client')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.revenue')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.costs')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.margin')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.profit')}</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {entriesList.map((project: any) => (
                                        <tr key={project.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm font-medium text-gray-900">{project.name}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{project.client?.name || '-'}</td>
                                            <td className="px-6 py-4 text-end text-sm font-bold text-gray-900 font-mono">{formatMoney(project.revenue, stats.business_currency_code)}</td>
                                            <td className="px-6 py-4 text-end text-sm text-rose-600 font-mono">{formatMoney(project.costs, stats.business_currency_code)}</td>
                                            <td className="px-6 py-4 text-end text-sm text-gray-900">{project.margin}%</td>
                                            <td className={`px-6 py-4 text-end text-sm font-bold font-mono ${project.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>{formatMoney(project.profit, stats.business_currency_code)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : currentTab === 'budgets' ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.category')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.budget_amount')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.spent')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.remaining')}</th>
                                        <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.status')}</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {entriesList.map((budget: any) => {
                                        const remaining = budget.amount - budget.spent;
                                        const percent = budget.amount > 0 ? (budget.spent / budget.amount * 100) : 0;
                                        const barColor = percent > 100 ? 'bg-rose-600' : percent > 80 ? 'bg-amber-500' : 'bg-emerald-600';
                                        return (
                                            <tr key={budget.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 text-sm font-medium text-gray-900">{budget.category}</td>
                                                <td className="px-6 py-4 text-end text-sm font-bold text-gray-900 font-mono">{budget.currency_symbol}{budget.amount}</td>
                                                <td className="px-6 py-4 text-end text-sm text-gray-900 font-mono">{budget.currency_symbol}{budget.spent}</td>
                                                <td className={`px-6 py-4 text-end text-sm font-bold font-mono ${remaining >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>{budget.currency_symbol}{remaining}</td>
                                                <td className="px-6 py-4 text-sm">
                                                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                                                        <div className={`h-2.5 rounded-full ${barColor}`} style={{ width: `${Math.min(percent, 100)}%` }} />
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th onClick={() => handleSort('reason')} className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                            {__('general.description')} {renderSortIcon('reason')}
                                        </th>
                                        <th onClick={() => handleSort('amount')} className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                            {__('general.amount')} {renderSortIcon('amount')}
                                        </th>
                                        <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.category')}</th>
                                        <th onClick={() => handleSort('created_at')} className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                            {__('general.date')} {renderSortIcon('created_at')}
                                        </th>
                                        <th onClick={() => handleSort('due_date')} className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none">
                                            {__('general.due_date')} {renderSortIcon('due_date')}
                                        </th>
                                        <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.status')}</th>
                                        <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {entriesList.map((entry: any) => {
                                        const effectiveStatus = getStatusFromDate(entry.next_due_date, entry.status);
                                        return (
                                            <tr key={entry.id} className="hover:bg-gray-50">
                                                <td onClick={() => openDetails(entry)} className="px-6 py-4 cursor-pointer">
                                                    <div className="text-sm font-medium text-gray-900">{entry.title}</div>
                                                    {entry.user && <div className="text-xs text-gray-500 mt-1">{__('general.user')}: {entry.user.name}</div>}
                                                    {entry.is_recurring && (
                                                        <span className="inline-flex items-center gap-1 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-semibold px-2 py-0.5 rounded-full mt-1.5">
                                                            <RefreshCw className="w-2.5 h-2.5" /> {__('general.recurring')}
                                                        </span>
                                                    )}
                                                </td>
                                                <td onClick={() => openDetails(entry)} className="px-6 py-4 cursor-pointer text-end">
                                                    <div className="text-sm font-bold text-gray-900 font-mono">{formatMoney(entry.amount, entry.currency)}</div>
                                                    {entry.business_amount && entry.currency !== stats.business_currency_code && (
                                                        <div className="text-xs text-gray-500 mt-0.5 font-mono">
                                                            ≈ {formatMoney(entry.business_amount, stats.business_currency_code)}
                                                        </div>
                                                    )}
                                                </td>
                                                <td onClick={() => openDetails(entry)} className="px-6 py-4 cursor-pointer">
                                                    <div className="text-sm text-gray-700">{entry.category?.name || __('general.uncategorized')}</div>
                                                    {entry.project && (
                                                        <div className="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                                            <Layers className="w-3 h-3" /> {entry.project.name}
                                                        </div>
                                                    )}
                                                </td>
                                                <td onClick={() => openDetails(entry)} className="px-6 py-4 cursor-pointer">
                                                    <span className="text-sm text-gray-900">{new Date(entry.created_at).toLocaleDateString()}</span>
                                                </td>
                                                <td onClick={() => openDetails(entry)} className="px-6 py-4 cursor-pointer">
                                                    <span className="text-sm text-gray-500">{entry.next_due_date ? new Date(entry.next_due_date).toLocaleDateString() : '-'}</span>
                                                </td>
                                                <td onClick={() => openDetails(entry)} className="px-6 py-4 cursor-pointer">
                                                    <StatBadge status={effectiveStatus} />
                                                </td>
                                                <td className="px-6 py-4 text-end text-sm font-medium">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="icon" onClick={() => openDetails(entry)} className="h-8 w-8 text-slate-500 hover:text-black hover:bg-slate-50" aria-label={__('general.view_details')}>
                                                            <Eye className="w-4 h-4" />
                                                        </Button>
                                                        {effectiveStatus === 'pending' && (
                                                            <Button variant="ghost" size="sm" className="h-8 px-2 border-green-200 text-emerald-700 hover:bg-emerald-50" onClick={() => handleMarkPaid(entry.id)}>
                                                                <CheckCircle2 className="w-3.5 h-3.5 me-1" /> {__('general.paid')}
                                                            </Button>
                                                        )}
                                                        <Button variant="ghost" size="icon" onClick={() => openEdit(entry)} className="h-8 w-8 text-slate-500 hover:text-black hover:bg-slate-50" aria-label={__('general.edit')}>
                                                            <Edit className="w-4 h-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-rose-500 hover:text-rose-700 hover:bg-rose-50" onClick={() => setPendingDelete(entry.id)} aria-label={__('general.delete')}>
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {currentTab !== 'calendar' && entries.links && entries.links.length > 3 && (
                <div className="flex justify-end gap-4 items-center mt-6">
                    <div className="me-auto text-sm text-gray-500">
                        {__('general.showing')} {entries.from} {__('general.to')} {entries.to} {__('general.of')} {entries.total} {__('general.entries')}
                    </div>
                    <div className="flex space-x-1">
                        {entries.links.map((link: any, idx: number) => {
                            if (link.url === null) {
                                return <span key={idx} className="px-3 py-2 border rounded text-gray-400 bg-gray-50 text-sm cursor-not-allowed" dangerouslySetInnerHTML={{ __html: link.label }} />;
                            }
                            return (
                                <Link
                                    key={idx}
                                    href={link.url}
                                    className={`px-3 py-2 border rounded text-sm ${link.active ? 'bg-black text-white border-black font-semibold' : 'bg-white text-gray-700 hover:bg-gray-50'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                </div>
            )}

            {currentTab === 'calendar' && (
                <div className="space-y-6">
                    <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                        <div className="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-end gap-4">
                            <div className="me-auto flex items-center gap-2">
                                <Button variant="outline" size="sm" onClick={handlePrevMonth} aria-label={__('general.previous_month')}>
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="sm" onClick={handleGoToToday}>{__('general.today')}</Button>
                                <Button variant="outline" size="sm" onClick={handleNextMonth} aria-label={__('general.next_month')}>
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                            <h2 className="text-lg font-bold text-slate-800">{format(new Date(year, month - 1, 1), 'MMMM yyyy')}</h2>
                        </div>

                        <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-100/80">
                            {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day) => (
                                <div key={day} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    {day}
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-7 bg-white divide-x divide-y divide-slate-100">
                            {(() => {
                                const currentDateObj = new Date(year, month - 1, 1);
                                const startDate = startOfWeek(startOfMonth(currentDateObj), { weekStartsOn });
                                const endDate = endOfWeek(endOfMonth(currentDateObj), { weekStartsOn });
                                const calendarDays = eachDayOfInterval({ start: startDate, end: endDate });

                                return calendarDays.map((day, idx) => {
                                    const dateStr = format(day, 'yyyy-MM-dd');
                                    const dayEvents = calendarEvents[dateStr] || [];
                                    const isCurrentMonth = isSameMonth(day, currentDateObj);
                                    const isCurrentDay = isToday(day);

                                    return (
                                        <div
                                            key={idx}
                                            className={`min-h-[140px] p-2 flex flex-col transition-colors relative ${
                                                !isCurrentMonth ? 'bg-slate-50/30 opacity-55' : ''
                                            } ${isCurrentDay ? 'bg-blue-50/30' : 'hover:bg-slate-50/50'}`}
                                        >
                                            <div className="flex justify-end gap-4 items-start mb-2">
                                                <button
                                                    type="button"
                                                    onClick={() => handleDayClick(day)}
                                                    className={`text-xs font-semibold flex items-center justify-center h-6 w-6 rounded-full transition-colors ${
                                                        isCurrentDay
                                                            ? 'bg-black text-white shadow font-bold'
                                                            : 'text-gray-700 hover:bg-slate-200'
                                                    }`}
                                                    aria-label={__('general.click_to_add_record_on_this_day')}
                                                    title={__('general.click_to_add_record_on_this_day')}
                                                >
                                                    {format(day, 'd')}
                                                </button>
                                            </div>

                                            <div className="flex-1 space-y-1 overflow-y-auto max-h-[110px]">
                                                {dayEvents.map((event: any) => {
                                                    let bgClass = 'bg-rose-50 text-rose-700 border-rose-100';
                                                    if (event.type === 'income') bgClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                                    if (event.type === 'salary') bgClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                                    return (
                                                        <button
                                                            key={`${event.type}-${event.id}`}
                                                            onClick={() => openEdit(event)}
                                                            className={`w-full text-start text-[10px] p-1 rounded border flex flex-col hover:opacity-80 transition-opacity truncate ${bgClass}`}
                                                            title={`${event.title}: ${formatMoney(event.amount, event.currency || stats.business_currency_code)}`}
                                                        >
                                                            <span className="font-semibold truncate">{event.title}</span>
                                                            <span className="font-bold font-mono">{formatMoney(event.amount, event.currency || stats.business_currency_code)}</span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                });
                            })()}
                        </div>
                    </div>
                </div>
            )}

            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="sm:max-w-[425px]">
                    <form onSubmit={handleUpdate}>
                        <DialogHeader>
                            <DialogTitle>{__('general.edit_ledger_record')}</DialogTitle>
                            <DialogDescription>{__('general.modify_the_selected_transaction_record_in_the_ledger')}</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            {currentTab === 'calendar' && (
                                <div className="space-y-2">
                                    <Label htmlFor="edit-type">{__('general.transaction_type')}</Label>
                                    <select id="edit-type" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editingEntry.type} onChange={(e) => setEditingEntry({ ...editingEntry, type: e.target.value })}>
                                        <option value="expense">{__('general.costs_expenses')}</option>
                                        <option value="income">{__('general.income_streams')}</option>
                                        <option value="salary">{__('general.employee_payroll')}</option>
                                    </select>
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="edit-title">{__('general.description_title')}</Label>
                                <Input id="edit-title" required value={editingEntry.title} onChange={(e) => setEditingEntry({ ...editingEntry, title: e.target.value })} />
                                {errors.title && <p className="text-sm text-destructive mt-1">{errors.title}</p>}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-amount">{__('general.amount')}</Label>
                                    <Input id="edit-amount" type="number" step="0.01" min="0.01" required value={editingEntry.amount} onChange={(e) => setEditingEntry({ ...editingEntry, amount: e.target.value })} />
                                    {errors.amount && <p className="text-sm text-destructive mt-1">{errors.amount}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-currency">{__('general.currency')}</Label>
                                    <select id="edit-currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editingEntry.currency_id} onChange={(e) => setEditingEntry({ ...editingEntry, currency_id: e.target.value })}>
                                        {currenciesList.map((c: any) => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                                    </select>
                                </div>
                            </div>

                            {currentTab !== 'salaries' && editingEntry.type !== 'salary' && (
                                <div className="space-y-2">
                                    <Label>{__('general.category_reason')}</Label>
                                    <div className="grid grid-cols-2 gap-2">
                                        <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editCategoryOption} onChange={(e) => {
                                            setEditCategoryOption(e.target.value);
                                            setEditingEntry({ ...editingEntry, category_id: e.target.value !== 'custom' ? e.target.value : '' });
                                        }}>
                                            {categoriesList.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                            <option value="custom">-- Custom Category --</option>
                                        </select>
                                        {editCategoryOption === 'custom' && (
                                            <Input required placeholder={__('general.enter_category')} value={editingEntry.category_id} onChange={(e) => setEditingEntry({ ...editingEntry, category_id: e.target.value })} />
                                        )}
                                    </div>
                                    {errors.category_id && <p className="text-sm text-destructive mt-1">{errors.category_id}</p>}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="edit-user">
                                    {currentTab === 'salaries' || editingEntry.type === 'salary' ? __('general.employee_required') || 'Employee (Required)'
                                        : currentTab === 'income' || editingEntry.type === 'income' ? __('general.client_user_optional') || 'Client/User (Optional)'
                                        : __('general.user_vendor_optional') || 'User/Vendor (Optional)'}
                                </Label>
                                <PremiumCombobox
                                    value={editingEntry.user_id ? String(editingEntry.user_id) : ''}
                                    onChange={(val) => setEditingEntry({ ...editingEntry, user_id: val ? String(val) : '' })}
                                    options={users.map((u: any) => ({ value: String(u.id), label: `${u.name} (${u.email || ''})` }))}
                                    placeholder={__('general.select_user')}
                                    searchPlaceholder={__('general.search_users')}
                                />
                                {errors.user_id && <p className="text-sm text-destructive mt-1">{errors.user_id}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit-status">{__('general.status')}</Label>
                                    <select id="edit-status" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editingEntry.status} onChange={(e) => setEditingEntry({ ...editingEntry, status: e.target.value })}>
                                        <option value="completed">{__('general.completed_paid')}</option>
                                        <option value="pending">{__('general.pending')}</option>
                                        <option value="overdue">{__('general.overdue')}</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edit-due">{__('general.due_date')}</Label>
                                    <Input id="edit-due" type="date" value={editingEntry.due_date} onChange={(e) => setEditingEntry({ ...editingEntry, due_date: e.target.value })} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit-tx-date">{__('general.transaction_date')}</Label>
                                <Input id="edit-tx-date" type="date" required value={editingEntry.transaction_date} onChange={(e) => setEditingEntry({ ...editingEntry, transaction_date: e.target.value })} />
                                {errors.transaction_date && <p className="text-sm text-destructive mt-1">{errors.transaction_date}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit" className="bg-black hover:bg-slate-800 text-white w-full">{__('general.update_record')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Sheet open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <SheetContent className="sm:max-w-[480px] overflow-y-auto bg-white border-s">
                    {selectedDetailEntry && (
                        <div className="space-y-6 pt-4">
                            <SheetHeader className="text-start border-b pb-4">
                                <div className="flex items-center justify-end gap-4 mb-2">
                                    <span className="me-auto text-xs font-semibold text-gray-400 uppercase tracking-widest">{__('general.transaction_details')}</span>
                                    <StatBadge status={getStatusFromDate(selectedDetailEntry.next_due_date, selectedDetailEntry.status)} />
                                </div>
                                <SheetTitle className="text-xl font-bold text-slate-900">
                                    {selectedDetailEntry.title}
                                </SheetTitle>
                                <SheetDescription className="text-xs text-gray-500">
                                    {__('general.record_id')} #{selectedDetailEntry.id} • {__('general.created_on')} {new Date(selectedDetailEntry.created_at).toLocaleString()}
                                </SheetDescription>
                            </SheetHeader>

                            <div className="bg-slate-50 border rounded-xl p-5 text-center">
                                <div className="text-xs text-gray-500 uppercase tracking-wider mb-1 font-semibold">{__('general.amount')}</div>
                                <div className="text-3xl font-black text-slate-900 flex justify-center items-baseline gap-1 font-mono">
                                    {formatMoney(selectedDetailEntry.amount, selectedDetailEntry.currency)}
                                </div>
                                {selectedDetailEntry.business_amount && selectedDetailEntry.currency !== stats.business_currency_code && (
                                    <div className="text-sm text-gray-500 mt-2 flex items-center justify-center gap-1 font-medium border-t pt-2 border-slate-200/60">
                                        <span>{__('general.equivalent')}:</span>
                                        <span className="font-bold text-slate-800 font-mono">
                                            {formatMoney(selectedDetailEntry.business_amount, stats.business_currency_code)}
                                        </span>
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4 border rounded-xl p-4">
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">{__('general.type')}</div>
                                    <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                        {selectedDetailEntry.type === 'received' ? (
                                            <span className="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-flex items-center text-xs font-bold">
                                                <TrendingUp className="w-3 h-3 me-1" />{__('general.deposit_income')}</span>
                                        ) : selectedDetailEntry.type === 'refunded' ? (
                                            <span className="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 inline-flex items-center text-xs font-bold">
                                                {__('general.refund')}</span>
                                        ) : selectedDetailEntry.type === 'salary' ? (
                                            <span className="text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 inline-flex items-center text-xs font-bold">
                                                {__('general.payroll')}</span>
                                        ) : (
                                            <span className="text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 inline-flex items-center text-xs font-bold">
                                                <TrendingDown className="w-3 h-3 me-1" /> {__('general.expense')}</span>
                                        )}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">{__('general.category')}</div>
                                    <div className="text-sm font-semibold text-slate-800">
                                        {selectedDetailEntry.category?.name || __('general.uncategorized')}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">{__('general.transaction_date')}</div>
                                    <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                        <Calendar className="w-3.5 h-3.5 text-gray-400" />
                                        {new Date(selectedDetailEntry.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="text-xs text-gray-400 font-medium">{__('general.due_date')}</div>
                                    <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                        <Calendar className="w-3.5 h-3.5 text-gray-400" />
                                        {selectedDetailEntry.next_due_date ? new Date(selectedDetailEntry.next_due_date).toLocaleDateString() : 'N/A'}
                                    </div>
                                </div>
                                {selectedDetailEntry.is_recurring && (
                                    <div className="col-span-2 space-y-1 pt-2 border-t border-slate-100">
                                        <div className="text-xs text-gray-400 font-medium">{__('general.recurrence')}</div>
                                        <div className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                            <RefreshCw className="w-3.5 h-3.5 text-slate-500" />
                                            <span>{__('general.this_is_a_recurring_transaction')}</span>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {selectedDetailEntry.user && (
                                <div className="bg-white border rounded-xl p-4 space-y-3">
                                    <div className="flex items-center justify-end gap-4 border-b pb-2">
                                        <div className="me-auto flex items-center gap-1.5">
                                            <Users className="w-4 h-4 text-slate-500" />
                                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">{__('general.associated_user_client')}</span>
                                        </div>
                                        <Link href={route('admin.users.show', selectedDetailEntry.user.id)} className="text-xs text-slate-500 hover:text-black flex items-center hover:underline font-semibold">
                                            {__('general.profile')}<ExternalLink className="w-3 h-3 ms-1" />
                                        </Link>
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold text-slate-800">{selectedDetailEntry.user.name}</div>
                                        <div className="text-xs text-gray-500 mt-0.5">{selectedDetailEntry.user.email}</div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2 pt-2">
                                        <Link href={route('admin.users.notes.index', selectedDetailEntry.user.id)} className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors">
                                            <FileText className="w-3.5 h-3.5" /> {__('general.notes')}
                                        </Link>
                                        <Link href={route('admin.users.files.index', selectedDetailEntry.user.id)} className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors">
                                            <Layers className="w-3.5 h-3.5" /> {__('general.files')}
                                        </Link>
                                        <Link href={route('admin.users.reports', selectedDetailEntry.user.id)} className="col-span-2 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors">
                                            <DollarSign className="w-3.5 h-3.5" />{__('general.financial_statement_report')}
                                        </Link>
                                    </div>
                                </div>
                            )}

                            {selectedDetailEntry.project && (
                                <div className="bg-white border rounded-xl p-4 space-y-2">
                                    <div className="flex items-center justify-end gap-4 border-b pb-2">
                                        <div className="me-auto flex items-center gap-1.5">
                                            <Layers className="w-4 h-4 text-slate-500" />
                                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">{__('general.linked_project')}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold text-slate-800">{selectedDetailEntry.project.name}</div>
                                    </div>
                                    <Link href={route('admin.projects.index')} className="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-black transition-colors">
                                        {__('general.go_to_projects_manager')}
                                    </Link>
                                </div>
                            )}

                            <div className="border-t pt-6 space-y-2">
                                {getStatusFromDate(selectedDetailEntry.next_due_date, selectedDetailEntry.status) === 'pending' && (
                                    <Button
                                        className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold inline-flex items-center justify-center"
                                        onClick={() => handleMarkPaid(selectedDetailEntry.id, true)}
                                    >
                                        <CheckCircle2 className="w-4 h-4 me-2" />{__('general.mark_as_paid_completed')}
                                    </Button>
                                )}
                                <div className="flex gap-2">
                                    <Button variant="outline" className="flex-1 font-semibold text-slate-700 border-slate-300 hover:bg-slate-50 hover:text-black" onClick={() => openEdit(selectedDetailEntry)}>
                                        <Edit className="w-4 h-4 me-2" />{__('general.edit_record')}
                                    </Button>
                                    <Button variant="outline" className="flex-1 font-semibold border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-900" onClick={() => { setIsDetailOpen(false); setPendingDelete(selectedDetailEntry.id); }}>
                                        <Trash2 className="w-4 h-4 me-2" /> {__('general.delete')}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </SheetContent>
            </Sheet>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete') || 'Delete record?'}
                description={__('general.confirm_delete_record_desc') || 'This record will be permanently deleted.'}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}