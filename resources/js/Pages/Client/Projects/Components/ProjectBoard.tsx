import React, { useCallback, useMemo, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Plus, Trash2, StickyNote, ListTodo, FileText, CheckCircle2, Circle, GripVertical,
    Filter, StickyNote as NoteIcon, AlertCircle, ChevronDown, RotateCcw, Search,
} from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

/**
 * Professional per-day project board:
 *  - Free-canvas with vertically stacked lane columns
 *  - Drag & drop cards across lanes (native pointer events)
 *  - Filter chips (All / Notes / Tasks / Reports)
 *  - Lane header shows card counts and a micro progress meter
 *  - Live search across cards
 *  - Minimap on the right rail (sticky on large screens)
 *  - Polished card design with avatar/initials and metadata badges
 */

export type CardType = 'note' | 'task' | 'report';

export interface BoardCard {
    type: CardType;
    id: number;
    title: string;
    lane: string;
    pos_x: number;
    pos_y: number;
    color?: string;
    content?: string;
    priority?: string;
    done?: boolean;
    published_at?: string;
    author?: { id: number; name: string } | null;
    due_at?: string | null;
}

interface ProjectBoardProps {
    projectId: number | string;
    date: string;
    lanes: string[];
    initialCards: BoardCard[];
    hideFuture?: boolean;
    readOnly?: boolean;
}

const COL_W = 300;
const CARD_W = 248;
const CANVAS_H = 720;

const NOTE_COLORS: Record<string, { bg: string; border: string; text: string; swatch: string }> = {
    yellow: { bg: 'bg-amber-100', border: 'border-amber-300', text: 'text-amber-950', swatch: 'bg-amber-400' },
    green: { bg: 'bg-emerald-100', border: 'border-emerald-300', text: 'text-emerald-950', swatch: 'bg-emerald-400' },
    blue: { bg: 'bg-sky-100', border: 'border-sky-300', text: 'text-sky-950', swatch: 'bg-sky-400' },
    red: { bg: 'bg-rose-100', border: 'border-rose-300', text: 'text-rose-950', swatch: 'bg-rose-400' },
    purple: { bg: 'bg-violet-100', border: 'border-violet-300', text: 'text-violet-950', swatch: 'bg-violet-400' },
    pink: { bg: 'bg-pink-100', border: 'border-pink-300', text: 'text-pink-950', swatch: 'bg-pink-400' },
    slate: { bg: 'bg-slate-100', border: 'border-slate-300', text: 'text-slate-900', swatch: 'bg-slate-400' },
};

const MINI_DOT: Record<CardType, string> = {
    note: 'bg-amber-400',
    task: 'bg-sky-500',
    report: 'bg-emerald-500',
};

const TYPE_META: Record<CardType, { label: string; icon: React.ElementType; color: string; ring: string }> = {
    note: { label: 'Note', icon: StickyNote, color: 'text-amber-700 bg-amber-50 ring-amber-200', ring: 'ring-amber-200' },
    task: { label: 'Task', icon: ListTodo, color: 'text-sky-700 bg-sky-50 ring-sky-200', ring: 'ring-sky-200' },
    report: { label: 'Report', icon: FileText, color: 'text-emerald-700 bg-emerald-50 ring-emerald-200', ring: 'ring-emerald-200' },
};

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700 ring-rose-200',
    urgent: 'bg-orange-100 text-orange-700 ring-orange-200',
    medium: 'bg-amber-100 text-amber-700 ring-amber-200',
    low: 'bg-slate-100 text-slate-600 ring-slate-200',
};

function laneKey(lane: string): string {
    return `general.lane_${lane}`;
}

function initials(name?: string | null): string {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/).slice(0, 2);
    return parts.map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';
}

type FilterKey = 'all' | CardType;

