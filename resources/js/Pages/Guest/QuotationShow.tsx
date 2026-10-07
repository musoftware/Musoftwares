import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { 
    CheckCircle2, Server, Code, ShieldCheck, Printer, 
    ArrowLeft, ExternalLink, CreditCard, Sparkles, 
    Lock, Calendar, HelpCircle, FileText, Check, PhoneCall
} from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { __ } from '@/lib/i18n';

interface QuotationItem {
    id: number;
    type: 'our_work' | 'indicative_cost';
    title: string;
    description?: string;
    price: number;
    quantity: number;
    total: number;
    external_link?: string;
    link_label?: string;
}

interface Quotation {
    id: number;
    uuid: string;
    quotation_number: string;
    title: string;
    currency: string;
    deposit_percentage: number;
    development_total: number;
    indicative_total: number;
    grand_total: number;
    deposit_amount: number;
    remaining_amount: number;
    valid_until?: string;
    scope_markdown?: string;
    created_at: string;
    items: QuotationItem[];
}

interface QuotationShowProps {
    quotation: Quotation;
    checkoutUrl: string;
}

export default function QuotationShow({ quotation, checkoutUrl }: QuotationShowProps) {
    const ourWorkItems = quotation.items?.filter(it => it.type === 'our_work') || [];
    const indicativeItems = quotation.items?.filter(it => it.type === 'indicative_cost') || [];

    const handlePrint = () => {
        window.print();
    };

    return (
        <div className="min-h-screen bg-background text-foreground selection:bg-indigo-500 selection:text-white pb-32">
            <Head title={__('quotations.guest_page_title', { title: quotation.title })} />

            {/* Top Brand Header */}
            <header className="bg-card/80 backdrop-blur-md border-b border-border sticky top-0 z-30 print:static print:border-none">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="w-9 h-9 fill-current text-foreground" />
                        <div>
                            <span className="font-extrabold text-lg tracking-tight text-foreground block leading-none">
                                MUSOFTWARE
                            </span>
                            <span className="text-[10px] text-muted-foreground font-medium tracking-wider uppercase">
                                {__('quotations.guest_brand_tagline')}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handlePrint}
                            className="hidden sm:inline-flex items-center gap-1.5 text-xs text-muted-foreground border-border print:hidden"
                        >
                            <Printer className="w-3.5 h-3.5" />
                            {__('quotations.guest_print')}
                        </Button>

                        <Link href={checkoutUrl} className="print:hidden">
                            <Button className="text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all gap-1.5">
                                <Sparkles className="w-4 h-4 text-amber-400" />
                                {__('quotations.guest_accept_and_pay', { pct: quotation.deposit_percentage })}
                            </Button>
                        </Link>
                    </div>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-4 sm:px-6 pt-8 sm:pt-12 space-y-8">
                {/* Hero / Proposal Title Banner */}
                <div className="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white p-6 sm:p-10 rounded-3xl shadow-xl relative overflow-hidden border border-border">
                    <div className="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
                    <div className="relative z-10 space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="font-mono text-xs uppercase tracking-widest text-indigo-300 font-bold bg-white/10 px-3 py-1 rounded-full border border-white/10">
                                {__('quotations.guest_official_quotation')} • {quotation.quotation_number}
                            </span>
                            {quotation.valid_until && (
                                <span className="text-xs text-slate-300 flex items-center gap-1 font-medium">
                                    <Calendar className="w-3.5 h-3.5 text-indigo-400" />
                                    {__('quotations.guest_valid_until', { date: new Date(quotation.valid_until).toLocaleDateString('ar-EG') })}
                                </span>
                            )}
                        </div>

                        <h1 className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            {quotation.title}
                        </h1>

                        <p className="text-sm sm:text-base text-slate-300 max-w-2xl leading-relaxed">
                            {__('quotations.guest_intro')}
                        </p>

                        <div className="pt-4 flex flex-wrap items-center gap-4 text-xs text-slate-300">
                            <span className="flex items-center gap-1.5">
                                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                                {__('quotations.guest_quality_guarantee')}
                            </span>
                            <span className="flex items-center gap-1.5">
                                <Lock className="w-4 h-4 text-indigo-400" />
                                {__('quotations.guest_secure_payment')}
                            </span>
                            <span className="flex items-center gap-1.5">
                                <CheckCircle2 className="w-4 h-4 text-amber-400" />
                                {__('quotations.guest_start_after_deposit', { pct: quotation.deposit_percentage })}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Financial Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {/* Dev Total */}
                    <Card className="border-border shadow-sm bg-card text-card-foreground">
                        <CardHeader className="pb-2">
                            <span className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                {__('quotations.guest_dev_total')}
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl sm:text-3xl font-extrabold font-mono text-foreground">
                                {quotation.development_total} <span className="text-sm font-normal text-muted-foreground">{quotation.currency}</span>
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">{__('quotations.guest_dev_total_desc')}</p>
                        </CardContent>
                    </Card>

                    {/* Deposit Due Now (Highlight) */}
                    <Card className="border-emerald-500/30 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-md relative overflow-hidden">
                        <div className="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none" />
                        <CardHeader className="pb-2">
                            <span className="text-xs font-bold text-emerald-100 uppercase tracking-wider flex items-center gap-1">
                                <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                                {__('quotations.guest_deposit_title', { pct: quotation.deposit_percentage })}
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl sm:text-4xl font-extrabold font-mono text-white">
                                {quotation.deposit_amount} <span className="text-sm font-normal text-emerald-100">{quotation.currency}</span>
                            </div>
                            <p className="text-xs text-emerald-100 mt-1 font-medium">{__('quotations.guest_deposit_desc')}</p>
                        </CardContent>
                    </Card>

                    {/* Remaining */}
                    <Card className="border-border shadow-sm bg-card text-card-foreground">
                        <CardHeader className="pb-2">
                            <span className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                {__('quotations.guest_remaining_title', { pct: 100 - Number(quotation.deposit_percentage) })}
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl sm:text-3xl font-extrabold font-mono text-foreground">
                                {quotation.remaining_amount} <span className="text-sm font-normal text-muted-foreground">{quotation.currency}</span>
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">{__('quotations.guest_remaining_desc')}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Scope of Work & Deliverables (Markdown) */}
                {quotation.scope_markdown && (
                    <Card className="border-border shadow-sm bg-card text-card-foreground overflow-hidden">
                        <CardHeader className="border-b border-border bg-muted/40 py-4">
                            <CardTitle className="text-base font-bold text-foreground flex items-center gap-2">
                                <FileText className="w-4 h-4 text-indigo-500" />
                                {__('quotations.guest_scope_title')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 sm:p-8">
                            <div className="prose prose-slate dark:prose-invert max-w-none prose-headings:font-bold prose-h3:text-lg prose-h3:mt-6 prose-p:text-muted-foreground prose-li:text-muted-foreground">
                                <MDEditor.Markdown source={quotation.scope_markdown} />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Development Pricing Items Table */}
                <Card className="border-border shadow-sm bg-card text-card-foreground overflow-hidden">
                    <CardHeader className="border-b border-border bg-muted/40 py-4">
                        <CardTitle className="text-base font-bold text-foreground flex items-center gap-2">
                            <Code className="w-4 h-4 text-emerald-500" />
                            {__('quotations.guest_items_title')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-right border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 border-b border-border text-xs font-semibold text-muted-foreground uppercase">
                                        <th className="py-3.5 px-6">{__('quotations.guest_col_item')}</th>
                                        <th className="py-3.5 px-4 text-center">{__('quotations.guest_col_qty')}</th>
                                        <th className="py-3.5 px-4 text-left">{__('quotations.guest_col_unit_price')}</th>
                                        <th className="py-3.5 px-6 text-left">{__('quotations.guest_col_total')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {ourWorkItems.map((item, idx) => (
                                        <tr key={item.id || idx} className="hover:bg-muted/30 transition-colors">
                                            <td className="py-4 px-6">
                                                <div className="font-bold text-foreground">{item.title}</div>
                                                {item.description && (
                                                    <p className="text-xs text-muted-foreground mt-1 max-w-xl leading-relaxed">{item.description}</p>
                                                )}
                                            </td>
                                            <td className="py-4 px-4 text-center font-mono text-muted-foreground">
                                                {item.quantity}
                                            </td>
                                            <td className="py-4 px-4 text-left font-mono text-muted-foreground">
                                                {item.price} {quotation.currency}
                                            </td>
                                            <td className="py-4 px-6 text-left font-mono font-bold text-foreground">
                                                {item.total} {quotation.currency}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="bg-muted/50 font-bold text-foreground border-t border-border">
                                        <td colSpan={3} className="py-4 px-6 text-left">{__('quotations.guest_items_total')}</td>
                                        <td className="py-4 px-6 text-left font-mono text-base text-foreground">
                                            {quotation.development_total} {quotation.currency}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Indicative External Costs (Hosting / Domain / SMS) */}
                {indicativeItems.length > 0 && (
                    <Card className="border-amber-500/20 bg-amber-500/5 shadow-sm overflow-hidden">
                        <CardHeader className="border-b border-amber-500/20 bg-amber-500/10 py-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <CardTitle className="text-base font-bold text-amber-600 dark:text-amber-400 flex items-center gap-2">
                                    <Server className="w-4 h-4 text-amber-500" />
                                    {__('quotations.guest_indicative_title')}
                                </CardTitle>
                                <Badge variant="outline" className="bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30 text-xs w-fit">
                                    {__('quotations.guest_indicative_badge')}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="divide-y divide-amber-500/15">
                                {indicativeItems.map((item, idx) => (
                                    <div key={item.id || idx} className="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div className="space-y-1">
                                            <h4 className="font-bold text-foreground text-sm">{item.title}</h4>
                                            {item.description && <p className="text-xs text-muted-foreground">{item.description}</p>}
                                            {item.external_link && (
                                                <a
                                                    href={item.external_link}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold mt-1"
                                                >
                                                    <ExternalLink className="w-3.5 h-3.5" />
                                                    {item.link_label || __('quotations.guest_provider_link')}
                                                </a>
                                            )}
                                        </div>

                                        <div className="text-left font-mono">
                                            <span className="text-xs text-muted-foreground block">{__('quotations.guest_estimated')}</span>
                                            <span className="font-bold text-amber-600 dark:text-amber-400 text-base">{item.total} {quotation.currency}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Payment Gateways Badges Section */}
                <Card className="border-border shadow-sm bg-card text-card-foreground p-6 sm:p-8">
                    <div className="text-center space-y-2 mb-6">
                        <h3 className="text-base font-bold text-foreground flex items-center justify-center gap-2">
                            <CreditCard className="w-5 h-5 text-indigo-500" />
                            {__('quotations.guest_gateways_title')}
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            {__('quotations.guest_gateways_desc')}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                        {/* Visa */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <span className="font-extrabold text-[#1A1F71] dark:text-[#6a7df7] text-sm italic">VISA</span>
                        </div>

                        {/* Mastercard */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <div className="flex -space-x-2">
                                <div className="w-4 h-4 rounded-full bg-[#EB001B]" />
                                <div className="w-4 h-4 rounded-full bg-[#F79E1B] opacity-80" />
                            </div>
                            <span>Mastercard</span>
                        </div>

                        {/* Meeza */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <span className="font-extrabold text-[#00828A] dark:text-[#2dd4bf]">{__('quotations.guest_gateway_meeza')}</span>
                        </div>

                        {/* Kashier */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <span className="font-extrabold text-indigo-600 dark:text-indigo-400 font-mono">Kashier</span>
                        </div>

                        {/* Instapay */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <span className="font-extrabold text-[#9b26b6] dark:text-[#c084fc]">InstaPay</span>
                        </div>

                        {/* Vodafone Cash / Wallets */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <span className="font-extrabold text-[#E60000] dark:text-[#f87171]">{__('quotations.guest_gateway_wallets')}</span>
                        </div>

                        {/* Bank Transfer */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-border bg-muted/40 text-xs font-bold text-foreground shadow-xs">
                            <span>{__('quotations.guest_gateway_bank')}</span>
                        </div>
                    </div>
                </Card>
            </main>

            {/* Sticky Bottom Action Bar */}
            <div className="fixed bottom-0 inset-x-0 bg-card/95 backdrop-blur-md border-t border-border py-4 px-4 sm:px-6 shadow-2xl z-40 print:hidden">
                <div className="max-w-5xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold shrink-0">
                            {quotation.deposit_percentage}%
                        </div>
                        <div>
                            <span className="text-xs text-muted-foreground block">{__('quotations.guest_deposit_now')}</span>
                            <span className="font-mono text-xl sm:text-2xl font-extrabold text-foreground">
                                {quotation.deposit_amount} {quotation.currency}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href={checkoutUrl} className="w-full sm:w-auto">
                            <Button className="w-full sm:w-auto text-sm sm:text-base font-bold px-8 py-6 rounded-2xl shadow-lg hover:shadow-xl transition-all gap-2">
                                <Sparkles className="w-5 h-5 text-amber-400" />
                                {__('quotations.guest_approve_and_pay', { pct: quotation.deposit_percentage })}
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
