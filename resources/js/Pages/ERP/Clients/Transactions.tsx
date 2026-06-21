import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ClientPageLayout from './Components/ClientPageLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { EmptyState } from '@/Components/ui/EmptyState';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { CreditCard, Wallet, ArrowDownLeft, ArrowUpRight } from 'lucide-react';

interface Props {
    client: any;
    balance: number;
    lockedBalance: number;
    totalRevenue: number;
    unpaidRevenue: number;
    projectsCount: number;
    ticketsCount: number;
    hasTickets: boolean;
    transactions: any[];
}

export default function ClientTransactions({
    client, balance, lockedBalance, totalRevenue, unpaidRevenue, projectsCount, ticketsCount, hasTickets, transactions
}: Props) {
    const currencyCode = client.currency?.currency;

    return (
        <ClientPageLayout
            client={client}
            balance={balance}
            lockedBalance={lockedBalance}
            totalRevenue={totalRevenue}
            unpaidRevenue={unpaidRevenue}
            projectsCount={projectsCount}
            ticketsCount={ticketsCount}
            hasTickets={hasTickets}
            activeTab="transactions"
        >
            <Card className="bg-white border border-slate-200 shadow-sm">
                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle className="text-slate-900 text-lg font-semibold">{__('general.wallet_transactions')}</CardTitle>
                        <p className="text-sm text-slate-500 mt-1">{__('general.history_of_all_financial_transactions')}</p>
                    </div>
                    <Link href={route('erp.clients.wallet.index', client.id)}>
                        <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700">
                            <Wallet className="w-3.5 h-3.5" /> {__('general.full_ledger')}
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent className="p-0">
                    {transactions.length === 0 ? (
                        <EmptyState icon={CreditCard} title={__('general.no_transactions')} description={__('general.no_wallet_transactions_found')} />
                    ) : (
                        <div className="divide-y divide-slate-100 border-t border-slate-100">
                            {transactions.map(tx => (
                                <div key={tx.id} className="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition-colors">
                                    <div className="flex items-center gap-4">
                                        <div className={`w-10 h-10 rounded-full flex items-center justify-center shrink-0 ${
                                            ['received', 'earned', 'refunded_to_wallet'].includes(tx.type) ? 'bg-green-100 text-green-600' : 'bg-rose-100 text-rose-600'
                                        }`}>
                                            {['received', 'earned', 'refunded_to_wallet'].includes(tx.type) ? <ArrowDownLeft className="w-5 h-5" /> : <ArrowUpRight className="w-5 h-5" />}
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 capitalize">{tx.type.replace('_', ' ')}</p>
                                            <p className="text-xs text-slate-500 mt-0.5">{tx.description || '-'}</p>
                                            <p className="text-[10px] text-slate-400 mt-0.5"><DateDisplay date={tx.created_at} format="datetime" /></p>
                                        </div>
                                    </div>
                                    <div className="text-end">
                                        <p className={`text-base font-bold ${
                                            ['received', 'earned', 'refunded_to_wallet'].includes(tx.type) ? 'text-green-600' : 'text-slate-900'
                                        }`}>
                                            {['received', 'earned', 'refunded_to_wallet'].includes(tx.type) ? '+' : '-'}
                                            <CurrencyDisplay amount={Math.abs(tx.amount)} currency={tx.currency?.currency || currencyCode} />
                                        </p>
                                        {tx.business_amount && (
                                            <p className="text-[10px] text-slate-400 font-medium">
                                                {formatCurrency(Math.abs(tx.business_amount), 'USD')}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </ClientPageLayout>
    );
}
