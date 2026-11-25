import React, { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, Copy } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useConfirm } from '@/hooks/useConfirm';
import { __ } from '@/lib/i18n';
import { LastCheckIn, PriceText, StateText, SystemFields, SystemFieldsData, SystemSummary } from '@/Components/ClientSystems/shared';

interface System extends SystemSummary {
    grace_days: number;
    open_until: string | null;
    manually_locked: boolean;
    next_bill_at: string | null;
    last_check_in_ip: string | null;
    fingerprint_changed_at: string | null;
}

interface Bill {
    id: number;
    period_start: string;
    period_end: string;
    monthly_fee: string;
    sales_total: string;
    sales_percent: string;
    sales_fee: string;
    lock_at: string;
    invoice: { id: number; status: string } | null;
}

interface Sale {
    id: number;
    external_id: string;
    amount: string;
    status: string;
    sold_at: string;
    client_system_bill_id: number | null;
}

interface Props {
    system: System;
    bills: Bill[];
    sales: Sale[];
    prompt: string;
}

const day = (value: string) => new Date(value).toLocaleDateString();

function Card({ title, hint, children }: { title: string; hint?: string; children: React.ReactNode }) {
    return (
        <section className="space-y-3 rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div>
                <h2 className="font-semibold">{title}</h2>
                {hint && <p className="mt-1 text-sm text-muted-foreground">{hint}</p>}
            </div>
            {children}
        </section>
    );
}

function ChangedAlert({ system }: { system: System }) {
    if (!system.fingerprint_changed_at) return null;
    return (
        <div role="alert" className="space-y-2 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-900">
            <p className="font-semibold">{__('client_systems.alert_changed_title', { date: new Date(system.fingerprint_changed_at).toLocaleString() })}</p>
            <p>{__('client_systems.alert_changed_body')}</p>
            <Button type="button" variant="outline" className="min-h-11" onClick={() => router.post(`/admin/client-systems/${system.id}/accept-fingerprint`, {}, { preserveScroll: true })}>
                {__('client_systems.alert_changed_accept')}
            </Button>
        </div>
    );
}

function PromptCard({ prompt }: { prompt: string }) {
    const [copied, setCopied] = useState(false);
    const copy = async () => {
        await navigator.clipboard.writeText(prompt);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 2000);
    };
    return (
        <Card title={__('client_systems.prompt_title')} hint={__('client_systems.prompt_hint')}>
            <Button type="button" onClick={copy} className="min-h-11">
                {copied ? <Check size={16} aria-hidden="true" /> : <Copy size={16} aria-hidden="true" />}
                {copied ? __('client_systems.prompt_copied') : __('client_systems.prompt_copy')}
            </Button>
            <p className="text-sm text-amber-800">{__('client_systems.prompt_secret_warning')}</p>
            <details>
                <summary className="min-h-11 cursor-pointer py-2 text-sm text-blue-700 underline">{__('client_systems.prompt_show')}</summary>
                <pre dir="ltr" className="max-h-[480px] overflow-auto whitespace-pre-wrap break-words rounded bg-slate-50 p-3 text-xs">{prompt}</pre>
            </details>
        </Card>
    );
}

