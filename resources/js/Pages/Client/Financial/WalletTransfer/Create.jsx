import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Send } from 'lucide-react';
import ClientPage, { Card, Section, TextAction } from '@/Components/Client/ClientPage';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

function useDebounce(value, delay) {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const handler = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(handler);
    }, [value, delay]);
    return debounced;
}

const inputClass = 'h-11 w-full rounded-xl border border-black/10 bg-white px-3 text-base focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900';

/** Send balance to another account: find them by e-mail, enter the amount (fee shown live), confirm. */
export default function Create({ user, wallet }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [recipient, setRecipient] = useState(null);
    const [step, setStep] = useState(1);
    const [preview, setPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [previewError, setPreviewError] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ receiver_email: '', amount: '', reason: '', confirm_transfer: false });
    const currency = wallet?.currency;

    const debouncedSearch = useDebounce(searchQuery, 400);
    useEffect(() => {
        if (debouncedSearch.length < 5) {
            setSearchResults([]);
            return;
        }
        setSearching(true);
        fetch(route('financial.transfer.search-users') + `?q=${encodeURIComponent(debouncedSearch)}`)
            .then((res) => res.json())
            .then((json) => setSearchResults(json.users || []))
            .catch(() => setSearchResults([]))
            .finally(() => setSearching(false));
    }, [debouncedSearch]);

    const debouncedAmount = useDebounce(data.amount, 500);
    useEffect(() => {
        if (!recipient || !debouncedAmount || parseFloat(debouncedAmount) <= 0) {
            setPreview(null);
            setPreviewError(null);
            return;
        }
        setPreviewLoading(true);
        setPreviewError(null);
        fetch(route('financial.transfer.calculate-fee') + `?amount=${debouncedAmount}&receiver_email=${encodeURIComponent(recipient.email)}`)
            .then(async (res) => {
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || __('client.transfer_estimate_failed'));
                return json;
            })
            .then((json) => (json.success ? setPreview(json.preview) : setPreviewError(json.message)))
            .catch((err) => setPreviewError(err.message))
            .finally(() => setPreviewLoading(false));
    }, [debouncedAmount, recipient]);

    const pick = (match) => {
        setRecipient(match);
        setData('receiver_email', match.email);
        setSearchQuery('');
        setSearchResults([]);
        setStep(2);
    };
    const changeRecipient = () => {
        setRecipient(null);
        setData('receiver_email', '');
        setPreview(null);
        setStep(1);
    };
    const submit = (e) => {
        e.preventDefault();
        post(route('financial.transfer.store'));
    };

    const money = (amount, cur) => formatMoney(Number(amount || 0), cur || currency);
    const total = preview ? Number(preview.amount) + Number(preview.fee) : 0;

    return (
        <ClientPage
            width="sm"
            title={__('client_money.transfer.title')}
            hint={__('client_money.transfer.hint')}
            action={<TextAction href={route('financial.transfer.history')}>{__('client_money.transfer.history')}</TextAction>}
        >
            <Card className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                {__('client_money.wallet_balance')}: <b dir="ltr">{money(wallet?.balance)}</b>
            </Card>

            {step === 1 && (
                <Section title={__('client_money.transfer.who')}>
                    <Card className="space-y-3">
                        <label htmlFor="receiver_search" className="block text-sm font-semibold">{__('client_money.transfer.email')}</label>
                        <input id="receiver_search" type="email" dir="ltr" autoComplete="off" value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} className={inputClass} />
                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_money.transfer.email_hint')}</p>
                        {searching && <p className="text-sm text-[#1d1d1f]/60">{__('client_money.transfer.searching')}</p>}
                        {!searching && searchResults.length > 0 && (
                            <ul className="divide-y divide-black/5 rounded-xl border border-black/10 dark:divide-white/10 dark:border-white/10">
                                {searchResults.map((match) => (
                                    <li key={match.email}>
                                        <button type="button" onClick={() => pick(match)} className="flex w-full items-center justify-between gap-3 px-4 py-3 text-start hover:bg-[#f5f5f7] dark:hover:bg-zinc-800">
                                            <span className="min-w-0">
                                                <span className="block truncate text-[15px] font-medium">{match.name}</span>
                                                <span className="block truncate text-xs text-[#1d1d1f]/50" dir="ltr">{match.email}</span>
                                            </span>
                                            <ArrowRight className="h-4 w-4 shrink-0 text-[#1d1d1f]/40 rtl:rotate-180" />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {!searching && searchResults.length === 0 && debouncedSearch.length >= 5 && (
                            <p className="text-sm text-[#1d1d1f]/60">{__('client_money.transfer.not_found')}</p>
                        )}
                        {errors.receiver_email && <p className="text-sm font-medium text-rose-600">{errors.receiver_email}</p>}
                    </Card>
                </Section>
            )}

            {step >= 2 && recipient && (
                <Card className="flex items-center justify-between gap-3">
                    <span className="min-w-0">
                        <span className="block text-xs text-[#1d1d1f]/50">{__('client_money.transfer.to')}</span>
                        <span className="block truncate font-semibold">{recipient.name}</span>
                        <span className="block truncate text-xs text-[#1d1d1f]/50" dir="ltr">{recipient.email}</span>
                    </span>
                    <TextAction onClick={changeRecipient}>{__('client_money.transfer.change')}</TextAction>
                </Card>
            )}

            {step === 2 && recipient && (
                <Card className="space-y-4">
                    <div>
                        <label htmlFor="amount" className="mb-1.5 block text-sm font-semibold">{__('client_money.transfer.amount', { currency: currency || '' })}</label>
                        <input id="amount" type="number" step="0.01" min="0" inputMode="decimal" dir="ltr" value={data.amount} onChange={(e) => setData('amount', e.target.value)} className={inputClass} />
                        {errors.amount && <p className="mt-1 text-sm font-medium text-rose-600">{errors.amount}</p>}
                    </div>
                    <div>
                        <label htmlFor="reason" className="mb-1.5 block text-sm font-semibold">{__('client_money.transfer.note')}</label>
                        <input id="reason" value={data.reason} placeholder={__('client_money.transfer.note_ph')} onChange={(e) => setData('reason', e.target.value)} className={inputClass} />
                        {errors.reason && <p className="mt-1 text-sm font-medium text-rose-600">{errors.reason}</p>}
                    </div>

                    {previewLoading && <p className="text-sm text-[#1d1d1f]/60">{__('client_money.transfer.calculating')}</p>}
                    {!previewLoading && previewError && <p className="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{previewError}</p>}
                    {!previewLoading && preview && (
                        <dl className="space-y-2 rounded-2xl bg-[#f5f5f7] p-4 text-sm dark:bg-zinc-800">
                            <Row label={__('client_money.transfer.fee')} value={money(preview.fee, preview.currency)} />
                            <Row label={__('client_money.transfer.total')} value={money(total, preview.currency)} bold />
                            <Row label={__('client_money.transfer.receives')} value={money(preview.converted_amount, preview.converted_currency)} />
                            {preview.requires_conversion && (
                                <p className="text-xs text-[#1d1d1f]/50" dir="ltr">{__('client_money.transfer.rate', { from: preview.currency, rate: Number(preview.exchange_rate).toFixed(4), to: preview.converted_currency })}</p>
                            )}
                            {preview.remaining_limit !== undefined && preview.remaining_limit !== null && (
                                <p className="text-xs text-[#1d1d1f]/50">{__('client_money.transfer.limit', { amount: money(preview.remaining_limit, preview.currency) })}</p>
                            )}
                        </dl>
                    )}

                    <button type="button" disabled={!preview || !!previewError || previewLoading} onClick={() => setStep(3)} className="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                        {__('client_money.continue')}<ArrowRight className="h-4 w-4 rtl:rotate-180" />
                    </button>
                </Card>
            )}

            {step === 3 && recipient && preview && (
                <form onSubmit={submit}>
                    <Card className="space-y-4">
                        <p className="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />{__('client_money.transfer.final')}
                        </p>
                        <dl className="space-y-2 text-sm">
                            <Row label={__('client_money.transfer.from')} value={user?.name} />
                            <Row label={__('client_money.transfer.total')} value={money(total, preview.currency)} bold />
                            <Row label={__('client_money.transfer.receives')} value={money(preview.converted_amount, preview.converted_currency)} />
                        </dl>
                        <label className="flex items-start gap-3 text-sm">
                            <input type="checkbox" checked={data.confirm_transfer} onChange={(e) => setData('confirm_transfer', e.target.checked)} className="mt-0.5 h-4 w-4 rounded border-black/20 text-[#0071e3]" />
                            {__('client_money.transfer.authorize', { amount: money(preview.amount, preview.currency), name: recipient.name })}
                        </label>
                        {errors.confirm_transfer && <p className="text-sm font-medium text-rose-600">{errors.confirm_transfer}</p>}
                        <div className="flex flex-wrap items-center gap-4">
                            <button type="submit" disabled={processing || !data.confirm_transfer} className="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                                <Send className="h-4 w-4" />{__('client_money.transfer.send', { amount: money(preview.amount, preview.currency) })}
                            </button>
                            <TextAction onClick={() => setStep(2)}>{__('client_money.back')}</TextAction>
                        </div>
                    </Card>
                </form>
            )}
        </ClientPage>
    );
}

function Row({ label, value, bold = false }) {
    return (
        <div className="flex items-center justify-between gap-4">
            <dt className="text-[#1d1d1f]/60 dark:text-zinc-400">{label}</dt>
            <dd className={bold ? 'font-bold' : 'font-medium'} dir="ltr">{value}</dd>
        </div>
    );
}
