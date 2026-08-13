import React, { useCallback, useState } from 'react';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { 
    Image as ImageIcon, 
    Video, 
    X, 
    UploadCloud, 
    Sparkles, 
    Loader2, 
    Copy, 
    Check, 
    ExternalLink, 
    FileText,
    Star,
    ChevronLeft,
    ChevronRight,
    GripVertical
} from 'lucide-react';
import { useDropzone } from 'react-dropzone';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { __ } from '@/lib/i18n';

interface UnifiedItem {
    id: string;
    type: 'kept' | 'new';
    path?: string;
    file?: File;
    src: string;
    originalIndex: number;
}

export default function GalleryStep({ data, setData, errors }: any) {
    const { auth } = usePage().props as any;
    const isAdmin = auth?.user && (
        auth.user.role === 'admin' || 
        auth.user.role === 'super_admin' || 
        auth.user.roles?.includes('admin') || 
        auth.user.is_admin
    );

    const [generatingAi, setGeneratingAi] = useState(false);
    const [fetchingPrompt, setFetchingPrompt] = useState(false);
    const [promptText, setPromptText] = useState<string | null>(null);
    const [copiedPrompt, setCopiedPrompt] = useState(false);
    const [showPromptBox, setShowPromptBox] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);

    const [dragItemIndex, setDragItemIndex] = useState<number | null>(null);
    const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);

    const keptCount = data.kept_gallery?.length || 0;
    const totalCount = keptCount + (data.gallery?.length || 0);

    const getImageSrc = (path: string) => {
        if (path.startsWith('http')) return path;
        if (path.startsWith('/')) return path;
        const clean = path.replace(/^storage\//, '').replace(/^uploads\//, '');
        return `/uploads/${clean}`;
    };

    const getUnifiedItems = (): UnifiedItem[] => {
        const keptList: string[] = data.kept_gallery || [];
        const newFilesList: File[] = data.gallery || [];
        const orderList: string[] = data.gallery_order || [];

        if (orderList.length > 0) {
            const items: UnifiedItem[] = [];
            const usedKept = new Set<number>();
            const usedNew = new Set<number>();

            orderList.forEach((token) => {
                if (token.startsWith('kept:')) {
                    const kIdx = parseInt(token.replace('kept:', ''), 10);
                    if (!isNaN(kIdx) && keptList[kIdx] !== undefined && !usedKept.has(kIdx)) {
                        usedKept.add(kIdx);
                        items.push({
                            id: `kept-${kIdx}-${keptList[kIdx]}`,
                            type: 'kept',
                            path: keptList[kIdx],
                            src: getImageSrc(keptList[kIdx]),
                            originalIndex: kIdx,
                        });
                    }
                } else if (token.startsWith('new:') || token.startsWith('gallery:')) {
                    const nIdx = parseInt(token.replace(/^(new:|gallery:)/, ''), 10);
                    if (!isNaN(nIdx) && newFilesList[nIdx] !== undefined && !usedNew.has(nIdx)) {
                        usedNew.add(nIdx);
                        items.push({
                            id: `new-${nIdx}-${newFilesList[nIdx].name}`,
                            type: 'new',
                            file: newFilesList[nIdx],
                            src: URL.createObjectURL(newFilesList[nIdx]),
                            originalIndex: nIdx,
                        });
                    }
                }
            });

            // Append any remaining items
            keptList.forEach((path: string, kIdx: number) => {
                if (!usedKept.has(kIdx)) {
                    items.push({
                        id: `kept-${kIdx}-${path}`,
                        type: 'kept',
                        path,
                        src: getImageSrc(path),
                        originalIndex: kIdx,
                    });
                }
            });

            newFilesList.forEach((file: File, nIdx: number) => {
                if (!usedNew.has(nIdx)) {
                    items.push({
                        id: `new-${nIdx}-${file.name}`,
                        type: 'new',
                        file,
                        src: URL.createObjectURL(file),
                        originalIndex: nIdx,
                    });
                }
            });

            return items;
        }

        const defaultItems: UnifiedItem[] = [];
        keptList.forEach((path: string, kIdx: number) => {
            defaultItems.push({
                id: `kept-${kIdx}-${path}`,
                type: 'kept',
                path,
                src: getImageSrc(path),
                originalIndex: kIdx,
            });
        });
        newFilesList.forEach((file: File, nIdx: number) => {
            defaultItems.push({
                id: `new-${nIdx}-${file.name}`,
                type: 'new',
                file,
                src: URL.createObjectURL(file),
                originalIndex: nIdx,
            });
        });
        return defaultItems;
    };

    const syncState = (items: UnifiedItem[]) => {
        const newKept: string[] = [];
        const newGalleryFiles: File[] = [];
        const newOrder: string[] = [];

        items.forEach((item) => {
            if (item.type === 'kept' && item.path) {
                const indexInKept = newKept.length;
                newKept.push(item.path);
                newOrder.push(`kept:${indexInKept}`);
            } else if (item.type === 'new' && item.file) {
                const indexInNew = newGalleryFiles.length;
                newGalleryFiles.push(item.file);
                newOrder.push(`new:${indexInNew}`);
            }
        });

        if (typeof setData === 'function') {
            setData((prev: any) => ({
                ...prev,
                kept_gallery: newKept,
                gallery: newGalleryFiles,
                gallery_order: newOrder,
            }));
        }
    };

    const handleReorder = (fromIdx: number, toIdx: number) => {
        const items = getUnifiedItems();
        if (fromIdx < 0 || fromIdx >= items.length || toIdx < 0 || toIdx >= items.length || fromIdx === toIdx) {
            return;
        }
        const updated = [...items];
        const [moved] = updated.splice(fromIdx, 1);
        updated.splice(toIdx, 0, moved);
        syncState(updated);
    };

    const handleMakePrimary = (idx: number) => {
        handleReorder(idx, 0);
    };

    const handleRemoveItem = (idx: number) => {
        const items = getUnifiedItems();
        const updated = items.filter((_, i) => i !== idx);
        syncState(updated);
    };

    const handleGenerateAiImage = async () => {
        if (totalCount >= 5) return;
        setGeneratingAi(true);
        setAiError(null);

        try {
            const titlePrompt = data.title || 'Software Development Service';
            const response = await axios.post(route('marketplace.services.generate-ai-image'), {
                title: titlePrompt,
                description: data.description || '',
            });

            if (response.data?.success && response.data?.path) {
                const items = getUnifiedItems();
                const newItem: UnifiedItem = {
                    id: `kept-ai-${Date.now()}-${response.data.path}`,
                    type: 'kept',
                    path: response.data.path,
                    src: getImageSrc(response.data.path),
                    originalIndex: items.length,
                };
                syncState([...items, newItem]);
                if (response.data?.prompt) {
                    setPromptText(response.data.prompt);
                }
            } else {
                setAiError(response.data?.error || 'فشل في توليد الصورة. يرجى المحاولة لاحقاً.');
            }
        } catch (err: any) {
            setAiError(err?.response?.data?.error || err?.message || 'حدث خطأ أثناء الاتصال بسيرفر الذكاء الاصطناعي.');
        } finally {
            setGeneratingAi(false);
        }
    };

    const handleFetchPrompt = async () => {
        setFetchingPrompt(true);
        setAiError(null);
        try {
            const titlePrompt = data.title || 'Software Development Service';
            const response = await axios.post(route('marketplace.services.get-ai-image-prompt'), {
                title: titlePrompt,
                description: data.description || '',
            });

            if (response.data?.success && response.data?.prompt) {
                setPromptText(response.data.prompt);
                setShowPromptBox(true);
            }
        } catch (err: any) {
            setAiError(err?.response?.data?.error || err?.message || 'تعذر جلب البرومبت المحسن.');
        } finally {
            setFetchingPrompt(false);
        }
    };

    const handleCopyPromptText = () => {
        if (!promptText) return;
        navigator.clipboard.writeText(promptText);
        setCopiedPrompt(true);
        setTimeout(() => setCopiedPrompt(false), 2500);
    };

    const onDrop = useCallback((acceptedFiles: File[]) => {
        const currentTotal = (data.kept_gallery?.length || 0) + (data.gallery?.length || 0);
        if (currentTotal + acceptedFiles.length <= 5) {
            const items = getUnifiedItems();
            const newItems: UnifiedItem[] = acceptedFiles.map((file, idx) => ({
                id: `new-drop-${Date.now()}-${idx}-${file.name}`,
                type: 'new',
                file,
                src: URL.createObjectURL(file),
                originalIndex: items.length + idx,
            }));
            syncState([...items, ...newItems]);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.gallery, data.kept_gallery, data.gallery_order, setData]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: { 'image/*': ['.jpeg', '.png', '.jpg', '.webp'] },
        maxSize: 5 * 1024 * 1024, // 5MB
        disabled: totalCount >= 5
    });

    const unifiedItems = getUnifiedItems();

    return (
        <div className="space-y-10">
            <div>
                <h2 className="text-2xl font-bold text-slate-900 mb-1">{__('general.showcase_your_services')}</h2>
                <p className="text-sm text-slate-500">{__('general.encourage_buyers_to_choose_your_service_by_featuring_a_variety_of_your_work')}</p>
            </div>

            {isAdmin && (
                <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl p-4 sm:p-5 border border-indigo-500/30 shadow-md space-y-4">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-indigo-500/20 rounded-xl border border-indigo-400/30 shrink-0">
                                <Sparkles className="w-5 h-5 text-amber-400 animate-pulse" />
                            </div>
                            <div>
                                <h4 className="text-sm font-bold flex items-center gap-2 text-white">
                                    أدوات غلاف الخدمة بالذكاء الاصطناعي (أدمن فقط)
                                    <span className="text-[10px] bg-amber-400/20 text-amber-300 font-semibold px-2 py-0.5 rounded-full border border-amber-400/30">Admin Only</span>
                                </h4>
                                <p className="text-xs text-slate-300 mt-0.5">
                                    توليد صورة تلقائية مباشرة أو إظهار البرومبت المخصص لنسخه وإرساله لـ ChatGPT يدويًا.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            {/* Option 1: Direct AI Generation */}
                            <button
                                type="button"
                                onClick={handleGenerateAiImage}
                                disabled={generatingAi || totalCount >= 5}
                                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 via-purple-600 to-indigo-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-bold rounded-xl shadow-lg hover:shadow-indigo-500/30 transition-all shrink-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {generatingAi ? (
                                    <>
                                        <Loader2 className="w-4 h-4 animate-spin text-amber-300" />
                                        <span>جاري التصميم...</span>
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="w-4 h-4 text-amber-300" />
                                        <span>توليد صورة تلقائياً</span>
                                    </>
                                )}
                            </button>

                            {/* Option 2: Copy ChatGPT Prompt */}
                            <button
                                type="button"
                                onClick={handleFetchPrompt}
                                disabled={fetchingPrompt}
                                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-100 text-xs font-bold rounded-xl transition-all shrink-0 cursor-pointer"
                            >
                                {fetchingPrompt ? (
                                    <Loader2 className="w-4 h-4 animate-spin text-indigo-400" />
                                ) : (
                                    <FileText className="w-4 h-4 text-emerald-400" />
                                )}
                                <span>نسخ البرومبت لـ ChatGPT 📋</span>
                            </button>
                        </div>
                    </div>

                    {/* Expandable Prompt Copy Box */}
                    {(showPromptBox || promptText) && (
                        <div className="mt-3 bg-slate-950/80 border border-indigo-500/30 rounded-xl p-4 space-y-3 transition-all">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                                    <FileText className="w-4 h-4" /> البرومبت المحسن الجاهز لـ ChatGPT (Single SaaS Hero Dashboard)
                                </span>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={handleCopyPromptText}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition-all"
                                    >
                                        {copiedPrompt ? (
                                            <>
                                                <Check className="w-3.5 h-3.5 text-white" />
                                                <span>تم النسخ!</span>
                                            </>
                                        ) : (
                                            <>
                                                <Copy className="w-3.5 h-3.5" />
                                                <span>نسخ البرومبت</span>
                                            </>
                                        )}
                                    </button>
                                    <a
                                        href="https://chatgpt.com"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-lg transition-all border border-slate-700"
                                    >
                                        <ExternalLink className="w-3.5 h-3.5 text-blue-400" />
                                        <span>فتح ChatGPT</span>
                                    </a>
                                </div>
                            </div>
                            <div className="bg-slate-900 border border-slate-800 rounded-lg p-3 text-xs text-slate-300 font-mono leading-relaxed select-all">
                                {promptText}
                            </div>
                            <p className="text-[11px] text-slate-400 dir-rtl">
                                💡 **طريقة الاستخدام**: انسخ هذا النص أعلاه، ثم افتح ChatGPT والصقه مباشرة ليولد لك أحدث وأفضل صورة غلاف عالية الدقة، ثم ارفع الصورة الناتجة في المربع أدناه يدويًا.
                            </p>
                        </div>
                    )}

                    {aiError && (
                        <p className="text-xs text-red-400 mt-2 font-medium bg-red-950/40 p-2 rounded-lg border border-red-500/20">{aiError}</p>
                    )}
                </div>
            )}

            <div className="space-y-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                        <ImageIcon className="w-5 h-5 text-indigo-500" /> Images (up to 5)
                    </h3>
                    <p className="text-sm text-slate-500 mb-4">{__('general.get_noticed_by_the_right_buyers_with_visual_examples_of_your_services')}</p>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {/* Render Unified Ordered Images */}
                    {unifiedItems.map((item, idx) => {
                        const isPrimary = idx === 0;
                        const isDragging = dragItemIndex === idx;
                        const isDragOver = dragOverIndex === idx;

                        return (
                            <div
                                key={item.id}
                                draggable
                                onDragStart={(e) => {
                                    setDragItemIndex(idx);
                                    e.dataTransfer.effectAllowed = 'move';
                                }}
                                onDragOver={(e) => {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'move';
                                    if (dragOverIndex !== idx) setDragOverIndex(idx);
                                }}
                                onDragLeave={() => {
                                    if (dragOverIndex === idx) setDragOverIndex(null);
                                }}
                                onDrop={(e) => {
                                    e.preventDefault();
                                    setDragOverIndex(null);
                                    if (dragItemIndex !== null) {
                                        handleReorder(dragItemIndex, idx);
                                        setDragItemIndex(null);
                                    }
                                }}
                                onDragEnd={() => {
                                    setDragItemIndex(null);
                                    setDragOverIndex(null);
                                }}
                                className={`relative aspect-[4/3] rounded-2xl overflow-hidden border transition-all duration-200 group bg-slate-100 cursor-grab active:cursor-grabbing select-none ${
                                    isDragging ? 'opacity-40 scale-95 border-indigo-400' : 'opacity-100 border-slate-200 shadow-sm hover:shadow-md'
                                } ${isDragOver ? 'ring-4 ring-indigo-500/40 border-indigo-500 scale-[1.02]' : ''}`}
                            >
                                <img src={item.src} alt={`Service visual ${idx + 1}`} className="w-full h-full object-cover" />

                                {/* Primary Badge */}
                                {isPrimary && (
                                    <div className="absolute top-3 start-3 bg-emerald-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-md shadow-md flex items-center gap-1 z-10">
                                        <Star className="w-3 h-3 fill-white" />
                                        <span>{__('general.primary')}</span>
                                    </div>
                                )}

                                {/* Action Overlay */}
                                <div className="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-all duration-200 flex flex-col justify-between p-3 z-20">
                                    <div className="flex items-center justify-between gap-1">
                                        {!isPrimary ? (
                                            <button
                                                type="button"
                                                onClick={() => handleMakePrimary(idx)}
                                                className="inline-flex items-center gap-1 bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm transition-transform active:scale-95 cursor-pointer"
                                                title="Set as Primary cover image"
                                            >
                                                <Star className="w-3 h-3" /> Make Primary
                                            </button>
                                        ) : (
                                            <div />
                                        )}

                                        <button
                                            type="button"
                                            onClick={() => handleRemoveItem(idx)}
                                            className="p-1.5 bg-white/90 hover:bg-red-500 text-slate-700 hover:text-white rounded-lg transition-colors shadow-sm ms-auto cursor-pointer"
                                            title="Remove image"
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>

                                    <div className="flex items-center justify-between bg-white/90 backdrop-blur-sm rounded-xl p-1.5 shadow-md">
                                        <div className="flex items-center gap-1 text-[11px] font-bold text-slate-700 px-1.5">
                                            <GripVertical className="w-3.5 h-3.5 text-slate-400" />
                                            <span>#{idx + 1}</span>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            <button
                                                type="button"
                                                disabled={idx === 0}
                                                onClick={() => handleReorder(idx, idx - 1)}
                                                className="p-1 hover:bg-slate-200 text-slate-700 rounded-md disabled:opacity-30 disabled:hover:bg-transparent transition-colors cursor-pointer disabled:cursor-not-allowed"
                                                title="Move left"
                                            >
                                                <ChevronLeft className="w-4 h-4 rtl:rotate-180" />
                                            </button>
                                            <button
                                                type="button"
                                                disabled={idx === unifiedItems.length - 1}
                                                onClick={() => handleReorder(idx, idx + 1)}
                                                className="p-1 hover:bg-slate-200 text-slate-700 rounded-md disabled:opacity-30 disabled:hover:bg-transparent transition-colors cursor-pointer disabled:cursor-not-allowed"
                                                title="Move right"
                                            >
                                                <ChevronRight className="w-4 h-4 rtl:rotate-180" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}

                    {/* Upload Dropzone */}
                    {totalCount < 5 && (
                        <div 
                            {...getRootProps()} 
                            className={`aspect-[4/3] rounded-2xl border-2 border-dashed flex flex-col items-center justify-center p-6 text-center cursor-pointer transition-all ${
                                isDragActive ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100'
                            }`}
                        >
                            <input {...getInputProps()} />
                            <UploadCloud className={`w-8 h-8 mb-3 ${isDragActive ? 'text-indigo-500' : 'text-slate-400'}`} />
                            <p className="text-sm font-bold text-slate-700">{__('general.drag_drop_photos_or')}</p>
                            <p className="text-sm font-bold text-indigo-600 mb-1">{__('general.browse')}</p>
                            <p className="text-[10px] text-slate-400 uppercase tracking-wider font-bold">{__('general.max_5mb')}</p>
                        </div>
                    )}
                </div>
                {errors.gallery && <p className="text-xs text-red-500 font-medium">{errors.gallery}</p>}
                {(errors as any)['gallery.0'] && <p className="text-xs text-red-500 font-medium">{__('general.please_upload_at_least_one_image')}</p>}
            </div>

            <div className="border-t border-slate-200 pt-10 space-y-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                        <Video className="w-5 h-5 text-indigo-500" /> Video (Optional)
                    </h3>
                    <p className="text-sm text-slate-500">{__('general.capture_buyers_attention_with_a_video_that_showcases_your_service')}</p>
                </div>

                <div className="space-y-2 max-w-xl">
                    <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider">{__('general.youtube_or_vimeo_url')}</Label>
                    <Input
                        value={data.video_url || ''}
                        onChange={e => setData('video_url', e.target.value)}
                        placeholder={__('general.https_youtube_com_watch_v')}
                        className="h-12"
                    />
                    {errors.video_url && <p className="text-xs text-red-500 font-medium">{errors.video_url}</p>}
                </div>
            </div>
        </div>
    );
}
