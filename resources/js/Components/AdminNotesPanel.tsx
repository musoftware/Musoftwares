import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

interface AdminNote {
    id: number;
    author_id: number;
    visibility: 'private' | 'staff_only' | 'admins_only';
    type: 'general' | 'warning' | 'fraud_risk' | 'accounting' | 'moderation' | 'legal' | 'support';
    content: string;
    is_pinned: boolean;
    risk_level: 'none' | 'low' | 'medium' | 'high' | 'critical';
    created_at: string;
    author?: {
        name: string;
        avatar?: string;
    };
}

interface Props {
    noteableType: string;
    noteableId: number;
    initialNotes?: AdminNote[];
}

export default function AdminNotesPanel({ noteableType, noteableId, initialNotes = [] }: Props) {
    const [notes, setNotes] = useState<AdminNote[]>(initialNotes);
    const [content, setContent] = useState('');
    const [type, setType] = useState('general');
    const [visibility, setVisibility] = useState('staff_only');
    const [riskLevel, setRiskLevel] = useState('none');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Fetch notes on mount
    useEffect(() => {
        fetchNotes();
        // Here we would also bind to Laravel Echo / Reverb for real-time updates:
        // Echo.private(`admin-notes.${noteableType}.${noteableId}`)
        //     .listen('AdminNoteCreated', (e) => setNotes(prev => [e.note, ...prev]))
        //     .listen('AdminNoteUpdated', (e) => setNotes(prev => prev.map(n => n.id === e.note.id ? e.note : n)))
        //     .listen('AdminNoteDeleted', (e) => setNotes(prev => prev.filter(n => n.id !== e.noteId)));
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
                content,
                type,
                visibility,
                risk_level: riskLevel
            });

            if (res.data && (res.data.note || res.data.id)) {
                setNotes(prev => [res.data.note || res.data, ...prev]);
            }
            setContent('');
        } catch (err) {
            setError("Failed to add note.");
        } finally {
            setLoading(false);
        }
    };

    const handleTogglePin = async (noteId: number) => {
        // Optimistic update
        setNotes(prev => prev.map(n => n.id === noteId ? { ...n, is_pinned: !n.is_pinned } : n));
        try {
            await axios.post(`/admin/users/${noteableId}/notes/${noteId}/archive`);
        } catch (err) {
            // Revert
            setNotes(prev => prev.map(n => n.id === noteId ? { ...n, is_pinned: !n.is_pinned } : n));
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

    // Render markdown safely
    const renderMarkdown = (text: string) => {
        // Simple mention highlighting (e.g., @admin)
        const textWithMentions = text.replace(/(@\w+)/g, '<span class="font-bold text-slate-900">$1</span>');
        const html = marked(textWithMentions) as string;
        return { __html: DOMPurify.sanitize(html) };
    };

    // Sort: pinned first, then by date desc
    const sortedNotes = [...notes].sort((a, b) => {
        if (a.is_pinned && !b.is_pinned) return -1;
        if (!a.is_pinned && b.is_pinned) return 1;
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });

    return (
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 className="text-lg font-medium mb-4">Internal Admin Notes</h3>

            <div className="mb-6 space-y-4 max-h-96 overflow-y-auto pr-2">
                {sortedNotes.map(note => (
                    <div key={note.id} className={`p-4 rounded-[8px] border ${note.risk_level === 'high' || note.risk_level === 'critical' ? 'border-red-200 bg-white' : 'border-slate-200 bg-white'}`}>
                        <div className="flex justify-between items-start mb-2">
                            <div className="flex items-center space-x-2">
                                <span className="font-semibold text-sm">{note.author?.name || 'Unknown Staff'}</span>
                                <span className="text-xs text-gray-500">{new Date(note.created_at).toLocaleString()}</span>
                            </div>
                            <div className="flex space-x-2 items-center">
                                {note.is_pinned && <span className="px-2 py-1 text-xs bg-slate-800 text-white rounded font-bold uppercase tracking-wider">Pinned</span>}
                                <span className="px-2 py-1 text-xs bg-slate-100 text-slate-700 rounded capitalize font-medium">{note.type.replace('_', ' ')}</span>
                                {note.risk_level !== 'none' && (
                                    <span className="px-2 py-1 text-xs bg-red-50 text-red-700 border border-red-100 rounded capitalize font-medium">Risk: {note.risk_level}</span>
                                )}

                                <div className="relative group ml-2">
                                    <button className="text-gray-400 hover:text-gray-600 px-1">...</button>
                                    <div className="absolute right-0 hidden group-hover:block bg-white border rounded shadow-lg z-10 w-24">
                                        <button onClick={() => handleTogglePin(note.id)} className="block w-full text-left px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">{note.is_pinned ? 'Unpin' : 'Pin'}</button>
                                        <button onClick={() => handleDelete(note.id)} className="block w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-red-50">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="text-sm prose" dangerouslySetInnerHTML={renderMarkdown(note.content)} />
                    </div>
                ))}
                {notes.length === 0 && <p className="text-sm text-gray-500">No notes found.</p>}
            </div>

            <form onSubmit={handleAddNote} className="space-y-4 border-t pt-4">
                {error && <div className="text-red-500 text-sm mb-2">{error}</div>}
                <div>
                    <textarea
                        className="w-full border-slate-300 rounded-[8px] shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900"
                        rows={3}
                        placeholder="Add a new note (Markdown and @mentions supported)..."
                        value={content}
                        onChange={e => setContent(e.target.value)}
                        required
                        disabled={loading}
                    />
                </div>
                <div className="flex flex-wrap gap-4 items-center justify-between">
                    <div className="flex space-x-4">
                        <select className="border-slate-300 rounded-[8px] text-sm focus:border-slate-900 focus:ring-slate-900" value={type} onChange={e => setType(e.target.value)} disabled={loading}>
                            <option value="general">General</option>
                            <option value="warning">Warning</option>
                            <option value="fraud_risk">Fraud Risk</option>
                            <option value="accounting">Accounting</option>
                            <option value="moderation">Moderation</option>
                            <option value="legal">Legal</option>
                            <option value="support">Support</option>
                        </select>

                        <select className="border-slate-300 rounded-[8px] text-sm focus:border-slate-900 focus:ring-slate-900" value={visibility} onChange={e => setVisibility(e.target.value)} disabled={loading}>
                            <option value="staff_only">Staff Only</option>
                            <option value="admins_only">Admins Only</option>
                            <option value="private">Private</option>
                        </select>

                        <select className="border-slate-300 rounded-[8px] text-sm focus:border-slate-900 focus:ring-slate-900" value={riskLevel} onChange={e => setRiskLevel(e.target.value)} disabled={loading}>
                            <option value="none">No Risk Flag</option>
                            <option value="low">Low Risk</option>
                            <option value="medium">Medium Risk</option>
                            <option value="high">High Risk</option>
                            <option value="critical">Critical Risk</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" disabled={loading || !content.trim()} className="px-4 py-2 bg-slate-900 text-white text-sm font-bold rounded-[8px] hover:bg-slate-800 disabled:opacity-50 font-sora">
                            {loading ? 'Adding...' : 'Add Note'}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    );
}
