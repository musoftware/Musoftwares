import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Wallet, ArrowUpRight, ArrowDownLeft, Clock, CheckCircle2, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Transactions({ transactions, wallet }) {
    return (
        <AuthenticatedLayout header="Financial Transactions">
            <Head title={__('general.transactions')} />

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8">
                
                {/* Header */}
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight">{__('general.transactions')}</h1>
                    <p className="text-sm text-muted-foreground">{__('general.view_your_wallet_transaction_history_and_ledgers')}</p>
                </div>

                {/* Balance Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card className="shadow-none border-primary/20 bg-muted/10">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">{__('general.total_balance')}</CardTitle>
                            <Wallet className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {Number(wallet?.balance || 0).toFixed(2)} <span className="text-sm font-normal text-muted-foreground">{wallet?.currency}</span>
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">{__('general.available_across_all_platform_workspaces')}</p>
                        </CardContent>
                    </Card>

                    <Card className="shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">{__('general.earned_balance')}</CardTitle>
                            <ArrowDownLeft className="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {Number(wallet?.earned_balance || 0).toFixed(2)} <span className="text-sm font-normal text-muted-foreground">{wallet?.currency}</span>
                            </div>
                            <p className="text-xs text-emerald-600 font-medium flex items-center gap-1 mt-1">
                                <CheckCircle2 className="w-3 h-3" />{__('general.eligible_for_withdrawal')}</p>
                        </CardContent>
                    </Card>

                    <Card className="shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">{__('general.locked_pending')}</CardTitle>
                            <Clock className="h-4 w-4 text-amber-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {Number(wallet?.locked_balance || 0).toFixed(2)} <span className="text-sm font-normal text-muted-foreground">{wallet?.currency}</span>
                            </div>
                            <p className="text-xs text-amber-600 font-medium flex items-center gap-1 mt-1">
                                <AlertCircle className="w-3 h-3" />{__('general.pending_in_active_contracts')}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Transactions Table */}
                <Card className="shadow-none">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-base font-semibold">{__('general.transaction_history')}</CardTitle>
                            <CardDescription>{__('general.a_list_of_your_recent_transactions')}</CardDescription>
                        </div>
                        <Badge variant="secondary" className="font-normal">{transactions?.total || 0} Records</Badge>
                    </CardHeader>
                    <CardContent className="px-0 pt-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="ps-6">{__('general.type')}</TableHead>
                                    <TableHead>{__('general.description')}</TableHead>
                                    <TableHead>{__('general.amount')}</TableHead>
                                    <TableHead>{__('general.balance_before')}</TableHead>
                                    <TableHead>{__('general.balance_after')}</TableHead>
                                    <TableHead className="pe-6 text-end">{__('general.date')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(!transactions?.data || transactions.data.length === 0) ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-12 text-center text-muted-foreground">{__('general.no_transactions_found')}</TableCell>
                                    </TableRow>
                                ) : (
                                    transactions.data.map((tx) => (
                                        <TableRow key={tx.id}>
                                            <TableCell className="ps-6">
                                                <Badge variant={tx.type === 'credit' ? 'outline' : 'secondary'} className={`font-normal tracking-wide bg-opacity-10 ${tx.type === 'credit' ? 'text-emerald-700 border-emerald-200 bg-emerald-50' : 'text-rose-700 border-rose-200 bg-rose-50'}`}>
                                                    {tx.type === 'credit' ? <ArrowDownLeft className="w-3 h-3 me-1" /> : <ArrowUpRight className="w-3 h-3 me-1" />}
                                                    {tx.type === 'credit' ? 'Credit' : 'Debit'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium">{tx.description || 'System transaction'}</p>
                                                {tx.reference_type && (
                                                    <span className="text-xs text-muted-foreground">Ref: {tx.reference_type}</span>
                                                )}
                                            </TableCell>
                                            <TableCell className={`font-medium ${tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                {tx.type === 'credit' ? '+' : '-'}{Number(tx.amount).toFixed(2)} {wallet?.currency}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {Number(tx.balance_before).toFixed(2)}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {Number(tx.balance_after).toFixed(2)}
                                            </TableCell>
                                            <TableCell className="pe-6 text-end text-muted-foreground text-xs">
                                                {new Date(tx.created_at).toLocaleDateString(undefined, {
                                                    year: 'numeric', month: 'short', day: 'numeric',
                                                    hour: '2-digit', minute: '2-digit'
                                                })}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                    
                    {/* Pagination */}
                    {transactions?.links && transactions.links.length > 3 && (
                        <div className="p-4 border-t flex items-center justify-end gap-1">
                            {transactions.links.map((link, idx) => {
                                const isCurrent = link.active;
                                const isPrevious = link.label.includes('Previous');
                                const isNext = link.label.includes('Next');
                                
                                return (
                                    <Button
                                        key={idx}
                                        asChild={!!link.url}
                                        variant={isCurrent ? 'default' : 'outline'}
                                        size="sm"
                                        className={`shadow-none ${!link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}`}
                                    >
                                        {link.url ? (
                                            <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                );
                            })}
                        </div>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

