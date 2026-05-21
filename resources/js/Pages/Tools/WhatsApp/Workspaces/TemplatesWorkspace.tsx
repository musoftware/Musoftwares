import React, { useState, useEffect } from 'react';
import {
    FileText, Plus, Trash2, Edit3, X, Check, Save,
    Image, Video, FileArchive, Mic, RefreshCw, Search, Tag
} from 'lucide-react';

const MEDIA_TYPES = [
    { value: 'text',     label: 'Text Only',  icon: FileText },
    { value: 'image',    label: 'Image',       icon: Image },
    { value: 'video',    label: 'Video',       icon: Video },
    { value: 'document', label: 'Document',    icon: FileArchive },
    { value: 'audio',    label: 'Audio',       icon: Mic },
];

interface Template {
    id: string;
    name: string;
    message: string;
    media_url?: string;
    media_type: string;
    tags: string[];
    created_at: string;
}

interface Props {
    callRPC: (action: string, data?: any) => Promise<any>;
    onUseTemplate?: (template: Template) => void;
}

function TemplateCard({ tpl, onEdit, onDelete, onUse }: any) {
    const MediaIcon = MEDIA_TYPES.find(m => m.value === tpl.media_type)?.icon || FileText;
    return (
        <div className="group bg-white/70 backdrop-blur-xl border border-white/60 rounded-2xl p-5 shadow-[0_4px_20px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-0.5 transition-all duration-200">
            <div className="flex items-start justify-between gap-3 mb-3">
                <div className="flex items-center gap-2.5 min-w-0">
                    <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center flex-shrink-0 shadow-[0_4px_10px_rgb(52,211,153,0.3)]">
                        <MediaIcon className="w-4 h-4 text-white" />
                    </div>
                    <div className="min-w-0">
                        <h3 className="font-bold text-slate-800 text-sm truncate">{tpl.name}</h3>
                        <span className="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{tpl.media_type}</span>
                    </div>
                </div>
                <div className="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                    <button onClick={() => onUse(tpl)} className="p-1.5 bg-teal-500 text-white rounded-lg hover:bg-teal-400 transition-colors" title="Use Template">
                        <Check className="w-3.5 h-3.5" />
                    </button>
                    <button onClick={() => onEdit(tpl)} className="p-1.5 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors" title="Edit">
                        <Edit3 className="w-3.5 h-3.5" />
                    </button>
                    <button onClick={() => onDelete(tpl.id)} className="p-1.5 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors" title="Delete">
                        <Trash2 className="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {tpl.message && (
                <p className="text-xs text-slate-500 line-clamp-3 leading-relaxed mb-3">{tpl.message}</p>
            )}
            {tpl.media_url && (
                <div className="text-[10px] font-mono text-slate-400 bg-slate-50 rounded-lg px-3 py-1.5 truncate mb-3">
                    🔗 {tpl.media_url}
                </div>
            )}
            {tpl.tags && tpl.tags.length > 0 && (
                <div className="flex flex-wrap gap-1">
                    {tpl.tags.map((tag: string) => (
                        <span key={tag} className="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">#{tag}</span>
                    ))}
                </div>
            )}
        </div>
    );
}

