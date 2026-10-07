import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { 
    ArrowRight, Lock, ShieldCheck, CheckCircle2, 
    CreditCard, Sparkles, User, Mail, Phone, Building2, FileText
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Quotation {
    id: number;
    uuid: string;
    quotation_number: string;
    title: string;
    currency: string;
    deposit_percentage: number;
    development_total: number;
    deposit_amount: number;
    remaining_amount: number;
}

interface QuotationCheckoutProps {
    quotation: Quotation;
    payUrl: string;
    backUrl: string;
}

export default function QuotationCheckout({ quotation, payUrl, backUrl }: QuotationCheckoutProps) {
    const { data, setData, post, processing, errors } = useForm({
        client_name: '',
        client_email: '',
        client_phone: '',
        client_whatsapp: '',
        company_name: '',
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.client_name.trim() || !data.client_email.trim()) {
            toast.error(__('quotations.guest_checkout_name_email_required'));
            return;
        }

        post(payUrl, {
            onError: (errs) => {
                console.error(errs);
                toast.error(__('quotations.guest_checkout_check_data'));
            },
        });
    };

    return (
        <div className="min-h-screen bg-background text-foreground selection:bg-primary selection:text-white pb-24 transition-colors duration-200">
            <Head title={__('quotations.guest_checkout_page_title', { title: quotation.title })} />

            {/* Header */}
            <header className="bg-card/80 backdrop-blur-md border-b border-border sticky top-0 z-30">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="w-8 h-8 fill-current text-foreground" />
                        <div>
                            <span className="font-extrabold text-base tracking-tight text-foreground block leading-none">
                                MUSOFTWARE
                            </span>
                            <span className="text-[10px] text-muted-foreground font-medium tracking-wider uppercase">
                                {__('quotations.guest_checkout_secure')}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <ThemeToggle className="h-8 w-8" />
                        <Link href={backUrl} className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium">
                            <ArrowRight className="w-4 h-4 rotate-180 rtl:rotate-0" />
                            {__('quotations.guest_checkout_back')}
                        </Link>
                    </div>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-4 sm:px-6 pt-8 sm:pt-12">
                {/* Step Indicator */}
                <div className="mb-8 flex items-center justify-center gap-2 sm:gap-4 text-xs font-semibold">
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <span className="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-muted-foreground">1</span>
                        <span>{__('quotations.guest_checkout_step_review')}</span>
                    </div>
                    <div className="w-8 h-0.5 bg-border" />
                    <div className="flex items-center gap-1.5 text-primary">
                        <span className="w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-bold">2</span>
                        <span className="font-bold">{__('quotations.guest_checkout_step_details')}</span>
                    </div>
                    <div className="w-8 h-0.5 bg-border" />
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <span className="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-muted-foreground">3</span>
                        <span>{__('quotations.guest_checkout_step_confirm')}</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    {/* Left 7 Cols: Customer Information Form */}
                    <div className="lg:col-span-7 space-y-6">
                        <Card className="border-border shadow-sm bg-card text-card-foreground">
                            <CardHeader className="border-b border-border pb-4">
                                <CardTitle className="text-lg font-bold text-foreground flex items-center gap-2">
                                    <User className="w-5 h-5 text-primary" />
                                    {__('quotations.guest_checkout_contact_title')}
                                </CardTitle>
                                <CardDescription className="text-muted-foreground">
                                    {__('quotations.guest_checkout_contact_desc')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <form onSubmit={submit} id="checkout-form" className="space-y-4">
                                    {/* Full Name */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="client_name" className="text-xs font-bold text-foreground">
                                            {__('quotations.guest_checkout_full_name')} <span className="text-destructive">*</span>
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="client_name"
                                                type="text"
                                                placeholder={__('quotations.guest_checkout_name_placeholder')}
                                                value={data.client_name}
                                                onChange={(e) => setData('client_name', e.target.value)}
                                                className="bg-background text-foreground border-input focus-visible:ring-primary"
                                                required
                                            />
                                        </div>
                                        {errors.client_name && <p className="text-xs text-destructive">{errors.client_name}</p>}
                                    </div>

                                    {/* Email */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="client_email" className="text-xs font-bold text-foreground">
                                            {__('quotations.guest_checkout_email')} <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="client_email"
                                            type="email"
                                            placeholder="name@example.com"
                                            value={data.client_email}
                                            onChange={(e) => setData('client_email', e.target.value)}
                                            className="bg-background text-foreground border-input focus-visible:ring-primary font-mono text-sm"
                                            required
                                        />
                                        {errors.client_email && <p className="text-xs text-destructive">{errors.client_email}</p>}
                                        <span className="text-[11px] text-muted-foreground">{__('quotations.guest_checkout_email_hint')}</span>
                                    </div>

                                    {/* Phone & WhatsApp */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="client_phone" className="text-xs font-bold text-foreground">
                                                {__('quotations.guest_checkout_phone')}
                                            </Label>
                                            <Input
                                                id="client_phone"
                                                type="tel"
                                                placeholder="010XXXXXXXX"
                                                value={data.client_phone}
                                                onChange={(e) => {
                                                    const val = e.target.value;
                                                    setData(prev => ({
                                                        ...prev,
                                                        client_phone: val,
                                                        client_whatsapp: prev.client_whatsapp || val,
                                                    }));
                                                }}
                                                className="bg-background text-foreground border-input focus-visible:ring-primary font-mono text-sm"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="client_whatsapp" className="text-xs font-bold text-foreground">
                                                {__('quotations.guest_checkout_whatsapp')}
                                            </Label>
                                            <Input
                                                id="client_whatsapp"
                                                type="tel"
                                                placeholder="010XXXXXXXX"
                                                value={data.client_whatsapp}
                                                onChange={(e) => setData('client_whatsapp', e.target.value)}
                                                className="bg-background text-foreground border-input focus-visible:ring-primary font-mono text-sm"
                                            />
                                        </div>
                                    </div>

                                    {/* Company Name */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="company_name" className="text-xs font-bold text-foreground">
                                            {__('quotations.guest_checkout_company')}
                                        </Label>
                                        <Input
                                            id="company_name"
                                            type="text"
                                            placeholder={__('quotations.guest_checkout_company_placeholder')}
                                            value={data.company_name}
                                            onChange={(e) => setData('company_name', e.target.value)}
                                            className="bg-background text-foreground border-input focus-visible:ring-primary"
                                        />
                                    </div>

                                    {/* Notes */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="notes" className="text-xs font-bold text-foreground">
                                            {__('quotations.guest_checkout_notes')}
                                        </Label>
                                        <Textarea
                                            id="notes"
                                            placeholder={__('quotations.guest_checkout_notes_placeholder')}
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            rows={3}
                                            className="bg-background text-foreground border-input focus-visible:ring-primary text-sm"
                                        />
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right 5 Cols: Order Summary & Checkout Action */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="border-border shadow-md bg-card text-card-foreground overflow-hidden">
                            <CardHeader className="bg-primary text-primary-foreground p-5">
                                <span className="font-mono text-xs text-primary-foreground/80 font-semibold">{quotation.quotation_number}</span>
                                <CardTitle className="text-base font-bold text-primary-foreground mt-1 line-clamp-2">
                                    {quotation.title}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-6 space-y-4">
                                <div className="space-y-2.5 pb-4 border-b border-border text-sm">
                                    <div className="flex items-center justify-between text-muted-foreground">
                                        <span>{__('quotations.guest_checkout_project_total')}</span>
                                        <span className="font-mono font-bold text-foreground">{quotation.development_total} {quotation.currency}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-muted-foreground">
                                        <span>{__('quotations.guest_checkout_deposit_rate')}</span>
                                        <span className="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{quotation.deposit_percentage}%</span>
                                    </div>
                                    <div className="flex items-center justify-between text-muted-foreground">
                                        <span>{__('quotations.guest_checkout_remaining')}</span>
                                        <span className="font-mono text-muted-foreground">{quotation.remaining_amount} {quotation.currency}</span>
                                    </div>
                                </div>

                                <div className="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 space-y-1 text-center">
                                    <span className="text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider block">
                                        {__('quotations.guest_checkout_due_now', { pct: quotation.deposit_percentage })}
                                    </span>
                                    <div className="text-3xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400">
                                        {quotation.deposit_amount} <span className="text-sm font-normal text-muted-foreground">{quotation.currency}</span>
                                    </div>
                                </div>

                                <div className="space-y-2 pt-2">
                                    <Button
                                        type="submit"
                                        form="checkout-form"
                                        disabled={processing}
                                        className="w-full bg-primary hover:bg-primary/90 text-primary-foreground text-base font-bold py-6 rounded-2xl shadow-lg hover:shadow-xl transition-all gap-2"
                                    >
                                        <Lock className="w-4 h-4 text-emerald-300" />
                                        {processing ? __('quotations.guest_checkout_redirecting') : __('quotations.guest_checkout_pay_now')}
                                    </Button>
                                    <p className="text-[11px] text-center text-muted-foreground">
                                        {__('quotations.guest_checkout_redirect_hint')}
                                    </p>
                                </div>

                                <div className="pt-4 border-t border-border flex items-center justify-center gap-3 text-xs text-muted-foreground font-medium">
                                    <span className="flex items-center gap-1">
                                        <ShieldCheck className="w-3.5 h-3.5 text-emerald-500" />
                                        {__('quotations.guest_checkout_ssl')}
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-primary" />
                                        {__('quotations.guest_checkout_instant_invoice')}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </main>
        </div>
    );
}
