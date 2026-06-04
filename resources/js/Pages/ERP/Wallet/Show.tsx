import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';
import {
    Wallet, Lock, ArrowUpRight, ArrowDownLeft, TrendingUp,
    History, User, FileText, ArrowLeft, RotateCcw,
    AlertCircle, FileSpreadsheet
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { __ } from '@/lib/i18n';

interface WalletShowProps {
    wallet?: { balance: number; locked_balance: number; currency_id: number; currency?: { id: number; currency: string; symbol: string } };
    transactions?: { data: any[]; current_page: number; last_page: number; total: number };
    client?: { id: number; name: string; email: string; phone?: string; address?: string; currency?: { id: number; currency: string; symbol: string } };
}

const TYPE_CONFIG: Record<string, { label: string; color: string; bgColor: string; icon: any }> = {
    received: { label: 'Received', color: 'text-emerald-700', bgColor: 'bg-emerald-100', icon: ArrowDownLeft },
    earned: { label: 'Earned', color: 'text-emerald-700', bgColor: 'bg-emerald-100', icon: TrendingUp },
    sent: { label: 'Sent', color: 'text-amber-700', bgColor: 'bg-amber-100', icon: ArrowUpRight },
    refunded: { label: 'Refunded', color: 'text-blue-700', bgColor: 'bg-blue-100', icon: RotateCcw },
    used: { label: 'Used', color: 'text-slate-600', bgColor: 'bg-slate-100', icon: FileText },
};

export default function Show({ wallet, transactions, client }: WalletShowProps) {
    const activeClient = client || { id: 0, name: __('erp.unknown_client'), email: 'N/A', phone: '', address: '' };
    const activeWallet = wallet || { balance: 0, locked_balance: 0, currency_id: 0, currency: undefined };
    const activeTransactions = transactions?.data || [];
    
    const currencyCode = activeClient.currency?.currency || activeWallet.currency?.currency;

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`${__('erp.transaction_ledger')} - ${activeClient.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8">
                {/* Header */}
                <div className="space-y-2">
                    <Link href={route('erp.clients.show', activeClient.id)} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                        <ArrowLeft className="mr-2 h-4 w-4" /> {__('erp.back_to_client')}
                    </Link>
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">{activeClient.name} — {__('erp.transaction_ledger')}</h1>
                            <p className="text-sm text-muted-foreground">{__('erp.complete_record_of_all_client')}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Link href={route('erp.clients.wallet.adjust', activeClient.id) + '?type=receive'}>
                                <Button size="sm" className="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-none">
                                    <ArrowDownLeft className="w-3.5 h-3.5" /> {__('general.receive')}
                                </Button>
                            </Link>
                            <Link href={route('erp.clients.wallet.adjust', activeClient.id) + '?type=send'}>
                                <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200">
                                    <ArrowUpRight className="w-3.5 h-3.5 text-amber-600" /> {__('general.send')}
                                </Button>
                            </Link>
                            <Link href={route('erp.clients.wallet.adjust', activeClient.id) + '?type=refund'}>
                                <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200">
                                    <RotateCcw className="w-3.5 h-3.5 text-blue-600" /> {__('general.refund')}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Financial Summary */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1">{__('general.available_balance')}</div>
                        <div className="text-3xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={activeWallet.balance} currency={currencyCode} />
                        </div>
                        <p className="text-xs text-slate-400 mt-2">{__('general.ready_for_use')}</p>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1 flex items-center gap-1.5">
                            {__('general.locked_balance')} <Lock className="h-3 w-3" />
                        </div>
                        <div className="text-3xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={activeWallet.locked_balance ?? 0} currency={currencyCode} />
                        </div>
                        <p className="text-xs text-slate-400 mt-2">{__('erp.reserved_for_unpaid_invoices')}</p>
                    </div>
                    <div className="bg-slate-50 p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1">{__('erp.total_ledger')}</div>
                        <div className="text-3xl font-bold tracking-tight text-slate-900">
                            <CurrencyDisplay amount={activeWallet.balance + (activeWallet.locked_balance ?? 0)} currency={currencyCode} />
                        </div>
                        <p className="text-xs text-slate-400 mt-2">{__('erp.computed_from_all_transactions')}</p>
                    </div>
                </div>

                {/* Transactions Table */}
                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div className="p-6 flex flex-row items-center justify-between border-b border-slate-100">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">{__('erp.transaction_history')}</h3>
                            <p className="text-sm text-slate-500 mt-1">
                                {transactions?.total ? `${transactions.total} ${__('erp.transactions_2')}` : __('erp.no_transactions_yet_2')}
                            </p>
                        </div>
                    </div>
                    <div className="px-0">
                        {activeTransactions.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent border-slate-100">
                                        <TableHead className="pl-6 text-xs font-semibold uppercase text-slate-500 tracking-wider">{__('general.type')}</TableHead>
                                        <TableHead className="text-xs font-semibold uppercase text-slate-500 tracking-wider">{__('general.amount')}</TableHead>
                                        <TableHead className="text-xs font-semibold uppercase text-slate-500 tracking-wider">{__('erp.project')}</TableHead>
                                        <TableHead className="text-xs font-semibold uppercase text-slate-500 tracking-wider">{__('general.note')}</TableHead>
                                        <TableHead className="pr-6 text-right text-xs font-semibold uppercase text-slate-500 tracking-wider">{__('general.date')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activeTransactions.map((tx) => {
                                        const typeConfig = TYPE_CONFIG[tx.type] || TYPE_CONFIG['used'];
                                        const TypeIcon = typeConfig.icon;
                                        const isPositive = tx.amount > 0;
                                        
                                        return (
                                            <TableRow key={tx.id} className="hover:bg-slate-50/50 border-slate-50">
                                                <TableCell className="pl-6 py-4">
                                                    <Badge variant="secondary" className={`font-medium tracking-wide text-[10px] ${typeConfig.bgColor} ${typeConfig.color} hover:${typeConfig.bgColor} gap-1`}>
                                                        <TypeIcon className="w-3 h-3" />
                                                        {__(typeConfig.label)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="font-semibold py-4">
                                                    <div className={`flex items-center gap-0.5 ${isPositive ? 'text-emerald-700' : 'text-rose-600'}`}>
                                                        <span>{isPositive ? '+' : ''}</span>
                                                        <CurrencyDisplay amount={tx.amount} currency={tx.currency?.currency || currencyCode} />
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-sm text-slate-500 py-4">
                                                    {tx.project ? (
                                                        <Link href={route('erp.projects.show', tx.project.id)} className="text-primary hover:underline text-xs">
                                                            {tx.project.title}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="max-w-[250px] truncate text-sm text-slate-700 py-4" title={tx.note}>
                                                    {tx.note || '—'}
                                                </TableCell>
                                                <TableCell className="pr-6 text-right text-slate-400 text-xs py-4">
                                                    <DateDisplay date={tx.created_at} />
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="py-16 text-center text-slate-500">
                                <AlertCircle className="w-8 h-8 mx-auto text-slate-300 mb-3" />
                                <p className="font-medium text-slate-900">{__('erp.no_transactions_found')}</p>
                                <p className="text-sm mt-1">{__('erp.this_client_has_no_transaction')}</p>
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {transactions && transactions.last_page > 1 && (
                        <div className="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                            <p className="text-sm text-slate-500">
                                {__('general.page')} {transactions.current_page} / {transactions.last_page}
                            </p>
                            <div className="flex gap-2">
                                {transactions.current_page > 1 && (
                                    <Link href={`?page=${transactions.current_page - 1}`}>
                                        <Button variant="outline" size="sm" className="shadow-none border-slate-200">{__('general.previous')}</Button>
                                    </Link>
                                )}
                                {transactions.current_page < transactions.last_page && (
                                    <Link href={`?page=${transactions.current_page + 1}`}>
                                        <Button variant="outline" size="sm" className="shadow-none border-slate-200">{__('general.next')}</Button>
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* Client Overview Sidebar */}
                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <User className="h-4 w-4 text-slate-400" /> {__('erp.client_overview')}
                    </h3>
                    <div className="grid grid-cols-1 sm:grid-cols-4 gap-6 text-sm">
                        <div className="flex justify-between items-start sm:flex-col sm:gap-1">
                            <span className="text-slate-500">{__('general.name')}</span>
                            <span className="font-semibold text-slate-900">{activeClient.name}</span>
                        </div>
                        <div className="flex justify-between items-start sm:flex-col sm:gap-1">
                            <span className="text-slate-500">{__('general.email')}</span>
                            <span className="font-medium text-slate-700">{activeClient.email || '—'}</span>
                        </div>
                        <div className="flex justify-between items-start sm:flex-col sm:gap-1">
                            <span className="text-slate-500">{__('general.phone')}</span>
                            <span className="font-medium text-slate-700">{activeClient.phone || '—'}</span>
                        </div>
                        <div className="flex justify-between items-start sm:flex-col sm:gap-1">
                            <span className="text-slate-500">{__('general.address')}</span>
                            <span className="font-medium text-slate-700 truncate max-w-[200px]" title={activeClient.address}>{activeClient.address || '—'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
