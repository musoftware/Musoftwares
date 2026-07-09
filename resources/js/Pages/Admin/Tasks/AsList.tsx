import React, { useState, useMemo, useCallback } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ListTodo,
    Search,
    Circle,
    CheckCircle2,
    Calendar,
    Pause,
    User,
    ChevronRight,
    Filter,
    ClipboardList,
    Briefcase,
    Download,
    AlertTriangle,
    Clock,
    X,
    ChevronLeft,
    ChevronRight as ChevronRightIcon,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import axios from 'axios';
import { __ } from '@/lib/i18n';
import { formatMoney, formatDate } from '@/lib/utils';

interface TodoItem {
    id: number;
    task_id: number | null;
    title: string;
    description: string | null;
    priority: 'low' | 'normal' | 'high' | 'urgent';
    priority_color: string | null;
    paused: boolean;
    is_paid: boolean;
    cost: number | null;
    cost_currency: string | null;
    cost_currency_id: number | null;
    start_at: string | null;
    end_at: string | null;
    tags: string[];
    created_at: string | null;
    is_orphan: boolean;
    is_overdue: boolean;
    stale: boolean;
}

interface TaskData {
    id: number | null;
    task_name: string;
    status: string;
    is_orphan: boolean;
    todos: TodoItem[];
}

interface ClientData {
    client: { id: number; name: string; email: string; avatar_url: string | null };
    tasks: TaskData[];
}

interface DropdownClient {
    id: number;
    name: string;
}

interface Stats {
    total_active_todos: number;
    total_in_boards: number;
    total_active_clients: number;
    total_task_boards: number;
    overdue_count: number;
    orphan_count: number;
    total_clients: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search?: string | null;
    client_id?: string | number | null;
    priority?: string | null;
    is_paid?: string | null;
    paused?: string | null;
    date_from?: string | null;
    date_to?: string | null;
    sort?: string;
    per_page?: number;
}

interface Props {
    arrangedClients: ClientData[];
    clients: DropdownClient[];
    filters: Filters;
    pagination: Pagination;
    stats: Stats;
    auth: { user: any };
}

const ALL = '__all__';

const PRIORITY_LABEL: Record<string, string> = {
    urgent: 'general.urgent',
    high:   'general.high',
    normal: 'general.normal',
    low:    'general.low',
};

const PRIORITY_CLS: Record<string, string> = {
    urgent: 'bg-red-100 text-red-800 border border-red-200',
    high:   'bg-yellow-100 text-yellow-700 border border-yellow-200',
    normal: 'bg-slate-50 text-slate-700 border border-slate-200',
    low:    'bg-slate-100 text-slate-500 border border-slate-200',
};

const STATUS_CLS: Record<string, string> = {
    open:       'bg-slate-100 text-slate-600',
    in_progress:'bg-blue-100 text-blue-800',
    review:     'bg-yellow-100 text-yellow-800',
    completed:  'bg-green-100 text-green-800',
    orphaned:   'bg-orange-100 text-orange-800',
};

const STATUS_LABEL: Record<string, string> = {
    open: 'general.open',
    in_progress: 'general.in_progress',
    review: 'general.review',
    completed: 'general.completed',
    orphaned: 'general.orphaned',
};

const SORT_LABEL: Record<string, string> = {
    created_desc: 'Newest first',
    created_asc:  'general.oldest_first',
    priority:     'general.highest_priority',
    due_asc:      'general.soonest_due',
    due_desc:     'general.latest_due',
    client:       'general.name_asc',
};

function priorityLabel(p: string): string {
    return __(PRIORITY_LABEL[p] ?? 'general.normal');
}

