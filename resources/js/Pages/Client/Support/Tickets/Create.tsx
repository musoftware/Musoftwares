import React, { useState, useRef, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Send, 
    Sparkles, 
    FolderKanban, 
    ShieldAlert, 
    HelpCircle, 
    FileEdit, 
    DollarSign,
    Check,
    Image as ImageIcon,
    Mic,
    Music,
    Video,
    Square,
    Trash2,
    AlertCircle,
    X,
    Radio
} from 'lucide-react';
import InputError from '@/Components/InputError';
import { __ } from '@/lib/i18n';

interface ProjectOption {
    id: number;
    project_name: string;
}

interface Props {
    projects?: ProjectOption[];
    initialProjectId?: number | null;
}

interface PresetOption {
    id: string;
    title: string;
    icon: React.ComponentType<{ className?: string }>;
    priority: 'Low' | 'Medium' | 'High';
    defaultSubject: string;
    defaultDescription: string;
}

const PRESETS: PresetOption[] = [
    {
        id: 'quote',
        title: 'طلب تسعير ميزة',
        icon: DollarSign,
        priority: 'Medium',
        defaultSubject: 'طلب تسعير ميزة جديدة: ',
        defaultDescription: 'أرغب في الاستفسار عن إمكانية وتكلفة تنفيذ الميزة التالية:\n\n1. تفاصيل الميزة المطلوبة:\n\n2. المخرجات المتوقعة:\n\n3. الموعد المفضل للتسليم (إن وجد):\n',
    },
    {
        id: 'project_inquiry',
        title: 'استفسار مشروع',
        icon: HelpCircle,
        priority: 'Medium',
        defaultSubject: 'استفسار بخصوص المشروع: ',
        defaultDescription: 'السلام عليكم، لدي استفسار بخصوص سير العمل في المشروع:\n\n- البند المطلوب توضيحه:\n\n- الاستفسار:\n',
    },
    {
        id: 'urgent',
        title: 'مشكلة فنية عاجلة',
        icon: ShieldAlert,
        priority: 'High',
        defaultSubject: 'مشكلة عاجلة: ',
        defaultDescription: 'نواجه مشكلة تتطلب فحصاً فورياً:\n\n1. وصف المشكلة:\n\n2. الخطوات المؤدية لحدوثها:\n\n3. الأثر الحالي:\n',
    },
    {
        id: 'modification',
        title: 'طلب تعديل أو تحسين',
        icon: FileEdit,
        priority: 'Low',
        defaultSubject: 'طلب تعديل على: ',
        defaultDescription: 'أود طلب تعديل على الجزء التالي:\n\n- الجزء المطلوب تعديله:\n\n- التعديل المقترح:\n\n- الهدف من التعديل:\n',
    },
];

// Max video size allowed in bytes (3MB)
const MAX_VIDEO_SIZE = 3 * 1024 * 1024;
// Max recording time in seconds (3 minutes)
const MAX_RECORDING_SECONDS = 180;

