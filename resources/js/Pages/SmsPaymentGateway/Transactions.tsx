import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { format, parseISO } from 'date-fns';
import { ArrowLeft, ArrowUpDown, ChevronLeft, ChevronRight, Activity, Smartphone } from 'lucide-react';

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface TransactionsProps {
    transactions: {
        data: any[];
        links: PaginationLinks[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: any;
}

export default function Transactions({ transactions, filters }: TransactionsProps) {
    const formatDate = (dateString: string) => {
        if (!dateString) return 'N/A';
        try {
            return format(parseISO(dateString), 'PP p');
        } catch (e) {
            return dateString;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'verified':
                return <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100 border-none">{__('Verified')}</Badge>;
            case 'pending':
                return <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">{__('Pending')}</Badge>;
            case 'ignored':
                return <Badge variant="outline" className="text-slate-500 border-slate-200 bg-slate-50">{__('Ignored')}</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('Transactions')}</h2>}>
            <Head title={__('Transactions - Payment Gateway')} />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Activity className="w-6 h-6 text-indigo-600" />
                                {__('All Transactions')}
                            </h1>
                            <p className="text-slate-500 mt-1">{__('View all SMS receipts captured from your connected Android devices.')}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                {__('Back')}
                            </Button>
                        </div>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('Transactions History')}</CardTitle>
                            <CardDescription>
                                {__('Showing')} {transactions.from} {__('to')} {transactions.to} {__('of')} {transactions.total} {__('results')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border overflow-hidden">
                                <Table>
                                    <TableHeader className="bg-slate-50">
                                        <TableRow>
                                            <TableHead>{__('Date')}</TableHead>
                                            <TableHead>{__('Sender')}</TableHead>
                                            <TableHead>{__('Amount')}</TableHead>
                                            <TableHead>{__('Reference')}</TableHead>
                                            <TableHead>{__('Device')}</TableHead>
                                            <TableHead>{__('Status')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {transactions.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-12 text-slate-500">
                                                    {__('No transactions found.')}
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            transactions.data.map((tx) => (
                                                <TableRow key={tx.id}>
                                                    <TableCell className="whitespace-nowrap text-sm text-slate-600">
                                                        {formatDate(tx.transaction_date || tx.created_at)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="font-medium">{tx.sender_name || tx.sender}</div>
                                                        {tx.phone_number && <div className="text-xs text-slate-500">{tx.phone_number}</div>}
                                                    </TableCell>
                                                    <TableCell className="font-semibold text-slate-900">
                                                        {tx.amount} {tx.currency}
                                                    </TableCell>
                                                    <TableCell className="text-sm font-mono text-slate-600">
                                                        {tx.reference_number || '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {tx.device ? (
                                                            <div className="flex items-center text-sm text-slate-600">
                                                                <Smartphone className="w-3 h-3 mr-1" />
                                                                {tx.device.device_name}
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-slate-400">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {getStatusBadge(tx.status)}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {transactions.links && transactions.links.length > 3 && (
                                <div className="flex items-center justify-between mt-4">
                                    <div className="flex flex-wrap gap-1">
                                        {transactions.links.map((link, i) => {
                                            const isPrevious = link.label.includes('Previous');
                                            const isNext = link.label.includes('Next');
                                            return (
                                                <Button
                                                    key={i}
                                                    variant={link.active ? "default" : "outline"}
                                                    size="sm"
                                                    disabled={!link.url}
                                                    onClick={() => link.url && router.visit(link.url)}
                                                    className={`
                                                        ${link.active ? 'pointer-events-none' : ''}
                                                        ${(!isPrevious && !isNext) ? 'w-9' : ''}
                                                    `}
                                                >
                                                    {isPrevious ? <ChevronLeft className="w-4 h-4" /> : isNext ? <ChevronRight className="w-4 h-4" /> : link.label}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
