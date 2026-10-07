import React, { useState, useMemo, useCallback } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    Search,
    Filter,
    ArrowUpRight,
    FileText,
    Paperclip,
    X,
    ChevronLeft,
    ChevronRight,
    Sparkles,
    CheckCircle2,
    Clock,
    ArrowRight,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { SimpleSelect } from '@/Components/ui/SimpleSelect';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import { BoardCategoryChip } from '@/Pages/Client/Projects/Components/BoardCategoryChip';
import { __ } from '@/lib/i18n';
import { formatDate } from '@/lib/utils';

interface BoardItem {
    id: number;
    project_id: number;
    project_name: string;
    client_name: string;
    lane: 'backlog' | 'in_progress' | 'review' | 'done';
    for_date: string | null;
    type: 'note' | 'task' | 'todo' | 'report' | 'file';
    itemable_id: number;
    title: string;
    description: string | null;
    category_name: string | null;
    category_color: string | null;
}

interface DropdownProject {
    id: number;
    project_name: string;
}

interface DropdownClient {
    id: number;
    name: string;
}

interface Stats {
    total: number;
    notes: number;
    tasks: number;
    reports: number;
    todos: number;
    files: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search?: string | null;
    project_id?: string | number | null;
    client_id?: string | number | null;
    lane?: string | null;
    type?: string | null;
    date_from?: string | null;
    date_to?: string | null;
    per_page?: number;
}

interface Props {
    items: BoardItem[];
    projects: DropdownProject[];
    clients: DropdownClient[];
    stats: Stats;
    filters: Filters;
    pagination: Pagination;
}

const ALL = 'all';

const LANE_COLORS: Record<string, string> = {
    backlog: 'bg-slate-100 text-slate-700 border-slate-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-100',
    review: 'bg-amber-50 text-amber-700 border-amber-100',
    done: 'bg-green-50 text-green-700 border-green-100',
};

const TYPE_ICONS: Record<string, React.ComponentType<any>> = {
    note: Sparkles,
    task: CheckCircle2,
    todo: Clock,
    report: FileText,
    file: Paperclip,
};

const TYPE_LABEL_KEYS: Record<string, string> = {
    note: 'general.note',
    task: 'general.task',
    todo: 'admin.board_explorer_type_todo',
    report: 'general.report',
    file: 'admin.board_explorer_type_file',
};

