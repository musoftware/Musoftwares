import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import {
    X,
    LifeBuoy,
    Sparkles,
    Send,
    AlertCircle,
    CheckCircle2,
    Calculator,
    HelpCircle,
    Zap,
    FileEdit,
    ExternalLink,
    ChevronRight,
} from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';
import { Link } from '@inertiajs/react';

interface ProjectOption {
    id: number;
    name?: string;
    project_name?: string;
}

interface CreateTicketModalProps {
    isOpen: boolean;
    onClose: () => void;
    projectId?: number | null;
    projectName?: string | null;
    userProjects?: ProjectOption[];
    onTicketCreated?: (ticket: any, pointsAwarded: number) => void;
}

interface TemplateOption {
    id: string;
    label: string;
    icon: React.ComponentType<{ className?: string }>;
    subject: string;
    message: string;
    urgency: 'normal' | 'high' | 'critical';
}

const TEMPLATES: TemplateOption[] = [
    {
        id: 'pricing',
        label: 'طلب تسعير ميزة',
        icon: Calculator,
        subject: 'طلب تسعير: ميزة / إضافة جديدة',
        message: 'أرغب في الحصول على تسعير وتقدير تكلفة للميزة التالية:\n- وصف الميزة المطلوبة:\n- الهدف منها:\n- الموعد المتوقع للتسليم إن وجد:',
        urgency: 'normal',
    },
    {
        id: 'inquiry',
        label: 'استفسار مشروع',
        icon: HelpCircle,
        subject: 'استفسار بخصوص مراحل المشروع',
        message: 'لدي استفسار بخصوص سير العمل في المشروع:\n- الموضوع المطلوب توضيحه:\n- النقطة المحددة:',
        urgency: 'normal',
    },
    {
        id: 'urgent_bug',
        label: 'مشكلة عاجلة',
        icon: Zap,
        subject: 'عاجل: مشكلة تقنية تتطلب تدخلاً',
        message: 'واجهت مشكلة تقنية أثناء الاستخدام:\n- تفاصيل الخطأ:\n- خطوات تكرار المشكلة:\n- الرابط أو الصفحة المتأثرة:',
        urgency: 'critical',
    },
    {
        id: 'modification',
        label: 'طلب تعديل',
        icon: FileEdit,
        subject: 'طلب تعديل على المخرجات',
        message: 'أرجو إجراء التعديلات التالية على المشروع / الخدمة:\n- التعديل المطلوب:\n- الملاحظات الإضافية:',
        urgency: 'normal',
    },
];