function statusLabel(s: string): string {
    if (STATUS_LABEL[s]) return __(STATUS_LABEL[s]);
    return s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function buildQuery(currentFilters: Record<string, any>): string {
    const params = new URLSearchParams();
    Object.entries(currentFilters).forEach(([k, v]) => {
        if (v === null || v === undefined || v === '' || v === ALL) return;
        params.set(k, String(v));
    });
    const s = params.toString();
    return s ? `?${s}` : '';
}

export default function AsList({ arrangedClients, clients, filters, pagination, stats }: Props) {
    const initialClientId = filters.client_id ? String(filters.client_id) : '';
    const [search, setSearch] = useState(filters.search || '');
    const [clientFilter, setClient] = useState(initialClientId);
    const [priority, setPriority] = useState(filters.priority || ALL);
    const [isPaid, setIsPaid] = useState(filters.is_paid ?? ALL);
    const [paused, setPaused] = useState(filters.paused ?? ALL);
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [perPage, setPerPage] = useState(String(filters.per_page || 50));
    const [sort, setSort] = useState(filters.sort || 'created_desc');
    const [completedIds, setCompleted] = useState<Set<number>>(new Set());
    const [banner, setBanner] = useState<{ type: 'error' | 'success'; text: string } | null>(null);

    const pageProps = usePage().props as any;
    const flashSuccess = pageProps?.flash?.success ?? null;
    const flashError = pageProps?.flash?.error ?? null;
    const validationErrors: Record<string, string> = pageProps?.errors ?? {};

    const filterPayload = useMemo(
        () => ({
            search,
            client_id: clientFilter,
            priority: priority === ALL ? '' : priority,
            is_paid: isPaid === ALL ? '' : isPaid,
            paused: paused === ALL ? '' : paused,
            date_from: dateFrom,
            date_to: dateTo,
            sort,
            per_page: perPage,
        }),
        [search, clientFilter, priority, isPaid, paused, dateFrom, dateTo, sort, perPage],
    );

    const submit = useCallback(
        (overrides: Record<string, any> = {}) => {
            const merged = { ...filterPayload, ...overrides };
            router.get(route('admin.tasks.as_list'), merged, {
                preserveState: true,
                replace: true,
            });
        },
        [filterPayload],
    );

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        submit();
    };

    const handleClientChange = (val: string) => {
        setClient(val);
        submit({ client_id: val });
    };

    const handleClear = () => {
        setSearch('');
        setClient('');
        setPriority(ALL);
        setIsPaid(ALL);
        setPaused(ALL);
        setDateFrom('');
        setDateTo('');
        setSort('created_desc');
        setPerPage('50');
        router.get(route('admin.tasks.as_list'));
    };

    const handleMarkComplete = async (todoId: number) => {
        setBanner(null);
        try {
            await axios.post(route('admin.tasks.todos.complete', todoId), { completed: true });
            setCompleted((prev) => new Set([...prev, todoId]));
        } catch (err: any) {
            setBanner({
                type: 'error',
                text: err?.response?.data?.message || __('general.task_status_updated_successfully'),
            });
        }
    };

    const handleBulkComplete = async (ids: number[]) => {
        if (ids.length === 0) return;
        setBanner(null);
        try {
            const { data } = await axios.post(route('admin.tasks.todos.bulk-complete'), {
                todo_ids: ids,
                completed: true,
            });
            setCompleted((prev) => {
                const next = new Set(prev);
                ids.forEach((id) => next.add(id));
                return next;
            });
            setBanner({ type: 'success', text: data?.message ?? __('general.task_status_updated_successfully') });
            router.reload({ only: ['arrangedClients', 'pagination', 'stats'] });
        } catch (err: any) {
            setBanner({
                type: 'error',
                text: err?.response?.data?.message || __('general.task_status_updated_successfully'),
            });
        }
    };

    const exportUrl = useMemo(
        () => route('admin.tasks.as_list.export') + buildQuery(filterPayload),
        [filterPayload],
    );

    const allVisibleTodoIds = useMemo(() => {
        const ids: number[] = [];
        arrangedClients.forEach((c) => c.tasks.forEach((t) => t.todos.forEach((td) => {
            if (!completedIds.has(td.id)) ids.push(td.id);
        })));
        return ids;
    }, [arrangedClients, completedIds]);

    const isAnyFilterActive =
        Boolean(search || clientFilter || dateFrom || dateTo) ||
        (priority !== ALL) || (isPaid !== ALL) || (paused !== ALL);

    return (
        <AdminSidebarLayout title={__('general.tasks_list')} header={__('general.tasks_list')}>
            <Head title={__('general.all_active_tasks_admin')} />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <ListTodo className="h-6 w-6 text-slate-900" />
                            {__('general.active_tasks_platform_clients')}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('general.platform_wide_view_of_pending_checklist_items_grouped_by_client_task_board')}
                        </p>
                    </div>
                    <Button asChild variant="outline" size="sm" className="h-9 text-xs">
                        <a href={exportUrl} download>
                            <Download className="h-3.5 w-3.5 me-1.5" />
                            {__('general.export_to_csv')}
                        </a>
                    </Button>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    {[
                        { label: __('general.active_todos'), value: stats.total_active_todos, color: 'text-slate-900',  bg: 'bg-slate-50',   icon: ClipboardList },
                        { label: __('general.active_clients'), value: stats.total_active_clients, color: 'text-green-700',  bg: 'bg-green-50', icon: User },
                        { label: __('general.task_boards'), value: stats.total_task_boards, color: 'text-blue-700',  bg: 'bg-blue-50',  icon: Briefcase },
                        { label: __('general.overdue_count'), value: stats.overdue_count, color: 'text-red-700',    bg: 'bg-red-50',    icon: AlertTriangle },
                        { label: __('general.orphan_count'), value: stats.orphan_count, color: 'text-orange-700', bg: 'bg-orange-50', icon: Clock },
                        { label: __('general.total_clients'), value: stats.total_clients, color: 'text-slate-700',  bg: 'bg-slate-50',   icon: User },
                    ].map(({ label, value, color, bg, icon: Icon }) => (
                        <Card key={label} className="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <CardContent className="p-4 flex items-center justify-between">
                                <div className="space-y-1 min-w-0">
                                    <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate block">
                                        {label}
                                    </span>
                                    <h3 className={`text-xl font-bold ${color}`}>{value}</h3>
                                </div>
                                <div className={`p-2 ${bg} ${color} rounded-lg flex-shrink-0`}>
                                    <Icon className="h-4 w-4" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <CardContent className="p-4">
                        <form onSubmit={handleSearch}>
                            <div className="flex flex-wrap items-center gap-3">
                                <div className="flex items-center gap-1.5 text-slate-500 text-xs">
                                    <Filter className="h-3.5 w-3.5" />
                                    <span className="font-semibold uppercase tracking-wider">
                                        {__('general.filters')}
                                    </span>
                                </div>

                                <div className="w-[200px]">
                                    <ClientAutocomplete
                                        value={clientFilter}
                                        onChange={handleClientChange}
                                        searchEndpoint={route('admin.projects.search-clients')}
                                        placeholder={__('general.all_clients')}
                                    />
                                </div>

                                <div className="w-[150px]">
                                    <Select value={priority} onValueChange={(v) => { if (v === null) return; setPriority(v); submit({ priority: v === ALL ? '' : v }); }}>
                                        <SelectTrigger className="h-9 text-xs"><SelectValue placeholder={__('general.all_priorities')} /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>{__('general.all_priorities')}</SelectItem>
                                            <SelectItem value="urgent">{__('general.urgent')}</SelectItem>
                                            <SelectItem value="high">{__('general.high')}</SelectItem>
                                            <SelectItem value="normal">{__('general.normal')}</SelectItem>
                                            <SelectItem value="low">{__('general.low')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="w-[130px]">
                                    <Select value={isPaid} onValueChange={(v) => { if (v === null) return; setIsPaid(v); submit({ is_paid: v === ALL ? '' : v }); }}>
                                        <SelectTrigger className="h-9 text-xs"><SelectValue placeholder={__('general.all_paid')} /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>{__('general.all_paid')}</SelectItem>
                                            <SelectItem value="1">{__('general.is_paid')}</SelectItem>
                                            <SelectItem value="0">{__('general.is_unpaid')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="w-[130px]">
                                    <Select value={paused} onValueChange={(v) => { if (v === null) return; setPaused(v); submit({ paused: v === ALL ? '' : v }); }}>
                                        <SelectTrigger className="h-9 text-xs"><SelectValue placeholder={__('general.all_paused')} /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>{__('general.all_paused')}</SelectItem>
                                            <SelectItem value="1">{__('general.paused')}</SelectItem>
                                            <SelectItem value="0">{__('general.active')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center gap-1.5">
                                    <Input
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                        onBlur={() => submit()}
                                        className="h-9 w-[150px] text-xs"
                                        aria-label={__('general.from')}
                                    />
                                    <span className="text-xs text-slate-400">→</span>
                                    <Input
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                        onBlur={() => submit()}
                                        className="h-9 w-[150px] text-xs"
                                        aria-label={__('general.to')}
                                    />
                                </div>

                                <div className="flex-1 min-w-[180px] relative">
                                    <Search className="absolute start-3 top-2.5 h-4 w-4 text-slate-400" />
                                    <Input
                                        type="text"
                                        placeholder={__('general.search_todo_title_or_client_name')}
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="ps-9 h-9 shadow-none border-slate-200 text-xs focus-visible:ring-slate-900"
                                    />
                                </div>

                                <Button type="submit" variant="outline" size="sm" className="h-9 text-xs">
                                    {__('general.apply_filters')}
                                </Button>

                                {isAnyFilterActive && (
                                    <button
                                        type="button"
                                        onClick={handleClear}
                                        className="text-xs text-slate-400 hover:text-slate-600 underline flex items-center gap-1"
                                    >
                                        <X className="h-3 w-3" />
                                        {__('general.clear_filters')}
                                    </button>
                                )}

                                <div className="ms-auto w-[170px]">
                                    <Select value={sort} onValueChange={(v) => { if (v === null) return; setSort(v); submit({ sort: v }); }}>
                                        <SelectTrigger className="h-9 text-xs"><SelectValue placeholder={__('general.sort_by')} /></SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(SORT_LABEL).map(([k, v]) => (
                                                <SelectItem key={k} value={k}>{v.startsWith('general.') ? __(v) : v}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="w-[100px]">
                                    <Select value={perPage} onValueChange={(v) => { if (v === null) return; setPerPage(v); submit({ per_page: v }); }}>
                                        <SelectTrigger className="h-9 text-xs"><SelectValue placeholder="50" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10</SelectItem>
                                            <SelectItem value="25">25</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                            <SelectItem value="100">100</SelectItem>
                                            <SelectItem value="200">200</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {(banner || flashSuccess || flashError || Object.keys(validationErrors).length > 0) && (
                    <div className={`rounded-md border px-4 py-2 text-xs ${
                        banner?.type === 'error' || flashError || Object.keys(validationErrors).length > 0
                            ? 'border-red-200 bg-red-50 text-red-700'
                            : 'border-green-200 bg-green-50 text-green-700'
                    }`}>
                        {banner?.text || flashSuccess || flashError || Object.values(validationErrors)[0]}
                    </div>
                )}

                {allVisibleTodoIds.length > 0 && (
                    <BulkBar visibleIds={allVisibleTodoIds} onComplete={handleBulkComplete} />
                )}

                {arrangedClients.length === 0 ? (
                    <Card className="rounded-xl border border-dashed border-slate-300 bg-white shadow-sm">
                        <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                            <ListTodo className="h-12 w-12 text-slate-300 mb-4" />
                            <h3 className="font-semibold text-slate-700 text-sm">
                                {__('general.no_active_todos_found')}
                            </h3>
                            <p className="text-xs text-slate-400 max-w-xs mt-1">
                                {isAnyFilterActive
                                    ? __('general.no_items_match_filters')
                                    : 'All checklist items have been completed or are paused across all platform clients.'}
                            </p>
                            {isAnyFilterActive && (
                                <Button variant="outline" size="sm" className="mt-4 text-xs" onClick={handleClear}>
                                    {__('general.clear_filters')}
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-8">
                        {arrangedClients.map((clientGroup) => (
                            <ClientGroup
                                key={clientGroup.client.id}
                                group={clientGroup}
                                completedIds={completedIds}
                                onComplete={handleMarkComplete}
                            />
                        ))}
                    </div>
                )}

                {pagination.last_page > 1 && (
                    <div className="flex items-center justify-between pt-2">
                        <p className="text-xs text-slate-500">
                            {__('general.n_results', { count: pagination.total })} · {__('general.page_of', { current: pagination.current_page, last: pagination.last_page })}
                        </p>
                        <div className="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.current_page <= 1}
                                onClick={() => submit({ page: pagination.current_page - 1 })}
                                aria-label={__('general.prev_month')}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.current_page >= pagination.last_page}
                                onClick={() => submit({ page: pagination.current_page + 1 })}
                                aria-label={__('general.next_month')}
                            >
                                <ChevronRightIcon className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}

function BulkBar({
    visibleIds,
    onComplete,
}: {
    visibleIds: number[];
    onComplete: (ids: number[]) => void;
}) {
    const [selected, setSelected] = useState<Set<number>>(new Set());

    const toggleAll = () => {
        if (selected.size === visibleIds.length) setSelected(new Set());
        else setSelected(new Set(visibleIds));
    };

    return (
        <div className="sticky top-0 z-10 bg-slate-50 border border-slate-200 rounded-md px-3 py-2 flex items-center gap-3 text-xs">
            <Checkbox
                checked={selected.size > 0 && selected.size === visibleIds.length}
                onCheckedChange={toggleAll}
                aria-label={__('general.select_all')}
            />
            <span className="font-semibold text-slate-600">
                {selected.size > 0
                    ? __('general.items_selected', { count: selected.size })
                    : __('general.select_all')}
            </span>
            <div className="ms-auto flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={selected.size === 0}
                    onClick={() => onComplete(Array.from(selected))}
                    className="h-7 text-xs"
                >
                    <CheckCircle2 className="h-3.5 w-3.5 me-1" />
                    {__('general.mark_complete')}
                </Button>
            </div>
        </div>
    );
}

function ClientGroup({
    group,
    completedIds,
    onComplete,
}: {
    group: ClientData;
    completedIds: Set<number>;
    onComplete: (id: number) => void;
}) {
    return (
        <div>
            <div className="flex items-center gap-3 mb-4">
                <Avatar className="h-8 w-8 rounded-lg border border-slate-200">
                    <AvatarImage src={group.client.avatar_url || undefined} alt={group.client.name} />
                    <AvatarFallback className="rounded-lg bg-slate-50 text-slate-900 font-bold text-sm">
                        {group.client.name.charAt(0).toUpperCase()}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                    <h2 className="text-base font-bold text-slate-900 truncate">
                        {group.client.name}
                    </h2>
                    <span className="text-xs text-slate-400 truncate block">
                        {group.client.email}
                    </span>
                </div>
                <Link
                    href={route('admin.users.show', group.client.id)}
                    className="ms-auto text-xs text-slate-900 hover:underline flex items-center gap-0.5"
                >
                    {__('general.view_client_profile')}
                    <ChevronRight className="h-3.5 w-3.5" />
                </Link>
            </div>

            <div className="space-y-5 ps-11">
                {group.tasks.map((task) => (
                    <TaskCard
                        key={task.id ?? 'orphan'}
                        task={task}
                        clientId={group.client.id}
                        completedIds={completedIds}
                        onComplete={onComplete}
                    />
                ))}
            </div>

            <div className="border-b border-slate-100 mt-6" />
        </div>
    );
}

function TaskCard({
    task,
    clientId,
    completedIds,
    onComplete,
}: {
    task: TaskData;
    clientId: number;
    completedIds: Set<number>;
    onComplete: (id: number) => void;
}) {
    return (
        <Card className="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div className="bg-slate-50 border-b border-slate-100 px-4 py-2.5 flex items-center justify-between gap-2">
                <div className="flex items-center gap-2 min-w-0">
                    <span className="font-semibold text-sm text-slate-800 truncate">
                        📌 {task.task_name}
                    </span>
                    <span
                        className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase whitespace-nowrap ${
                            STATUS_CLS[task.status] || 'bg-slate-100 text-slate-500'
                        }`}
                    >
                        {statusLabel(task.status)}
                    </span>
                </div>
                {task.id !== null && (
                    <Link
                        href={route('admin.tasks.client-tasks', { client_id: clientId })}
                        className="text-xs text-slate-900 hover:underline flex items-center gap-0.5 whitespace-nowrap"
                    >
                        {__('general.open_client_focus_board')}
                        <ChevronRight className="h-3.5 w-3.5" />
                    </Link>
                )}
            </div>
            <div className="divide-y divide-slate-50">
                {task.todos.map((todo) => (
                    <TodoRow
                        key={todo.id}
                        todo={todo}
                        isDone={completedIds.has(todo.id)}
                        onComplete={onComplete}
                    />
                ))}
            </div>
        </Card>
    );
}

function TodoRow({
    todo,
    isDone,
    onComplete,
}: {
    todo: TodoItem;
    isDone: boolean;
    onComplete: (id: number) => void;
}) {
    const rowCls = [
        'px-4 py-3 flex items-start gap-3 group transition-colors',
        isDone ? 'opacity-50 bg-slate-50' : 'hover:bg-slate-50/60',
        todo.is_overdue && !isDone ? 'bg-red-50/30' : '',
        todo.stale && !isDone && !todo.is_overdue ? 'bg-amber-50/30' : '',
    ].filter(Boolean).join(' ');

    return (
        <div className={rowCls}>
            <button
                onClick={() => onComplete(todo.id)}
                disabled={isDone}
                className="mt-0.5 text-slate-300 hover:text-slate-900 transition-colors flex-shrink-0 disabled:cursor-not-allowed"
                title={isDone ? __('general.completed') : __('general.mark_as_complete')}
                aria-label={isDone ? __('general.completed') : __('general.mark_as_complete')}
            >
                {isDone ? (
                    <CheckCircle2 className="h-4 w-4 text-green-700 fill-green-50" />
                ) : (
                    <Circle className="h-4 w-4" />
                )}
            </button>

            <div className="flex-1 min-w-0 space-y-1">
                <div className="flex flex-wrap items-center gap-1.5">
                    <span
                        className={`text-sm font-medium text-slate-800 ${
                            isDone ? 'line-through text-slate-400' : ''
                        }`}
                    >
                        {todo.title}
                    </span>

                    <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ${PRIORITY_CLS[todo.priority] || PRIORITY_CLS.low}`}>
                        {priorityLabel(todo.priority)}
                    </span>

                    {todo.is_orphan && (
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-orange-100 text-orange-800 border border-orange-200">
                            {__('general.orphaned')}
                        </span>
                    )}

                    {todo.is_overdue && !isDone && (
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-800 border border-red-200">
                            {__('general.overdue')}
                        </span>
                    )}

                    {todo.stale && !isDone && !todo.is_overdue && (
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200">
                            7d+
                        </span>
                    )}

                    {todo.paused && (
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-yellow-100 text-yellow-800 border border-yellow-200 flex items-center gap-0.5">
                            <Pause className="h-2 w-2" /> {__('general.paused')}
                        </span>
                    )}

                    {todo.is_paid && (
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-green-100 text-green-800 border border-green-200">
                            {__('general.paid')}
                        </span>
                    )}

                    {(todo.tags || []).slice(0, 3).map((tag, i) => (
                        <span
                            key={i}
                            className="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-500 border border-slate-200"
                        >
                            {tag}
                        </span>
                    ))}
                </div>

                {todo.description && (
                    <p className="text-xs text-slate-500 leading-relaxed line-clamp-2">
                        {todo.description}
                    </p>
                )}

                <div className="flex flex-wrap items-center gap-3 pt-0.5 text-[11px] text-slate-400 font-medium">
                    {todo.cost !== null && todo.cost > 0 && (
                        <span className="flex items-center gap-0.5 text-slate-900 font-semibold">
                            {formatMoney(todo.cost, todo.cost_currency ?? todo.cost_currency_id ?? null)}
                        </span>
                    )}
                    {(todo.start_at || todo.end_at) && (
                        <span className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />
                            {todo.start_at ? formatDate(todo.start_at) : '—'}
                            {' → '}
                            {todo.end_at ? formatDate(todo.end_at) : '—'}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}
