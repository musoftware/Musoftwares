import React, { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    ExternalLink,
    CheckCircle2,
    Copy,
    AlertTriangle,
    ShieldCheck,
    Smartphone,
    Key,
    Layers,
    ArrowRight,
    ArrowLeft,
    Sparkles,
    HelpCircle,
    Info,
    Check,
} from 'lucide-react';

interface MetaSetupGuideModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export const MetaSetupGuideModal: React.FC<MetaSetupGuideModalProps> = ({
    isOpen,
    onClose,
}) => {
    const [currentStep, setCurrentStep] = useState<number>(1);
    const [copiedField, setCopiedField] = useState<string | null>(null);

    const copyToClipboard = (text: string, label: string) => {
        navigator.clipboard.writeText(text);
        setCopiedField(label);
        setTimeout(() => setCopiedField(null), 2000);
    };

    const steps = [
        {
            id: 1,
            title: 'تطبيقات Meta',
            badge: 'الخطوة 1',
            icon: Layers,
        },
        {
            id: 2,
            title: 'رقم الهاتف والمُعرّفات',
            badge: 'الخطوة 2',
            icon: Smartphone,
        },
        {
            id: 3,
            title: 'التوكن الدائم (Token)',
            badge: 'الخطوة 3',
            icon: Key,
        },
        {
            id: 4,
            title: 'ربط الحساب بالنظام',
            badge: 'الخطوة 4',
            icon: ShieldCheck,
        },
        {
            id: 5,
            title: 'الـ Webhooks والاختبار',
            badge: 'الخطوة 5',
            icon: Sparkles,
        },
    ];

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto bg-slate-950 text-slate-100 border-slate-800 p-0 sm:rounded-2xl dir-rtl">
                {/* Header Section */}
                <div className="bg-gradient-to-r from-emerald-950 via-slate-900 to-slate-950 border-b border-slate-800/80 p-6 relative overflow-hidden">
                    <div className="absolute -top-12 -left-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
                    
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400">
                                <HelpCircle className="w-6 h-6" />
                            </div>
                            <div>
                                <DialogTitle className="text-xl font-bold text-white flex items-center gap-2">
                                    دليل ربط واتساب سحابي (Meta Cloud API)
                                    <Badge className="bg-emerald-500/20 text-emerald-300 border-emerald-500/30 text-xs font-normal">
                                        شرح خطوة بخطوة
                                    </Badge>
                                </DialogTitle>
                                <DialogDescription className="text-slate-400 text-sm mt-1">
                                    تعلم كيفية إنشاء حساب Meta Developer، استخراج التوكن الدائم، وربط رقمك بالسيستم في 5 خطوات بسيطة.
                                </DialogDescription>
                            </div>
                        </div>
                    </div>

                    {/* Stepper Tabs Bar */}
                    <div className="grid grid-cols-5 gap-2 mt-6 pt-4 border-t border-slate-800/60">
                        {steps.map((step) => {
                            const Icon = step.icon;
                            const isActive = currentStep === step.id;
                            const isDone = currentStep > step.id;

                            return (
                                <button
                                    key={step.id}
                                    onClick={() => setCurrentStep(step.id)}
                                    className={`flex flex-col items-center gap-1.5 p-2.5 rounded-xl transition-all text-center border text-xs font-medium ${
                                        isActive
                                            ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-300 shadow-lg shadow-emerald-950/50'
                                            : isDone
                                            ? 'bg-slate-900/80 border-slate-800 text-slate-300 hover:bg-slate-900'
                                            : 'bg-slate-950/40 border-slate-900 text-slate-500 hover:bg-slate-900/50'
                                    }`}
                                >
                                    <div className="flex items-center justify-center gap-1">
                                        {isDone ? (
                                            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
                                        ) : (
                                            <Icon className={`w-3.5 h-3.5 ${isActive ? 'text-emerald-400' : 'text-slate-400'}`} />
                                        )}
                                        <span className="font-semibold">{step.badge}</span>
                                    </div>
                                    <span className="truncate w-full text-[11px] font-normal">{step.title}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Body Content */}
                <div className="p-6 space-y-6">
                    {/* STEP 1: META APP */}
                    {currentStep === 1 && (
                        <div className="space-y-4 animate-in fade-in duration-300">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-white flex items-center gap-2">
                                        <Layers className="w-5 h-5 text-emerald-400" />
                                        الخطوة الأولى: إنشاء تطبيق الأعمال في Meta for Developers
                                    </h3>
                                    <p className="text-sm text-slate-400 mt-1">
                                        يتطلب استخدام WhatsApp Cloud API فتح حساب مطور وإنشاء تطبيق مخصص للأعمال.
                                    </p>
                                </div>
                                <a
                                    href="https://developers.facebook.com/apps/"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shrink-0"
                                >
                                    <ExternalLink className="w-4 h-4" />
                                    فتح Meta for Developers
                                </a>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-3">
                                    <span className="inline-block px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-md text-xs font-bold">
                                        1. تسجيل الدخول وإنشاء التطبيق
                                    </span>
                                    <ul className="text-xs text-slate-300 space-y-2 list-disc list-inside leading-relaxed">
                                        <li>انتقل إلى منصة <strong className="text-white">developers.facebook.com</strong> وسجل الدخول بحساب Facebook.</li>
                                        <li>اضغط على زر <strong className="text-emerald-400">My Apps (تطبيقاتي)</strong> ثم <strong className="text-emerald-400">Create App (إنشاء تطبيق)</strong>.</li>
                                        <li>اختر نوع التطبيق: <strong className="text-white">Other (أخرى)</strong> ثم اختر <strong className="text-white">Business (أعمال)</strong>.</li>
                                    </ul>
                                </div>

                                <div className="p-4 bg-slate-900/80 border border-slate-800 rounded-xl space-y-3">
                                    <span className="inline-block px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-md text-xs font-bold">
                                        2. إضافة منتج WhatsApp
                                    </span>
                                    <ul className="text-xs text-slate-300 space-y-2 list-disc list-inside leading-relaxed">
                                        <li>ادخل اسم التطبيق (مثلاً: <strong className="text-white">Musoftware WhatsApp API</strong>).</li>
                                        <li>اختر حساب الأعمال الخاص بشركتك (<strong className="text-white">Meta Business Account</strong>).</li>
                                        <li>في لوحة التحكم، ابحث عن منتج <strong className="text-emerald-400">WhatsApp</strong> واضغط على <strong className="text-white">Set Up (إعداد)</strong>.</li>
                                    </ul>
                                </div>
                            </div>

                            <div className="p-4 bg-blue-950/40 border border-blue-900/50 rounded-xl flex items-start gap-3 text-xs text-blue-200">
                                <Info className="w-5 h-5 text-blue-400 shrink-0 mt-0.5" />
                                <div>
                                    <strong className="font-semibold block text-blue-300 mb-1">ملاحظة هامة للمبتدئين:</strong>
                                    توفر Meta رقم اختبار مؤقت يمكنك التجربة به فوراً، ولكن لإرسال الرسائل لعملائك الفعليين ستنتقل للخطوة الثانية لإضافة رقمك الرسمي.
                                </div>
                            </div>
                        </div>
                    )}

                    {/* STEP 2: PHONE NUMBER & IDS */}
                    {currentStep === 2 && (
                        <div className="space-y-4 animate-in fade-in duration-300">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-white flex items-center gap-2">
                                        <Smartphone className="w-5 h-5 text-emerald-400" />
                                        الخطوة الثانية: إضافة رقم الهاتف واستخراج المعرّفات (IDs)
                                    </h3>
                                    <p className="text-sm text-slate-400 mt-1">
                                        تأكيد ملكية رقم الهاتف والحصول على Meta Phone Number ID و WABA ID.
                                    </p>
                                </div>
                                <a
                                    href="https://business.facebook.com/wa/manage/phone-numbers/"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shrink-0"
                                >
                                    <ExternalLink className="w-4 h-4" />
                                    إدارة أرقام واتساب على Meta
                                </a>
                            </div>

                            <div className="space-y-3">
                                <div className="p-4 bg-slate-900/90 border border-slate-800 rounded-xl space-y-3">
                                    <h4 className="text-xs font-bold text-emerald-400 flex items-center gap-2">
                                        <CheckCircle2 className="w-4 h-4" /> كيفية إضافة وتأكيد رقم جديد:
                                    </h4>
                                    <ol className="text-xs text-slate-300 space-y-2 list-decimal list-inside leading-relaxed">
                                        <li>من داخل تطبيقك على Meta، اذهب إلى القائمة الجانبية: <strong className="text-white">WhatsApp &gt; API Setup</strong>.</li>
                                        <li>انزل لأسفل الشاشة واضغط على <strong className="text-emerald-400">Add Phone Number (إضافة رقم هاتف)</strong>.</li>
                                        <li>أدخل اسم العرض الترويجي (Business Profile Display Name) وفئة النشاط التجاري.</li>
                                        <li>أدخل رقم الهاتف المراد ربطه (يجب ألا يكون مستخدماً حالياً على تطبيق WhatsApp العادي أو Business على الهاتف).</li>
                                        <li>سيصلك كود تحقق مكون من 6 أرقام عبر <strong className="text-white">SMS</strong> أو <strong className="text-white">اتصال صوّتي</strong>، أدخله للتأكيد.</li>
                                    </ol>
                                </div>

                                {/* Demo Preview of ID card */}
                                <div className="p-4 bg-slate-900 border border-emerald-500/30 rounded-xl space-y-3">
                                    <h4 className="text-xs font-bold text-white flex items-center justify-between">
                                        <span>أين تجد المعرّفات المطلوبة في صفحة API Setup؟</span>
                                        <Badge className="bg-slate-800 text-slate-300 text-[10px]">توضيح بصري</Badge>
                                    </h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 space-y-1">
                                            <span className="text-slate-400 text-[11px] block">1. Meta Phone Number ID</span>
                                            <code className="text-emerald-400 font-mono font-bold block text-sm">104829103948123</code>
                                            <span className="text-[10px] text-slate-500 block">المعرف الفريد الخاص بخط الواتساب نفسه (مطلوب)</span>
                                        </div>
                                        <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 space-y-1">
                                            <span className="text-slate-400 text-[11px] block">2. WABA ID (WhatsApp Business Account ID)</span>
                                            <code className="text-emerald-400 font-mono font-bold block text-sm">982347109283741</code>
                                            <span className="text-[10px] text-slate-500 block">المعرف الرقمي لحساب الأعمال المالي (اختياري)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* STEP 3: PERMANENT SYSTEM USER ACCESS TOKEN */}
                    {currentStep === 3 && (
                        <div className="space-y-4 animate-in fade-in duration-300">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-white flex items-center gap-2">
                                        <Key className="w-5 h-5 text-emerald-400" />
                                        الخطوة الثالثة: استخراج رمز الوصول الدائم (Permanent Access Token)
                                    </h3>
                                    <p className="text-sm text-slate-400 mt-1">
                                        إنشاء رمز وصول آمن لا ينتهي عبر System User ليعمل النظام بدون انقطاع.
                                    </p>
                                </div>
                                <a
                                    href="https://business.facebook.com/settings/system-users"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shrink-0"
                                >
                                    <ExternalLink className="w-4 h-4" />
                                    فتح System Users في Meta
                                </a>
                            </div>

                            {/* Critical Warning Alert */}
                            <div className="p-4 bg-amber-950/60 border border-amber-500/40 rounded-xl flex items-start gap-3 text-xs text-amber-200">
                                <AlertTriangle className="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
                                <div>
                                    <strong className="font-bold block text-amber-300 text-sm mb-1">⚠️ تحذير هـام جداً:</strong>
                                    الرمز المسمى (Temporary Access Token) الموجود في صفحة API Setup ينتهي تلقائياً بعد <span className="underline font-bold">24 ساعة</span> وتتوقف الرسائل!
                                    يجب عليك إتباع الخطوات التالية لإنشاء <strong className="text-white underline">Permanent Token (توكن دائم لا ينتهي)</strong> عبر مستخدم النظام.
                                </div>
                            </div>

                            <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-3 text-xs text-slate-300 leading-relaxed">
                                <h4 className="font-bold text-emerald-400 text-sm flex items-center gap-2">
                                    <ShieldCheck className="w-4 h-4" /> خطوات استخراج التوكن الدائم:
                                </h4>
                                <ol className="space-y-2.5 list-decimal list-inside">
                                    <li>انتقل إلى <strong className="text-white">Meta Business Settings &gt; Users &gt; System Users</strong>.</li>
                                    <li>اضغط على <strong className="text-emerald-400">Add (إضافة)</strong>، اكتب اسماً مثل (<code className="bg-slate-800 text-emerald-300 px-1.5 py-0.5 rounded">Musoftware System User</code>) وحدد الدور: <strong className="text-white">Admin</strong>.</li>
                                    <li>اضغط على <strong className="text-white">Add Assets (إضافة أصول)</strong> اختر <strong className="text-emerald-400">Apps (التطبيقات)</strong>، حدد تطبيقك وفعّل الخيار <strong className="text-white">Full Control (التحكم الكامل)</strong> ثم احفظ.</li>
                                    <li>اضغط على زر <strong className="text-emerald-400">Generate New Token (إنشاء رمز جديد)</strong>.</li>
                                    <li>حدد تطبيقك، وفي خانة مدة الصلاحية (Expiration) اختر: <strong className="text-white underline">Never (أبداً / لا ينتهي)</strong>.</li>
                                    <li>قم بتعليم الأذونات (Permissions) التالية حصراً:
                                        <div className="flex flex-wrap gap-2 my-2 dir-ltr">
                                            <Badge className="bg-slate-800 text-emerald-400 border-slate-700 font-mono text-[11px]">whatsapp_business_messaging</Badge>
                                            <Badge className="bg-slate-800 text-emerald-400 border-slate-700 font-mono text-[11px]">whatsapp_business_management</Badge>
                                        </div>
                                    </li>
                                    <li>اضغط <strong className="text-white">Generate Token</strong> وانسخ الرمز الذي يبدأ بـ (<code className="text-emerald-400 font-mono font-bold">EAA...</code>) واحفظه لديك.</li>
                                </ol>
                            </div>
                        </div>
                    )}

                    {/* STEP 4: LINKING TO MUSOFTWARE */}
                    {currentStep === 4 && (
                        <div className="space-y-4 animate-in fade-in duration-300">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-white flex items-center gap-2">
                                        <ShieldCheck className="w-5 h-5 text-emerald-400" />
                                        الخطوة الرابعة: ربط البيانات وتفعيل الحساب بالنظام
                                    </h3>
                                    <p className="text-sm text-slate-400 mt-1">
                                        إدخال الحقول الأربعة المطلوبة في نموذج Connect Meta Credentials للحفظ والتأكيد.
                                    </p>
                                </div>
                            </div>

                            <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-4">
                                <h4 className="text-xs font-bold text-slate-300 flex items-center gap-2">
                                    <Info className="w-4 h-4 text-emerald-400" /> الجدول التوضيحي لإدخال الحقول:
                                </h4>

                                <div className="space-y-3 text-xs">
                                    <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-between gap-4">
                                        <div>
                                            <span className="font-bold text-white block">1. Account Label (اسم الحساب)</span>
                                            <span className="text-slate-400 text-[11px]">اسم توضيحي تختار لنفسك لتمييز الرقم.</span>
                                        </div>
                                        <code className="text-emerald-400 font-mono bg-slate-900 px-2 py-1 rounded text-xs shrink-0">مثال: خط الواتساب الرئيسي</code>
                                    </div>

                                    <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-between gap-4">
                                        <div>
                                            <span className="font-bold text-white block">2. Meta Phone Number ID</span>
                                            <span className="text-slate-400 text-[11px]">رقم المعرف المنسوخ من الخطوة 2.</span>
                                        </div>
                                        <code className="text-emerald-400 font-mono bg-slate-900 px-2 py-1 rounded text-xs shrink-0">مثال: 104829103948123</code>
                                    </div>

                                    <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-between gap-4">
                                        <div>
                                            <span className="font-bold text-white block">3. WABA ID (اختياري)</span>
                                            <span className="text-slate-400 text-[11px]">معرف حساب WhatsApp Business.</span>
                                        </div>
                                        <code className="text-emerald-400 font-mono bg-slate-900 px-2 py-1 rounded text-xs shrink-0">مثال: 982347109283741</code>
                                    </div>

                                    <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-between gap-4">
                                        <div>
                                            <span className="font-bold text-white block">4. Meta Access Token</span>
                                            <span className="text-slate-400 text-[11px]">التوكن الدائم المنسوخ من الخطوة 3.</span>
                                        </div>
                                        <code className="text-emerald-400 font-mono bg-slate-900 px-2 py-1 rounded text-xs shrink-0">يبدأ بـ EAA...</code>
                                    </div>
                                </div>

                                <div className="p-3 bg-emerald-950/40 border border-emerald-500/30 rounded-lg text-xs text-emerald-300 flex items-center gap-2">
                                    <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-400" />
                                    يقوم النظام فور الضغط على <strong className="text-white font-bold">Save Meta Credentials</strong> بعمل فحص تلقائي (Ping Verification) مع خوادم Meta للتحقق من صحة التوكن ورقم الهاتف قبل التنشيط!
                                </div>
                            </div>
                        </div>
                    )}

                    {/* STEP 5: WEBHOOKS & TESTING */}
                    {currentStep === 5 && (
                        <div className="space-y-4 animate-in fade-in duration-300">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-white flex items-center gap-2">
                                        <Sparkles className="w-5 h-5 text-emerald-400" />
                                        الخطوة الخامسة: إرسال أول رسالة وضبط الـ Webhooks
                                    </h3>
                                    <p className="text-sm text-slate-400 mt-1">
                                        اختبار الإرسال الفوري وإعداد إشعارات القراءة والتسليم.
                                    </p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-3 text-xs text-slate-300">
                                    <h4 className="font-bold text-emerald-400 text-sm flex items-center gap-2">
                                        <Check className="w-4 h-4" /> 1. تجربة إرسال رسالة:
                                    </h4>
                                    <p className="leading-relaxed">
                                        انتقل إلى تاب <strong className="text-white">Send Message (إرسال رسالة)</strong>، اختر الرقم المرتبط، أدخل رقم المستلم بالصيغة الدولية (مثل: <code className="text-emerald-400 font-mono">201012345678</code>) واكتب نص الرسالة أو اختر قالب معتمد (Template)، ثم اضغط إرسال.
                                    </p>
                                </div>

                                <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-3 text-xs text-slate-300">
                                    <h4 className="font-bold text-emerald-400 text-sm flex items-center gap-2">
                                        <ExternalLink className="w-4 h-4" /> 2. إعداد الـ Webhooks (اختياري):
                                    </h4>
                                    <p className="leading-relaxed">
                                        في تطبيق Meta، اذهب لـ <strong className="text-white">WhatsApp &gt; Configuration</strong> واشترك في الأحداث لمتابعة حالة التسليم والرسائل الواردة.
                                    </p>
                                    <div className="p-2.5 bg-slate-950 border border-slate-800 rounded font-mono text-[11px] text-emerald-400 flex items-center justify-between">
                                        <span className="truncate">/api/v1/whatsapp/webhook</span>
                                        <button
                                            onClick={() => copyToClipboard('/api/v1/whatsapp/webhook', 'webhook')}
                                            className="text-slate-400 hover:text-white shrink-0 ml-2"
                                        >
                                            {copiedField === 'webhook' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="p-4 bg-gradient-to-r from-emerald-950/60 to-slate-900 border border-emerald-500/30 rounded-xl flex items-center justify-between text-xs text-emerald-200">
                                <div className="flex items-center gap-3">
                                    <CheckCircle2 className="w-6 h-6 text-emerald-400 shrink-0" />
                                    <div>
                                        <span className="font-bold text-white block text-sm">تهانينا! أنت جاهز تماماً الآن.</span>
                                        يمكنك البدء بإرسال الرسائل، الربط البرمجي عبر REST API، ومتابعة رصيد محفظة الأعمال.
                                    </div>
                                </div>
                                <Button
                                    onClick={onClose}
                                    className="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 shrink-0"
                                >
                                    إغلاق وبدء الربط الان
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer Controls */}
                <DialogFooter className="p-4 bg-slate-900/90 border-t border-slate-800 flex items-center justify-between sm:justify-between flex-row-reverse">
                    <div>
                        {currentStep < 5 ? (
                            <Button
                                onClick={() => setCurrentStep((prev) => Math.min(prev + 1, 5))}
                                className="bg-emerald-600 hover:bg-emerald-500 text-white gap-2 font-semibold text-xs px-5"
                            >
                                الخطوة التالية
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        ) : (
                            <Button
                                onClick={onClose}
                                className="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5"
                            >
                                إغلاق الشرح
                            </Button>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        {currentStep > 1 && (
                            <Button
                                variant="outline"
                                onClick={() => setCurrentStep((prev) => Math.max(prev - 1, 1))}
                                className="border-slate-800 bg-slate-950 text-slate-300 hover:bg-slate-900 gap-2 font-semibold text-xs"
                            >
                                <ArrowRight className="w-4 h-4" />
                                الخطوة السابقة
                            </Button>
                        )}
                        <span className="text-xs text-slate-400 font-medium px-2">
                            خطوة {currentStep} من 5
                        </span>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};
