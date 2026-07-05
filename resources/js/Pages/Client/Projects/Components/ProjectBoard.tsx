import React, { useCallback, useMemo, useRef, useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    Plus, Trash2, StickyNote, ListTodo, FileText, CheckCircle2, Circle, GripVertical,
    Filter, StickyNote as NoteIcon, AlertCircle, ChevronDown, RotateCcw, Search, Paperclip,
    ClipboardList, Download, Edit3, X, UploadCloud, CalendarDays, BarChart, Eye,
    ArrowLeft, ArrowRight, CalendarClock, Calendar as CalendarIcon, Tag,
    LayoutGrid, Rows3, Table2, ArrowUpDown, ArrowUp, ArrowDown, LayoutList
} from 'lucide-react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import {
    FaRegStickyNote, FaBolt, FaSearch, FaCheckCircle, FaGlobe, FaRegClipboard
} from 'react-icons/fa';
import type { IconType } from 'react-icons';
import {
    DragDropContext,
    Draggable,
    Droppable,
    type DropResult,
} from '@hello-pangea/dnd';
import axios from 'axios';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import CommentsPopover from '@/Pages/Client/Projects/Components/CommentsPopover';
import BoardCategoryChip, { categoryPalette, type BoardCategoryLike } from './BoardCategoryChip';
import BoardCategoryPicker, { type BoardCategory } from './BoardCategoryPicker';

export type CardType = 'note' | 'task' | 'report' | 'todo' | 'file';

export type ViewMode = 'cards' | 'grid' | 'lines' | 'table';
export type SortBy = 'manual' | 'title' | 'type' | 'lane' | 'priority' | 'category';
export type SortDir = 'asc' | 'desc';

export interface BoardPreferences {
    view_mode: ViewMode;
    sort_by: SortBy;
    sort_dir: SortDir;
}

export interface BoardCard {
    type: CardType;
    id: number;
    title: string;
    lane: string;
    pos_x: number;
    pos_y: number;
    sort?: number;
    color?: string;
    content?: string;
    description?: string;
    body?: string;
    priority?: string;
    done?: boolean;
    completed?: boolean;
    published_at?: string;
    due_at?: string | null;
    checklist?: { id?: number; title: string; is_completed: boolean }[];
    size?: number;
    human_size?: string;
    mime?: string;
    download_url?: string;
    comments_count?: number;
    category_id?: number | null;
    category?: BoardCategoryLike | null;
}

interface ProjectBoardProps {
    projectId: number | string;
    date: string;
    lanes: string[];
    initialCards: BoardCard[];
    hideFuture?: boolean;
    readOnly?: boolean;
    externalFilter?: string;
    hideToolbar?: boolean;
    /** Mark cards as guest-viewable for the comment endpoints. */
    guestMode?: boolean;
    /** Required when `guestMode` is true. */
    shareToken?: string | null;
    /** Per-project category taxonomy. Falls back to a default-derived list when undefined. */
    categories?: BoardCategory[];
    /** Server-persisted view + sort preference. The component still works without it
     * (it falls back to local defaults) but the toolbar only persists changes when
     * this prop is provided by the parent. */
    preferences?: BoardPreferences;
}

const NOTE_COLORS: Record<string, { bg: string; border: string; text: string; swatch: string }> = {
    yellow: { bg: 'bg-amber-100', border: 'border-amber-300', text: 'text-amber-950', swatch: 'bg-amber-400' },
    green: { bg: 'bg-emerald-100', border: 'border-emerald-300', text: 'text-emerald-950', swatch: 'bg-emerald-400' },
    blue: { bg: 'bg-sky-100', border: 'border-sky-300', text: 'text-sky-950', swatch: 'bg-sky-400' },
    red: { bg: 'bg-rose-100', border: 'border-rose-300', text: 'text-rose-950', swatch: 'bg-rose-400' },
    purple: { bg: 'bg-violet-100', border: 'border-violet-300', text: 'text-violet-950', swatch: 'bg-violet-400' },
    pink: { bg: 'bg-pink-100', border: 'border-pink-300', text: 'text-pink-950', swatch: 'bg-pink-400' },
    slate: { bg: 'bg-slate-100', border: 'border-slate-300', text: 'text-slate-900', swatch: 'bg-slate-400' },
};

const LANE_META: Record<string, { labelKey: string; icon: IconType; bg: string; text: string; border: string }> = {
    backlog: { labelKey: 'general.lane_backlog', icon: FaRegStickyNote, bg: 'bg-indigo-50 text-indigo-700', text: 'text-indigo-700', border: 'border-indigo-100' },
    in_progress: { labelKey: 'general.lane_in_progress', icon: FaBolt, bg: 'bg-amber-50 text-amber-700', text: 'text-amber-700', border: 'border-amber-100' },
    review: { labelKey: 'general.lane_review', icon: FaSearch, bg: 'bg-purple-50 text-purple-700', text: 'text-purple-700', border: 'border-purple-100' },
    done: { labelKey: 'general.lane_done', icon: FaCheckCircle, bg: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', border: 'border-emerald-100' },
};

const TYPE_META: Record<CardType, { label: string; icon: React.ElementType; color: string; ring: string }> = {
    note: { label: 'Note', icon: StickyNote, color: 'text-amber-700 bg-amber-50 ring-amber-200', ring: 'ring-amber-200' },
    task: { label: 'Task', icon: ListTodo, color: 'text-sky-700 bg-sky-50 ring-sky-200', ring: 'ring-sky-200' },
    report: { label: 'Report', icon: FileText, color: 'text-emerald-700 bg-emerald-50 ring-emerald-200', ring: 'ring-emerald-200' },
    todo: { label: 'Todo', icon: ClipboardList, color: 'text-violet-700 bg-violet-50 ring-violet-200', ring: 'ring-violet-200' },
    file: { label: 'File', icon: Paperclip, color: 'text-orange-700 bg-orange-50 ring-orange-200', ring: 'ring-orange-200' },
};

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700 ring-rose-200',
    urgent: 'bg-orange-100 text-orange-700 ring-orange-200',
    normal: 'bg-amber-100 text-amber-700 ring-amber-200',
    low: 'bg-slate-100 text-slate-600 ring-slate-200',
};

const PRIORITY_RANK: Record<string, number> = {
    urgent: 0,
    high: 1,
    normal: 2,
    low: 3,
};

const LANE_RANK: Record<string, number> = {
    backlog: 0,
    in_progress: 1,
    review: 2,
    done: 3,
};

const TYPE_RANK: Record<CardType, number> = {
    note: 0,
    task: 1,
    todo: 2,
    report: 3,
    file: 4,
};

const VIEW_MODES: ViewMode[] = ['cards', 'grid', 'lines', 'table'];

const SORT_KEYS: SortBy[] = ['manual', 'title', 'type', 'lane', 'priority', 'category'];

const DEFAULT_PREFERENCES: BoardPreferences = {
    view_mode: 'cards',
    sort_by: 'manual',
    sort_dir: 'asc',
};

/**
 * Server-side persistence is the source of truth for view/sort preferences.
 * The PUT endpoint accepts a partial payload and merges with the existing row,
 * so callers can send one field at a time without clobbering the others.
 */
function persistPreferences(projectId: number | string, patch: Partial<BoardPreferences>): void {
    const url = route('admin.projects.board.preferences.update', { project: projectId });
    axios.put(url, patch).catch(() => {
        // The board stays usable even if persistence fails; just toast the user.
        toast.error(__('general.error') || 'Could not save board preference.');
    });
}

