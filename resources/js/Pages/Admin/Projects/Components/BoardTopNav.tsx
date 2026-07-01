import React, { useRef } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    ChevronLeft, ChevronRight, CalendarDays, LayoutDashboard, KanbanSquare,
    StickyNote, ListTodo, FileText, Paperclip, ClipboardList, Plus,
    ChevronDown, ArrowLeft, Wallet,
} from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export type BoardFilter = 'all' | 'card' | 'task' | 'todo' | 'note' | 'report' | 'file';

export interface BoardTopNavCounts {
    all?: number;
    card?: number;
    task?: number;
    todo?: number;
    note?: number;
    report?: number;
    file?: number;
}

interface BoardTopNavProps {
    project: {
        id: number;
        name: string;
        status?: string;
        archived?: boolean;
    };
    activeFilter: BoardFilter;
    onFilterChange: (next: BoardFilter) => void;
    counts?: BoardTopNavCounts;
    date: string;
    onAdd: (kind: 'note' | 'task' | 'todo' | 'file' | 'report' | 'card') => void;
}

const FILTER_META: Record<BoardFilter, { labelKey: string; icon: React.ElementType }> = {
    all: { labelKey: 'general.board_nav_board', icon: LayoutDashboard },
    card: { labelKey: 'general.board_nav_cards', icon: KanbanSquare },
    task: { labelKey: 'general.board_nav_tasks', icon: ListTodo },
    todo: { labelKey: 'general.board_nav_todos', icon: ClipboardList },
    note: { labelKey: 'general.board_nav_notes', icon: StickyNote },
    report: { labelKey: 'general.board_nav_reports', icon: FileText },
    file: { labelKey: 'general.board_nav_files', icon: Paperclip },
};

const ADD_MENU: { kind: 'note' | 'task' | 'todo' | 'file' | 'report' | 'card'; labelKey: string; icon: React.ElementType; color: string }[] = [
    { kind: 'note', labelKey: 'general.board_add_note', icon: StickyNote, color: 'text-amber-600' },
    { kind: 'task', labelKey: 'general.board_add_task', icon: ListTodo, color: 'text-sky-600' },
    { kind: 'todo', labelKey: 'general.board_add_todo', icon: ClipboardList, color: 'text-violet-600' },
    { kind: 'file', labelKey: 'general.board_add_file', icon: Paperclip, color: 'text-orange-600' },
    { kind: 'report', labelKey: 'general.board_add_report', icon: FileText, color: 'text-emerald-600' },
    { kind: 'card', labelKey: 'general.board_add_card', icon: KanbanSquare, color: 'text-indigo-600' },
];

const FILTERS: BoardFilter[] = ['all', 'card', 'task', 'todo', 'note', 'report', 'file'];