export default function Create({ projects = [], initialProjectId = null }: Props) {
    const [activePreset, setActivePreset] = useState<string | null>(null);

    // Media & Audio Recording States
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [mediaType, setMediaType] = useState<'image' | 'audio' | 'video' | null>(null);
    const [attachmentError, setAttachmentError] = useState<string | null>(null);
    const [isRecording, setIsRecording] = useState(false);
    const [recordingDuration, setRecordingDuration] = useState(0);

    // File Input Refs
    const imageInputRef = useRef<HTMLInputElement | null>(null);
    const audioInputRef = useRef<HTMLInputElement | null>(null);
    const videoInputRef = useRef<HTMLInputElement | null>(null);

    // Media Recorder Refs
    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const audioStreamRef = useRef<MediaStream | null>(null);
    const audioChunksRef = useRef<Blob[]>([]);
    const timerIntervalRef = useRef<any>(null);

    const { data, setData, post, processing, errors } = useForm<{
        subject: string;
        priority: 'Low' | 'Medium' | 'High';
        description: string;
        project_id: string;
        attachment: File | null;
    }>({
        subject: '',
        priority: 'Medium',
        description: '',
        project_id: initialProjectId ? String(initialProjectId) : '',
        attachment: null,
    });

    // Clean up preview object URL on unmount
    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            if (timerIntervalRef.current) {
                clearInterval(timerIntervalRef.current);
            }
            if (audioStreamRef.current) {
                audioStreamRef.current.getTracks().forEach((track) => track.stop());
            }
        };
    }, [previewUrl]);

    // Handle Quick Preset Click
    const applyPreset = (preset: PresetOption) => {
        if (activePreset === preset.id) {
            setActivePreset(null);
            return;
        }

        setActivePreset(preset.id);
        setData((prev) => ({
            ...prev,
            subject: prev.subject && prev.subject.trim() !== '' ? prev.subject : preset.defaultSubject,
            priority: preset.priority,
            description: prev.description && prev.description.trim() !== '' ? prev.description : preset.defaultDescription,
        }));
    };

    // Remove current attachment
    const removeAttachment = () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        setPreviewUrl(null);
        setMediaType(null);
        setAttachmentError(null);
        setData('attachment', null);

        if (imageInputRef.current) imageInputRef.current.value = '';
        if (audioInputRef.current) audioInputRef.current.value = '';
        if (videoInputRef.current) videoInputRef.current.value = '';
    };

    // 1. Handle Image Selection
    const handleImageSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        removeAttachment();
        setAttachmentError(null);
        setMediaType('image');
        setPreviewUrl(URL.createObjectURL(file));
        setData('attachment', file);
    };

    // 2. Handle Audio (MP3) Upload
    const handleAudioSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        removeAttachment();
        setAttachmentError(null);
        setMediaType('audio');
        setPreviewUrl(URL.createObjectURL(file));
        setData('attachment', file);
    };

    // 3. Handle Short Video Selection (strictly <= 3MB)
    const handleVideoSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > MAX_VIDEO_SIZE) {
            setAttachmentError(
                `حجم الفيديو (${(file.size / 1024 / 1024).toFixed(1)}MB) يتجاوز الحد الأقصى المسموح وهو 3 ميجابايت (3MB). يرجى اختيار مقطع أقصر أو ضغطه.`
            );
            if (videoInputRef.current) videoInputRef.current.value = '';
            return;
        }

        removeAttachment();
        setAttachmentError(null);
        setMediaType('video');
        setPreviewUrl(URL.createObjectURL(file));
        setData('attachment', file);
    };

    // 4. Start Live Voice Recording
    const startVoiceRecording = async () => {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setAttachmentError('المتصفح الحالي لا يدعم تسجيل الصوت المباشر.');
                return;
            }

            removeAttachment();
            setAttachmentError(null);

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioStreamRef.current = stream;
            audioChunksRef.current = [];

            const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                ? 'audio/webm;codecs=opus'
                : MediaRecorder.isTypeSupported('audio/webm')
                ? 'audio/webm'
                : MediaRecorder.isTypeSupported('audio/mp4')
                ? 'audio/mp4'
                : '';

            const recorder = mimeType 
                ? new MediaRecorder(stream, { mimeType }) 
                : new MediaRecorder(stream);

            recorder.ondataavailable = (e) => {
                if (e.data && e.data.size > 0) {
                    audioChunksRef.current.push(e.data);
                }
            };

            recorder.onstop = () => {
                const audioBlob = new Blob(audioChunksRef.current, { 
                    type: mimeType || 'audio/webm' 
                });
                const audioFile = new File(
                    [audioBlob], 
                    `voice-note-${Date.now()}.webm`, 
                    { type: audioBlob.type || 'audio/webm' }
                );

                setMediaType('audio');
                setPreviewUrl(URL.createObjectURL(audioBlob));
                setData('attachment', audioFile);

                // Stop media tracks
                if (audioStreamRef.current) {
                    audioStreamRef.current.getTracks().forEach((track) => track.stop());
                    audioStreamRef.current = null;
                }
            };

            mediaRecorderRef.current = recorder;
            recorder.start(250); // collect 250ms chunks
            setIsRecording(true);
            setRecordingDuration(0);

            // Timer counter
            timerIntervalRef.current = setInterval(() => {
                setRecordingDuration((prev) => {
                    if (prev >= MAX_RECORDING_SECONDS) {
                        stopVoiceRecording();
                        return prev;
                    }
                    return prev + 1;
                });
            }, 1000);

        } catch (err: any) {
            console.error('Microphone access denied:', err);
            setAttachmentError('تعذر الوصول إلى الميكروفون. يرجى التأكد من منح الإذن للمتصفح.');
        }
    };

    // Stop and save recording
    const stopVoiceRecording = () => {
        if (timerIntervalRef.current) {
            clearInterval(timerIntervalRef.current);
            timerIntervalRef.current = null;
        }

        if (mediaRecorderRef.current && mediaRecorderRef.current.state !== 'inactive') {
            mediaRecorderRef.current.stop();
        }

        setIsRecording(false);
    };

    // Cancel voice recording without saving
    const cancelVoiceRecording = () => {
        if (timerIntervalRef.current) {
            clearInterval(timerIntervalRef.current);
            timerIntervalRef.current = null;
        }

        if (mediaRecorderRef.current && mediaRecorderRef.current.state !== 'inactive') {
            mediaRecorderRef.current.stop();
        }

        if (audioStreamRef.current) {
            audioStreamRef.current.getTracks().forEach((track) => track.stop());
            audioStreamRef.current = null;
        }

        audioChunksRef.current = [];
        setIsRecording(false);
        setRecordingDuration(0);
    };

    // Format recording seconds to mm:ss
    const formatTime = (seconds: number) => {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    };

    const submitTicket = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tickets.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.open_new_ticket') || 'فتح تذكرة دعم واستفسار'} — Musoftwares`} />

            {/* Hidden Native File Inputs */}
            <input
                ref={imageInputRef}
                type="file"
                accept="image/*"
                onChange={handleImageSelect}
                className="hidden"
            />
            <input
                ref={audioInputRef}
                type="file"
                accept="audio/*,.mp3,.wav,.ogg,.m4a"
                onChange={handleAudioSelect}
                className="hidden"
            />
            <input
                ref={videoInputRef}
                type="file"
                accept="video/*,.mp4,.webm,.mov"
                onChange={handleVideoSelect}
                className="hidden"
            />

            <div className="min-h-screen bg-[#fbfbfd] dark:bg-[#000000] text-[#1d1d1f] dark:text-[#f5f5f7] font-sans antialiased pb-24">
                
                {/* Apple-style Top Bar Navigation */}
                <header className="sticky top-0 z-30 w-full backdrop-blur-xl bg-white/70 dark:bg-black/70 border-b border-black/5 dark:border-white/10 transition-colors">
                    <div className="max-w-4xl mx-auto px-6 h-16 flex items-center justify-between">
                        <Link
                            href={route('tickets.index')}
                            className="inline-flex items-center gap-2 text-xs font-semibold text-[#1d1d1f]/70 dark:text-white/70 hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 rtl:rotate-180" />
                            <span>{__('general.back_to_tickets') || 'العودة لتذاكر الدعم'}</span>
                        </Link>

                        {/* Discreet Loyalty Incentive Badge */}
                        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-black/[0.03] dark:bg-white/[0.06] border border-black/5 dark:border-white/10 text-xs font-medium">
                            <Sparkles className="w-3.5 h-3.5 text-[#0071e3] dark:text-[#2997ff]" />
                            <span className="text-[#1d1d1f] dark:text-white font-semibold">+15 نقطة ولاء</span>
                            <span className="text-black/30 dark:text-white/30">|</span>
                            <span className="text-black/60 dark:text-white/60">+25 عند الإغلاق</span>
                        </div>
                    </div>
                </header>

                {/* Main Content Container with Breathable Apple Spacing */}
                <main className="max-w-3xl mx-auto px-6 pt-12 sm:pt-16">
                    
                    {/* Header Section */}
                    <div className="text-start mb-10">
                        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-[#1d1d1f] dark:text-white">
                            {__('general.open_new_ticket') || 'فتح تذكرة دعم واستفسار'}
                        </h1>
                        <p className="mt-3 text-base text-[#1d1d1f]/60 dark:text-white/60 leading-relaxed max-w-xl">
                            صف استفسارك أو طلبك بدقة أدناه، ويمكنك إرفاق صور، تسجيل صوتك لشرح التفاصيل، أو إرفاق فيديو توضيحي قصير (3MB).
                        </p>
                    </div>

                    {/* Presets - Apple Segmented Chips */}
                    <div className="mb-10">
                        <div className="text-[11px] font-semibold uppercase tracking-wider text-[#1d1d1f]/40 dark:text-white/40 mb-3 px-1">
                            قوالب جاهزة لتسريع طلبك
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            {PRESETS.map((preset) => {
                                const Icon = preset.icon;
                                const isSelected = activePreset === preset.id;
                                return (
                                    <button
                                        type="button"
                                        key={preset.id}
                                        onClick={() => applyPreset(preset)}
                                        className={`group p-4 rounded-2xl border text-start transition-all cursor-pointer flex flex-col justify-between min-h-[90px] ${
                                            isSelected
                                                ? 'bg-[#1d1d1f] dark:bg-white text-white dark:text-black border-transparent shadow-lg shadow-black/10'
                                                : 'bg-white dark:bg-[#121214] text-[#1d1d1f] dark:text-white border-black/5 dark:border-white/10 hover:border-black/20 dark:hover:border-white/20 hover:shadow-xs'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between w-full">
                                            <div className={`w-8 h-8 rounded-xl flex items-center justify-center transition-colors ${
                                                isSelected 
                                                    ? 'bg-white/20 dark:bg-black/10 text-white dark:text-black' 
                                                    : 'bg-black/[0.03] dark:bg-white/[0.05] text-[#1d1d1f] dark:text-white'
                                            }`}>
                                                <Icon className="w-4 h-4" />
                                            </div>
                                            {isSelected && (
                                                <Check className="w-4 h-4 text-[#0071e3] dark:text-[#0071e3]" />
                                            )}
                                        </div>
                                        <div className="mt-3 text-xs font-bold leading-snug">
                                            {preset.title}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Clean Apple Form Card */}
                    <div className="bg-white dark:bg-[#121214] border border-black/5 dark:border-white/10 rounded-[28px] p-8 sm:p-12 shadow-sm">
                        <form onSubmit={submitTicket} className="space-y-8">
                            
                            {/* Project Association (Optional) */}
                            {projects.length > 0 && (
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <label htmlFor="project_id" className="text-xs font-semibold text-[#1d1d1f] dark:text-white flex items-center gap-1.5">
                                            <FolderKanban className="w-3.5 h-3.5 text-[#0071e3] dark:text-[#2997ff]" />
                                            <span>ربط التذكرة بمشروع (اختياري)</span>
                                        </label>
                                        {data.project_id && (
                                            <button
                                                type="button"
                                                onClick={() => setData('project_id', '')}
                                                className="text-[11px] text-[#0071e3] dark:text-[#2997ff] hover:underline cursor-pointer"
                                            >
                                                إلغاء الربط (تذكرة عامة)
                                            </button>
                                        )}
                                    </div>
                                    <select
                                        id="project_id"
                                        value={data.project_id}
                                        onChange={(e) => setData('project_id', e.target.value)}
                                        className="h-12 w-full rounded-xl bg-[#f5f5f7] dark:bg-[#1c1c1e] border border-transparent dark:border-white/5 px-4 text-xs sm:text-sm text-[#1d1d1f] dark:text-white font-medium focus:bg-white dark:focus:bg-black focus:border-[#0071e3] focus:ring-4 focus:ring-[#0071e3]/10 focus:outline-none transition-all"
                                    >
                                        <option value="">دعم فني واستفسار عام (بدون مشروع محدد)</option>
                                        {projects.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                مشروع: {p.project_name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.project_id} />
                                </div>
                            )}

                            {/* Subject Field */}
                            <div className="space-y-2">
                                <label htmlFor="subject" className="text-xs font-semibold text-[#1d1d1f] dark:text-white block">
                                    {__('general.subject') || 'عنوان التذكرة'} <span className="text-[#0071e3]">*</span>
                                </label>
                                <input
                                    id="subject"
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    required
                                    placeholder="مثال: استفسار عن خطة التسليم أو طلب تسعير ميزة"
                                    className="h-12 w-full rounded-xl bg-[#f5f5f7] dark:bg-[#1c1c1e] border border-transparent dark:border-white/5 px-4 text-xs sm:text-sm text-[#1d1d1f] dark:text-white font-semibold focus:bg-white dark:focus:bg-black focus:border-[#0071e3] focus:ring-4 focus:ring-[#0071e3]/10 focus:outline-none transition-all placeholder:text-[#1d1d1f]/30 dark:placeholder:text-white/30"
                                />
                                <InputError message={errors.subject} />
                            </div>

                            {/* Priority - Apple Native Segmented Control */}
                            <div className="space-y-2">
                                <label className="text-xs font-semibold text-[#1d1d1f] dark:text-white block">
                                    {__('general.priority') || 'درجة الأهمية'}
                                </label>
                                <div className="grid grid-cols-3 gap-1.5 bg-[#f5f5f7] dark:bg-[#1c1c1e] p-1.5 rounded-2xl">
                                    <button
                                        type="button"
                                        onClick={() => setData('priority', 'Low')}
                                        className={`py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer ${
                                            data.priority === 'Low'
                                                ? 'bg-white dark:bg-[#2c2c2e] text-[#1d1d1f] dark:text-white shadow-xs font-bold'
                                                : 'text-[#1d1d1f]/60 dark:text-white/60 hover:text-[#1d1d1f] dark:hover:text-white'
                                        }`}
                                    >
                                        عادية (Normal)
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setData('priority', 'Medium')}
                                        className={`py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer ${
                                            data.priority === 'Medium'
                                                ? 'bg-white dark:bg-[#2c2c2e] text-[#1d1d1f] dark:text-white shadow-xs font-bold'
                                                : 'text-[#1d1d1f]/60 dark:text-white/60 hover:text-[#1d1d1f] dark:hover:text-white'
                                        }`}
                                    >
                                        أولوية (High)
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setData('priority', 'High')}
                                        className={`py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer ${
                                            data.priority === 'High'
                                                ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 shadow-xs font-bold'
                                                : 'text-[#1d1d1f]/60 dark:text-white/60 hover:text-[#1d1d1f] dark:hover:text-white'
                                        }`}
                                    >
                                        عاجلة جداً (Critical)
                                    </button>
                                </div>
                                <InputError message={errors.priority} />
                            </div>

                            {/* Description Field */}
                            <div className="space-y-2">
                                <label htmlFor="description" className="text-xs font-semibold text-[#1d1d1f] dark:text-white block">
                                    {__('general.description') || 'تفاصيل الطلب أو الاستفسار'} <span className="text-[#0071e3]">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    rows={7}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    required
                                    placeholder="اكتب هنا التفاصيل بوضوح لمساعدتنا في تقديم الحل أو الرد الدقيق..."
                                    className="w-full rounded-2xl bg-[#f5f5f7] dark:bg-[#1c1c1e] border border-transparent dark:border-white/5 p-4 text-xs sm:text-sm text-[#1d1d1f] dark:text-white leading-relaxed resize-none focus:bg-white dark:focus:bg-black focus:border-[#0071e3] focus:ring-4 focus:ring-[#0071e3]/10 focus:outline-none transition-all placeholder:text-[#1d1d1f]/30 dark:placeholder:text-white/30 font-normal"
                                />
                                <InputError message={errors.description} />
                            </div>

                            {/* Apple-Style Media & Attachment Toolbar */}
                            <div className="space-y-3 pt-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-[#1d1d1f] dark:text-white">
                                        إرفاق وسائط وتوضيحات (اختياري)
                                    </span>
                                    <span className="text-[11px] text-[#1d1d1f]/50 dark:text-white/50">
                                        صورة · تسجيل صوتي · ملف MP3 · فيديو قصير (3MB)
                                    </span>
                                </div>

                                {/* Media Attachment Action Chips */}
                                {!isRecording && !data.attachment && (
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                        
                                        {/* 1. Upload Image */}
                                        <button
                                            type="button"
                                            onClick={() => imageInputRef.current?.click()}
                                            className="flex items-center justify-center gap-2 p-3 rounded-xl border border-black/5 dark:border-white/10 bg-[#f5f5f7] dark:bg-[#1c1c1e] hover:bg-black/[0.06] dark:hover:bg-white/[0.08] text-[#1d1d1f] dark:text-white text-xs font-semibold transition-all cursor-pointer shadow-2xs"
                                        >
                                            <ImageIcon className="w-4 h-4 text-[#0071e3]" />
                                            <span>رفع صورة</span>
                                        </button>

                                        {/* 2. Record Live Voice Note */}
                                        <button
                                            type="button"
                                            onClick={startVoiceRecording}
                                            className="flex items-center justify-center gap-2 p-3 rounded-xl border border-rose-500/20 bg-rose-50/50 dark:bg-rose-950/20 hover:bg-rose-100/60 dark:hover:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-semibold transition-all cursor-pointer shadow-2xs"
                                        >
                                            <Mic className="w-4 h-4" />
                                            <span>تسجيل صوتي</span>
                                        </button>

                                        {/* 3. Upload Audio File (MP3) */}
                                        <button
                                            type="button"
                                            onClick={() => audioInputRef.current?.click()}
                                            className="flex items-center justify-center gap-2 p-3 rounded-xl border border-black/5 dark:border-white/10 bg-[#f5f5f7] dark:bg-[#1c1c1e] hover:bg-black/[0.06] dark:hover:bg-white/[0.08] text-[#1d1d1f] dark:text-white text-xs font-semibold transition-all cursor-pointer shadow-2xs"
                                        >
                                            <Music className="w-4 h-4 text-purple-600" />
                                            <span>ملف صوتي MP3</span>
                                        </button>

                                        {/* 4. Upload Short Video (3M) */}
                                        <button
                                            type="button"
                                            onClick={() => videoInputRef.current?.click()}
                                            className="flex items-center justify-center gap-2 p-3 rounded-xl border border-black/5 dark:border-white/10 bg-[#f5f5f7] dark:bg-[#1c1c1e] hover:bg-black/[0.06] dark:hover:bg-white/[0.08] text-[#1d1d1f] dark:text-white text-xs font-semibold transition-all cursor-pointer shadow-2xs"
                                        >
                                            <Video className="w-4 h-4 text-emerald-600" />
                                            <span>فيديو قصير (≤ 3MB)</span>
                                        </button>

                                    </div>
                                )}

                                {/* Live Voice Recording HUD */}
                                {isRecording && (
                                    <div className="p-4 rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/70 dark:bg-rose-950/30 flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in duration-200">
                                        <div className="flex items-center gap-3">
                                            <div className="relative flex items-center justify-center">
                                                <span className="w-3.5 h-3.5 rounded-full bg-rose-600 animate-ping absolute" />
                                                <span className="w-3 h-3 rounded-full bg-rose-600 relative" />
                                            </div>
                                            <div>
                                                <div className="text-xs font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                                                    <span>جاري تسجيل الصوت المباشر...</span>
                                                    <span className="font-mono text-sm px-2 py-0.5 rounded-md bg-white/80 dark:bg-black/40 text-rose-600 dark:text-rose-400">
                                                        {formatTime(recordingDuration)} / {formatTime(MAX_RECORDING_SECONDS)}
                                                    </span>
                                                </div>
                                                <span className="text-[11px] text-rose-700/70 dark:text-rose-300/70">
                                                    تحدث بوضوح، اضغط على إنهاء وحفظ عند الانتهاء
                                                </span>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
                                            <button
                                                type="button"
                                                onClick={cancelVoiceRecording}
                                                className="px-3.5 py-2 rounded-xl bg-white dark:bg-black/30 hover:bg-rose-100 text-xs font-semibold text-rose-700 dark:text-rose-300 transition cursor-pointer"
                                            >
                                                إلغاء
                                            </button>
                                            <button
                                                type="button"
                                                onClick={stopVoiceRecording}
                                                className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-bold text-white transition flex items-center gap-1.5 shadow-sm cursor-pointer"
                                            >
                                                <Square className="w-3.5 h-3.5 fill-white" />
                                                <span>إنهاء وحفظ</span>
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {/* Error Notification Banner */}
                                {attachmentError && (
                                    <div className="p-3.5 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 text-xs flex items-center justify-between gap-3">
                                        <div className="flex items-center gap-2">
                                            <AlertCircle className="w-4 h-4 shrink-0" />
                                            <span>{attachmentError}</span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setAttachmentError(null)}
                                            className="text-rose-500 hover:text-rose-700 cursor-pointer"
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                )}
                                <InputError message={errors.attachment} />

                                {/* Attached Media Preview Capsule */}
                                {data.attachment && previewUrl && (
                                    <div className="p-4 rounded-2xl border border-black/5 dark:border-white/10 bg-[#f5f5f7] dark:bg-[#1c1c1e] space-y-3">
                                        
                                        {/* Header info bar */}
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2 text-xs font-bold text-[#1d1d1f] dark:text-white">
                                                {mediaType === 'image' && <ImageIcon className="w-4 h-4 text-[#0071e3]" />}
                                                {mediaType === 'audio' && <Music className="w-4 h-4 text-purple-600" />}
                                                {mediaType === 'video' && <Video className="w-4 h-4 text-emerald-600" />}
                                                <span className="truncate max-w-[200px] sm:max-w-md">
                                                    {data.attachment.name}
                                                </span>
                                                <span className="text-[10px] font-mono px-2 py-0.5 rounded-full bg-black/5 dark:bg-white/10 text-black/60 dark:text-white/60">
                                                    {(data.attachment.size / 1024 / 1024).toFixed(2)} MB
                                                </span>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={removeAttachment}
                                                className="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 flex items-center gap-1 font-semibold cursor-pointer"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                                <span>إزالة المرفق</span>
                                            </button>
                                        </div>

                                        {/* Specific Preview Player */}
                                        {mediaType === 'image' && (
                                            <div className="rounded-xl overflow-hidden border border-black/5 dark:border-white/5 bg-black/5 dark:bg-black">
                                                <img
                                                    src={previewUrl}
                                                    alt="Attached preview"
                                                    className="max-h-60 w-auto object-contain mx-auto rounded-lg"
                                                />
                                            </div>
                                        )}

                                        {mediaType === 'audio' && (
                                            <div className="p-2 rounded-xl bg-white dark:bg-black/40 border border-black/5 dark:border-white/5">
                                                <audio
                                                    controls
                                                    src={previewUrl}
                                                    className="w-full h-10"
                                                />
                                            </div>
                                        )}

                                        {mediaType === 'video' && (
                                            <div className="rounded-xl overflow-hidden border border-black/5 dark:border-white/5 bg-black">
                                                <video
                                                    controls
                                                    playsInline
                                                    src={previewUrl}
                                                    className="max-h-64 w-auto mx-auto rounded-lg"
                                                />
                                            </div>
                                        )}

                                    </div>
                                )}

                            </div>

                            {/* Action Buttons */}
                            <div className="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-black/5 dark:border-white/10">
                                <Link
                                    href={route('tickets.index')}
                                    className="text-xs font-semibold text-[#1d1d1f]/60 dark:text-white/60 hover:text-[#1d1d1f] dark:hover:text-white transition-colors order-2 sm:order-1"
                                >
                                    إلغاء والعودة
                                </Link>

                                <button
                                    type="submit"
                                    disabled={processing || isRecording}
                                    className="w-full sm:w-auto px-8 py-3.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-bold rounded-full transition-all flex items-center justify-center gap-2 shadow-sm shadow-blue-500/25 disabled:opacity-50 cursor-pointer order-1 sm:order-2"
                                >
                                    <Send className="w-3.5 h-3.5 rtl:rotate-180" />
                                    <span>{processing ? 'جاري الإرسال...' : 'إرسال التذكرة (+15 PTS)'}</span>
                                </button>
                            </div>

                        </form>
                    </div>

                </main>

            </div>
        </AuthenticatedLayout>
    );
}
