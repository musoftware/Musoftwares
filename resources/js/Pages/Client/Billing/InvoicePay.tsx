import React, { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import { WatermarkStamp, type WatermarkTone } from '@/Components/ui/WatermarkStamp';
import {
    ArrowLeft,
    Wallet,
    CreditCard,
    FileText,
    CheckCircle2,
    ShieldAlert,
    Download
} from 'lucide-react';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

interface InvoiceItem {
    title: string;
    quantity: number;
    unit_price: number;
    total: number;
}

interface InvoiceDetails {
    id: number;
    uuid: string;
    invoice_number: string;
    amount: number;
    paid_amount: number;
    remaining: number;
    currency: any;
    status: string;
    due_date: string;
    issued_at: string;
    items: InvoiceItem[];
}

interface PayProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    invoice: InvoiceDetails;
    client_balance: number;
    wallet_currency: any;
    remaining_in_wallet_currency: number;
}

function mapInvoiceStatusToTone(status: string): WatermarkTone {
    const normalized = status?.toLowerCase();
    if (normalized === 'paid' || normalized === 'completed') return 'paid';
    if (normalized === 'overdue') return 'overdue';
    if (
        normalized === 'unpaid' ||
        normalized === 'partially_paid' ||
        normalized === 'pending' ||
        normalized === 'processing' ||
        normalized === 'open'
    ) {
        return 'unpaid';
    }
    return 'draft';
}

