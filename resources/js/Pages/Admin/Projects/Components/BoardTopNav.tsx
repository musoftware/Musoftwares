import React, { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    ChevronLeft, ChevronRight, CalendarDays, LayoutDashboard,
    StickyNote, ListTodo, FileText, Paperclip, ClipboardList, Plus,
    ChevronDown, ArrowLeft, Wallet, Share2, Calendar as LucideCalendar, Sparkles, Tag, Bell,
    X, Trash2
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
        share_url_edit?: string;
        short_url_edit?: string;
        client_name?: string;
    };
    activeFilter: BoardFilter;
    onFilterChange: (next: BoardFilter) => void;
    counts?: BoardTopNavCounts;
    date: string;
    onAdd: (kind: 'note' | 'task' | 'todo' | 'file' | 'report' | 'ai') => void;
    activeDates?: string[];
    /** Renders a small "Manage categories" action button. Admin-only. */
    onManageCategories?: () => void;
    /** Renders a small "Manage notices" action button that opens the inline recurring-notices manager. */
    onManageNotices?: () => void;
    /** Renders a small "Admin Notes" button that toggles the Admin-only notes sidebar. */
    onToggleAdminNotes?: () => void;
    /** Whether the admin notes sidebar is open. */
    adminNotesOpen?: boolean;
}

