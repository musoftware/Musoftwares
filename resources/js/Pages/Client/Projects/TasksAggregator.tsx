import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ListTodo,
    FolderKanban,
    Flag,
    CalendarClock,
    CheckCircle2,
    Circle,
    AlertCircle,
    Filter,
    ChevronRight,
    Layers,
    ArrowLeft,
    Coffee,
    Sparkles,
    ExternalLink,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface ProjectOption {
    id: number;
    name: string;
    archived: boolean;
}

type Item =
    | {
          kind: 'task';
          id: number;
          title: string;
          description?: string | null;
          project_id: number;
          project_name: string;
          priority?: string | null;
          due_date?: string | null;
          progress: number | null;
          todo_total: number;
          todo_done: number;
          completed: boolean;
      }
    | {
          kind: 'todo';
          id: number;
          title: string;
          description?: string | null;
          project_id: number;
          project_name: string;
          priority?: string | null;
          due_date?: string | null;
          completed: boolean;
          parent_task_id?: number | null;
          parent_task_name?: string | null;
          start_at?: string | null;
          end_at?: string | null;
      };

interface Props {
    projects: ProjectOption[];
    items: Item[];
    filters: { project_id: number | null; completed: boolean; with_archived: boolean };
    stats: { total: number; tasks: number; todos: number; completed: number };
}

interface Bucket {
    key: string;
    label: string;
    hint?: string;
    tone: 'danger' | 'today' | 'soon' | 'later' | 'none';
    items: Item[];
}

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700',
    urgent: 'bg-rose-100 text-rose-700',
    normal: 'bg-slate-100 text-slate-600',
    low: 'bg-slate-100 text-slate-600',
};

const TONE_BORDER: Record<Bucket['tone'], string> = {
    danger: 'border-rose-200 bg-rose-50/40',
    today: 'border-indigo-200 bg-indigo-50/40',
    soon: 'border-sky-200 bg-sky-50/30',
    later: 'border-slate-200 bg-white',
    none: 'border-dashed border-slate-200 bg-slate-50/40',
};

const TONE_LABEL: Record<Bucket['tone'], string> = {
    danger: 'text-rose-700',
    today: 'text-indigo-700',
    soon: 'text-sky-700',
    later: 'text-slate-700',
    none: 'text-slate-500',
};

function startOfDay(d: Date) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);
    return x;
}

function addDays(d: Date, n: number) {
    const x = new Date(d);
    x.setDate(x.getDate() + n);
    return x;
}