export const CreateTicketModal: React.FC<CreateTicketModalProps> = ({
    isOpen,
    onClose,
    projectId = null,
    projectName = null,
    userProjects = [],
    onTicketCreated,
}) => {
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [urgency, setUrgency] = useState<'normal' | 'high' | 'critical'>('normal');
    const [selectedProjectId, setSelectedProjectId] = useState<number | null>(projectId);
    const [availableProjects, setAvailableProjects] = useState<ProjectOption[]>(userProjects);
    const [submitting, setSubmitting] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);
    const [mounted, setMounted] = useState(false);
    const [createdTicket, setCreatedTicket] = useState<{ id: number; points: number } | null>(null);
    const [activeTemplate, setActiveTemplate] = useState<string | null>(null);

    useEffect(() => {
        setMounted(true);
        return () => setMounted(false);
    }, []);

    useEffect(() => {
        if (projectId) {
            setSelectedProjectId(projectId);
        }
    }, [projectId]);

    // If outside a project and no projects were passed in props, load user's projects
    useEffect(() => {
        if (isOpen && !projectId && availableProjects.length === 0) {
            axios.get('/api/portal/projects')
                .then(res => {
                    if (res.data?.data) {
                        setAvailableProjects(res.data.data);
                    }
                })
                .catch(() => {});
        }
    }, [isOpen, projectId]);

    useEffect(() => {
        if (isOpen) {
            const originalOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            setCreatedTicket(null);
            setErrorMsg(null);
            return () => {
                document.body.style.overflow = originalOverflow;
            };
        }
    }, [isOpen]);

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && isOpen) {
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, onClose]);

    if (!isOpen || !mounted) return null;

    const handleSelectTemplate = (tpl: TemplateOption) => {
        setActiveTemplate(tpl.id);
        setSubject(tpl.subject);
        setMessage(tpl.message);
        setUrgency(tpl.urgency);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!subject.trim()) {
            setErrorMsg('يرجى كتابة عنوان التذكرة أو تحديد نموذج جاهز.');
            return;
        }
        if (!message.trim() || message.trim().length < 10) {
            setErrorMsg('يرجى تقديم تفاصيل وافية عن الطلب (10 أحرف على الأقل).');
            return;
        }

        setSubmitting(true);
        setErrorMsg(null);

        try {
            const res = await axios.post('/api/portal/tickets', {
                ticket_subject: subject.trim(),
                ticket_message: message.trim(),
                urgency,
                project_id: selectedProjectId || undefined,
            });

            if (res.data?.status === 'success') {
                const points = res.data?.data?.points_awarded || 15;
                const ticketData = res.data?.data?.ticket;
                toast.success(`تم فتح التذكرة بنجاح! +${points} نقطة ولاء أضيفت لحسابك.`);

                if (onTicketCreated) {
                    onTicketCreated(ticketData, points);
                }

                setCreatedTicket({
                    id: ticketData?.id || 0,
                    points,
                });
            }
        } catch (err: any) {
            console.error('Failed to open ticket:', err);
            const msg = err.response?.data?.message || 'تعذر فتح التذكرة حالياً، يرجى المحاولة مرة أخرى.';
            setErrorMsg(msg);
            toast.error(msg);
        } finally {
            setSubmitting(false);
        }
    };

    const handleModalClose = () => {
        setSubject('');
        setMessage('');
        setUrgency('normal');
        setActiveTemplate(null);
        setCreatedTicket(null);
        onClose();
    };

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
                onClick={handleModalClose}
            />

            {/* Modal Box */}
            <div className="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 shadow-2xl transition-all">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 px-6 py-4 bg-slate-50/70 dark:bg-zinc-900/70">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/40 text-[#0071e3] dark:text-blue-400">
                            <LifeBuoy className="h-5 w-5" />
                        </div>
                        <div>
                            <h2 className="text-sm font-bold text-slate-900 dark:text-white">
                                {projectId ? `تذكرة لمشروع: ${projectName || '#' + projectId}` : 'فتح تذكرة دعم واستفسار'}
                            </h2>
                            <p className="text-[11px] text-slate-500 dark:text-zinc-400">
                                إشعار فوري للأدمن عبر FCM ومتابعة مستمرة
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={handleModalClose}
                        className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-slate-600 transition"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* State: Ticket Created Successfully Screen */}
                {createdTicket ? (
                    <div className="p-8 text-center space-y-5">
                        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200">
                            <CheckCircle2 className="h-8 w-8" />
                        </div>

                        <div className="space-y-1">
                            <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                تم فتح التذكرة #{createdTicket.id} بنجاح!
                            </h3>
                            <p className="text-xs text-slate-600 dark:text-zinc-400">
                                تم إرسال إشعار فوري لفريق الدعم والأدمن وسيتم الرد عليك في أقرب وقت.
                            </p>
                        </div>

                        {/* Reward Card */}
                        <div className="flex items-center justify-center gap-2 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 rounded-xl text-xs text-amber-800 dark:text-amber-300 font-semibold">
                            <Sparkles className="h-4 w-4 text-amber-600" />
                            <span>تمت إضافة +{createdTicket.points} نقطة مكافأة إلى رصيدك!</span>
                        </div>

                        <div className="flex items-center justify-center gap-3 pt-2">
                            <a
                                href={`/tickets/${createdTicket.id}`}
                                className="px-5 py-2.5 text-xs font-bold text-white bg-black hover:bg-slate-800 rounded-xl transition flex items-center gap-1.5 shadow-sm"
                            >
                                <span>متابعة التذكرة الآن</span>
                                <ChevronRight className="h-3.5 w-3.5" />
                            </a>
                            <button
                                type="button"
                                onClick={handleModalClose}
                                className="px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition"
                            >
                                إغلاق
                            </button>
                        </div>
                    </div>
                ) : (
                    /* Normal Ticket Form */
                    <form onSubmit={handleSubmit} className="p-6 space-y-4">
                        {/* Loyalty Points Incentive Banner */}
                        <div className="flex items-start gap-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/60 p-3">
                            <Sparkles className="h-4 w-4 text-[#0071e3] shrink-0 mt-0.5" />
                            <div className="text-xs text-blue-900 dark:text-blue-200 leading-relaxed">
                                <strong>اربح +15 نقطة ولاء</strong> فور فتح التذكرة، و<strong>+25 نقطة إضافية</strong> عند سرعة حل وإغلاق الطلب!
                            </div>
                        </div>

                        {errorMsg && (
                            <div className="flex items-center gap-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 text-rose-700 dark:text-rose-300 p-3 text-xs">
                                <AlertCircle className="h-4 w-4 shrink-0" />
                                <span>{errorMsg}</span>
                            </div>
                        )}

                        {/* Quick-Start Templates (Chips) */}
                        <div className="space-y-1.5">
                            <label className="block text-[11px] font-semibold text-slate-600 dark:text-zinc-400">
                                قوالب جاهزة سريعة:
                            </label>
                            <div className="flex flex-wrap gap-1.5">
                                {TEMPLATES.map((tpl) => {
                                    const Icon = tpl.icon;
                                    const isSelected = activeTemplate === tpl.id;
                                    return (
                                        <button
                                            key={tpl.id}
                                            type="button"
                                            onClick={() => handleSelectTemplate(tpl)}
                                            className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border transition cursor-pointer ${
                                                isSelected
                                                    ? 'bg-blue-50 dark:bg-blue-950/50 text-[#0071e3] border-blue-300 dark:border-blue-800'
                                                    : 'bg-slate-50 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border-slate-200 dark:border-zinc-700 hover:bg-slate-100'
                                            }`}
                                        >
                                            <Icon className="h-3 w-3" />
                                            <span>{tpl.label}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Project Selector (if not locked to a specific project) */}
                        {!projectId && availableProjects.length > 0 && (
                            <div className="space-y-1.5">
                                <label className="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                    ربط بالمشروع (اختياري)
                                </label>
                                <select
                                    value={selectedProjectId || ''}
                                    onChange={(e) => setSelectedProjectId(e.target.value ? Number(e.target.value) : null)}
                                    className="w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-[#0071e3] focus:outline-hidden"
                                >
                                    <option value="">دعم فني واستفسار عام (بدون مشروع)</option>
                                    {availableProjects.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name || p.project_name || `مشروع #${p.id}`}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Subject */}
                        <div className="space-y-1.5">
                            <label className="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                عنوان التذكرة <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                                placeholder="مثال: طلب تسعير ميزة جديدة أو استفسار عن الفاتورة"
                                className="w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-[#0071e3] focus:outline-hidden"
                                required
                            />
                        </div>

                        {/* Urgency */}
                        <div className="space-y-1.5">
                            <label className="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                درجة الأهمية
                            </label>
                            <div className="grid grid-cols-3 gap-2">
                                {(
                                    [
                                        { level: 'normal', label: 'عادية (Normal)' },
                                        { level: 'high', label: 'أولوية (High)' },
                                        { level: 'critical', label: 'عاجلة جداً (Critical)' },
                                    ] as const
                                ).map(({ level, label }) => (
                                    <button
                                        key={level}
                                        type="button"
                                        onClick={() => setUrgency(level)}
                                        className={`py-1.5 px-2 text-xs font-semibold rounded-xl border transition ${
                                            urgency === level
                                                ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-transparent shadow-xs'
                                                : 'bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border-slate-200 dark:border-zinc-700 hover:bg-slate-50'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Message */}
                        <div className="space-y-1.5">
                            <label className="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                تفاصيل الطلب / الاستفسار <span className="text-rose-500">*</span>
                            </label>
                            <textarea
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                rows={4}
                                placeholder="اكتب هنا التفاصيل بوضوح لمساعدتنا في تقديم الحل أو التسعير الدقيق..."
                                className="w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-[#0071e3] focus:outline-hidden resize-none leading-relaxed"
                                required
                            />
                        </div>

                        {/* Submit Footer */}
                        <div className="pt-2 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-zinc-800">
                            <button
                                type="button"
                                onClick={handleModalClose}
                                className="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition"
                            >
                                إلغاء
                            </button>
                            <button
                                type="submit"
                                disabled={submitting}
                                className="px-5 py-2 text-xs font-bold text-white bg-black hover:bg-slate-800 rounded-xl transition flex items-center gap-1.5 shadow-sm disabled:opacity-50 cursor-pointer"
                            >
                                <Send className="h-3.5 w-3.5" />
                                <span>{submitting ? 'جاري الإرسال...' : 'إرسال التذكرة (+15 PTS)'}</span>
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>,
        document.body
    );
};
