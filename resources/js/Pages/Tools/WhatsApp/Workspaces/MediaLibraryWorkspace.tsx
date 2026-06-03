import React, { useState, useEffect, useRef } from 'react';
import { Image as ImageIcon, Film, FileText, Music, Upload, Trash2, Copy, Check, Search, FolderOpen, X, Download, Eye, CloudUpload } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';

interface MediaItem {
    id: string;
    name: string;
    type: 'image' | 'video' | 'document' | 'audio';
    url: string;
    size: number;
    created_at: string;
}

const TYPE_ICONS: Record<string, any> = {
    image: ImageIcon,
    video: Film,
    document: FileText,
    audio: Music,
};

const TYPE_COLORS: Record<string, string> = {
    image: 'bg-blue-50 text-blue-600 dark:bg-blue-950/30',
    video: 'bg-purple-50 text-purple-600 dark:bg-purple-950/30',
    document: 'bg-amber-50 text-amber-600 dark:bg-amber-950/30',
    audio: 'bg-pink-50 text-pink-600 dark:bg-pink-950/30',
};

function formatBytes(bytes: number) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

export default function MediaLibraryWorkspace({ t, locale, callRPC, daemonConnected }: any) {
    const isRtl = locale === 'ar';
    const [media, setMedia] = useState<MediaItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [typeFilter, setTypeFilter] = useState('all');
    const [copiedId, setCopiedId] = useState<string | null>(null);
    const [previewItem, setPreviewItem] = useState<MediaItem | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<{ total: number; done: number; uploading: boolean }>({ total: 0, done: 0, uploading: false });
    const dragCounter = useRef(0);

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
        if (daemonConnected) fetchMedia();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [daemonConnected, typeFilter]);

    const processFiles = async (files: File[]) => {
        if (files.length === 0) return;
        setUploadProgress({ total: files.length, done: 0, uploading: true });

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            try {
                const base64 = await new Promise<string>((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (ev) => resolve(ev.target?.result as string);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });

                let type: string = 'document';
                if (file.type.startsWith('image/')) type = 'image';
                else if (file.type.startsWith('video/')) type = 'video';
                else if (file.type.startsWith('audio/')) type = 'audio';

                await callRPC('saveMediaItem', {
                    name: file.name,
                    type,
                    data: base64,
                    size: file.size
                });
                setUploadProgress(prev => ({ ...prev, done: i + 1 }));
            } catch (err: any) {
                console.error(`Upload failed for ${file.name}:`, err);
            }
        }

        setUploadProgress({ total: 0, done: 0, uploading: false });
        fetchMedia();
    };

    const handleUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files;
        if (!files || files.length === 0) return;
        await processFiles(Array.from(files));
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    // ── Drag & Drop ──────────────────────────────────────────────────────
    const handleDragEnter = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current++;
        if (e.dataTransfer.types.includes('Files')) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current--;
        if (dragCounter.current === 0) {
            setIsDragging(false);
        }
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
    };

    const handleDrop = async (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        dragCounter.current = 0;
        setIsDragging(false);

        const files = Array.from(e.dataTransfer.files);
        if (files.length > 0) {
            await processFiles(files);
        }
    };

    const handleDelete = async (id: string) => {
        if (!confirm(isRtl ? 'حذف هذا الملف؟' : 'Delete this file?')) return;
        try {
            await callRPC('deleteMediaItem', { id });
            fetchMedia();
        } catch (err: any) {
            alert(`Delete failed: ${err.message}`);
        }
    };

    const handleCopy = (url: string, id: string) => {
        navigator.clipboard.writeText(url);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    const filtered = media.filter(m => {
        if (search && !m.name.toLowerCase().includes(search.toLowerCase())) return false;
        return true;
    });

    const typeCounts = media.reduce((acc: Record<string, number>, m) => {
        acc[m.type] = (acc[m.type] || 0) + 1;
        return acc;
    }, {});

    return (
        <div
            className={`space-y-6 animate-in fade-in duration-300 relative transition-all ${isDragging ? 'ring-2 ring-pink-500/50 ring-offset-4 rounded-3xl' : ''}`}
            onDragEnter={handleDragEnter}
            onDragLeave={handleDragLeave}
            onDragOver={handleDragOver}
            onDrop={handleDrop}
        >
            {/* Header */}
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight flex items-center gap-2">
                        <div className="size-9 rounded-xl bg-gradient-to-br from-pink-100 to-purple-100 dark:from-pink-950/40 dark:to-purple-950/40 flex items-center justify-center">
                            <FolderOpen className="w-5 h-5 text-pink-600" />
                        </div>
                        {isRtl ? 'مكتبة الوسائط' : 'Media Library'}
                        <Badge variant="secondary" className="text-xs font-bold">{media.length}</Badge>
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'ارفع واستخدم الصور والفيديو والملفات في حملاتك' : 'Upload and use images, videos, and files in your campaigns'}
                    </p>
                </div>
                <div>
                    <input ref={fileInputRef} type="file" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xlsx,.csv" className="hidden" onChange={handleUpload} />
                    <Button onClick={() => fileInputRef.current?.click()} className="bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl gap-2">
                        <Upload className="w-4 h-4" />
                        {isRtl ? 'رفع ملفات' : 'Upload Files'}
                    </Button>
                </div>
            </div>

            {/* Filters */}
            <div className="flex gap-3 flex-wrap">
                <div className="relative flex-1 min-w-[200px]">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        placeholder={isRtl ? 'بحث بالاسم...' : 'Search by name...'}
                        className="pl-10 rounded-xl text-start"
                    />
                </div>
                <div className="flex gap-1.5">
                    {['all', 'image', 'video', 'document', 'audio'].map(type => {
                        const Icon = type === 'all' ? FolderOpen : TYPE_ICONS[type];
                        return (
                            <Button
                                key={type}
                                variant={typeFilter === type ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setTypeFilter(type)}
                                className={`rounded-lg text-xs gap-1 font-bold ${typeFilter === type ? 'bg-pink-600 hover:bg-pink-700 text-white' : ''}`}
                            >
                                <Icon className="w-3 h-3" />
                                {type === 'all' ? (isRtl ? 'الكل' : 'All') : type}
                                {type !== 'all' && typeCounts[type] ? <Badge variant="secondary" className="text-[9px] px-1 h-3.5 ml-1">{typeCounts[type]}</Badge> : null}
                            </Button>
                        );
                    })}
                </div>
            </div>

            {/* Drag & Drop Zone */}
            <div
                className={`relative border-2 border-dashed rounded-2xl transition-all duration-300 cursor-pointer ${
                    isDragging
                        ? 'border-pink-500 bg-pink-50/50 dark:bg-pink-950/20 scale-[1.01]'
                        : 'border-muted-foreground/20 hover:border-pink-400/50 hover:bg-pink-50/20 dark:hover:bg-pink-950/10'
                }`}
                onClick={() => fileInputRef.current?.click()}
            >
                <div className={`flex flex-col items-center justify-center py-8 gap-3 transition-all duration-300 ${isDragging ? 'scale-110' : ''}`}>
                    <div className={`size-14 rounded-2xl flex items-center justify-center transition-all duration-300 ${
                        isDragging
                            ? 'bg-pink-100 dark:bg-pink-900/30 animate-bounce'
                            : 'bg-muted/30'
                    }`}>
                        <CloudUpload className={`w-7 h-7 transition-colors duration-300 ${isDragging ? 'text-pink-600' : 'text-muted-foreground/50'}`} />
                    </div>
                    <div className="text-center">
                        <p className={`text-sm font-bold transition-colors duration-300 ${isDragging ? 'text-pink-600' : 'text-muted-foreground'}`}>
                            {isDragging
                                ? (isRtl ? '✨ أفلت الملفات هنا!' : '✨ Drop files here!')
                                : (isRtl ? 'اسحب وأفلت الملفات هنا أو انقر للرفع' : 'Drag & drop files here, or click to browse')
                            }
                        </p>
                        <p className="text-[10px] text-muted-foreground mt-1">
                            {isRtl ? 'صور، فيديو، صوتيات، PDF، مستندات' : 'Images, Videos, Audio, PDF, Documents'}
                        </p>
                    </div>
                </div>
            </div>

            {/* Upload Progress */}
            {uploadProgress.uploading && (
                <div className="bg-pink-50 dark:bg-pink-950/20 rounded-2xl p-4 flex items-center gap-4 animate-in slide-in-from-top-2 duration-300">
                    <span className="size-8 border-3 border-pink-500 border-t-transparent rounded-full animate-spin shrink-0" />
                    <div className="flex-1 min-w-0">
                        <p className="text-xs font-bold text-pink-700 dark:text-pink-300">
                            {isRtl
                                ? `جارٍ رفع ${uploadProgress.done}/${uploadProgress.total} ملف...`
                                : `Uploading ${uploadProgress.done}/${uploadProgress.total} files...`
                            }
                        </p>
                        <div className="h-1.5 bg-pink-200 dark:bg-pink-900 rounded-full mt-2 overflow-hidden">
                            <div
                                className="h-full bg-pink-500 rounded-full transition-all duration-500 ease-out"
                                style={{ width: `${uploadProgress.total > 0 ? (uploadProgress.done / uploadProgress.total) * 100 : 0}%` }}
                            />
                        </div>
                    </div>
                </div>
            )}

            {/* Media Grid */}
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                {filtered.map(item => {
                    const Icon = TYPE_ICONS[item.type] || FileText;
                    return (
                        <Card key={item.id} className="rounded-2xl overflow-hidden hover:shadow-md transition-all duration-200 group">
                            {/* Preview */}
                            <div
                                className="h-32 bg-muted/20 flex items-center justify-center relative cursor-pointer"
                                onClick={() => setPreviewItem(item)}
                            >
                                {item.type === 'image' && item.url ? (
                                    <img src={item.url} alt={item.name} className="w-full h-full object-cover" />
                                ) : (
                                    <div className={`size-14 rounded-xl ${TYPE_COLORS[item.type]} flex items-center justify-center`}>
                                        <Icon className="w-7 h-7" />
                                    </div>
                                )}
                                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <Eye className="w-6 h-6 text-white" />
                                </div>
                            </div>
                            {/* Info */}
                            <CardContent className="p-3 text-start space-y-2">
                                <p className="text-xs font-bold truncate" title={item.name}>{item.name}</p>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-1.5">
                                        <Badge variant="outline" className="text-[9px] px-1.5 py-0 h-4 rounded">{item.type}</Badge>
                                        <span className="text-[10px] text-muted-foreground">{formatBytes(item.size)}</span>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-6 w-6 rounded"
                                            onClick={() => handleCopy(item.url, item.id)}
                                        >
                                            {copiedId === item.id ? <Check className="w-3 h-3 text-emerald-500" /> : <Copy className="w-3 h-3" />}
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-6 w-6 rounded text-destructive"
                                            onClick={() => handleDelete(item.id)}
                                        >
                                            <Trash2 className="w-3 h-3" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {filtered.length === 0 && !loading && (
                <Card className="rounded-2xl">
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <div className="size-16 rounded-2xl bg-pink-50/50 dark:bg-pink-950/20 flex items-center justify-center mb-4">
                            <FolderOpen className="w-7 h-7 text-pink-400/50" />
                        </div>
                        <p className="text-sm font-bold">{isRtl ? 'لا توجد ملفات' : 'No media files yet'}</p>
                        <p className="text-xs text-muted-foreground mt-1">
                            {isRtl ? 'ارفع صور وفيديو وملفات لاستخدامها في حملاتك' : 'Upload images, videos, and files to use in campaigns'}
                        </p>
                    </div>
                </Card>
            )}

            {/* Preview Modal */}
            {previewItem && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200" onClick={() => setPreviewItem(null)}>
                    <div className="bg-background rounded-2xl max-w-2xl w-full max-h-[80vh] overflow-auto shadow-2xl" onClick={e => e.stopPropagation()}>
                        <div className="flex items-center justify-between p-4 border-b">
                            <h3 className="font-bold text-sm truncate flex-1">{previewItem.name}</h3>
                            <Button variant="ghost" size="icon" className="h-8 w-8 rounded-lg" onClick={() => setPreviewItem(null)}>
                                <X className="w-4 h-4" />
                            </Button>
                        </div>
                        <div className="p-4 flex items-center justify-center min-h-[200px]">
                            {previewItem.type === 'image' && previewItem.url && (
                                <img src={previewItem.url} alt={previewItem.name} className="max-w-full max-h-[60vh] rounded-xl" />
                            )}
                            {previewItem.type === 'video' && previewItem.url && (
                                <video src={previewItem.url} controls className="max-w-full max-h-[60vh] rounded-xl" />
                            )}
                            {previewItem.type === 'audio' && previewItem.url && (
                                <audio src={previewItem.url} controls className="w-full" />
                            )}
                            {previewItem.type === 'document' && (
                                <div className="flex flex-col items-center gap-4">
                                    <div className={`size-20 rounded-2xl ${TYPE_COLORS.document} flex items-center justify-center`}>
                                        <FileText className="w-10 h-10" />
                                    </div>
                                    <p className="text-sm font-bold">{previewItem.name}</p>
                                    <p className="text-xs text-muted-foreground">{formatBytes(previewItem.size)}</p>
                                </div>
                            )}
                        </div>
                        <div className="p-4 border-t flex gap-2 justify-end">
                            <Button variant="outline" className="rounded-xl gap-1.5 text-xs font-bold" onClick={() => handleCopy(previewItem.url, previewItem.id)}>
                                {copiedId === previewItem.id ? <Check className="w-3 h-3" /> : <Copy className="w-3 h-3" />}
                                {isRtl ? 'نسخ الرابط' : 'Copy URL'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Full-page drag overlay */}
            {isDragging && (
                <div className="fixed inset-0 z-40 pointer-events-none">
                    <div className="absolute inset-0 bg-pink-500/5 backdrop-blur-[1px] animate-in fade-in duration-200" />
                </div>
            )}
        </div>
    );
}
