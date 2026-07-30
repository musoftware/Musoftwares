import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft, Play, CheckCircle, Circle, BookOpen, AlertCircle, FileText, Settings, Loader2 } from 'lucide-react';
import axios from 'axios';

interface VideoItem {
    id: number;
    youtube_video_id: string;
    title: string;
    description: string;
    thumbnail: string;
    position: number;
    is_completed: boolean;
    notes: string;
}

interface PlaylistInfo {
    id: number;
    title: string;
    description: string;
    channel_title: string;
}

interface ShowProps {
    playlist: PlaylistInfo;
    videos: VideoItem[];
}

export default function Show({ playlist, videos }: ShowProps) {
    const [localVideos, setLocalVideos] = useState<VideoItem[]>(videos);
    const [activeIdx, setActiveIdx] = useState(0);
    const [activeTab, setActiveTab] = useState<'notes' | 'info'>('notes');
    
    const activeVideo = localVideos[activeIdx] || null;
    const [noteText, setNoteText] = useState(activeVideo ? activeVideo.notes : '');
    const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
    
    const saveTimeout = useRef<NodeJS.Timeout | null>(null);

    // Sync noteText state when changing active video
    useEffect(() => {
        if (activeVideo) {
            setNoteText(activeVideo.notes || '');
            setSaveStatus('idle');
        }
    }, [activeIdx]);

    const handleNoteChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const val = e.target.value;
        setNoteText(val);
        setSaveStatus('saving');

        if (saveTimeout.current) {
            clearTimeout(saveTimeout.current);
        }

        saveTimeout.current = setTimeout(() => {
            if (!activeVideo) return;
            axios.post(`/series/video/${activeVideo.id}/notes`, { notes: val })
                .then(() => {
                    setSaveStatus('saved');
                    setLocalVideos(prev => 
                        prev.map(v => v.id === activeVideo.id ? { ...v, notes: val } : v)
                    );
                })
                .catch(() => {
                    setSaveStatus('error');
                });
        }, 1200);
    };

    const toggleComplete = (video: VideoItem, index: number) => {
        const newStatus = !video.is_completed;
        
        // Optimistic UI Update
        setLocalVideos(prev =>
            prev.map(v => v.id === video.id ? { ...v, is_completed: newStatus } : v)
        );

        axios.post(`/series/video/${video.id}/complete`, { is_completed: newStatus })
            .catch(() => {
                // Revert on failure
                setLocalVideos(prev =>
                    prev.map(v => v.id === video.id ? { ...v, is_completed: !newStatus } : v)
                );
            });
    };

    const completedCount = localVideos.filter(v => v.is_completed).length;
    const progressPercent = localVideos.length > 0 
        ? Math.round((completedCount / localVideos.length) * 100) 
        : 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between w-full">
                    <Link
                        href="/series"
                        className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-400 hover:text-slate-100 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        الرجوع للأكاديمية
                    </Link>
                    <div className="text-right">
                        <h2 className="font-semibold text-lg text-slate-100 leading-tight font-sans">
                            {playlist.title}
                        </h2>
                        <span className="text-xs text-red-400 font-bold">{playlist.channel_title}</span>
                    </div>
                </div>
            }
        >
            <Head>
                <title>{`${playlist.title} - مساحة المذاكرة`}</title>
            </Head>

            <div className="min-h-screen bg-slate-950 text-slate-100 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    {/* Video Player & Study Notes (Left Panel) */}
                    <div className="lg:col-span-2 space-y-6">
                        {activeVideo ? (
                            <div className="space-y-4">
                                {/* YouTube Embed Iframe Wrapper */}
                                <div className="relative aspect-video w-full bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                                    <iframe
                                        src={`https://www.youtube.com/embed/${activeVideo.youtube_video_id}?autoplay=0&rel=0`}
                                        title={activeVideo.title}
                                        frameBorder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowFullScreen
                                        className="w-full h-full"
                                    ></iframe>
                                </div>

                                {/* Active Video Metadata */}
                                <div className="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4">
                                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-850 pb-4">
                                        <div className="text-right flex-1">
                                            <h1 className="text-xl font-bold text-slate-150 leading-tight">
                                                {activeVideo.title}
                                            </h1>
                                        </div>
                                        <button
                                            onClick={() => toggleComplete(activeVideo, activeIdx)}
                                            className={`px-4 py-2 text-xs font-bold rounded-xl border flex items-center gap-2 transition-all ${
                                                activeVideo.is_completed
                                                    ? 'bg-emerald-950/40 border-emerald-900/60 text-emerald-400'
                                                    : 'bg-slate-800 hover:bg-slate-700 border-slate-700 text-slate-300'
                                            }`}
                                        >
                                            <CheckCircle className="h-4 w-4" />
                                            {activeVideo.is_completed ? 'تمت دراسته بنجاح' : 'تحديد كـ مكتمل'}
                                        </button>
                                    </div>

                                    {/* Tabs (Notes vs Description) */}
                                    <div className="flex justify-end gap-2 border-b border-slate-850 pb-2">
                                        <button
                                            onClick={() => setActiveTab('info')}
                                            className={`px-4 py-2 text-xs font-bold rounded-lg border transition-all ${
                                                activeTab === 'info'
                                                    ? 'bg-red-950/20 border-red-900/50 text-red-400'
                                                    : 'border-transparent text-slate-400 hover:text-slate-200'
                                            }`}
                                        >
                                            تفاصيل الدرس
                                        </button>
                                        <button
                                            onClick={() => setActiveTab('notes')}
                                            className={`px-4 py-2 text-xs font-bold rounded-lg border transition-all ${
                                                activeTab === 'notes'
                                                    ? 'bg-red-950/20 border-red-900/50 text-red-400'
                                                    : 'border-transparent text-slate-400 hover:text-slate-200'
                                            }`}
                                        >
                                            مذكرات الدرس
                                        </button>
                                    </div>

                                    {/* Tab Contents */}
                                    {activeTab === 'notes' ? (
                                        <div className="space-y-3 text-right">
                                            <div className="flex items-center justify-between text-xs text-slate-500">
                                                <div className="flex items-center gap-1.5">
                                                    {saveStatus === 'saving' && (
                                                        <span className="flex items-center gap-1 text-slate-400">
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                            جاري الحفظ...
                                                        </span>
                                                    )}
                                                    {saveStatus === 'saved' && (
                                                        <span className="text-emerald-400">تم الحفظ تلقائيًا</span>
                                                    )}
                                                    {saveStatus === 'error' && (
                                                        <span className="text-rose-500">فشل في الحفظ!</span>
                                                    )}
                                                </div>
                                                <span className="font-bold flex items-center gap-1">
                                                    <FileText className="h-3.5 w-3.5" />
                                                    اكتب ملاحظاتك أثناء تشغيل الفيديو (تُحفظ تلقائيًا):
                                                </span>
                                            </div>
                                            <textarea
                                                value={noteText}
                                                onChange={handleNoteChange}
                                                placeholder="اكتب ملاحظاتك البرمجية، الأكواد الهامة، أو ملخص الدرس هنا..."
                                                rows={8}
                                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-650 focus:outline-none focus:border-red-500 text-right transition-colors font-mono text-sm leading-relaxed"
                                            />
                                        </div>
                                    ) : (
                                        <div className="space-y-2 text-right">
                                            <h3 className="text-sm font-bold text-slate-350">وصف الدرس:</h3>
                                            <div className="text-xs text-slate-400 leading-relaxed whitespace-pre-wrap font-sans">
                                                {activeVideo.description || 'لا يوجد وصف تفصيلي لهذا الفيديو.'}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-20 bg-slate-900/40 border border-slate-850 rounded-2xl">
                                <AlertCircle className="h-12 w-12 mx-auto text-slate-700 mb-2" />
                                <p className="text-slate-500">يرجى اختيار فيديو من القائمة للبدء.</p>
                            </div>
                        )}
                    </div>

                    {/* Playlist Curriculum Sidebar (Right Panel) */}
                    <div className="space-y-6">
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-xl shadow-xl flex flex-col justify-between space-y-6">
                            
                            {/* Playlist Header & Progress */}
                            <div className="space-y-3 text-right">
                                <h3 className="text-base font-bold text-slate-200">منهج المساق التعليمي</h3>
                                <div className="space-y-1.5 pt-1">
                                    <div className="flex items-center justify-between text-xs text-slate-450">
                                        <span>{progressPercent}% مكتمل</span>
                                        <span>{completedCount} من {localVideos.length} درس</span>
                                    </div>
                                    <div className="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-850">
                                        <div
                                            className="bg-gradient-to-r from-red-600 to-red-500 h-full rounded-full transition-all duration-500"
                                            style={{ width: `${progressPercent}%` }}
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            {/* Videos Scrollable List */}
                            <div className="space-y-3 max-h-[500px] overflow-y-auto scrollbar-thin pr-1 text-right">
                                {localVideos.map((video, idx) => {
                                    const isActive = idx === activeIdx;
                                    return (
                                        <div
                                            key={video.id}
                                            className={`group relative p-3 rounded-xl border transition-all duration-200 flex items-center justify-between gap-3 cursor-pointer ${
                                                isActive
                                                    ? 'bg-slate-900 border-red-900/60 shadow-lg'
                                                    : 'bg-slate-950/40 border-slate-850 hover:bg-slate-900/40'
                                            }`}
                                        >
                                            {/* Completion Checkbox */}
                                            <button
                                                onClick={(e) => {
                                                    e.stopPropagation(); // Avoid switching video when checking complete
                                                    toggleComplete(video, idx);
                                                }}
                                                className="shrink-0 text-slate-500 hover:text-emerald-500 transition-colors p-1"
                                            >
                                                {video.is_completed ? (
                                                    <CheckCircle className="h-5 w-5 text-emerald-500 fill-emerald-950/20" />
                                                ) : (
                                                    <Circle className="h-5 w-5 text-slate-650" />
                                                )}
                                            </button>

                                            {/* Video Details & Switch Click target */}
                                            <div
                                                onClick={() => setActiveIdx(idx)}
                                                className="flex-1 flex items-center gap-3 justify-end text-right select-none"
                                            >
                                                <div className="flex-1 min-w-0">
                                                    <div className={`text-xs font-bold leading-snug line-clamp-2 ${
                                                        isActive ? 'text-red-400' : 'text-slate-300 group-hover:text-slate-100'
                                                    }`}>
                                                        {video.title}
                                                    </div>
                                                </div>
                                                
                                                {/* Mini Thumbnail */}
                                                <div className="relative w-16 aspect-video bg-slate-900 border border-slate-800 rounded-lg overflow-hidden shrink-0">
                                                    <img src={video.thumbnail} alt="" className="w-full h-full object-cover" />
                                                    {isActive && (
                                                        <div className="absolute inset-0 bg-red-950/50 flex items-center justify-center">
                                                            <Play className="h-4 w-4 text-red-500 fill-current" />
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