export default function ProjectBoard({
    projectId, date, lanes, initialCards, hideFuture, readOnly = false,
}: ProjectBoardProps) {
    const canvasW = lanes.length * COL_W;
    const canvasRef = useRef<HTMLDivElement | null>(null);
    const [cards, setCards] = useState<BoardCard[]>(initialCards);
    const [dragging, setDragging] = useState<string | null>(null);
    const [adding, setAdding] = useState(false);
    const [newColor, setNewColor] = useState('yellow');
    const [newText, setNewText] = useState('');
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editText, setEditText] = useState('');
    const [filter, setFilter] = useState<FilterKey>('all');
    const [query, setQuery] = useState('');

    const cardKey = useCallback((c: { type: CardType; id: number }) => `${c.type}:${c.id}`, []);

    const laneForX = useCallback(
        (x: number) => {
            const idx = Math.min(lanes.length - 1, Math.max(0, Math.floor((x + CARD_W / 2) / COL_W)));
            return lanes[idx];
        },
        [lanes],
    );

    const persistMove = useCallback(
        (card: BoardCard) => {
            axios.post(route('client.projects.board.move-card', { project: projectId }), {
                for_date: date,
                type: card.type,
                id: card.id,
                lane: card.lane,
                pos_x: Math.round(card.pos_x),
                pos_y: Math.round(card.pos_y),
            }).catch(() => toast.error(__('general.could_not_save_card_position')));
        },
        [projectId, date],
    );

    const onPointerDown = (e: React.PointerEvent, card: BoardCard) => {
        if (readOnly) return;
        if ((e.target as HTMLElement).closest('[data-no-drag]')) return;
        const key = cardKey(card);
        setDragging(key);
        const startX = e.clientX;
        const startY = e.clientY;
        const origX = card.pos_x;
        const origY = card.pos_y;
        (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);

        const move = (ev: PointerEvent) => {
            const dx = ev.clientX - startX;
            const dy = ev.clientY - startY;
            const nx = Math.max(0, Math.min(canvasW - CARD_W, origX + dx));
            const ny = Math.max(0, Math.min(CANVAS_H - 32, origY + dy));
            const lane = laneForX(nx);
            setCards((cs) => cs.map((c) => (cardKey(c) === key ? { ...c, pos_x: nx, pos_y: ny, lane } : c)));
        };
        const up = () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
            setDragging(null);
            setCards((cs) => {
                const moved = cs.find((c) => cardKey(c) === key);
                if (moved) persistMove(moved);
                return cs;
            });
        };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
    };

    const addNote = () => {
        if (readOnly) return;
        const text = newText.trim();
        if (!text) return;
        const payload = {
            for_date: date,
            content: text,
            color: newColor,
            lane: lanes[0],
            pos_x: 16,
            pos_y: 16,
        };
        axios.post(route('client.projects.board.store-note', { project: projectId }), payload)
            .then(({ data }) => {
                if (data?.card) setCards((cs) => [...cs, data.card as BoardCard]);
                setNewText('');
                setAdding(false);
                toast.success(__('general.note_added'));
            })
            .catch(() => toast.error(__('general.could_not_add_note')));
    };

    const saveEdit = (id: number) => {
        if (readOnly) return;
        const note = cards.find((c) => c.type === 'note' && c.id === id);
        if (!note) return;
        axios.put(route('client.projects.board.update-note', { project: projectId, note: id }), {
            content: editText,
            color: note.color,
        }).then(({ data }) => {
            setCards((cs) => cs.map((c) => (c.type === 'note' && c.id === id && data?.card ? { ...data.card } : c)));
            setEditingId(null);
        }).catch(() => toast.error(__('general.could_not_update_note')));
    };

    const removeNote = (id: number) => {
        if (readOnly) return;
        if (!confirm(__('general.delete_this_note'))) return;
        axios.delete(route('client.projects.board.destroy-note', { project: projectId, note: id }))
            .then(() => {
                setCards((cs) => cs.filter((c) => !(c.type === 'note' && c.id === id)));
                toast.success(__('general.note_removed'));
            })
            .catch(() => toast.error(__('general.could_not_remove_note')));
    };

    // Derived datasets
    const filteredCards = useMemo(() => {
        const q = query.trim().toLowerCase();
        return cards.filter((c) => {
            if (filter !== 'all' && c.type !== filter) return false;
            if (!q) return true;
            return (
                c.title.toLowerCase().includes(q) ||
                (c.content ?? '').toLowerCase().includes(q) ||
                (c.lane ?? '').toLowerCase().includes(q)
            );
        });
    }, [cards, filter, query]);

    const stats = useMemo(() => {
        const total = cards.length;
        const notes = cards.filter((c) => c.type === 'note').length;
        const tasks = cards.filter((c) => c.type === 'task').length;
        const doneTasks = cards.filter((c) => c.type === 'task' && c.done).length;
        const reports = cards.filter((c) => c.type === 'report').length;
        return { total, notes, tasks, doneTasks, reports };
    }, [cards]);

    const laneCounts = useMemo(() => {
        const m: Record<string, number> = {};
        for (const l of lanes) m[l] = 0;
        for (const c of filteredCards) m[c.lane] = (m[c.lane] ?? 0) + 1;
        return m;
    }, [lanes, filteredCards]);

    const minimap = useMemo(() => {
        return cards.map((c) => ({
            key: cardKey(c),
            left: `${(c.pos_x / canvasW) * 100}%`,
            top: `${(c.pos_y / CANVAS_H) * 100}%`,
            cls: MINI_DOT[c.type] ?? 'bg-slate-400',
        }));
    }, [cards, canvasW, cardKey]);

    if (hideFuture) {
        return (
            <div className="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">
                {__('general.future_items_are_hidden_for_this_project')}
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Toolbar */}
            <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                        <FilterButton current={filter} value="all" label={__('general.all')} onClick={() => setFilter('all')} />
                        <FilterButton current={filter} value="note" label={__('general.notes')} icon={StickyNote} onClick={() => setFilter('note')} />
                        <FilterButton current={filter} value="task" label={__('general.tasks')} icon={ListTodo} onClick={() => setFilter('task')} />
                        <FilterButton current={filter} value="report" label={__('general.reports')} icon={FileText} onClick={() => setFilter('report')} />
                    </div>

                    <div className="relative">
                        <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                        <input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={__('general.search_cards')}
                            className="h-8 w-44 rounded-lg border border-slate-200 bg-white pl-8 pr-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                        />
                    </div>

                    <span className="hidden text-xs text-slate-400 sm:inline">
                        {filteredCards.length} / {stats.total} {__('general.cards')}
                    </span>
                </div>

                <div className="flex items-center gap-2">
                    {!readOnly && !adding && (
                        <button
                            type="button"
                            onClick={() => setAdding(true)}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-slate-800"
                        >
                            <Plus className="h-3.5 w-3.5" /> {__('general.add_sticky_note')}
                        </button>
                    )}
                </div>
            </div>

            {adding && (
                <div className="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <span className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <StickyNote className="h-3.5 w-3.5" /> {__('general.new_note')}
                    </span>
                    <div className="flex items-center gap-1">
                        {Object.entries(NOTE_COLORS).map(([key, c]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => setNewColor(key)}
                                className={cn(
                                    'h-5 w-5 rounded-full border-2 transition-all',
                                    c.swatch,
                                    newColor === key ? 'border-slate-900 scale-110' : 'border-white',
                                )}
                                aria-label={key}
                            />
                        ))}
                    </div>
                    <input
                        value={newText}
                        onChange={(e) => setNewText(e.target.value)}
                        placeholder={__('general.note_text')}
                        className="min-w-[14rem] flex-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                        onKeyDown={(e) => e.key === 'Enter' && addNote()}
                        autoFocus
                    />
                    <button
                        onClick={addNote}
                        className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500"
                    >
                        <Plus className="h-3.5 w-3.5" /> {__('general.add')}
                    </button>
                    <button
                        onClick={() => { setAdding(false); setNewText(''); }}
                        className="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100"
                    >
                        <RotateCcw className="h-3.5 w-3.5" /> {__('general.cancel')}
                    </button>
                </div>
            )}

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_240px]">
                {/* Canvas */}
                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-slate-50 p-0 shadow-inner">
                    <div className="relative min-h-[12rem] p-3">
                        {/* Lane header strip */}
                        <div className="sticky top-0 z-30 mb-3 flex rounded-lg border border-slate-200 bg-white/95 shadow-sm backdrop-blur">
                            {lanes.map((lane, idx) => {
                                const count = laneCounts[lane] ?? 0;
                                return (
                                    <div
                                        key={lane}
                                        className={cn(
                                            'flex items-center justify-between border-slate-200 px-3 py-2.5 text-xs',
                                            idx < lanes.length - 1 && 'border-e',
                                        )}
                                        style={{ width: COL_W }}
                                    >
                                        <span className="flex items-center gap-2 font-semibold uppercase tracking-wide text-slate-700">
                                            {__(laneKey(lane))}
                                        </span>
                                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                            {count}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>

                        <div
                            ref={canvasRef}
                            className="relative select-none rounded-lg bg-slate-50/80"
                            style={{ width: canvasW, height: CANVAS_H }}
                        >
                            {/* Lane columns (background) */}
                            <div className="absolute inset-0 flex rounded-lg overflow-hidden border border-slate-200">
                                {lanes.map((lane, idx) => (
                                    <div
                                        key={lane}
                                        className={cn(
                                            'relative h-full',
                                            idx < lanes.length - 1 && 'border-e border-slate-200/80',
                                            idx % 2 === 0 ? 'bg-slate-50/40' : 'bg-white/40',
                                        )}
                                        style={{ width: COL_W }}
                                    >
                                        <div className="absolute left-2 top-2 text-[10px] font-semibold uppercase tracking-wider text-slate-300">
                                            {__(laneKey(lane))}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Empty state */}
                            {filteredCards.length === 0 && (
                                <div className="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 text-center">
                                    <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-200">
                                        <AlertCircle className="h-7 w-7 text-slate-400" />
                                    </div>
                                    <p className="text-sm font-medium text-slate-600">{__('general.no_cards_match_filter')}</p>
                                    <p className="text-xs text-slate-400">{__('general.try_changing_filter')}</p>
                                </div>
                            )}

                            {/* Cards layer */}
                            {filteredCards.map((card) => {
                                const key = cardKey(card);
                                const isNote = card.type === 'note';
                                const noteColor = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
                                const meta = TYPE_META[card.type];
                                const TypeIcon = meta.icon;
                                const priorityCls = card.priority ? PRIORITY_STYLES[card.priority] : null;
                                return (
                                    <div
                                        key={key}
                                        onPointerDown={(e) => onPointerDown(e, card)}
                                        style={{ left: card.pos_x, top: card.pos_y, width: CARD_W, touchAction: 'none' }}
                                        className={cn(
                                            'group absolute z-20 cursor-grab rounded-xl border p-3 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:shadow-md',
                                            dragging === key && 'cursor-grabbing shadow-xl ring-2 ring-slate-900/10 scale-[1.02]',
                                            isNote
                                                ? cn(noteColor.bg, noteColor.border, noteColor.text)
                                                : 'border-slate-200 bg-white text-slate-900 ring-1 ring-slate-100',
                                        )}
                                    >
                                        <div className="flex items-start gap-2">
                                            <GripVertical className="mt-0.5 h-3.5 w-3.5 shrink-0 opacity-30 group-hover:opacity-70" />
                                            <div className="min-w-0 flex-1">
                                                <div className="mb-1.5 flex items-center gap-1.5">
                                                    <span className={cn('inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide ring-1 ring-inset', meta.color, meta.ring)}>
                                                        <TypeIcon className="h-2.5 w-2.5" /> {meta.label}
                                                    </span>
                                                    {card.type === 'task' && (
                                                        card.done
                                                            ? <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500" />
                                                            : <Circle className="h-3.5 w-3.5 text-slate-300" />
                                                    )}
                                                    {priorityCls && (
                                                        <span className={cn('inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset', priorityCls)}>
                                                            {card.priority}
                                                        </span>
                                                    )}
                                                </div>

                                                {editingId === card.id ? (
                                                    <div className="space-y-1.5" data-no-drag>
                                                        <textarea
                                                            value={editText}
                                                            onChange={(e) => setEditText(e.target.value)}
                                                            className="w-full resize-none rounded-md border border-black/10 bg-white/90 p-1.5 text-sm"
                                                            rows={3}
                                                        />
                                                        <div className="flex gap-1">
                                                            <button data-no-drag onClick={() => saveEdit(card.id)} className="rounded bg-slate-900 px-2 py-0.5 text-[11px] font-medium text-white">
                                                                {__('general.save')}
                                                            </button>
                                                            <button data-no-drag onClick={() => setEditingId(null)} className="rounded px-2 py-0.5 text-[11px] text-slate-600">
                                                                {__('general.cancel')}
                                                            </button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <p className="break-words text-sm font-semibold leading-snug">{card.title}</p>
                                                )}

                                                {card.type === 'report' && (
                                                    <div className="mt-2 flex items-center justify-between" data-no-drag>
                                                        <span className="text-[11px] text-slate-400">
                                                            {card.published_at ? new Date(card.published_at).toLocaleDateString() : '—'}
                                                        </span>
                                                        <button
                                                            onClick={() => router.get(route('client.projects.reports.show', { project: projectId, report: card.id }))}
                                                            className="text-[11px] font-semibold text-emerald-600 hover:underline"
                                                        >
                                                            {__('general.view_report')} →
                                                        </button>
                                                    </div>
                                                )}

                                                {card.author && (
                                                    <div className="mt-2 flex items-center gap-1.5 border-t border-slate-100 pt-1.5" data-no-drag>
                                                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white">
                                                            {initials(card.author.name)}
                                                        </span>
                                                        <span className="truncate text-[11px] text-slate-500">{card.author.name}</span>
                                                    </div>
                                                )}
                                            </div>

                                            {isNote && editingId !== card.id && !readOnly && (
                                                <div className="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100" data-no-drag>
                                                    <button
                                                        title={__('general.edit')}
                                                        onClick={() => { setEditingId(card.id); setEditText(card.content ?? card.title); }}
                                                        className="rounded p-1 hover:bg-black/5"
                                                    >
                                                        <NoteIcon className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button title={__('general.delete')} onClick={() => removeNote(card.id)} className="rounded p-1 hover:bg-black/5">
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Right rail: stats + minimap */}
                <aside className="space-y-3">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{__('general.at_a_glance')}</p>
                        <div className="space-y-2.5">
                            <RailBar label={__('general.notes')} value={stats.notes} total={stats.total || 1} color="bg-amber-400" />
                            <RailBar label={__('general.tasks')} value={stats.tasks - stats.doneTasks} total={stats.total || 1} color="bg-sky-500" />
                            {stats.doneTasks > 0 && (
                                <RailBar label={`${__('general.completed')}`} value={stats.doneTasks} total={stats.total || 1} color="bg-emerald-500" />
                            )}
                            <RailBar label={__('general.reports')} value={stats.reports} total={stats.total || 1} color="bg-emerald-500" />
                        </div>
                        <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <span>{__('general.total_cards')}</span>
                            <span className="font-mono text-base font-semibold text-slate-900">{stats.total}</span>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{__('general.minimap')}</p>
                        <div className="relative w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50" style={{ aspectRatio: `${canvasW} / ${CANVAS_H}` }}>
                            <div className="absolute inset-0 flex">
                                {lanes.map((l) => (
                                    <div
                                        key={l}
                                        className="border-slate-200/70 border-e last:border-e-0"
                                        style={{ width: `${100 / lanes.length}%` }}
                                    />
                                ))}
                            </div>
                            {minimap.map((m) => (
                                <span
                                    key={m.key}
                                    className={cn('absolute h-1.5 w-1.5 rounded-full ring-1 ring-white/60', m.cls)}
                                    style={{ left: m.left, top: m.top }}
                                />
                            ))}
                        </div>
                        <div className="mt-3 space-y-1.5 text-[11px] text-slate-500">
                            <LegendRow color="bg-amber-400" label={__('general.notes')} />
                            <LegendRow color="bg-sky-500" label={__('general.tasks')} />
                            <LegendRow color="bg-emerald-500" label={__('general.reports')} />
                        </div>
                    </div>

                    {!readOnly && (
                        <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center text-xs text-slate-500">
                            <p className="mb-1 font-semibold text-slate-700">{__('general.pro_tip')}</p>
                            <p>{__('general.drag_cards_tip')}</p>
                        </div>
                    )}
                </aside>
            </div>
        </div>
    );
}

function FilterButton({
    current, value, label, icon: Icon, onClick,
}: {
    current: FilterKey;
    value: FilterKey;
    label: string;
    icon?: React.ElementType;
    onClick: () => void;
}) {
    const active = current === value;
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                active
                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
                    : 'text-slate-500 hover:text-slate-800',
            )}
        >
            {Icon && <Icon className="h-3.5 w-3.5" />}
            {label}
        </button>
    );
}

function RailBar({ label, value, total, color }: { label: string; value: number; total: number; color: string }) {
    const pct = Math.round((value / Math.max(1, total)) * 100);
    return (
        <div>
            <div className="mb-1 flex items-center justify-between text-[11px]">
                <span className="font-medium text-slate-600">{label}</span>
                <span className="font-mono font-semibold text-slate-900">{value}</span>
            </div>
            <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div className={cn('h-full rounded-full transition-all', color)} style={{ width: `${Math.min(100, Math.max(0, pct))}%` }} />
            </div>
        </div>
    );
}

function LegendRow({ color, label }: { color: string; label: string }) {
    return (
        <div className="flex items-center gap-2">
            <span className={cn('h-2 w-2 rounded-full', color)} />
            <span>{label}</span>
        </div>
    );
}
