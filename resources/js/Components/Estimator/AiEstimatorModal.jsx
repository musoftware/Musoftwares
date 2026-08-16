import React, { useState } from 'react';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription,
    DialogFooter 
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { useToast } from '@/Components/ui/use-toast';
import { 
    Sparkles, 
    Bot, 
    Loader2, 
    CheckCircle2, 
    Globe, 
    Smartphone, 
    Monitor, 
    Layers, 
    SlidersHorizontal, 
    ArrowRight,
    Wand2,
    Lightbulb
} from 'lucide-react';
import axios from 'axios';
import { __ } from '@/lib/i18n';

export default function AiEstimatorModal({ isOpen, onClose, onApply, optionsDefinitions = {} }) {
    const { toast } = useToast();
    const [prompt, setPrompt] = useState('');
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);

    const samplePrompts = [
        "متجر إلكتروني متكامل لبيع الملابس مع تطبيق موبايل وبوابات دفع وشات ذكاء اصطناعي وفواتير وإشعارات واتساب",
        "منصة حجز استشارات طبية مع تطبيق أطباء وتطبيق مرضى، دفع إلكتروني، خرائط GPS ومحادثة مباشرة",
        "برنامج نقاط بيع POS وكاشير لشبكة مطاعم ومخازن يعمل أوفلاين ويدعم الفاتورة الإلكترونية وطباعة الباركود",
        "SaaS Web & Mobile platform with subscription billing, multi-tenancy, REST APIs, and analytics dashboard"
    ];

    const handleAnalyze = async () => {
        if (!prompt.trim() || prompt.trim().length < 10) {
            toast({
                title: 'تنبيه',
                description: 'يرجى كتابة وصف كافٍ للمشروع (10 أحرف على الأقل).',
                variant: 'destructive'
            });
            return;
        }

        setLoading(true);
        setResult(null);

        try {
            const response = await axios.post(route('estimator.ai-analyze'), {
                prompt: prompt.trim()
            });

            if (response.data?.success && response.data?.data) {
                setResult(response.data.data);
                toast({
                    title: 'تم التحليل بنجاح ✨',
                    description: 'قام الذكاء الاصطناعي بتقدير المنصات، الصفحات، والإضافات المناسبة.',
                });
            } else {
                throw new Error(response.data?.message || 'فشل التحليل');
            }
        } catch (error) {
            const msg = error.response?.data?.message || error.message || 'حدث خطأ أثناء الاتصال بالذكاء الاصطناعي.';
            toast({
                title: 'خطأ في التحليل',
                description: msg,
                variant: 'destructive'
            });
        } finally {
            setLoading(false);
        }
    };

    const handleApply = () => {
        if (!result) return;
        onApply(result);
        onClose();
        toast({
            title: 'تم تطبيق التقدير على الحاسبة 🚀',
            description: 'تم تحديث المنصات، عدد الصفحات، والوحدات المحددة تلقائياً.',
        });
    };

    // Helper to find human-readable title of option
    const getOptionTitle = (optId) => {
        for (const plat of ['web', 'mobile', 'desktop']) {
            const found = (optionsDefinitions[plat] || []).find(o => o.id === optId);
            if (found) return found.title;
        }
        return optId.replace(/^(web_|mobile_|desktop_)/, '').replace(/_/g, ' ');
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => { if (!open) onClose(); }}>
            <DialogContent className="max-w-2xl w-full p-0 overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-2xl">
                {/* Header with gradient badge */}
                <div className="bg-slate-900 text-white p-6 relative overflow-hidden">
                    <div className="absolute top-0 end-0 p-8 opacity-10 pointer-events-none">
                        <Wand2 className="w-32 h-32 text-white" />
                    </div>
                    <div className="flex items-center gap-3 mb-2">
                        <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/20 shadow-inner">
                            <Sparkles className="w-5 h-5 text-amber-300 animate-pulse" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h3 className="text-xl font-bold text-white tracking-tight">
                                    ساحر التسعير الذكي (Admin AI Assistant)
                                </h3>
                                <span className="px-2 py-0.5 text-[11px] font-semibold bg-amber-400/20 text-amber-300 border border-amber-400/30 rounded-full">
                                    AI Powered
                                </span>
                            </div>
                            <p className="text-xs text-slate-300 mt-0.5">
                                أدخل وصف المشروع أو متطلبات العميل وسيقوم الذكاء الاصطناعي بتقدير المنصات، عدد الصفحات، واختيار الوحدات تلقائياً.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Body */}
                <div className="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    {/* Prompt input */}
                    <div className="space-y-2">
                        <label className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <Bot className="w-4 h-4 text-slate-600" />
                            وصف المشروع أو متطلبات العميل:
                        </label>
                        <Textarea
                            value={prompt}
                            onChange={(e) => setPrompt(e.target.value)}
                            placeholder="مثال: محتاج متجر إلكتروني مع تطبيق موبايل لبيع العطور، فيه بوابات دفع، تسجيل دخول بالهاتف، شات ذكاء اصطناعي، وإشعارات واتساب..."
                            rows={4}
                            className="text-sm resize-none rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 text-slate-900 placeholder:text-slate-400"
                        />
                    </div>

                    {/* Quick prompt suggestions */}
                    <div className="space-y-1.5">
                        <div className="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                            <Lightbulb className="w-3.5 h-3.5 text-amber-500" />
                            نماذج متطلبات جاهزة للتجربة السريعة:
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            {samplePrompts.map((sp, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    onClick={() => setPrompt(sp)}
                                    className="text-xs px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition text-start truncate max-w-full"
                                >
                                    {sp}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Submit Button */}
                    <Button
                        type="button"
                        onClick={handleAnalyze}
                        disabled={loading || !prompt.trim()}
                        className="w-full bg-slate-900 hover:bg-black text-white font-medium py-2.5 rounded-xl transition flex items-center justify-center gap-2 shadow-sm"
                    >
                        {loading ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin text-amber-400" />
                                جاري تحليل المتطلبات وتقدير البنية البرمجية...
                            </>
                        ) : (
                            <>
                                <Sparkles className="w-4 h-4 text-amber-300" />
                                تحليل المشروع واقتراح التقدير بالذكاء الاصطناعي
                            </>
                        )}
                    </Button>

                    {/* AI Results Preview */}
                    {result && (
                        <div className="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4 animate-in fade-in-50 duration-200">
                            <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                                <span className="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                                    نتيجة التحليل المقترحة
                                </span>
                                <span className="text-[11px] text-slate-500">
                                    {result.platforms?.length || 0} منصات | {Object.keys(result.selectedOptions || {}).length} ميزة محددة
                                </span>
                            </div>

                            {/* Summary */}
                            {(result.summary_ar || result.summary_en) && (
                                <div className="text-xs text-slate-700 bg-white p-3 rounded-lg border border-slate-200">
                                    <p className="font-semibold text-slate-900 mb-1">ملخص النطاق المقترح:</p>
                                    <p className="leading-relaxed">{result.summary_ar || result.summary_en}</p>
                                </div>
                            )}

                            {/* Platforms & Screen Breakdown */}
                            <div>
                                <p className="text-xs font-semibold text-slate-800 mb-2">المنصات وعدد الشاشات المقدرة:</p>
                                <div className="grid grid-cols-3 gap-2">
                                    <div className={`p-2.5 rounded-lg border text-center transition ${result.platforms?.includes('web') ? 'bg-white border-slate-900 text-slate-900 shadow-sm' : 'bg-slate-100 border-slate-200 text-slate-400 opacity-60'}`}>
                                        <Globe className="w-4 h-4 mx-auto mb-1 text-slate-700" />
                                        <div className="text-xs font-bold">موقع ويب</div>
                                        <div className="text-[11px] text-slate-500 font-medium">
                                            {result.platforms?.includes('web') ? `${result.platformScreens?.web || 5} صفحات` : 'غير مشمول'}
                                        </div>
                                    </div>
                                    <div className={`p-2.5 rounded-lg border text-center transition ${result.platforms?.includes('mobile') ? 'bg-white border-slate-900 text-slate-900 shadow-sm' : 'bg-slate-100 border-slate-200 text-slate-400 opacity-60'}`}>
                                        <Smartphone className="w-4 h-4 mx-auto mb-1 text-slate-700" />
                                        <div className="text-xs font-bold">تطبيق موبايل</div>
                                        <div className="text-[11px] text-slate-500 font-medium">
                                            {result.platforms?.includes('mobile') ? `${result.platformScreens?.mobile || 5} شاشات` : 'غير مشمول'}
                                        </div>
                                    </div>
                                    <div className={`p-2.5 rounded-lg border text-center transition ${result.platforms?.includes('desktop') ? 'bg-white border-slate-900 text-slate-900 shadow-sm' : 'bg-slate-100 border-slate-200 text-slate-400 opacity-60'}`}>
                                        <Monitor className="w-4 h-4 mx-auto mb-1 text-slate-700" />
                                        <div className="text-xs font-bold">برنامج سطح مكتب</div>
                                        <div className="text-[11px] text-slate-500 font-medium">
                                            {result.platforms?.includes('desktop') ? `${result.platformScreens?.desktop || 5} شاشات` : 'غير مشمول'}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Selected Addons Badges */}
                            <div>
                                <p className="text-xs font-semibold text-slate-800 mb-2">الوحدات والإضافات المختارة:</p>
                                <div className="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto p-1">
                                    {Object.entries(result.selectedOptions || {}).map(([key, val]) => (
                                        <span
                                            key={key}
                                            className="inline-flex items-center gap-1 text-[11px] font-medium bg-white text-slate-800 px-2.5 py-1 rounded-md border border-slate-200 shadow-xs"
                                        >
                                            <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                                            {getOptionTitle(key)}
                                            {typeof val === 'number' && val > 1 && (
                                                <span className="bg-slate-900 text-white text-[10px] px-1 rounded font-bold">
                                                    x{val}
                                                </span>
                                            )}
                                        </span>
                                    ))}
                                    {Object.keys(result.selectedOptions || {}).length === 0 && (
                                        <span className="text-xs text-slate-400 italic">لا توجد وحدات إضافية محددة</span>
                                    )}
                                </div>
                            </div>

                            {/* Recommendations list */}
                            {result.recommended_reasons && result.recommended_reasons.length > 0 && (
                                <div className="text-[11px] text-slate-600 space-y-1 bg-white/70 p-2.5 rounded-lg border border-slate-200">
                                    <p className="font-semibold text-slate-800">ملاحظات الذكاء الاصطناعي:</p>
                                    <ul className="list-disc list-inside space-y-0.5">
                                        {result.recommended_reasons.map((r, i) => (
                                            <li key={i}>{r}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Footer Actions */}
                <div className="bg-slate-100 p-4 border-t border-slate-200 flex items-center justify-between gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="rounded-xl border-slate-300 text-slate-700"
                    >
                        إلغاء
                    </Button>
                    <Button
                        type="button"
                        disabled={!result}
                        onClick={handleApply}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl px-5 flex items-center gap-1.5 shadow-sm"
                    >
                        <CheckCircle2 className="w-4 h-4" />
                        تطبيق التقدير على الحاسبة الآن
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
