import React, { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    ChevronLeft, ChevronRight, CalendarDays, LayoutDashboard,
    StickyNote, ListTodo, FileText, Paperclip, ClipboardList, Plus,
    ChevronDown, ArrowLeft, Wallet, Share2, Calendar as LucideCalendar, Sparkles
} from 'lucide-react';
import {
    FaRegStickyNote, FaBolt, FaSearch, FaCheckCircle, FaGlobe, FaRegClipboard
} from 'react-icons/fa';
import type { IconType } from 'react-icons';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { toast } from 'sonner';
import axios from 'axios';
import CalendarSelector from '@/Components/CalendarSelector';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

export type BoardFilter = 'all' | 'backlog' | 'in_progress' | 'review' | 'done' | 'note' | 'task' | 'report' | 'todo' | 'file' | 'card';

export interface BoardTopNavCounts {
    all?: number;
    backlog?: number;
    in_progress?: number;
    review?: number;
    done?: number;
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
        share_url?: string;
        short_url?: string;
        client_name?: string;
    };
    activeFilter: BoardFilter;
    onFilterChange: (next: BoardFilter) => void;
    counts?: BoardTopNavCounts;
    date: string;
    onAdd: (kind: 'note' | 'task' | 'todo' | 'file' | 'report') => void;
    activeDates?: string[];
}

