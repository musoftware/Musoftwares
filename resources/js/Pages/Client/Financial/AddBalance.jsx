import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { AlertCircle, Check, CreditCard, ShieldCheck } from 'lucide-react';
import ClientPage, { Card, Section } from '@/Components/Client/ClientPage';
import { cn, formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const MINIMUM = 5;

/** Top up the wallet: pick an amount (or type one) and pay by card on Kashier. */
export default function AddBalance({ wallet, presets = [] }) {
    const defaultAmount = presets.length > 0 ? presets[0] : 50;
    const [selectedPreset, setSelectedPreset] = useState(defaultAmount);
    const [customAmount, setCustomAmount] = useState('');
    const { data, setData, post, processing, errors } = useForm({ amount: defaultAmount });

    const currency = wallet?.currency;
    const pickPreset = (value) => {
        setSelectedPreset(value);
        setCustomAmount('');
        setData('amount', value);
    };
    const typeAmount = (e) => {
        setCustomAmount(e.target.value);
        setSelectedPreset(null);
        const num = parseFloat(e.target.value);
        setData('amount', !isNaN(num) && num > 0 ? num : 0);
    };
    const submit = (e) => {
        e.preventDefault();
        post(route().has('financial.add-balance.kashier') ? route('financial.add-balance.kashier') : '/financial/add-balance/kashier');
    };

    return (
        <ClientPage
            width="sm"
            title={__('client_money.add.title')}
            hint={__('client_money.add.hint')}
        >
            <Card className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                {__('client_money.wallet_balance')}: <b dir="ltr">{formatMoney(Number(wallet?.balance || 0), currency)}</b>
            </Card>

            {Object.keys(errors || {}).length > 0 && (
                <div className="flex items-start gap-3 rounded-[16px] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                    <AlertCircle className="mt-0.5 h-5 w-5 shrink-0" />
                    <div>
                        <p className="font-semibold">{__('client_money.add.error')}</p>
                        {Object.values(errors).map((err, i) => <p key={i}>{err}</p>)}
                    </div>
                </div>
            )}

            <form onSubmit={submit}>
                <Section title={__('client_money.add.choose')}>
                    <Card className="space-y-5">
                        {presets.length > 0 && (
                            <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                {presets.map((amount) => {
                                    const active = selectedPreset === amount;
                                    return (
                                        <button
                                            type="button"
                                            key={amount}
                                            onClick={() => pickPreset(amount)}
                                            aria-pressed={active}
                                            className={cn('flex h-12 items-center justify-center gap-1.5 rounded-full text-sm font-semibold transition-colors',
                                                active ? 'bg-[#1d1d1f] text-white dark:bg-white dark:text-[#1d1d1f]' : 'bg-[#f5f5f7] text-[#1d1d1f] hover:bg-black/5 dark:bg-zinc-800 dark:text-zinc-100')}
                                        >
                                            {active && <Check className="h-4 w-4 text-emerald-400" />}
                                            <span dir="ltr">{formatMoney(amount, currency)}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}

                        <div>
                            <label htmlFor="custom-amount" className="mb-1.5 block text-sm font-semibold">{__('client_money.add.other')}</label>
                            <div className="relative max-w-xs">
                                <span className="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-[#1d1d1f]/40">{currency}</span>
                                <input
                                    id="custom-amount"
                                    type="number"
                                    step="0.01"
                                    min={MINIMUM}
                                    inputMode="decimal"
                                    placeholder="0.00"
                                    value={customAmount}
                                    onChange={typeAmount}
                                    dir="ltr"
                                    className="h-12 w-full rounded-xl border border-black/10 bg-white pe-4 ps-16 text-base font-semibold focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900"
                                />
                            </div>
                            <p className="mt-1.5 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_money.add.minimum', { amount: formatMoney(MINIMUM, currency) })}</p>
                            {errors.amount && <p className="mt-1 text-sm font-medium text-rose-600">{errors.amount}</p>}
                        </div>
                    </Card>
                </Section>

                <div className="mt-6 space-y-3">
                    <button
                        type="submit"
                        disabled={processing || !data.amount || data.amount < MINIMUM}
                        className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white shadow-sm transition-colors hover:bg-[#0077ed] disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    >
                        <CreditCard className="h-4 w-4" />
                        {processing ? __('client_money.add.redirecting') : __('client_money.add.pay', { amount: formatMoney(data.amount || 0, currency) })}
                    </button>
                    <p className="flex items-start gap-2 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                        <ShieldCheck className="h-4 w-4 shrink-0 text-emerald-600" />
                        {__('client_money.add.secure')}
                    </p>
                </div>
            </form>
        </ClientPage>
    );
}
