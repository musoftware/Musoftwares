import React, { useState, useEffect, useRef } from 'react';
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
import { runtimeSDK } from '@/lib/runtime-sdk';



interface Template {
    id: string;
    name: string;
    message: string;
    media_url?: string;
    media_type: string;
    parts: Array<{ type: string; message?: string; media_url?: string; caption?: string }>;
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
    const parts = tpl.parts || [];
    const partTypes = parts.map((p: any) => p.type);
    const uniqueTypes = [...new Set(partTypes)] as string[];
    const partColorMap: Record<string, string> = {
        text: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        image: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        video: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        document: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        audio: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    };

    const firstText = parts.find((p: any) => p.type === 'text');
    const iconMap: Record<string, any> = { text: FileText, image: Image, video: Video, document: FileArchive, audio: Mic };
    const MainIcon = iconMap[uniqueTypes.find(t => t !== 'text') || 'text'] || FileText;

    return (
        <Card className="group hover:shadow-md transition-all duration-200">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3 mb-3">
                    <div className="flex items-center gap-2.5 min-w-0">
                        <div className="w-9 h-9 rounded-xl bg-teal-600 flex items-center justify-center flex-shrink-0 text-white shadow-sm">
                            <MainIcon className="w-4 h-4" />
                        </div>
                        <div className="min-w-0">
                            <h3 className="font-bold text-sm truncate">{tpl.name}</h3>
                            <div className="flex items-center gap-1 mt-0.5 flex-wrap">
                                <Badge variant="secondary" className="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 text-[10px]">
                                    {parts.length} part{parts.length !== 1 ? 's' : ''}
                                </Badge>
                                {uniqueTypes.map((t: string) => (
                                    <Badge key={t} variant="secondary" className={`text-[10px] ${partColorMap[t] || 'bg-slate-100 text-slate-600'}`}>
                                        {t}
                                    </Badge>
                                ))}
                            </div>
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

                {firstText?.message && (
                    <p className="text-xs text-muted-foreground line-clamp-3 leading-relaxed mb-3">{firstText.message}</p>
                )}
                {tpl.tags && tpl.tags.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {tpl.tags.map((tag: string) => (
                            <Badge key={tag} variant="outline" className="text-[10px]">
                                <Tag className="w-2.5 h-2.5 mr-0.5" />{tag}
                            </Badge>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

const PART_TYPES = [
    { value: 'text',     label: 'Text',      icon: FileText,    color: 'bg-blue-500' },
    { value: 'image',    label: 'Image',      icon: Image,       color: 'bg-emerald-500' },
    { value: 'video',    label: 'Video',      icon: Video,       color: 'bg-purple-500' },
    { value: 'document', label: 'Document',   icon: FileArchive, color: 'bg-amber-500' },
    { value: 'audio',    label: 'Audio',      icon: Mic,         color: 'bg-rose-500' },
];

interface TemplatePart {
    type: string;
    message?: string;
    media_url?: string;
    caption?: string;
}

function PartBlock({ part, index, onChange, onRemove, onMoveUp, onMoveDown, isFirst, isLast }: {
    part: TemplatePart; index: number;
    onChange: (updated: TemplatePart) => void; onRemove: () => void;
    onMoveUp: () => void; onMoveDown: () => void;
    isFirst: boolean; isLast: boolean;
}) {
    const partDef = PART_TYPES.find(p => p.value === part.type) || PART_TYPES[0];
    const Icon = partDef.icon;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('file', file);
            const res = await fetch(`${runtimeSDK.runtimeHttp}/upload`, { method: 'POST', body: formData });
            if (!res.ok) throw new Error('Upload failed');
            const data = await res.json();
            if (data.ok && data.url) {
                onChange({ ...part, media_url: data.url });
            }
        } catch (err: any) {
            alert(`Upload failed: ${err.message}`);
        } finally {
            setUploading(false);
        }
    };

    return (
        <div className="relative group border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-slate-900/50 transition-all hover:shadow-sm">
            {/* Header */}
            <div className="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-700/80">
                <div className={`w-6 h-6 rounded-lg ${partDef.color} flex items-center justify-center text-white`}>
                    <Icon className="w-3.5 h-3.5" />
                </div>
                <span className="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">{partDef.label}</span>
                <span className="text-[10px] text-slate-400 ml-1">#{index + 1}</span>
                <div className="ml-auto flex items-center gap-0.5">
                    {!isFirst && (
                        <button type="button" onClick={onMoveUp} className="w-6 h-6 flex items-center justify-center rounded hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-colors" title="Move up">↑</button>
                    )}
                    {!isLast && (
                        <button type="button" onClick={onMoveDown} className="w-6 h-6 flex items-center justify-center rounded hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-colors" title="Move down">↓</button>
                    )}
                    <button type="button" onClick={onRemove} className="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-slate-400 hover:text-red-500 transition-colors ml-1" title="Remove">
                        <X className="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {/* Body */}
            <div className="p-4 space-y-3">
                {part.type === 'text' ? (
                    <Textarea
                        value={part.message || ''}
                        onChange={e => onChange({ ...part, message: e.target.value })}
                        placeholder="Write your message... Use {name}, {phone}, {company} for variables"
                        rows={3}
                        className="resize-none text-sm"
                    />
                ) : (
                    <>
                        {/* File upload area */}
                        <div
                            className="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 flex items-center gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:border-teal-400/50 transition-all"
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <input
                                type="file"
                                ref={fileInputRef}
                                onChange={handleFileUpload}
                                className="hidden"
                                accept={
                                    part.type === 'image' ? 'image/*' :
                                    part.type === 'video' ? 'video/*' :
                                    part.type === 'audio' ? 'audio/*' : '*'
                                }
                            />
                            {uploading ? (
                                <div className="flex items-center gap-2 w-full">
                                    <RefreshCw className="w-4 h-4 animate-spin text-teal-500" />
                                    <span className="text-xs text-slate-500">Uploading...</span>
                                </div>
                            ) : part.media_url ? (
                                <div className="flex items-center gap-2 w-full min-w-0">
                                    <div className="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                        <Check className="w-3.5 h-3.5" />
                                    </div>
                                    <span className="text-xs text-emerald-700 dark:text-emerald-300 font-medium truncate">{part.media_url.split('/').pop()}</span>
                                    <span className="text-[10px] text-teal-500 underline ml-auto flex-shrink-0">Change</span>
                                </div>
                            ) : (
                                <div className="flex items-center gap-2 w-full">
                                    <Plus className="w-4 h-4 text-slate-400" />
                                    <span className="text-xs text-slate-500">Choose {part.type} file</span>
                                </div>
                            )}
                        </div>

                        {/* Or paste URL */}
                        {!part.media_url && (
                            <Input
                                value={part.media_url || ''}
                                onChange={e => onChange({ ...part, media_url: e.target.value })}
                                placeholder="Or paste URL here..."
                                className="text-xs font-mono h-8"
                            />
                        )}

                        {/* Caption */}
                        <Input
                            value={part.caption || ''}
                            onChange={e => onChange({ ...part, caption: e.target.value })}
                            placeholder={`Caption for this ${part.type} (optional)`}
                            className="text-xs h-8"
                        />
                    </>
                )}
            </div>
        </div>
    );
}

function TemplateEditor({ template, onSave, onCancel }: any) {
    const [name, setName]           = useState(template?.name || '');
    const [tagsInput, setTagsInput] = useState((template?.tags || []).join(', '));
    const [saving, setSaving]       = useState(false);

    // Initialize parts from template
    const [parts, setParts] = useState<TemplatePart[]>(() => {
        if (template?.parts && template.parts.length > 0) {
            return template.parts;
        }
        // Legacy: build parts from old fields
        const p: TemplatePart[] = [];
        if (template?.message?.trim()) p.push({ type: 'text', message: template.message });
        if (template?.media_url && template?.media_type !== 'text') {
            p.push({ type: template.media_type, media_url: template.media_url, caption: '' });
        }
        return p.length > 0 ? p : [{ type: 'text', message: '' }];
    });

    const addPart = (type: string) => {
        if (type === 'text') {
            setParts([...parts, { type: 'text', message: '' }]);
        } else {
            setParts([...parts, { type, media_url: '', caption: '' }]);
        }
    };

    const updatePart = (index: number, updated: TemplatePart) => {
        const next = [...parts];
        next[index] = updated;
        setParts(next);
    };

    const removePart = (index: number) => {
        if (parts.length <= 1) return; // Keep at least 1
        setParts(parts.filter((_, i) => i !== index));
    };

    const movePart = (index: number, dir: -1 | 1) => {
        const next = [...parts];
        const target = index + dir;
        if (target < 0 || target >= next.length) return;
        [next[index], next[target]] = [next[target], next[index]];
        setParts(next);
    };

    const handleSave = async () => {
        if (!name.trim()) return alert('Template name is required');
        const validParts = parts.filter(p =>
            (p.type === 'text' && p.message?.trim()) ||
            (p.type !== 'text' && p.media_url?.trim())
        );
        if (validParts.length === 0) return alert('Add at least one message or media part');
        setSaving(true);
        const tags = tagsInput.split(',').map((t: string) => t.trim()).filter(Boolean);
        await onSave({ id: template?.id, name, parts: validParts, tags });
        setSaving(false);
    };

    return (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm flex items-center justify-center p-4" onClick={(e) => { if (e.target === e.currentTarget) onCancel(); }}>
            <Card className="w-full max-w-2xl animate-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
                <CardContent className="p-6 flex flex-col overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-5">
                        <h2 className="text-lg font-bold">{template?.id ? 'Edit Template' : 'New Template'}</h2>
                        <Button variant="ghost" size="icon" onClick={onCancel}>
                            <X className="w-4 h-4 text-muted-foreground" />
                        </Button>
                    </div>

                    {/* Scrollable content */}
                    <div className="overflow-y-auto flex-1 space-y-4 pr-1" style={{ maxHeight: 'calc(90vh - 200px)' }}>
                        {/* Template Name */}
                        <div className="space-y-2">
                            <Label>Template Name</Label>
                            <Input value={name} onChange={e => setName(e.target.value)} placeholder="e.g. Welcome Message" />
                        </div>

                        {/* Parts List */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <Label className="text-sm font-bold">Message Parts ({parts.length})</Label>
                            </div>

                            {parts.map((part, i) => (
                                <PartBlock
                                    key={i}
                                    part={part}
                                    index={i}
                                    onChange={(updated) => updatePart(i, updated)}
                                    onRemove={() => removePart(i)}
                                    onMoveUp={() => movePart(i, -1)}
                                    onMoveDown={() => movePart(i, 1)}
                                    isFirst={i === 0}
                                    isLast={i === parts.length - 1}
                                />
                            ))}

                            {/* Add Part Buttons */}
                            <div className="flex flex-wrap gap-2 pt-1">
                                {PART_TYPES.map(({ value, label, icon: Icon, color }) => (
                                    <button
                                        key={value}
                                        type="button"
                                        onClick={() => addPart(value)}
                                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-600 dark:text-slate-300 hover:border-teal-400 hover:text-teal-600 dark:hover:text-teal-400 hover:bg-teal-50/50 dark:hover:bg-teal-900/20 transition-all"
                                    >
                                        <div className={`w-4 h-4 rounded ${color} flex items-center justify-center text-white`}>
                                            <Plus className="w-2.5 h-2.5" />
                                        </div>
                                        {label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Tags */}
                        <div className="space-y-2">
                            <Label>Tags (comma-separated)</Label>
                            <Input value={tagsInput} onChange={e => setTagsInput(e.target.value)} placeholder="promo, welcome, arabic" />
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex gap-3 mt-5 pt-4 border-t">
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