const FILTER_META: Record<BoardFilter, { labelKey: string; icon: IconType; activeColor: string; baseColor: string; shadowColor: string }> = {
    all: { labelKey: 'general.all', icon: FaGlobe, activeColor: 'bg-slate-900 text-white border-slate-900', baseColor: 'bg-slate-50 text-slate-700 hover:bg-slate-100/80 border-slate-200', shadowColor: 'shadow-slate-500/10' },
    backlog: { labelKey: 'general.lane_backlog', icon: FaRegStickyNote, activeColor: 'bg-indigo-600 text-white border-indigo-600', baseColor: 'bg-indigo-50/50 text-indigo-700 hover:bg-indigo-50 border-indigo-100', shadowColor: 'shadow-indigo-500/20' },
    in_progress: { labelKey: 'general.lane_in_progress', icon: FaBolt, activeColor: 'bg-amber-500 text-white border-amber-500', baseColor: 'bg-amber-50/50 text-amber-700 hover:bg-amber-50 border-amber-100', shadowColor: 'shadow-amber-500/20' },
    review: { labelKey: 'general.lane_review', icon: FaSearch, activeColor: 'bg-purple-600 text-white border-purple-600', baseColor: 'bg-purple-50/50 text-purple-700 hover:bg-purple-50 border-purple-100', shadowColor: 'shadow-purple-500/20' },
    done: { labelKey: 'general.lane_done', icon: FaCheckCircle, activeColor: 'bg-emerald-600 text-white border-emerald-600', baseColor: 'bg-emerald-50/50 text-emerald-700 hover:bg-emerald-50 border-emerald-100', shadowColor: 'shadow-emerald-500/20' },
    card: { labelKey: 'general.board_nav_cards', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    task: { labelKey: 'general.board_nav_tasks', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    todo: { labelKey: 'general.board_nav_todos', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    note: { labelKey: 'general.board_nav_notes', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    report: { labelKey: 'general.board_nav_reports', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    file: { labelKey: 'general.board_nav_files', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
};

const ADD_MENU: { kind: 'note' | 'task' | 'todo' | 'file' | 'report'; labelKey: string; icon: React.ElementType; color: string }[] = [
    { kind: 'note', labelKey: 'general.board_add_note', icon: StickyNote, color: 'text-amber-600' },
    { kind: 'task', labelKey: 'general.board_add_task', icon: ListTodo, color: 'text-sky-600' },
    { kind: 'todo', labelKey: 'general.board_add_todo', icon: ClipboardList, color: 'text-violet-600' },
    { kind: 'file', labelKey: 'general.board_add_file', icon: Paperclip, color: 'text-orange-600' },
    { kind: 'report', labelKey: 'general.board_add_report', icon: FileText, color: 'text-emerald-600' },
];

const FILTERS: BoardFilter[] = ['all', 'backlog', 'in_progress', 'review', 'done'];

export default function BoardTopNav({ project, activeFilter, onFilterChange, counts, date, onAdd, activeDates = [] }: BoardTopNavProps) {
    const day = parseISO(date);
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');
    const isToday = date === todayStr;
    const dateInputRef = useRef<HTMLInputElement | null>(null);
    const [addOpen, setAddOpen] = React.useState(false);
    const [showShareModal, setShowShareModal] = React.useState(false);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const [bringingUndone, setBringingUndone] = useState(false);
    const shareUrl = project.share_url || '';
    const shortUrl = project.short_url || '';

    const handleCopyLink = (url: string) => {
        if (!url) return;
        navigator.clipboard.writeText(url);
        toast.success(__('general.share_link_copied') || 'Link copied to clipboard!');
    };

    const goToDate = (target: string) => {
        if (!target || target === date) return;
        // Check if admin page or client page based on window pathname
        const isAdmin = window.location.pathname.includes('/admin/');
        if (isAdmin) {
            router.visit(route('admin.projects.board', { project: project.id, date: target }), { preserveScroll: true });
        } else {
            router.visit(route('client.projects.calendar.date', { project: project.id, date: target }), { preserveScroll: true });
        }
    };

    const handleBringUndone = () => {
        setBringingUndone(true);
        axios.post(route('client.projects.board.bring-undone', { project: project.id }), {
            for_date: date,
        }).then(({ data }) => {
            if (data.ok) {
                if (data.new_cards.length === 0) {
                    toast.info(__('general.no_undone_work_found') || 'No incomplete work was found in past days.');
                } else {
                    toast.success(__('general.undone_cards_brought') || `Brought ${data.new_cards.length} incomplete tasks forward!`);
                    const customEvent = new CustomEvent('board-undone-brought', { detail: { cards: data.new_cards } });
                    window.dispatchEvent(customEvent);
                }
            }
        }).catch(() => {
            toast.error(__('general.error') || 'Failed to bring undone work.');
        }).finally(() => {
            setBringingUndone(false);
        });
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

                    {/* Calendar Toggle Button */}
                    <button
                        type="button"
                        onClick={() => setCalendarOpen(true)}
                        className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors"
                    >
                        <LucideCalendar className="h-3.5 w-3.5 text-indigo-500" />
                        <span>{__('general.calendar') || 'Calendar'}</span>
                    </button>

                    {/* Bring Undone Yet Button */}
                    <button
                        type="button"
                        onClick={handleBringUndone}
                        disabled={bringingUndone}
                        className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors disabled:opacity-50"
                    >
                        <Sparkles className={cn("h-3.5 w-3.5 text-amber-500", bringingUndone && "animate-spin")} />
                        <span>{bringingUndone ? 'Bringing...' : __('general.bring_undone') || 'Bring Undone Yet'}</span>
                    </button>

                    {/* Share Button */}
                    {project.share_url && (
                        <button
                            type="button"
                            onClick={() => setShowShareModal(true)}
                            className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors"
                        >
                            <Share2 className="h-3.5 w-3.5 text-slate-500" />
                            <span>{__('general.board_share_btn') || 'Share'}</span>
                        </button>
                    )}

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

                {/* Row 2: Facebook Reaction style status bar with custom animations */}
                <div className="-mx-4 overflow-x-auto border-t border-slate-100 sm:-mx-6 lg:-mx-8 bg-slate-50/40 py-2.5">
                    <div className="flex min-w-max items-center gap-3 px-4 sm:px-6 lg:px-8">
                        {FILTERS.map((key) => {
                            const meta = FILTER_META[key];
                            const FilterIcon = meta.icon;
                            const label = __(meta.labelKey) || key;
                            const count = safeCounts[key] ?? 0;
                            const isActive = key === activeFilter;
                            return (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => onFilterChange(key)}
                                    className={cn(
                                        'group relative inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-bold transition-all duration-300 ease-out active:scale-95 shadow-sm hover:scale-[1.05]',
                                        isActive ? cn(meta.activeColor, meta.shadowColor) : cn(meta.baseColor, 'border-slate-200/80')
                                    )}
                                >
                                    <span className="text-sm transform transition-transform duration-300 group-hover:rotate-12 group-hover:scale-110">
                                        <FilterIcon className="h-4 w-4" />
                                    </span>
                                    <span>{label}</span>
                                    <span className={cn(
                                        'rounded-full px-2 py-0.5 text-[10px] font-extrabold transition-colors',
                                        isActive ? 'bg-white text-slate-900' : 'bg-slate-200/60 text-slate-800'
                                    )}>
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>

            {/* Share Dialog */}
            <Dialog open={showShareModal} onOpenChange={setShowShareModal}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold">
                            {__('general.share_project_board') || 'Share Project Board'}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 mt-1">
                            {__('general.share_project_board_desc') || 'Anyone with this link can view the read-only project board for this specific date.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="mt-4 space-y-3">
                        {project.client_name && (
                            <div className="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 border border-slate-100 flex items-center justify-between">
                                <span>{__('general.board_client') || 'Client'}</span>
                                <span className="font-semibold text-slate-800">{project.client_name}</span>
                            </div>
                        )}
                        {shortUrl ? (
                            <div className="space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                        {__('shortlink.short_link') || 'Short link'}
                                    </span>
                                    <span className="text-[10px] font-medium text-emerald-600">
                                        {__('shortlink.recommended_for_sharing') || 'Recommended for sharing'}
                                    </span>
                                </div>
                                <div className="relative">
                                    <input
                                        type="text"
                                        readOnly
                                        value={shortUrl}
                                        className="w-full h-10 rounded-lg border border-slate-300 bg-white px-3 pr-24 text-xs font-mono text-slate-900 focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => handleCopyLink(shortUrl)}
                                        className="absolute right-1.5 top-1.5 inline-flex h-7 items-center justify-center rounded-md bg-slate-900 px-3 text-[11px] font-semibold text-white hover:bg-slate-800"
                                    >
                                        {__('general.copy') || 'Copy'}
                                    </button>
                                </div>
                            </div>
                        ) : null}
                        <div className="space-y-1.5">
                            <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                {__('shortlink.destination_url') || 'Full link'}
                            </span>
                            <div className="relative">
                                <input
                                    type="text"
                                    readOnly
                                    value={shareUrl}
                                    className="w-full h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 pr-24 text-xs font-mono text-slate-500 focus:outline-none"
                                />
                                <button
                                    type="button"
                                    onClick={() => handleCopyLink(shareUrl)}
                                    className="absolute right-1.5 top-1.5 inline-flex h-7 items-center justify-center rounded-md bg-slate-200 px-3 text-[11px] font-semibold text-slate-700 hover:bg-slate-300"
                                >
                                    {__('general.copy') || 'Copy'}
                                </button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Calendar Selector Dialog */}
            <CalendarSelector
                open={calendarOpen}
                onOpenChange={setCalendarOpen}
                activeDates={activeDates}
                selectedDate={date}
                onSelectDate={goToDate}
            />
        </div>
    );
}