function TemplateEditor({ template, onSave, onCancel }: any) {
    const [name, setName]           = useState(template?.name || '');
    const [message, setMessage]     = useState(template?.message || '');
    const [mediaType, setMediaType] = useState(template?.media_type || 'text');
    const [mediaUrl, setMediaUrl]   = useState(template?.media_url || '');
    const [tagsInput, setTagsInput] = useState((template?.tags || []).join(', '));
    const [saving, setSaving]       = useState(false);

    const handleSave = async () => {
        if (!name.trim()) return alert('Template name is required');
        if (!message.trim() && !mediaUrl.trim()) return alert('Message or media URL required');
        setSaving(true);
        const tags = tagsInput.split(',').map((t: string) => t.trim()).filter(Boolean);
        await onSave({ id: template?.id, name, message, mediaType, mediaUrl: mediaUrl || null, tags });
        setSaving(false);
    };

    return (
        <div className="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4" onClick={(e) => { if (e.target === e.currentTarget) onCancel(); }}>
            <div className="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 animate-in zoom-in-95 duration-200">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-lg font-bold text-slate-800">{template?.id ? 'Edit Template' : 'New Template'}</h2>
                    <button onClick={onCancel} className="p-2 hover:bg-slate-100 rounded-xl transition-colors"><X className="w-4 h-4 text-slate-400" /></button>
                </div>

                <div className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Template Name</label>
                        <input value={name} onChange={e => setName(e.target.value)} placeholder="e.g. Welcome Message" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all" />
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Message</label>
                        <textarea value={message} onChange={e => setMessage(e.target.value)} placeholder="Hi {name}! Your message here..." rows={4} className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all resize-none" />
                        <p className="text-[10px] text-slate-400 mt-1">Variables: <code className="bg-slate-100 px-1 rounded">{'{name}'}</code> <code className="bg-slate-100 px-1 rounded">{'{phone}'}</code> <code className="bg-slate-100 px-1 rounded">{'{company}'}</code></p>
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Media Type</label>
                        <div className="grid grid-cols-5 gap-2">
                            {MEDIA_TYPES.map(({ value, label, icon: Icon }) => (
                                <button key={value} onClick={() => setMediaType(value)} className={`flex flex-col items-center gap-1 py-2.5 px-2 rounded-xl border text-center transition-all ${mediaType === value ? 'border-teal-400 bg-teal-50 text-teal-600' : 'border-slate-200 hover:border-slate-300 text-slate-500'}`}>
                                    <Icon className="w-4 h-4" />
                                    <span className="text-[9px] font-bold uppercase tracking-wider">{label}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    {mediaType !== 'text' && (
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Media URL</label>
                            <input value={mediaUrl} onChange={e => setMediaUrl(e.target.value)} placeholder="https://example.com/media.jpg" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all font-mono" />
                        </div>
                    )}

                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tags (comma-separated)</label>
                        <input value={tagsInput} onChange={e => setTagsInput(e.target.value)} placeholder="promo, welcome, arabic" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all" />
                    </div>
                </div>

                <div className="flex gap-3 mt-6">
                    <button onClick={onCancel} className="flex-1 py-3 border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors">Cancel</button>
                    <button onClick={handleSave} disabled={saving} className="flex-1 py-3 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl text-sm font-bold hover:from-teal-400 hover:to-emerald-500 transition-all shadow-[0_4px_15px_rgb(52,211,153,0.3)] active:scale-95 disabled:opacity-60 flex items-center justify-center gap-2">
                        {saving ? <><div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" /> Saving...</> : <><Save className="w-4 h-4" /> Save Template</>}
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function TemplatesWorkspace({ callRPC, onUseTemplate }: Props) {
    const [templates, setTemplates] = useState<Template[]>([]);
    const [loading, setLoading]     = useState(false);
    const [editTarget, setEditTarget] = useState<Template | null | 'new'>(null);
    const [search, setSearch]       = useState('');

    const fetch = async () => {
        setLoading(true);
        try {
            const res: any = await callRPC('getTemplates');
            setTemplates(res.templates || []);
        } catch (e) { console.error(e); }
        setLoading(false);
    };

    useEffect(() => { fetch(); }, []);

    const handleSave = async (tpl: any) => {
        await callRPC('saveTemplate', tpl);
        setEditTarget(null);
        fetch();
    };

    const handleDelete = async (id: string) => {
        if (!confirm('Delete this template?')) return;
        await callRPC('deleteTemplate', { id });
        fetch();
    };

    const filtered = templates.filter(t =>
        t.name.toLowerCase().includes(search.toLowerCase()) ||
        (t.message || '').toLowerCase().includes(search.toLowerCase()) ||
        (t.tags || []).some(tag => tag.toLowerCase().includes(search.toLowerCase()))
    );

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800">Message Templates</h2>
                    <p className="text-xs text-slate-400 mt-1">Save reusable messages and media — load them instantly when creating campaigns.</p>
                </div>
                <div className="flex items-center gap-2">
                    <button onClick={fetch} disabled={loading} className="p-2 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all">
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                    <button onClick={() => setEditTarget('new')} className="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl text-sm font-bold hover:from-teal-400 hover:to-emerald-500 transition-all shadow-[0_4px_10px_rgb(52,211,153,0.3)] active:scale-95">
                        <Plus className="w-4 h-4" /> New Template
                    </button>
                </div>
            </div>

            {/* Search */}
            <div className="relative">
                <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search templates by name, content, or tag…" className="w-full pl-10 pr-4 py-3 bg-white/70 border border-slate-200/80 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all" />
            </div>

            {/* Template Grid */}
            {filtered.length === 0 && !loading ? (
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-14 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col items-center gap-4 text-center">
                    <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                        <FileText className="w-8 h-8 text-slate-300" />
                    </div>
                    <div>
                        <h3 className="font-bold text-slate-600">No Templates Yet</h3>
                        <p className="text-sm text-slate-400 mt-1">Create your first template to speed up campaign creation.</p>
                    </div>
                    <button onClick={() => setEditTarget('new')} className="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl text-sm font-bold hover:from-teal-400 hover:to-emerald-500 transition-all shadow-[0_4px_10px_rgb(52,211,153,0.3)]">
                        <Plus className="w-4 h-4" /> Create First Template
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    {filtered.map(tpl => (
                        <TemplateCard key={tpl.id} tpl={tpl} onEdit={setEditTarget} onDelete={handleDelete} onUse={onUseTemplate} />
                    ))}
                </div>
            )}

            {/* Editor Modal */}
            {editTarget !== null && (
                <TemplateEditor
                    template={editTarget === 'new' ? null : editTarget}
                    onSave={handleSave}
                    onCancel={() => setEditTarget(null)}
                />
            )}
        </div>
    );
}
