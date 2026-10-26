import { useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { AlertCircle, CalendarClock, CheckCircle2, Circle, ExternalLink, Flag } from 'lucide-react';
import ClientPage, { EmptyNote, Section, TextAction } from '@/Components/Client/ClientPage';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface ProjectOption { id: number; name: string; archived: boolean }

type Item =
    | { kind: 'task'; id: number; title: string; description?: string | null; project_id: number; project_name: string; priority?: string | null; due_date?: string | null; progress: number | null; todo_total: number; todo_done: number; completed: boolean }
    | { kind: 'todo'; id: number; title: string; description?: string | null; project_id: number; project_name: string; priority?: string | null; due_date?: string | null; completed: boolean; parent_task_id?: number | null; parent_task_name?: string | null; start_at?: string | null; end_at?: string | null };

interface Props {
    projects: ProjectOption[];
    items: Item[];
    filters: { project_id: number | null; completed: boolean; with_archived: boolean };
    stats: { total: number; tasks: number; todos: number; completed: number };
}

type Tone = 'danger' | 'today' | 'soon' | 'later' | 'none';
interface Bucket { key: string; label: string; tone: Tone; items: Item[] }

const URGENT = ['high', 'urgent'];
const priorityLabel = (priority: string) => (['high', 'urgent', 'normal', 'low'].includes(priority) ? __(`client.board_priority_${priority}`) : priority);
const startOfDay = (d: Date) => { const n = new Date(d); n.setHours(0, 0, 0, 0); return n; };
const diffDays = (a: Date, b: Date) => Math.round((startOfDay(a).getTime() - startOfDay(b).getTime()) / 86400000);

function bucketize(items: Item[]): Bucket[] {
    const today = startOfDay(new Date());
    const b: Record<Tone, Bucket> = {
        danger: { key: 'overdue', label: __('client_work.tasks.overdue'), tone: 'danger', items: [] },
        today: { key: 'today', label: __('client_work.tasks.today'), tone: 'today', items: [] },
        soon: { key: 'next_7_days', label: __('client_work.tasks.week'), tone: 'soon', items: [] },
        later: { key: 'later', label: __('client_work.tasks.later'), tone: 'later', items: [] },
        none: { key: 'no_due_date', label: __('client_work.tasks.no_date'), tone: 'none', items: [] },
    };
    for (const it of items) {
        if (it.completed) { b.later.items.push(it); continue; }
        const d = it.due_date ? new Date(it.due_date) : null;
        if (!d || isNaN(d.getTime())) { b.none.items.push(it); continue; }
        const days = diffDays(d, today);
        (days < 0 ? b.danger : days === 0 ? b.today : days <= 7 ? b.soon : b.later).items.push(it);
    }
    return [b.danger, b.today, b.soon, b.later, b.none].filter((x) => x.items.length > 0);
}

/** Every task and to-do across the client's projects, grouped by when it is due. */
export default function TasksAggregator({ projects, items, filters }: Props) {
    const { locale } = usePage().props as any;
    const formatDate = (date?: string | null) =>
        date ? new Date(date).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    const [projectId, setProjectId] = useState<string>(filters.project_id != null ? String(filters.project_id) : 'all');
    const [showCompleted, setShowCompleted] = useState<boolean>(filters.completed);
    const [openItemKey, setOpenItemKey] = useState<string | null>(null);

    const openItem = useMemo(() => {
        if (!openItemKey) return null;
        const [kind, idStr] = openItemKey.split('-');
        return items.find((it) => it.kind === kind && it.id === Number(idStr)) ?? null;
    }, [openItemKey, items]);

    const buckets = useMemo(() => bucketize(items), [items]);

    const applyFilters = (next: { project_id?: string; completed?: boolean }) => {
        const params: Record<string, string> = {};
        const pid = next.project_id !== undefined ? next.project_id : projectId;
        const comp = next.completed !== undefined ? next.completed : showCompleted;
        if (pid && pid !== 'all') params.project_id = pid;
        if (comp) params.completed = '1';
        if (filters.with_archived) params.with_archived = '1';
        router.get(route('client.projects.all-tasks'), params, { preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <ClientPage title={__('client_work.tasks.title')} hint={__('client_work.tasks.hint')}>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Select
                    value={projectId}
                    onValueChange={(v) => { const next = v ?? 'all'; setProjectId(next); applyFilters({ project_id: next }); }}
                >
                    <SelectTrigger aria-label={__('client_work.tasks.all_projects')} className="h-11 w-full rounded-xl bg-white sm:w-[260px] dark:bg-zinc-900">
                        <SelectValue placeholder={__('client_work.tasks.all_projects')} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{__('client_work.tasks.all_projects')}</SelectItem>
                        {projects.map((p) => (
                            <SelectItem key={p.id} value={String(p.id)}>
                                {p.name}{p.archived ? ` (${__('client_work.tasks.archived')})` : ''}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <TextAction onClick={() => { const next = !showCompleted; setShowCompleted(next); applyFilters({ completed: next }); }}>
                    {showCompleted ? __('client_work.tasks.hide_done') : __('client_work.tasks.show_done')}
                </TextAction>
            </div>

            {buckets.length === 0 ? (
                <EmptyNote>{__('client_work.tasks.empty')}</EmptyNote>
            ) : (
                buckets.map((bucket) => (
                    <Section
                        key={bucket.key}
                        title={`${bucket.label} (${bucket.items.length})`}
                    >
                        <ul className="space-y-2">
                            {bucket.items.map((it) => {
                                const overdue = bucket.tone === 'danger';
                                return (
                                    <li key={`${it.kind}-${it.id}`}>
                                        <button
                                            type="button"
                                            onClick={() => setOpenItemKey(`${it.kind}-${it.id}`)}
                                            className={cn('flex w-full items-start gap-3 rounded-[18px] border bg-white p-4 text-start shadow-sm transition-colors hover:border-[#0071e3]/30 dark:bg-zinc-900',
                                                overdue ? 'border-rose-200 dark:border-rose-900/50' : 'border-black/5 dark:border-white/10')}
                                        >
                                            {it.completed
                                                ? <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" />
                                                : <Circle className="mt-0.5 h-5 w-5 shrink-0 text-[#1d1d1f]/25 dark:text-zinc-600" />}
                                            <span className="min-w-0 flex-1">
                                                <span className={cn('block text-[15px] font-medium', it.completed && 'line-through opacity-50')}>{it.title}</span>
                                                <span className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#1d1d1f]/55 dark:text-zinc-400">
                                                    <span>{it.project_name}</span>
                                                    {it.due_date && (
                                                        <span className={cn('inline-flex items-center gap-1', overdue && 'font-semibold text-rose-600')}>
                                                            {overdue ? <AlertCircle className="h-3.5 w-3.5" /> : <CalendarClock className="h-3.5 w-3.5" />}{formatDate(it.due_date)}
                                                        </span>
                                                    )}
                                                    {it.priority && URGENT.includes(it.priority) && (
                                                        <span className="inline-flex items-center gap-1 font-semibold text-rose-600"><Flag className="h-3.5 w-3.5" />{priorityLabel(it.priority)}</span>
                                                    )}
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    </Section>
                ))
            )}

            <Sheet open={openItemKey != null} onOpenChange={(o) => (o ? null : setOpenItemKey(null))}>
                <SheetContent side="right" className="w-full gap-0 overflow-y-auto p-0 sm:max-w-md">
                    {openItem && (
                        <>
                            <SheetHeader className="border-b border-black/5 p-6 text-start dark:border-white/10">
                                <SheetTitle className="text-lg font-bold">{openItem.title}</SheetTitle>
                                <SheetDescription className="mt-1 text-sm">
                                    {openItem.kind === 'task' ? __('client_work.tasks.task') : __('client_work.tasks.todo')} · {openItem.project_name}
                                </SheetDescription>
                            </SheetHeader>
                            <div className="space-y-4 p-6 text-sm">
                                {openItem.priority && (
                                    <p className="flex items-center gap-2"><Flag className="h-4 w-4 text-[#1d1d1f]/40" />{priorityLabel(openItem.priority)}</p>
                                )}
                                {openItem.due_date && (
                                    <p className="flex items-center gap-2 text-[#1d1d1f]/70 dark:text-zinc-300"><CalendarClock className="h-4 w-4 text-[#0071e3]" />{formatDate(openItem.due_date)}</p>
                                )}
                                {openItem.description
                                    ? <p className="whitespace-pre-wrap rounded-xl bg-[#f5f5f7] p-4 leading-relaxed dark:bg-zinc-800" dir="auto">{openItem.description}</p>
                                    : <p className="text-[#1d1d1f]/40">{__('client_work.tasks.no_description')}</p>}
                                <Link href={route('client.projects.show', openItem.project_id)} className="inline-flex items-center gap-1.5 font-semibold text-[#0071e3]">
                                    <ExternalLink className="h-4 w-4" />{__('client_work.tasks.open_project')}
                                </Link>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </ClientPage>
    );
}