export default function ProjectBoard({
    projectId, date, lanes, initialCards, hideFuture, readOnly = false,
    externalFilter, guestMode = false, shareToken = null, categories,
    preferences,
}: ProjectBoardProps) {
    const { auth } = usePage().props as any;
    const userRoles: string[] = auth?.user?.roles ?? [];
    const isAdmin: boolean = !readOnly && (userRoles.includes('admin') || userRoles.includes('super_admin'));

    const [cards, setCards] = useState<BoardCard[]>(initialCards);
    const [query, setQuery] = useState('');
    const [categoryFilter, setCategoryFilter] = useState<'all' | 'uncategorized' | number>('all');

    // View/sort state hydrates from server preference when provided. We still
    // allow unsynced local toggles (e.g. in guest read-only mode) but only fire
    // the PUT when `preferences` was passed by the parent (admin/client portal).
    const initialPrefs: BoardPreferences = useMemo(() => ({
        ...DEFAULT_PREFERENCES,
        ...(preferences ?? {}),
    }), [preferences]);
    const [viewMode, setViewMode] = useState<ViewMode>(initialPrefs.view_mode);
    const [sortBy, setSortBy] = useState<SortBy>(initialPrefs.sort_by);
    const [sortDir, setSortDir] = useState<SortDir>(initialPrefs.sort_dir);

    useEffect(() => {
        setViewMode(initialPrefs.view_mode);
        setSortBy(initialPrefs.sort_by);
        setSortDir(initialPrefs.sort_dir);
    }, [initialPrefs.view_mode, initialPrefs.sort_by, initialPrefs.sort_dir]);

    const updateViewMode = useCallback((next: ViewMode) => {
        setViewMode(next);
        if (preferences && next !== preferences.view_mode) {
            persistPreferences(projectId, { view_mode: next });
        }
    }, [preferences, projectId]);

    const updateSortBy = useCallback((next: SortBy) => {
        setSortBy(next);
        if (preferences && next !== preferences.sort_by) {
            persistPreferences(projectId, { sort_by: next });
        }
    }, [preferences, projectId]);

    const updateSortDir = useCallback((next: SortDir) => {
        setSortDir(next);
        if (preferences && next !== preferences.sort_dir) {
            persistPreferences(projectId, { sort_dir: next });
        }
    }, [preferences, projectId]);

    const [statusPopover, setStatusPopover] = useState<{ cardId: number; type: CardType; x: number; y: number } | null>(null);
    const [contextMenu, setContextMenu] = useState<{ card: BoardCard; x: number; y: number } | null>(null);
    const [rescheduleDialog, setRescheduleDialog] = useState<{ card: BoardCard; targetDate: string } | null>(null);
    const [rescheduling, setRescheduling] = useState(false);

    const [activeModal, setActiveModal] = useState<{
        type: 'note' | 'task' | 'todo' | 'file' | 'report';
        action: 'create' | 'edit';
        cardId?: number;
    } | null>(null);

    const [noteForm, setNoteForm] = useState({ title: '', content: '', color: 'yellow' });
    const [taskForm, setTaskForm] = useState({ task_name: '', task_description: '', priority: 'normal' });
    const [todoForm, setTodoForm] = useState({ title: '', description: '', completed: false, checklist: [] as { id?: number; title: string; is_completed: boolean }[] });
    const [newCheckItem, setNewCheckItem] = useState('');
    const [fileForm, setFileForm] = useState<File | null>(null);
    const [reportForm, setReportForm] = useState({ title: '', body: '', published_at: '' });
    const [viewingCard, setViewingCard] = useState<BoardCard | null>(null);
    const [uploading, setUploading] = useState(false);
    const [highlightedCardKey, setHighlightedCardKey] = useState<string | null>(null);
    const highlightTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const updateCardCount = useCallback((cardKey: string, count: number) => {
        setCards((prev) => {
            const current = prev.find((c) => `${c.type}-${c.id}` === cardKey);
            if (current && (current.comments_count ?? 0) === count) return prev;
            return prev.map((c) => (`${c.type}-${c.id}` === cardKey ? { ...c, comments_count: count } : c));
        });
    }, []);

    const handlerCacheRef = useRef<Map<string, (count: number) => void>>(new Map());
    const makeCountHandler = useCallback((cardKey: string) => {
        const cached = handlerCacheRef.current.get(cardKey);
        if (cached) return cached;
        const fn = (count: number) => updateCardCount(cardKey, count);
        handlerCacheRef.current.set(cardKey, fn);
        return fn;
    }, [updateCardCount]);

    useEffect(() => {
        const handler = (e: Event) => {
            const kind = (e as CustomEvent).detail?.kind;
            if (kind) {
                openCreateModal(kind);
            }
        };
        window.addEventListener('board-add-trigger', handler);
        return () => window.removeEventListener('board-add-trigger', handler);
    }, []);

    useEffect(() => {
        const handleDismiss = () => {
            setContextMenu(null);
            setStatusPopover(null);
        };
        window.addEventListener('click', handleDismiss);
        return () => window.removeEventListener('click', handleDismiss);
    }, []);

    useEffect(() => {
        return () => {
            if (highlightTimeoutRef.current) {
                clearTimeout(highlightTimeoutRef.current);
            }
        };
    }, []);

    useEffect(() => {
        setCards(initialCards);
    }, [initialCards]);

    // Fall back to the four canonical system categories if the parent didn't pass any.
    // This keeps the chip + filter UI functional in non-seeded environments (e.g. tests).
    const effectiveCategories: BoardCategory[] = useMemo(() => {
        if (categories && categories.length > 0) return categories;
        return [
            { id: -1, slug: 'urgent', name: __('general.board_category_urgent') || 'Urgent', color: 'rose', is_system: true },
            { id: -2, slug: 'important', name: __('general.board_category_important') || 'Important', color: 'amber', is_system: true },
            { id: -3, slug: 'normal', name: __('general.board_category_normal') || 'Normal', color: 'slate', is_system: true },
            { id: -4, slug: 'idea', name: __('general.board_category_idea') || 'Idea', color: 'sky', is_system: true },
        ];
    }, [categories]);

    useEffect(() => {
        const handler = (e: Event) => {
            const newCards = (e as CustomEvent).detail?.cards;
            if (newCards && newCards.length > 0) {
                setCards((prev) => {
                    // Filter out any duplicates just in case
                    const existingIds = new Set(prev.map(c => `${c.type}-${c.id}`));
                    const uniqueNew = newCards.filter((nc: BoardCard) => !existingIds.has(`${nc.type}-${nc.id}`));
                    return [...prev, ...uniqueNew];
                });
            }
        };
        window.addEventListener('board-undone-brought', handler);
        return () => window.removeEventListener('board-undone-brought', handler);
    }, []);

    const filteredCards = useMemo(() => {
        const q = query.trim().toLowerCase();
        const list = cards.filter((c) => {
            if (externalFilter && externalFilter !== 'all') {
                const isLane = lanes.includes(externalFilter);
                if (isLane && c.lane !== externalFilter) return false;
                if (!isLane && c.type !== externalFilter) return false;
            }
            // 'all' shows everything; numeric = a real category id; 'uncategorized' = no chip.
            if (categoryFilter !== 'all') {
                if (categoryFilter === 'uncategorized') {
                    if (c.category_id) return false;
                } else if (c.category_id !== categoryFilter) {
                    return false;
                }
            }
            if (!q) return true;
            return (
                c.title.toLowerCase().includes(q) ||
                (c.description ?? '').toLowerCase().includes(q) ||
                (c.content ?? '').toLowerCase().includes(q)
            );
        });

        // The server already returns cards in lane-grouped, drag-drop order, but we
        // re-sort client-side to honor the user's chosen key/direction without
        // waiting for a round-trip. "manual" preserves the server's saved order so
        // drag-and-drop matches what gets persisted.
        const sorted = [...list];
        const dir = sortDir === 'desc' ? -1 : 1;

        const byTitle = (a: BoardCard, b: BoardCard) =>
            (a.title ?? '').localeCompare(b.title ?? '', undefined, { sensitivity: 'base' });

        const byStableId = (a: BoardCard, b: BoardCard) =>
            `${a.type}:${a.id}`.localeCompare(`${b.type}:${b.id}`);

        switch (sortBy) {
            case 'manual':
                sorted.sort((a, b) => (a.sort ?? 1e9) - (b.sort ?? 1e9));
                break;
            case 'title':
                sorted.sort((a, b) => dir * (byTitle(a, b) || byStableId(a, b)));
                break;
            case 'type':
                sorted.sort((a, b) => {
                    const cmp = (TYPE_RANK[a.type] ?? 99) - (TYPE_RANK[b.type] ?? 99);
                    return cmp !== 0 ? dir * cmp : dir * (byTitle(a, b) || byStableId(a, b));
                });
                break;
            case 'lane':
                sorted.sort((a, b) => {
                    const cmp = (LANE_RANK[a.lane] ?? 99) - (LANE_RANK[b.lane] ?? 99);
                    return cmp !== 0 ? dir * cmp : dir * ((a.sort ?? 0) - (b.sort ?? 0));
                });
                break;
            case 'priority':
                sorted.sort((a, b) => {
                    // Tasks are the only type with a priority. Non-tasks sort last
                    // so the priority order remains meaningful.
                    const ap = a.type === 'task' ? (PRIORITY_RANK[a.priority ?? ''] ?? 99) : 100;
                    const bp = b.type === 'task' ? (PRIORITY_RANK[b.priority ?? ''] ?? 99) : 100;
                    const cmp = ap - bp;
                    return cmp !== 0 ? dir * cmp : dir * (byTitle(a, b) || byStableId(a, b));
                });
                break;
            case 'category':
                sorted.sort((a, b) => {
                    const ac = a.category_id;
                    const bc = b.category_id;
                    if (ac == null && bc == null) return byStableId(a, b);
                    if (ac == null) return 1; // nulls always at the bottom regardless of dir
                    if (bc == null) return -1;
                    const cmp = ac - bc;
                    return cmp !== 0 ? dir * cmp : dir * (byTitle(a, b) || byStableId(a, b));
                });
                break;
        }

        return sorted;
    }, [cards, externalFilter, query, lanes, categoryFilter, sortBy, sortDir]);

    const updateCardLane = useCallback((type: CardType, id: number, nextLane: string) => {
        if (readOnly) return;
        axios.post(route('client.projects.board.move-card', { project: projectId }), {
            for_date: date,
            type,
            id,
            lane: nextLane,
            pos_x: 0,
            pos_y: 0,
        }).then(() => {
            setCards((prev) => prev.map((c) => c.type === type && c.id === id ? { ...c, lane: nextLane } : c));
            toast.success(__('general.card_moved') || 'Card status updated!');
        }).catch(() => {
            toast.error(__('general.could_not_save_card_position') || 'Failed to update status.');
        });
    }, [projectId, date, readOnly]);

    /**
     * Persist a brand-new sort order for the visible lane after a drag-drop interaction.
     * `order` is the array of card keys in the desired final order; we send it bulk so the
     * server can write sequential sort values in one transaction rather than N PATCH calls.
     */
    const reorderCards = useCallback(async (lane: string, order: { type: CardType; id: number }[]) => {
        if (readOnly) return;
        if (order.length === 0) return;
        try {
            await axios.post(route('client.projects.board.reorder-cards', { project: projectId }), {
                for_date: date,
                lane,
                order,
            });
            // The server is the source of truth — rebuild the sort field from the new order
            // so a refresh keeps the user's drag sequence even if the API response carries no payload.
            const orderIndex = new Map<string, number>();
            order.forEach((o, idx) => orderIndex.set(`${o.type}-${o.id}`, idx));
            setCards((prev) => prev.map((c) => (
                orderIndex.has(`${c.type}-${c.id}`)
                    ? { ...c, sort: orderIndex.get(`${c.type}-${c.id}`)! }
                    : c
            )));
            toast.success(__('general.board_reorder_saved') || 'Order saved.');
        } catch (err) {
            toast.error(__('general.board_reorder_failed') || 'Could not save the new order.');
        }
    }, [projectId, date, readOnly]);

    /**
     * Update only a card's category; we don't change lane or order. The card payload is returned
     * by `move-card` so the UI reflects the canonical server state.
     */
    const updateCardCategory = useCallback(async (type: CardType, id: number, categoryId: number | null) => {
        if (readOnly) return;
        try {
            const res = await axios.post(
                route('client.projects.board.move-card', { project: projectId }),
                { for_date: date, type, id, lane: undefined, category_id: categoryId },
            );
            const meta = res.data;
            setCards((prev) => prev.map((c) => {
                if (!(c.type === type && c.id === id)) return c;
                const newCategory = categoryId == null ? null : (effectiveCategories.find((x) => x.id === categoryId) ?? null);
                return {
                    ...c,
                    category_id: meta?.category_id ?? categoryId,
                    category: newCategory
                        ? { id: newCategory.id, slug: newCategory.slug, name: newCategory.name, color: newCategory.color, text_color: newCategory.text_color }
                        : null,
                };
            }));
        } catch (err) {
            toast.error(__('general.board_category_assign_failed') || 'Could not assign the category.');
        }
    }, [projectId, date, readOnly, effectiveCategories]);

    /**
     * `DragDropContext` callback. Because the board renders only the filter-selected lane
     * (the user filters via BoardTopNav), we always reorder the single visible lane and
     * pass the post-drag order to `reorderCards`. When no lane filter is active we fall
     * back to the card's current lane.
     */
    const onDragEnd = useCallback((result: DropResult) => {
        if (readOnly) return;
        if (!result.destination) return;
        if (result.destination.index === result.source.index) return;

        const draggedCard = filteredCards[result.source.index];
        if (!draggedCard) return;
        const targetLane = draggedCard.lane ?? 'backlog';

        // The droppable can mix lanes when the top-nav filter is "all" (or a type).
        // The backend reorder endpoint is per-lane and would silently move cards
        // across lanes if we submitted the whole flat list — restrict the payload
        // to the dragged card's lane and translate the flat drop index into a
        // lane-local index so within-lane order persists correctly.
        const laneCards = filteredCards.filter(
            (c) => (c.lane ?? 'backlog') === targetLane,
        );
        const sourceLaneIdx = laneCards.findIndex(
            (c) => c.type === draggedCard.type && c.id === draggedCard.id,
        );
        if (sourceLaneIdx === -1) return;

        let destLaneIdx = 0;
        for (let i = 0; i < result.destination.index && i < filteredCards.length; i++) {
            if ((filteredCards[i].lane ?? 'backlog') === targetLane) destLaneIdx++;
        }
        destLaneIdx = Math.min(destLaneIdx, laneCards.length - 1);
        if (sourceLaneIdx === destLaneIdx) return;

        const ordered = laneCards.map((c) => ({ type: c.type, id: c.id }));
        const [moved] = ordered.splice(sourceLaneIdx, 1);
        ordered.splice(destLaneIdx, 0, moved);

        void reorderCards(targetLane, ordered);
    }, [readOnly, filteredCards, reorderCards]);

    // ─── Reschedule (admin-only) ───────────────────────────────────────
    // Sends the card to a new for_date and removes it from the current day.
    // The underlying modelable's own date column is also updated server-side
    // so the card surfaces on the new day rather than vanishing entirely.
    const performReschedule = useCallback(async (card: BoardCard, targetDate: string) => {
        if (!isAdmin) {
            toast.error(__('general.card_reschedule_admin_only'));
            return;
        }
        if (card.type === 'file') {
            toast.error(__('general.card_reschedule_not_supported'));
            return;
        }
        try {
            await axios.post(
                route('client.projects.board.reschedule-card', { project: projectId }),
                { for_date: targetDate, type: card.type, id: card.id },
            );
            setCards((prev) => prev.filter((c) => !(c.type === card.type && c.id === card.id)));
            toast.success(
                __('general.card_rescheduled', { date: targetDate }, `Card rescheduled to ${targetDate}.`),
                {
                    action: {
                        label: __('general.view') || 'View',
                        onClick: () => router.visit(
                            route('admin.projects.board', { project: projectId, date: targetDate }),
                            { preserveScroll: true, preserveState: false },
                        ),
                    },
                },
            );
        } catch (err) {
            const message = (err as any)?.response?.data?.message;
            toast.error(message || __('general.card_reschedule_failed') || 'Failed to reschedule card.');
        }
    }, [projectId, isAdmin]);

    const shiftDay = useCallback((card: BoardCard, days: number) => {
        const today = new Date(date + 'T00:00:00');
        today.setDate(today.getDate() + days);
        const target = today.toISOString().slice(0, 10);
        if (target === date) return;
        void performReschedule(card, target);
    }, [date, performReschedule]);

    const reportHtml = useMemo(() => {
        if (!viewingCard) return '';
        const source = viewingCard.type === 'report'
            ? viewingCard.body
            : (viewingCard.content || viewingCard.description || '');
        if (!source) return '';
        const raw = marked.parse(source, { async: false }) as string;
        return DOMPurify.sanitize(raw);
    }, [viewingCard]);

    const openCreateModal = (type: 'note' | 'task' | 'todo' | 'file' | 'report') => {
        setNoteForm({ title: '', content: '', color: 'yellow' });
        setTaskForm({ task_name: '', task_description: '', priority: 'normal' });
        setTodoForm({ title: '', description: '', completed: false, checklist: [] });
        setFileForm(null);
        setReportForm({ title: '', body: '', published_at: date });
        setActiveModal({ type, action: 'create' });
    };

    const openEditModal = (card: BoardCard) => {
        if (card.type === 'note') {
            setNoteForm({ title: card.title ?? '', content: card.content ?? '', color: card.color || 'yellow' });
        } else if (card.type === 'task') {
            setTaskForm({ task_name: card.title, task_description: card.description || '', priority: card.priority || 'normal' });
        } else if (card.type === 'todo') {
            setTodoForm({ title: card.title, description: card.description || '', completed: !!card.completed, checklist: card.checklist || [] });
        } else if (card.type === 'report') {
            setReportForm({ title: card.title, body: card.description || '', published_at: card.published_at ? card.published_at.slice(0, 10) : date });
        }
        setActiveModal({ type: card.type as any, action: 'edit', cardId: card.id });
    };

    const flashCard = (type: CardType, id: number) => {
        const newKey = `${type}:${id}`;
        setHighlightedCardKey(newKey);
        if (highlightTimeoutRef.current) {
            clearTimeout(highlightTimeoutRef.current);
        }
        highlightTimeoutRef.current = setTimeout(() => {
            setHighlightedCardKey((current) => (current === newKey ? null : current));
            highlightTimeoutRef.current = null;
        }, 2400);
        requestAnimationFrame(() => {
            const el = document.querySelector(`[data-card-key="${newKey}"]`);
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    };

    const handleSaveNote = () => {
        const isEdit = activeModal?.action === 'edit';
        const url = isEdit
            ? route('client.projects.board.update-note', { project: projectId, note: activeModal?.cardId })
            : route('client.projects.board.store-note', { project: projectId });

        const payload = isEdit
            ? { title: noteForm.title, content: noteForm.content, color: noteForm.color }
            : { for_date: date, title: noteForm.title, content: noteForm.content, color: noteForm.color, lane: 'backlog' };

        axios({ method: isEdit ? 'put' : 'post', url, data: payload })
            .then(({ data }) => {
                if (data.card) {
                    setCards((prev) => isEdit 
                        ? prev.map((c) => c.type === 'note' && c.id === activeModal?.cardId ? data.card : c)
                        : [...prev, data.card]
                    );
                }
                setActiveModal(null);
                toast.success(isEdit ? __('general.note_updated') : __('general.note_added'));
            })
            .catch(() => toast.error(__('general.error') || 'Failed to save note.'));
    };

    const handleSaveTask = () => {
        const isEdit = activeModal?.action === 'edit';
        const url = isEdit
            ? route('client.projects.board.update-task', { project: projectId, task: activeModal?.cardId })
            : route('client.projects.board.store-task', { project: projectId });

        const payload = isEdit 
            ? taskForm
            : { ...taskForm, for_date: date, lane: 'backlog' };

        axios({ method: isEdit ? 'put' : 'post', url, data: payload })
            .then(({ data }) => {
                if (data.card) {
                    setCards((prev) => isEdit 
                        ? prev.map((c) => c.type === 'task' && c.id === activeModal?.cardId ? data.card : c)
                        : [...prev, data.card]
                    );
                }
                setActiveModal(null);
                toast.success(isEdit ? __('general.task_updated') : __('general.task_added'));
            })
            .catch(() => toast.error(__('general.error') || 'Failed to save task.'));
    };

    const handleSaveTodo = () => {
        const isEdit = activeModal?.action === 'edit';
        const url = isEdit
            ? route('client.projects.board.update-todo', { project: projectId, todo: activeModal?.cardId })
            : route('client.projects.board.store-todo', { project: projectId });

        const payload = isEdit 
            ? todoForm
            : { ...todoForm, for_date: date, checklist: todoForm.checklist.map(c => c.title), lane: 'backlog' };

        axios({ method: isEdit ? 'put' : 'post', url, data: payload })
            .then(({ data }) => {
                if (data.card) {
                    setCards((prev) => isEdit 
                        ? prev.map((c) => c.type === 'todo' && c.id === activeModal?.cardId ? data.card : c)
                        : [...prev, data.card]
                    );
                }
                setActiveModal(null);
                toast.success(isEdit ? __('general.todo_updated') : __('general.todo_added'));
            })
            .catch(() => toast.error(__('general.error') || 'Failed to save todo.'));
    };

    const handleUploadFile = () => {
        if (!fileForm) return;
        setUploading(true);
        const formData = new FormData();
        formData.append('file', fileForm);
        formData.append('for_date', date);
        formData.append('lane', 'done');

        axios.post(route('client.projects.board.store-file', { project: projectId }), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        }).then(({ data }) => {
            if (data.card) {
                setCards((prev) => [...prev, data.card]);
                flashCard(data.card.type, data.card.id);
            }
            setActiveModal(null);
            toast.success(__('general.file_uploaded') || 'File uploaded successfully!');
        }).catch(() => {
            toast.error(__('general.error') || 'Failed to upload file.');
        }).finally(() => {
            setUploading(false);
        });
    };

    const handleSaveReport = () => {
        const isEdit = activeModal?.action === 'edit';
        const url = isEdit
            ? route('client.projects.board.update-report', { project: projectId, report: activeModal?.cardId })
            : route('client.projects.board.store-report', { project: projectId });

        const payload = isEdit 
            ? reportForm
            : { ...reportForm, for_date: date, lane: 'done' };

        axios({ method: isEdit ? 'put' : 'post', url, data: payload })
            .then(({ data }) => {
                if (data.card) {
                    setCards((prev) => isEdit 
                        ? prev.map((c) => c.type === 'report' && c.id === activeModal?.cardId ? data.card : c)
                        : [...prev, data.card]
                    );
                }
                setActiveModal(null);
                toast.success(isEdit ? __('general.report_updated') : __('general.report_added'));
            })
            .catch(() => toast.error(__('general.error') || 'Failed to save report.'));
    };

    const handleDeleteCard = (card: BoardCard) => {
        if (readOnly) return;
        if (!confirm(__('general.delete_confirm') || 'Are you sure you want to delete this item?')) return;
        
        let url = '';
        if (card.type === 'note') url = route('client.projects.board.destroy-note', { project: projectId, note: card.id });
        else if (card.type === 'task') url = route('client.projects.board.destroy-task', { project: projectId, task: card.id });
        else if (card.type === 'todo') url = route('client.projects.board.destroy-todo', { project: projectId, todo: card.id });
        else if (card.type === 'file') url = route('client.projects.board.destroy-file', { project: projectId, file: card.id });
        else if (card.type === 'report') url = route('client.projects.board.destroy-report', { project: projectId, report: card.id });

        axios.delete(url)
            .then(() => {
                setCards((prev) => prev.filter((c) => !(c.type === card.type && c.id === card.id)));
                toast.success(__('general.card_deleted') || 'Item deleted successfully.');
            })
            .catch(() => toast.error(__('general.error') || 'Deletion failed.'));
    };

    const addTodoCheckItem = () => {
        if (!newCheckItem.trim()) return;
        setTodoForm((prev) => ({
            ...prev,
            checklist: [...prev.checklist, { title: newCheckItem.trim(), is_completed: false }]
        }));
        setNewCheckItem('');
    };

    const removeTodoCheckItem = (index: number) => {
        setTodoForm((prev) => ({
            ...prev,
            checklist: prev.checklist.filter((_, i) => i !== index)
        }));
    };

    const toggleTodoCheckItem = (index: number) => {
        setTodoForm((prev) => ({
            ...prev,
            checklist: prev.checklist.map((chk, i) => i === index ? { ...chk, is_completed: !chk.is_completed } : chk)
        }));
    };

    if (hideFuture) {
        return (
            <div className="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">
                {__('general.future_items_are_hidden_for_this_project')}
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <div className="relative w-full sm:w-72">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={__('general.search_cards') || 'Search board cards...'}
                            className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-xs text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-300 transition-all"
                        />
                    </div>

                    {/* Category filter — chip select that mirrors the project's category taxonomy */}
                    <CategoryFilterDropdown
                        categories={effectiveCategories}
                        value={categoryFilter}
                        onChange={setCategoryFilter}
                    />

                    {/* Sort dropdown — persists to server via PUT preferences */}
                    <SortDropdown
                        sortBy={sortBy}
                        sortDir={sortDir}
                        onChangeBy={updateSortBy}
                        onChangeDir={updateSortDir}
                    />

                    {/* View-mode toggle: cards / grid / lines / table */}
                    <ViewModeToggle value={viewMode} onChange={updateViewMode} />
                </div>

                {!readOnly && (
                    <div className="-mx-1 flex flex-wrap items-center gap-2 px-1 sm:mx-0 sm:px-0">
                        <button
                            onClick={() => openCreateModal('note')}
                            className="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm"
                        >
                            <Plus className="h-4 w-4 text-amber-500" /> {__('general.board_add_note')}
                        </button>
                        <button
                            onClick={() => openCreateModal('task')}
                            className="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm"
                        >
                            <Plus className="h-4 w-4 text-sky-500" /> {__('general.board_add_task')}
                        </button>
                    </div>
                )}
            </div>

            {filteredCards.length === 0 ? (
                <div className="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-16 text-center">
                    <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-100">
                        <AlertCircle className="h-7 w-7 text-slate-400" />
                    </div>
                    <p className="text-sm font-semibold text-slate-600">{__('general.no_cards_match_filter') || 'No cards matches filter'}</p>
                    <p className="text-xs text-slate-400">{__('general.try_changing_filter') || 'Try switching top status tabs'}</p>
                </div>
            ) : viewMode === 'table' ? (
                <BoardTableView
                    cards={filteredCards}
                    categories={effectiveCategories}
                    onView={(card) => setViewingCard(card)}
                    onEdit={(card) => !readOnly && openEditModal(card)}
                    onDelete={(card) => handleDeleteCard(card)}
                    onOpenMenu={(card, x, y) => setContextMenu({ card, x, y })}
                    readOnly={readOnly}
                    projectId={projectId}
                    guestMode={guestMode}
                    shareToken={shareToken}
                    makeCountHandler={makeCountHandler}
                />
            ) : viewMode === 'grid' ? (
                <BoardGridView
                    cards={filteredCards}
                    onOpenMenu={(card, x, y) => setContextMenu({ card, x, y })}
                    onView={(card) => setViewingCard(card)}
                    onEdit={(card) => !readOnly && openEditModal(card)}
                    onDelete={(card) => handleDeleteCard(card)}
                    readOnly={readOnly}
                    highlightedKey={highlightedCardKey}
                    projectId={projectId}
                    guestMode={guestMode}
                    shareToken={shareToken}
                    makeCountHandler={makeCountHandler}
                />
            ) : viewMode === 'lines' ? (
                <BoardLinesView
                    cards={filteredCards}
                    onOpenMenu={(card, x, y) => setContextMenu({ card, x, y })}
                    onView={(card) => setViewingCard(card)}
                    onEdit={(card) => !readOnly && openEditModal(card)}
                    onDelete={(card) => handleDeleteCard(card)}
                    readOnly={readOnly}
                    projectId={projectId}
                    guestMode={guestMode}
                    shareToken={shareToken}
                    makeCountHandler={makeCountHandler}
                />
            ) : (
                <DragDropContext onDragEnd={onDragEnd}>
                    <Droppable droppableId={`board-${externalFilter ?? date}`} direction="vertical" isDropDisabled={readOnly || sortBy !== 'manual'}>
                        {(provided, snapshot) => (
                            <div
                                ref={provided.innerRef}
                                {...provided.droppableProps}
                                className="flex flex-col gap-4"
                            >
                                {sortBy !== 'manual' && (
                                    <div className="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] font-semibold text-amber-800">
                                        <AlertCircle className="h-3.5 w-3.5" />
                                        <span>{__('general.board_drag_disabled_when_sorted') || 'Drag-to-reorder is only available when sort is set to Manual order.'}</span>
                                    </div>
                                )}
                                {filteredCards.map((card, index) => {
                                    const isNote = card.type === 'note';
                                    const noteColor = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
                                    const meta = TYPE_META[card.type];
                                    const TypeIcon = meta.icon;
                                    const priorityCls = card.priority ? PRIORITY_STYLES[card.priority] : null;
                                    const lane = LANE_META[card.lane] || LANE_META.backlog;
                                    const LaneIcon = lane.icon;
                                    const cardKey = `${card.type}:${card.id}`;
                                    const draggableId = cardKey;
                                    const isHighlighted = cardKey === highlightedCardKey;
                                    const dragDisabled = readOnly || sortBy !== 'manual';

                                    return (
                                        <Draggable draggableId={draggableId} index={index} isDragDisabled={dragDisabled} key={cardKey}>
                                            {(dragProvided, dragSnapshot) => (
                                                <div
                                                    ref={dragProvided.innerRef}
                                                    {...dragProvided.draggableProps}
                                                    data-card-key={cardKey}
                                                    onContextMenu={(e) => {
                                                        if (readOnly) return;
                                                        e.preventDefault();
                                                        setContextMenu({ card, x: e.clientX, y: e.clientY });
                                                    }}
                                                    className={cn(
                                                        'group relative flex flex-col justify-between rounded-2xl border p-4 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 ease-out cursor-pointer',
                                                        dragSnapshot.isDragging && 'shadow-2xl ring-2 ring-slate-400/40 rotate-1 scale-[1.02] z-50',
                                                        isHighlighted && 'ring-2 ring-emerald-400 ring-offset-2 animate-in zoom-in-95 fade-in duration-700',
                                                        isNote ? cn(noteColor.bg, noteColor.border, noteColor.text) : 'border-slate-200 bg-white text-slate-900'
                                                    )}
                                                >
                                                    {isHighlighted && (
                                                        <span className="pointer-events-none absolute -top-2.5 -end-2.5 z-10 inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-lg ring-2 ring-white">
                                                            <CheckCircle2 className="h-3 w-3" /> Uploaded
                                                        </span>
                                                    )}
                                                    <div className="space-y-3">
                                                        <div className="flex items-center justify-between gap-2">
                                                            {!readOnly && (
                                                                dragDisabled ? (
                                                                    <span
                                                                        className="inline-flex h-5 w-5 items-center justify-center rounded-md text-slate-200 cursor-not-allowed"
                                                                        title={__('general.board_drag_disabled_when_sorted') || 'Drag disabled while sorted.'}
                                                                        aria-label={__('general.board_drag_handle') || 'Drag handle'}
                                                                    >
                                                                        <GripVertical className="h-3.5 w-3.5" />
                                                                    </span>
                                                                ) : (
                                                                    <span
                                                                        {...dragProvided.dragHandleProps}
                                                                        className="inline-flex h-5 w-5 items-center justify-center rounded-md text-slate-300 group-hover:text-slate-500 hover:bg-slate-100 transition-colors cursor-grab active:cursor-grabbing"
                                                                        title={__('general.board_drag_handle') || 'Drag handle'}
                                                                        aria-label={__('general.board_drag_handle') || 'Drag handle'}
                                                                        onClick={(e) => e.stopPropagation()}
                                                                    >
                                                                        <GripVertical className="h-3.5 w-3.5" />
                                                                    </span>
                                                                )
                                                            )}
                                                            <span className={cn('inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider shadow-sm ring-1 ring-inset', meta.color, meta.ring)}>
                                                                <TypeIcon className="h-2.5 w-2.5" />
                                                                {meta.label}
                                                            </span>

                                                            <button
                                                                type="button"
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    if (readOnly) return;
                                                                    const rect = e.currentTarget.getBoundingClientRect();
                                                                    setStatusPopover({ cardId: card.id, type: card.type, x: rect.left, y: rect.bottom + window.scrollY });
                                                                }}
                                                                className={cn('inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold border transition-colors shadow-sm active:scale-95', lane.bg, lane.border)}
                                                            >
                                                                <LaneIcon className="h-2.5 w-2.5" />
                                                                <span>{__(lane.labelKey)}</span>
                                                                {!readOnly && <ChevronDown className="h-2.5 w-2.5 opacity-60" />}
                                                            </button>
                                                        </div>

                                                        {card.category && (
                                                            <div onClick={(e) => e.stopPropagation()}>
                                                                <BoardCategoryChip category={card.category} />
                                                            </div>
                                                        )}

                                                        <div className="space-y-1.5" onClick={() => !readOnly && openEditModal(card)}>
                                                            <h3 className={cn('line-clamp-2 leading-snug tracking-tight', isNote ? 'text-sm font-extrabold' : 'text-sm font-extrabold')}>
                                                                {card.title}
                                                            </h3>
                                                            {isNote && card.content && (
                                                                <p className="line-clamp-4 text-xs leading-relaxed opacity-80 break-words">
                                                                    {card.content}
                                                                </p>
                                                            )}
                                                            {!isNote && card.description && (
                                                                <p className="line-clamp-3 text-xs text-slate-500 leading-relaxed">
                                                                    {card.description}
                                                                </p>
                                                            )}

                                                            {card.type === 'task' && priorityCls && (
                                                                <div className="pt-1">
                                                                    <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-bold uppercase ring-1 ring-inset', priorityCls)}>
                                                                        {card.priority}
                                                                    </span>
                                                                </div>
                                                            )}

                                                            {card.type === 'todo' && card.checklist && card.checklist.length > 0 && (
                                                                <div className="mt-2 space-y-1 text-[11px] text-slate-500 bg-slate-50/50 p-2 rounded-xl border border-slate-100">
                                                                    <div className="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                                        <span>Progress</span>
                                                                        <span>
                                                                            {card.checklist.filter(c => c.is_completed).length} / {card.checklist.length}
                                                                        </span>
                                                                    </div>
                                                                    <div className="h-1 w-full bg-slate-200/80 rounded-full overflow-hidden">
                                                                        <div
                                                                            className="h-full bg-violet-500 transition-all duration-300"
                                                                            style={{ width: `${(card.checklist.filter(c => c.is_completed).length / card.checklist.length) * 100}%` }}
                                                                        />
                                                                    </div>
                                                                </div>
                                                            )}

                                                            {card.type === 'file' && (
                                                                <div className="mt-2 flex items-center justify-between bg-slate-50 p-2.5 rounded-xl border border-slate-100" onClick={(e) => e.stopPropagation()} title={card.title}>
                                                                    <div className="min-w-0 flex-1">
                                                                        <p className="truncate text-[10px] font-bold text-slate-700">{card.mime}</p>
                                                                        <p className="text-[10px] font-mono text-slate-500">{card.human_size}</p>
                                                                    </div>
                                                                    {card.download_url && (
                                                                        <a
                                                                            href={card.download_url}
                                                                            className="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm"
                                                                            title={`Download ${card.title}`}
                                                                        >
                                                                            <Download className="h-3.5 w-3.5" />
                                                                        </a>
                                                                    )}
                                                                </div>
                                                            )}

                                                            {card.type === 'report' && (
                                                                <div className="mt-2 flex items-center gap-1 text-[10px] text-slate-400 font-semibold">
                                                                    <CalendarDays className="h-3 w-3" />
                                                                    <span>{card.published_at ? new Date(card.published_at).toLocaleDateString() : date}</span>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <div className="mt-3 flex items-center justify-between gap-2 pt-2 border-t border-slate-100/60" onClick={(e) => e.stopPropagation()}>
                                                        <CommentsPopover
                                                            card={card}
                                                            projectId={projectId}
                                                            guestMode={guestMode}
                                                            shareToken={shareToken}
                                                            initialCount={card.comments_count}
                                                            onCountChange={makeCountHandler(`${card.type}-${card.id}`)}
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => setViewingCard(card)}
                                                            className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100 hover:ring-emerald-300 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400/60"
                                                            title={__('general.view') || 'View'}
                                                            aria-label={__('general.view') || 'View'}
                                                        >
                                                            <Eye className="h-3.5 w-3.5" />
                                                            <span>{__('general.view') || 'View'}</span>
                                                        </button>
                                                    </div>

                                                    {!readOnly && (
                                                        <div className="mt-3 flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity pt-2 border-t border-slate-100/50">
                                                            {!guestMode && (
                                                                <div onClick={(e) => e.stopPropagation()} title={__('general.board_category') || 'Category'}>
                                                                    <BoardCategoryPicker
                                                                        projectId={projectId}
                                                                        categories={effectiveCategories}
                                                                        selectedId={card.category_id ?? null}
                                                                        onChange={(next) => updateCardCategory(card.type, card.id, next)}
                                                                    />
                                                                </div>
                                                            )}
                                                            <button
                                                                onClick={() => openEditModal(card)}
                                                                className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors"
                                                                title="Edit Card"
                                                            >
                                                                <Edit3 className="h-3.5 w-3.5" />
                                                            </button>
                                                            <button
                                                                onClick={() => handleDeleteCard(card)}
                                                                className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors"
                                                                title="Delete Card"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </Draggable>
                                    );
                                })}
                                {snapshot.isDraggingOver && provided.placeholder
                                    ? React.cloneElement(provided.placeholder as React.ReactElement<{ className?: string }>, {
                                          className:
                                              'rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/60 transition-colors',
                                      })
                                    : provided.placeholder}
                            </div>
                        )}
                    </Droppable>
                </DragDropContext>
            )}

            {statusPopover && (
                <div
                    className="fixed z-50 flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/95 p-1.5 shadow-xl backdrop-blur animate-in fade-in slide-in-from-top-1 duration-150"
                    style={{
                        left: Math.max(8, Math.min(statusPopover.x, (typeof window !== 'undefined' ? window.innerWidth : 1024) - 220)),
                        top: statusPopover.y + 4,
                    }}
                    onClick={(e) => e.stopPropagation()}
                >
                    {Object.entries(LANE_META).map(([laneKey, meta]) => {
                        const LaneIcon = meta.icon;
                        return (
                            <button
                                key={laneKey}
                                type="button"
                                onClick={() => {
                                    updateCardLane(statusPopover.type, statusPopover.cardId, laneKey);
                                    setStatusPopover(null);
                                }}
                                className={cn(
                                    'flex h-9 w-9 items-center justify-center rounded-full text-base transition-all hover:scale-110 hover:bg-slate-50 shadow-sm active:scale-90',
                                )}
                                title={__(meta.labelKey)}
                                aria-label={__(meta.labelKey)}
                            >
                                <LaneIcon className="h-4 w-4" />
                            </button>
                        );
                    })}
                </div>
            )}

            {contextMenu && (
                <div
                    className="fixed z-50 w-56 max-w-[calc(100vw-1rem)] rounded-xl border border-slate-200 bg-white py-1 shadow-2xl animate-in zoom-in-95 duration-100"
                    style={{
                        left: Math.max(8, Math.min(contextMenu.x, (typeof window !== 'undefined' ? window.innerWidth : 1024) - 224)),
                        top: contextMenu.y,
                    }}
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="px-3 py-1.5 text-[10px] font-bold text-slate-400 border-b border-slate-100 uppercase tracking-wider truncate">
                        {contextMenu.card.title}
                    </div>
                    <button
                        onClick={() => { openEditModal(contextMenu.card); setContextMenu(null); }}
                        className="flex w-full items-center gap-2 px-3 py-2 text-start text-xs text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                        <Edit3 className="h-3.5 w-3.5 text-slate-400" />
                        <span>Edit</span>
                    </button>
                    <button
                        onClick={() => { handleDeleteCard(contextMenu.card); setContextMenu(null); }}
                        className="flex w-full items-center gap-2 px-3 py-2 text-start text-xs text-rose-600 hover:bg-rose-50 transition-colors"
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                        <span>Delete</span>
                    </button>
                    <div className="border-t border-slate-100 my-1" />
                    <div className="px-3 py-1 text-[9px] font-semibold text-slate-400 uppercase tracking-wider">Move Status</div>
                    {Object.entries(LANE_META).map(([laneKey, meta]) => {
                        const LaneIcon = meta.icon;
                        return (
                            <button
                                key={laneKey}
                                onClick={() => {
                                    updateCardLane(contextMenu.card.type, contextMenu.card.id, laneKey);
                                    setContextMenu(null);
                                }}
                                className={cn(
                                    "flex w-full items-center gap-2 px-3 py-1.5 text-start text-xs text-slate-600 hover:bg-slate-50",
                                    contextMenu.card.lane === laneKey && "bg-slate-50/50 font-bold text-slate-900"
                                )}
                            >
                                <LaneIcon className="h-3.5 w-3.5" />
                                <span>{__(meta.labelKey)}</span>
                            </button>
                        );
                    })}

                    {isAdmin && contextMenu.card.type !== 'file' && (
                        <>
                            <div className="border-t border-slate-100 my-1" />
                            <div className="px-3 py-1 text-[9px] font-semibold text-slate-400 uppercase tracking-wider inline-flex items-center gap-1">
                                <CalendarClock className="h-3 w-3" />
                                <span>{__('general.reschedule') || 'Reschedule'}</span>
                            </div>
                            <button
                                onClick={() => { const c = contextMenu.card; setContextMenu(null); shiftDay(c, -1); }}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-start text-xs text-slate-600 hover:bg-slate-50"
                            >
                                <ArrowLeft className="h-3.5 w-3.5" />
                                <span>{__('general.reschedule_back_day') || 'Back 1 day'}</span>
                            </button>
                            <button
                                onClick={() => { const c = contextMenu.card; setContextMenu(null); shiftDay(c, 1); }}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-start text-xs text-slate-600 hover:bg-slate-50"
                            >
                                <ArrowRight className="h-3.5 w-3.5" />
                                <span>{__('general.reschedule_next_day') || 'Next 1 day'}</span>
                            </button>
                            <button
                                onClick={() => {
                                    const c = contextMenu.card;
                                    setContextMenu(null);
                                    const today = new Date(date + 'T00:00:00');
                                    const next = new Date(today);
                                    next.setDate(next.getDate() + 1);
                                    setRescheduleDialog({ card: c, targetDate: next.toISOString().slice(0, 10) });
                                }}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-start text-xs text-slate-600 hover:bg-slate-50"
                            >
                                <CalendarIcon className="h-3.5 w-3.5" />
                                <span>{__('general.reschedule_choose_date') || 'Choose date…'}</span>
                            </button>
                        </>
                    )}
                </div>
            )}

            <Dialog open={rescheduleDialog !== null} onOpenChange={(open) => { if (!open) setRescheduleDialog(null); }}>
                <DialogContent className="w-full sm:max-w-md max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-extrabold uppercase tracking-wide text-slate-500 inline-flex items-center gap-2">
                                <CalendarClock className="h-4 w-4 text-indigo-500" />
                                {__('general.reschedule_dialog_title') || 'Reschedule card'}
                            </DialogTitle>
                            <DialogDescription className="text-xs text-slate-500 mt-1">
                                {__('general.reschedule_dialog_description') || 'Pick the new date for this card. The card will be moved out of the current day.'}
                            </DialogDescription>
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-3">
                        {rescheduleDialog && (
                            <>
                                <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2 text-[11px] text-slate-600 truncate">
                                    <span className="font-bold uppercase tracking-wider text-slate-400 mr-2">Card</span>
                                    <span className="font-semibold text-slate-700">{rescheduleDialog.card.title}</span>
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-bold text-slate-600">{__('general.reschedule_choose_date') || 'Choose date'}</Label>
                                    <Input
                                        type="date"
                                        value={rescheduleDialog.targetDate}
                                        onChange={(e) => setRescheduleDialog({ ...rescheduleDialog, targetDate: e.target.value })}
                                        className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                                    />
                                </div>
                            </>
                        )}
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <button
                            type="button"
                            onClick={() => setRescheduleDialog(null)}
                            disabled={rescheduling}
                            className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50"
                        >
                            {__('general.cancel') || 'Cancel'}
                        </button>
                        <button
                            type="button"
                            disabled={rescheduling || !rescheduleDialog?.targetDate || rescheduleDialog.targetDate === date}
                            onClick={async () => {
                                if (!rescheduleDialog) return;
                                setRescheduling(true);
                                try {
                                    await performReschedule(rescheduleDialog.card, rescheduleDialog.targetDate);
                                    setRescheduleDialog(null);
                                } finally {
                                    setRescheduling(false);
                                }
                            }}
                            className="rounded-xl bg-indigo-600 text-white px-4 py-2 text-xs font-semibold hover:bg-indigo-500 disabled:opacity-50 inline-flex items-center gap-2 shadow-sm"
                        >
                            <CalendarClock className="h-3.5 w-3.5" />
                            <span>{rescheduling ? '…' : __('general.reschedule_card') || 'Reschedule'}</span>
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={activeModal?.type === 'note'} onOpenChange={() => setActiveModal(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-extrabold uppercase tracking-wide text-slate-500">
                                {activeModal?.action === 'edit' ? 'Edit Sticky Note' : 'New Sticky Note'}
                            </DialogTitle>
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-4">
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">{__('general.note_title') || 'Title'}</Label>
                            <Input
                                value={noteForm.title}
                                onChange={(e) => setNoteForm({ ...noteForm, title: e.target.value })}
                                placeholder={__('general.note_title_placeholder') || 'Give your note a short title…'}
                                maxLength={255}
                                className="rounded-xl border-slate-200 text-sm focus:ring-slate-300"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Note Content</Label>
                            <Textarea
                                value={noteForm.content}
                                onChange={(e) => setNoteForm({ ...noteForm, content: e.target.value })}
                                placeholder="Write down your notes here..."
                                rows={6}
                                maxLength={61440}
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label className="text-xs font-bold text-slate-600">Background Color</Label>
                            <div className="flex items-center gap-2">
                                {Object.entries(NOTE_COLORS).map(([key, c]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => setNoteForm({ ...noteForm, color: key })}
                                        className={cn(
                                            'h-7 w-7 rounded-full border-2 transition-all shadow-sm active:scale-95',
                                            c.swatch,
                                            noteForm.color === key ? 'border-slate-900 scale-110 shadow' : 'border-white',
                                        )}
                                        aria-label={key}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <button onClick={() => setActiveModal(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button onClick={handleSaveNote} className="rounded-xl bg-slate-900 text-white px-4 py-2 text-xs font-semibold hover:bg-slate-800">
                            Save
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={activeModal?.type === 'task'} onOpenChange={() => setActiveModal(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-extrabold uppercase tracking-wide text-slate-500">
                                {activeModal?.action === 'edit' ? 'Edit Project Task' : 'New Project Task'}
                            </DialogTitle>
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-4">
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Task Name</Label>
                            <Input
                                value={taskForm.task_name}
                                onChange={(e) => setTaskForm({ ...taskForm, task_name: e.target.value })}
                                placeholder="Enter task title"
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Description</Label>
                            <Textarea
                                value={taskForm.task_description}
                                onChange={(e) => setTaskForm({ ...taskForm, task_description: e.target.value })}
                                placeholder="Add descriptive notes..."
                                rows={5}
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Priority</Label>
                            <select
                                value={taskForm.priority}
                                onChange={(e) => setTaskForm({ ...taskForm, priority: e.target.value })}
                                className="flex h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs ring-offset-white focus:outline-none focus:ring-1 focus:ring-slate-300"
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <button onClick={() => setActiveModal(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button onClick={handleSaveTask} className="rounded-xl bg-slate-900 text-white px-4 py-2 text-xs font-semibold hover:bg-slate-800">
                            Save
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={activeModal?.type === 'todo'} onOpenChange={() => setActiveModal(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-extrabold uppercase tracking-wide text-slate-500">
                                {activeModal?.action === 'edit' ? 'Edit Board Todo Checklist' : 'New Board Todo Checklist'}
                            </DialogTitle>
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-4">
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Todo Title</Label>
                            <Input
                                value={todoForm.title}
                                onChange={(e) => setTodoForm({ ...todoForm, title: e.target.value })}
                                placeholder="E.g., Design UI layout"
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Description</Label>
                            <Textarea
                                value={todoForm.description}
                                onChange={(e) => setTodoForm({ ...todoForm, description: e.target.value })}
                                placeholder="Summary notes..."
                                rows={3}
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                            />
                        </div>

                        <div className="space-y-2 border-t border-slate-100 pt-3">
                            <Label className="text-xs font-bold text-slate-600 flex items-center justify-between">
                                <span>Checklist Sub-items</span>
                                <span className="text-[10px] text-slate-400 font-extrabold">
                                    {todoForm.checklist.filter(c => c.is_completed).length} / {todoForm.checklist.length}
                                </span>
                            </Label>
                            <div className="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                {todoForm.checklist.map((chk, index) => (
                                    <div key={index} className="flex items-center justify-between bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-100">
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                checked={chk.is_completed}
                                                onCheckedChange={() => toggleTodoCheckItem(index)}
                                                id={`chk-${index}`}
                                            />
                                            <span className={cn('text-xs', chk.is_completed && 'line-through text-slate-400')}>{chk.title}</span>
                                        </div>
                                        <button onClick={() => removeTodoCheckItem(index)} className="text-slate-400 hover:text-rose-600">
                                            <X className="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                            <div className="flex items-center gap-1.5 mt-2">
                                <Input
                                    value={newCheckItem}
                                    onChange={(e) => setNewCheckItem(e.target.value)}
                                    placeholder="Add checklist sub-item..."
                                    className="h-8 rounded-xl border-slate-200 text-xs flex-1"
                                    onKeyDown={(e) => e.key === 'Enter' && addTodoCheckItem()}
                                />
                                <button
                                    onClick={addTodoCheckItem}
                                    className="inline-flex h-8 items-center justify-center rounded-xl bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-800 shadow-sm"
                                >
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <button onClick={() => setActiveModal(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button onClick={handleSaveTodo} className="rounded-xl bg-slate-900 text-white px-4 py-2 text-xs font-semibold hover:bg-slate-800">
                            Save
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={activeModal?.type === 'file'} onOpenChange={() => setActiveModal(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-extrabold uppercase tracking-wide text-slate-500">Upload Attachment Card</DialogTitle>
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-4">
                        <div className="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 hover:border-slate-400 bg-slate-50 rounded-2xl py-12 px-4 text-center cursor-pointer transition-colors relative">
                            <input
                                type="file"
                                onChange={(e) => setFileForm(e.target.files?.[0] || null)}
                                className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                            />
                            <UploadCloud className="h-10 w-10 text-slate-400 mb-2" />
                            {fileForm ? (
                                <div className="space-y-1">
                                    <p className="text-xs font-bold text-slate-700 truncate max-w-xs">{fileForm.name}</p>
                                    <p className="text-[10px] font-mono text-slate-500">{(fileForm.size / 1024 / 1024).toFixed(2)} MB</p>
                                </div>
                            ) : (
                                <div>
                                    <p className="text-xs font-semibold text-slate-600">Drag & Drop or Click to Select File</p>
                                    <p className="text-[10px] text-slate-400 mt-1">Maximum upload size 20MB</p>
                                </div>
                            )}
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <button onClick={() => setActiveModal(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" disabled={uploading}>
                            Cancel
                        </button>
                        <button onClick={handleUploadFile} className="rounded-xl bg-slate-900 text-white px-4 py-2 text-xs font-semibold hover:bg-slate-800 disabled:opacity-50" disabled={!fileForm || uploading}>
                            {uploading ? 'Uploading...' : 'Upload'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={activeModal?.type === 'report'} onOpenChange={() => setActiveModal(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-extrabold uppercase tracking-wide text-slate-500">
                                {activeModal?.action === 'edit' ? 'Edit Progress Report' : 'New Progress Report'}
                            </DialogTitle>
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-4">
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Report Title</Label>
                            <Input
                                value={reportForm.title}
                                onChange={(e) => setReportForm({ ...reportForm, title: e.target.value })}
                                placeholder="E.g., Weekly progress updates"
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-bold text-slate-600">Report Body (Markdown Supported)</Label>
                            <Textarea
                                value={reportForm.body}
                                onChange={(e) => setReportForm({ ...reportForm, body: e.target.value })}
                                placeholder="Use markdown headings, checklists, or descriptions..."
                                rows={10}
                                className="rounded-xl border-slate-200 text-xs focus:ring-slate-300 font-mono"
                            />
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <button onClick={() => setActiveModal(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button onClick={handleSaveReport} className="rounded-xl bg-slate-900 text-white px-4 py-2 text-xs font-semibold hover:bg-slate-800">
                            Save
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* View Card Dialog (works for any card type so guests can read full content) */}
            <Dialog open={!!viewingCard} onOpenChange={(open) => !open && setViewingCard(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    {viewingCard && (() => {
                        const meta = TYPE_META[viewingCard.type];
                        const TypeIcon = meta.icon;
                        const isNote = viewingCard.type === 'note';
                        const headerGradient = isNote
                            ? 'from-amber-50/80 to-white'
                            : 'from-emerald-50/60 to-white';
                        const headerIconColor = isNote ? 'text-amber-600' : 'text-emerald-600';
                        return (
                            <>
                                <div className={cn('px-6 pt-5 pb-3 border-b border-slate-100 shrink-0 bg-gradient-to-b', headerGradient)}>
                                    <DialogHeader>
                                        <div className={cn('flex items-center gap-2', headerIconColor)}>
                                            <TypeIcon className="h-4 w-4" />
                                            <span className="text-[10px] font-bold uppercase tracking-wider">
                                                {__(`general.${viewingCard.type === 'note' ? 'note' : viewingCard.type === 'task' ? 'task' : viewingCard.type === 'todo' ? 'todo' : viewingCard.type === 'file' ? 'file' : 'report'}`) || meta.label}
                                            </span>
                                        </div>
                                        <DialogTitle className="text-lg font-bold text-slate-900 mt-1 break-words">
                                            {viewingCard.title || __('general.sticky_note')}
                                        </DialogTitle>
                                        {(viewingCard.published_at || viewingCard.due_at) && (
                                            <p className="mt-1 text-xs text-slate-400">
                                                {viewingCard.published_at && new Date(viewingCard.published_at).toLocaleString()}
                                            </p>
                                        )}
                                    </DialogHeader>
                                </div>
                                <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5">
                                    {viewingCard.type === 'file' ? (
                                        <div className="flex flex-col items-center justify-center gap-4 py-6 text-center">
                                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-200">
                                                <Paperclip className="h-8 w-8" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-bold text-slate-800">{viewingCard.title}</p>
                                                <p className="text-xs text-slate-500">{viewingCard.mime} · {viewingCard.human_size}</p>
                                            </div>
                                            {viewingCard.download_url && (
                                                <a
                                                    href={viewingCard.download_url}
                                                    className="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors"
                                                >
                                                    <Download className="h-3.5 w-3.5" />
                                                    {__('general.download') || 'Download'}
                                                </a>
                                            )}
                                        </div>
                                    ) : viewingCard.type === 'todo' && viewingCard.checklist && viewingCard.checklist.length > 0 ? (
                                        <div className="space-y-4">
                                            {viewingCard.description && (
                                                <p className="text-sm text-slate-600 whitespace-pre-wrap">{viewingCard.description}</p>
                                            )}
                                            <ul className="space-y-2">
                                                {viewingCard.checklist.map((chk, i) => (
                                                    <li key={chk.id ?? i} className="flex items-start gap-2 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                                                        {chk.is_completed ? (
                                                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                                        ) : (
                                                            <Circle className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                                        )}
                                                        <span className={cn('text-sm', chk.is_completed ? 'line-through text-slate-400' : 'text-slate-700')}>
                                                            {chk.title}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ) : reportHtml ? (
                                        <article
                                            className="prose prose-slate max-w-none prose-headings:font-heading prose-a:text-emerald-600 prose-pre:bg-slate-900 prose-pre:text-slate-100"
                                            dangerouslySetInnerHTML={{ __html: reportHtml }}
                                        />
                                    ) : isNote && viewingCard.content ? (
                                        <p className="text-sm text-slate-700 whitespace-pre-wrap break-words">{viewingCard.content}</p>
                                    ) : isNote ? (
                                        <p className="text-sm text-slate-400 italic text-center py-8">
                                            {__('general.no_content') || 'No content available.'}
                                        </p>
                                    ) : viewingCard.description ? (
                                        <p className="text-sm text-slate-700 whitespace-pre-wrap">{viewingCard.description}</p>
                                    ) : (
                                        <p className="text-sm text-slate-400 italic text-center py-8">
                                            {__('general.no_content') || 'No content available.'}
                                        </p>
                                    )}
                                </div>
                                <DialogFooter className="gap-2 sm:gap-0 px-6 py-3 border-t border-slate-100 bg-slate-50/60 shrink-0">
                                    {viewingCard.type === 'report' && (
                                        <a
                                            href={route('client.projects.board.export-report-pdf', {
                                                project: projectId,
                                                report: viewingCard.id,
                                            })}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                                        >
                                            <Download className="h-3.5 w-3.5" />
                                            {__('general.export_as_pdf') || 'Export as PDF'}
                                        </a>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => setViewingCard(null)}
                                        className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white"
                                    >
                                        {__('general.close') || 'Close'}
                                    </button>
                                </DialogFooter>
                            </>
                        );
                    })()}
                </DialogContent>
            </Dialog>
        </div>
    );
}

const CategoryFilterDropdown: React.FC<{
    categories: BoardCategory[];
    value: 'all' | 'uncategorized' | number;
    onChange: (next: 'all' | 'uncategorized' | number) => void;
}> = ({ categories, value, onChange }) => {
    const [open, setOpen] = useState(false);
    const wrapRef = React.useRef<HTMLDivElement | null>(null);

    React.useEffect(() => {
        if (!open) return;
        const onDoc = (e: MouseEvent) => {
            if (!wrapRef.current?.contains(e.target as Node)) setOpen(false);
        };
        window.addEventListener('mousedown', onDoc);
        return () => window.removeEventListener('mousedown', onDoc);
    }, [open]);

    const selectedLabel = (() => {
        if (value === 'all') return __('general.board_clear_category_filter') || 'All categories';
        if (value === 'uncategorized') return __('general.board_no_category') || 'No category';
        return categories.find((c) => c.id === value)?.name ?? '—';
    })();

    return (
        <div className="relative" ref={wrapRef}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition-colors"
            >
                <Tag className="h-3.5 w-3.5 text-slate-400" />
                <span className="truncate max-w-[10rem]">{selectedLabel}</span>
                <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
            </button>
            {open && (
                <div className="absolute right-0 sm:left-0 z-30 mt-1.5 w-60 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl ring-1 ring-black/5 animate-in fade-in slide-in-from-top-1 duration-150">
                    <div className="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        {__('general.board_filter_by_category') || 'Filter by category'}
                    </div>
                    <button
                        type="button"
                        onClick={() => { onChange('all'); setOpen(false); }}
                        className={cn(
                            'flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-start text-xs transition-colors',
                            value === 'all' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-600 hover:bg-slate-50',
                        )}
                    >
                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200/60 text-slate-500">
                            <Tag className="h-3 w-3" />
                        </span>
                        <span className="flex-1 truncate">{__('general.board_clear_category_filter') || 'All categories'}</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => { onChange('uncategorized'); setOpen(false); }}
                        className={cn(
                            'flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-start text-xs transition-colors',
                            value === 'uncategorized' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-600 hover:bg-slate-50',
                        )}
                    >
                        <span className="flex h-5 w-5 items-center justify-center rounded-full border-2 border-dashed border-slate-300 text-slate-400">—</span>
                        <span className="flex-1 truncate">{__('general.board_no_category') || 'No category'}</span>
                    </button>
                    {categories.length > 0 && <div className="my-1 border-t border-slate-100" />}
                    <div className="max-h-56 overflow-y-auto">
                        {categories.map((c) => {
                            const palette = categoryPalette(c);
                            const isActive = value === c.id;
                            return (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => { onChange(c.id); setOpen(false); }}
                                    className={cn(
                                        'flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-start text-xs transition-colors',
                                        isActive ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-600 hover:bg-slate-50',
                                    )}
                                >
                                    <span className={cn('h-3 w-3 shrink-0 rounded-full ring-1 ring-inset', palette.dot, palette.ring)} />
                                    <span className="flex-1 truncate">{c.name}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
};

// ──────────────────────────────────────────────────────────────────────────────
// View-mode toggle, sort dropdown, and the alternate board renderings.
// Kept in the same file because they share LANE_META / TYPE_META / NOTE_COLORS
// tables; if a third view lands they can graduate to ./views/*.
// ──────────────────────────────────────────────────────────────────────────────

const ViewModeToggle: React.FC<{
    value: ViewMode;
    onChange: (next: ViewMode) => void;
}> = ({ value, onChange }) => {
    const items: { id: ViewMode; icon: React.ElementType; label: string }[] = [
        { id: 'cards', icon: LayoutList, label: __('general.board_view_cards') || 'Cards' },
        { id: 'grid', icon: LayoutGrid, label: __('general.board_view_grid') || 'Grid' },
        { id: 'lines', icon: Rows3, label: __('general.board_view_lines') || 'Lines' },
        { id: 'table', icon: Table2, label: __('general.board_view_table') || 'Table' },
    ];

    return (
        <div className="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-sm">
            {items.map(({ id, icon: Icon, label }) => {
                const active = value === id;
                return (
                    <button
                        key={id}
                        type="button"
                        onClick={() => onChange(id)}
                        className={cn(
                            'inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-[11px] font-semibold transition-colors',
                            active
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
                                : 'text-slate-500 hover:text-slate-900',
                        )}
                        aria-pressed={active}
                        aria-label={label}
                        title={label}
                    >
                        <Icon className="h-3.5 w-3.5" />
                        <span className="hidden sm:inline">{label}</span>
                    </button>
                );
            })}
        </div>
    );
};

const SortDropdown: React.FC<{
    sortBy: SortBy;
    sortDir: SortDir;
    onChangeBy: (next: SortBy) => void;
    onChangeDir: (next: SortDir) => void;
}> = ({ sortBy, sortDir, onChangeBy, onChangeDir }) => {
    const [open, setOpen] = useState(false);
    const wrapRef = React.useRef<HTMLDivElement | null>(null);

    React.useEffect(() => {
        if (!open) return;
        const onDoc = (e: MouseEvent) => {
            if (!wrapRef.current?.contains(e.target as Node)) setOpen(false);
        };
        window.addEventListener('mousedown', onDoc);
        return () => window.removeEventListener('mousedown', onDoc);
    }, [open]);

    const label = (() => {
        switch (sortBy) {
            case 'manual': return __('general.board_sort_manual') || 'Manual';
            case 'title': return __('general.board_sort_title') || 'Title';
            case 'type': return __('general.board_sort_type') || 'Type';
            case 'lane': return __('general.board_sort_lane') || 'Status';
            case 'priority': return __('general.board_sort_priority') || 'Priority';
            case 'category': return __('general.board_sort_category') || 'Category';
        }
    })();

    const DirIcon = sortDir === 'desc' ? ArrowDown : ArrowUp;

    return (
        <div className="relative" ref={wrapRef}>
            <div className="inline-flex h-10 items-stretch overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <button
                    type="button"
                    onClick={() => setOpen((v) => !v)}
                    className="inline-flex h-full items-center gap-1.5 px-3 text-[11px] font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <ArrowUpDown className="h-3.5 w-3.5 text-slate-400" />
                    <span className="text-slate-400">{__('general.board_sort_label') || 'Sort'}:</span>
                    <span>{label}</span>
                    <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
                </button>
                <button
                    type="button"
                    onClick={() => onChangeDir(sortDir === 'asc' ? 'desc' : 'asc')}
                    className="inline-flex h-full items-center gap-1 border-l border-slate-200 px-2.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50"
                    title={__('general.board_sort_direction') || 'Sort direction'}
                    aria-label={__('general.board_sort_direction') || 'Sort direction'}
                >
                    <DirIcon className="h-3.5 w-3.5 text-slate-400" />
                </button>
            </div>
            {open && (
                <div className="absolute right-0 sm:left-0 z-30 mt-1.5 w-56 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl ring-1 ring-black/5 animate-in fade-in slide-in-from-top-1 duration-150">
                    {SORT_KEYS.map((key) => {
                        const itemLabel = (() => {
                            switch (key) {
                                case 'manual': return __('general.board_sort_manual') || 'Manual order';
                                case 'title': return __('general.board_sort_title') || 'Title';
                                case 'type': return __('general.board_sort_type') || 'Type';
                                case 'lane': return __('general.board_sort_lane') || 'Status';
                                case 'priority': return __('general.board_sort_priority') || 'Priority';
                                case 'category': return __('general.board_sort_category') || 'Category';
                            }
                        })();
                        const isActive = sortBy === key;
                        return (
                            <button
                                key={key}
                                type="button"
                                onClick={() => { onChangeBy(key); setOpen(false); }}
                                className={cn(
                                    'flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-start text-xs transition-colors',
                                    isActive ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-600 hover:bg-slate-50',
                                )}
                            >
                                <ArrowUpDown className="h-3.5 w-3.5 text-slate-400" />
                                <span className="flex-1 truncate">{itemLabel}</span>
                                {isActive && (
                                    <DirIcon className={cn('h-3.5 w-3.5 text-slate-400', sortDir === 'desc' && 'rotate-180')} />
                                )}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
};

// Compact shared card chrome (type badge + lane pill + category) used by the
// grid/lines/table views. Centralized so the alternate views stay consistent
// with the rich cards view.
const CardChrome: React.FC<{
    card: BoardCard;
    size?: 'sm' | 'md';
    onClick?: (e: React.MouseEvent) => void;
}> = ({ card, size = 'sm', onClick }) => {
    const isNote = card.type === 'note';
    const noteColor = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
    const meta = TYPE_META[card.type];
    const TypeIcon = meta.icon;
    const lane = LANE_META[card.lane] || LANE_META.backlog;
    const LaneIcon = lane.icon;
    const sizeCls = size === 'sm' ? 'text-[9px] px-1.5 py-0.5' : 'text-[10px] px-2 py-0.5';

    return (
        <div className={cn('flex items-center gap-1.5', onClick && 'cursor-pointer')} onClick={onClick}>
            <span className={cn('inline-flex items-center gap-1 rounded-full font-extrabold uppercase tracking-wider shadow-sm ring-1 ring-inset', meta.color, meta.ring, sizeCls)}>
                <TypeIcon className="h-2.5 w-2.5" />
                {meta.label}
            </span>
            <span className={cn('inline-flex items-center gap-1 rounded-full border font-bold', lane.bg, lane.border, sizeCls)}>
                <LaneIcon className="h-2.5 w-2.5" />
                <span>{__(lane.labelKey)}</span>
            </span>
            {isNote && (
                <span className={cn('inline-flex h-2 w-2 rounded-full ring-1 ring-inset ring-white/40', noteColor.swatch)} />
            )}
        </div>
    );
};

const BoardGridView: React.FC<{
    cards: BoardCard[];
    onOpenMenu: (card: BoardCard, x: number, y: number) => void;
    onView: (card: BoardCard) => void;
    onEdit: (card: BoardCard) => void;
    onDelete: (card: BoardCard) => void;
    readOnly: boolean;
    highlightedKey?: string | null;
    projectId: ProjectBoardProps['projectId'];
    guestMode?: boolean;
    shareToken?: string | null;
    makeCountHandler: (cardKey: string) => (count: number) => void;
}> = ({ cards, onOpenMenu, onView, onEdit, onDelete, readOnly, highlightedKey, projectId, guestMode = false, shareToken = null, makeCountHandler }) => {
    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {cards.map((card) => {
                const isNote = card.type === 'note';
                const noteColor = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
                const cardKey = `${card.type}:${card.id}`;
                const isHighlighted = cardKey === highlightedKey;
                return (
                    <div
                        key={cardKey}
                        data-card-key={cardKey}
                        onClick={() => onView(card)}
                        onContextMenu={(e) => {
                            if (readOnly) return;
                            e.preventDefault();
                            onOpenMenu(card, e.clientX, e.clientY);
                        }}
                        className={cn(
                            'group relative flex h-full flex-col justify-between gap-3 rounded-2xl border p-3.5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md cursor-pointer',
                            isHighlighted && 'ring-2 ring-emerald-400 ring-offset-2',
                            isNote ? cn(noteColor.bg, noteColor.border, noteColor.text) : 'border-slate-200 bg-white text-slate-900',
                        )}
                    >
                        <div className="space-y-2">
                            <CardChrome card={card} size="sm" />
                            <h3 className="line-clamp-2 text-xs font-extrabold leading-snug">
                                {card.title}
                            </h3>
                            {isNote && card.content && (
                                <p className="line-clamp-3 text-[11px] leading-relaxed opacity-80 break-words">
                                    {card.content}
                                </p>
                            )}
                            {!isNote && card.description && (
                                <p className="line-clamp-2 text-[11px] text-slate-500 leading-relaxed">
                                    {card.description}
                                </p>
                            )}
                            {card.category && (
                                <BoardCategoryChip category={card.category} />
                            )}
                        </div>
                        <div className="flex items-center justify-between gap-1 border-t border-slate-100/50 pt-2" onClick={(e) => e.stopPropagation()}>
                            <CommentsPopover
                                card={card}
                                projectId={projectId}
                                guestMode={guestMode}
                                shareToken={shareToken}
                                initialCount={card.comments_count}
                                onCountChange={makeCountHandler(`${card.type}-${card.id}`)}
                            />
                            {!readOnly && (
                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onClick={() => onEdit(card)} className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors" title="Edit">
                                        <Edit3 className="h-3.5 w-3.5" />
                                    </button>
                                    <button onClick={() => onDelete(card)} className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

const BoardLinesView: React.FC<{
    cards: BoardCard[];
    onOpenMenu: (card: BoardCard, x: number, y: number) => void;
    onView: (card: BoardCard) => void;
    onEdit: (card: BoardCard) => void;
    onDelete: (card: BoardCard) => void;
    readOnly: boolean;
    projectId: ProjectBoardProps['projectId'];
    guestMode?: boolean;
    shareToken?: string | null;
    makeCountHandler: (cardKey: string) => (count: number) => void;
}> = ({ cards, onOpenMenu, onView, onEdit, onDelete, readOnly, projectId, guestMode = false, shareToken = null, makeCountHandler }) => {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <ul className="divide-y divide-slate-100">
                {cards.map((card) => {
                    const isNote = card.type === 'note';
                    const noteColor = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
                    const cardKey = `${card.type}:${card.id}`;
                    return (
                        <li
                            key={cardKey}
                            data-card-key={cardKey}
                            onClick={() => onView(card)}
                            onContextMenu={(e) => {
                                if (readOnly) return;
                                e.preventDefault();
                                onOpenMenu(card, e.clientX, e.clientY);
                            }}
                            className={cn(
                                'group flex items-center gap-3 px-4 py-2.5 transition-colors cursor-pointer',
                                isNote ? noteColor.bg : 'hover:bg-slate-50',
                            )}
                        >
                            <div className="min-w-0 flex-1 flex items-center gap-3">
                                <CardChrome card={card} size="sm" />
                                <span className="truncate text-xs font-semibold text-slate-800">{card.title}</span>
                                {card.category && (
                                    <span className="hidden md:inline-flex">
                                        <BoardCategoryChip category={card.category} />
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
                                <CommentsPopover
                                    card={card}
                                    projectId={projectId}
                                    guestMode={guestMode}
                                    shareToken={shareToken}
                                    initialCount={card.comments_count}
                                    onCountChange={makeCountHandler(`${card.type}-${card.id}`)}
                                />
                                {!readOnly && (
                                    <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button onClick={() => onEdit(card)} className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors" title="Edit">
                                            <Edit3 className="h-3.5 w-3.5" />
                                        </button>
                                        <button onClick={() => onDelete(card)} className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                )}
                            </div>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
};

const BoardTableView: React.FC<{
    cards: BoardCard[];
    categories: BoardCategory[];
    onView: (card: BoardCard) => void;
    onEdit: (card: BoardCard) => void;
    onDelete: (card: BoardCard) => void;
    onOpenMenu: (card: BoardCard, x: number, y: number) => void;
    readOnly: boolean;
    projectId: ProjectBoardProps['projectId'];
    guestMode?: boolean;
    shareToken?: string | null;
    makeCountHandler: (cardKey: string) => (count: number) => void;
}> = ({ cards, categories, onView, onEdit, onDelete, onOpenMenu, readOnly, projectId, guestMode = false, shareToken = null, makeCountHandler }) => {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-xs">
                    <thead className="bg-slate-50">
                        <tr>
                            <th scope="col" className="px-4 py-2.5 text-start text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('general.board_sort_type') || 'Type'}</th>
                            <th scope="col" className="px-4 py-2.5 text-start text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('general.board_sort_title') || 'Title'}</th>
                            <th scope="col" className="px-4 py-2.5 text-start text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('general.board_sort_lane') || 'Status'}</th>
                            <th scope="col" className="px-4 py-2.5 text-start text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('general.board_sort_category') || 'Category'}</th>
                            <th scope="col" className="px-4 py-2.5 text-start text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('general.board_sort_priority') || 'Priority'}</th>
                            <th scope="col" className="px-4 py-2.5 text-start text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{__('general.comments') || 'Comments'}</th>
                            <th scope="col" className="px-4 py-2.5 text-end text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {cards.map((card) => {
                            const cardKey = `${card.type}:${card.id}`;
                            const priorityCls = card.priority ? PRIORITY_STYLES[card.priority] : null;
                            return (
                                <tr
                                    key={cardKey}
                                    data-card-key={cardKey}
                                    onClick={() => onView(card)}
                                    onContextMenu={(e) => {
                                        if (readOnly) return;
                                        e.preventDefault();
                                        onOpenMenu(card, e.clientX, e.clientY);
                                    }}
                                    className="cursor-pointer hover:bg-slate-50/80 transition-colors"
                                >
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        <CardChrome card={card} size="sm" />
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <span className="font-semibold text-slate-800">{card.title}</span>
                                        {card.description && (
                                            <p className="line-clamp-1 text-[10px] text-slate-500 mt-0.5">{card.description}</p>
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap text-slate-600">
                                        {__(LANE_META[card.lane]?.labelKey ?? 'general.lane_backlog')}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {card.category ? (
                                            <BoardCategoryChip category={card.category} />
                                        ) : (
                                            <span className="text-slate-300">—</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                        {priorityCls ? (
                                            <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-bold uppercase ring-1 ring-inset', priorityCls)}>
                                                {card.priority}
                                            </span>
                                        ) : (
                                            <span className="text-slate-300">—</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                                        <CommentsPopover
                                            card={card}
                                            projectId={projectId}
                                            guestMode={guestMode}
                                            shareToken={shareToken}
                                            initialCount={card.comments_count}
                                            onCountChange={makeCountHandler(`${card.type}-${card.id}`)}
                                        />
                                    </td>
                                    <td className="px-4 py-2.5 whitespace-nowrap text-end" onClick={(e) => e.stopPropagation()}>
                                        {!readOnly ? (
                                            <div className="inline-flex items-center gap-1">
                                                <button onClick={() => onEdit(card)} className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors" title="Edit">
                                                    <Edit3 className="h-3.5 w-3.5" />
                                                </button>
                                                <button onClick={() => onDelete(card)} className="inline-flex h-6 w-6 items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        ) : (
                                            <span className="text-slate-300">—</span>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
};
