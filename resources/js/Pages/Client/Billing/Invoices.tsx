import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { PageShell } from '@/Components/ui/PageShell';
import { PageHeroHeader } from '@/Components/ui/PageHeroHeader';
import { BentoStatCard } from '@/Components/ui/BentoStatCard';
import { ContentCard } from '@/Components/ui/ContentCard';
import { FileText, CreditCard, Wallet, Eye, Download, Plus } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Invoice {
    id: number;
    uuid: string;
    invoice_number: string;
    amount: number;
    paid_amount: number;
    remaining: number;
    wallet_amount?: number;
    wallet_remaining?: number;
    currency: any;
    status: string;
    due_date: string;
    issued_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface IndexProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    invoices?: {
        data: Invoice[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
        per_page: number;
        links: PaginationLink[];
    };
    unpaid_invoices?: Invoice[];
    paid_invoices?: Invoice[];
    total_outstanding?: number;
    client_balance?: number;
    wallet_currency?: any;
}

export default function Invoices({
    auth,
    invoices,
    unpaid_invoices = [],
    paid_invoices = [],
    total_outstanding,
    client_balance = 0.0,
    wallet_currency = 'USD',
}: IndexProps) {
    const totalOutstanding = total_outstanding !== undefined
        ? total_outstanding
        : unpaid_invoices
            .filter((inv) => inv.status === 'unpaid' || inv.status === 'partially_paid')
            .reduce((sum, inv) => sum + inv.remaining, 0);

    const columns = [
        {
            key: 'invoice_number',
            label: __('erp.invoice_no'),
            render: (row: Invoice) => (
                <Link
                    href={route('billing.invoices.pay', row.uuid)}
                    className="font-mono text-[#1d1d1f] dark:text-[#f8fafc] font-semibold hover:text-[#0071e3] dark:hover:text-[#2997ff] transition-colors text-xs sm:text-sm"
                >
                    {row.invoice_number}
                </Link>
            ),
        },
        {
            key: 'issued_at',
            label: __('general.issued'),
            render: (row: Invoice) => (
                <DateDisplay date={row.issued_at} className="text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 text-xs font-sans" />
            ),
        },
        {
            key: 'due_date',
            label: __('general.due_date'),
            render: (row: Invoice) => {
                const isOverdue =
                    row.due_date &&
                    new Date(row.due_date) < new Date() &&
                    row.status !== 'paid';
                return (
                    <DateDisplay
                        date={row.due_date}
                        className={isOverdue ? 'text-rose-600 dark:text-rose-400 font-semibold text-xs' : 'text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 text-xs'}
                    />
                );
            },
        },
        {
            key: 'amount',
            label: __('general.amount'),
            render: (row: Invoice) => {
                const isDifferentCurrency =
                    row.wallet_amount !== undefined &&
                    row.currency &&
                    wallet_currency &&
                    ((row.currency.id && wallet_currency.id && row.currency.id !== wallet_currency.id) ||
                     (row.currency.currency && wallet_currency.currency && row.currency.currency !== wallet_currency.currency));

                return (
                    <CurrencyDisplay
                        amount={row.amount}
                        currency={row.currency}
                        businessAmount={isDifferentCurrency ? row.wallet_amount : undefined}
                        businessCurrency={wallet_currency}
                        className="font-bold text-[#1d1d1f] dark:text-[#f8fafc] text-xs sm:text-sm font-mono"
                    />
                );
            },
        },
        {
            key: 'paid_amount',
            label: __('general.paid'),
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.paid_amount}
                    currency={row.currency}
                    className="font-medium text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 text-xs font-mono"
                />
            ),
        },
        {
            key: 'remaining',
            label: __('general.remaining'),
            render: (row: Invoice) => {
                const isDifferentCurrency =
                    row.wallet_remaining !== undefined &&
                    row.currency &&
                    wallet_currency &&
                    ((row.currency.id && wallet_currency.id && row.currency.id !== wallet_currency.id) ||
                     (row.currency.currency && wallet_currency.currency && row.currency.currency !== wallet_currency.currency));

                return (
                    <CurrencyDisplay
                        amount={row.remaining}
                        currency={row.currency}
                        businessAmount={isDifferentCurrency ? row.wallet_remaining : undefined}
                        businessCurrency={wallet_currency}
                        className="font-bold text-[#1d1d1f] dark:text-[#f8fafc] text-xs sm:text-sm font-mono"
                    />
                );
            },
        },
        {
            key: 'status',
            label: __('general.status'),
            render: (row: Invoice) => <StatusBadge status={row.status} />,
        },
        {
            key: 'actions',
            label: '',
            className: 'text-end w-[110px]',
            render: (row: Invoice) => (
                <div className="flex items-center justify-end gap-1.5">
                    {row.status !== 'paid' && row.status !== 'cancelled' && row.status !== 'refunded' ? (
                        <Link
                            href={route('billing.invoices.pay', row.uuid)}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs px-3 py-1.5 rounded-[980px] shadow-sm shadow-blue-500/20 inline-flex items-center gap-1 transition-all"
                        >
                            <CreditCard className="w-3 h-3" />
                            <span>{__('payment.pay')}</span>
                        </Link>
                    ) : (
                        <Link
                            href={route('billing.invoices.pay', row.uuid)}
                            className="w-8 h-8 rounded-full bg-[#f5f5f7] dark:bg-zinc-800 hover:bg-black/5 dark:hover:bg-zinc-700 text-[#1d1d1f]/60 dark:text-zinc-300 hover:text-[#1d1d1f] dark:hover:text-white inline-flex items-center justify-center transition-colors"
                            title="View"
                        >
                            <Eye className="w-3.5 h-3.5" />
                        </Link>
                    )}
                    <a
                        href={route('billing.invoices.pdf', row.uuid)}
                        className="w-8 h-8 rounded-full bg-[#f5f5f7] dark:bg-zinc-800 hover:bg-black/5 dark:hover:bg-zinc-700 text-[#1d1d1f]/60 dark:text-zinc-300 hover:text-[#1d1d1f] dark:hover:text-white inline-flex items-center justify-center transition-colors"
                        target="_blank"
                        rel="noreferrer"
                        title={__('general.download')}
                    >
                        <Download className="w-3.5 h-3.5" />
                    </a>
                </div>
            ),
        },
    ];

    const tableData = invoices?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title={`${__('erp.billing_invoices')} — Musoftwares Studio`} />

            <div className="w-full">
                {/* Hero Header */}
                <PageHeroHeader
                    backHref="/dashboard"
                    backLabel={__('general.back_to_dashboard')}
                    title={__('erp.billing_invoices')}
                    description={__('erp.view_outstanding_statements_and_settle')}
                    actions={
                        <Link
                            href={route('financial.add-balance')}
                            className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer shrink-0"
                        >
                            <Plus className="w-4 h-4" />
                            <span>{__('general.charge_balance')}</span>
                        </Link>
                    }
                />

                {/* Main Content Area */}
                <PageShell maxWidth="7xl" className="space-y-8">
                    {/* Dashboard Bento Stats Panel */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* 1. Wallet Balance */}
                        <BentoStatCard
                            label={__('erp.wallet_balance')}
                            value={<CurrencyDisplay amount={client_balance} currency={wallet_currency} />}
                            description={__('erp.your_current_available_balance_which')}
                            icon={Wallet}
                            accentColor="emerald"
                        />

                        {/* 2. Outstanding Balance */}
                        <BentoStatCard
                            label={__('erp.total_outstanding_invoices')}
                            value={<CurrencyDisplay amount={totalOutstanding} currency={wallet_currency} />}
                            description={__('erp.settle_outstanding_payments_instantly_with')}
                            icon={FileText}
                            accentColor="blue"
                        />
                    </div>

                    {/* Invoices List Table Card */}
                    <ContentCard
                        title={__('billing.billing_history')}
                        subtitle="Track all issued and settled invoices with official tax statements."
                    >
                        <DataTable
                            columns={columns}
                            data={tableData}
                            pagination={
                                invoices?.links
                                    ? {
                                          current_page: invoices.current_page,
                                          last_page: invoices.last_page,
                                          total: invoices.total,
                                          from: invoices.from,
                                          to: invoices.to,
                                          per_page: invoices.per_page,
                                          links: invoices.links,
                                      }
                                    : undefined
                            }
                            emptyIcon={FileText}
                            emptyTitle={__('erp.no_invoices')}
                            emptyDescription={__('billing.there_are_currently_no_billing')}
                        />
                    </ContentCard>
                </PageShell>
            </div>
        </AuthenticatedLayout>
    );
}
