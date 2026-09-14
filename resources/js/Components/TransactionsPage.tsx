import React, { useState, useEffect } from 'react';
import { router, Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    MoreHorizontal,
    Trash,
    Search,
    X,
    Filter,
    RotateCcw,
    Calendar,
    Coins,
    SlidersHorizontal,
    ArrowUpRight,
    ArrowDownRight,
    Hash,
    Plus,
    User as UserIcon,
    FolderKanban,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export type TransactionsTab = 'income' | 'cost' | 'revenue';

export interface FilterOptions {
    users?: Array<{ id: number; name: string; email: string }>;
    projects?: Array<{ id: number; project_name: string; user_id?: number }>;
    currencies?: Array<{ id: number; currency: string; symbol: string }>;
    types?: string[];
}

export interface TransactionSummary {
    total_count: number;
    total_business_amount: number;
    positive_business_amount: number;
    negative_business_amount: number;
    avg_business_amount: number;
}

export interface TransactionsPageProps {
    type: TransactionsTab;
    titleKey: string;
    headerKey: string;
    descriptionKey: string;
    emptyTitleKey: string;
    emptyDescriptionKey: string;
    primaryCreateType: 'receive' | 'earn';
    primaryCreateLabelKey: string;
    showMoreMenu?: boolean;
    amountColorize?: 'green' | 'red';
    transactions: any;
    filters: any;
    filteredUser?: any;
    filterOptions?: FilterOptions;
    summary?: TransactionSummary;
    businessCurrency?: any;
    children?: (ctx: { handleSearch: (s: string) => void; handleSort: (key: string) => void }) => React.ReactNode;
}

function TransactionActions({ tx, type }: { tx: any; type: TransactionsTab }) {
    const [pending, setPending] = useState(false);

    const handleDelete = () => {
        if (pending) return;
        setPending(true);
        router.delete(`/admin/transactions/${tx.id}?type=${type}`, {
            preserveScroll: true,
            onSuccess: () => {
                setPending(false);
                toast.success(__('general.deleted') || 'Deleted');
            },
            onError: () => {
                setPending(false);
                toast.error(__('general.error_occurred') || 'Something went wrong');
            },
        });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">{__('general.actions')}</span>
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleDelete} className="text-red-600 focus:text-red-600 cursor-pointer">
                    <Trash className="w-4 h-4 me-2" />
                    {__('general.delete')}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function getDatePresetRange(preset: string): { from_date?: string; to_date?: string } {
    const now = new Date();
    const formatDate = (d: Date) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (preset === 'today') {
        const today = formatDate(now);
        return { from_date: today, to_date: today };
    }
    if (preset === 'yesterday') {
        const y = new Date(now);
        y.setDate(y.getDate() - 1);
        const yesterday = formatDate(y);
        return { from_date: yesterday, to_date: yesterday };
    }
    if (preset === 'this_week') {
        const start = new Date(now);
        const day = start.getDay();
        const diff = start.getDate() - day + (day === 0 ? -6 : 1);
        start.setDate(diff);
        return { from_date: formatDate(start), to_date: formatDate(now) };
    }
    if (preset === 'this_month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        return { from_date: formatDate(start), to_date: formatDate(now) };
    }
    if (preset === 'last_month') {
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        return { from_date: formatDate(start), to_date: formatDate(end) };
    }
    if (preset === 'this_year') {
        const start = new Date(now.getFullYear(), 0, 1);
        return { from_date: formatDate(start), to_date: formatDate(now) };
    }
    return {};
}

export function TransactionsPage(props: TransactionsPageProps) {
    const {
        type,
        titleKey,
        headerKey,
        descriptionKey,
        emptyTitleKey,
        emptyDescriptionKey,
        primaryCreateType,
        primaryCreateLabelKey,
        showMoreMenu = true,
        amountColorize = 'green',
        transactions,
        filters,
        filteredUser,
        filterOptions = {},
        summary,
        businessCurrency,
        children,
    } = props;

    // Filter controls state
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
    const [fromDate, setFromDate] = useState(filters?.from_date || '');
    const [toDate, setToDate] = useState(filters?.to_date || '');
    const [minAmount, setMinAmount] = useState(filters?.min_amount || '');
    const [maxAmount, setMaxAmount] = useState(filters?.max_amount || '');

    // Synchronize local states when props change
    useEffect(() => {
        setSearchTerm(filters?.search || '');
        setFromDate(filters?.from_date || '');
        setToDate(filters?.to_date || '');
        setMinAmount(filters?.min_amount || '');
        setMaxAmount(filters?.max_amount || '');
    }, [filters]);

    // Check if advanced filters have any active values
    const advancedActive = Boolean(
        filters?.user ||
        filters?.project ||
        filters?.currency ||
        filters?.from_date ||
        filters?.to_date ||
        filters?.min_amount ||
        filters?.max_amount ||
        filters?.month ||
        filters?.year
    );

    // Auto-open advanced panel if any advanced filter is populated
    useEffect(() => {
        if (advancedActive) {
            setShowAdvancedFilters(true);
        }
    }, [advancedActive]);

    const applyFilters = (updated: Record<string, any>) => {
        const params: Record<string, any> = { type };
        const merged = { ...filters, ...updated };

        Object.keys(merged).forEach((k) => {
            const val = merged[k];
            if (val !== undefined && val !== null && val !== '') {
                params[k] = val;
            }
        });

        params.page = 1;

        router.get('/admin/transactions', params, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        applyFilters({ search: searchTerm.trim() });
    };

    const handleClearSearch = () => {
        setSearchTerm('');
        applyFilters({ search: '' });
    };

    const handleDatePreset = (preset: string) => {
        if (preset === 'all') {
            setFromDate('');
            setToDate('');
            applyFilters({ from_date: '', to_date: '', month: '', year: '' });
            return;
        }
        const range = getDatePresetRange(preset);
        setFromDate(range.from_date || '');
        setToDate(range.to_date || '');
        applyFilters({ ...range, month: '', year: '' });
    };

    const handleSort = (key: string) => {
        const direction = filters?.sort === key && (filters?.direction === 'asc' || filters?.dir === 'asc') ? 'desc' : 'asc';
        applyFilters({ sort: key, direction });
    };

    const handleResetAll = () => {
        setSearchTerm('');
        setFromDate('');
        setToDate('');
        setMinAmount('');
        setMaxAmount('');
        router.get(
            '/admin/transactions',
            { type, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const activeFilterCount = [
        filters?.search,
        filters?.tx_type,
        filters?.user,
        filters?.project,
        filters?.currency,
        filters?.from_date,
        filters?.to_date,
        filters?.min_amount,
        filters?.max_amount,
        filters?.month,
        filters?.year,
    ].filter((v) => v !== undefined && v !== null && v !== '').length;

    // Filter projects according to selected client if applicable
    const availableProjects = (filterOptions.projects || []).filter((p) => {
        if (!filters?.user) return true;
        return !p.user_id || String(p.user_id) === String(filters.user);
    });

    const colorClass = amountColorize === 'green' ? 'text-emerald-600' : 'text-rose-600';

    const columns = [
        {
            key: 'id',
            label: __('general.id'),
            sortable: true,
            className: 'w-[70px]',
            render: (tx: any) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>,
        },
        {
            key: 'user',
            label: __('general.user'),
            render: (tx: any) => tx.user ? (
                <div className="flex flex-col">
                    <span className="font-medium text-slate-900 dark:text-zinc-100">{tx.user.name}</span>
                    <span className="text-xs text-slate-500 dark:text-zinc-400">{tx.user.email}</span>
                </div>
            ) : <span className="text-slate-400">—</span>,
        },
        {
            key: 'project',
            label: __('erp.project'),
            render: (tx: any) => tx.project ? (
                <span className="text-slate-700 dark:text-zinc-300 font-medium">{tx.project.project_name}</span>
            ) : <span className="text-slate-400">—</span>,
        },
        ...(type === 'income' ? [{
            key: 'type',
            label: __('general.type'),
            sortable: true,
            render: (tx: any) => {
                const creditTypes = ['earned', 'received'];
                const isCredit = creditTypes.includes(tx.type);
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border ${isCredit ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border-slate-200 dark:border-zinc-700'}`}>
                        {tx.type}
                    </span>
                );
            },
        }] : []),
        {
            key: 'amount',
            label: __('general.amount'),
            className: 'text-end',
            sortable: true,
            render: (tx: any) => (
                <span className="font-medium font-mono text-slate-800 dark:text-zinc-200">
                    {formatMoney(tx.amount || 0, tx.currency)}
                </span>
            ),
        },
        {
            key: 'business_amount',
            label: __('general.business_amount'),
            className: 'text-end',
            sortable: true,
            render: (tx: any) => (
                <span className={`font-medium font-mono ${colorClass}`}>
                    {formatMoney(tx.business_amount || 0, tx.business_currency || businessCurrency)}
                </span>
            ),
        },
        ...(type === 'income' ? [{
            key: 'balance',
            label: __('general.balance'),
            className: 'text-end',
            render: (tx: any) => (
                <span className={`font-medium font-mono ${tx.balance !== undefined && tx.balance !== null ? (tx.balance < 0 ? 'text-rose-600' : tx.balance > 0 ? 'text-emerald-600' : 'text-slate-800 dark:text-zinc-200') : 'text-slate-400'}`}>
                    {tx.balance !== undefined && tx.balance !== null
                        ? formatMoney(tx.balance, tx.currency)
                        : '—'}
                </span>
            ),
        }] : []),
        {
            key: 'reason',
            label: __('general.reason'),
            render: (tx: any) => <span className="text-slate-600 dark:text-zinc-400 max-w-[240px] truncate block text-xs" title={tx.reason}>{tx.reason || '—'}</span>,
        },
        {
            key: 'created_at',
            label: __('general.date'),
            sortable: true,
            render: (tx: any) => (
                <span className="text-slate-600 dark:text-zinc-400 whitespace-nowrap text-xs">
                    {new Date(tx.created_at).toLocaleDateString()}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[50px] text-end',
            render: (tx: any) => <TransactionActions tx={tx} type={type} />,
        },
    ];

    const userParam = filteredUser ? `&user=${filteredUser.id}` : '';

    return (
        <AdminSidebarLayout title={__(titleKey)} header={__(headerKey)}>
            <Head title={__(titleKey)} />

            {/* View switcher and Actions */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <nav className="inline-flex rounded-lg p-1 bg-slate-100 dark:bg-zinc-800/80 border border-slate-200 dark:border-zinc-700/60 text-xs font-medium">
                            <Link
                                href="/admin/transactions?type=income"
                                className={`px-3 py-1 rounded-md transition-colors ${type === 'income' ? 'bg-white dark:bg-zinc-900 text-slate-900 dark:text-white shadow-sm font-semibold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'}`}
                            >
                                {__('erp.income_transactions') || 'Income'}
                            </Link>
                            <Link
                                href="/admin/transactions?type=cost"
                                className={`px-3 py-1 rounded-md transition-colors ${type === 'cost' ? 'bg-white dark:bg-zinc-900 text-slate-900 dark:text-white shadow-sm font-semibold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'}`}
                            >
                                {__('erp.cost_transactions') || 'Cost'}
                            </Link>
                            <Link
                                href="/admin/transactions?type=revenue"
                                className={`px-3 py-1 rounded-md transition-colors ${type === 'revenue' ? 'bg-white dark:bg-zinc-900 text-slate-900 dark:text-white shadow-sm font-semibold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'}`}
                            >
                                {__('general.revenue') || 'Revenue'}
                            </Link>
                        </nav>
                    </div>
                    <p className="text-xs text-slate-500 dark:text-zinc-400">{__(descriptionKey)}</p>
                </div>

                <div className="flex gap-2 flex-wrap items-center">
                    <Button asChild className="bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100">
                        <a href={`/admin/transactions/create?type=${primaryCreateType}${userParam}`}>
                            <Plus className="w-4 h-4 me-1.5" />
                            {__(primaryCreateLabelKey)}
                        </a>
                    </Button>
                    {showMoreMenu && (
                        <Button variant="outline" asChild className="border-slate-300 dark:border-zinc-700 hover:bg-slate-100 dark:hover:bg-zinc-800">
                            <a href={`/admin/transactions/create?type=${type === 'income' ? 'used' : 'send'}${userParam}`}>
                                {type === 'income' ? __('general.used') : __('general.send')}
                            </a>
                        </Button>
                    )}
                </div>
            </div>

            {/* Render any filteredUser card or custom slot */}
            {children?.({ handleSearch: (s) => applyFilters({ search: s }), handleSort })}

            {/* Summary KPI Strip */}
            {summary && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div className="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 p-3.5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-500 dark:text-zinc-400 mb-1">
                            <span className="text-xs font-medium">{__('general.total_transactions') || 'Matching Count'}</span>
                            <Hash className="w-3.5 h-3.5" />
                        </div>
                        <div className="text-lg font-bold font-mono text-slate-900 dark:text-zinc-100">
                            {summary.total_count.toLocaleString()}
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 p-3.5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-500 dark:text-zinc-400 mb-1">
                            <span className="text-xs font-medium">{__('general.volume') || 'Net Volume'}</span>
                            <Coins className="w-3.5 h-3.5" />
                        </div>
                        <div className="text-lg font-bold font-mono text-slate-900 dark:text-zinc-100">
                            {formatMoney(summary.total_business_amount, businessCurrency)}
                        </div>
                    </div>

                    {type === 'income' ? (
                        <>
                            <div className="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 p-3.5 shadow-sm">
                                <div className="flex items-center justify-between text-slate-500 dark:text-zinc-400 mb-1">
                                    <span className="text-xs font-medium">{__('general.received') || 'Inflow'}</span>
                                    <ArrowDownRight className="w-3.5 h-3.5 text-emerald-600" />
                                </div>
                                <div className="text-lg font-bold font-mono text-emerald-600">
                                    +{formatMoney(summary.positive_business_amount, businessCurrency)}
                                </div>
                            </div>

                            <div className="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 p-3.5 shadow-sm">
                                <div className="flex items-center justify-between text-slate-500 dark:text-zinc-400 mb-1">
                                    <span className="text-xs font-medium">{__('general.used') || 'Outflow'}</span>
                                    <ArrowUpRight className="w-3.5 h-3.5 text-rose-600" />
                                </div>
                                <div className="text-lg font-bold font-mono text-rose-600">
                                    -{formatMoney(summary.negative_business_amount, businessCurrency)}
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 p-3.5 shadow-sm col-span-2">
                            <div className="flex items-center justify-between text-slate-500 dark:text-zinc-400 mb-1">
                                <span className="text-xs font-medium">{__('erp.avg_cost') || 'Average Transaction'}</span>
                                <Coins className="w-3.5 h-3.5" />
                            </div>
                            <div className="text-lg font-bold font-mono text-slate-900 dark:text-zinc-100">
                                {formatMoney(summary.avg_business_amount, businessCurrency)}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Comprehensive Filter Toolbar */}
            <div className="mb-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 shadow-sm p-4 space-y-4">
                {/* Primary Search & Quick Actions Row */}
                <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    {/* Search Input */}
                    <form onSubmit={handleSearchSubmit} className="relative flex-1">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-zinc-500 pointer-events-none" />
                        <Input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            onBlur={() => applyFilters({ search: searchTerm.trim() })}
                            placeholder={__('general.search_transactions_hint') || 'Search ID (#123), description, client, or project...'}
                            className="ps-9 pe-9 h-9 text-xs bg-slate-50/50 dark:bg-zinc-950/50 border-slate-200 dark:border-zinc-800 focus-visible:ring-1 focus-visible:ring-slate-900 dark:focus-visible:ring-zinc-100"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={handleClearSearch}
                                className="absolute end-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 p-0.5"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        )}
                    </form>

                    {/* Quick Date Presets Dropdown */}
                    <div className="flex items-center gap-2 flex-wrap">
                        <div className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-zinc-400">
                            <Calendar className="w-3.5 h-3.5" />
                            <select
                                value={
                                    filters?.from_date && filters?.from_date === filters?.to_date
                                        ? 'today'
                                        : (filters?.from_date ? 'custom' : 'all')
                                }
                                onChange={(e) => handleDatePreset(e.target.value)}
                                className="h-9 rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-950/50 px-2.5 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-zinc-100"
                            >
                                <option value="all">{__('general.all_time') || 'All Time'}</option>
                                <option value="today">{__('general.today') || 'Today'}</option>
                                <option value="yesterday">{__('general.yesterday') || 'Yesterday'}</option>
                                <option value="this_week">{__('general.this_week') || 'This Week'}</option>
                                <option value="this_month">{__('general.this_month') || 'This Month'}</option>
                                <option value="last_month">{__('general.last_month') || 'Last Month'}</option>
                                <option value="this_year">{__('general.this_year') || 'This Year'}</option>
                            </select>
                        </div>

                        {/* Toggle Advanced Filters Button */}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                            className={`h-9 text-xs border-slate-200 dark:border-zinc-800 ${showAdvancedFilters ? 'bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white font-medium' : 'text-slate-600 dark:text-zinc-400'}`}
                        >
                            <SlidersHorizontal className="w-3.5 h-3.5 me-1.5" />
                            {__('general.filters') || 'Filters'}
                            {activeFilterCount > 0 && (
                                <span className="ms-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-900 dark:bg-white text-[10px] text-white dark:text-slate-900 font-bold">
                                    {activeFilterCount}
                                </span>
                            )}
                        </Button>

                        {/* Reset All Filters Button */}
                        {activeFilterCount > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={handleResetAll}
                                className="h-9 text-xs text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white"
                                title={__('general.reset_filters') || 'Reset All'}
                            >
                                <RotateCcw className="w-3.5 h-3.5 me-1" />
                                {__('general.reset') || 'Reset'}
                            </Button>
                        )}
                    </div>
                </div>

                {/* Expanded Advanced Filters Panel */}
                {showAdvancedFilters && (
                    <div className="pt-3 border-t border-slate-100 dark:border-zinc-800/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {/* Transaction Type Filter (for Income) */}
                        {type === 'income' && (
                            <div className="space-y-1">
                                <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                    {__('general.type') || 'Transaction Type'}
                                </label>
                                <select
                                    value={filters?.tx_type || ''}
                                    onChange={(e) => applyFilters({ tx_type: e.target.value })}
                                    className="w-full h-8 rounded-lg border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-2 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-zinc-100"
                                >
                                    <option value="">{__('general.all_types') || 'All Types'}</option>
                                    <option value="received">{__('general.received') || 'Received (Deposit)'}</option>
                                    <option value="earned">{__('general.earned') || 'Earned'}</option>
                                    <option value="used">{__('general.used') || 'Used (Paid)'}</option>
                                    <option value="refunded">{__('general.refunded') || 'Refunded'}</option>
                                    <option value="sent">{__('general.sent') || 'Sent'}</option>
                                </select>
                            </div>
                        )}

                        {/* Client / User Filter */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('general.client') || 'Client'}
                            </label>
                            <select
                                value={filters?.user || ''}
                                onChange={(e) => applyFilters({ user: e.target.value, project: '' })}
                                className="w-full h-8 rounded-lg border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-2 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-zinc-100 truncate"
                            >
                                <option value="">{__('general.all_clients') || 'All Clients'}</option>
                                {(filterOptions.users || []).map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name} ({u.email})
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Project Filter */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('erp.project') || 'Project'}
                            </label>
                            <select
                                value={filters?.project || ''}
                                onChange={(e) => applyFilters({ project: e.target.value })}
                                className="w-full h-8 rounded-lg border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-2 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-zinc-100 truncate"
                            >
                                <option value="">{__('general.all_projects') || 'All Projects'}</option>
                                {availableProjects.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.project_name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Currency Filter */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('general.currency') || 'Currency'}
                            </label>
                            <select
                                value={filters?.currency || ''}
                                onChange={(e) => applyFilters({ currency: e.target.value })}
                                className="w-full h-8 rounded-lg border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-2 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-zinc-100"
                            >
                                <option value="">{__('general.all_currencies') || 'All Currencies'}</option>
                                {(filterOptions.currencies || []).map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.currency} ({c.symbol})
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Date From */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('general.from_date') || 'From Date'}
                            </label>
                            <Input
                                type="date"
                                value={fromDate}
                                onChange={(e) => {
                                    setFromDate(e.target.value);
                                    applyFilters({ from_date: e.target.value });
                                }}
                                className="h-8 text-xs bg-white dark:bg-zinc-950 border-slate-200 dark:border-zinc-800"
                            />
                        </div>

                        {/* Date To */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('general.to_date') || 'To Date'}
                            </label>
                            <Input
                                type="date"
                                value={toDate}
                                onChange={(e) => {
                                    setToDate(e.target.value);
                                    applyFilters({ to_date: e.target.value });
                                }}
                                className="h-8 text-xs bg-white dark:bg-zinc-950 border-slate-200 dark:border-zinc-800"
                            />
                        </div>

                        {/* Min Amount */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('general.min_amount') || 'Min Amount'}
                            </label>
                            <Input
                                type="number"
                                step="any"
                                value={minAmount}
                                onChange={(e) => setMinAmount(e.target.value)}
                                onBlur={() => applyFilters({ min_amount: minAmount })}
                                placeholder="0.00"
                                className="h-8 text-xs bg-white dark:bg-zinc-950 border-slate-200 dark:border-zinc-800"
                            />
                        </div>

                        {/* Max Amount */}
                        <div className="space-y-1">
                            <label className="text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {__('general.max_amount') || 'Max Amount'}
                            </label>
                            <Input
                                type="number"
                                step="any"
                                value={maxAmount}
                                onChange={(e) => setMaxAmount(e.target.value)}
                                onBlur={() => applyFilters({ max_amount: maxAmount })}
                                placeholder="10,000.00"
                                className="h-8 text-xs bg-white dark:bg-zinc-950 border-slate-200 dark:border-zinc-800"
                            />
                        </div>
                    </div>
                )}

                {/* Active Filter Chips */}
                {activeFilterCount > 0 && (
                    <div className="pt-2 border-t border-slate-100 dark:border-zinc-800/80 flex items-center gap-1.5 flex-wrap text-xs">
                        <span className="text-slate-400 dark:text-zinc-500 text-[11px] me-1">
                            {__('general.active_filters') || 'Active filters:'}
                        </span>

                        {filters?.search && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                <Search className="w-3 h-3 text-slate-400" />
                                "{filters.search}"
                                <button
                                    onClick={() => {
                                        setSearchTerm('');
                                        applyFilters({ search: '' });
                                    }}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        {filters?.tx_type && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                {__('general.type')}: {filters.tx_type}
                                <button
                                    onClick={() => applyFilters({ tx_type: '' })}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        {filters?.user && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                <UserIcon className="w-3 h-3 text-slate-400" />
                                {filterOptions.users?.find((u) => String(u.id) === String(filters.user))?.name || `User #${filters.user}`}
                                <button
                                    onClick={() => applyFilters({ user: '', project: '' })}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        {filters?.project && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                <FolderKanban className="w-3 h-3 text-slate-400" />
                                {filterOptions.projects?.find((p) => String(p.id) === String(filters.project))?.project_name || `Project #${filters.project}`}
                                <button
                                    onClick={() => applyFilters({ project: '' })}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        {filters?.currency && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                <Coins className="w-3 h-3 text-slate-400" />
                                {filterOptions.currencies?.find((c) => String(c.id) === String(filters.currency))?.currency || `Currency #${filters.currency}`}
                                <button
                                    onClick={() => applyFilters({ currency: '' })}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        {(filters?.from_date || filters?.to_date) && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                <Calendar className="w-3 h-3 text-slate-400" />
                                {filters.from_date || '...'} → {filters.to_date || '...'}
                                <button
                                    onClick={() => {
                                        setFromDate('');
                                        setToDate('');
                                        applyFilters({ from_date: '', to_date: '' });
                                    }}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        {(filters?.min_amount || filters?.max_amount) && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-200 border border-slate-200 dark:border-zinc-700">
                                {__('general.amount')}: {filters.min_amount || '0'} → {filters.max_amount || '∞'}
                                <button
                                    onClick={() => {
                                        setMinAmount('');
                                        setMaxAmount('');
                                        applyFilters({ min_amount: '', max_amount: '' });
                                    }}
                                    className="hover:text-slate-950 dark:hover:text-white"
                                >
                                    <X className="w-3 h-3" />
                                </button>
                            </span>
                        )}

                        <button
                            onClick={handleResetAll}
                            className="text-[11px] text-slate-500 hover:text-slate-900 dark:hover:text-white underline ms-1"
                        >
                            {__('general.clear_all') || 'Clear all'}
                        </button>
                    </div>
                )}
            </div>

            {/* Data Table */}
            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={transactions.data}
                    pagination={transactions}
                    filters={filters}
                    onSort={handleSort}
                    onPageChange={(page) => applyFilters({ page })}
                    onPerPageChange={(per_page) => applyFilters({ per_page })}
                    emptyTitle={__(emptyTitleKey)}
                    emptyDescription={__(emptyDescriptionKey)}
                />
            </div>
        </AdminSidebarLayout>
    );
}

export default TransactionsPage;