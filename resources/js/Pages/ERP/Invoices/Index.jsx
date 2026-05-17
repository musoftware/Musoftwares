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
            <div className="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
                <PageHeader title="Invoices">
                    <Button asChild className="shadow-none">
                        <Link href={route('erp.invoices.create')}>
                            <Plus className="mr-2 h-4 w-4" /> New Invoice
                        </Link>
                    </Button>
                </PageHeader>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="shadow-none">
                        <CardContent className="pt-6">
                            <div className="text-sm font-medium text-muted-foreground">Total</div>
                            <div className="text-2xl font-bold tracking-tight text-foreground">
                                <CurrencyDisplay amount={stats.total} currency={stats.business_currency} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-none border-primary/20 bg-primary/5">
                        <CardContent className="pt-6">
                            <div className="text-sm font-semibold text-primary">Paid</div>
                            <div className="text-2xl font-bold tracking-tight text-primary">
                                <CurrencyDisplay amount={stats.paid} currency={stats.business_currency} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-none border-amber-500/20 bg-amber-500/5">
                        <CardContent className="pt-6">
                            <div className="text-sm font-semibold text-amber-600">Pending</div>
                            <div className="text-2xl font-bold tracking-tight text-amber-600">
                                <CurrencyDisplay amount={stats.pending} currency={stats.business_currency} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-none border-destructive/20 bg-destructive/5">
                        <CardContent className="pt-6">
                            <div className="text-sm font-semibold text-destructive">Overdue</div>
                            <div className="text-2xl font-bold tracking-tight text-destructive">
                                <CurrencyDisplay amount={stats.overdue} currency={stats.business_currency} />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-none">
                    <CardContent className="p-4 flex flex-col gap-4">
                        <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                            <div className="flex flex-1 items-center gap-4 w-full md:w-auto">
                                <div className="relative flex-1 md:max-w-xs">
                                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search by number/client..."
                                        className="pl-9 h-10 shadow-none"
                                        defaultValue={filters.search}
                                        onBlur={(e) => handleFilterChange('search', e.target.value)}
                                    />
                                </div>
                                <select
                                    className="flex h-10 w-[150px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 shadow-none"
                                    defaultValue={filters.status}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                >
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="paid">Paid</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button variant="outline" size="sm" className="shadow-none">
                                    <FileText className="mr-2 h-4 w-4" /> Export CSV
                                </Button>
                                <Button variant="outline" size="sm" className="shadow-none">
                                    <Download className="mr-2 h-4 w-4" /> Export PDF
                                </Button>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-4 pt-4 border-t">
                            <div className="flex items-center gap-2 text-sm text-muted-foreground font-medium">
                                <Filter className="h-4 w-4" />
                                <span>Filters:</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <CalendarIcon className="h-4 w-4 text-muted-foreground" />
                                <Input
                                    type="date"
                                    className="h-8 w-auto text-xs shadow-none"
                                    onChange={(e) => handleFilterChange('start_date', e.target.value)}
                                />
                                <span className="text-muted-foreground text-xs font-medium">to</span>
                                <Input
                                    type="date"
                                    className="h-8 w-auto text-xs shadow-none"
                                    onChange={(e) => handleFilterChange('end_date', e.target.value)}
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <select
                                    className="flex h-8 w-[100px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-none"
                                    onChange={(e) => handleFilterChange('currency', e.target.value)}
                                >
                                    <option value="">Currency</option>
                                    <option value="USD">USD</option>
                                    <option value="EGP">EGP</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="bg-card border rounded-xl shadow-none overflow-hidden">
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