const FILTER_META: Record<BoardFilter, { labelKey: string; icon: IconType; activeColor: string; baseColor: string; shadowColor: string }> = {
    all: { labelKey: 'general.all', icon: FaGlobe, activeColor: 'bg-slate-900 text-white border-slate-900', baseColor: 'bg-slate-50 text-slate-700 hover:bg-slate-100/80 border-slate-200', shadowColor: 'shadow-slate-500/10' },
    backlog: { labelKey: 'general.lane_backlog', icon: FaRegStickyNote, activeColor: 'bg-indigo-600 text-white border-indigo-600', baseColor: 'bg-indigo-50/50 text-indigo-700 hover:bg-indigo-50 border-indigo-100', shadowColor: 'shadow-indigo-500/20' },
    in_progress: { labelKey: 'general.lane_in_progress', icon: FaBolt, activeColor: 'bg-amber-500 text-white border-amber-500', baseColor: 'bg-amber-50/50 text-amber-700 hover:bg-amber-50 border-amber-100', shadowColor: 'shadow-amber-500/20' },
    review: { labelKey: 'general.lane_review', icon: FaSearch, activeColor: 'bg-purple-600 text-white border-purple-600', baseColor: 'bg-purple-50/50 text-purple-700 hover:bg-purple-50 border-indigo-100', shadowColor: 'shadow-purple-500/20' },
    done: { labelKey: 'general.lane_done', icon: FaCheckCircle, activeColor: 'bg-emerald-600 text-white border-emerald-600', baseColor: 'bg-emerald-50/50 text-emerald-700 hover:bg-emerald-50 border-emerald-100', shadowColor: 'shadow-emerald-500/20' },
    card: { labelKey: 'general.board_nav_cards', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    task: { labelKey: 'general.board_nav_tasks', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    todo: { labelKey: 'general.board_nav_todos', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    note: { labelKey: 'general.board_nav_notes', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    report: { labelKey: 'general.board_nav_reports', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
    file: { labelKey: 'general.board_nav_files', icon: FaRegClipboard, activeColor: '', baseColor: '', shadowColor: '' },
};

const ADD_MENU: { kind: 'note' | 'task' | 'todo' | 'file' | 'report' | 'ai'; labelKey: string; icon: React.ElementType; color: string }[] = [
    { kind: 'ai', labelKey: 'general.add_with_ai', icon: Sparkles, color: 'text-violet-600' },
    { kind: 'note', labelKey: 'general.board_add_note', icon: StickyNote, color: 'text-amber-600' },
    { kind: 'task', labelKey: 'general.board_add_task', icon: ListTodo, color: 'text-sky-600' },
    { kind: 'todo', labelKey: 'general.board_add_todo', icon: ClipboardList, color: 'text-violet-600' },
    { kind: 'file', labelKey: 'general.board_add_file', icon: Paperclip, color: 'text-orange-600' },
    { kind: 'report', labelKey: 'general.board_add_report', icon: FileText, color: 'text-emerald-600' },
];

const FILTERS: BoardFilter[] = ['all', 'backlog', 'in_progress', 'review', 'done'];

export default function BoardTopNav({ project, activeFilter, onFilterChange, counts, date, onAdd, activeDates = [], onManageCategories, onManageNotices, onToggleAdminNotes, adminNotesOpen = false }: BoardTopNavProps) {
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
    const [shareMode, setShareMode] = useState<'view' | 'edit'>('view');

    const shareUrl = shareMode === 'edit' ? (project.share_url_edit || '') : (project.share_url || '');
    const shortUrl = shareMode === 'edit' ? (project.short_url_edit || '') : (project.short_url || '');

    const [searchQuery, setSearchQuery] = useState('');
    const [suggestions, setSuggestions] = useState<any[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [updatingClient, setUpdatingClient] = useState(false);

    const [collaborators, setCollaborators] = useState<any[]>([]);
    const [loadingCollaborators, setLoadingCollaborators] = useState(false);

    const loadCollaborators = async () => {
        setLoadingCollaborators(true);
        try {
            const { data } = await axios.get(route('admin.projects.shares.index', { project: project.id }));
            setCollaborators(data);
        } catch (e) {
            console.error('Failed to load collaborators:', e);
        } finally {
            setLoadingCollaborators(false);
        }
    };

    React.useEffect(() => {
        if (showShareModal) {
            loadCollaborators();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [showShareModal]);

    const handleSearchUser = async (val: string) => {
        setSearchQuery(val);
        if (val.trim().length < 2) {
            setSuggestions([]);
            return;
        }

        try {
            const { data } = await axios.get(route('admin.projects.search-clients'), {
                params: { q: val }
            });
            setSuggestions(data);
        } catch (e) {
            console.error('Failed to search clients:', e);
        }
    };

    const handleSelectUser = async (user: any) => {
        setSearchQuery('');
        setSuggestions([]);
        setShowSuggestions(false);
        setUpdatingClient(true);

        try {
            const { data } = await axios.post(route('admin.projects.shares.store', { project: project.id }), {
                user_id: user.id
            });
            if (data.ok) {
                toast.success(__('general.project_updated_successfully') || 'Collaborator added successfully!');
                setCollaborators(prev => {
                    if (prev.some(c => c.user_id === user.id)) return prev;
                    return [...prev, data.share];
                });
            }
        } catch (e) {
            toast.error(__('general.error') || 'Failed to add collaborator.');
        } finally {
            setUpdatingClient(false);
        }
    };

    const handleRemoveCollaborator = async (shareId: number) => {
        try {
            const { data } = await axios.delete(route('admin.projects.shares.destroy', { project: project.id, share: shareId }));
            if (data.ok) {
                toast.success(__('general.project_updated_successfully') || 'Collaborator removed successfully!');
                setCollaborators(prev => prev.filter(c => c.id !== shareId));
            }
        } catch (e) {
            toast.error(__('general.error') || 'Failed to remove collaborator.');
        }
    };

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
        <header
            className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm"
            style={{
                paddingTop: 'env(safe-area-inset-top, 0px)'
            }}
            aria-label={__('general.board_layout_aria')}
        >
            <div className="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
                {/* Row 1: Project context + Date navigator + Quick Add */}
                <div className="flex flex-col gap-3 py-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <div className="flex items-center gap-2 sm:gap-3">
                        <Link
                            href={route('admin.projects.index')}
                            className="inline-flex h-10 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                            title={__('general.board_back_to_admin')}
                            aria-label={__('general.board_back_to_admin')}
                        >
                            <ArrowLeft className="h-4 w-4" />
                            <span className="hidden md:inline">{__('general.admin_dashboard')}</span>
                        </Link>

                        <div className="min-w-0 flex-1 sm:flex-none sm:basis-auto">
                            <div className="flex items-center gap-2">
                                <h1 className="truncate text-base font-bold text-slate-900 sm:text-lg">{project.name}</h1>
                                {project.archived && (
                                    <span className="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
                                        {__('general.archived')}
                                    </span>
                                )}
                            </div>
                            <div className="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                                <LayoutDashboard className="h-3 w-3 text-slate-400" />
                                <span className="truncate">{format(day, 'EEE, MMM d, yyyy')}</span>
                                {isToday && (
                                    <span className="shrink-0 rounded-full bg-slate-900 px-1.5 py-0 text-[10px] font-bold uppercase tracking-wider text-white">
                                        {__('general.today')}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-1.5 sm:ms-auto">
                        {/* Date navigator */}
                        <div className="flex items-center gap-1.5">
                            <Link
                                href={route('admin.projects.board', { project: project.id, date: prev })}
                                preserveScroll
                                aria-label={__('general.previous_day')}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Link>

                            <button
                                type="button"
                                onClick={() => dateInputRef.current?.showPicker?.() ?? dateInputRef.current?.focus()}
                                className={cn(
                                    'inline-flex h-10 items-center gap-1.5 rounded-lg border px-2.5 text-xs font-medium transition-colors',
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
                                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Link>

                            {!isToday && (
                                <Link
                                    href={route('admin.projects.board', { project: project.id, date: todayStr })}
                                    preserveScroll
                                    className="hidden sm:inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-700 transition-colors hover:bg-slate-50"
                                >
                                    {__('general.today')}
                                </Link>
                            )}
                        </div>

                        {/* Calendar Toggle Button */}
                        <button
                            type="button"
                            onClick={() => setCalendarOpen(true)}
                            className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors sm:w-auto sm:gap-1.5 sm:px-3 sm:text-xs sm:font-semibold"
                            title={__('general.calendar') || 'Calendar'}
                            aria-label={__('general.calendar') || 'Calendar'}
                        >
                            <LucideCalendar className="h-4 w-4 text-indigo-500 sm:h-3.5 sm:w-3.5" />
                            <span className="hidden sm:inline">{__('general.calendar') || 'Calendar'}</span>
                        </button>

                        {/* Bring Undone Yet Button */}
                        <button
                            type="button"
                            onClick={handleBringUndone}
                            disabled={bringingUndone}
                            className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors disabled:opacity-50 sm:w-auto sm:gap-1.5 sm:px-3 sm:text-xs sm:font-semibold"
                            title={__('general.bring_undone') || 'Bring Undone Yet'}
                            aria-label={__('general.bring_undone') || 'Bring Undone Yet'}
                        >
                            <Sparkles className={cn("h-4 w-4 text-amber-500 sm:h-3.5 sm:w-3.5", bringingUndone && "animate-spin")} />
                            <span className="hidden sm:inline">{bringingUndone ? 'Bringing...' : __('general.bring_undone') || 'Bring Undone Yet'}</span>
                        </button>

                        {/* Share Button */}
                        {project.share_url && (
                            <button
                                type="button"
                                onClick={() => setShowShareModal(true)}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors sm:w-auto sm:gap-1.5 sm:px-3 sm:text-xs sm:font-semibold"
                                title={__('general.board_share_btn') || 'Share'}
                                aria-label={__('general.board_share_btn') || 'Share'}
                            >
                                <Share2 className="h-4 w-4 text-slate-500 sm:h-3.5 sm:w-3.5" />
                                <span className="hidden sm:inline">{__('general.board_share_btn') || 'Share'}</span>
                            </button>
                        )}

                        {/* Manage categories (admin-only). Hidden when no callback is provided. */}
                        {onManageCategories && (
                            <button
                                type="button"
                                onClick={onManageCategories}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors sm:w-auto sm:gap-1.5 sm:px-3 sm:text-xs sm:font-semibold"
                                title={__('general.board_manage_categories') || 'Manage categories'}
                                aria-label={__('general.board_manage_categories') || 'Manage categories'}
                            >
                                <Tag className="h-4 w-4 text-amber-500 sm:h-3.5 sm:w-3.5" />
                                <span className="hidden sm:inline">{__('general.board_manage_categories') || 'Manage categories'}</span>
                            </button>
                        )}

                        {/* Manage recurring notices (admin-only). Opens the inline modal mounted in AdminBoardLayout. */}
                        {onManageNotices && (
                            <button
                                type="button"
                                onClick={onManageNotices}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors sm:w-auto sm:gap-1.5 sm:px-3 sm:text-xs sm:font-semibold"
                                title={__('general.manage_notices') || 'Manage notices'}
                                aria-label={__('general.manage_notices') || 'Manage notices'}
                            >
                                <Bell className="h-4 w-4 text-sky-500 sm:h-3.5 sm:w-3.5" />
                                <span className="hidden sm:inline">{__('general.manage_notices') || 'Manage notices'}</span>
                            </button>
                        )}

                        {/* Admin Internal Notes (admin-only) */}
                        {onToggleAdminNotes && (
                            <button
                                type="button"
                                onClick={onToggleAdminNotes}
                                className={cn(
                                    "inline-flex h-10 w-10 items-center justify-center rounded-lg border shadow-sm transition-colors sm:w-auto sm:gap-1.5 sm:px-3 sm:text-xs sm:font-semibold",
                                    adminNotesOpen
                                        ? "border-slate-900 bg-slate-900 text-white hover:bg-slate-800"
                                        : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900"
                                )}
                                title={__('general.admin_notes') || 'Admin Notes'}
                                aria-label={__('general.admin_notes') || 'Admin Notes'}
                            >
                                <StickyNote className={cn("h-4 w-4 sm:h-3.5 sm:w-3.5", adminNotesOpen ? "text-white" : "text-amber-500")} />
                                <span className="hidden sm:inline">{__('general.admin_notes') || 'Admin Notes'}</span>
                            </button>
                        )}

                        {/* Quick Add */}
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setAddOpen((v) => !v)}
                                onBlur={() => window.setTimeout(() => setAddOpen(false), 150)}
                                className="inline-flex h-10 items-center gap-1.5 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                <Plus className="h-4 w-4" />
                                <span className="hidden sm:inline">{__('general.board_quick_add')}</span>
                                <ChevronDown className="hidden h-3 w-3 opacity-70 sm:inline" />
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
                            {shareMode === 'view'
                                ? __('general.share_project_board_desc_view') || 'Anyone with this link can view the read-only project board for this specific date.'
                                : __('general.share_project_board_desc_edit') || 'Anyone with this link can view, add, edit, and move items on the project board exactly like a client.'
                            }
                        </DialogDescription>
                    </DialogHeader>

                    {/* Mode Toggle Tabs */}
                    <div className="mt-4 flex rounded-lg bg-slate-100 p-0.5">
                        <button
                            type="button"
                            onClick={() => setShareMode('view')}
                            className={cn(
                                'flex-1 rounded-md py-1.5 text-center text-xs font-semibold transition-all',
                                shareMode === 'view'
                                    ? 'bg-white text-slate-900 shadow-sm border border-slate-200/40'
                                    : 'text-slate-600 hover:text-slate-900'
                            )}
                        >
                            Read-Only
                        </button>
                        <button
                            type="button"
                            onClick={() => setShareMode('edit')}
                            className={cn(
                                'flex-1 rounded-md py-1.5 text-center text-xs font-semibold transition-all',
                                shareMode === 'edit'
                                    ? 'bg-white text-slate-900 shadow-sm border border-slate-200/40'
                                    : 'text-slate-600 hover:text-slate-900'
                            )}
                        >
                            Collaborative (Add &amp; Edit)
                        </button>
                    </div>

                    <div className="mt-3 space-y-3">
                        {project.client_name && (
                            <div className="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 border border-slate-100 flex items-center justify-between">
                                <span>{__('general.board_client') || 'Client'}</span>
                                <span className="font-semibold text-slate-800">{project.client_name}</span>
                            </div>
                        )}

                        <div className="space-y-2 pt-2 border-t border-slate-100">
                            <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                Collaborators
                            </span>
                            {loadingCollaborators ? (
                                <div className="text-[11px] text-slate-400 py-1">Loading collaborators...</div>
                            ) : collaborators.length === 0 ? (
                                <div className="text-[11px] text-slate-400 py-1 italic">No collaborators added yet.</div>
                            ) : (
                                <div className="space-y-1.5 max-h-36 overflow-y-auto">
                                    {collaborators.map((c) => (
                                        <div key={c.id} className="flex items-center justify-between p-2 rounded-md bg-slate-50 border border-slate-100/50">
                                            <div className="flex flex-col min-w-0">
                                                <span className="text-xs font-semibold text-slate-800 truncate">{c.name}</span>
                                                <span className="text-[10px] text-slate-500 truncate">{c.email}</span>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveCollaborator(c.id)}
                                                className="p-1 hover:bg-slate-100 rounded-md text-red-500 hover:text-red-700 transition-colors"
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="space-y-1 relative pt-2 border-t border-slate-100">
                            <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                Add Collaborator
                            </span>
                            <div className="relative">
                                <input
                                    type="text"
                                    placeholder="Search user to share with..."
                                    value={searchQuery}
                                    onChange={(e) => handleSearchUser(e.target.value)}
                                    onFocus={() => setShowSuggestions(true)}
                                    onBlur={() => setTimeout(() => setShowSuggestions(false), 200)}
                                    disabled={updatingClient}
                                    className="w-full h-10 rounded-lg border border-slate-200 bg-white pl-3 pr-10 text-xs text-slate-900 focus:outline-none focus:border-slate-400 placeholder:text-slate-400 disabled:opacity-50"
                                />
                                <div className="absolute right-3 top-3 flex items-center pointer-events-none">
                                    <ChevronDown className="h-4 w-4 text-slate-400" />
                                </div>
                                {showSuggestions && suggestions.length > 0 && (
                                    <div className="absolute left-0 right-0 top-11 z-50 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                        {suggestions.map((u: any) => (
                                            <button
                                                key={u.id}
                                                type="button"
                                                onClick={() => handleSelectUser(u)}
                                                className="w-full px-3 py-2 text-left text-xs hover:bg-slate-100 flex flex-col"
                                            >
                                                <span className="font-semibold text-slate-800">{u.name}</span>
                                                <span className="text-[10px] text-slate-500">{u.email}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
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
        </header>
    );
}