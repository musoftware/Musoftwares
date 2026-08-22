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
    Layers,
    ArrowLeft,
    Coffee,
    Sparkles,
    ExternalLink,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
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
    high: 'bg-rose-50 text-rose-700 border-rose-200/60',
    urgent: 'bg-rose-50 text-rose-700 border-rose-200/60',
    normal: 'bg-[#f5f5f7] text-[#1d1d1f]/70 border-black/5',
    low: 'bg-[#f5f5f7] text-[#1d1d1f]/70 border-black/5',
};

const TONE_LABEL: Record<Bucket['tone'], string> = {
    danger: 'text-rose-600',
    today: 'text-[#0071e3]',
    soon: 'text-sky-600',
    later: 'text-[#1d1d1f]/70',
    none: 'text-[#1d1d1f]/50',
};

function startOfDay(d: Date): Date {
    const next = new Date(d);
    next.setHours(0, 0, 0, 0);
    return next;
}

function diffDays(a: Date, b: Date): number {
    const ms = startOfDay(a).getTime() - startOfDay(b).getTime();
    return Math.round(ms / (1000 * 60 * 60 * 24));
}

function bucketize(items: Item[]): Bucket[] {
    const today = startOfDay(new Date());

    const buckets: Record<Bucket['tone'], Bucket> = {
        danger: { key: 'overdue', label: __('general.overdue'), tone: 'danger', items: [] },
        today: { key: 'today', label: __('general.today'), tone: 'today', items: [] },
        soon: { key: 'next_7_days', label: __('general.next_7_days'), tone: 'soon', items: [] },
        later: { key: 'later', label: __('general.later'), tone: 'later', items: [] },
        none: { key: 'no_due_date', label: __('general.no_due_date'), tone: 'none', items: [] },
    };

    for (const it of items) {
        if (it.completed) {
            buckets.later.items.push(it);
            continue;
        }

        const dateStr = it.due_date;
        if (!dateStr) {
            buckets.none.items.push(it);
            continue;
        }

        const d = new Date(dateStr);
        if (isNaN(d.getTime())) {
            buckets.none.items.push(it);
            continue;
        }

        const days = diffDays(d, today);
        if (days < 0) {
            buckets.danger.items.push(it);
        } else if (days === 0) {
            buckets.today.items.push(it);
        } else if (days <= 7) {
            buckets.soon.items.push(it);
        } else {
            buckets.later.items.push(it);
        }
    }

    return [
        buckets.danger,
        buckets.today,
        buckets.soon,
        buckets.later,
        buckets.none,
    ].filter((b) => b.items.length > 0);
}

