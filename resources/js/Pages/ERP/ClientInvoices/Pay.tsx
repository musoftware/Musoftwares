import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, router } from '@inertiajs/react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import { ArrowLeft, Wallet, CreditCard, ChevronRight, FileText, CheckCircle2, ShieldAlert } from 'lucide-react';
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
    currency: string;
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
    wallet_currency: string;
}

export default function Pay({
    auth,
    invoice,
    client_balance,
    wallet_currency,
}: PayProps) {
    const { toast } = useToast();
    const [loading, setLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const hasBalance = client_balance >= invoice.remaining;

    const handlePayment = async () => {
        setLoading(true);
        setErrorMessage(null);
        try {
            const response = await axios.post(
                route('erp.client-invoices.pay.wallet', invoice.id),
                { amount: invoice.remaining }
            );

            if (response.data.success) {
                setSuccessMessage('Invoice payment successfully processed!');
                toast({
                    title: 'Payment Successful',
                    description: response.data.message || 'Your invoice has been settled.',
                    variant: 'default',
                });
                setTimeout(() => {
                    router.visit(response.data.redirect_url);
                }, 2000);
            } else {
                setErrorMessage(response.data.message || 'Payment processing failed.');
            }
        } catch (error: any) {
            console.error('Payment Error:', error);
            const msg = (error as any).response?.data?.message || 'An error occurred during payment processing.';
            setErrorMessage(msg);
            toast({
                title: 'Payment Failed',
                description: msg,
                variant: 'destructive',
            });
        } finally {
            setLoading(false);
        }
    };

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={`Invoice #${invoice.invoice_number}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            
            <div className="max-w-[1000px] mx-auto px-4 sm:px-6 py-10 font-sans space-y-6">
                {/* Navigation Back */}
                <div className="flex items-center gap-2">
                    <Link
                        href={route('erp.client-invoices.index')}
                        className={buttonVariants({
                            variant: 'ghost',
                            size: 'sm',
                            className: 'text-slate-500 hover:text-slate-900 inline-flex items-center'
                        })}
                    >
                        <ArrowLeft className="me-1.5 h-4 w-4" />{__('general.back_to_invoices')}</Link>
                    <ChevronRight className="h-4 w-4 text-slate-300" />
                    <span className="text-sm font-semibold text-slate-900 font-mono">Invoice #{invoice.invoice_number}</span>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Invoice Detail Card */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                            {/* Receipt Header */}
                            <div className="p-6 sm:p-8 bg-slate-900 text-white flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                <div className="space-y-1.5">
                                    <div className="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 text-xs font-medium border border-slate-700">
                                        <FileText className="w-3.5 h-3.5" />{__('general.billing_statement')}</div>
                                    <h1 className="text-2xl font-bold font-mono tracking-tight">{invoice.invoice_number}</h1>
                                </div>
                                <div className="text-end sm:text-end space-y-1.5">
                                    <span className="text-xs text-slate-400 block uppercase tracking-wider font-semibold">{__('general.status')}</span>
                                    <StatusBadge status={invoice.status} />
                                </div>
                            </div>

                            {/* Dates Summary */}
                            <div className="grid grid-cols-2 border-b border-slate-100 bg-slate-50/50">
                                <div className="p-4 sm:p-6 border-e border-slate-100 space-y-1">
                                    <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">{__('general.issued_date')}</span>
                                    <DateDisplay date={invoice.issued_at} className="text-sm font-medium text-slate-800" />
                                </div>
                                <div className="p-4 sm:p-6 space-y-1">
                                    <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">{__('general.due_date')}</span>
                                    <DateDisplay date={invoice.due_date} className="text-sm font-semibold text-slate-900" />
                                </div>
                            </div>

                            {/* Items List */}
                            <div className="p-6 sm:p-8 space-y-6">
                                <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3">{__('general.line_items')}</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-slate-100">
                                        <thead>
                                            <tr className="text-start text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                                <th className="pb-3 pe-4">{__('general.description')}</th>
                                                <th className="pb-3 px-4 text-center">Qty</th>
                                                <th className="pb-3 px-4 text-end">{__('general.unit_price')}</th>
                                                <th className="pb-3 ps-4 text-end">{__('general.total')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 text-[13px] text-slate-700">
                                            {invoice.items.map((item, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/40">
                                                    <td className="py-4 pe-4 font-medium text-slate-900 max-w-[250px] truncate">{item.title}</td>
                                                    <td className="py-4 px-4 text-center font-mono">{item.quantity}</td>
                                                    <td className="py-4 px-4 text-end font-mono">
                                                        <CurrencyDisplay amount={item.unit_price} currency={invoice.currency} />
                                                    </td>
                                                    <td className="py-4 ps-4 text-end font-semibold font-mono text-slate-900">
                                                        <CurrencyDisplay amount={item.total} currency={invoice.currency} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Financial Summary */}
                                <div className="border-t border-slate-100 pt-6 flex flex-col items-end space-y-2.5">
                                    <div className="flex justify-between w-64 text-sm text-slate-500">
                                        <span>{__('general.subtotal')}</span>
                                        <span className="font-mono font-medium">
                                            <CurrencyDisplay amount={invoice.amount} currency={invoice.currency} />
                                        </span>
                                    </div>
                                    <div className="flex justify-between w-64 text-sm text-slate-500">
                                        <span>{__('general.paid_to_date')}</span>
                                        <span className="font-mono font-medium text-emerald-600">
                                            <CurrencyDisplay amount={invoice.paid_amount} currency={invoice.currency} />
                                        </span>
                                    </div>
                                    <div className="flex justify-between w-64 text-base font-bold text-slate-900 border-t border-slate-100 pt-3">
                                        <span>{__('general.total_outstanding')}</span>
                                        <span className="font-mono text-indigo-600">
                                            <CurrencyDisplay amount={invoice.remaining} currency={invoice.currency} />
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Checkout / Payment Section */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                            <h2 className="text-lg font-semibold text-slate-900">{__('general.secure_payment')}</h2>
                            
                            {/* Wallet Info Widget */}
                            <div className="bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-start gap-3">
                                <div className="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <Wallet className="w-5 h-5" />
                                </div>
                                <div className="space-y-0.5 min-w-0">
                                    <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">{__('general.wallet_balance')}</span>
                                    <span className="text-lg font-bold text-slate-900 tracking-tight block">
                                        <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                                    </span>
                                    <span className="text-[10px] text-slate-500 leading-normal block">{__('general.platform_wallet')}</span>
                                </div>
                            </div>

                            {/* Status messages */}
                            {successMessage && (
                                <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl flex items-start gap-2.5">
                                    <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                                    <div className="text-xs font-semibold leading-normal">{successMessage}</div>
                                </div>
                            )}

                            {errorMessage && (
                                <Alert variant="destructive">
                                    <ShieldAlert className="w-4 h-4" />
                                    <AlertTitle>{__('general.checkout_failed')}</AlertTitle>
                                    <AlertDescription>{errorMessage}</AlertDescription>
                                </Alert>
                            )}

                            {/* Payment Actions */}
                            {invoice.status === 'paid' ? (
                                <div className="bg-emerald-50 border border-emerald-100 text-emerald-800 p-4 rounded-xl flex flex-col items-center text-center space-y-2">
                                    <CheckCircle2 className="w-8 h-8 text-emerald-600" />
                                    <span className="text-sm font-semibold">{__('general.paid_statement_settled')}</span>
                                    <span className="text-xs text-slate-500">{__('general.this_invoice_has_been_fully_paid_no_further_action_is_required')}</span>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {!hasBalance && (
                                        <div className="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl flex items-start gap-2.5">
                                            <ShieldAlert className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                            <div className="text-xs leading-normal">
                                                <span className="font-bold block mb-1">{__('general.insufficient_funds')}</span>{__('general.your_wallet_balance_is_not_sufficient_to_settle_this_invoice_please_top_up_your_wallet_balance')}</div>
                                        </div>
                                    )}

                                    <Button
                                        onClick={handlePayment}
                                        disabled={loading || !hasBalance || successMessage !== null}
                                        className="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white font-semibold shadow-sm transition-all duration-150 rounded-xl flex items-center justify-center gap-2"
                                    >
                                        <CreditCard className="w-4 h-4" />
                                        {loading ? 'Processing Securely...' : `Settle & Pay Outstanding`}
                                    </Button>

                                    <p className="text-[10px] text-slate-400 text-center leading-normal">{__('general.by_checking_out_you_authorize_immediate_debit_of')}<span className="font-semibold">{invoice.remaining.toFixed(2)} {invoice.currency}</span>{__('general.from_your_platform_wallet_balance')}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
