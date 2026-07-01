import React, { useCallback, useMemo, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Plus, Trash2, StickyNote, ListTodo, FileText, CheckCircle2, Circle, GripVertical } from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

/**
 * Hybrid per-day board: a single free canvas sits over vertical lane columns.
 *
 * Drag-drop decision (per plan "open items"): no @dnd-kit / reactflow dependency was
 * needed for our use-case, so cards are dragged with native pointer events and
 * absolutely positioned. Positions are persisted server-side only on pointer-up
 * (one request per drop = effectively debounced), and lane is recomputed from the
 * card's horizontal position over the column grid.
 *
 * The minimap is a pure-frontend scaled projection of the same absolute positions.
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
}

interface ProjectBoardProps {
    projectId: number | string;
    date: string;
    lanes: string[];
    initialCards: BoardCard[];
    hideFuture?: boolean;
}

const COL_W = 280; // px width of each lane column
const CARD_W = 232; // px width of a card
const CANVAS_H = 640; // px height of the canvas

const NOTE_COLORS: Record<string, string> = {
    yellow: 'bg-yellow-200 border-yellow-300 text-yellow-950',
    green: 'bg-emerald-200 border-emerald-300 text-emerald-950',
    blue: 'bg-sky-200 border-sky-300 text-sky-950',
    red: 'bg-rose-200 border-rose-300 text-rose-950',
    purple: 'bg-violet-200 border-violet-300 text-violet-950',
    pink: 'bg-pink-200 border-pink-300 text-pink-950',
};

const MINI_DOT: Record<string, string> = {
    note: 'bg-amber-400',
    task: 'bg-sky-400',
    report: 'bg-emerald-400',
};

function laneKey(lane: string): string {
    return `general.lane_${lane}`;
}

export default function ProjectBoard({ projectId, date, lanes, initialCards, hideFuture }: ProjectBoardProps) {
    const canvasW = lanes.length * COL_W;
    const canvasRef = useRef<HTMLDivElement | null>(null);
    const [cards, setCards] = useState<BoardCard[]>(initialCards);
    const [dragging, setDragging] = useState<string | null>(null);
    const [adding, setAdding] = useState(false);
    const [newColor, setNewColor] = useState('yellow');
    const [newText, setNewText] = useState('');
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editText, setEditText] = useState('');

    const cardKey = useCallback((c: { type: CardType; id: number }) => `${c.type}:${c.id}`, []);

    const laneForX = useCallback((x: number) => {
        const idx = Math.min(lanes.length - 1, Math.max(0, Math.floor((x + CARD_W / 2) / COL_W)));
        return lanes[idx];
    }, [lanes]);

    const persistMove = useCallback((card: BoardCard) => {
        axios.post(route('client.projects.board.move-card', { project: projectId }), {
            for_date: date,
            type: card.type,
            id: card.id,
            lane: card.lane,
            pos_x: Math.round(card.pos_x),
            pos_y: Math.round(card.pos_y),
        }).catch(() => toast.error(__('general.could_not_save_card_position')));
    }, [projectId, date]);

    const onPointerDown = (e: React.PointerEvent, card: BoardCard) => {
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
        if (!confirm(__('general.delete_this_note'))) return;
        axios.delete(route('client.projects.board.destroy-note', { project: projectId, note: id }))
            .then(() => {
                setCards((cs) => cs.filter((c) => !(c.type === 'note' && c.id === id)));
                toast.success(__('general.note_removed'));
            })
            .catch(() => toast.error(__('general.could_not_remove_note')));
    };

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
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2 text-sm text-slate-500">
                    <StickyNote className="h-4 w-4" />
                    <span>{__('general.drag_cards_to_move')}</span>
                </div>
                <div className="flex items-center gap-2">
                    {!adding ? (
                        <button
                            type="button"
                            onClick={() => setAdding(true)}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800"
                        >
                            <Plus className="h-3.5 w-3.5" /> {__('general.add_sticky_note')}
                        </button>
                    ) : (
                        <div className="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                            <select value={newColor} onChange={(e) => setNewColor(e.target.value)} className="rounded-md border border-slate-200 px-2 py-1 text-xs">
                                {Object.keys(NOTE_COLORS).map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                            <input
                                value={newText}
                                onChange={(e) => setNewText(e.target.value)}
                                placeholder={__('general.note_text')}
                                className="w-44 rounded-md border border-slate-200 px-2 py-1 text-xs"
                                onKeyDown={(e) => e.key === 'Enter' && addNote()}
                                autoFocus
                            />
                            <button onClick={addNote} className="rounded-md bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-500">{__('general.add')}</button>
                            <button onClick={() => { setAdding(false); setNewText(''); }} className="rounded-md px-2 py-1 text-xs text-slate-500 hover:bg-slate-100">{__('general.cancel')}</button>
                        </div>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_180px]">
                {/* Canvas */}
                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                    <div
                        ref={canvasRef}
                        className="relative select-none"
                        style={{ width: canvasW, height: CANVAS_H }}
                    >
                        {/* Lane columns (background) */}
                        <div className="absolute inset-0 flex">
                            {lanes.map((lane) => (
                                <div key={lane} className="border-slate-200/70 border-e" style={{ width: COL_W }}>
                                    <div className="sticky top-0 z-10 border-b border-slate-200/70 bg-slate-100/90 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {__(laneKey(lane))}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Cards layer */}
                        {cards.map((card) => {
                            const key = cardKey(card);
                            const isNote = card.type === 'note';
                            const noteCls = NOTE_COLORS[card.color ?? 'yellow'] ?? NOTE_COLORS.yellow;
                            return (
                                <div
                                    key={key}
                                    onPointerDown={(e) => onPointerDown(e, card)}
                                    style={{ left: card.pos_x, top: card.pos_y, width: CARD_W, touchAction: 'none' }}
                                    className={cn(
                                        'group absolute z-20 cursor-grab rounded-lg border p-3 shadow-sm transition-shadow hover:shadow-md',
                                        dragging === key && 'cursor-grabbing shadow-lg ring-2 ring-slate-900/10',
                                        isNote ? noteCls : 'bg-white text-slate-900 border-slate-200',
                                    )}
                                >
                                    <div className="flex items-start gap-2">
                                        <GripVertical className="mt-0.5 h-3.5 w-3.5 shrink-0 opacity-40" />
                                        <div className="min-w-0 flex-1">
                                            {card.type === 'task' && (
                                                <div className="mb-1 flex items-center gap-1 text-[10px] font-semibold uppercase text-slate-400">
                                                    <ListTodo className="h-3 w-3" /> {__('general.task')}
                                                    {card.done ? <CheckCircle2 className="ms-1 h-3 w-3 text-emerald-500" /> : <Circle className="ms-1 h-3 w-3" />}
                                                </div>
                                            )}
                                            {card.type === 'report' && (
                                                <div className="mb-1 flex items-center gap-1 text-[10px] font-semibold uppercase text-emerald-500">
                                                    <FileText className="h-3 w-3" /> {__('general.report')}
                                                </div>
                                            )}

                                            {editingId === card.id ? (
                                                <div className="space-y-1" data-no-drag>
                                                    <textarea value={editText} onChange={(e) => setEditText(e.target.value)} className="w-full resize-none rounded border border-black/10 bg-white/80 p-1 text-sm" rows={3} />
                                                    <div className="flex gap-1">
                                                        <button data-no-drag onClick={() => saveEdit(card.id)} className="rounded bg-slate-900 px-2 py-0.5 text-[11px] text-white">{__('general.save')}</button>
                                                        <button data-no-drag onClick={() => setEditingId(null)} className="rounded px-2 py-0.5 text-[11px]">{__('general.cancel')}</button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <p className="break-words text-sm font-medium leading-snug">{card.title}</p>
                                            )}

                                            {card.type === 'task' && card.priority && (
                                                <span className="mt-1 inline-block rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{card.priority}</span>
                                            )}
                                            {card.type === 'report' && (
                                                <div className="mt-2" data-no-drag>
                                                    <button
                                                        onClick={() => router.get(route('client.projects.reports.show', { project: projectId, report: card.id }))}
                                                        className="text-[11px] font-semibold text-emerald-600 hover:underline"
                                                    >{__('general.view_report')} →</button>
                                                </div>
                                            )}
                                        </div>

                                        {/* Card actions (don't initiate drag) */}
                                        {isNote && editingId !== card.id && (
                                            <div className="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100" data-no-drag>
                                                <button
                                                    title={__('general.edit')}
                                                    onClick={() => { setEditingId(card.id); setEditText(card.content ?? card.title); }}
                                                    className="rounded p-1 hover:bg-black/5"
                                                >
                                                    <StickyNote className="h-3.5 w-3.5" />
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

                {/* Minimap */}
                <div className="hidden rounded-xl border border-slate-200 bg-white p-3 lg:block">
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{__('general.minimap')}</p>
                    <div className="relative mx-auto w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50" style={{ aspectRatio: `${canvasW} / ${CANVAS_H}` }}>
                        {/* lane grid lines */}
                        <div className="absolute inset-0 flex">
                            {lanes.map((l) => (<div key={l} className="border-slate-200/70 border-e last:border-e-0" style={{ width: `${100 / lanes.length}%` }} />))}
                        </div>
                        {minimap.map((m) => (
                            <span key={m.key} className={cn('absolute h-1.5 w-1.5 rounded-full', m.cls)} style={{ left: m.left, top: m.top }} />
                        ))}
                    </div>
                    <div className="mt-3 space-y-1 text-[11px] text-slate-500">
                        <div className="flex items-center gap-1.5"><span className="h-2 w-2 rounded-full bg-amber-400" /> {__('general.notes')}</div>
                        <div className="flex items-center gap-1.5"><span className="h-2 w-2 rounded-full bg-sky-400" /> {__('general.tasks')}</div>
                        <div className="flex items-center gap-1.5"><span className="h-2 w-2 rounded-full bg-emerald-400" /> {__('general.reports')}</div>
                    </div>
                </div>
            </div>
        </div>
    );
}