export default function BoardExplorer({ items, projects, clients, stats, filters, pagination }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [projectId, setProjectId] = useState(String(filters.project_id || ALL));
    const [clientId, setClientId] = useState(String(filters.client_id || ALL));
    const [lane, setLane] = useState(filters.lane || ALL);
    const [type, setType] = useState(filters.type || ALL);
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [perPage, setPerPage] = useState(String(filters.per_page || 25));

    const filterPayload = useMemo(() => ({
        search,
        project_id: projectId === ALL ? '' : projectId,
        client_id: clientId === ALL ? '' : clientId,
        lane: lane === ALL ? '' : lane,
        type: type === ALL ? '' : type,
        date_from: dateFrom,
        date_to: dateTo,
        per_page: perPage,
    }), [search, projectId, clientId, lane, type, dateFrom, dateTo, perPage]);

    const submit = useCallback((overrides: Record<string, any> = {}) => {
        const merged = { ...filterPayload, ...overrides };
        router.get(route('admin.tasks.board-explorer'), merged, {
            preserveState: true,
            replace: true,
        });
    }, [filterPayload]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        submit();
    };

    const handleClientChange = (val: string) => {
        setClientId(val);
        submit({ client_id: val });
    };

    const handleClear = () => {
        setSearch('');
        setProjectId(ALL);
        setClientId(ALL);
        setLane(ALL);
        setType(ALL);
        setDateFrom('');
        setDateTo('');
        setPerPage('25');
        router.get(route('admin.tasks.board-explorer'));
    };

    const handlePageChange = (page: number) => {
        submit({ page });
    };

    const isAnyFilterActive = Boolean(
        search ||
        (projectId !== ALL && projectId !== '') ||
        (clientId !== ALL && clientId !== '') ||
        (lane !== ALL && lane !== '') ||
        (type !== ALL && type !== '') ||
        dateFrom ||
        dateTo
    );

    return (
        <AdminSidebarLayout title={__('general.board_explorer')} header={__('general.board_explorer')}>
            <Head title={__('general.board_explorer')} />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <LayoutDashboard className="h-6 w-6 text-slate-900" />
                            {__('general.board_explorer')}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('general.board_explorer_subtitle')}
                        </p>
                    </div>
                </div>

                {/* Stats row */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    {[
                        { label: __('general.total_items'), value: stats.total, color: 'text-slate-900', bg: 'bg-slate-50', icon: LayoutDashboard },
                        { label: __('general.tasks'), value: stats.tasks, color: 'text-sky-700', bg: 'bg-sky-50', icon: CheckCircle2 },
                        { label: __('general.todos'), value: stats.todos, color: 'text-indigo-700', bg: 'bg-indigo-50', icon: Clock },
                        { label: __('general.notes'), value: stats.notes, color: 'text-purple-700', bg: 'bg-purple-50', icon: Sparkles },
                        { label: __('general.reports'), value: stats.reports, color: 'text-amber-700', bg: 'bg-amber-50', icon: FileText },
                        { label: __('general.files'), value: stats.files, color: 'text-emerald-700', bg: 'bg-emerald-50', icon: Paperclip },
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

                {/* Search and Filters Card */}
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

                                {/* Client Filter */}
                                <div className="w-[180px]">
                                    <ClientAutocomplete
                                        value={clientId}
                                        onChange={handleClientChange}
                                        searchEndpoint={route('admin.projects.search-clients')}
                                        placeholder={__('general.all_clients')}
                                    />
                                </div>

                                {/* Project Filter */}
                                <div className="w-[180px]">
                                    <SimpleSelect
                                        value={projectId}
                                        onChange={(v) => { setProjectId(v); submit({ project_id: v === ALL ? '' : v }); }}
                                        placeholder={__('general.all_projects')}
                                        options={[
                                            { value: ALL, label: __('general.all_projects') },
                                            ...projects.map(p => ({ value: String(p.id), label: p.project_name })),
                                        ]}
                                    />
                                </div>

                                {/* Lane Filter */}
                                <div className="w-[150px]">
                                    <SimpleSelect
                                        value={lane}
                                        onChange={(v) => { setLane(v); submit({ lane: v === ALL ? '' : v }); }}
                                        placeholder={__('general.all_lanes')}
                                        options={[
                                            { value: ALL, label: __('general.all_lanes') },
                                            { value: 'backlog', label: __('general.backlog') },
                                            { value: 'in_progress', label: __('general.in_progress') },
                                            { value: 'review', label: __('general.review') },
                                            { value: 'done', label: __('general.done') },
                                        ]}
                                    />
                                </div>

                                {/* Type Filter */}
                                <div className="w-[130px]">
                                    <SimpleSelect
                                        value={type}
                                        onChange={(v) => { setType(v); submit({ type: v === ALL ? '' : v }); }}
                                        placeholder={__('general.all_types')}
                                        options={[
                                            { value: ALL, label: __('general.all_types') },
                                            { value: 'task', label: __('general.tasks') },
                                            { value: 'todo', label: __('general.todos') },
                                            { value: 'note', label: __('general.notes') },
                                            { value: 'report', label: __('general.reports') },
                                            { value: 'file', label: __('general.files') },
                                        ]}
                                    />
                                </div>

                                {/* Date range */}
                                <div className="flex items-center gap-1.5">
                                    <Input
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                        onBlur={() => submit()}
                                        className="h-9 w-[140px] text-xs"
                                        aria-label={__('general.from')}
                                    />
                                    <ArrowRight className="h-3.5 w-3.5 text-slate-400 rtl:rotate-180" aria-hidden="true" />
                                    <Input
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                        onBlur={() => submit()}
                                        className="h-9 w-[140px] text-xs"
                                        aria-label={__('general.to')}
                                    />
                                </div>

                                {/* Search Bar */}
                                <div className="flex-1 min-w-[200px] relative">
                                    <Search className="absolute start-3 top-2.5 h-4 w-4 text-slate-400" />
                                    <Input
                                        type="text"
                                        placeholder={__('general.search_board_placeholder')}
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

                                <div className="ms-auto w-[80px]">
                                    <SimpleSelect
                                        value={perPage}
                                        onChange={(v) => { setPerPage(v); submit({ per_page: v }); }}
                                        placeholder="25"
                                        options={[
                                            { value: '10', label: '10' },
                                            { value: '25', label: '25' },
                                            { value: '50', label: '50' },
                                            { value: '100', label: '100' },
                                        ]}
                                    />
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table list */}
                {items.length === 0 ? (
                    <Card className="rounded-xl border border-dashed border-slate-300 bg-white shadow-sm">
                        <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                            <LayoutDashboard className="h-12 w-12 text-slate-300 mb-4" />
                            <h3 className="font-semibold text-slate-700 text-sm">
                                {__('general.no_items_found')}
                            </h3>
                            <p className="text-xs text-slate-400 max-w-xs mt-1">
                                {isAnyFilterActive
                                    ? __('general.no_items_match_filters')
                                    : __('admin.board_explorer_empty')}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="overflow-x-auto">
                                <table className="w-full text-start text-xs border-collapse">
                                    <thead>
                                        <tr className="border-b border-slate-100 bg-slate-50 text-[10px] font-semibold text-slate-400 uppercase tracking-wider text-start">
                                            <th className="px-4 py-3 text-start font-semibold">{__('general.card')}</th>
                                            <th className="px-4 py-3 text-start font-semibold">{__('general.project')}</th>
                                            <th className="px-4 py-3 text-start font-semibold">{__('general.client')}</th>
                                            <th className="px-4 py-3 text-start font-semibold">{__('general.lane')}</th>
                                            <th className="px-4 py-3 text-start font-semibold">{__('general.category')}</th>
                                            <th className="px-4 py-3 text-start font-semibold">{__('general.date')}</th>
                                            <th className="px-4 py-3 text-end font-semibold">{__('general.actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 text-slate-700">
                                        {items.map((item) => {
                                            const Icon = TYPE_ICONS[item.type] || Sparkles;
                                            const label = __(TYPE_LABEL_KEYS[item.type] ?? 'general.note');
                                            return (
                                                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                                                    <td className="px-4 py-4">
                                                        <div className="flex items-start gap-2.5 max-w-[320px]">
                                                            <div className="p-1.5 rounded-lg bg-slate-100 text-slate-600 shrink-0 mt-0.5" title={label}>
                                                                <Icon className="h-3.5 w-3.5" />
                                                            </div>
                                                            <div className="min-w-0 space-y-0.5">
                                                                <div className="font-semibold text-slate-900 truncate font-mono text-xs uppercase" title={item.title}>
                                                                    {item.title}
                                                                </div>
                                                                {item.description && (
                                                                    <div className="text-[11px] text-slate-400 line-clamp-1" title={item.description}>
                                                                        {item.description}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4 font-medium text-slate-900 whitespace-nowrap">
                                                        {item.project_name}
                                                    </td>
                                                    <td className="px-4 py-4 text-slate-500 whitespace-nowrap">
                                                        {item.client_name}
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border ${LANE_COLORS[item.lane] || 'bg-slate-100 text-slate-700'}`}>
                                                            {__(`general.${item.lane}`)}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap">
                                                        {item.category_name ? (
                                                            <BoardCategoryChip category={{ id: 0, name: item.category_name, color: item.category_color }} />
                                                        ) : (
                                                            <span className="text-slate-300">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4 text-slate-600 font-medium whitespace-nowrap">
                                                        {item.for_date ? formatDate(item.for_date) : <span className="text-slate-300">{__('admin.board_explorer_unscheduled')}</span>}
                                                    </td>
                                                    <td className="px-4 py-4 text-end whitespace-nowrap">
                                                        {item.for_date && (
                                                            <Button asChild variant="ghost" size="sm" className="h-8 hover:text-black">
                                                                <a
                                                                    href={route('client.projects.calendar.date', {
                                                                        project: item.project_id,
                                                                        date: item.for_date,
                                                                        card_type: item.type,
                                                                        card_id: item.itemable_id,
                                                                    })}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                >
                                                                    <span>{__('admin.board_explorer_go_to_day_board')}</span>
                                                                    <ArrowUpRight className="h-3 w-3 ms-1" />
                                                                </a>
                                                            </Button>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Pagination footer */}
                        {pagination.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-slate-100 pt-4 px-1">
                                <span className="text-xs text-slate-500">
                                    {__('admin.pending_tasks_page_info', { current: pagination.current_page, last: pagination.last_page, total: pagination.total })}
                                </span>
                                <div className="flex items-center gap-1">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={pagination.current_page === 1}
                                        onClick={() => handlePageChange(pagination.current_page - 1)}
                                        className="h-8 text-xs"
                                    >
                                        <ChevronLeft className="h-3.5 w-3.5 me-1 rtl:rotate-180" />
                                        {__('general.previous')}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={pagination.current_page === pagination.last_page}
                                        onClick={() => handlePageChange(pagination.current_page + 1)}
                                        className="h-8 text-xs"
                                    >
                                        {__('admin.board_explorer_next')}
                                        <ChevronRight className="h-3.5 w-3.5 ms-1 rtl:rotate-180" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
