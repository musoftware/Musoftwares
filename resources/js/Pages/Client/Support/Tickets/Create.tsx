import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
    ArrowLeft, 
    ArrowRight,
    Send, 
    Sparkles, 
    FolderKanban, 
    ShieldAlert, 
    HelpCircle, 
    FileEdit, 
    DollarSign,
    Check
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

export default function Create({ projects = [], initialProjectId = null }: Props) {
    const [activePreset, setActivePreset] = useState<string | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        priority: 'Medium' as 'Low' | 'Medium' | 'High',
        description: '',
        project_id: initialProjectId ? String(initialProjectId) : '',
    });

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

    const submitTicket = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tickets.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.open_new_ticket') || 'فتح تذكرة دعم واستفسار'} — Musoftwares`} />

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
                            صف استفسارك أو طلبك التقني، وسيقوم مهندسونا بمراجعته والرد المباشر عليك فوراً مع إشعارك أولاً بأول.
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
                                    rows={8}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    required
                                    placeholder="اكتب هنا التفاصيل بوضوح لمساعدتنا في تقديم الحل أو الرد الدقيق..."
                                    className="w-full rounded-2xl bg-[#f5f5f7] dark:bg-[#1c1c1e] border border-transparent dark:border-white/5 p-4 text-xs sm:text-sm text-[#1d1d1f] dark:text-white leading-relaxed resize-none focus:bg-white dark:focus:bg-black focus:border-[#0071e3] focus:ring-4 focus:ring-[#0071e3]/10 focus:outline-none transition-all placeholder:text-[#1d1d1f]/30 dark:placeholder:text-white/30 font-normal"
                                />
                                <InputError message={errors.description} />
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
                                    disabled={processing}
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
