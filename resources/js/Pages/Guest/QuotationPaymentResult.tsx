import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { 
    CheckCircle2, XCircle, ArrowLeft, RotateCcw, 
    FileText, User, ShieldCheck, Sparkles, MessageCircle
} from 'lucide-react';

interface QuotationPaymentResultProps {
    status: 'success' | 'failed';
    message: string;
    order?: {
        id: number;
        uuid: string;
        order_number: string;
        client_name: string;
        client_email: string;
        deposit_amount: number;
        currency: string;
        quotation?: {
            title: string;
            quotation_number: string;
        };
        invoice?: {
            id: number;
            invoice_number: string;
        };
    };
    retryUrl?: string;
}

export default function QuotationPaymentResult({ status, message, order, retryUrl }: QuotationPaymentResultProps) {
    const isSuccess = status === 'success';

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col justify-between text-slate-900 selection:bg-indigo-500 selection:text-white">
            <Head title={isSuccess ? 'تم الدفع بنجاح - مسوفتوير' : 'فشلت عملية الدفع - مسوفتوير'} />

            {/* Header */}
            <header className="bg-white/80 backdrop-blur-md border-b border-slate-200 py-4 px-6">
                <div className="max-w-4xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="w-8 h-8 fill-current text-slate-900" />
                        <span className="font-extrabold text-base tracking-tight text-slate-900">
                            MUSOFTWARE
                        </span>
                    </div>
                </div>
            </header>

            {/* Main Result Card */}
            <main className="max-w-xl mx-auto px-4 py-12 w-full">
                <Card className="border-slate-200 shadow-xl bg-white overflow-hidden text-center">
                    <div className={`py-8 px-6 ${isSuccess ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white' : 'bg-gradient-to-br from-red-500 to-rose-600 text-white'}`}>
                        <div className="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center mx-auto mb-3">
                            {isSuccess ? (
                                <CheckCircle2 className="w-10 h-10 text-white" />
                            ) : (
                                <XCircle className="w-10 h-10 text-white" />
                            )}
                        </div>
                        <h1 className="text-2xl font-extrabold">
                            {isSuccess ? 'تم استلام الدفعة المقدمة بنجاح!' : 'تعذر إتمام عملية الدفع'}
                        </h1>
                        <p className="text-xs text-white/80 mt-1 max-w-sm mx-auto">
                            {message}
                        </p>
                    </div>

                    <CardContent className="p-6 sm:p-8 space-y-6">
                        {isSuccess && order && (
                            <div className="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 space-y-3 text-right text-xs sm:text-sm">
                                <div className="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                    <span className="text-slate-500">رقم طلب العرض:</span>
                                    <span className="font-mono font-bold text-slate-900">{order.order_number}</span>
                                </div>
                                <div className="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                    <span className="text-slate-500">المشروع:</span>
                                    <span className="font-bold text-slate-900 line-clamp-1">{order.quotation?.title}</span>
                                </div>
                                <div className="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                    <span className="text-slate-500">الاسم والبريد:</span>
                                    <span className="font-medium text-slate-900">{order.client_name} ({order.client_email})</span>
                                </div>
                                <div className="flex items-center justify-between pt-1 text-emerald-700">
                                    <span className="font-bold">المبلغ المسدد (50%):</span>
                                    <span className="font-mono font-extrabold text-base">{order.deposit_amount} {order.currency}</span>
                                </div>
                            </div>
                        )}

                        {isSuccess ? (
                            <div className="space-y-4">
                                <div className="p-4 rounded-xl bg-indigo-50/60 border border-indigo-100 text-indigo-950 text-xs leading-relaxed text-right space-y-1">
                                    <p className="font-bold">🚀 ما هي الخطوات القادمة؟</p>
                                    <p className="text-slate-600">
                                        تم إنشاء حسابك في المنصة وإصدار الفاتورة الرسمية. سيتواصل معك مدير المشروع والمهندس المسؤول خلال دقائق عبر الهاتف أو الواتساب لبدء أولى خطوات التنفيذ الفعلي ومتابعة المخرجات.
                                    </p>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <Link href="/login" className="flex-1">
                                        <Button className="w-full bg-slate-900 text-white hover:bg-slate-800 font-bold">
                                            تسجيل الدخول إلى حسابك
                                        </Button>
                                    </Link>
                                    <Link href="/" className="flex-1">
                                        <Button variant="outline" className="w-full border-slate-200">
                                            العودة للرئيسية
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-xs text-slate-500 text-right">
                                    قد تكون المشكلة بسبب رفض من البنك أو انتهاء جلسة السداد. يمكنك إعادة المحاولة الآن.
                                </p>
                                {retryUrl && (
                                    <Link href={retryUrl}>
                                        <Button className="w-full bg-slate-900 text-white hover:bg-slate-800 gap-2">
                                            <RotateCcw className="w-4 h-4" />
                                            إعادة محاولة الدفع
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </main>

            {/* Footer */}
            <footer className="text-center py-6 text-xs text-slate-400">
                © {new Date().getFullYear()} Musoftware. جميع الحقوق محفوظة.
            </footer>
        </div>
    );
}
