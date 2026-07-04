import React, { useEffect, useMemo, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Plus, Trash2, Pencil, Tag, X, Save } from 'lucide-react';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import axios from 'axios';
import { toast } from 'sonner';
import { BoardCategoryChip, categoryPalette, CATEGORY_COLOR_CLASSES } from '@/Pages/Client/Projects/Components/BoardCategoryChip';

export interface BoardCategory {
    id: number;
    slug: string;
    name: string;
    name_ar?: string | null;
    color: string;
    text_color?: string;
    is_system: boolean;
    sort: number;
}

const COLOR_KEYS = Object.keys(CATEGORY_COLOR_CLASSES);

/**
 * Admin-side modal for managing the per-project board category taxonomy.
 *
 * The page passes the initial server-rendered list (so the chip filters work without a round-trip);
 * local state stays in sync with the list returned by each CRUD response. The server is the source
 * of truth — after every successful mutation we `router.reload({ only: [...] })` so the parent page
 * (which owns the categories prop passed into <ProjectBoard>) refreshes its own copy.
 */
export default function BoardCategoriesManager({
    projectId,
    open,
    onClose,
    initialCategories,
}: {
    projectId: number | string;
    open: boolean;
    onClose: () => void;
    initialCategories: BoardCategory[];
}) {
    const [list, setList] = useState<BoardCategory[]>(initialCategories);
    const [editing, setEditing] = useState<{ id: number; name: string; color: string } | null>(null);
    const [creating, setCreating] = useState(false);
    const [draft, setDraft] = useState({ name: '', name_ar: '', color: 'rose' });
    const [busy, setBusy] = useState(false);
    const inputRef = useRef<HTMLInputElement | null>(null);

    // Sync when the parent provides a fresh list (e.g. after a sibling reload).
    useEffect(() => { setList(initialCategories); }, [initialCategories]);

    useEffect(() => {
        if (!open) return;
        setEditing(null); setCreating(false); setDraft({ name: '', name_ar: '', color: 'rose' });
    }, [open]);

    useEffect(() => {
        if (creating) {
            window.setTimeout(() => inputRef.current?.focus(), 50);
        }
    }, [creating]);

    const sortedList = useMemo(() => [...list].sort((a, b) => a.sort - b.sort || a.id - b.id), [list]);

    const refreshParent = () => router.reload({ only: ['categories'] });

    const submitCreate = async () => {
        if (!draft.name.trim()) return;
        setBusy(true);
        try {
            const res = await axios.post(
                route('admin.projects.board.categories.store', { project: projectId }),
                { name: draft.name.trim(), name_ar: draft.name_ar.trim() || null, color: draft.color },
            );
            const created = res.data?.category;
            if (created) setList((prev) => [...prev, created]);
            setDraft({ name: '', name_ar: '', color: 'rose' });
            setCreating(false);
            toast.success(__('general.board_category_added') || 'Category added.');
            refreshParent();
        } catch (err: any) {
            toast.error(err?.response?.data?.message || __('general.board_category_assign_failed') || 'Could not add category.');
        } finally {
            setBusy(false);
        }
    };

    const submitEdit = async () => {
        if (!editing) return;
        if (!editing.name.trim()) return;
        setBusy(true);
        try {
            const res = await axios.put(
                route('admin.projects.board.categories.update', { project: projectId, category: editing.id }),
                { name: editing.name.trim(), color: editing.color },
            );
            const updated = res.data?.category;
            if (updated) {
                setList((prev) => prev.map((c) => (c.id === updated.id ? updated : c)));
            }
            setEditing(null);
            toast.success(__('general.board_category_updated') || 'Category updated.');
            refreshParent();
        } catch (err: any) {
            toast.error(err?.response?.data?.message || __('general.board_category_assign_failed') || 'Could not update category.');
        } finally {
            setBusy(false);
        }
    };

    const submitDelete = async (cat: BoardCategory) => {
        if (cat.is_system) {
            toast.error(__('general.board_system_category_locked') || 'System categories cannot be deleted.');
            return;
        }
        if (!confirm(__('general.confirm_delete_category') || 'Delete this category? Items inside will not be deleted.')) return;
        setBusy(true);
        try {
            await axios.delete(
                route('admin.projects.board.categories.destroy', { project: projectId, category: cat.id }),
            );
            setList((prev) => prev.filter((c) => c.id !== cat.id));
            toast.success(__('general.board_category_deleted') || 'Category removed.');
            refreshParent();
        } catch (err: any) {
            toast.error(err?.response?.data?.message || __('general.board_category_assign_failed') || 'Could not delete category.');
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent className="sm:max-w-[640px] max-h-[calc(100vh-3rem)] flex flex-col gap-0 p-0 overflow-hidden">
                <div className="px-6 pt-5 pb-3 border-b border-slate-100 shrink-0">
                    <DialogHeader>
                        <DialogTitle className="text-base font-extrabold inline-flex items-center gap-2">
                            <Tag className="h-4 w-4 text-amber-500" />
                            {__('general.board_manage_categories') || 'Manage categories'}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 mt-1">
                            {__('general.board_drag_to_reorder') || 'Drag to reorder cards within this lane.'}
                        </DialogDescription>
                    </DialogHeader>
                </div>

                <div className="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-3">
                    {sortedList.length === 0 && !creating && (
                        <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50/40 p-4 text-center text-xs text-slate-500">
                            —
                        </div>
                    )}

                    <ul className="space-y-1.5">
                        {sortedList.map((c) => {
                            const palette = categoryPalette(c);
                            const isEditing = editing?.id === c.id;
                            return (
                                <li
                                    key={c.id}
                                    className={cn(
                                        'rounded-xl border bg-white px-3 py-2 transition-colors',
                                        isEditing ? 'border-slate-300 ring-1 ring-slate-200' : 'border-slate-200 hover:border-slate-300',
                                    )}
                                >
                                    {isEditing ? (
                                        <div className="space-y-2">
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    value={editing.name}
                                                    onChange={(e) => setEditing({ ...editing, name: e.target.value })}
                                                    className="h-9 rounded-lg text-xs flex-1"
                                                    placeholder="Name"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setEditing(null)}
                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500"
                                                    title="Cancel"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled={busy || !editing.name.trim()}
                                                    onClick={() => void submitEdit()}
                                                    className="inline-flex h-9 items-center gap-1 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                                >
                                                    <Save className="h-3.5 w-3.5" />
                                                    {__('general.save') || 'Save'}
                                                </button>
                                            </div>
                                            <div className="flex flex-wrap gap-1.5">
                                                {COLOR_KEYS.map((ck) => (
                                                    <button
                                                        key={ck}
                                                        type="button"
                                                        onClick={() => setEditing({ ...editing, color: ck })}
                                                        className={cn(
                                                            'h-5 w-5 rounded-full ring-1 ring-inset ring-white shadow-sm transition-all',
                                                            categoryPalette({ id: 0, name: '', color: ck }).dot,
                                                            editing.color === ck ? 'ring-2 ring-slate-900 scale-110' : 'hover:scale-110',
                                                        )}
                                                        aria-label={ck}
                                                        title={ck}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="flex items-center gap-3">
                                            <span className={cn('h-3 w-3 shrink-0 rounded-full ring-1 ring-inset', palette.dot, palette.ring)} />
                                            <BoardCategoryChip category={c} className="flex-1" />
                                            {c.is_system && (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 ring-1 ring-inset ring-slate-200">
                                                    {__('general.system') || 'System'}
                                                </span>
                                            )}
                                            <div className="flex items-center gap-0.5">
                                                <button
                                                    type="button"
                                                    onClick={() => setEditing({ id: c.id, name: c.name, color: c.color })}
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                    title={__('general.board_edit_category') || 'Edit'}
                                                >
                                                    <Pencil className="h-3.5 w-3.5" />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => void submitDelete(c)}
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                                    title={__('general.board_delete_category') || 'Delete'}
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>

                    {creating ? (
                        <div className="rounded-xl border border-slate-200 bg-slate-50/40 px-3 py-2 space-y-2">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <Input
                                    ref={inputRef}
                                    value={draft.name}
                                    onChange={(e) => setDraft({ ...draft, name: e.target.value })}
                                    placeholder="Name (e.g., Blockers)"
                                    className="h-9 rounded-lg text-xs"
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') { e.preventDefault(); void submitCreate(); }
                                        if (e.key === 'Escape') setCreating(false);
                                    }}
                                />
                                <Input
                                    value={draft.name_ar}
                                    onChange={(e) => setDraft({ ...draft, name_ar: e.target.value })}
                                    placeholder="(الاسم بالعربية)"
                                    dir="rtl"
                                    className="h-9 rounded-lg text-xs"
                                />
                            </div>
                            <div className="flex flex-wrap items-center gap-1.5">
                                <span className="text-[11px] font-bold text-slate-500 mr-1">Color</span>
                                {COLOR_KEYS.map((ck) => (
                                    <button
                                        key={ck}
                                        type="button"
                                        onClick={() => setDraft({ ...draft, color: ck })}
                                        className={cn(
                                            'h-5 w-5 rounded-full ring-1 ring-inset ring-white shadow-sm transition-all',
                                            categoryPalette({ id: 0, name: '', color: ck }).dot,
                                            draft.color === ck ? 'ring-2 ring-slate-900 scale-110' : 'hover:scale-110',
                                        )}
                                        aria-label={ck}
                                        title={ck}
                                    />
                                ))}
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => setCreating(false)}
                                    className="flex-1 h-9 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-white"
                                >
                                    {__('general.cancel') || 'Cancel'}
                                </button>
                                <button
                                    type="button"
                                    disabled={busy || !draft.name.trim()}
                                    onClick={() => void submitCreate()}
                                    className="flex-1 h-9 rounded-lg bg-slate-900 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                >
                                    {__('general.save') || 'Save'}
                                </button>
                            </div>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setCreating(true)}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                        >
                            <Plus className="h-3.5 w-3.5" />
                            {__('general.board_add_category') || 'Add category'}
                        </button>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
