import React, { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Building, DollarSign, Pencil, Plus, Send, Smartphone, Trash2 } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import { useConfirm } from '@/hooks/useConfirm';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const TYPES = [
    { id: 'bank_transfer', label: () => __('client.payout_tab_bank'), icon: Building, blank: { full_name: '', bank_name: '', account_number: '', routing_number: '' } },
    { id: 'vodafone_cash', label: () => __('client.payout_tab_vodafone'), icon: Smartphone, blank: { mobile_number: '' } },
    { id: 'instapay', label: () => __('client.payout_type_instapay'), icon: Send, blank: { mobile_number: '', instapay_username: '' } },
    { id: 'paypal', label: () => __('client.payout_type_paypal'), icon: DollarSign, blank: { paypal_email: '' } },
];

/** Fields per method type: [detail key, label key, input type, required]. */
const FIELDS = {
    bank_transfer: [['full_name', 'client.payout_full_name', 'text', true], ['bank_name', 'client.payout_bank_name', 'text', true], ['account_number', 'client.payout_account_number', 'text', true], ['routing_number', 'client.payout_routing_number', 'text', false]],
    vodafone_cash: [['mobile_number', 'client.payout_vodafone_mobile', 'tel', true]],
    instapay: [['instapay_username', 'client.payout_instapay_username', 'text', true], ['mobile_number', 'client.payout_mobile_optional', 'tel', false]],
    paypal: [['paypal_email', 'client.payout_paypal_email', 'email', true]],
};

const summary = (pm) => {
    const d = pm.details || {};
    if (pm.type === 'bank_transfer') return [d.full_name, d.bank_name, d.account_number ? `••••${String(d.account_number).slice(-4)}` : null].filter(Boolean).join(' · ');
    if (pm.type === 'paypal') return d.paypal_email;
    if (pm.type === 'instapay') return [d.instapay_username, d.mobile_number].filter(Boolean).join(' · ');
    return d.mobile_number;
};

/** Saved payout methods as a simple list; adding or editing opens one form card. */
export default function PayoutMethods({ payoutMethods = [] }) {
    const { confirm, confirmDialog } = useConfirm();
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({ type: 'bank_transfer', details: { ...TYPES[0].blank }, is_default: false });

    const openNew = () => {
        setEditing(null);
        reset();
        clearErrors();
        setFormOpen(true);
    };
    const openEdit = (method) => {
        setEditing(method);
        clearErrors();
        setData({ type: method.type, details: method.details || {}, is_default: !!method.is_default });
        setFormOpen(true);
    };
    const close = () => {
        setFormOpen(false);
        setEditing(null);
        reset();
    };
    const changeType = (type) => setData({ type, details: { ...TYPES.find((t) => t.id === type).blank }, is_default: data.is_default });
    const submit = (e) => {
        e.preventDefault();
        if (editing) patch(route('financial.payout-methods.update', editing.id), { onSuccess: close });
        else post(route('financial.payout-methods.store'), { onSuccess: close });
    };
    const remove = async (id) => {
        if (await confirm({ title: __('client.payout_delete_title'), description: __('client.payout_delete_confirm'), variant: 'danger' })) {
            router.delete(route('financial.payout-methods.destroy', id));
        }
    };

    return (
        <ClientPage
            width="sm"
            title={__('client_money.payout.title')}
            hint={__('client_money.payout.hint')}
            action={!formOpen && <PrimaryAction onClick={openNew} icon={<Plus className="h-4 w-4" />}>{__('client_money.payout.add')}</PrimaryAction>}
        >
            {formOpen && (
                <Card>
                    <form onSubmit={submit} className="space-y-4">
                        <h2 className="text-lg font-bold">{editing ? __('client.payout_edit_title') : __('client.payout_add_title')}</h2>
                        <fieldset>
                            <legend className="mb-2 text-sm font-semibold">{__('client_money.payout.type')}</legend>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                {TYPES.map(({ id, label, icon: Icon }) => (
                                    <button
                                        key={id}
                                        type="button"
                                        onClick={() => changeType(id)}
                                        aria-pressed={data.type === id}
                                        className={cn('flex flex-col items-center gap-1.5 rounded-2xl border p-3 text-xs font-semibold transition-colors',
                                            data.type === id ? 'border-[#0071e3] bg-[#0071e3]/5 text-[#0071e3]' : 'border-black/10 hover:bg-[#f5f5f7] dark:border-white/10 dark:hover:bg-zinc-800')}
                                    >
                                        <Icon className="h-5 w-5" />{label()}
                                    </button>
                                ))}
                            </div>
                            {errors.type && <p className="mt-1 text-sm font-medium text-rose-600">{errors.type}</p>}
                        </fieldset>

                        {FIELDS[data.type].map(([key, labelKey, type, required]) => (
                            <div key={key}>
                                <label htmlFor={key} className="mb-1.5 block text-sm font-semibold">{__(labelKey)}</label>
                                <input
                                    id={key}
                                    type={type}
                                    dir={type === 'text' ? undefined : 'ltr'}
                                    value={data.details?.[key] || ''}
                                    onChange={(e) => setData('details', { ...data.details, [key]: e.target.value })}
                                    required={required}
                                    className="h-11 w-full rounded-xl border border-black/10 bg-white px-3 text-base focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900"
                                />
                                {errors[`details.${key}`] && <p className="mt-1 text-sm font-medium text-rose-600">{errors[`details.${key}`]}</p>}
                            </div>
                        ))}
                        {errors.details && <p className="text-sm font-medium text-rose-600">{errors.details}</p>}

                        <label className="flex items-center gap-3 text-sm">
                            <input type="checkbox" checked={!!data.is_default} onChange={(e) => setData('is_default', e.target.checked)} className="h-4 w-4 rounded border-black/20 text-[#0071e3]" />
                            {__('client_money.payout.make_default')}
                        </label>

                        <div className="flex flex-wrap items-center gap-4 pt-2">
                            <button type="submit" disabled={processing} className="inline-flex h-11 items-center justify-center rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                                {__('client_money.payout.save')}
                            </button>
                            <TextAction onClick={close}>{__('client_money.cancel')}</TextAction>
                        </div>
                    </form>
                </Card>
            )}

            <Section>
                {payoutMethods.length === 0 ? (
                    <EmptyNote>{__('client_money.payout.empty')}</EmptyNote>
                ) : (
                    <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                        {payoutMethods.map((pm) => {
                            const Icon = (TYPES.find((t) => t.id === pm.type) || TYPES[0]).icon;
                            return (
                                <li key={pm.id} className="flex items-center gap-3 px-4 py-3">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3]"><Icon className="h-5 w-5" /></span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-[15px] font-medium">
                                            {__(`client.payout_type_${pm.type}`)}
                                            {pm.is_default && <span className="ms-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400">· {__('client_money.payout.default')}</span>}
                                        </p>
                                        <p className="truncate text-xs text-[#1d1d1f]/50 dark:text-zinc-500" dir="auto">{summary(pm)}</p>
                                    </div>
                                    <button type="button" onClick={() => openEdit(pm)} title={__('client_money.payout.edit')} aria-label={__('client_money.payout.edit')} className="rounded-full p-2 text-[#1d1d1f]/60 hover:bg-[#f5f5f7] dark:hover:bg-zinc-800">
                                        <Pencil className="h-4 w-4" />
                                    </button>
                                    <button type="button" onClick={() => remove(pm.id)} title={__('client_money.payout.delete')} aria-label={__('client_money.payout.delete')} className="rounded-full p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </Section>
            {confirmDialog}
        </ClientPage>
    );
}
