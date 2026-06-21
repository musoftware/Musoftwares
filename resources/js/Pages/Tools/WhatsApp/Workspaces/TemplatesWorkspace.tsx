import React, { useState, useEffect, useRef } from 'react';
import {
    FileText, Plus, Trash2, Edit3, X, Check, Save,
    Image, Video, FileArchive, Mic, RefreshCw, Search, Tag, FolderOpen, CloudUpload,
    MousePointerClick, ListOrdered, AlertTriangle, BarChart3
} from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { runtimeSDK } from '@/lib/runtime-sdk';
import { __ } from '@/lib/i18n';



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
    t: any;
    locale: 'en' | 'ar';
    callRPC: (action: string, data?: any) => Promise<any>;
    onUseTemplate?: (template: Template) => void;
    onTemplatesChange?: (templates: Template[]) => void;
    initialEditTemplateId?: string | null;
    setInitialEditTemplateId?: (id: string | null) => void;
    daemonConnected?: boolean;
}

function TemplateCard({ tpl, onEdit, onDelete, onUse, t, locale }: any) {
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
    const MainIcon = iconMap[uniqueTypes.find(type => type !== 'text') || 'text'] || FileText;

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
                                    {parts.length} {parts.length === 1 ? t.templates.part : t.templates.parts}
                                </Badge>
                                {uniqueTypes.map((type: string) => (
                                    <Badge key={type} variant="secondary" className={`text-[10px] ${partColorMap[type] || 'bg-slate-100 text-slate-600'}`}>
                                        {t.templates.partTypes?.[type] || type}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                        <Button size="icon" variant="ghost" onClick={() => onUse(tpl)} className="h-8 w-8 text-teal-600 hover:text-teal-700 hover:bg-teal-50" title={t.templates.useBtn}>
                            <Check className="w-3.5 h-3.5" />
                        </Button>
                        <Button size="icon" variant="ghost" onClick={() => onEdit(tpl)} className="h-8 w-8 text-slate-600 hover:text-slate-800 hover:bg-slate-100" title={t.templates.editBtn}>
                            <Edit3 className="w-3.5 h-3.5" />
                        </Button>
                        <Button size="icon" variant="ghost" onClick={() => onDelete(tpl.id)} className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10" title={t.templates.deleteBtn}>
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
                                <Tag className="w-2.5 h-2.5 me-0.5" />{tag}
                            </Badge>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

const PART_TYPES = [
    { value: 'text',     label: 'Text',      icon: FileText,        color: 'bg-blue-500' },
    { value: 'image',    label: 'Image',      icon: Image,           color: 'bg-emerald-500' },
    { value: 'video',    label: 'Video',      icon: Video,           color: 'bg-purple-500' },
    { value: 'document', label: 'Document',   icon: FileArchive,     color: 'bg-amber-500' },
    { value: 'audio',    label: 'Audio',      icon: Mic,             color: 'bg-rose-500' },
    { value: 'poll',     label: 'Poll',       icon: BarChart3,       color: 'bg-pink-500' },
];

interface TemplatePart {
    type: string;
    message?: string;
    media_url?: string;
    caption?: string;
    send_as_voice?: boolean;
    footer?: string;
    buttons?: Array<{ id: string; text: string }>;
    title?: string;
    buttonText?: string;
    sections?: Array<{ title: string; rows: Array<{ id: string; title: string; description: string }> }>;
    pollName?: string;
    pollOptions?: string[];
    selectableCount?: number;
}

interface MediaItem {
    id: string;
    name: string;
    type: 'image' | 'video' | 'document' | 'audio';
    url: string;
    size: number;
    created_at: string;
}

// ── Media Library Selector Modal ─────────────────────────────────────────
function MediaLibrarySelectorModal({ onClose, onSelect, activeType, callRPC, daemonConnected, t, locale }: {
    onClose: () => void;
    onSelect: (url: string) => void;
    activeType: string;
    callRPC: any;
    daemonConnected: boolean;
    t: any;
    locale: 'en' | 'ar';
}) {
    const isRtl = locale === 'ar';
    const [media, setMedia] = useState<MediaItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [typeFilter, setTypeFilter] = useState(activeType);

    const fetchMedia = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getMediaLibrary', { search, type: typeFilter !== 'all' ? typeFilter : undefined });
            setMedia(res.media || []);
        } catch (err: any) {
            console.error('Media fetch error:', err);
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchMedia();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [daemonConnected, typeFilter]);

    const filtered = media.filter(m => {
        if (search && !m.name.toLowerCase().includes(search.toLowerCase())) return false;
        return true;
    });

    const formatBytes = (bytes: number) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const typeIcons: Record<string, any> = {
        image: Image,
        video: Video,
        document: FileArchive,
        audio: Mic,
    };

    const typeColors: Record<string, string> = {
        image: 'bg-blue-50 text-blue-600 dark:bg-blue-950/30',
        video: 'bg-purple-50 text-purple-600 dark:bg-purple-950/30',
        document: 'bg-amber-50 text-amber-600 dark:bg-amber-950/30',
        audio: 'bg-pink-50 text-pink-600 dark:bg-pink-950/30',
    };

    return (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" onClick={onClose}>
            <Card className="w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200 shadow-2xl" onClick={e => e.stopPropagation()}>
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-slate-100 dark:border-slate-800">
                    <div className="flex items-center gap-2">
                        <FolderOpen className="w-5 h-5 text-teal-600" />
                        <h3 className="font-bold text-sm">
                            {isRtl ? 'اختر من مكتبة الوسائط' : 'Select from Media Library'}
                        </h3>
                    </div>
                    <Button variant="ghost" size="icon" className="h-8 w-8 rounded-lg" onClick={onClose}>
                        <X className="w-4 h-4" />
                    </Button>
                </div>

                {/* Filters */}
                <div className="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/20 space-y-3">
                    <div className="flex gap-2 flex-wrap items-center justify-between">
                        <div className="relative flex-1 min-w-[200px]">
                            <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground animate-pulse" />
                            <Input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                placeholder={isRtl ? 'بحث بالاسم...' : 'Search by name...'}
                                className="ps-9 h-9 rounded-xl text-start"
                            />
                        </div>
                        <div className="flex gap-1.5 flex-wrap">
                            {['all', 'image', 'video', 'document', 'audio'].map(type => {
                                const Icon = type === 'all' ? FolderOpen : typeIcons[type];
                                return (
                                    <Button
                                        key={type}
                                        variant={typeFilter === type ? 'default' : 'outline'}
                                        size="xs"
                                        onClick={() => setTypeFilter(type)}
                                        className={`rounded-lg text-xs gap-1 font-bold h-8 px-2.5 ${typeFilter === type ? 'bg-teal-600 hover:bg-teal-700 text-white' : ''}`}
                                    >
                                        <Icon className="w-3 h-3" />
                                        {type === 'all' ? (isRtl ? 'الكل' : 'All') : type}
                                    </Button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Media Grid */}
                <div className="flex-1 overflow-y-auto p-4 min-h-[300px]">
                    {loading ? (
                        <div className="flex flex-col items-center justify-center py-20 gap-3">
                            <RefreshCw className="w-8 h-8 text-teal-600 animate-spin" />
                            <p className="text-xs text-muted-foreground">{isRtl ? 'جاري تحميل الملفات...' : 'Loading files...'}</p>
                        </div>
                    ) : filtered.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="size-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                                <FolderOpen className="w-6 h-6 text-slate-400" />
                            </div>
                            <p className="text-sm font-bold">{isRtl ? 'لا توجد ملفات متطابقة' : 'No matching files'}</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                {isRtl ? 'تأكد من رفع ملفات في قسم مكتبة الوسائط أولاً' : 'Make sure you uploaded files in the Media Library first'}
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            {filtered.map(item => {
                                const Icon = typeIcons[item.type] || FileText;
                                return (
                                    <Card 
                                        key={item.id} 
                                        className="rounded-xl overflow-hidden hover:shadow-md transition-all duration-200 group cursor-pointer border border-slate-100 dark:border-slate-800/80 hover:border-teal-500/50"
                                        onClick={() => onSelect(item.url)}
                                    >
                                        <div className="h-24 bg-slate-50 dark:bg-slate-900/40 flex items-center justify-center relative">
                                            {item.type === 'image' && item.url ? (
                                                <img src={item.url} alt={item.name} className="w-full h-full object-cover" />
                                            ) : (
                                                <div className={`size-10 rounded-lg ${typeColors[item.type]} flex items-center justify-center`}>
                                                    <Icon className="w-5 h-5" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-2 text-start">
                                            <p className="text-[11px] font-bold truncate" title={item.name}>{item.name}</p>
                                            <div className="flex items-center justify-between mt-1">
                                                <span className="text-[9px] text-muted-foreground">{formatBytes(item.size)}</span>
                                                <Badge variant="outline" className="text-[8px] px-1 py-0 h-3.5 rounded font-medium">{item.type}</Badge>
                                            </div>
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </div>
            </Card>
        </div>
    );
}

function PartBlock({ part, index, onChange, onRemove, onMoveUp, onMoveDown, isFirst, isLast, t, locale, callRPC, daemonConnected }: {
    part: TemplatePart; index: number;
    onChange: (updated: TemplatePart) => void; onRemove: () => void;
    onMoveUp: () => void; onMoveDown: () => void;
    isFirst: boolean; isLast: boolean;
    t: any; locale: 'en' | 'ar';
    callRPC: any; daemonConnected: boolean;
}) {
    const partDef = PART_TYPES.find(p => p.value === part.type) || PART_TYPES[0];
    const Icon = partDef.icon;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [selectorOpen, setSelectorOpen] = useState(false);

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

    const localizedLabel = t.templates.partTypes?.[part.type] || partDef.label;

    return (
        <div className="relative group border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-slate-900/50 transition-all hover:shadow-sm">
            {/* Header */}
            <div className="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-700/80">
                <div className={`w-6 h-6 rounded-lg ${partDef.color} flex items-center justify-center text-white`}>
                    <Icon className="w-3.5 h-3.5" />
                </div>
                <span className="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">{localizedLabel}</span>
                <span className="text-[10px] text-slate-400 mx-1">#{index + 1}</span>
                <div className="ms-auto flex items-center gap-0.5">
                    {!isFirst && (
                        <button type="button" onClick={onMoveUp} className="w-6 h-6 flex items-center justify-center rounded hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-colors" title={t.templates.moveUp}>↑</button>
                    )}
                    {!isLast && (
                        <button type="button" onClick={onMoveDown} className="w-6 h-6 flex items-center justify-center rounded hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 transition-colors" title={t.templates.moveDown}>↓</button>
                    )}
                    <button type="button" onClick={onRemove} className="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-slate-400 hover:text-red-500 transition-colors ms-1" title={t.templates.remove}>
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
                        placeholder={t.templates.writeMessagePlaceholder}
                        rows={3}
                        className="resize-none text-sm text-start"
                    />
                ) : part.type === 'buttons' ? (
                    /* ── Interactive Buttons Builder ── */
                    <div className="space-y-3">
                        <div className="flex items-center gap-2 px-2.5 py-1.5 bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/30 rounded-lg">
                            <AlertTriangle className="w-3 h-3 text-amber-600 shrink-0" />
                            <span className="text-[10px] text-amber-700 dark:text-amber-400 font-medium">
                                {__('Interactive buttons may be limited depending on WhatsApp account type (Business vs Personal)')}
                            </span>
                        </div>
                        <Textarea
                            value={part.message || ''}
                            onChange={e => onChange({ ...part, message: e.target.value })}
                            placeholder={__('Main message text...')}
                            rows={2}
                            className="resize-none text-sm text-start"
                        />
                        <Input
                            value={part.footer || ''}
                            onChange={e => onChange({ ...part, footer: e.target.value })}
                            placeholder={__('Footer text (optional)')}
                            className="text-xs h-8 text-start"
                        />
                        <div className="space-y-2">
                            <Label className="text-xs font-bold text-start block">
                                {__('Buttons (max 3)')}
                            </Label>
                            {(part.buttons || []).map((btn, bIdx) => (
                                <div key={bIdx} className="flex items-center gap-2">
                                    <div className="w-5 h-5 rounded-md bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center text-cyan-600 text-[10px] font-bold shrink-0">{bIdx + 1}</div>
                                    <Input
                                        value={btn.text}
                                        onChange={e => {
                                            const updated = [...(part.buttons || [])];
                                            updated[bIdx] = { ...updated[bIdx], text: e.target.value };
                                            onChange({ ...part, buttons: updated });
                                        }}
                                        placeholder={__(`Button ${bIdx + 1} text`)}
                                        className="text-xs h-8 text-start flex-1"
                                    />
                                    <button type="button" onClick={() => {
                                        const updated = (part.buttons || []).filter((_, i) => i !== bIdx);
                                        onChange({ ...part, buttons: updated });
                                    }} className="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 text-slate-400 hover:text-red-500">
                                        <X className="w-3 h-3" />
                                    </button>
                                </div>
                            ))}
                            {(part.buttons || []).length < 3 && (
                                <button type="button" onClick={() => {
                                    const updated = [...(part.buttons || []), { id: `btn_${Date.now()}`, text: '' }];
                                    onChange({ ...part, buttons: updated });
                                }} className="w-full py-1.5 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-500 hover:text-cyan-600 hover:border-cyan-400/50 transition-colors font-bold">
                                    + {__('Add Button')}
                                </button>
                            )}
                        </div>
                    </div>
                ) : part.type === 'list' ? (
                    /* ── Interactive List Menu Builder ── */
                    <div className="space-y-3">
                        <div className="flex items-center gap-2 px-2.5 py-1.5 bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/30 rounded-lg">
                            <AlertTriangle className="w-3 h-3 text-amber-600 shrink-0" />
                            <span className="text-[10px] text-amber-700 dark:text-amber-400 font-medium">
                                {__('List messages may be limited depending on WhatsApp account type')}
                            </span>
                        </div>
                        <Textarea
                            value={part.message || ''}
                            onChange={e => onChange({ ...part, message: e.target.value })}
                            placeholder={__('Main message text...')}
                            rows={2}
                            className="resize-none text-sm text-start"
                        />
                        <div className="grid grid-cols-2 gap-2">
                            <Input
                                value={part.footer || ''}
                                onChange={e => onChange({ ...part, footer: e.target.value })}
                                placeholder={__('Footer (optional)')}
                                className="text-xs h-8 text-start"
                            />
                            <Input
                                value={part.buttonText || ''}
                                onChange={e => onChange({ ...part, buttonText: e.target.value })}
                                placeholder={__('Menu button text (e.g. Menu)')}
                                className="text-xs h-8 text-start"
                            />
                        </div>
                        <div className="space-y-3">
                            <Label className="text-xs font-bold text-start block">
                                {__('List Sections')}
                            </Label>
                            {(part.sections || []).map((sec, sIdx) => (
                                <div key={sIdx} className="border border-slate-200 dark:border-slate-700 rounded-xl p-3 space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Input
                                            value={sec.title}
                                            onChange={e => {
                                                const updated = [...(part.sections || [])];
                                                updated[sIdx] = { ...updated[sIdx], title: e.target.value };
                                                onChange({ ...part, sections: updated });
                                            }}
                                            placeholder={__(`Section ${sIdx + 1} title`)}
                                            className="text-xs h-7 text-start flex-1 font-bold"
                                        />
                                        <button type="button" onClick={() => {
                                            const updated = (part.sections || []).filter((_, i) => i !== sIdx);
                                            onChange({ ...part, sections: updated });
                                        }} className="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 text-slate-400 hover:text-red-500">
                                            <X className="w-3 h-3" />
                                        </button>
                                    </div>
                                    {(sec.rows || []).map((row, rIdx) => (
                                        <div key={rIdx} className="flex items-center gap-2 ps-4">
                                            <div className="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0" />
                                            <Input
                                                value={row.title}
                                                onChange={e => {
                                                    const updatedSections = [...(part.sections || [])];
                                                    const updatedRows = [...(updatedSections[sIdx].rows || [])];
                                                    updatedRows[rIdx] = { ...updatedRows[rIdx], title: e.target.value };
                                                    updatedSections[sIdx] = { ...updatedSections[sIdx], rows: updatedRows };
                                                    onChange({ ...part, sections: updatedSections });
                                                }}
                                                placeholder={__('Item title')}
                                                className="text-xs h-7 text-start flex-1"
                                            />
                                            <Input
                                                value={row.description}
                                                onChange={e => {
                                                    const updatedSections = [...(part.sections || [])];
                                                    const updatedRows = [...(updatedSections[sIdx].rows || [])];
                                                    updatedRows[rIdx] = { ...updatedRows[rIdx], description: e.target.value };
                                                    updatedSections[sIdx] = { ...updatedSections[sIdx], rows: updatedRows };
                                                    onChange({ ...part, sections: updatedSections });
                                                }}
                                                placeholder={__('Description')}
                                                className="text-xs h-7 text-start flex-1"
                                            />
                                            <button type="button" onClick={() => {
                                                const updatedSections = [...(part.sections || [])];
                                                updatedSections[sIdx] = { ...updatedSections[sIdx], rows: (updatedSections[sIdx].rows || []).filter((_, i) => i !== rIdx) };
                                                onChange({ ...part, sections: updatedSections });
                                            }} className="w-5 h-5 flex items-center justify-center rounded hover:bg-red-100 text-slate-400 hover:text-red-500">
                                                <X className="w-2.5 h-2.5" />
                                            </button>
                                        </div>
                                    ))}
                                    {(sec.rows || []).length < 10 && (
                                        <button type="button" onClick={() => {
                                            const updatedSections = [...(part.sections || [])];
                                            const newRow = { id: `row_${Date.now()}`, title: '', description: '' };
                                            updatedSections[sIdx] = { ...updatedSections[sIdx], rows: [...(updatedSections[sIdx].rows || []), newRow] };
                                            onChange({ ...part, sections: updatedSections });
                                        }} className="w-full py-1 border border-dashed border-slate-200 dark:border-slate-700 rounded-lg text-[10px] text-slate-500 hover:text-indigo-600 hover:border-indigo-400/50 transition-colors font-bold ms-4">
                                            + {__('Add Row')}
                                        </button>
                                    )}
                                </div>
                            ))}
                            <button type="button" onClick={() => {
                                const newSection = { title: '', rows: [{ id: `row_${Date.now()}`, title: '', description: '' }] };
                                onChange({ ...part, sections: [...(part.sections || []), newSection] });
                            }} className="w-full py-1.5 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-500 hover:text-indigo-600 hover:border-indigo-400/50 transition-colors font-bold">
                                + {__('Add Section')}
                            </button>
                        </div>
                    </div>
                ) : part.type === 'poll' ? (
                    /* ── Poll Builder ── */
                    <div className="space-y-3">
                        <Input
                            value={part.pollName || ''}
                            onChange={e => onChange({ ...part, pollName: e.target.value })}
                            placeholder={__('Poll question...')}
                            className="text-sm h-9 text-start font-bold"
                        />
                        <div className="space-y-2">
                            <Label className="text-xs font-bold text-start block">
                                {__('Poll Options (max 12)')}
                            </Label>
                            {(part.pollOptions || []).map((opt, oIdx) => (
                                <div key={oIdx} className="flex items-center gap-2">
                                    <div className="w-5 h-5 rounded-md bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 text-[10px] font-bold shrink-0">{oIdx + 1}</div>
                                    <Input
                                        value={opt}
                                        onChange={e => {
                                            const updated = [...(part.pollOptions || [])];
                                            updated[oIdx] = e.target.value;
                                            onChange({ ...part, pollOptions: updated });
                                        }}
                                        placeholder={__(`Option ${oIdx + 1}`)}
                                        className="text-xs h-8 text-start flex-1"
                                    />
                                    <button type="button" onClick={() => {
                                        const updated = (part.pollOptions || []).filter((_, i) => i !== oIdx);
                                        onChange({ ...part, pollOptions: updated });
                                    }} className="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 text-slate-400 hover:text-red-500">
                                        <X className="w-3 h-3" />
                                    </button>
                                </div>
                            ))}
                            {(part.pollOptions || []).length < 12 && (
                                <button type="button" onClick={() => {
                                    onChange({ ...part, pollOptions: [...(part.pollOptions || []), ''] });
                                }} className="w-full py-1.5 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-500 hover:text-pink-600 hover:border-pink-400/50 transition-colors font-bold">
                                    + {__('Add Option')}
                                </button>
                            )}
                        </div>
                        <div className="flex items-center gap-3">
                            <Label className="text-xs font-medium text-start shrink-0">
                                {__('Selectable:')}
                            </Label>
                            <Input
                                type="number"
                                min={1}
                                max={Math.max(1, (part.pollOptions || []).length)}
                                value={part.selectableCount || 1}
                                onChange={e => onChange({ ...part, selectableCount: parseInt(e.target.value) || 1 })}
                                className="w-20 text-xs h-8 text-center"
                            />
                            <span className="text-[10px] text-muted-foreground">
                                {__('(1 = single choice)')}
                            </span>
                        </div>
                    </div>
                ) : (
                    <>
                        {/* File upload / library area */}
                        {!part.media_url && !uploading ? (
                            <div className="grid grid-cols-2 gap-2.5">
                                <div
                                    className="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-3 flex flex-col items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:border-teal-400/50 transition-all text-center"
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
                                    <CloudUpload className="w-5 h-5 text-slate-400 animate-pulse" />
                                    <span className="text-[11px] font-bold text-slate-600 dark:text-slate-300">
                                        {__('Upload Local')}
                                    </span>
                                </div>
                                <div
                                    className="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-3 flex flex-col items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:border-teal-400/50 transition-all text-center"
                                    onClick={() => setSelectorOpen(true)}
                                >
                                    <FolderOpen className="w-5 h-5 text-teal-600 dark:text-teal-400" />
                                    <span className="text-[11px] font-bold text-slate-600 dark:text-slate-300">
                                        {__('Media Library')}
                                    </span>
                                </div>
                            </div>
                        ) : uploading ? (
                            <div className="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 flex items-center justify-center gap-2 w-full bg-slate-50 dark:bg-slate-800/40">
                                <RefreshCw className="w-4 h-4 animate-spin text-teal-500" />
                                <span className="text-xs text-slate-500">{t.templates.uploading}</span>
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 w-full min-w-0 border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/10 rounded-xl p-3">
                                <div className="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                    <Check className="w-3.5 h-3.5" />
                                </div>
                                <span className="text-xs text-emerald-700 dark:text-emerald-300 font-medium truncate flex-1 text-start" title={part.media_url}>
                                    {part.media_url?.split('/').pop()}
                                </span>
                                <div className="flex items-center gap-2 shrink-0">
                                    <button 
                                        type="button" 
                                        onClick={() => setSelectorOpen(true)} 
                                        className="text-[10px] text-teal-600 dark:text-teal-400 hover:underline font-bold"
                                    >
                                        {__('Library')}
                                    </button>
                                    <span className="text-slate-300">|</span>
                                    <button 
                                        type="button" 
                                        onClick={() => fileInputRef.current?.click()} 
                                        className="text-[10px] text-teal-600 dark:text-teal-400 hover:underline font-bold"
                                    >
                                        {__('Upload')}
                                    </button>
                                </div>
                            </div>
                        )}

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

                        {/* Or paste URL */}
                        {!part.media_url && (
                            <Input
                                value={part.media_url || ''}
                                onChange={e => onChange({ ...part, media_url: e.target.value })}
                                placeholder={t.templates.orUrl}
                                className="text-xs font-mono h-8 text-start"
                            />
                        )}

                        {/* Caption or Send as Voice toggle */}
                        {part.type === 'audio' ? (
                            <div className="flex items-center justify-between border border-slate-150 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/10 rounded-xl p-2.5 mt-2">
                                <div className="flex flex-col text-start">
                                    <span className="text-xs font-bold text-slate-700 dark:text-slate-200">
                                        {__('Send as recorded voice message (PTT)')}
                                    </span>
                                    <span className="text-[10px] text-muted-foreground mt-0.5 leading-relaxed">
                                        {__('Appears to recipient as a real-time recorded voice note')}
                                    </span>
                                </div>
                                <Switch
                                    checked={!!part.send_as_voice}
                                    onCheckedChange={(checked) => onChange({ ...part, send_as_voice: checked })}
                                    className="data-checked:bg-teal-600"
                                />
                            </div>
                        ) : (
                            <Input
                                value={part.caption || ''}
                                onChange={e => onChange({ ...part, caption: e.target.value })}
                                placeholder={t.templates.captionPlaceholder}
                                className="text-xs h-8 text-start"
                            />
                        )}
                    </>
                )}
            </div>

            {selectorOpen && (
                <MediaLibrarySelectorModal
                    onClose={() => setSelectorOpen(false)}
                    onSelect={(url) => {
                        onChange({ ...part, media_url: url });
                        setSelectorOpen(false);
                    }}
                    activeType={part.type}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    t={t}
                    locale={locale}
                />
            )}
        </div>
    );
}

function TemplateEditor({ template, onSave, onCancel, t, locale, callRPC, daemonConnected }: any) {
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
        if (!name.trim()) return alert(t.templates.nameRequiredError);
        const validParts = parts.filter(p =>
            (p.type === 'text' && p.message?.trim()) ||
            (p.type === 'buttons' && p.message?.trim() && (p.buttons || []).length > 0) ||
            (p.type === 'list' && p.message?.trim() && (p.sections || []).length > 0) ||
            (p.type === 'poll' && p.pollName?.trim() && (p.pollOptions || []).filter(o => o.trim()).length >= 2) ||
            (!['text', 'buttons', 'list', 'poll'].includes(p.type) && p.media_url?.trim())
        );
        if (validParts.length === 0) return alert(t.templates.partRequiredError);
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
                        <h2 className="text-lg font-bold">{template?.id ? t.templates.editTitle : t.templates.newTemplateTitle}</h2>
                        <Button variant="ghost" size="icon" onClick={onCancel}>
                            <X className="w-4 h-4 text-muted-foreground" />
                        </Button>
                    </div>

                    {/* Scrollable content */}
                    <div className="overflow-y-auto flex-1 space-y-4 pe-1" style={{ maxHeight: 'calc(90vh - 200px)' }}>
                        {/* Template Name */}
                        <div className="space-y-2">
                            <Label className="text-start block">{t.templates.nameLabel}</Label>
                            <Input value={name} onChange={e => setName(e.target.value)} placeholder={t.templates.namePlaceholder} className="text-start" />
                        </div>

                        {/* Parts List */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <Label className="text-sm font-bold text-start block">{t.templates.partsLabel} ({parts.length})</Label>
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
                                    t={t}
                                    locale={locale}
                                    callRPC={callRPC}
                                    daemonConnected={daemonConnected}
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
                                        {t.templates.partTypes?.[value] || label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Tags */}
                        <div className="space-y-2">
                            <Label className="text-start block">{t.templates.tagsLabel}</Label>
                            <Input value={tagsInput} onChange={e => setTagsInput(e.target.value)} placeholder={t.templates.tagsPlaceholder} className="text-start" />
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex gap-3 mt-5 pt-4 border-t">
                        <Button variant="outline" onClick={onCancel} className="flex-1">{t.templates.cancel}</Button>
                        <Button onClick={handleSave} disabled={saving} className="flex-1 bg-teal-600 hover:bg-teal-700 text-white gap-2">
                            {saving ? <><div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" /> {t.templates.saving}</> : <><Save className="w-4 h-4" /> {t.templates.saveBtn}</>}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}


export default function TemplatesWorkspace({ 
    t, locale,
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
        if (!confirm(t.templates.deleteConfirm)) return;
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
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight">{t.templates.title}</h2>
                    <p className="text-sm text-muted-foreground mt-1">{t.templates.subtitle}</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="icon" onClick={fetch} disabled={loading}>
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </Button>
                    <Button onClick={() => setEditTarget('new')} className="bg-teal-600 hover:bg-teal-700 text-white gap-2">
                        <Plus className="w-4 h-4" /> {t.templates.newBtn}
                    </Button>
                </div>
            </div>

            <div className="relative">
                <Search className="absolute start-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input value={search} onChange={e => setSearch(e.target.value)} placeholder={t.templates.searchPlaceholder} className="ps-10 text-start" />
            </div>

            {/* Template Grid */}
            {filtered.length === 0 && !loading ? (
                <Card className="border-dashed">
                    <CardContent className="flex flex-col items-center gap-4 text-center py-16">
                        <div className="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center">
                            <FileText className="w-8 h-8 text-muted-foreground" />
                        </div>
                        <div>
                            <h3 className="font-bold">{t.templates.emptyTitle}</h3>
                            <p className="text-sm text-muted-foreground mt-1">{t.templates.emptySub}</p>
                        </div>
                        <Button onClick={() => setEditTarget('new')} className="bg-teal-600 hover:bg-teal-700 text-white gap-2">
                            <Plus className="w-4 h-4" /> {t.templates.createFirstBtn}
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 text-start">
                    {filtered.map(tpl => (
                        <TemplateCard key={tpl.id} tpl={tpl} onEdit={setEditTarget} onDelete={handleDelete} onUse={onUseTemplate} t={t} locale={locale} />
                    ))}
                </div>
            )}

            {/* Editor Modal */}
            {editTarget !== null && (
                <TemplateEditor
                    template={editTarget === 'new' ? null : editTarget}
                    onSave={handleSave}
                    onCancel={() => setEditTarget(null)}
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                />
            )}
        </div>
    );
}
