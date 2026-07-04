import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Plus, Check, Tag } from 'lucide-react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    BoardCategoryChip,
    categoryPalette,
    type BoardCategoryLike,
} from './BoardCategoryChip';

const POPOVER_WIDTH = 240; // matches Tailwind w-60 (15rem)

export interface BoardCategory extends BoardCategoryLike {
    id: number;
    slug: string;
    is_system?: boolean;
    name_ar?: string | null;
}

/**
 * Tiny anchor-positioned popover that lists every category for the project plus a
 * "no category" command. Click a chip to select it, click the + button to add a
 * new custom category without leaving the board.
 *
 * The picker is purely controlled: parents own `selectedId` and decide what to do
 * with the change. We don't fire any API calls here except the optional "create
 * new custom category" inline form.
 */
export default function BoardCategoryPicker({
    projectId,
    categories,
    selectedId,
    onChange,
    disabled = false,
    align = 'end',
}: {
    projectId: number | string;
    categories: BoardCategory[];
    selectedId: number | null | undefined;
    onChange: (next: number | null) => void;
    disabled?: boolean;
    align?: 'start' | 'end';
}) {
    const [open, setOpen] = useState(false);
    const [showAdd, setShowAdd] = useState(false);
    const [newName, setNewName] = useState('');
    const [newColor, setNewColor] = useState('rose');
    const [creating, setCreating] = useState(false);
    const [coords, setCoords] = useState<{ top: number; left: number } | null>(null);
    const wrapRef = useRef<HTMLDivElement | null>(null);
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const popoverRef = useRef<HTMLDivElement | null>(null);

    const selected = useMemo(
        () => (selectedId ? categories.find((c) => c.id === selectedId) ?? null : null),
        [selectedId, categories],
    );

    const computeCoords = (button: HTMLButtonElement) => {
        const rect = button.getBoundingClientRect();
        return {
            top: rect.bottom + 6, // mt-1.5 = 6px
            left: align === 'end' ? rect.right - POPOVER_WIDTH : rect.left,
        };
    };

    const handleToggle = (e: React.MouseEvent<HTMLButtonElement>) => {
        e.stopPropagation();
        if (!open && buttonRef.current) {
            setCoords(computeCoords(buttonRef.current));
        }
        setOpen((v) => !v);
    };

    React.useEffect(() => {
        if (!open) return;
        const onDoc = (e: MouseEvent) => {
            const t = e.target as Node;
            if (wrapRef.current?.contains(t)) return;
            if (popoverRef.current?.contains(t)) return;
            setOpen(false);
        };
        window.addEventListener('mousedown', onDoc);
        return () => window.removeEventListener('mousedown', onDoc);
    }, [open]);

    // Close on scroll/resize so the portaled popover doesn't drift from its anchor.
    useEffect(() => {
        if (!open) return;
        const onScroll = () => setOpen(false);
        window.addEventListener('scroll', onScroll, true);
        window.addEventListener('resize', onScroll);
        return () => {
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', onScroll);
        };
    }, [open]);

    const submitNew = async () => {
        const trimmed = newName.trim();
        if (!trimmed) return;
        setCreating(true);
        try {
            await axios.post(
                route('admin.projects.board.categories.store', { project: projectId }),
                { name: trimmed, color: newColor },
            );
            setNewName('');
            setShowAdd(false);
            // Reload just the categories page piece so the new row appears in the list.
            router.reload({ only: ['categories'] });
            toast.success(__('general.board_category_added') || 'Category added.');
        } catch (err) {
            toast.error(__('general.board_category_assign_failed') || 'Could not add category.');
        } finally {
            setCreating(false);
        }
    };

    return (
        <div className="relative inline-block" ref={wrapRef}>
            <button
                ref={buttonRef}
                type="button"
                disabled={disabled}
                onClick={handleToggle}
                className={cn(
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold border transition-colors shadow-sm active:scale-95 ring-1 ring-inset',
                    selected
                        ? cn(categoryPalette(selected).bg, categoryPalette(selected).text, categoryPalette(selected).ring)
                        : 'bg-slate-50 text-slate-500 border-slate-200 ring-slate-200 hover:bg-slate-100',
                )}
                title={__('general.board_category') || 'Category'}
                aria-label={__('general.board_category') || 'Category'}
            >
                <Tag className="h-2.5 w-2.5" />
                <span className="truncate max-w-[8rem]">
                    {(selected?.name ?? __('general.board_no_category')) || 'No category'}
                </span>
            </button>

            {open && coords && typeof document !== 'undefined' && createPortal(
                <div
                    ref={popoverRef}
                    style={{ position: 'fixed', top: coords.top, left: coords.left, width: POPOVER_WIDTH }}
                    className="z-[1000] rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl ring-1 ring-black/5 animate-in fade-in slide-in-from-top-1 duration-150"
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        {__('general.board_categories') || 'Categories'}
                    </div>
                    <button
                        type="button"
                        onClick={() => { onChange(null); setOpen(false); }}
                        className={cn(
                            'flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-start text-xs transition-colors',
                            selectedId == null ? 'bg-slate-50 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50',
                        )}
                    >
                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200/60 text-slate-500">
                            <Tag className="h-3 w-3" />
                        </span>
                        <span className="flex-1 truncate">{__('general.board_no_category') || 'No category'}</span>
                        {selectedId == null && <Check className="h-3.5 w-3.5 text-slate-700" />}
                    </button>
                    <div className="my-1 border-t border-slate-100" />
                    <div className="max-h-56 overflow-y-auto pr-0.5">
                        {categories.map((c) => {
                            const palette = categoryPalette(c);
                            const isActive = selectedId === c.id;
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
                                    {isActive && <Check className="h-3.5 w-3.5 text-slate-700" />}
                                </button>
                            );
                        })}
                        {categories.length === 0 && (
                            <div className="px-3 py-2 text-[11px] text-slate-400 italic">—</div>
                        )}
                    </div>
                    <div className="my-1 border-t border-slate-100" />
                    {!showAdd ? (
                        <button
                            type="button"
                            onClick={() => setShowAdd(true)}
                            className="flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-start text-xs text-slate-600 hover:bg-slate-50"
                        >
                            <Plus className="h-3.5 w-3.5 text-slate-400" />
                            <span>{__('general.board_add_category') || 'Add category'}</span>
                        </button>
                    ) : (
                        <div className="space-y-1.5 px-1 py-1">
                            <input
                                autoFocus
                                type="text"
                                value={newName}
                                onChange={(e) => setNewName(e.target.value)}
                                placeholder={__('general.board_add_category') || 'Add category'}
                                className="h-8 w-full rounded-lg border border-slate-200 bg-slate-50 px-2 text-xs focus:border-slate-400 focus:bg-white focus:outline-none"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') { e.preventDefault(); void submitNew(); }
                                    if (e.key === 'Escape') { setShowAdd(false); }
                                }}
                            />
                            <div className="flex flex-wrap gap-1">
                                {Object.keys({
                                    rose: 1, amber: 1, slate: 1, sky: 1, emerald: 1, violet: 1,
                                    indigo: 1, fuchsia: 1, pink: 1, cyan: 1, teal: 1, lime: 1, orange: 1,
                                }).map((c) => (
                                    <button
                                        key={c}
                                        type="button"
                                        onClick={() => setNewColor(c)}
                                        className={cn(
                                            'h-5 w-5 rounded-full ring-1 ring-inset ring-white shadow-sm',
                                            categoryPalette({ id: 0, name: '', color: c }).dot,
                                            newColor === c ? 'ring-2 ring-slate-900 scale-110' : 'hover:scale-110',
                                        )}
                                        aria-label={c}
                                        title={c}
                                    />
                                ))}
                            </div>
                            <div className="flex items-center gap-1.5 pt-1">
                                <button
                                    type="button"
                                    onClick={() => setShowAdd(false)}
                                    className="flex-1 rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"
                                >
                                    {__('general.cancel') || 'Cancel'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => void submitNew()}
                                    disabled={!newName.trim() || creating}
                                    className="flex-1 rounded-lg bg-slate-900 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                >
                                    {__('general.save') || 'Save'}
                                </button>
                            </div>
                        </div>
                    )}
                </div>,
                document.body,
            )}
        </div>
    );
}
