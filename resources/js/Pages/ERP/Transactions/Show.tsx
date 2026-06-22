import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, router } from '@inertiajs/react';
import React from 'react';
import { 
    ArrowLeft, 
    Calendar, 
    User, 
    FileText, 
    Activity, 
    ArrowUpRight, 
    ArrowDownLeft, 
    DollarSign,
    Hash
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

interface Transaction {
    id: number;
    reference_id: string;
    title: string;
    type: string;
    note: string;
    direction: 'CREDIT' | 'DEBIT';
    amount: number;
    business_amount: number;
    currency: string;
    client_currency: string;
    business_currency: string;
    reference_type: string;
    reference_id_raw: number;
    client_name: string;
    client_id: number;
    authorizer: string;
    date: string;
}

interface Props {
    transaction: Transaction;
}

export default function ShowTransaction({ transaction }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('transactions');
    const isCredit = transaction.direction === 'CREDIT';

    return (
        <ERPLayout
            title={`Transaction ${transaction.reference_id}`}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
        >
            <Head title={`Transaction ${transaction.reference_id}`} />

            <div className="max-w-7xl mx-auto space-y-6">
                <ModulePageHeader
                    title={`Transaction ${transaction.reference_id}`}
                    description={__('general.detailed_view_of_the_ledger_transaction')}
                    icon={Activity}
                    actions={
                        <Button variant="outline" size="sm" onClick={() => router.get(route('erp.dashboard', { section: 'transactions' }))}>
                            <ArrowLeft className="w-4 h-4 me-2" />{__('general.back_to_ledger')}</Button>
                    }
                />

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-xl">{transaction.title}</CardTitle>
                                        <CardDescription className="mt-1">{transaction.note}</CardDescription>
                                    </div>
                                    <Badge variant={isCredit ? 'default' : 'secondary'} className={isCredit ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}>
                                        {isCredit ? <ArrowDownLeft className="w-4 h-4 me-1" /> : <ArrowUpRight className="w-4 h-4 me-1" />}
                                        {transaction.direction}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-8 py-4">
                                    <div>
                                        <span className="text-sm font-medium text-slate-500 flex items-center mb-1">
                                            <Hash className="w-4 h-4 me-2" />{__('general.reference_id')}</span>
                                        <span className="text-lg font-mono font-semibold text-slate-900">{transaction.reference_id}</span>
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-slate-500 flex items-center mb-1">
                                            <DollarSign className="w-4 h-4 me-2" />{__('general.business_amount')}</span>
                                        <span className={`text-2xl font-bold tracking-tight ${isCredit ? 'text-emerald-600' : 'text-rose-600'}`}>
                                            {isCredit ? '+' : '-'}
                                            <CurrencyDisplay amount={transaction.business_amount} currency={transaction.business_currency} />
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <span className="text-sm font-medium text-slate-500 flex items-center mb-1">
                                            <User className="w-4 h-4 me-2" />
                                            {__('general.client')}</span>
                                        {transaction.client_id ? (
                                            <Link href={route('erp.clients.show', transaction.client_id)} className="text-base font-medium text-indigo-600 hover:underline">
                                                {transaction.client_name}
                                            </Link>
                                        ) : (
                                            <span className="text-base font-medium text-slate-900">{transaction.client_name}</span>
                                        )}
                                    </div>

                                    <div>
                                        <span className="text-sm font-medium text-slate-500 flex items-center mb-1">
                                            <Calendar className="w-4 h-4 me-2" />{__('general.date_time_1')}</span>
                                        <span className="text-base font-medium text-slate-900">{transaction.date}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">{__('general.metadata')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <span className="text-xs text-slate-500 block uppercase tracking-wider font-medium mb-1">{__('general.original_amount')}</span>
                                    <div className="font-semibold text-slate-900">
                                        <CurrencyDisplay amount={transaction.amount} currency={transaction.client_currency} />
                                    </div>
                                </div>
                                
                                <div className="border-t border-slate-100 pt-4">
                                    <span className="text-xs text-slate-500 block uppercase tracking-wider font-medium mb-1">{__('general.authorizer')}</span>
                                    <div className="flex items-center gap-2">
                                        <div className="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600">
                                            {transaction.authorizer.substring(0, 2).toUpperCase()}
                                        </div>
                                        <span className="text-sm font-medium text-slate-900">{transaction.authorizer}</span>
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-4">
                                    <span className="text-xs text-slate-500 block uppercase tracking-wider font-medium mb-1">{__('general.source_reference')}</span>
                                    <div className="flex items-center gap-2">
                                        <FileText className="w-4 h-4 text-slate-400" />
                                        <span className="text-sm font-medium text-slate-700 capitalize">
                                            {transaction.reference_type} 
                                            {transaction.reference_id_raw ? ` #${transaction.reference_id_raw}` : ''}
                                        </span>
                                    </div>
                                    {transaction.reference_type === 'invoice' && transaction.reference_id_raw && (
                                        <Link 
                                            href={route('erp.invoices.show', transaction.reference_id_raw)} 
                                            className="text-xs text-indigo-600 hover:underline mt-1 inline-block"
                                        >{__('general.view_related_invoice')}</Link>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
