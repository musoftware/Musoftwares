import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, router } from '@inertiajs/react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Plus, Search, FileText, Download, MoreHorizontal, Eye, Edit, Send, Wallet, CheckCircle } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { EmptyState } from '@/Components/ui/EmptyState';

export default function Index({ invoices, stats, filters }) {
    // ── DataTable columns using {key, label, render} format ─────────────────
    const columns = [
        {
            key: 'invoice_number',
            label: 'Invoice No',
            render: (row) => (
                <Link
                    href={route('erp.invoices.show', row.id)}
                    className="font-mono text-slate-900 font-medium hover:text-indigo-600 transition-colors text-[13px]"
                >
                    {row.invoice_number}
                </Link>
            ),
        },
        {
            key: 'client',
            label: 'Client',
            render: (row) => (
                <div className="flex items-center gap-2.5">
                    <Avatar className="h-7 w-7 shrink-0">
                        <AvatarFallback className="bg-slate-100 text-slate-600 text-[11px] font-semibold">
                            {row.client?.name?.substring(0, 2).toUpperCase() || '??'}
                        </AvatarFallback>
                    </Avatar>
                    <span className="font-medium text-slate-800 text-[13px]">{row.client?.name || '—'}</span>
                </div>
            ),
        },
        {
            key: 'issued_at',
            label: 'Issued',
            render: (row) => (
                <DateDisplay date={row.issued_at} className="text-slate-500 text-[13px]" />
            ),
        },
        {
            key: 'due_date',
            label: 'Due',
            render: (row) => {
                const isOverdue =
                    row.due_date &&
                    new Date(row.due_date) < new Date() &&
                    row.status !== 'paid';

                return (
                    <DateDisplay
                        date={row.due_date}
                        className={isOverdue ? 'text-red-600 font-medium text-[13px]' : 'text-slate-500 text-[13px]'}
                    />
                );
            },
        },
        {
            key: 'amount',
            label: 'Amount',
            render: (row) => (
                <CurrencyDisplay
                    amount={row.amount}
                    currency={row.amount_currency}
                    className="font-medium"
                />
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: 'actions',
            label: '',
            className: 'text-right w-[80px]',
            render: (row) => (
                <div className="flex items-center justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        asChild
                        className="text-slate-400 hover:text-slate-700"
                    >
                        <Link href={route('erp.invoices.show', row.id)}>
                            <Eye className="h-4 w-4" />
                        </Link>
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                className="text-slate-400 hover:text-slate-700"
                            >
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            {!['paid', 'cancelled', 'refunded'].includes(row.status) && (
                                <DropdownMenuItem asChild>
                                    <Link href={route('erp.invoices.edit', row.id)}>
                                        <Edit className="mr-2 h-4 w-4 text-slate-400" /> Edit
                                    </Link>
                                </DropdownMenuItem>
                            )}
                            {row.status === 'draft' && (
                                <DropdownMenuItem
                                    onClick={() => router.post(route('erp.invoices.send', row.id))}
                                >
                                    <Send className="mr-2 h-4 w-4 text-slate-400" />{__('general.issue_invoice')}</DropdownMenuItem>
                            )}
                            {(row.status === 'sent' || row.status === 'partial') && (
                                <>
                                    <DropdownMenuItem
                                        onClick={() => router.post(route('erp.invoices.mark-paid', row.id))}
                                    >
                                        <CheckCircle className="mr-2 h-4 w-4 text-slate-400" />{__('general.mark_as_paid')}</DropdownMenuItem>
                                    <DropdownMenuItem
                                        onClick={() => router.post(route('erp.invoices.pay-wallet', row.id))}
                                    >
                                        <Wallet className="mr-2 h-4 w-4 text-slate-400" />{__('general.pay_with_wallet')}</DropdownMenuItem>
                                </>
                            )}
                            <DropdownMenuItem
                                onClick={() => router.get(route('erp.invoices.download', row.id))}
                            >
                                <Download className="mr-2 h-4 w-4 text-slate-400" />{__('general.download_pdf')}</DropdownMenuItem>
                            {(row.status === 'sent' || row.status === 'partial' || row.status === 'paid') && (
                                <DropdownMenuItem
                                    onClick={() => router.post(route('erp.invoices.cancel', row.id))}
                                    className="text-rose-600 hover:text-rose-700 focus:text-rose-700"
                                >
                                    <CheckCircle className="mr-2 h-4 w-4 text-rose-500" />{__('general.cancel_invoice')}</DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            ),
        },
    ];

    const handleFilterChange = (key, value) => {
        router.get(
            route('erp.invoices.index'),
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    const tableData = Array.isArray(invoices?.data) ? invoices.data : Array.isArray(invoices) ? invoices : [];
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title="Invoices" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans space-y-8">

                {/* ── Header ───────────────────────────────────────────────── */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">Invoices</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.manage_billing_track_payments_and_follow_up_on_overdues')}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" className="shadow-none border-slate-200">
                            <FileText className="mr-2 h-4 w-4" /> Export
                        </Button>
                        <Button asChild size="sm" className="shadow-none bg-slate-900 text-white hover:bg-slate-800">
                            <Link href={route('erp.invoices.create')}>
                                <Plus className="mr-2 h-4 w-4" />{__('general.new_invoice')}</Link>
                        </Button>
                    </div>
                </div>

                {/* ── Stats Row ────────────────────────────────────────────── */}
                {stats && (
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        {[
                            { label: 'Total Billed', value: stats.total, color: 'text-slate-900' },
                            { label: 'Paid', value: stats.paid, color: 'text-emerald-600' },
                            { label: 'Pending', value: stats.pending, color: 'text-amber-600' },
                            { label: 'Overdue', value: stats.overdue, color: 'text-red-600' },
                        ].map(({ label, value, color }) => (
                            <div
                                key={label}
                                className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm"
                            >
                                <p className={`text-xs font-semibold uppercase tracking-wider mb-1.5 ${color}`}>
                                    {label}
                                </p>
                                <div className="text-2xl font-bold tracking-tight text-slate-900">
                                    <CurrencyDisplay amount={value} currency={stats.business_currency} />
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* ── Filters & Search ─────────────────────────────────────── */}
                <div className="bg-white rounded-xl border border-slate-100 shadow-sm p-2 flex flex-col md:flex-row gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <Input
                            placeholder={__('general.search_invoice_number_or_client')}
                            className="pl-9 h-10 shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors"
                            defaultValue={filters?.search}
                            onBlur={(e) => handleFilterChange('search', e.target.value)}
                        />
                    </div>
                    <div className="flex items-center gap-2 overflow-x-auto">
                        <select
                            className="h-10 rounded-lg border-transparent bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-slate-300 transition-colors"
                            defaultValue={filters?.status}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                        >
                            <option value="">{__('general.all_statuses')}</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <Input
                            type="date"
                            className="h-10 w-auto text-sm shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors"
                            onChange={(e) => handleFilterChange('start_date', e.target.value)}
                        />
                        <span className="text-slate-300 font-medium">–</span>
                        <Input
                            type="date"
                            className="h-10 w-auto text-sm shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors"
                            onChange={(e) => handleFilterChange('end_date', e.target.value)}
                        />
                    </div>
                </div>

                {/* ── Data Table ───────────────────────────────────────────── */}
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
                    emptyTitle="No invoices yet"
                    emptyDescription="Create your first invoice to start tracking payments."
                />
            </div>
        </ERPLayout>
    );
}
