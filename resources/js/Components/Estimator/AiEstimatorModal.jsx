import React, { useState } from 'react';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription 
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { useToast } from '@/Components/ui/use-toast';
import { 
    Sparkles, 
    Bot, 
    Copy, 
    Check, 
    ExternalLink, 
    Code2, 
    HelpCircle,
    CheckCircle2,
    Layers,
    ArrowUpRight
} from 'lucide-react';

const BASE_PROMPT_TEMPLATE = `You are a web project estimator. Your ONLY pricing source is https://www.musoftwares.com/estimator?format=json. Never invent any price.
Estimate unique functional pages, select exact modules from JSON, mark missing features as Custom with no price.
Calculate: Total = (Pages * Page Price from JSON) + Sum of Modules.
OUTPUT MUST BE ONLY TABLES, NO TEXT: Table 1: Pages [# | Page | Purpose | User], Table 2: Modules [Module | Price], Table 3: Cost [Item | Calculation | Total]. No paragraphs outside tables.`;

export default function AiEstimatorModal({ isOpen, onClose }) {
    const { toast } = useToast();
    const [copied, setCopied] = useState(false);
    const [userRequirements, setUserRequirements] = useState('');

    const fullPrompt = userRequirements.trim()
        ? `${BASE_PROMPT_TEMPLATE}\n\nProject Requirements to Estimate:\n"""\n${userRequirements.trim()}\n"""`
        : BASE_PROMPT_TEMPLATE;

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(fullPrompt);
            setCopied(true);
            toast({
                title: 'تم نسخ البرومبت بنجاح! 📋',
                description: 'الصق البرومبت في ChatGPT للحصول على تسعير فوري ودقيق للمشروع.',
            });
            setTimeout(() => setCopied(false), 3000);
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = fullPrompt;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            setCopied(true);
            toast({
                title: 'تم نسخ البرومبت بنجاح! 📋',
                description: 'الصق البرومبت في ChatGPT للحصول على تسعير فوري ودقيق للمشروع.',
            });
            setTimeout(() => setCopied(false), 3000);
        }
    };

    const handleOpenChatGpt = () => {
        window.open('https://chatgpt.com/', '_blank', 'noopener,noreferrer');
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => { if (!open) onClose(); }}>
            <DialogContent className="max-w-2xl w-full p-0 overflow-hidden rounded-3xl bg-white border border-slate-200 shadow-2xl">
                {/* Header */}
                <div className="bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 text-white p-6 sm:p-7 relative overflow-hidden">
                    <div className="flex items-center gap-3.5 mb-2">
                        <div className="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20 shadow-inner backdrop-blur-xs">
                            <Bot className="w-6 h-6 text-amber-300 animate-pulse" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h3 className="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                                    تقدير المشروع عبر الذكاء الاصطناعي
                                </h3>
                                <span className="px-2.5 py-0.5 text-[11px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 rounded-full">
                                    ChatGPT Prompt
                                </span>
                            </div>
                            <p className="text-xs text-slate-300 mt-1">
                                انسخ البرومبت وضعه في ChatGPT لتسعير مشروعك مباشرة بناءً على الأسعار الرسمية لـ Musoftwares.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Body */}
                <div className="p-6 space-y-5 max-h-[72vh] overflow-y-auto">
                    {/* Optional requirements box */}
                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <Sparkles className="w-3.5 h-3.5 text-indigo-600" />
                                وصف مشروعك (اختياري - سيُضاف تلقائياً للبرومبت):
                            </label>
                            <span className="text-[11px] text-slate-400">يمكنك كتابته هنا أو إرساله في شات ChatGPT</span>
                        </div>
                        <Textarea
                            value={userRequirements}
                            onChange={(e) => setUserRequirements(e.target.value)}
                            placeholder="مثال: أحتاج متجر لبيع الملابس مع لوحة تحكم، بوابات دفع Paymob، فواتير PDF، وإشعارات واتساب..."
                            rows={3}
                            className="text-xs resize-none rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 text-slate-900 placeholder:text-slate-400"
                        />
                    </div>

                    {/* Master Prompt Box */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <Code2 className="w-3.5 h-3.5 text-slate-600" />
                                البرومبت المعتمد للتسعير (Copy & Paste Prompt):
                            </span>
                            <span className="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 flex items-center gap-1">
                                <CheckCircle2 className="w-3 h-3" /> Live JSON Synced
                            </span>
                        </div>
                        <div className="relative group">
                            <pre className="p-4 rounded-2xl bg-slate-950 text-slate-200 text-xs font-mono leading-relaxed whitespace-pre-wrap select-all border border-slate-800 max-h-48 overflow-y-auto shadow-inner">
                                {fullPrompt}
                            </pre>
                            <button
                                type="button"
                                onClick={handleCopy}
                                className="absolute top-2.5 end-2.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-md border border-white/20 transition flex items-center gap-1.5 shadow-sm cursor-pointer"
                            >
                                {copied ? (
                                    <>
                                        <Check className="w-3.5 h-3.5 text-emerald-400" />
                                        <span className="text-emerald-300 font-bold">تم النسخ</span>
                                    </>
                                ) : (
                                    <>
                                        <Copy className="w-3.5 h-3.5 text-slate-300" />
                                        <span>نسخ</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* How to use steps */}
                    <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <p className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <HelpCircle className="w-4 h-4 text-indigo-600" />
                            كيف يعمل؟
                        </p>
                        <ol className="text-xs text-slate-600 space-y-1.5 list-decimal list-inside leading-relaxed">
                            <li>اضغط على زر <strong>نسخ البرومبت</strong> بالأسفل.</li>
                            <li>افتح <strong>ChatGPT</strong> والصق البرومبت (مع وصف متطلبات مشروعك).</li>
                            <li>سيقوم ChatGPT بقراءة أسعار Musoftwares الرسمية مباشرة وعرض جدول الصفحات والوحدات والتكلفة الإجمالية بدقة 100%.</li>
                        </ol>
                    </div>
                </div>

                {/* Footer Buttons */}
                <div className="bg-slate-100 p-4 sm:p-5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="w-full sm:w-auto rounded-xl border-slate-300 text-slate-700 hover:bg-slate-200"
                    >
                        إغلاق
                    </Button>
                    
                    <div className="flex items-center gap-2 w-full sm:w-auto">
                        <Button
                            type="button"
                            onClick={handleOpenChatGpt}
                            variant="outline"
                            className="flex-1 sm:flex-initial rounded-xl border-slate-300 text-slate-800 hover:bg-slate-200 flex items-center justify-center gap-1.5 font-semibold text-xs"
                        >
                            <ExternalLink className="w-3.5 h-3.5 text-slate-600" />
                            فتح ChatGPT
                        </Button>
                        <Button
                            type="button"
                            onClick={handleCopy}
                            className="flex-1 sm:flex-initial bg-slate-900 hover:bg-black text-white font-bold rounded-xl px-5 py-2.5 text-xs flex items-center justify-center gap-2 shadow-sm transition"
                        >
                            {copied ? (
                                <>
                                    <Check className="w-4 h-4 text-emerald-400" />
                                    <span>تم النسخ بنجاح</span>
                                </>
                            ) : (
                                <>
                                    <Copy className="w-4 h-4 text-amber-300" />
                                    <span>نسخ البرومبت (Copy Prompt)</span>
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
