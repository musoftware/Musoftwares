import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import GuestFrame from '@/Components/GuestFrame';
import InvoiceStatement, { InvoiceSummary } from '@/Components/Billing/InvoiceStatement';
import { Card } from '@/Components/Client/ClientPage';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const CLOSED = ['paid', 'cancelled', 'refunded'];

/** An invoice opened from a signed link, no sign-in: the same invoice view, paid by card with a name and email. */
export default function InvoiceShow({ invoice, pay_url, prefill }: { invoice: InvoiceSummary; pay_url: string; prefill?: { name?: string | null; email?: string | null } }) {
    return (
        <GuestFrame width="lg" title={__('frontend.guest_invoice_title', { id: invoice.number })}>
            <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div className="min-w-0 lg:col-span-2">
                    <InvoiceStatement invoice={invoice} />
                </div>
                <GuestPayBox invoice={invoice} payUrl={pay_url} prefill={prefill} />
            </div>
        </GuestFrame>
    );
}

function GuestPayBox({ invoice, payUrl, prefill }: { invoice: InvoiceSummary; payUrl: string; prefill?: { name?: string | null; email?: string | null } }) {
    const { data, setData, post, processing, errors } = useForm({ guest_name: prefill?.name || '', guest_email: prefill?.email || '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(payUrl);
    };

    if (CLOSED.includes(invoice.status)) {
        const text = invoice.status === 'paid' ? __('frontend.guest_invoice_already_paid') : __('frontend.guest_invoice_cancelled');
        return <Card className="h-fit text-center text-sm font-medium">{text}</Card>;
    }

    return (
        <Card className="h-fit space-y-4 lg:sticky lg:top-6">
            <div>
                <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('frontend.guest_invoice_amount_due')}</p>
                <p className="text-2xl font-bold" dir="ltr">{formatMoney(invoice.remaining, invoice.currency)}</p>
            </div>
            <form onSubmit={submit} className="space-y-4">
                <GuestField id="guest_name" label={__('general.name')} value={data.guest_name} error={errors.guest_name} onChange={(v) => setData('guest_name', v)} />
                <GuestField id="guest_email" type="email" label={__('general.email')} value={data.guest_email} error={errors.guest_email} onChange={(v) => setData('guest_email', v)} />
                <button type="submit" disabled={processing}
                    className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#0071e3] text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                    <CreditCard className="h-4 w-4" />
                    {processing ? __('general.processing') : __('general.pay_now')}
                </button>
            </form>
        </Card>
    );
}

function GuestField({ id, label, value, error, onChange, type = 'text' }: { id: string; label: string; value: string; error?: string; onChange: (v: string) => void; type?: string }) {
    return (
        <div className="space-y-1.5">
            <label htmlFor={id} className="block text-sm font-semibold">{label}</label>
            <input id={id} type={type} value={value} onChange={(e) => onChange(e.target.value)} required
                aria-invalid={Boolean(error)} aria-describedby={error ? `${id}-error` : undefined}
                className="h-11 w-full rounded-xl border border-black/10 bg-white px-3 text-base focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900" />
            {error && <p id={`${id}-error`} className="text-xs text-rose-600">{error}</p>}
        </div>
    );
}
