import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ListTodo,
    Search,
    Circle,
    CheckCircle2,
    Calendar,
    DollarSign,
    Pause,
    User,
    ChevronRight,
    Filter,
    ClipboardList,
    Briefcase,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import axios from 'axios';
import { __ } from '@/lib/i18n';

interface TodoItem {
    id: number;
    title: string;
    description: string | null;
    priority: 'low' | 'normal' | 'high' | 'urgent';
    priority_color: string | null;
    paused: boolean;
    is_paid: boolean;
    cost: number | null;
    cost_currency: string | null;
    start_at: string | null;
    end_at: string | null;
    tags: string[];
    created_at: string;
}

interface TaskData {
    id: number;
    task_name: string;
    status: string;
    todos: TodoItem[];
}

interface ClientData {
    client: { id: number; name: string; email: string; avatar_url?: string };
    tasks: TaskData[];
}

interface DropdownClient {
    id: number;
    name: string;
}

interface Props {
    arrangedClients: ClientData[];
    clients: DropdownClient[];
    filters: { search?: string; client_id?: string; tenant_id?: string };
    stats: { total_active_todos: number; total_clients: number };
    auth: { user: any };
}

const PRIORITY_CONFIG: Record<string, { label: string; cls: string }> = {
    urgent: { label: 'Urgent', cls: 'bg-rose-100 text-rose-700 border border-rose-200' },
    high:   { label: 'High',   cls: 'bg-amber-100 text-amber-700 border border-amber-200' },
    normal: { label: 'Normal', cls: 'bg-blue-100 text-blue-700 border border-blue-200' },
    low:    { label: 'Low',    cls: 'bg-slate-100 text-slate-500 border border-slate-200' },
};

const STATUS_CONFIG: Record<string, string> = {
    open:        'bg-slate-100 text-slate-600',
    in_progress: 'bg-blue-100 text-blue-700',
    review:      'bg-indigo-100 text-indigo-700',
    completed:   'bg-emerald-100 text-emerald-700',
};

