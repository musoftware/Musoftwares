import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { Pin, Archive, Trash2, Key, Monitor, FileText, Upload } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface AdminNote {
    id: number;
    category: 'password' | 'anydesk' | 'notes' | 'archived';
    original_category: string | null;
    title: string | null;
    content: string;
    is_pinned: boolean;
    created_at: string;
    author?: {
        name: string;
    };
}

interface Props {
    noteableType: string;
    noteableId: number;
    initialNotes?: AdminNote[];
}

export default function AdminNotesPanel({ noteableType, noteableId, initialNotes = [] }: Props) {
    const [notes, setNotes] = useState<AdminNote[]>(initialNotes);
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [category, setCategory] = useState('notes');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchNotes();
    }, [noteableType, noteableId]);

    const fetchNotes = async () => {
        try {
            const res = await axios.get(`/admin/users/${noteableId}/notes/json`);
            setNotes(res.data.data || res.data || []);
        } catch (err) {
            console.error("Failed to load notes", err);
        }
    };

    const handleAddNote = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const res = await axios.post(`/admin/users/${noteableId}/notes`, {
                title,
                content,
                category
            });

            if (res.data && (res.data.note || res.data.id)) {
                setNotes(prev => [res.data.note || res.data, ...prev]);
            }
            setTitle('');
            setContent('');
        } catch (err) {
            setError("Failed to add note.");
        } finally {
            setLoading(false);
        }
    };

    const handleTogglePin = async (noteId: number) => {
        setNotes(prev => prev.map(n => n.id === noteId ? { ...n, is_pinned: !n.is_pinned } : n));
        try {
            await axios.post(`/admin/users/${noteableId}/notes/${noteId}/pin`);
        } catch (err) {
            setNotes(prev => prev.map(n => n.id === noteId ? { ...n, is_pinned: !n.is_pinned } : n));
        }
    };

    const handleArchive = async (noteId: number, currentCategory: string) => {
        const isArchived = currentCategory === 'archived';
        try {
            if (isArchived) {
                await axios.post(`/admin/users/${noteableId}/notes/${noteId}/unarchive`);
            } else {
                await axios.post(`/admin/users/${noteableId}/notes/${noteId}/archive`);
            }
            fetchNotes();
        } catch (err) {
            alert('Failed to archive/unarchive note.');
        }
    };

    const handleDelete = async (noteId: number) => {
        if (!confirm("Are you sure you want to delete this note?")) return;

        const previousNotes = [...notes];
        setNotes(prev => prev.filter(n => n.id !== noteId));

        try {
            await axios.delete(`/admin/users/${noteableId}/notes/${noteId}`);
        } catch (err) {
            setNotes(previousNotes);
        }
    };

    const renderMarkdown = (text: string) => {
        const html = marked(text) as string;
        return { __html: DOMPurify.sanitize(html) };
    };

    const sortedNotes = [...notes].sort((a, b) => {
        if (a.is_pinned && !b.is_pinned) return -1;
        if (!a.is_pinned && b.is_pinned) return 1;
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });

    const getCategoryIcon = (cat: string) => {
        switch (cat) {
            case 'password': return <Key size={14} className="text-amber-500" />;
            case 'anydesk': return <Monitor size={14} className="text-blue-500" />;
            case 'archived': return <Archive size={14} className="text-gray-500" />;
            default: return <FileText size={14} className="text-slate-500" />;
        }
    };

    return (
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 className="text-lg font-medium mb-4">{__('general.internal_admin_notes')}</h3>

            <div className="mb-6 space-y-4 max-h-96 overflow-y-auto pr-2">
                {sortedNotes.map(note => (
                    <div key={note.id} className={`p-4 rounded-[8px] border border-slate-200 bg-white`}>
                        <div className="flex justify-between items-start mb-2">
                            <div className="flex items-center space-x-2">
                                <span className="font-semibold text-sm">{note.author?.name || 'Unknown Staff'}</span>
                                <span className="text-xs text-gray-500">{new Date(note.created_at).toLocaleString()}</span>
                            </div>
                            <div className="flex space-x-2 items-center">
                                {note.is_pinned && <span className="px-2 py-1 text-xs bg-slate-800 text-white rounded font-bold uppercase tracking-wider">Pinned</span>}
                                <span className="px-2 py-1 text-xs bg-slate-100 text-slate-700 rounded capitalize font-medium flex items-center gap-1">
                                    {getCategoryIcon(note.category)}
                                    {note.category}
                                </span>

                                <div className="relative group ml-2">
                                    <button className="text-gray-400 hover:text-gray-600 px-1">...</button>
                                    <div className="absolute right-0 hidden group-hover:block bg-white border rounded shadow-lg z-10 w-28">
                                        <button onClick={() => handleTogglePin(note.id)} className="block w-full text-left px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                            <Pin size={12} /> {note.is_pinned ? 'Unpin' : 'Pin'}
                                        </button>
                                        <button onClick={() => handleArchive(note.id, note.category)} className="block w-full text-left px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                            {note.category === 'archived' ? <Upload size={12} /> : <Archive size={12} />} 
                                            {note.category === 'archived' ? 'Unarchive' : 'Archive'}
                                        </button>
                                        <button onClick={() => handleDelete(note.id)} className="block w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                            <Trash2 size={12} /> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {note.title && <div className="text-sm font-bold mb-1 text-slate-800">{note.title}</div>}
                        <div className="text-sm prose text-slate-600" dangerouslySetInnerHTML={renderMarkdown(note.content)} />
                    </div>
                ))}
                {notes.length === 0 && <p className="text-sm text-gray-500">{__('general.no_notes_found')}</p>}
            </div>

            <form onSubmit={handleAddNote} className="space-y-4 border-t pt-4">
                {error && <div className="text-red-500 text-sm mb-2">{error}</div>}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="col-span-2">
                        <input
                            type="text"
                            className="w-full border-slate-300 rounded-[8px] shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 mb-4"
                            placeholder={__('general.note_title')}
                            value={title}
                            onChange={e => setTitle(e.target.value)}
                            required
                            disabled={loading}
                        />
                        <textarea
                            className="w-full border-slate-300 rounded-[8px] shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900"
                            rows={3}
                            placeholder={__('general.add_a_new_note_markdown_supported')}
                            value={content}
                            onChange={e => setContent(e.target.value)}
                            required
                            disabled={loading}
                        />
                    </div>
                    <div className="flex flex-col gap-4">
                        <select className="border-slate-300 rounded-[8px] text-sm focus:border-slate-900 focus:ring-slate-900" value={category} onChange={e => setCategory(e.target.value)} disabled={loading}>
                            <option value="notes">{__('general.general_notes')}</option>
                            <option value="password">{__('general.password_credentials')}</option>
                            <option value="anydesk">AnyDesk</option>
                        </select>
                        <button type="submit" disabled={loading || !content.trim() || !title.trim()} className="w-full px-4 py-2 bg-slate-900 text-white text-sm font-bold rounded-[8px] hover:bg-slate-800 disabled:opacity-50 font-sora">
                            {loading ? 'Adding...' : 'Add Note'}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    );
}