export default function TasksAggregator({ projects, items, filters, stats }: Props) {
    const [projectId, setProjectId] = useState<string>(
        filters.project_id != null ? String(filters.project_id) : 'all'
    );
    const [showCompleted, setShowCompleted] = useState<boolean>(filters.completed);
    const [openItemKey, setOpenItemKey] = useState<string | null>(null);

    const openItem = useMemo(() => {
        if (!openItemKey) return null;
        const [kind, idStr] = openItemKey.split('-');
        const id = Number(idStr);
        return items.find((it) => it.kind === kind && it.id === id) ?? null;
    }, [openItemKey, items]);

    const handleRowOpen = (it: Item) => {
        setOpenItemKey(`${it.kind}-${it.id}`);
    };

    const handleClose = () => {
        setOpenItemKey(null);
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
            <Head title={`${__('general.all_tasks')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href={route('client.projects.index')}
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.projects')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.all_tasks')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('general.all_tasks_desc')}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* 4-Pillar Summary Bento */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                        <div className="bg-white border border-black/5 rounded-[20px] p-5 shadow-sm">
                            <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                {__('general.total')}
                            </span>
                            <span className="text-2xl sm:text-3xl font-bold text-[#1d1d1f] font-sans">
                                {stats.total}
                            </span>
                        </div>
                        <div className="bg-white border border-black/5 rounded-[20px] p-5 shadow-sm">
                            <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                {__('general.tasks')}
                            </span>
                            <span className="text-2xl sm:text-3xl font-bold text-[#0071e3] font-sans">
                                {stats.tasks}
                            </span>
                        </div>
                        <div className="bg-white border border-black/5 rounded-[20px] p-5 shadow-sm">
                            <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                {__('general.todos')}
                            </span>
                            <span className="text-2xl sm:text-3xl font-bold text-amber-600 font-sans">
                                {stats.todos}
                            </span>
                        </div>
                        <div className="bg-white border border-black/5 rounded-[20px] p-5 shadow-sm">
                            <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block mb-1">
                                {__('general.completed')}
                            </span>
                            <span className="text-2xl sm:text-3xl font-bold text-emerald-600 font-sans">
                                {stats.completed}
                            </span>
                        </div>
                    </div>

                    {/* Filter Bar */}
                    <div className="bg-white border border-black/5 rounded-[20px] p-4 shadow-sm flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            <Filter className="h-4 w-4 text-[#0071e3]" />
                            <span className="text-xs font-bold text-[#1d1d1f] uppercase tracking-wider font-mono">
                                Filter Workspaces
                            </span>
                        </div>

                        <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                            <Select
                                value={projectId}
                                onValueChange={(v) => {
                                    const next = v ?? 'all';
                                    setProjectId(next);
                                    applyFilters({ project_id: next });
                                }}
                            >
                                <SelectTrigger className="w-full sm:w-[220px] h-10 rounded-xl bg-white border-black/10 text-xs font-semibold text-[#1d1d1f]">
                                    <SelectValue placeholder={__('general.all_projects') ?? ''} />
                                </SelectTrigger>
                                <SelectContent className="rounded-xl border-black/10 shadow-lg">
                                    <SelectItem value="all">{__('general.all_projects')}</SelectItem>
                                    {projects.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>
                                            {p.name}
                                            {p.archived ? ` (${__('general.archived').toLowerCase()})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <button
                                type="button"
                                onClick={() => {
                                    const next = !showCompleted;
                                    setShowCompleted(next);
                                    applyFilters({ completed: next });
                                }}
                                className={`px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer ${
                                    showCompleted
                                        ? 'bg-[#1d1d1f] text-white shadow-xs'
                                        : 'bg-[#f5f5f7] border border-black/5 text-[#1d1d1f]/70 hover:bg-black/5'
                                }`}
                            >
                                {showCompleted ? __('general.hide_completed') : __('general.show_completed')}
                            </button>
                        </div>
                    </div>

                    {/* Task Buckets */}
                    {buckets.length === 0 ? (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm max-w-xl mx-auto">
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] mx-auto mb-4">
                                <Sparkles className="w-7 h-7" />
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                {__('general.all_tasks_empty_title')}
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 max-w-md mx-auto mt-1.5 leading-relaxed">
                                {__('general.all_tasks_empty_desc')}
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-8">
                            {buckets.map((bucket) => (
                                <section key={bucket.key} className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <h2 className={`flex items-center gap-2 text-xs font-bold uppercase tracking-wider font-mono ${TONE_LABEL[bucket.tone]}`}>
                                            {bucket.tone === 'danger' ? (
                                                <AlertCircle className="h-4 w-4" />
                                            ) : (
                                                <CalendarClock className="h-4 w-4" />
                                            )}
                                            {bucket.label}
                                            <span className="rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-[#1d1d1f]/60 border border-black/5 shadow-2xs">
                                                {bucket.items.length}
                                            </span>
                                        </h2>
                                        {bucket.hint && <span className="text-[11px] text-[#1d1d1f]/40">{bucket.hint}</span>}
                                    </div>

                                    <div className="space-y-2.5">
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

            </div>

            {/* Item Details Sheet */}
            <Sheet open={openItemKey != null} onOpenChange={(o) => (o ? null : handleClose())}>
                <SheetContent
                    side="right"
                    className="w-full gap-0 overflow-y-auto p-0 sm:max-w-md bg-white border-s border-black/5 text-[#1d1d1f]"
                >
                    {openItem && (
                        <>
                            <SheetHeader className="border-b border-black/5 bg-[#f5f5f7]/50 p-6 text-start">
                                <SheetTitle className="text-base font-bold text-[#1d1d1f] font-sans">
                                    {openItem.title}
                                </SheetTitle>
                                <SheetDescription className="mt-1 text-xs text-[#1d1d1f]/60">
                                    {openItem.kind === 'task' ? __('general.task') : __('general.todo')} ·{' '}
                                    {openItem.project_name}
                                </SheetDescription>
                            </SheetHeader>
                            <div className="space-y-5 p-6 text-xs sm:text-sm">
                                {openItem.priority && (
                                    <div className="flex items-center gap-2">
                                        <Flag className="h-3.5 w-3.5 text-[#1d1d1f]/40" />
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize border ${
                                                PRIORITY_STYLES[openItem.priority] ?? PRIORITY_STYLES.normal
                                            }`}
                                        >
                                            {openItem.priority}
                                        </span>
                                    </div>
                                )}
                                {openItem.due_date && (
                                    <div className="flex items-center gap-2 text-xs text-[#1d1d1f]/60">
                                        <CalendarClock className="h-3.5 w-3.5 text-[#0071e3]" /> {formatDate(openItem.due_date)}
                                    </div>
                                )}
                                {openItem.description ? (
                                    <p className="whitespace-pre-wrap text-xs sm:text-sm text-[#1d1d1f]/80 leading-relaxed bg-[#f5f5f7] p-4 rounded-xl border border-black/5">
                                        {openItem.description}
                                    </p>
                                ) : (
                                    <p className="text-xs text-[#1d1d1f]/40 italic">{__('general.no_description')}</p>
                                )}
                                <Link
                                    href={route('client.projects.show', openItem.project_id)}
                                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed]"
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

function TaskRow({ item, onOpen }: { item: Item; onOpen: (item: Item) => void }) {
    const projectHref = route('client.projects.show', item.project_id);
    const today = startOfDay(new Date());
    const isOverdue = !item.completed && !!item.due_date && startOfDay(new Date(item.due_date)).getTime() < today.getTime();

    return (
        <div
            onClick={() => onOpen(item)}
            className={`bg-white border rounded-[18px] p-4 shadow-sm transition-all hover:border-[#0071e3]/30 hover:shadow-md flex items-start gap-4 cursor-pointer ${
                isOverdue ? 'border-rose-200' : 'border-black/5'
            }`}
        >
            {item.completed ? (
                <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" />
            ) : (
                <Circle className="mt-0.5 h-5 w-5 shrink-0 text-[#1d1d1f]/20" />
            )}

            <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-3">
                    <h3 className={`text-xs sm:text-sm font-semibold text-[#1d1d1f] truncate ${
                        item.completed ? 'line-through opacity-50' : ''
                    }`}>
                        {item.title}
                    </h3>
                    {item.priority && (
                        <span className={`shrink-0 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border capitalize font-mono ${
                            PRIORITY_STYLES[item.priority] ?? PRIORITY_STYLES.normal
                        }`}>
                            {item.priority}
                        </span>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3 mt-1.5 text-[11px] text-[#1d1d1f]/50">
                    <span className="font-medium text-[#1d1d1f]/70">
                        {item.project_name}
                    </span>
                    {item.due_date && (
                        <span className={`flex items-center gap-1 ${isOverdue ? 'text-rose-600 font-semibold' : ''}`}>
                            <CalendarClock className="w-3 h-3" />
                            {formatDate(item.due_date)}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}