function PriceCard({ system }: { system: System }) {
    const form = useForm<SystemFieldsData>({
        name: system.name, site_url: system.site_url ?? '', monthly_price: String(system.monthly_price),
        sales_percent: String(system.sales_percent), grace_days: String(system.grace_days),
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/client-systems/${system.id}`, { preserveScroll: true });
    };
    return (
        <Card title={__('client_systems.price_title')} hint={__('client_systems.price_hint', { currency: system.currency ?? '' })}>
            <form onSubmit={submit} className="space-y-4">
                <SystemFields idPrefix="cs-edit" data={form.data} errors={form.errors} setData={(key, value) => form.setData(key, value)} />
                <Button type="submit" disabled={form.processing} className="min-h-11">{__('client_systems.save')}</Button>
            </form>
        </Card>
    );
}

function AccessCard({ system }: { system: System }) {
    const [openUntil, setOpenUntil] = useState(system.open_until ?? '');
    const { confirm, confirmDialog } = useConfirm();
    const post = (path: string, data = {}) => router.post(`/admin/client-systems/${system.id}/${path}`, data, { preserveScroll: true });
    const rotate = async () => {
        const accepted = await confirm({ title: __('client_systems.rotate_title'), description: __('client_systems.rotate_body'), variant: 'danger', confirmLabel: __('client_systems.rotate') });
        if (accepted) post('rotate-keys');
    };
    return (
        <Card title={__('client_systems.access_title')}>
            {confirmDialog}
            <div className="space-y-1">
                <Label htmlFor="cs-open-until">{__('client_systems.open_until')}</Label>
                <p className="text-xs text-muted-foreground">{__('client_systems.open_until_hint')}</p>
                <div className="flex flex-wrap gap-2">
                    <Input id="cs-open-until" type="date" dir="ltr" className="w-48" value={openUntil} onChange={(e) => setOpenUntil(e.target.value)} />
                    <Button type="button" variant="outline" className="min-h-11" onClick={() => post('open-until', { open_until: openUntil || null })}>{__('client_systems.save')}</Button>
                </div>
            </div>
            <div className="flex flex-wrap gap-2 border-t pt-3">
                <Button type="button" variant={system.manually_locked ? 'default' : 'outline'} className="min-h-11" onClick={() => post('toggle-lock')}>
                    {system.manually_locked ? __('client_systems.unlock') : __('client_systems.lock')}
                </Button>
                <Button type="button" variant="outline" className="min-h-11 text-red-700" onClick={rotate}>{__('client_systems.rotate')}</Button>
            </div>
        </Card>
    );
}

function BillsCard({ bills, currency }: { bills: Bill[]; currency: string | null }) {
    return (
        <Card title={__('client_systems.bills_title')} hint={__('client_systems.bills_hint', { currency: currency ?? '' })}>
            {bills.length === 0 ? <p className="text-sm text-muted-foreground">{__('client_systems.bills_empty')}</p> : (
                <ul className="divide-y text-sm">
                    {bills.map((bill) => (
                        <li key={bill.id} className="flex flex-wrap justify-between gap-2 py-2">
                            <span><bdi dir="ltr">{day(bill.period_start)} – {day(bill.period_end)}</bdi></span>
                            <span className="text-muted-foreground">
                                {__('client_systems.bill_line', { monthly: bill.monthly_fee, fee: bill.sales_fee, percent: Number(bill.sales_percent), total: bill.sales_total })}
                            </span>
                            {bill.invoice
                                ? <Link href={`/admin/invoices/${bill.invoice.id}`} className="text-blue-700 underline">{__('client_systems.bill_invoice', { id: bill.invoice.id, status: __(`client_systems.invoice_${bill.invoice.status}`) })}</Link>
                                : <span className="text-muted-foreground">{__('client_systems.bill_nothing')}</span>}
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}

function SalesCard({ sales, currency }: { sales: Sale[]; currency: string | null }) {
    return (
        <Card title={__('client_systems.sales_title')} hint={__('client_systems.sales_hint')}>
            {sales.length === 0 ? <p className="text-sm text-muted-foreground">{__('client_systems.sales_empty')}</p> : (
                <ul className="divide-y text-sm">
                    {sales.map((sale) => (
                        <li key={sale.id} className="flex flex-wrap justify-between gap-2 py-2">
                            <span dir="ltr" className="break-all">{sale.external_id}</span>
                            <span className="tabular-nums" dir="ltr">{sale.amount} {currency}</span>
                            <span className="text-muted-foreground">{__(`client_systems.sale_${sale.status}`)} · {day(sale.sold_at)}{sale.client_system_bill_id ? ` · ${__('client_systems.sale_billed')}` : ''}</span>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}

export default function Show({ system, bills, sales, prompt }: Props) {
    return (
        <AdminSidebarLayout>
            <Head title={system.name} />
            <div className="mx-auto max-w-4xl min-w-0 space-y-6 overflow-x-clip p-4 sm:p-6">
                <div className="space-y-1">
                    {system.user && <Link href={`/admin/users/${system.user.id}#client-systems`} className="inline-flex min-h-11 items-center text-sm text-blue-600 underline">{system.user.name}</Link>}
                    <h1 className="text-2xl font-bold">{system.name}</h1>
                    {system.site_url && <a href={system.site_url} target="_blank" rel="noreferrer" dir="ltr" className="block break-all text-sm text-blue-700 underline">{system.site_url}</a>}
                    <p className="text-sm"><StateText state={system.state} /> · <PriceText system={system} /></p>
                    <p className="text-sm text-muted-foreground">
                        {__('client_systems.last_check_in')}: <LastCheckIn at={system.last_check_in_at} />
                        {system.next_bill_at && <> · {__('client_systems.next_bill', { date: day(system.next_bill_at) })}</>}
                    </p>
                </div>
                <ChangedAlert system={system} />
                <PromptCard prompt={prompt} />
                <PriceCard system={system} />
                <AccessCard system={system} />
                <BillsCard bills={bills} currency={system.currency} />
                <SalesCard sales={sales} currency={system.currency} />
            </div>
        </AdminSidebarLayout>
    );
}
