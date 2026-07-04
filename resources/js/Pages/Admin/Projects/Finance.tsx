import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Wallet, PiggyBank, Receipt, Clock, TrendingDown, TrendingUp } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { MetricCard } from '@/Components/ui/MetricCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney, formatDate } from '@/lib/utils';

interface ProjectCurrency {
    id: number;
    currency: string;
    symbol?: string;
    string_format?: string;
}

interface ProjectSummary {
    name: string;
    currency: ProjectCurrency | null;
}

interface CostRow {
    id: number;
    reason: string | null;
    amount: string;
    currency_id: number | null;
    currency_code: string | null;
    business_amount: string;
    created_at: string | null;
}

interface InvoiceRow {
    id: number;
    uuid: string | null;
    status: string;
    total: string;
    paid: string;
    unpaid: string;
    currency_id: number | null;
    currency_code: string | null;
    created_at: string | null;
}

interface Summary {
    cost: string;
    paid_invoices: string;
    pending_invoices: string;
    budget: string;
    business_cost: string;
    business_currency_code: string | null;
}

interface Props {
    project: {
        id: number;
        name: string;
        description: string | null;
        status: string | null;
        archived: boolean;
        budget: string;
        client_name: string | null;
        owner_name: string | null;
        currency: ProjectCurrency | null;
    };
    summary: Summary;
    costTransactions: CostRow[];
    invoices: InvoiceRow[];
}

const STATUS_STYLES: Record<string, string> = {
    paid: 'bg-emerald-100 text-emerald-700',
    unpaid: 'bg-rose-100 text-rose-700',
    partially_paid: 'bg-amber-100 text-amber-700',
    cancelled: 'bg-slate-200 text-slate-600',
};

export default function ProjectFinance({ project, summary, costTransactions, invoices }: Props) {
    const cur = project.currency;
    const net = (Number(summary.paid_invoices) || 0) - (Number(summary.cost) || 0);
    const overBudget = (Number(summary.cost) || 0) > (Number(project.budget) || 0) && Number(project.budget) > 0;

    return (
        <AdminSidebarLayout title={__('general.cost_analysis')} header={project.name}>
            <Head title={`${__('general.cost_analysis')} — ${project.name}`} />
            <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div>
                    <Link
                        href={route('admin.projects.board.index', project.id)}
                        className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                    >
                        <ArrowLeft className="h-4 w-4" /> {__('general.back_to_project')}
                    </Link>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900">{project.name}</h1>
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${project.archived ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-700'}`}>
                            {project.archived ? __('general.archived') : (project.status ?? 'open').replace('_', ' ')}
                        </span>
                    </div>
                    {(project.client_name || project.owner_name) && (
                        <p className="mt-1 text-sm text-slate-500">
                            {project.client_name && <span>{__('general.client')}: {project.client_name}</span>}
                            {project.client_name && project.owner_name && <span className="mx-2">·</span>}
                            {project.owner_name && <span>{__('general.owner')}: {project.owner_name}</span>}
                        </p>
                    )}
                </div>

                {/* Summary metrics (all real / derivable) */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <MetricCard label={__('general.cost')} value={formatMoney(summary.cost, cur)} icon={TrendingDown} />
                    <MetricCard label={__('general.paid_invoices')} value={formatMoney(summary.paid_invoices, cur)} icon={TrendingUp} />
                    <MetricCard label={__('general.pending_invoices')} value={formatMoney(summary.pending_invoices, cur)} icon={Receipt} />
                    <MetricCard
                        label={__('general.net')}
                        value={formatMoney(String(net), cur)}
                        icon={Wallet}
                    />
                </div>

                {/* Budget vs cost context */}
                {Number(project.budget) > 0 && (
                    <Card className="rounded-xl border border-slate-200">
                        <CardContent className="p-5">
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span className="font-medium text-slate-700">{__('general.budget_vs_cost')}</span>
                                <span className={`font-mono font-semibold ${overBudget ? 'text-rose-600' : 'text-slate-900'}`}>
                                    {formatMoney(summary.cost, cur)} / {formatMoney(project.budget, cur)}
                                </span>
                            </div>
                            <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className={`h-full rounded-full ${overBudget ? 'bg-rose-500' : 'bg-emerald-500'}`}
                                    style={{ width: `${Math.min(100, (Number(summary.cost) || 0) / (Number(project.budget) || 1) * 100)}%` }}
                                />
                            </div>
                            {overBudget && (
                                <p className="mt-2 text-xs font-medium text-rose-600">{__('general.project_over_budget')}</p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Cost transactions */}
                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
                                <PiggyBank className="h-4 w-4" /> {__('general.cost_transactions')}
                            </h2>
                            <span className="text-xs text-slate-400">
                                {__('general.business_total')}: {summary.business_currency_code ?? ''} {Number(summary.business_cost || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                        </div>
                        {costTransactions.length === 0 ? (
                            <EmptyState icon={PiggyBank} title={__('general.no_cost_transactions')} description={__('general.no_cost_transactions_desc')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                            <th className="py-2 pe-4">{__('general.reason')}</th>
                                            <th className="py-2 pe-4">{__('general.amount')}</th>
                                            <th className="py-2 pe-4">{__('general.date')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {costTransactions.map((c) => (
                                            <tr key={c.id}>
                                                <td className="py-2.5 pe-4 text-slate-700">{c.reason || '—'}</td>
                                                <td className="py-2.5 pe-4 font-mono font-semibold text-rose-700">
                                                    {c.currency_code} {Number(c.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 pe-4 text-xs text-slate-400">
                                                    <Clock className="me-1 inline h-3 w-3" />
                                                    {c.created_at ? formatDate(c.created_at) : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Invoices */}
                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="p-5">
                        <h2 className="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            <Receipt className="h-4 w-4" /> {__('general.invoices')}
                        </h2>
                        {invoices.length === 0 ? (
                            <EmptyState icon={Receipt} title={__('general.no_invoices')} description={__('general.no_invoices_desc')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                            <th className="py-2 pe-4">#</th>
                                            <th className="py-2 pe-4">{__('general.status')}</th>
                                            <th className="py-2 pe-4">{__('general.total')}</th>
                                            <th className="py-2 pe-4">{__('general.paid')}</th>
                                            <th className="py-2 pe-4">{__('general.pending')}</th>
                                            <th className="py-2 pe-4">{__('general.date')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {invoices.map((inv) => (
                                            <tr key={inv.id}>
                                                <td className="py-2.5 pe-4 font-mono text-slate-500">#{inv.id}</td>
                                                <td className="py-2.5 pe-4">
                                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold capitalize ${STATUS_STYLES[inv.status] ?? 'bg-slate-100 text-slate-600'}`}>
                                                        {inv.status.replace('_', ' ')}
                                                    </span>
                                                </td>
                                                <td className="py-2.5 pe-4 font-mono text-slate-700">
                                                    {inv.currency_code} {Number(inv.total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 pe-4 font-mono font-semibold text-emerald-700">
                                                    {inv.currency_code} {Number(inv.paid).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 pe-4 font-mono font-semibold text-amber-700">
                                                    {inv.currency_code} {Number(inv.unpaid).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-2.5 pe-4 text-xs text-slate-400">
                                                    {inv.created_at ? formatDate(inv.created_at) : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
