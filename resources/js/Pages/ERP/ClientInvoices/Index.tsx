import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { FileText, Eye, CreditCard, Wallet, AlertCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Invoice {
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
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
    per_page: number;
    links: PaginationLink[];
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
    client_balance?: number;
    wallet_currency?: string;
    no_client?: boolean;
}

export default function Index({
    auth,
    invoices,
    unpaid_invoices = [],
    paid_invoices = [],
    client_balance = 0.0,
    wallet_currency = 'USD',
    no_client = false,
}: IndexProps) {
    if (no_client) {
        return (
            <ERPLayout title={__('general.billing_portal')} workspaceName="Workspace" tenantId="ACTIVE" menuItems={[]}>
                <div className="max-w-[800px] mx-auto px-4 sm:px-6 py-20 font-sans">
                    <Card className="border border-red-100 shadow-xl rounded-2xl overflow-hidden bg-white">
                        <CardContent className="p-8 flex flex-col items-center text-center space-y-6">
                            <div className="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                                <AlertCircle className="w-8 h-8" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-bold text-slate-900">{__('general.portal_account_unresolved')}</h1>
                                <p className="text-sm text-slate-500 max-w-md">{__('general.your_musoftware_platform_account_is_registered_but_hasn_t_been_associated_with_an_active_client_billing_account_by_a_tenant_admin_yet')}</p>
                            </div>
                            <div className="text-xs text-slate-400 bg-slate-50 border border-slate-100 rounded-lg p-3 w-full">
                                Request your account representative or tenant admin to register your client profile using your email: <span className="font-semibold">{auth.user.email}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </ERPLayout>
        );
    }

    const totalOutstanding = unpaid_invoices.reduce((sum, inv) => sum + inv.remaining, 0);

    const columns = [
        {
            key: 'invoice_number',
            label: 'Invoice No',
            render: (row: Invoice) => (
                <Link
                    href={route('erp.client-invoices.pay', row.id)}
                    className="font-mono text-slate-900 font-semibold hover:text-indigo-600 transition-colors text-[13px]"
                >
                    {row.invoice_number}
                </Link>
            ),
        },
        {
            key: 'issued_at',
            label: 'Issued',
            render: (row: Invoice) => (
                <DateDisplay date={row.issued_at} className="text-slate-500 text-[13px]" />
            ),
        },
        {
            key: 'due_date',
            label: 'Due Date',
            render: (row: Invoice) => {
                const isOverdue =
                    row.due_date &&
                    new Date(row.due_date) < new Date() &&
                    row.status !== 'paid';
                return (
                    <DateDisplay
                        date={row.due_date}
                        className={isOverdue ? 'text-red-600 font-semibold text-[13px]' : 'text-slate-500 text-[13px]'}
                    />
                );
            },
        },
        {
            key: 'amount',
            label: 'Amount',
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.amount}
                    currency={row.currency}
                    className="font-semibold text-slate-900 text-[13px]"
                />
            ),
        },
        {
            key: 'paid_amount',
            label: 'Paid',
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.paid_amount}
                    currency={row.currency}
                    className="font-medium text-slate-500 text-[13px]"
                />
            ),
        },
        {
            key: 'remaining',
            label: 'Remaining',
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.remaining}
                    currency={row.currency}
                    className="font-semibold text-slate-900 text-[13px]"
                />
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: Invoice) => <StatusBadge status={row.status} />,
        },
        {
            key: 'actions',
            label: '',
            className: 'text-end w-[100px]',
            render: (row: Invoice) => (
                <div className="flex items-center justify-end gap-2">
                    {row.status !== 'paid' && row.status !== 'cancelled' && row.status !== 'refunded' ? (
                        <Link
                            href={route('erp.client-invoices.pay', row.id)}
                            className={buttonVariants({
                                variant: 'default',
                                size: 'sm',
                                className: 'bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs px-3 shadow-sm h-8 inline-flex items-center'
                            })}
                        >
                            <CreditCard className="me-1.5 h-3.5 w-3.5" /> Pay
                        </Link>
                    ) : (
                        <Link
                            href={route('erp.client-invoices.pay', row.id)}
                            className={buttonVariants({
                                variant: 'ghost',
                                size: 'icon-sm',
                                className: 'text-slate-400 hover:text-slate-600 inline-flex items-center justify-center'
                            })}
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                    )}
                </div>
            ),
        },
    ];

    const tableData = invoices?.data || [];

    // eslint-disable-next-line react-hooks/rules-of-hooks
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={__('general.billing_invoices')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans space-y-8">
                
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('general.billing_invoices')}</h1>
                    <p className="text-sm text-slate-500 mt-1">{__('general.view_outstanding_statements_and_settle_balances_securely_using_your_wallet')}</p>
                </div>

                {/* Dashboard Stats Panel */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* wallet Balance */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{__('general.wallet_balance')}</span>
                            <span className="text-3xl font-bold text-slate-900 tracking-tight">
                                <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                            </span>
                            <p className="text-xs text-slate-400 leading-normal">{__('general.your_current_available_balance_which_can_be_used_to_settle_outstanding_invoices_and_platform_services')}</p>
                        </div>
                        <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <Wallet className="w-6 h-6" />
                        </div>
                    </div>

                    {/* Outstanding Balance */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{__('general.total_outstanding_invoices')}</span>
                            <span className="text-3xl font-bold text-slate-900 tracking-tight">
                                <CurrencyDisplay amount={totalOutstanding} currency={wallet_currency} />
                            </span>
                            <p className="text-xs text-slate-400 leading-normal">{__('general.settle_outstanding_payments_instantly_with_your_wallet_balance')}</p>
                        </div>
                        <div className="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <FileText className="w-6 h-6" />
                        </div>
                    </div>
                </div>

                {/* Invoices List Table */}
                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-slate-900">{__('general.billing_history')}</h2>
                    </div>

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
                        emptyTitle="No invoices"
                        emptyDescription="There are currently no billing statements associated with your account."
                    />
                </div>
            </div>
        </ERPLayout>
    );
}
