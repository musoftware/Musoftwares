import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { __ } from '@/lib/i18n';
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
        <div className="min-h-screen bg-background flex flex-col justify-between text-foreground selection:bg-primary selection:text-white transition-colors duration-200">
            <Head title={isSuccess ? __('quotations.guest_result_success_title') : __('quotations.guest_result_failed_title')} />

            {/* Header */}
            <header className="bg-card/80 backdrop-blur-md border-b border-border py-4 px-6 sticky top-0 z-30">
                <div className="max-w-4xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="w-8 h-8 fill-current text-foreground" />
                        <span className="font-extrabold text-base tracking-tight text-foreground">
                            MUSOFTWARE
                        </span>
                    </div>
                    <ThemeToggle className="h-8 w-8" />
                </div>
            </header>

            {/* Main Result Card */}
            <main className="max-w-xl mx-auto px-4 py-12 w-full">
                <Card className="border-border shadow-xl bg-card text-card-foreground overflow-hidden text-center">
                    <div className={`py-8 px-6 ${isSuccess ? 'bg-gradient-to-br from-emerald-600 to-teal-700 text-white' : 'bg-gradient-to-br from-red-600 to-rose-700 text-white'}`}>
                        <div className="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center mx-auto mb-3">
                            {isSuccess ? (
                                <CheckCircle2 className="w-10 h-10 text-white" />
                            ) : (
                                <XCircle className="w-10 h-10 text-white" />
                            )}
                        </div>
                        <h1 className="text-2xl font-extrabold text-white">
                            {isSuccess ? __('quotations.guest_result_success_heading') : __('quotations.guest_result_failed_heading')}
                        </h1>
                        <p className="text-xs text-white/90 mt-1 max-w-sm mx-auto">
                            {message}
                        </p>
                    </div>

                    <CardContent className="p-6 sm:p-8 space-y-6">
                        {isSuccess && order && (
                            <div className="bg-muted/40 dark:bg-slate-800/40 rounded-2xl p-5 border border-border space-y-3 text-start text-xs sm:text-sm">
                                <div className="flex items-center justify-between pb-2 border-b border-border">
                                    <span className="text-muted-foreground">{__('quotations.guest_result_order_number')}</span>
                                    <span className="font-mono font-bold text-foreground">{order.order_number}</span>
                                </div>
                                <div className="flex items-center justify-between pb-2 border-b border-border">
                                    <span className="text-muted-foreground">{__('quotations.guest_result_project')}</span>
                                    <span className="font-bold text-foreground line-clamp-1">{order.quotation?.title}</span>
                                </div>
                                <div className="flex items-center justify-between pb-2 border-b border-border">
                                    <span className="text-muted-foreground">{__('quotations.guest_result_name_email')}</span>
                                    <span className="font-medium text-foreground">{order.client_name} ({order.client_email})</span>
                                </div>
                                <div className="flex items-center justify-between pt-1 text-emerald-600 dark:text-emerald-400">
                                    <span className="font-bold">{__('quotations.guest_result_amount_paid')}</span>
                                    <span className="font-mono font-extrabold text-base">{order.deposit_amount} {order.currency}</span>
                                </div>
                            </div>
                        )}

                        {isSuccess ? (
                            <div className="space-y-4">
                                <div className="p-4 rounded-xl bg-primary/10 border border-primary/20 text-foreground text-xs leading-relaxed text-start space-y-1">
                                    <p className="font-bold text-primary">{__('quotations.guest_result_next_steps')}</p>
                                    <p className="text-muted-foreground">
                                        {__('quotations.guest_result_next_steps_desc')}
                                    </p>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <Link href="/login" className="flex-1">
                                        <Button className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-bold">
                                            {__('quotations.guest_result_login')}
                                        </Button>
                                    </Link>
                                    <Link href="/" className="flex-1">
                                        <Button variant="outline" className="w-full border-border text-foreground hover:bg-muted">
                                            {__('quotations.guest_result_back_home')}
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-xs text-muted-foreground text-start">
                                    {__('quotations.guest_result_failed_hint')}
                                </p>
                                {retryUrl && (
                                    <Link href={retryUrl}>
                                        <Button className="w-full bg-primary hover:bg-primary/90 text-primary-foreground gap-2">
                                            <RotateCcw className="w-4 h-4" />
                                            {__('quotations.guest_result_retry')}
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </main>

            {/* Footer */}
            <footer className="text-center py-6 text-xs text-muted-foreground">
                © {new Date().getFullYear()} Musoftware. {__('quotations.guest_result_rights')}
            </footer>
        </div>
    );
}