export default function BoardTopNav({ project, activeFilter, onFilterChange, counts, date, onAdd }: BoardTopNavProps) {
    const day = parseISO(date);
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');
    const isToday = date === todayStr;
    const dateInputRef = useRef<HTMLInputElement | null>(null);
    const [addOpen, setAddOpen] = React.useState(false);

    const goToDate = (target: string) => {
        if (!target || target === date) return;
        router.visit(route('admin.projects.board', { project: project.id, date: target }), { preserveScroll: true });
    };

    const safeCounts: BoardTopNavCounts = counts ?? {};

    return (
        <div
            className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm"
            aria-label={__('general.board_layout_aria')}
        >
            <div className="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
                {/* Row 1: Project context + Date navigator + Quick Add */}
                <div className="flex flex-wrap items-center gap-3 py-3">
                    <Link
                        href={route('admin.projects.index')}
                        className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                        title={__('general.board_back_to_admin')}
                        aria-label={__('general.board_back_to_admin')}
                    >
                        <ArrowLeft className="h-4 w-4" />
                        <span className="hidden md:inline">{__('general.admin_dashboard')}</span>
                    </Link>

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <h1 className="truncate text-base font-bold text-slate-900 sm:text-lg">{project.name}</h1>
                            {project.archived && (
                                <span className="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
                                    {__('general.archived')}
                                </span>
                            )}
                        </div>
                        <div className="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                            <LayoutDashboard className="h-3 w-3 text-slate-400" />
                            <span>{format(day, 'EEE, MMM d, yyyy')}</span>
                            {isToday && (
                                <span className="rounded-full bg-slate-900 px-1.5 py-0 text-[10px] font-bold uppercase tracking-wider text-white">
                                    {__('general.today')}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Date navigator */}
                    <div className="flex items-center gap-1.5">
                        <Link
                            href={route('admin.projects.board', { project: project.id, date: prev })}
                            preserveScroll
                            aria-label={__('general.previous_day')}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Link>

                        <button
                            type="button"
                            onClick={() => dateInputRef.current?.showPicker?.() ?? dateInputRef.current?.focus()}
                            className={cn(
                                'inline-flex h-9 items-center gap-1.5 rounded-lg border px-2.5 text-xs font-medium transition-colors',
                                isToday
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm hover:bg-slate-800'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                            )}
                            title={__('general.board_jump_to_date')}
                        >
                            <CalendarDays className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">{format(day, 'MMM d')}</span>
                        </button>
                        <input
                            ref={dateInputRef}
                            type="date"
                            value={date}
                            onChange={(e) => goToDate(e.target.value)}
                            className="sr-only"
                            tabIndex={-1}
                            aria-hidden="true"
                        />

                        <Link
                            href={route('admin.projects.board', { project: project.id, date: next })}
                            preserveScroll
                            aria-label={__('general.next_day')}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Link>

                        {!isToday && (
                            <Link
                                href={route('admin.projects.board', { project: project.id, date: todayStr })}
                                preserveScroll
                                className="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-700 transition-colors hover:bg-slate-50"
                            >
                                {__('general.today')}
                            </Link>
                        )}
                    </div>

                    {/* Quick Add */}
                    <div className="relative">
                        <button
                            type="button"
                            onClick={() => setAddOpen((v) => !v)}
                            onBlur={() => window.setTimeout(() => setAddOpen(false), 150)}
                            className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            <Plus className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">{__('general.board_quick_add')}</span>
                            <ChevronDown className="h-3 w-3 opacity-70" />
                        </button>
                        {addOpen && (
                            <div className="absolute right-0 z-40 mt-1.5 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                                {ADD_MENU.map((item) => {
                                    const Icon = item.icon;
                                    return (
                                        <button
                                            key={item.kind}
                                            type="button"
                                            onMouseDown={(e) => { e.preventDefault(); onAdd(item.kind); setAddOpen(false); }}
                                            className="flex w-full items-center gap-2 px-3 py-2 text-start text-xs text-slate-700 transition-colors hover:bg-slate-50"
                                        >
                                            <Icon className={cn('h-3.5 w-3.5', item.color)} />
                                            <span className="flex-1">{__(item.labelKey)}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>

                {/* Row 2: Section filter chips — all visible on the board */}
                <div className="-mx-4 overflow-x-auto border-t border-slate-100 sm:-mx-6 lg:-mx-8">
                    <div className="flex min-w-max items-center gap-1 px-4 sm:px-6 lg:px-8">
                        {FILTERS.map((key) => {
                            const meta = FILTER_META[key];
                            const Icon = meta.icon;
                            const label = __(meta.labelKey);
                            const count = safeCounts[key];
                            const isActive = key === activeFilter;
                            return (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => onFilterChange(key)}
                                    className={cn(
                                        'relative inline-flex items-center gap-1.5 border-b-2 px-3 py-2.5 text-xs font-semibold transition-colors',
                                        isActive
                                            ? 'border-slate-900 text-slate-900'
                                            : 'border-transparent text-slate-500 hover:border-slate-200 hover:text-slate-700',
                                    )}
                                >
                                    <Icon className={cn('h-3.5 w-3.5', isActive ? 'text-slate-900' : 'text-slate-400')} />
                                    <span>{label}</span>
                                    {typeof count === 'number' && count > 0 && (
                                        <span className={cn(
                                            'rounded-full px-1.5 py-0 text-[10px] font-bold',
                                            isActive ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600',
                                        )}>
                                            {count}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>
        </div>
    );
}