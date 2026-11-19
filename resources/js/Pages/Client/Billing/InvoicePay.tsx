import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { CheckCircle2, CreditCard, Download, Wallet } from 'lucide-react';
import ClientPage, { Card, TextAction } from '@/Components/Client/ClientPage';
import InvoiceStatement, { InvoiceSummary } from '@/Components/Billing/InvoiceStatement';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface PayProps {
    invoice: InvoiceSummary;
    client_balance: number;
    wallet_currency: string | null;
    remaining_in_wallet_currency: number;
}

/** A signed-in client pays an invoice: the same invoice view, paid from the wallet or by card. */
export default function InvoicePay({ invoice, client_balance, wallet_currency, remaining_in_wallet_currency }: PayProps) {
    return (
        <ClientPage
            width="lg"
            title={__('client.invoice_pay_title', { number: invoice.number })}
            action={
                <a href={route('billing.invoices.pdf', invoice.uuid)} target="_blank" rel="noreferrer"
                    className="inline-flex h-11 items-center gap-2 rounded-full border border-black/10 px-5 text-sm font-semibold dark:border-white/15">
                    <Download className="h-4 w-4" />{__('general.download_pdf')}
                </a>
            }
        >
            <TextAction href={route('billing.invoices.index')}>{__('general.back_to_invoices')}</TextAction>
            <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div className="min-w-0 lg:col-span-2">
                    <InvoiceStatement invoice={invoice} />
                </div>
                <WalletPayBox invoice={invoice} balance={client_balance} walletCurrency={wallet_currency} dueInWallet={remaining_in_wallet_currency} />
            </div>
        </ClientPage>
    );
}

function WalletPayBox({ invoice, balance, walletCurrency, dueInWallet }: { invoice: InvoiceSummary; balance: number; walletCurrency: string | null; dueInWallet: number }) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [done, setDone] = useState<string | null>(null);
    const fromWallet = balance >= dueInWallet;

    const pay = async () => {
        setLoading(true);
        setError(null);
        try {
            const { data } = await axios.post(route('billing.invoices.pay.process', invoice.uuid));
            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }
            if (!data.success) {
                setError(data.message || __('payment.payment_failed'));
                return;
            }
            setDone(data.message || __('general.invoice_paid_successfully'));
            setTimeout(() => router.reload(), 1500);
        } catch (err: any) {
            setError(err.response?.data?.message || err.response?.data?.error || __('payment.unexpected_payment_error'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <Card className="h-fit space-y-4 lg:sticky lg:top-24">
            <div className="flex items-center gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3]"><Wallet className="h-5 w-5" /></span>
                <div>
                    <p className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_money.wallet_balance')}</p>
                    <p className="font-bold" dir="ltr">{formatMoney(balance, walletCurrency)}</p>
                </div>
            </div>

            {done && <p className="flex items-start gap-2 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"><CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />{done}</p>}
            {error && <p role="alert" className="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{error}</p>}
            {!fromWallet && <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('erp.your_wallet_balance_is_not_enough_redirecting_to_payment_gateway')}</p>}

            <button type="button" onClick={pay} disabled={loading || done !== null}
                className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#0071e3] text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                {fromWallet ? <Wallet className="h-4 w-4" /> : <CreditCard className="h-4 w-4" />}
                {loading ? __('general.processing_securely') : fromWallet ? __('payment.settle_pay_outstanding') : __('payment.pay_via_card_gateway')}
            </button>
            <p className="text-center text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                {__('general.by_checking_out_you_authorize')}{' '}
                <b dir="ltr">{fromWallet ? formatMoney(dueInWallet, walletCurrency) : formatMoney(invoice.remaining, invoice.currency)}</b>
                {fromWallet ? ` ${__('erp.from_your_platform_wallet_balance')}` : ''}
            </p>
        </Card>
    );
}
