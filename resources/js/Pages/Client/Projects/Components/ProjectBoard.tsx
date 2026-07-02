import React, { useCallback, useMemo, useRef, useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Plus, Trash2, StickyNote, ListTodo, FileText, CheckCircle2, Circle, GripVertical,
    Filter, StickyNote as NoteIcon, AlertCircle, ChevronDown, RotateCcw, Search, Paperclip,
    ClipboardList, Download, Edit3, X, UploadCloud, CalendarDays, BarChart, Eye
} from 'lucide-react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import {
    FaRegStickyNote, FaBolt, FaSearch, FaCheckCircle, FaGlobe, FaRegClipboard
} from 'react-icons/fa';
import type { IconType } from 'react-icons';
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
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';

export type CardType = 'note' | 'task' | 'report' | 'todo' | 'file';

export interface BoardCard {
    type: CardType;
    id: number;
    title: string;
    lane: string;
    pos_x: number;
    pos_y: number;
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

export default function ProjectBoard({
    projectId, date, lanes, initialCards, hideFuture, readOnly = false,
    externalFilter,
}: ProjectBoardProps) {
    const [cards, setCards] = useState<BoardCard[]>(initialCards);
    const [query, setQuery] = useState('');
    
    const [statusPopover, setStatusPopover] = useState<{ cardId: number; type: CardType; x: number; y: number } | null>(null);
    const [contextMenu, setContextMenu] = useState<{ card: BoardCard; x: number; y: number } | null>(null);

    const [activeModal, setActiveModal] = useState<{
        type: 'note' | 'task' | 'todo' | 'file' | 'report';
        action: 'create' | 'edit';
        cardId?: number;
    } | null>(null);

    const [noteForm, setNoteForm] = useState({ content: '', color: 'yellow' });
    const [taskForm, setTaskForm] = useState({ task_name: '', task_description: '', priority: 'normal' });
    const [todoForm, setTodoForm] = useState({ title: '', description: '', completed: false, checklist: [] as { id?: number; title: string; is_completed: boolean }[] });
    const [newCheckItem, setNewCheckItem] = useState('');
    const [fileForm, setFileForm] = useState<File | null>(null);
    const [reportForm, setReportForm] = useState({ title: '', body: '', published_at: '' });
    const [viewingReport, setViewingReport] = useState<BoardCard | null>(null);
    const [uploading, setUploading] = useState(false);
    const [highlightedCardKey, setHighlightedCardKey] = useState<string | null>(null);
    const highlightTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

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
        return cards.filter((c) => {
            if (externalFilter && externalFilter !== 'all') {
                const isLane = lanes.includes(externalFilter);
                if (isLane && c.lane !== externalFilter) return false;
                if (!isLane && c.type !== externalFilter) return false;
            }
            if (!q) return true;
            return (
                c.title.toLowerCase().includes(q) ||
                (c.description ?? '').toLowerCase().includes(q) ||
                (c.content ?? '').toLowerCase().includes(q)
            );
        });
    }, [cards, externalFilter, query, lanes]);

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

    const reportHtml = useMemo(() => {
        if (!viewingReport?.body) return '';
        const raw = marked.parse(viewingReport.body, { async: false }) as string;
        return DOMPurify.sanitize(raw);
    }, [viewingReport?.body]);

    const openCreateModal = (type: 'note' | 'task' | 'todo' | 'file' | 'report') => {
        setNoteForm({ content: '', color: 'yellow' });
        setTaskForm({ task_name: '', task_description: '', priority: 'normal' });
        setTodoForm({ title: '', description: '', completed: false, checklist: [] });
        setFileForm(null);
        setReportForm({ title: '', body: '', published_at: date });
        setActiveModal({ type, action: 'create' });
    };

    const openEditModal = (card: BoardCard) => {
        if (card.type === 'note') {
            setNoteForm({ content: card.content || card.title, color: card.color || 'yellow' });
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
            ? { content: noteForm.content, color: noteForm.color }
            : { for_date: date, content: noteForm.content, color: noteForm.color, lane: 'backlog' };

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
            <div className="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="relative w-72">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder={__('general.search_cards') || 'Search board cards...'}
                        className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-xs text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-300 transition-all"
                    />
                </div>

                {!readOnly && (
                    <div className="flex gap-2">
                        <button
                            onClick={() => openCreateModal('note')}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm"
                        >
                            <Plus className="h-4 w-4 text-amber-500" /> {__('general.board_add_note')}
                        </button>
                        <button
                            onClick={() => openCreateModal('task')}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm"
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
            ) : (
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                    {filteredCards.map((card) => {
                        const isNote = card.type === 'note';
                        const noteColor = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
                        const meta = TYPE_META[card.type];
                        const TypeIcon = meta.icon;
                        const priorityCls = card.priority ? PRIORITY_STYLES[card.priority] : null;
                        const lane = LANE_META[card.lane] || LANE_META.backlog;
                        const LaneIcon = lane.icon;
                        const cardKey = `${card.type}:${card.id}`;
                        const isHighlighted = cardKey === highlightedCardKey;

                        return (
                            <div
                                key={cardKey}
                                data-card-key={cardKey}
                                onContextMenu={(e) => {
                                    if (readOnly) return;
                                    e.preventDefault();
                                    setContextMenu({ card, x: e.clientX, y: e.clientY });
                                }}
                                className={cn(
                                    'group relative flex flex-col justify-between rounded-2xl border p-4 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 ease-out cursor-pointer',
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
                                    <div className="flex items-center justify-between">
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

                                    <div className="space-y-1.5" onClick={() => !readOnly && openEditModal(card)}>
                                        <h3 className="line-clamp-2 text-sm font-extrabold leading-snug tracking-tight">
                                            {card.title}
                                        </h3>
                                        {card.description && (
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
                                            <div className="mt-2 flex items-center justify-between bg-slate-50 p-2.5 rounded-xl border border-slate-100" onClick={(e) => e.stopPropagation()}>
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-[10px] font-bold text-slate-700">{card.mime}</p>
                                                    <p className="text-[10px] font-mono text-slate-500">{card.human_size}</p>
                                                </div>
                                                {card.download_url && (
                                                    <a
                                                        href={card.download_url}
                                                        className="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm"
                                                        title="Download file"
                                                    >
                                                        <Download className="h-3.5 w-3.5" />
                                                    </a>
                                                )}
                                            </div>
                                        )}

                                        {card.type === 'report' && (
                                            <div className="mt-2 flex items-center justify-between" onClick={(e) => e.stopPropagation()}>
                                                <span className="text-[10px] text-slate-400 inline-flex items-center gap-1 font-semibold">
                                                    <CalendarDays className="h-3 w-3" />
                                                    {card.published_at ? new Date(card.published_at).toLocaleDateString() : date}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => setViewingReport(card)}
                                                    className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100 hover:ring-emerald-300 transition-colors"
                                                >
                                                    <Eye className="h-3 w-3" />
                                                    {__('general.view') || 'View'}
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {!readOnly && (
                                    <div className="mt-3 flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity pt-2 border-t border-slate-100/50">
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
                        );
                    })}
                </div>
            )}

            {statusPopover && (
                <div 
                    className="fixed z-50 flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/95 p-1.5 shadow-xl backdrop-blur animate-in fade-in slide-in-from-top-1 duration-150"
                    style={{ left: statusPopover.x, top: statusPopover.y + 4 }}
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
                                    'flex h-8 w-8 items-center justify-center rounded-full text-base transition-all hover:scale-125 hover:bg-slate-50 shadow-sm active:scale-90',
                                )}
                                title={__(meta.labelKey)}
                            >
                                <LaneIcon className="h-4 w-4" />
                            </button>
                        );
                    })}
                </div>
            )}

            {contextMenu && (
                <div
                    className="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 shadow-2xl animate-in zoom-in-95 duration-100"
                    style={{ left: contextMenu.x, top: contextMenu.y }}
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="px-3 py-1.5 text-[10px] font-bold text-slate-400 border-b border-slate-100 uppercase tracking-wider">
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
                </div>
            )}

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
                            <Label className="text-xs font-bold text-slate-600">Note Content</Label>
                            <Textarea
                                value={noteForm.content}
                                onChange={(e) => setNoteForm({ ...noteForm, content: e.target.value })}
                                placeholder="Write down your notes here..."
                                rows={6}
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

            {/* View Report Dialog */}
            <Dialog open={!!viewingReport} onOpenChange={(open) => !open && setViewingReport(null)}>
                <DialogContent className="w-full sm:max-w-3xl max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                    <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0 bg-gradient-to-b from-emerald-50/60 to-white">
                        <DialogHeader>
                            <div className="flex items-center gap-2 text-emerald-600">
                                <FileText className="h-4 w-4" />
                                <span className="text-[10px] font-bold uppercase tracking-wider">
                                    {__('general.report') || 'Report'}
                                </span>
                            </div>
                            <DialogTitle className="text-lg font-bold text-slate-900 mt-1">
                                {viewingReport?.title}
                            </DialogTitle>
                            {viewingReport?.published_at && (
                                <p className="mt-1 text-xs text-slate-400">
                                    {new Date(viewingReport.published_at).toLocaleString()}
                                </p>
                            )}
                        </DialogHeader>
                    </div>
                    <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5">
                        {reportHtml ? (
                            <article
                                className="prose prose-slate max-w-none prose-headings:font-heading prose-a:text-emerald-600 prose-pre:bg-slate-900 prose-pre:text-slate-100"
                                dangerouslySetInnerHTML={{ __html: reportHtml }}
                            />
                        ) : (
                            <p className="text-sm text-slate-400 italic text-center py-8">
                                {__('general.no_content') || 'No content available for this report.'}
                            </p>
                        )}
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0 px-6 py-3 border-t border-slate-100 bg-slate-50/60 shrink-0">
                        <a
                            href={viewingReport ? route('client.projects.board.export-report-pdf', {
                                project: projectId,
                                report: viewingReport.id,
                            }) : '#'}
                            target="_blank"
                            rel="noreferrer"
                            aria-disabled={!viewingReport}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                        >
                            <Download className="h-3.5 w-3.5" />
                            {__('general.export_as_pdf') || 'Export as PDF'}
                        </a>
                        <button
                            type="button"
                            onClick={() => setViewingReport(null)}
                            className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white"
                        >
                            {__('general.close') || 'Close'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
