import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { ArrowLeft, Network, ArrowDownRight, ArrowUpRight, Receipt, Wallet, Layers } from 'lucide-react';

interface LinkedTransactionsProps {
    invoice: any;
    transactions: any[];
    costTransactions: any[];
    costLines: any[];
    walletTransactions: any[];
    counts: {
        transactions: number;
        cost_transactions: number;
        cost_lines: number;
        wallet_transactions: number;
    };
}

export default function LinkedTransactions({
    invoice,
    transactions,
    costTransactions,
    costLines,
    walletTransactions,
    counts,
}: LinkedTransactionsProps) {
    const businessCurrency = usePage<any>().props?.auth?.business_currency ?? invoice?.currency;

    const totalLinkedIncome = (transactions ?? []).reduce(
        (s, t) => s + (Number(t.business_amount) || (Number(t.amount) || 0)),
        0,
    );
    const totalLinkedCost = (costTransactions ?? []).reduce(
        (s, t) => s + (Number(t.business_amount) || (Number(t.amount) || 0)),
        0,
    );

    return (
        <AdminSidebarLayout
            title={__('admin.linked_transactions_title')}
            header={
                <div className="flex items-center gap-2 text-slate-700">
                    <Link
                        href={route('admin.invoices.show', invoice.id)}
                        className="inline-flex items-center text-slate-500 hover:text-slate-900"
                    >
                        <ArrowLeft className="w-4 h-4 me-1" />
                        {__('admin.back_to_invoice')}
                    </Link>
                </div>
            }
            actions={
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <a href={route('admin.invoices.print-pdf', invoice.id)} target="_blank" rel="noreferrer">
                            {__('admin.print_pdf')}
                        </a>
                    </Button>
                </div>
            }
        >
            <Head title={__('admin.linked_transactions_title')} />

            <div className="p-6 space-y-6">
                <Card className="shadow-sm border-slate-200">
                    <CardHeader className="pb-3 bg-gradient-to-r from-indigo-50 to-slate-50 rounded-t-lg">
                        <div className="flex items-start justify-between gap-4 flex-wrap">
                            <div>
                                <CardTitle className="text-lg flex items-center gap-2 text-slate-800">
                                    <Network className="w-5 h-5 text-indigo-500" />
                                    {__('admin.linked_transactions_title')}
                                </CardTitle>
                                <CardDescription className="mt-1">
                                    {__('admin.linked_transactions_desc')}
                                </CardDescription>
                            </div>
                            <div className="flex flex-wrap gap-2 text-xs">
                                <Badge variant="secondary" className="bg-white border">
                                    {__('admin.invoice')}: #{invoice.invoice_number}
                                </Badge>
                                <Badge variant="outline" className="capitalize">
                                    {invoice.status}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div className="text-slate-500 text-xs uppercase tracking-wide">{__('admin.client')}</div>
                            <div className="font-semibold text-slate-800 mt-1">{invoice.user?.name ?? '—'}</div>
                            {invoice.user?.email && (
                                <div className="text-xs text-slate-500">{invoice.user.email}</div>
                            )}
                        </div>
                        <div>
                            <div className="text-slate-500 text-xs uppercase tracking-wide">{__('admin.project')}</div>
                            <div className="font-semibold text-slate-800 mt-1">
                                {invoice.project?.project_name ?? '—'}
                            </div>
                        </div>
                        <div>
                            <div className="text-slate-500 text-xs uppercase tracking-wide">{__('admin.invoice_total')}</div>
                            <div className="font-semibold text-slate-800 mt-1 font-mono">
                                {formatMoney(invoice.total ?? 0, invoice.currency_id)}
                            </div>
                        </div>
                        <div>
                            <div className="text-slate-500 text-xs uppercase tracking-wide">{__('admin.outstanding')}</div>
                            <div className="font-semibold text-rose-600 mt-1 font-mono">
                                {formatMoney(invoice.unpaid ?? 0, invoice.currency_id)}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <SummaryCard
                        label={__('admin.linked_income_transactions')}
                        value={counts.transactions}
                        icon={<ArrowDownRight className="w-4 h-4 text-emerald-500" />}
                        total={formatMoney(totalLinkedIncome, businessCurrency)}
                        tone="emerald"
                    />
                    <SummaryCard
                        label={__('admin.linked_cost_transactions')}
                        value={counts.cost_transactions}
                        icon={<ArrowUpRight className="w-4 h-4 text-rose-500" />}
                        total={formatMoney(totalLinkedCost, businessCurrency)}
                        tone="rose"
                    />
                    <SummaryCard
                        label={__('admin.linked_invoice_cost_lines')}
                        value={counts.cost_lines}
                        icon={<Layers className="w-4 h-4 text-indigo-500" />}
                        total={formatMoney(
                            (costLines ?? []).reduce((s, l) => s + (Number(l.amount) || 0), 0),
                            invoice.currency_id,
                        )}
                        tone="indigo"
                    />
                    <SummaryCard
                        label={__('admin.linked_wallet_transactions')}
                        value={counts.wallet_transactions}
                        icon={<Wallet className="w-4 h-4 text-amber-500" />}
                        total={formatMoney(
                            (walletTransactions ?? []).reduce((s, l) => s + (Number(l.amount) || 0), 0),
                            invoice.currency_id,
                        )}
                        tone="amber"
                    />
                </div>

                <Section
                    icon={<ArrowDownRight className="w-4 h-4 text-emerald-600" />}
                    title={__('admin.linked_income_transactions')}
                    description={__('admin.linked_income_transactions_desc')}
                    count={counts.transactions}
                >
                    <DataBlock
                        rows={transactions}
                        empty={__('admin.no_income_transactions_linked')}
                        columns={[
                            { key: 'id', label: __('general.id'), render: (r) => <span className="font-mono text-xs text-slate-500">#{r.id}</span> },
                            {
                                key: 'user',
                                label: __('general.user'),
                                render: (r) =>
                                    r.user ? (
                                        <div className="flex flex-col">
                                            <span className="font-medium text-slate-800">{r.user.name}</span>
                                            <span className="text-xs text-slate-500">{r.user.email}</span>
                                        </div>
                                    ) : (
                                        <span className="text-slate-400">—</span>
                                    ),
                            },
                            {
                                key: 'project',
                                label: __('admin.project'),
                                render: (r) =>
                                    r.project ? <span>{r.project.project_name}</span> : <span className="text-slate-400">—</span>,
                            },
                            {
                                key: 'type',
                                label: __('general.type'),
                                render: (r) => (
                                    <span className="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 capitalize">
                                        {r.type}
                                    </span>
                                ),
                            },
                            {
                                key: 'amount',
                                label: __('general.amount'),
                                render: (r) => (
                                    <span className="font-mono font-medium text-emerald-700">
                                        {formatMoney(r.amount ?? 0, r.currency)}
                                    </span>
                                ),
                            },
                            {
                                key: 'business_amount',
                                label: __('general.business_amount'),
                                render: (r) => (
                                    <span className="font-mono text-slate-600">
                                        {formatMoney(r.business_amount ?? 0, businessCurrency)}
                                    </span>
                                ),
                            },
                            {
                                key: 'reason',
                                label: __('general.reason'),
                                render: (r) => (
                                    <span className="text-slate-600 max-w-[260px] truncate block" title={r.reason}>
                                        {r.reason}
                                    </span>
                                ),
                            },
                            {
                                key: 'created_at',
                                label: __('general.date'),
                                render: (r) =>
                                    r.created_at ? new Date(r.created_at).toLocaleDateString() : '—',
                            },
                        ]}
                    />
                </Section>

                <Section
                    icon={<ArrowUpRight className="w-4 h-4 text-rose-600" />}
                    title={__('admin.linked_cost_transactions')}
                    description={__('admin.linked_cost_transactions_desc')}
                    count={counts.cost_transactions}
                >
                    <DataBlock
                        rows={costTransactions}
                        empty={__('admin.no_cost_transactions_linked')}
                        columns={[
                            { key: 'id', label: __('general.id'), render: (r) => <span className="font-mono text-xs text-slate-500">#{r.id}</span> },
                            {
                                key: 'user',
                                label: __('general.user'),
                                render: (r) =>
                                    r.user ? (
                                        <div className="flex flex-col">
                                            <span className="font-medium text-slate-800">{r.user.name}</span>
                                            <span className="text-xs text-slate-500">{r.user.email}</span>
                                        </div>
                                    ) : (
                                        <span className="text-slate-400">—</span>
                                    ),
                            },
                            {
                                key: 'project',
                                label: __('admin.project'),
                                render: (r) =>
                                    r.project ? <span>{r.project.project_name}</span> : <span className="text-slate-400">—</span>,
                            },
                            {
                                key: 'amount',
                                label: __('general.amount'),
                                render: (r) => (
                                    <span className="font-mono font-medium text-rose-700">
                                        {formatMoney(r.amount ?? 0, r.currency)}
                                    </span>
                                ),
                            },
                            {
                                key: 'business_amount',
                                label: __('general.business_amount'),
                                render: (r) => (
                                    <span className="font-mono text-slate-600">
                                        {formatMoney(r.business_amount ?? 0, businessCurrency)}
                                    </span>
                                ),
                            },
                            {
                                key: 'reason',
                                label: __('general.reason'),
                                render: (r) => (
                                    <span className="text-slate-600 max-w-[260px] truncate block" title={r.reason}>
                                        {r.reason}
                                    </span>
                                ),
                            },
                            {
                                key: 'created_at',
                                label: __('general.date'),
                                render: (r) =>
                                    r.created_at ? new Date(r.created_at).toLocaleDateString() : '—',
                            },
                        ]}
                    />
                </Section>

                <Section
                    icon={<Layers className="w-4 h-4 text-indigo-600" />}
                    title={__('admin.linked_invoice_cost_lines')}
                    description={__('admin.linked_invoice_cost_lines_desc')}
                    count={counts.cost_lines}
                >
                    <DataBlock
                        rows={costLines}
                        empty={__('admin.no_cost_lines_linked')}
                        columns={[
                            { key: 'id', label: __('general.id'), render: (r) => <span className="font-mono text-xs text-slate-500">#{r.id}</span> },
                            {
                                key: 'line_type',
                                label: __('general.type'),
                                render: (r) => (
                                    <span className="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 capitalize">
                                        {r.line_type}
                                    </span>
                                ),
                            },
                            {
                                key: 'description',
                                label: __('general.description'),
                                render: (r) => <span className="text-slate-700">{r.description ?? '—'}</span>,
                            },
                            {
                                key: 'credit_user',
                                label: __('admin.credit_user'),
                                render: (r) => (r.credit_user ? r.credit_user.name : <span className="text-slate-400">—</span>),
                            },
                            {
                                key: 'amount',
                                label: __('general.amount'),
                                render: (r) => (
                                    <span className="font-mono font-medium text-indigo-700">
                                        {formatMoney(r.amount ?? 0, r.currency)}
                                    </span>
                                ),
                            },
                            {
                                key: 'processed',
                                label: __('general.status'),
                                render: (r) =>
                                    r.processed ? (
                                        <Badge className="bg-emerald-100 text-emerald-700">
                                            {__('admin.processed')}
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline" className="text-amber-700 border-amber-300">
                                            {__('admin.unprocessed')}
                                        </Badge>
                                    ),
                            },
                        ]}
                    />
                </Section>

                <Section
                    icon={<Wallet className="w-4 h-4 text-amber-600" />}
                    title={__('admin.linked_wallet_transactions')}
                    description={__('admin.linked_wallet_transactions_desc')}
                    count={counts.wallet_transactions}
                >
                    <DataBlock
                        rows={walletTransactions}
                        empty={__('admin.no_wallet_transactions_linked')}
                        columns={[
                            { key: 'id', label: __('general.id'), render: (r) => <span className="font-mono text-xs text-slate-500">#{r.id}</span> },
                            {
                                key: 'wallet_id',
                                label: __('admin.wallet'),
                                render: (r) => r.wallet_id ?? '—',
                            },
                            {
                                key: 'type',
                                label: __('general.type'),
                                render: (r) => (
                                    <span className="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 capitalize">
                                        {r.type ?? '—'}
                                    </span>
                                ),
                            },
                            {
                                key: 'amount',
                                label: __('general.amount'),
                                render: (r) => (
                                    <span className="font-mono font-medium text-amber-700">
                                        {formatMoney(r.amount ?? 0, r.currency)}
                                    </span>
                                ),
                            },
                            {
                                key: 'created_at',
                                label: __('general.date'),
                                render: (r) =>
                                    r.created_at ? new Date(r.created_at).toLocaleDateString() : '—',
                            },
                        ]}
                    />
                </Section>
            </div>
        </AdminSidebarLayout>
    );
}

function SummaryCard({
    label,
    value,
    icon,
    total,
    tone,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    total: string;
    tone: 'emerald' | 'rose' | 'indigo' | 'amber';
}) {
    const tones: Record<string, string> = {
        emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        rose: 'bg-rose-50 text-rose-700 border-rose-200',
        indigo: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        amber: 'bg-amber-50 text-amber-700 border-amber-200',
    };

    return (
        <div className={`rounded-lg border p-4 ${tones[tone]}`}>
            <div className="flex items-center justify-between">
                <div className="text-xs uppercase tracking-wide opacity-80">{label}</div>
                {icon}
            </div>
            <div className="mt-2 text-2xl font-bold">{value}</div>
            <div className="mt-1 text-xs font-mono opacity-80">{total}</div>
        </div>
    );
}

function Section({
    icon,
    title,
    description,
    count,
    children,
}: {
    icon: React.ReactNode;
    title: string;
    description?: string;
    count?: number;
    children: React.ReactNode;
}) {
    return (
        <Card className="shadow-sm border-slate-200">
            <CardHeader className="pb-3 border-b border-slate-100">
                <div className="flex items-center justify-between flex-wrap gap-2">
                    <CardTitle className="text-base flex items-center gap-2 text-slate-800">
                        {icon}
                        {title}
                    </CardTitle>
                    {typeof count === 'number' && (
                        <Badge variant="secondary" className="bg-slate-100">
                            {count}
                        </Badge>
                    )}
                </div>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>
            <CardContent className="pt-4">{children}</CardContent>
        </Card>
    );
}

function DataBlock({
    rows,
    columns,
    empty,
}: {
    rows: any[];
    columns: { key: string; label: string; render: (r: any) => React.ReactNode }[];
    empty: string;
}) {
    if (!rows || rows.length === 0) {
        return (
            <div className="flex items-center justify-center py-8 text-slate-400">
                <Receipt className="w-5 h-5 me-2 opacity-60" />
                <span>{empty}</span>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-slate-100 text-xs uppercase text-slate-500">
                        {columns.map((c) => (
                            <th key={c.key} className="text-start py-2 px-3 font-medium">
                                {c.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((r, idx) => (
                        <tr key={`${r.source ?? 'row'}-${r.id}-${idx}`} className="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                            {columns.map((c) => (
                                <td key={c.key} className="py-2 px-3 align-middle">
                                    {c.render(r)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