export default function InvoicePay({
    auth,
    invoice,
    client_balance,
    wallet_currency,
    remaining_in_wallet_currency,
}: PayProps) {
    const { toast } = useToast();
    const [loading, setLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const hasBalance = client_balance >= remaining_in_wallet_currency;

    const canPay =
        invoice.status !== 'paid' &&
        invoice.status !== 'cancelled' &&
        invoice.status !== 'refunded';

    const handlePayment = async () => {
        setLoading(true);
        setErrorMessage(null);
        try {
            const response = await axios.post(
                route('billing.invoices.pay.process', invoice.id),
                {}
            );

            if (response.data.success) {
                if (response.data.redirect_url) {
                    window.location.href = response.data.redirect_url;
                    return;
                }
                setSuccessMessage(response.data.message || __('general.invoice_paid_successfully'));
                toast({
                    title: __('general.success'),
                    description: response.data.message || __('general.invoice_settled_via_wallet'),
                });
                setTimeout(() => {
                    router.reload();
                }, 1500);
            } else {
                setErrorMessage(response.data.message || __('payment.payment_failed'));
            }
        } catch (err: any) {
            setErrorMessage(
                err.response?.data?.message ||
                err.response?.data?.error ||
                __('payment.unexpected_payment_error')
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Invoice #${invoice.invoice_number} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] dark:bg-[#090d16] text-[#1d1d1f] dark:text-[#f8fafc] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3] transition-colors">
                
                {/* Hero Header */}
                <div className="w-full bg-white dark:bg-[#0f172a] border-b border-black/5 dark:border-white/10 py-8 px-6 sm:px-10 transition-colors">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href={route('billing.invoices.index')}
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_invoices')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] dark:text-white font-sans">
                                Invoice #{invoice.invoice_number}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 dark:text-white/60 font-sans">
                                Official billing invoice &amp; tax settlement breakdown.
                            </p>
                        </div>

                        <a
                            href={route('billing.invoices.pdf', invoice.uuid)}
                            className="px-5 py-2.5 bg-white dark:bg-zinc-800/80 hover:bg-[#f5f5f7] dark:hover:bg-zinc-700/80 text-[#1d1d1f] dark:text-white border border-black/10 dark:border-white/10 text-xs font-semibold rounded-[980px] transition-all shadow-sm flex items-center gap-2 cursor-pointer shrink-0"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Download className="w-4 h-4 text-[#1d1d1f]/60 dark:text-white/60" />
                            <span>{__('general.download_pdf')}</span>
                        </a>
                    </div>
                </div>

                {/* Main Content Grid */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        
                        {/* Invoice Statement Box (2 columns) */}
                        <div className="lg:col-span-2 bg-white dark:bg-[#0f172a] rounded-[24px] border border-black/5 dark:border-white/10 shadow-sm overflow-hidden relative transition-colors">
                            <WatermarkStamp tone={mapInvoiceStatusToTone(invoice.status)} />

                            {/* Header details */}
                            <div className="p-6 sm:p-8 border-b border-black/5 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-white/50 block mb-1">
                                        Tax Invoice Statement
                                    </span>
                                    <h2 className="text-xl font-bold font-mono text-[#1d1d1f] dark:text-white">
                                        {invoice.invoice_number}
                                    </h2>
                                </div>
                                <div>
                                    <StatusBadge status={invoice.status} />
                                </div>
                            </div>

                            {/* Dates Summary */}
                            <div className="grid grid-cols-2 border-b border-black/5 dark:border-white/10 bg-[#f5f5f7]/50 dark:bg-white/[0.02]">
                                <div className="p-5 sm:p-6 border-e border-black/5 dark:border-white/10 space-y-1">
                                    <span className="block text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-white/50">
                                        {__('general.issued_date')}
                                    </span>
                                    <DateDisplay date={invoice.issued_at} className="text-xs sm:text-sm font-semibold text-[#1d1d1f] dark:text-white" />
                                </div>
                                <div className="p-5 sm:p-6 space-y-1">
                                    <span className="block text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-white/50">
                                        {__('general.due_date')}
                                    </span>
                                    <DateDisplay date={invoice.due_date} className="text-xs sm:text-sm font-semibold text-[#1d1d1f] dark:text-white" />
                                </div>
                            </div>

                            {/* Line Items */}
                            <div className="p-6 sm:p-8 space-y-6">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-white/50 font-mono pb-2 border-b border-black/5 dark:border-white/10">
                                    {__('general.line_items')}
                                </h3>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-start border-collapse">
                                        <thead>
                                            <tr className="text-[11px] font-semibold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-white/50 border-b border-black/5 dark:border-white/10">
                                                <th className="pb-3 text-start">{__('general.description')}</th>
                                                <th className="pb-3 px-4 text-center">{__('general.qty')}</th>
                                                <th className="pb-3 px-4 text-end">{__('general.unit_price')}</th>
                                                <th className="pb-3 text-end">{__('general.total')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-black/5 dark:divide-white/10 text-xs sm:text-sm">
                                            {invoice.items.map((item, idx) => (
                                                <tr key={idx}>
                                                    <td className="py-4 pe-4 font-semibold text-[#1d1d1f] dark:text-white">
                                                        {item.title}
                                                    </td>
                                                    <td className="py-4 px-4 text-center font-mono text-[#1d1d1f]/70 dark:text-white/70">
                                                        {item.quantity}
                                                    </td>
                                                    <td className="py-4 px-4 text-end font-mono text-[#1d1d1f]/70 dark:text-white/70">
                                                        <CurrencyDisplay amount={item.unit_price} currency={invoice.currency} />
                                                    </td>
                                                    <td className="py-4 text-end font-mono font-bold text-[#1d1d1f] dark:text-white">
                                                        <CurrencyDisplay amount={item.total} currency={invoice.currency} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Financial Totals */}
                                <div className="flex flex-col items-end space-y-2 pt-6 border-t border-black/5 dark:border-white/10">
                                    <div className="flex w-64 justify-between text-xs text-[#1d1d1f]/70 dark:text-white/70">
                                        <span>{__('general.subtotal')}</span>
                                        <span className="font-mono font-semibold">
                                            <CurrencyDisplay amount={invoice.amount} currency={invoice.currency} />
                                        </span>
                                    </div>
                                    <div className="flex w-64 justify-between text-xs text-emerald-600 dark:text-emerald-400">
                                        <span>{__('general.paid_to_date')}</span>
                                        <span className="font-mono font-semibold">
                                            <CurrencyDisplay amount={invoice.paid_amount} currency={invoice.currency} />
                                        </span>
                                    </div>
                                    <div className="flex w-64 justify-between pt-3 border-t border-black/5 dark:border-white/10 text-sm sm:text-base font-bold text-[#1d1d1f] dark:text-white">
                                        <span>{__('general.total_outstanding')}</span>
                                        <span className="font-mono text-[#0071e3] dark:text-[#2997ff]">
                                            <CurrencyDisplay amount={invoice.remaining} currency={invoice.currency} />
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Checkout & Settle Card (1 column) */}
                        <div className="space-y-6">
                            <div className="bg-white dark:bg-[#0f172a] rounded-[24px] border border-black/5 dark:border-white/10 shadow-sm p-6 sm:p-7 space-y-6 transition-colors">
                                <h2 className="text-base font-bold text-[#1d1d1f] dark:text-white font-sans">
                                    {__('payment.secure_payment')}
                                </h2>

                                {/* Wallet Widget */}
                                <div className="flex items-center gap-3.5 p-4 rounded-[18px] bg-[#f5f5f7] dark:bg-white/[0.04] border border-black/5 dark:border-white/10">
                                    <div className="w-10 h-10 rounded-xl bg-[#0071e3]/10 dark:bg-[#2997ff]/20 text-[#0071e3] dark:text-[#2997ff] flex items-center justify-center shrink-0">
                                        <Wallet className="w-5 h-5" />
                                    </div>
                                    <div className="min-w-0">
                                        <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-white/50 block">
                                            {__('erp.wallet_balance')}
                                        </span>
                                        <div className="text-base font-bold text-[#1d1d1f] dark:text-white font-mono">
                                            <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                                        </div>
                                    </div>
                                </div>

                                {/* Status messages */}
                                {successMessage && (
                                    <div className="flex items-start gap-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                                        <CheckCircle2 className="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                                        <span>{successMessage}</span>
                                    </div>
                                )}

                                {errorMessage && (
                                    <Alert variant="destructive">
                                        <ShieldAlert className="h-4 w-4" />
                                        <AlertTitle>{__('payment.checkout_failed')}</AlertTitle>
                                        <AlertDescription>{errorMessage}</AlertDescription>
                                    </Alert>
                                )}

                                {/* Actions */}
                                {invoice.status === 'paid' ? (
                                    <div className="flex flex-col items-center p-6 rounded-[20px] bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-800/40 text-center space-y-2">
                                        <CheckCircle2 className="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                            {__('general.paid_statement_settled')}
                                        </span>
                                        <span className="text-[11px] text-emerald-700/80 dark:text-emerald-400/80">
                                            {__('erp.this_invoice_has_been_fully')}
                                        </span>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {!hasBalance && (
                                            <div className="flex items-start gap-2.5 rounded-[18px] border border-amber-200/60 dark:border-amber-800/40 bg-amber-50 dark:bg-amber-950/30 p-4 text-amber-800 dark:text-amber-300 text-xs">
                                                <ShieldAlert className="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                                <div>
                                                    <strong className="block font-bold mb-0.5">
                                                        {__('general.insufficient_funds')}
                                                    </strong>
                                                    <span>{__('erp.your_wallet_balance_is_not_enough_redirecting_to_payment_gateway')}</span>
                                                </div>
                                            </div>
                                        )}

                                        <button
                                            onClick={handlePayment}
                                            disabled={loading || successMessage !== null}
                                            className="w-full h-12 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] dark:bg-[#0071e3] dark:hover:bg-[#0077ed] disabled:opacity-50 text-white font-semibold text-xs sm:text-sm shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            <CreditCard className="w-4 h-4" />
                                            <span>
                                                {loading
                                                    ? __('general.processing_securely')
                                                    : hasBalance
                                                      ? __('payment.settle_pay_outstanding')
                                                      : __('payment.pay_via_card_gateway')}
                                            </span>
                                        </button>

                                        <p className="text-center text-[10px] text-[#1d1d1f]/40 dark:text-white/40 leading-normal">
                                            {__('general.by_checking_out_you_authorize')}{' '}
                                            <span className="font-semibold text-[#1d1d1f] dark:text-white">
                                                <CurrencyDisplay
                                                    amount={
                                                        hasBalance ? remaining_in_wallet_currency : invoice.remaining
                                                    }
                                                    currency={hasBalance ? wallet_currency : invoice.currency}
                                                />
                                            </span>{' '}
                                            {hasBalance ? __('erp.from_your_platform_wallet_balance') : ''}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