export default function AsList({ arrangedClients, clients, filters, stats, auth }: Props) {
    const initialClientId = filters.client_id || filters.tenant_id || '';
    const [search, setSearch]       = useState(filters.search || '');
    const [clientFilter, setClient] = useState(initialClientId);
    const [completedIds, setCompleted] = useState<Set<number>>(new Set());

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.tasks.as_list'), { search, client_id: clientFilter || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleClientChange = (val: string) => {
        setClient(val);
        router.get(route('admin.tasks.as_list'), { search, client_id: val || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleMarkComplete = async (todoId: number) => {
        try {
            await axios.post(`/admin/tasks/todos/${todoId}/complete`, { completed: true });
            setCompleted(prev => new Set([...prev, todoId]));
        } catch {
            // silent — optimistic already handled
        }
    };

    const totalClientsWithTasks = arrangedClients.length;
    const totalTasks = arrangedClients.reduce((sum, c) => sum + c.tasks.length, 0);

    return (
        <AdminSidebarLayout title={__('general.tasks_list')} header="Tasks List">
            <Head title={__('general.all_active_tasks_admin')} />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <ListTodo className="h-6 w-6 text-indigo-600" />{__('general.active_tasks_platform_clients')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.platform_wide_view_of_pending_checklist_items_grouped_by_client_task_board')}</p>
                    </div>
                </div>

                {/* KPI Row */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {[
                        { label: 'Active Todos',     value: stats.total_active_todos, color: 'text-indigo-600',  bg: 'bg-indigo-50',  icon: ClipboardList },
                        { label: 'Total Clients',    value: stats.total_clients,      color: 'text-violet-600', bg: 'bg-violet-50',  icon: User },
                        { label: 'Active Clients',   value: totalClientsWithTasks,    color: 'text-emerald-600', bg: 'bg-emerald-50', icon: User },
                        { label: 'Task Boards',      value: totalTasks,               color: 'text-amber-600',  bg: 'bg-amber-50',   icon: Briefcase },
                    ].map(({ label, value, color, bg, icon: Icon }) => (
                        <Card key={label} className="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <CardContent className="p-5 flex items-center justify-between">
                                <div className="space-y-1">
                                    <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{label}</span>
                                    <h3 className={`text-2xl font-bold ${color}`}>{value}</h3>
                                </div>
                                <div className={`p-3 ${bg} ${color} rounded-lg`}>
                                    <Icon className="h-6 w-6" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <CardContent className="p-4">
                        <form onSubmit={handleSearch} className="flex flex-wrap gap-3 items-center">
                            <div className="flex items-center gap-2 text-slate-500 text-xs">
                                <Filter className="h-3.5 w-3.5" /> Filters:
                            </div>

                            {/* Client filter */}
                            <select
                                value={clientFilter}
                                onChange={e => handleClientChange(e.target.value)}
                                className="h-9 rounded-md border border-slate-200 bg-white px-3 text-xs text-slate-700 shadow-none focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="">{__('general.all_clients')}</option>
                                {clients.map(c => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>

                            {/* Search */}
                            <div className="relative flex-1 max-w-xs">
                                <Search className="absolute start-3 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder={__('general.search_todo_title_or_client_name')}
                                    value={search}
                                    onChange={e => setSearch(e.target.value)}
                                    className="ps-9 h-9 shadow-none border-slate-200 text-xs focus-visible:ring-indigo-500"
                                />
                            </div>

                            <Button type="submit" size="sm" className="h-9 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-none border-0 text-xs">
                                Apply
                            </Button>

                            {(search || clientFilter) && (
                                <button
                                    type="button"
                                    onClick={() => { setSearch(''); setClient(''); router.get(route('admin.tasks.as_list')); }}
                                    className="text-xs text-slate-400 hover:text-slate-600 underline"
                                >
                                    Clear
                                </button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Main Content */}
                {arrangedClients.length === 0 ? (
                    <Card className="rounded-xl border border-dashed border-slate-300 bg-white shadow-sm">
                        <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                            <ListTodo className="h-12 w-12 text-slate-300 mb-4" />
                            <h3 className="font-semibold text-slate-700 text-sm">{__('general.no_active_todos_found')}</h3>
                            <p className="text-xs text-slate-400 max-w-xs mt-1">
                                {search || clientFilter
                                    ? 'No items match your current filters. Try clearing them.'
                                    : 'All checklist items have been completed or are paused across all platform clients.'}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-8">
                        {arrangedClients.map((clientGroup) => (
                            <div key={clientGroup.client.id}>
                                {/* Client Header */}
                                <div className="flex items-center gap-3 mb-4">
                                    <Avatar className="h-8 w-8 rounded-lg border border-slate-200">
                                        <AvatarImage src={clientGroup.client.avatar_url} alt={clientGroup.client.name} />
                                        <AvatarFallback className="rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm">
                                            {clientGroup.client.name.charAt(0).toUpperCase()}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <h2 className="text-base font-bold text-slate-900">{clientGroup.client.name}</h2>
                                        <span className="text-xs text-slate-400">{clientGroup.client.email}</span>
                                    </div>
                                    <Link
                                        href={route('admin.users.show', clientGroup.client.id)}
                                        className="ms-auto text-xs text-indigo-600 hover:underline flex items-center gap-0.5"
                                    >{__('general.view_client_profile')}<ChevronRight className="h-3.5 w-3.5" />
                                    </Link>
                                </div>

                                {/* Tasks within Client */}
                                <div className="space-y-5 ps-11">
                                    {clientGroup.tasks.map((task) => (
                                        <Card key={task.id} className="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                                            {/* Task header */}
                                            <div className="bg-slate-50 border-b border-slate-100 px-4 py-2.5 flex items-center justify-between gap-2">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold text-sm text-slate-800">📌 {task.task_name}</span>
                                                    <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${STATUS_CONFIG[task.status] || 'bg-slate-100 text-slate-500'}`}>
                                                        {task.status.replace('_', ' ')}
                                                    </span>
                                                </div>
                                                <Link
                                                    href={`/admin/tasks/client-tasks?client_id=${clientGroup.client.id}`}
                                                    className="text-xs text-indigo-600 hover:underline flex items-center gap-0.5 whitespace-nowrap"
                                                >{__('general.open_client_focus_board')}<ChevronRight className="h-3.5 w-3.5" />
                                                </Link>
                                            </div>

                                            {/* Todo items */}
                                            <div className="divide-y divide-slate-50">
                                                {task.todos.map((todo) => {
                                                    const isDone = completedIds.has(todo.id);
                                                    const pCfg = PRIORITY_CONFIG[todo.priority] || PRIORITY_CONFIG.low;
                                                    return (
                                                        <div
                                                            key={todo.id}
                                                            className={`px-4 py-3 flex items-start gap-3 group transition-colors ${isDone ? 'opacity-50 bg-slate-50' : 'hover:bg-slate-50/60'}`}
                                                        >
                                                            {/* Complete button */}
                                                            <button
                                                                onClick={() => handleMarkComplete(todo.id)}
                                                                disabled={isDone}
                                                                className="mt-0.5 text-slate-300 hover:text-emerald-500 transition-colors flex-shrink-0 disabled:cursor-not-allowed"
                                                                title={isDone ? 'Completed' : 'Mark as complete'}
                                                            >
                                                                {isDone
                                                                    ? <CheckCircle2 className="h-4 w-4 text-emerald-500 fill-emerald-50" />
                                                                    : <Circle className="h-4 w-4" />
                                                                }
                                                            </button>

                                                            {/* Content */}
                                                            <div className="flex-1 min-w-0 space-y-1">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <span className={`text-sm font-medium text-slate-800 ${isDone ? 'line-through text-slate-400' : ''}`}>
                                                                        {todo.title}
                                                                    </span>

                                                                    {/* Priority */}
                                                                    <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ${pCfg.cls}`}>
                                                                        {pCfg.label}
                                                                    </span>

                                                                    {todo.paused && (
                                                                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-0.5">
                                                                            <Pause className="h-2 w-2" /> Paused
                                                                        </span>
                                                                    )}

                                                                    {todo.is_paid && (
                                                                        <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                                            Paid
                                                                        </span>
                                                                    )}

                                                                    {(todo.tags || []).slice(0, 3).map((tag, i) => (
                                                                        <span key={i} className="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-500 border border-slate-200">
                                                                            {tag}
                                                                        </span>
                                                                    ))}
                                                                </div>

                                                                {todo.description && (
                                                                    <p className="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                                                        {todo.description}
                                                                    </p>
                                                                )}

                                                                {/* Meta line */}
                                                                <div className="flex flex-wrap items-center gap-3 pt-0.5 text-[11px] text-slate-400 font-medium">
                                                                    {todo.cost && (
                                                                        <span className="flex items-center gap-0.5 text-emerald-600 font-semibold">
                                                                            <DollarSign className="h-3 w-3" />
                                                                            {todo.cost} {todo.cost_currency}
                                                                        </span>
                                                                    )}
                                                                    {(todo.start_at || todo.end_at) && (
                                                                        <span className="flex items-center gap-1">
                                                                            <Calendar className="h-3 w-3" />
                                                                            {todo.start_at ? new Date(todo.start_at).toLocaleDateString() : '—'}
                                                                            {' → '}
                                                                            {todo.end_at ? new Date(todo.end_at).toLocaleDateString() : '—'}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </Card>
                                    ))}
                                </div>

                                {/* Separator */}
                                <div className="border-b border-slate-100 mt-6" />
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
