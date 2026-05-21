import React, { useState, useEffect } from 'react';
import {
    FileText, Plus, Trash2, Edit3, X, Check, Save,
    Image, Video, FileArchive, Mic, RefreshCw, Search, Tag
} from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';

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
    onTemplatesChange?: (templates: Template[]) => void;
    initialEditTemplateId?: string | null;
    setInitialEditTemplateId?: (id: string | null) => void;
    daemonConnected?: boolean;
}

function TemplateCard({ tpl, onEdit, onDelete, onUse }: any) {
    const MediaIcon = MEDIA_TYPES.find(m => m.value === tpl.media_type)?.icon || FileText;
    return (
        <Card className="group hover:shadow-md transition-all duration-200">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3 mb-3">
                    <div className="flex items-center gap-2.5 min-w-0">
                        <div className="w-9 h-9 rounded-xl bg-teal-600 flex items-center justify-center flex-shrink-0 text-white shadow-sm">
                            <MediaIcon className="w-4 h-4" />
                        </div>
                        <div className="min-w-0">
                            <h3 className="font-bold text-sm truncate">{tpl.name}</h3>
                            <Badge variant="secondary" className="bg-emerald-50 text-emerald-700 mt-0.5 text-[10px]">
                                {tpl.media_type}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                        <Button size="icon" variant="ghost" onClick={() => onUse(tpl)} className="h-8 w-8 text-teal-600 hover:text-teal-700 hover:bg-teal-50" title="Use Template">
                            <Check className="w-3.5 h-3.5" />
                        </Button>
                        <Button size="icon" variant="ghost" onClick={() => onEdit(tpl)} className="h-8 w-8 text-slate-600 hover:text-slate-800 hover:bg-slate-100" title="Edit">
                            <Edit3 className="w-3.5 h-3.5" />
                        </Button>
                        <Button size="icon" variant="ghost" onClick={() => onDelete(tpl.id)} className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10" title="Delete">
                            <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                    </div>
                </div>

                {tpl.message && (
                    <p className="text-xs text-muted-foreground line-clamp-3 leading-relaxed mb-3">{tpl.message}</p>
                )}
                {tpl.media_url && (
                    <div className="text-[10px] font-mono text-muted-foreground bg-muted rounded-lg px-3 py-1.5 truncate mb-3">
                        🔗 {tpl.media_url}
                    </div>
                )}
                {tpl.tags && tpl.tags.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {tpl.tags.map((tag: string) => (
                            <Badge key={tag} variant="outline" className="text-[10px] font-semibold text-muted-foreground bg-muted/50">
                                #{tag}
                            </Badge>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
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
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm flex items-center justify-center p-4" onClick={(e) => { if (e.target === e.currentTarget) onCancel(); }}>
            <Card className="w-full max-w-xl animate-in zoom-in-95 duration-200">
                <CardContent className="p-8">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-lg font-bold">{template?.id ? 'Edit Template' : 'New Template'}</h2>
                        <Button variant="ghost" size="icon" onClick={onCancel}>
                            <X className="w-4 h-4 text-muted-foreground" />
                        </Button>
                    </div>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label>Template Name</Label>
                            <Input value={name} onChange={e => setName(e.target.value)} placeholder="e.g. Welcome Message" />
                        </div>

                        <div className="space-y-2">
                            <Label>Message</Label>
                            <Textarea value={message} onChange={e => setMessage(e.target.value)} placeholder="Hi {name}! Your message here..." rows={4} className="resize-none" />
                            <p className="text-[10px] text-muted-foreground mt-1">Variables: <code className="bg-muted px-1 rounded">{'{name}'}</code> <code className="bg-muted px-1 rounded">{'{phone}'}</code> <code className="bg-muted px-1 rounded">{'{company}'}</code></p>
                        </div>

                        <div className="space-y-2">
                            <Label>Media Type</Label>
                            <div className="grid grid-cols-5 gap-2">
                                {MEDIA_TYPES.map(({ value, label, icon: Icon }) => (
                                    <Button 
                                        key={value} 
                                        variant={mediaType === value ? 'default' : 'outline'}
                                        onClick={() => setMediaType(value)} 
                                        className={`h-auto flex flex-col items-center gap-1 py-2.5 px-2 ${mediaType === value ? 'bg-teal-600 hover:bg-teal-700' : ''}`}
                                    >
                                        <Icon className="w-4 h-4" />
                                        <span className="text-[9px] font-bold uppercase tracking-wider">{label}</span>
                                    </Button>
                                ))}
                            </div>
                        </div>

                        {mediaType !== 'text' && (
                            <div className="space-y-2">
                                <Label>Media URL</Label>
                                <Input value={mediaUrl} onChange={e => setMediaUrl(e.target.value)} placeholder="https://example.com/media.jpg" className="font-mono" />
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label>Tags (comma-separated)</Label>
                            <Input value={tagsInput} onChange={e => setTagsInput(e.target.value)} placeholder="promo, welcome, arabic" />
                        </div>
                    </div>

                    <div className="flex gap-3 mt-6">
                        <Button variant="outline" onClick={onCancel} className="flex-1">Cancel</Button>
                        <Button onClick={handleSave} disabled={saving} className="flex-1 bg-teal-600 hover:bg-teal-700 text-white gap-2">
                            {saving ? <><div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" /> Saving...</> : <><Save className="w-4 h-4" /> Save Template</>}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

export default function TemplatesWorkspace({ 
    callRPC, onUseTemplate, onTemplatesChange,
    initialEditTemplateId, setInitialEditTemplateId,
    daemonConnected
}: Props) {
    const [templates, setTemplates] = useState<Template[]>([]);
    const [loading, setLoading]     = useState(false);
    const [editTarget, setEditTarget] = useState<Template | null | 'new'>(null);
    const [search, setSearch]       = useState('');

    const fetch = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getTemplates');
            const list = res.templates || [];
            setTemplates(list);
            if (onTemplatesChange) {
                onTemplatesChange(list);
            }
        } catch (e) { console.error(e); }
        setLoading(false);
    };

    useEffect(() => {
        if (daemonConnected) {
            fetch();
        }
    }, [daemonConnected]);

    useEffect(() => {
        if (initialEditTemplateId && templates.length > 0) {
            const found = templates.find(t => t.id === initialEditTemplateId);
            if (found) {
                setEditTarget(found);
            }
            if (setInitialEditTemplateId) {
                setInitialEditTemplateId(null);
            }
        }
    }, [initialEditTemplateId, templates, setInitialEditTemplateId]);

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
                    <h2 className="text-xl font-bold tracking-tight">Message Templates</h2>
                    <p className="text-sm text-muted-foreground mt-1">Save reusable messages and media — load them instantly when creating campaigns.</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="icon" onClick={fetch} disabled={loading}>
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </Button>
                    <Button onClick={() => setEditTarget('new')} className="bg-teal-600 hover:bg-teal-700 text-white gap-2">
                        <Plus className="w-4 h-4" /> New Template
                    </Button>
                </div>
            </div>

            {/* Search */}
            <div className="relative">
                <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search templates by name, content, or tag…" className="pl-10" />
            </div>

            {/* Template Grid */}
            {filtered.length === 0 && !loading ? (
                <Card className="border-dashed">
                    <CardContent className="flex flex-col items-center gap-4 text-center py-16">
                        <div className="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center">
                            <FileText className="w-8 h-8 text-muted-foreground" />
                        </div>
                        <div>
                            <h3 className="font-bold">No Templates Yet</h3>
                            <p className="text-sm text-muted-foreground mt-1">Create your first template to speed up campaign creation.</p>
                        </div>
                        <Button onClick={() => setEditTarget('new')} className="bg-teal-600 hover:bg-teal-700 text-white gap-2">
                            <Plus className="w-4 h-4" /> Create First Template
                        </Button>
                    </CardContent>
                </Card>
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
