import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { ArrowLeft, ArrowUpRight, ArrowDownRight, User, Phone, Calendar, FileText } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

export default function DebtsShow({ client, transactions, baseCurrency }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('debts');

    return (
        <ERPLayout 
            title={__('erp.client_debt_details')}
            menuItems={menuItems} 
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('erp.client_debt_details') + ' - ' + client.name} />
            
            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" asChild>
                        <Link href={route('erp.debts.index')}>
                            <ArrowLeft className="w-4 h-4" />
                        </Link>
                    </Button>
                    <div>
                        <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{client.name}</h2>
                        <div className="text-sm text-slate-500 mt-1 flex items-center gap-2">
                            <Phone className="w-3 h-3" />
                            {client.phone || __('general.no_phone')}
                        </div>
                    </div>
                </div>

                {/* Summary Card */}
                <Card className={`border-border shadow-sm ${client.debt_balance > 0 ? 'bg-green-50/50' : client.debt_balance < 0 ? 'bg-red-50/50' : 'bg-slate-50/50'}`}>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-slate-500">
                            {__('erp.current_debt_balance')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-4">
                            <div className={`text-3xl font-bold ${client.debt_balance > 0 ? 'text-green-700' : client.debt_balance < 0 ? 'text-red-700' : 'text-slate-700'}`}>
                                {formatCurrency(Math.abs(client.debt_balance), baseCurrency)}
                            </div>
                            {client.debt_balance > 0 ? (
                                <Badge className="bg-green-100 text-green-800 hover:bg-green-100">{__('erp.client_owes_you')}</Badge>
                            ) : client.debt_balance < 0 ? (
                                <Badge className="bg-red-100 text-red-800 hover:bg-red-100">{__('erp.you_owe_client')}</Badge>
                            ) : (
                                <Badge variant="outline">{__('erp.settled')}</Badge>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Transactions */}
                <div>
                    <h3 className="text-lg font-medium text-slate-900 mb-4">{__('erp.debt_transactions_history')}</h3>
                    <div className="bg-white rounded-lg border shadow-sm">
                        {(transactions.data as any).length > 0 ? (
                            <div className="divide-y">
                                {(transactions.data as any).map((trx: any) => (
                                    <div key={trx.id} className="p-4 flex items-start justify-between hover:bg-slate-50 transition-colors">
                                        <div className="flex gap-4">
                                            <div className={`mt-1 p-2 rounded-full ${trx.type === 'given' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                {trx.type === 'given' ? <ArrowDownRight className="w-4 h-4" /> : <ArrowUpRight className="w-4 h-4" />}
                                            </div>
                                            <div>
                                                <div className="font-medium text-slate-900 flex items-center gap-2">
                                                    {trx.type === 'given' ? __('erp.debt_given_to_client') : __('erp.debt_received_from_client')}
                                                </div>
                                                {trx.note && (
                                                    <div className="text-sm text-slate-500 mt-1 flex items-start gap-1">
                                                        <FileText className="w-3.5 h-3.5 mt-0.5 text-slate-400 shrink-0" />
                                                        <span>{trx.note}</span>
                                                    </div>
                                                )}
                                                <div className="text-xs text-slate-400 mt-2 flex items-center gap-1">
                                                    <Calendar className="w-3 h-3" />
                                                    {trx.date}
                                                </div>
                                            </div>
                                        </div>
                                        <div className={`font-semibold ${trx.type === 'given' ? 'text-green-600' : 'text-red-600'}`}>
                                            {trx.type === 'given' ? '+' : '-'}{formatCurrency(trx.amount, baseCurrency)}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="p-8 text-center text-slate-500">
                                <FileText className="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                <p>{__('erp.no_debt_transactions_found')}</p>
                            </div>
                        )}
                    </div>
                </div>

            </div>
        </ERPLayout>
    );
}