function sameDay(a: Date, b: Date) {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

function diffDays(from: Date, to: Date) {
    const ms = startOfDay(to).getTime() - startOfDay(from).getTime();
    return Math.round(ms / (1000 * 60 * 60 * 24));
}

function bucketize(items: Item[]): Bucket[] {
    const today = startOfDay(new Date());
    const tomorrow = addDays(today, 1);
    const endOfWeek = addDays(today, 7);
    const endOfMonth = addDays(today, 30);

    const buckets: Bucket[] = [
        { key: 'overdue', label: __('general.bucket_overdue'), tone: 'danger', items: [] },
        { key: 'today', label: __('general.bucket_today'), tone: 'today', items: [] },
        { key: 'tomorrow', label: __('general.bucket_tomorrow'), tone: 'today', items: [] },
        { key: 'week', label: __('general.bucket_this_week'), hint: __('general.bucket_next_7_days'), tone: 'soon', items: [] },
        { key: 'later', label: __('general.bucket_later'), hint: __('general.bucket_after_week'), tone: 'later', items: [] },
        { key: 'nodate', label: __('general.bucket_no_date'), tone: 'none', items: [] },
    ];

    const byKey: Record<string, Bucket> = Object.fromEntries(buckets.map((b) => [b.key, b]));

    for (const it of items) {
        if (!it.due_date) {
            byKey.nodate.items.push(it);
            continue;
        }
        const d = startOfDay(new Date(it.due_date));
        if (d.getTime() < today.getTime()) {
            byKey.overdue.items.push(it);
        } else if (sameDay(d, today)) {
            byKey.today.items.push(it);
        } else if (sameDay(d, tomorrow)) {
            byKey.tomorrow.items.push(it);
        } else if (d.getTime() < endOfWeek.getTime()) {
            byKey.week.items.push(it);
        } else if (d.getTime() < endOfMonth.getTime()) {
            byKey.later.items.push(it);
        } else {
            byKey.later.items.push(it);
        }
    }

    return buckets.filter((b) => b.items.length > 0);
}

export default function TasksAggregator({ projects = [], items = [], filters, stats }: Props) {
    const [projectId, setProjectId] = useState<string>(filters.project_id ? String(filters.project_id) : 'all');
    const [showCompleted, setShowCompleted] = useState<boolean>(filters.completed);
    const [openItemKey, setOpenItemKey] = useState<string | null>(null);
    const triggerRef = useState<{ current: HTMLElement | null }>({ current: null })[0];

    const openItem = openItemKey
        ? items.find((i) => `${i.kind}-${i.id}` === openItemKey) ?? null
        : null;

    const handleRowOpen = (item: Item, el: HTMLElement) => {
        triggerRef.current = el;
        setOpenItemKey(`${item.kind}-${item.id}`);
        const url = new URL(window.location.href);
        url.searchParams.set('open', `${item.kind}-${item.id}`);
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
    };

    const handleClose = () => {
        const prev = triggerRef.current;
        setOpenItemKey(null);
        prev?.focus();
        const url = new URL(window.location.href);
        if (url.searchParams.has('open')) {
            url.searchParams.delete('open');
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }
    };

    const buckets = useMemo(() => bucketize(items), [items]);

    const applyFilters = (next: { project_id?: string; completed?: boolean }) => {
        const params: Record<string, string> = {};
        const pid = next.project_id !== undefined ? next.project_id : projectId;
        const comp = next.completed !== undefined ? next.completed : showCompleted;

        if (pid && pid !== 'all') params.project_id = pid;
        if (comp) params.completed = '1';
        if (filters.with_archived) params.with_archived = '1';

        router.get(route('client.projects.all-tasks'), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.all_tasks')} />
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <Link
                        href={route('client.projects.index')}
                        className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                    >
                        <ArrowLeft className="h-4 w-4" /> {__('general.projects')}
                    </Link>
                    <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight text-slate-900">
                        <ListTodo className="h-7 w-7 text-slate-400" />
                        {__('general.all_tasks')}
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">{__('general.all_tasks_desc')}</p>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <MetricCard label={__('general.total')} value={stats.total} icon={Layers} />
                    <MetricCard label={__('general.tasks')} value={stats.tasks} icon={ListTodo} />
                    <MetricCard label={__('general.todos')} value={stats.todos} icon={CheckCircle2} />
                    <MetricCard label={__('general.completed')} value={stats.completed} icon={CheckCircle2} />
                </div>

                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-1 items-center gap-2">
                            <Filter className="h-4 w-4 text-slate-400" />
                            <span className="text-sm font-medium text-slate-700">{__('general.filter')}</span>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Select
                                value={projectId}
                                onValueChange={(v) => {
                                    const next = v ?? 'all';
                                    setProjectId(next);
                                    applyFilters({ project_id: next });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-[220px]">
                                    <SelectValue placeholder={__('general.all_projects') ?? ''} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{__('general.all_projects')}</SelectItem>
                                    {projects.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>
                                            {p.name}
                                            {p.archived ? ` (${__('general.archived').toLowerCase()})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                variant={showCompleted ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => {
                                    const next = !showCompleted;
                                    setShowCompleted(next);
                                    applyFilters({ completed: next });
                                }}
                                className="rounded-lg"
                            >
                                {showCompleted ? __('general.hide_completed') : __('general.show_completed')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {buckets.length === 0 ? (
                    <EmptyState
                        icon={projectId === 'all' ? Coffee : Sparkles}
                        tone="friendly"
                        title={__('general.all_tasks_empty_title')}
                        description={__('general.all_tasks_empty_desc')}
                    />
                ) : (
                    <div className="space-y-8">
                        {buckets.map((bucket) => (
                            <section key={bucket.key}>
                                <div className="mb-3 flex items-baseline justify-between gap-2">
                                    <h2 className={`flex items-center gap-2 text-sm font-semibold uppercase tracking-wider ${TONE_LABEL[bucket.tone]}`}>
                                        {bucket.tone === 'danger' ? (
                                            <AlertCircle className="h-4 w-4" />
                                        ) : (
                                            <CalendarClock className="h-4 w-4" />
                                        )}
                                        {bucket.label}
                                        <span className="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">
                                            {bucket.items.length}
                                        </span>
                                    </h2>
                                    {bucket.hint && <span className="text-xs text-slate-400">{bucket.hint}</span>}
                                </div>

                                <div className="space-y-2">
                                    {bucket.items.map((it) => (
                                        <TaskRow
                                            key={`${it.kind}-${it.id}`}
                                            item={it}
                                            onOpen={handleRowOpen}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>

            <Sheet open={openItemKey != null} onOpenChange={(o) => (o ? null : handleClose())}>
                <SheetContent
                    side="right"
                    className="w-full gap-0 overflow-y-auto p-0 sm:max-w-md"
                >
                    {openItem && (
                        <>
                            <SheetHeader className="border-b border-slate-100 bg-slate-50/50 p-6 text-start">
                                <SheetTitle className="text-lg font-semibold text-slate-900">
                                    {openItem.title}
                                </SheetTitle>
                                <SheetDescription className="mt-1 text-sm text-slate-500">
                                    {openItem.kind === 'task' ? __('general.task') : __('general.todo')} ·{' '}
                                    {openItem.project_name}
                                </SheetDescription>
                            </SheetHeader>
                            <div className="space-y-4 p-6">
                                {openItem.priority && (
                                    <div className="flex items-center gap-2 text-xs">
                                        <Flag className="h-3.5 w-3.5 text-slate-400" />
                                        <span
                                            className={`rounded-full px-2 py-0.5 font-semibold capitalize ${
                                                PRIORITY_STYLES[openItem.priority] ?? PRIORITY_STYLES.normal
                                            }`}
                                        >
                                            {openItem.priority}
                                        </span>
                                    </div>
                                )}
                                {openItem.due_date && (
                                    <div className="flex items-center gap-2 text-xs text-slate-500">
                                        <CalendarClock className="h-3.5 w-3.5" /> {formatDate(openItem.due_date)}
                                    </div>
                                )}
                                {openItem.description ? (
                                    <p className="whitespace-pre-wrap text-sm text-slate-700">{openItem.description}</p>
                                ) : (
                                    <p className="text-sm text-slate-400">{__('general.no_description')}</p>
                                )}
                                <Link
                                    href={route('client.projects.show', openItem.project_id)}
                                    className="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-900"
                                >
                                    <ExternalLink className="h-3.5 w-3.5" /> {__('general.open_project')}
                                </Link>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AuthenticatedLayout>
    );
}

function TaskRow({ item, onOpen }: { item: Item; onOpen: (item: Item, el: HTMLElement) => void }) {
    const projectHref = route('client.projects.show', item.project_id);
    const today = startOfDay(new Date());
    const isOverdue = !!item.due_date && startOfDay(new Date(item.due_date)).getTime() < today.getTime();
    const overdueDays = item.due_date ? diffDays(new Date(item.due_date), today) : 0;

    return (
        <Card className={`rounded-xl border transition-shadow hover:shadow-sm ${isOverdue ? 'border-rose-200' : 'border-slate-200'}`}>
            <CardContent className="flex items-start gap-4 p-4">
                {item.completed ? (
                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" />
                ) : (
                    <Circle className="mt-0.5 h-5 w-5 shrink-0 text-slate-300" />
                )}

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href={projectHref}
                            className="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                        >
                            <FolderKanban className="h-3 w-3" />
                            {item.project_name}
                        </Link>
                        {item.kind === 'task' ? (
                            <span className="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-700">
                                <ListTodo className="h-3 w-3" /> {__('general.task')}
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-violet-700">
                                <CheckCircle2 className="h-3 w-3" /> {__('general.todo')}
                                {item.parent_task_name && (
                                    <span className="ms-1 font-normal text-slate-500">/ {item.parent_task_name}</span>
                                )}
                            </span>
                        )}
                        {item.priority && (
                            <span
                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize ${
                                    PRIORITY_STYLES[item.priority] ?? PRIORITY_STYLES.normal
                                }`}
                            >
                                <Flag className="h-3 w-3" />
                                {item.priority}
                            </span>
                        )}
                    </div>

                    <p className={`mt-1.5 font-medium ${item.completed ? 'text-slate-400 line-through' : 'text-slate-900'}`}>
                        {item.title}
                    </p>

                    {item.description && (
                        <p className="mt-1 line-clamp-2 text-sm text-slate-500">{item.description}</p>
                    )}

                    {item.kind === 'task' && item.todo_total > 0 && (
                        <div className="mt-2 flex items-center gap-2 text-xs text-slate-500">
                            <div className="h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className={`h-full ${item.completed ? 'bg-emerald-500' : 'bg-indigo-500'}`}
                                    style={{ width: `${item.progress ?? 0}%` }}
                                />
                            </div>
                            <span>
                                {item.todo_done}/{item.todo_total} {__('general.todos').toLowerCase()}
                            </span>
                        </div>
                    )}
                </div>

                <div className="flex shrink-0 flex-col items-end gap-1.5">
                    {item.due_date && (
                        <span
                            className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs ${
                                isOverdue
                                    ? 'bg-rose-50 font-semibold text-rose-700'
                                    : 'bg-slate-50 text-slate-600'
                            }`}
                        >
                            <CalendarClock className="h-3.5 w-3.5" />
                            {formatDate(item.due_date)}
                            {isOverdue && overdueDays > 0 && (
                                <span className="ms-1 text-[10px]">-{overdueDays}d</span>
                            )}
                        </span>
                    )}
                    <Link
                        href={projectHref}
                        className="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700"
                    >
                        {__('general.open_project')}
                        <ChevronRight className="h-3 w-3" />
                    </Link>
                    <button
                        type="button"
                        onClick={(e) => onOpen(item, e.currentTarget)}
                        className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                        aria-label={__('general.open')}
                    >
                        {__('general.open')}
                    </button>
                </div>
            </CardContent>
        </Card>
    );
}
