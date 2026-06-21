import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, ArrowDownLeft, Calendar, FileText, Send, Plus, Search, HelpCircle, History as HistoryIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

export default function History({ transfers }) {
    return (
        <AuthenticatedLayout header="P2P Transfer History">
            <Head title={__('general.transfer_history')} />

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8">
                
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">{__('general.transfer_ledger')}</h1>
                        <p className="text-sm text-muted-foreground">{__('general.manage_and_track_your_peer_to_peer_wallet_transfers')}</p>
                    </div>
                    <Button asChild className="shadow-none gap-2">
                        <Link href={route('financial.transfer.create')}>
                            <Send className="w-4 h-4" />{__('general.send_money')}</Link>
                    </Button>
                </div>

                {/* Ledger Table Card */}
                <Card className="shadow-none">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <HistoryIcon className="w-4 h-4 text-muted-foreground" />{__('general.recent_p2p_transactions')}</CardTitle>
                            <CardDescription>{__('general.records_of_wallet_balances_sent_or_received_from_other_users')}</CardDescription>
                        </div>
                        <Badge variant="secondary" className="font-normal">{transfers.total || 0} Transfers</Badge>
                    </CardHeader>
                    
                    <CardContent className="px-0 pt-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="ps-6">Type</TableHead>
                                    <TableHead>{__('general.recipient_sender')}</TableHead>
                                    <TableHead>{__('general.reason_memo')}</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>{__('general.fees_paid')}</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>{__('general.processed_date')}</TableHead>
                                    <TableHead className="pe-6 text-end">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(!transfers.data || transfers.data.length === 0) ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="py-16 text-center text-muted-foreground">
                                            <div className="flex flex-col items-center justify-center gap-3">
                                                <div className="p-3 rounded-full bg-muted/40 text-muted-foreground">
                                                    <Send className="w-6 h-6" />
                                                </div>
                                                <div className="space-y-1">
                                                    <h3 className="font-semibold text-sm text-foreground">{__('general.no_p2p_transfers_yet')}</h3>
                                                    <p className="text-xs text-muted-foreground max-w-xs mx-auto">{__('general.you_haven_t_made_any_balance_transfers_with_other_accounts_on_the_platform')}</p>
                                                </div>
                                                <Button variant="outline" size="sm" asChild className="shadow-none mt-2">
                                                    <Link href={route('financial.transfer.create')}>{__('general.send_your_first_transfer')}</Link>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    transfers.data.map((tx) => (
                                        <TableRow key={tx.id}>
                                            
                                            {/* Type indicator */}
                                            <TableCell className="ps-6">
                                                <Badge
                                                    variant={tx.type === 'sent' ? 'secondary' : 'outline'}
                                                    className={`font-normal tracking-wide bg-opacity-10 ${
                                                        tx.type === 'sent' 
                                                            ? 'text-rose-700 border-rose-200 bg-rose-50' 
                                                            : 'text-emerald-700 border-emerald-200 bg-emerald-50'
                                                    }`}
                                                >
                                                    {tx.type === 'sent' ? (
                                                        <>
                                                            <ArrowUpRight className="w-3 h-3 me-1" /> Sent
                                                        </>
                                                    ) : (
                                                        <>
                                                            <ArrowDownLeft className="w-3 h-3 me-1" /> Received
                                                        </>
                                                    )}
                                                </Badge>
                                            </TableCell>
                                            
                                            {/* Recipient / Sender Account */}
                                            <TableCell>
                                                <div className="flex flex-col">
                                                    <span className="font-medium text-sm text-foreground">{tx.other_party}</span>
                                                    <span className="text-xs text-muted-foreground">{tx.other_party_email}</span>
                                                </div>
                                            </TableCell>

                                            {/* Memo */}
                                            <TableCell>
                                                <span className="text-sm italic text-muted-foreground">
                                                    {tx.reason || '—'}
                                                </span>
                                            </TableCell>

                                            {/* Principal Amount (Handles cross-currency flags beautifully) */}
                                            <TableCell className={`font-semibold text-sm ${tx.type === 'sent' ? 'text-rose-600' : 'text-emerald-600'}`}>
                                                {tx.type === 'sent' ? (
                                                    <>
                                                        -{Number(tx.amount).toFixed(2)} {tx.currency}
                                                    </>
                                                ) : (
                                                    <>
                                                        +{Number(tx.converted_amount).toFixed(2)} {tx.converted_currency}
                                                    </>
                                                )}
                                                {tx.currency !== tx.converted_currency && (
                                                    <div className="text-[10px] font-normal text-muted-foreground">
                                                        ({tx.type === 'sent' ? `Credited: ${Number(tx.converted_amount).toFixed(2)} ${tx.converted_currency}` : `Debited: ${Number(tx.amount).toFixed(2)} ${tx.currency}`})
                                                    </div>
                                                )}
                                            </TableCell>

                                            {/* Fee Paid (only visible on sent transactions) */}
                                            <TableCell className="text-sm text-muted-foreground">
                                                {tx.type === 'sent' ? (
                                                    <span>{Number(tx.fee).toFixed(2)} {tx.currency}</span>
                                                ) : (
                                                    <span className="text-muted-foreground/30">—</span>
                                                )}
                                            </TableCell>

                                            {/* Status Badge */}
                                            <TableCell>
                                                <Badge variant="outline" className={`font-normal capitalize ${
                                                    tx.status === 'completed' 
                                                        ? 'text-emerald-700 border-emerald-200 bg-emerald-50/50' 
                                                        : tx.status === 'cancelled'
                                                        ? 'text-amber-700 border-amber-200 bg-amber-50/50'
                                                        : 'text-rose-700 border-rose-200 bg-rose-50/50'
                                                }`}>
                                                    {tx.status}
                                                </Badge>
                                            </TableCell>

                                            {/* Date */}
                                            <TableCell className="text-muted-foreground text-xs">
                                                {new Date(tx.created_at).toLocaleDateString(undefined, {
                                                    year: 'numeric', month: 'short', day: 'numeric',
                                                    hour: '2-digit', minute: '2-digit'
                                                })}
                                            </TableCell>

                                            {/* Actions */}
                                            <TableCell className="pe-6 text-end">
                                                <Button variant="ghost" size="sm" asChild className="shadow-none hover:bg-muted">
                                                    <Link href={route('financial.transfer.show', tx.id)}>
                                                        <FileText className="w-4 h-4 me-1" /> Receipt
                                                    </Link>
                                                </Button>
                                            </TableCell>

                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                    
                    {/* Pagination Links */}
                    {transfers.links && transfers.links.length > 3 && (
                        <div className="p-4 border-t flex items-center justify-end gap-1">
                            {transfers.links.map((link, idx) => {
                                const isCurrent = link.active;
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
