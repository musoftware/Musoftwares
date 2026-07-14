import React, { useState, useMemo } from 'react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/Components/ui/sheet';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';
import {
    StickyNote, Trash2, Pencil, Pin, X, Check, Calendar, User, Tag
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { toast } from 'sonner';
import axios from 'axios';
import { timeAgo } from '@/lib/utils';

export interface AdminNote {
    id: number;
    project_id: number;
    author_id: number;
    content: string;
    category: string;
    is_pinned: boolean;
    created_at: string;
    updated_at: string;
    author?: {
        id: number;
        name: string;
    };
}

interface ProjectAdminNotesSidebarProps {
    projectId: number;
    open: boolean;
    onClose: () => void;
    notes: AdminNote[];
    onNotesChange: (notes: AdminNote[]) => void;
    boardCategories?: { id: number; name: string }[];
}

const DEFAULT_CATEGORIES = [
    'General',
    'Client Info',
    'Requirements',
    'Finance',
    'Technical',
    'Meeting Notes'
];

export default function ProjectAdminNotesSidebar({
    projectId,
    open,
    onClose,
    notes,
    onNotesChange,
    boardCategories = [],
}: ProjectAdminNotesSidebarProps) {
    const [activeTab, setActiveTab] = useState<string>('all');
    const [content, setContent] = useState('');
    const [isPinned, setIsPinned] = useState(false);
    const [categoryType, setCategoryType] = useState<'select' | 'custom'>('select');
    const [selectedCategory, setSelectedCategory] = useState('General');
    const [customCategory, setCustomCategory] = useState('');
    const [editingNoteId, setEditingNoteId] = useState<number | null>(null);
    const [editContent, setEditContent] = useState('');
    const [editCategory, setEditCategory] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    // Merge default categories, board categories, and any unique categories existing in the notes
    const categoryOptions = useMemo(() => {
        const set = new Set(DEFAULT_CATEGORIES);
        boardCategories.forEach(c => set.add(c.name));
        notes.forEach(n => {
            if (n.category) set.add(n.category);
        });
        return Array.from(set);
    }, [boardCategories, notes]);

    // Unique categories for filtering
    const filterCategories = useMemo(() => {
        const set = new Set<string>();
        notes.forEach(n => {
            if (n.category) set.add(n.category);
        });
        return ['all', ...Array.from(set)];
    }, [notes]);

    // Filter and sort notes (pinned notes first, then latest first)
    const filteredNotes = useMemo(() => {
        let result = [...notes];
        if (activeTab !== 'all') {
            result = result.filter(n => n.category === activeTab);
        }
        return result.sort((a, b) => {
            if (a.is_pinned && !b.is_pinned) return -1;
            if (!a.is_pinned && b.is_pinned) return 1;
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        });
    }, [notes, activeTab]);

    const handleAddNote = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!content.trim()) return;

        const category = categoryType === 'custom' ? customCategory.trim() : selectedCategory;
        if (!category) {
            toast.error(__('general.error') || 'Category is required');
            return;
        }

        setIsSaving(true);
        try {
            const response = await axios.post(
                route('admin.projects.admin-notes.store', { project: projectId }),
                {
                    content: content.trim(),
                    category,
                    is_pinned: isPinned,
                }
            );

            onNotesChange([response.data, ...notes]);
            setContent('');
            setIsPinned(false);
            setCustomCategory('');
            setCategoryType('select');
            toast.success(__('general.note_created_successfully') || 'Admin note added successfully!');
        } catch (error: any) {
            toast.error(error.response?.data?.message || __('general.error') || 'Failed to save admin note');
        } finally {
            setIsSaving(false);
        }
    };

    const handleUpdateNote = async (noteId: number) => {
        if (!editContent.trim()) return;

        setIsSaving(true);
        try {
            const response = await axios.put(
                route('admin.projects.admin-notes.update', { project: projectId, note: noteId }),
                {
                    content: editContent.trim(),
                    category: editCategory,
                }
            );

            onNotesChange(notes.map(n => n.id === noteId ? response.data : n));
            setEditingNoteId(null);
            toast.success(__('general.note_updated_successfully') || 'Note updated successfully!');
        } catch (error: any) {
            toast.error(error.response?.data?.message || __('general.error') || 'Failed to update note');
        } finally {
            setIsSaving(false);
        }
    };

    const handleTogglePin = async (note: AdminNote) => {
        try {
            const response = await axios.put(
                route('admin.projects.admin-notes.update', { project: projectId, note: note.id }),
                {
                    content: note.content,
                    category: note.category,
                    is_pinned: !note.is_pinned,
                }
            );

            onNotesChange(notes.map(n => n.id === note.id ? response.data : n));
            toast.success(note.is_pinned ? 'Note unpinned' : 'Note pinned');
        } catch (error: any) {
            toast.error(__('general.error') || 'Failed to toggle pin');
        }
    };

    const handleDeleteNote = async (noteId: number) => {
        if (!window.confirm(__('general.confirm_delete') || 'Are you sure you want to delete this note?')) return;

        try {
            await axios.delete(route('admin.projects.admin-notes.destroy', { project: projectId, note: noteId }));
            onNotesChange(notes.filter(n => n.id !== noteId));
            toast.success(__('general.note_deleted_successfully') || 'Note deleted successfully!');
        } catch (error: any) {
            toast.error(__('general.error') || 'Failed to delete note');
        }
    };

    const startEditing = (note: AdminNote) => {
        setEditingNoteId(note.id);
        setEditContent(note.content);
        setEditCategory(note.category);
    };

    return (
        <Sheet open={open} onOpenChange={(v) => !v && onClose()}>
            <SheetContent className="sm:max-w-md md:max-w-lg flex flex-col h-full bg-white shadow-2xl border-s border-slate-100 p-0">
                <SheetHeader className="p-6 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <StickyNote className="h-5 w-5 text-amber-500" />
                        <SheetTitle className="text-xl font-bold text-slate-900">
                            {__('general.admin_notes') || 'Internal Admin Notes'}
                        </SheetTitle>
                    </div>
                    <SheetDescription className="text-xs text-slate-400 mt-1">
                        {__('general.admin_notes_desc') || 'These notes are private and visible only to administrators and staff.'}
                    </SheetDescription>
                </SheetHeader>

                {/* Main scrollable body */}
                <div className="flex-1 overflow-y-auto p-6 space-y-6">
                    {/* Add Note Form */}
                    <form onSubmit={handleAddNote} className="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <Tag className="h-3 w-3 text-slate-400" />
                            {__('general.add_note') || 'Add Admin Note'}
                        </h4>

                        <div className="space-y-2">
                            <Textarea
                                value={content}
                                onChange={(e) => setContent(e.target.value)}
                                placeholder={__('general.write_your_note_here') || 'Write your note here...'}
                                className="min-h-[80px] bg-white border-slate-200 text-sm focus-visible:ring-slate-400 focus-visible:ring-1"
                                required
                            />
                        </div>

                        {/* Category selection */}
                        <div className="flex items-center gap-2">
                            <div className="flex-1">
                                {categoryType === 'select' ? (
                                    <select
                                        value={selectedCategory}
                                        onChange={(e) => {
                                            if (e.target.value === '__custom__') {
                                                setCategoryType('custom');
                                            } else {
                                                setSelectedCategory(e.target.value);
                                            }
                                        }}
                                        className="w-full text-xs bg-white border border-slate-200 rounded-md px-2 py-1.5 text-slate-700 focus:outline-none focus:ring-1 focus:ring-slate-400"
                                    >
                                        {categoryOptions.map(cat => (
                                            <option key={cat} value={cat}>{cat}</option>
                                        ))}
                                        <option value="__custom__">+ {__('general.custom_category') || 'Custom category...'}</option>
                                    </select>
                                ) : (
                                    <div className="flex items-center gap-1">
                                        <Input
                                            value={customCategory}
                                            onChange={(e) => setCustomCategory(e.target.value)}
                                            placeholder={__('general.category_name') || 'Category Name'}
                                            className="h-8 text-xs bg-white border-slate-200"
                                            required
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setCategoryType('select')}
                                            className="h-8 px-2 hover:bg-slate-200 text-slate-500"
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </div>
                                )}
                            </div>

                            <div className="flex items-center gap-1.5 shrink-0">
                                <label className="flex items-center gap-1 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        checked={isPinned}
                                        onChange={(e) => setIsPinned(e.target.checked)}
                                        className="h-3 w-3 rounded text-slate-900 border-slate-300 focus:ring-slate-500"
                                    />
                                    <span className="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                        {__('general.pin') || 'Pin'}
                                    </span>
                                </label>
                            </div>

                            <Button
                                type="submit"
                                size="sm"
                                disabled={isSaving}
                                className="h-8 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs px-3 rounded-md shrink-0"
                            >
                                {isSaving ? '...' : __('general.save') || 'Save'}
                            </Button>
                        </div>
                    </form>

                    {/* Category tabs filters */}
                    {filterCategories.length > 2 && (
                        <div className="flex flex-wrap gap-1 border-b border-slate-100 pb-3">
                            {filterCategories.map(cat => (
                                <button
                                    key={cat}
                                    type="button"
                                    onClick={() => setActiveTab(cat)}
                                    className={`px-2 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider transition ${
                                        activeTab === cat
                                            ? 'bg-slate-900 text-white'
                                            : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                                    }`}
                                >
                                    {cat === 'all' ? __('general.all') || 'All' : cat}
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Notes List */}
                    <div className="space-y-4">
                        {filteredNotes.length === 0 ? (
                            <div className="text-center py-12 bg-slate-50/50 border border-dashed border-slate-200 rounded-xl">
                                <StickyNote className="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                <p className="text-sm font-semibold text-slate-400">
                                    {__('general.no_notes_found') || 'No internal notes found.'}
                                </p>
                            </div>
                        ) : (
                            filteredNotes.map(note => (
                                <div
                                    key={note.id}
                                    className={`group border rounded-xl p-4 transition-all relative ${
                                        note.is_pinned
                                            ? 'border-amber-100 bg-amber-50/30'
                                            : 'border-slate-100 bg-slate-50/30 hover:bg-slate-50/70'
                                    }`}
                                >
                                    {/* Edit mode vs view mode */}
                                    {editingNoteId === note.id ? (
                                        <div className="space-y-3">
                                            <Textarea
                                                value={editContent}
                                                onChange={(e) => setEditContent(e.target.value)}
                                                className="min-h-[70px] bg-white border-slate-200 text-sm focus-visible:ring-slate-400 focus-visible:ring-1"
                                                required
                                            />
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    value={editCategory}
                                                    onChange={(e) => setEditCategory(e.target.value)}
                                                    placeholder={__('general.category_name')}
                                                    className="h-8 text-xs bg-white border-slate-200"
                                                />
                                                <div className="flex items-center gap-1 ms-auto">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() => setEditingNoteId(null)}
                                                        className="h-8 w-8 p-0 bg-slate-100 text-slate-600 hover:bg-slate-200"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() => handleUpdateNote(note.id)}
                                                        disabled={isSaving}
                                                        className="h-8 w-8 p-0 bg-slate-900 text-white hover:bg-slate-800"
                                                    >
                                                        <Check className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <>
                                            {/* Note content */}
                                            <div className="flex justify-between items-start gap-4 mb-2">
                                                {note.category && (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200/80 text-slate-700">
                                                        {note.category}
                                                    </span>
                                                )}
                                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleTogglePin(note)}
                                                        className={`p-1 rounded hover:bg-slate-200 transition ${
                                                            note.is_pinned ? 'text-amber-500' : 'text-slate-400 hover:text-slate-600'
                                                        }`}
                                                        title={note.is_pinned ? 'Unpin' : 'Pin'}
                                                    >
                                                        <Pin className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => startEditing(note)}
                                                        className="p-1 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition"
                                                        title="Edit"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeleteNote(note.id)}
                                                        className="p-1 rounded hover:bg-slate-200 text-slate-400 hover:text-red-600 transition"
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Pin indicator for non-hover state */}
                                            {note.is_pinned && (
                                                <Pin className="absolute top-4 right-4 h-3.5 w-3.5 text-amber-500 fill-amber-500 group-hover:hidden" />
                                            )}

                                            <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                                {note.content}
                                            </p>

                                            {/* Metadata footer */}
                                            <div className="mt-3 pt-3 border-t border-slate-100/50 flex justify-between items-center text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                                <span className="flex items-center gap-1">
                                                    <User className="h-3 w-3" />
                                                    {note.author?.name || 'Admin'}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="h-3 w-3" />
                                                    {timeAgo(note.created_at)}
                                                </span>
                                            </div>
                                        </>
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
