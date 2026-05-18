import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Plus, Search, FileText, Download, MoreHorizontal, Eye, Edit, Send, Calendar as CalendarIcon, Filter } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';

export default function Index({ invoices, stats, filters }) {
    const columns = [
        {
            header: 'Invoice No',
            cell: (row) => (
                <Link href={route('erp.invoices.show', row.id)} className="font-mono text-primary font-medium hover:underline">
                    {row.invoice_number}
                </Link>
            )
        },
        {
            header: 'Client',
            cell: (row) => (
                <div className="flex items-center gap-2">
                    <Avatar className="h-8 w-8">
                        <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">{row.client?.name?.substring(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                    <span className="font-medium text-foreground">{row.client?.name}</span>
                </div>
            )
        },
        {
            header: 'Issued',
            accessorKey: 'issued_at',
            cell: (row) => <span className="text-muted-foreground">{row.issued_at ? new Date(row.issued_at).toLocaleDateString() : '-'}</span>
        },
        {
            header: 'Due',
            accessorKey: 'due_date',
            cell: (row) => {
                const isOverdue = new Date(row.due_date) < new Date() && row.status !== 'paid';
                return (
                    <span className={isOverdue ? 'text-destructive font-medium' : 'text-muted-foreground'}>
                        {new Date(row.due_date).toLocaleDateString()}
                    </span>
                );
            }
        },
        {
            header: 'Amount',
            cell: (row) => (
                <span className="font-mono font-medium text-foreground">
                    <CurrencyDisplay amount={row.amount} currency={row.amount_currency} />
                </span>
            )
        },
        {
            header: 'Status',
            cell: (row) => <StatusBadge status={row.status} />
        },
        {
            header: 'Actions',
            cell: (row) => (
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" asChild className="h-8 w-8 text-muted-foreground hover:text-foreground">
                        <Link href={route('erp.invoices.show', row.id)}>
                            <Eye className="h-4 w-4" />
                        </Link>
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground hover:text-foreground">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={route('erp.invoices.edit', row.id)}>
                                    <Edit className="mr-2 h-4 w-4" /> Edit
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => router.post(route('erp.invoices.send', row.id))}>
                                <Send className="mr-2 h-4 w-4" /> Send
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => router.get(route('erp.invoices.download', row.id))}>
                                <Download className="mr-2 h-4 w-4" /> Download PDF
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            )
        }
    ];

    const handleFilterChange = (key, value) => {
        router.get(route('erp.invoices.index'), { ...filters, [key]: value }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout header="Invoices">
            <Head title="Invoices" />
            <div className="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans space-y-8">
                
                {/* ──────────────────────────────────────────────────────── */}
                {/* HEADER & QUICK ACTIONS */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">Invoices</h1>
                        <p className="text-sm text-slate-500 mt-1">Manage billing, track payments, and follow up on overdues.</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" className="shadow-sm border-slate-200">
                            <FileText className="mr-2 h-4 w-4" /> Export
                        </Button>
                        <Button asChild size="sm" className="shadow-sm bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                            <Link href={route('erp.invoices.create')}>
                                <Plus className="mr-2 h-4 w-4" /> New Invoice
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* ──────────────────────────────────────────────────────── */}
                {/* STATS ROW */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1">Total Billed</div>
                        <div className="text-2xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={stats.total} currency={stats.business_currency} />
                        </div>
                    </div>
                    <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-emerald-600 mb-1">Paid</div>
                        <div className="text-2xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={stats.paid} currency={stats.business_currency} />
                        </div>
                    </div>
                    <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-amber-600 mb-1">Pending</div>
                        <div className="text-2xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={stats.pending} currency={stats.business_currency} />
                        </div>
                    </div>
                    <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-rose-600 mb-1">Overdue</div>
                        <div className="text-2xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={stats.overdue} currency={stats.business_currency} />
                        </div>
                    </div>
                </div>

                {/* ──────────────────────────────────────────────────────── */}
                {/* FILTERS & SEARCH */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-2 flex flex-col md:flex-row gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <Input
                            placeholder="Search invoice number or client..."
                            className="pl-9 h-10 shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors"
                            defaultValue={filters.search}
                            onBlur={(e) => handleFilterChange('search', e.target.value)}
                        />
                    </div>
                    <div className="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                        <select
                            className="h-10 w-[140px] rounded-lg border-transparent bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-slate-300 focus:ring-1 focus:ring-slate-300 transition-colors shadow-none"
                            defaultValue={filters.status}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <Input
                            type="date"
                            className="h-10 w-auto text-sm shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors"
                            onChange={(e) => handleFilterChange('start_date', e.target.value)}
                        />
                        <span className="text-slate-400 text-sm font-medium px-1">-</span>
                        <Input
                            type="date"
                            className="h-10 w-auto text-sm shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors"
                            onChange={(e) => handleFilterChange('end_date', e.target.value)}
                        />
                    </div>
                </div>

                {/* ──────────────────────────────────────────────────────── */}
                {/* DATA TABLE */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
                    <DataTable
                        columns={columns}
                        data={invoices.data}
                        pagination={{
                            current_page: invoices.current_page,
                            last_page: invoices.last_page,
                            prev_page_url: invoices.prev_page_url,
                            next_page_url: invoices.next_page_url,
                            links: invoices.links
                        }}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
