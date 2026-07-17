import React, { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import { WatermarkStamp, type WatermarkTone } from '@/Components/ui/WatermarkStamp';
import {
    ArrowLeft,
    Wallet,
    CreditCard,
    ChevronRight,
    FileText,
    CheckCircle2,
    ShieldAlert,
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
                setSuccessMessage(__('erp.invoice_payment_successfully_processed'));
                toast({
                    title: __('payment.payment_successful'),
                    description: response.data.message || __('erp.your_invoice_has_been_settled'),
                    variant: 'default',
                });
                if (response.data.gateway) {
                    window.location.href = response.data.redirect_url;
                } else {
                    setTimeout(() => {
                        router.visit(response.data.redirect_url);
                    }, 2000);
                }
            } else {
                setErrorMessage(response.data.message || __('payment.payment_processing_failed'));
            }
        } catch (error: any) {
            console.error('Payment Error:', error);
            const msg = (error as any).response?.data?.message || __('payment.an_error_occurred_during_payment');
            setErrorMessage(msg);
            toast({
                title: __('payment.payment_failed'),
                description: msg,
                variant: 'destructive',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${__('erp.invoice_2')} #${invoice.invoice_number}`} />

            <div className="max-w-[1000px] mx-auto space-y-6 bg-slate-100 px-4 py-8 font-sans sm:px-6 sm:py-10 rounded-3xl">
                {/* Navigation Back */}
                <div className="flex items-center gap-2">
                    <Link
                        href={route('billing.invoices.index')}
                        className={buttonVariants({
                            variant: 'ghost',
                            size: 'sm',
                            className: 'text-slate-500 hover:text-slate-900 inline-flex items-center',
                        })}
                    >
                        <ArrowLeft className="me-1.5 h-4 w-4" /> {__('erp.back_to_invoices')}
                    </Link>
                    <ChevronRight className="h-4 w-4 text-slate-300" />
                    <span className="text-sm font-semibold text-slate-900">
                        {__('erp.invoice_2')} #{invoice.invoice_number}
                    </span>
                </div>

                {canPay && (
                    <div className="sticky top-20 z-10 flex justify-end">
                        <Button
                            onClick={handlePayment}
                            disabled={loading || successMessage !== null}
                            className="h-11 rounded-xl bg-slate-900 px-5 font-semibold text-white shadow-sm transition-all duration-150 hover:bg-slate-800"
                        >
                            <CreditCard className="me-2 h-4 w-4" />
                            {loading
                                ? __('general.processing_securely')
                                : __('general.pay_invoice')}
                        </Button>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    {/* Invoice Detail Card */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="relative overflow-hidden rounded-2xl bg-white paper-shadow">
                            <WatermarkStamp tone={mapInvoiceStatusToTone(invoice.status)} />

                            {/* Receipt Header */}
                            <div className="relative z-10 flex flex-col gap-4 bg-slate-900 p-6 text-white sm:flex-row sm:items-start sm:justify-between sm:p-8">
                                <div className="space-y-1.5">
                                    <div className="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-300">
                                        <FileText className="h-3.5 w-3.5" /> {__('billing.billing_statement')}
                                    </div>
                                    <h1 className="text-2xl font-bold tracking-tight font-sans tabular-nums">
                                        {invoice.invoice_number}
                                    </h1>
                                </div>
                                <div className="flex flex-col items-end space-y-2 text-end">
                                    <div className="space-y-1.5">
                                        <span className="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                                            {__('general.status')}
                                        </span>
                                        <StatusBadge status={invoice.status} />
                                    </div>
                                    <a
                                        href={route('billing.invoices.pdf', invoice.uuid)}
                                        className={buttonVariants({
                                            variant: 'outline',
                                            size: 'sm',
                                            className:
                                                'mt-1 border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white',
                                        })}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <FileText className="me-1.5 h-3.5 w-3.5" /> {__('general.download_pdf')}
                                    </a>
                                </div>
                            </div>

                            {/* Dates Summary */}
                            <div className="relative z-10 grid grid-cols-2 border-b border-slate-100 bg-slate-50/50">
                                <div className="space-y-1 border-e border-slate-100 p-4 sm:p-6">
                                    <span className="block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                        {__('general.issued_date')}
                                    </span>
                                    <DateDisplay
                                        date={invoice.issued_at}
                                        className="text-sm font-medium text-slate-800"
                                    />
                                </div>
                                <div className="space-y-1 p-4 sm:p-6">
                                    <span className="block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                        {__('general.due_date')}
                                    </span>
                                    <DateDisplay
                                        date={invoice.due_date}
                                        className="text-sm font-semibold text-slate-900"
                                    />
                                </div>
                            </div>

                            {/* Items List */}
                            <div className="relative z-10 space-y-6 p-6 sm:p-8">
                                <h3 className="border-b border-slate-100 pb-3 text-sm font-semibold uppercase tracking-wider text-slate-900">
                                    {__('general.line_items')}
                                </h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-slate-100">
                                        <thead>
                                            <tr className="text-start text-xs font-semibold uppercase tracking-wider text-slate-400">
                                                <th className="pb-3 pe-4">{__('general.description')}</th>
                                                <th className="pb-3 px-4 text-center">{__('general.qty')}</th>
                                                <th className="pb-3 px-4 text-end">{__('general.unit_price')}</th>
                                                <th className="pb-3 ps-4 text-end">{__('general.total')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 text-[13px] text-slate-700">
                                            {invoice.items.map((item, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/40">
                                                    <td className="max-w-[250px] truncate py-4 pe-4 font-medium text-slate-900">
                                                        {item.title}
                                                    </td>
                                                    <td className="py-4 px-4 text-center font-sans font-medium tabular-nums">
                                                        {item.quantity}
                                                    </td>
                                                    <td className="py-4 px-4 text-end font-sans font-medium tabular-nums">
                                                        <CurrencyDisplay
                                                            amount={item.unit_price}
                                                            currency={invoice.currency}
                                                        />
                                                    </td>
                                                    <td className="py-4 ps-4 text-end font-sans font-semibold tabular-nums text-slate-900">
                                                        <CurrencyDisplay amount={item.total} currency={invoice.currency} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Financial Summary */}
                                <div className="flex flex-col items-end space-y-2.5 border-t border-slate-100 pt-6">
                                    <div className="flex w-64 justify-between text-sm text-slate-500">
                                        <span>{__('general.subtotal')}</span>
                                        <span className="font-sans font-medium tabular-nums">
                                            <CurrencyDisplay amount={invoice.amount} currency={invoice.currency} />
                                        </span>
                                    </div>
                                    <div className="flex w-64 justify-between text-sm text-slate-500">
                                        <span>{__('general.paid_to_date')}</span>
                                        <span className="font-sans font-medium tabular-nums text-emerald-600">
                                            <CurrencyDisplay
                                                amount={invoice.paid_amount}
                                                currency={invoice.currency}
                                            />
                                        </span>
                                    </div>
                                    <div className="flex w-64 justify-between border-t border-slate-100 pt-3 text-base font-bold text-slate-900">
                                        <span>{__('general.total_outstanding')}</span>
                                        <span className="font-sans tabular-nums text-indigo-600">
                                            <CurrencyDisplay
                                                amount={invoice.remaining}
                                                currency={invoice.currency}
                                            />
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Checkout / Payment Section */}
                    <div className="space-y-6">
                        <div className="space-y-6 rounded-2xl border border-slate-100 bg-white paper-shadow p-6">
                            <h2 className="text-lg font-semibold text-slate-900">{__('payment.secure_payment')}</h2>

                            {/* Wallet Info Widget */}
                            <div className="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                    <Wallet className="h-5 w-5" />
                                </div>
                                <div className="min-w-0 space-y-0.5">
                                    <span className="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        {__('erp.wallet_balance')}
                                    </span>
                                    <span className="block text-lg font-bold tracking-tight text-slate-900">
                                        <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                                    </span>
                                    <span className="block text-[10px] leading-normal text-slate-500">
                                        {__('erp.platform_wallet')}
                                    </span>
                                </div>
                            </div>

                            {/* Status messages */}
                            {successMessage && (
                                <div className="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
                                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                                    <div className="text-xs font-semibold leading-normal">{successMessage}</div>
                                </div>
                            )}

                            {errorMessage && (
                                <Alert variant="destructive">
                                    <ShieldAlert className="h-4 w-4" />
                                    <AlertTitle>{__('payment.checkout_failed')}</AlertTitle>
                                    <AlertDescription>{errorMessage}</AlertDescription>
                                </Alert>
                            )}

                            {/* Payment Actions */}
                            {invoice.status === 'paid' ? (
                                <div className="flex flex-col items-center space-y-2 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-center text-emerald-800">
                                    <CheckCircle2 className="h-8 w-8 text-emerald-600" />
                                    <span className="text-sm font-semibold">
                                        {__('general.paid_statement_settled')}
                                    </span>
                                    <span className="text-xs text-slate-500">
                                        {__('erp.this_invoice_has_been_fully')}
                                    </span>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {!hasBalance && (
                                        <div className="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
                                            <ShieldAlert className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                                            <div className="text-xs leading-normal">
                                                <span className="mb-1 block font-bold">
                                                    {__('general.insufficient_funds')}
                                                </span>
                                                {__('erp.your_wallet_balance_is_not_enough_redirecting_to_payment_gateway')}
                                            </div>
                                        </div>
                                    )}

                                    <Button
                                        onClick={handlePayment}
                                        disabled={loading || successMessage !== null}
                                        className="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-slate-900 font-semibold text-white shadow-sm transition-all duration-150 hover:bg-slate-800"
                                    >
                                        <CreditCard className="h-4 w-4" />
                                        {loading
                                            ? __('general.processing_securely')
                                            : hasBalance
                                              ? __('payment.settle_pay_outstanding')
                                              : __('payment.pay_via_card_gateway')}
                                    </Button>

                                    <p className="text-center text-[10px] leading-normal text-slate-400">
                                        {__('general.by_checking_out_you_authorize')}{' '}
                                        <span className="font-semibold">
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
        </AuthenticatedLayout>
    );
}
