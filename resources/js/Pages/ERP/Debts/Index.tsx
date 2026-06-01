import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { formatCurrency } from '@/lib/utils';
import { ArrowUpRight, ArrowDownRight, Wallet, User, Phone, Search } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { router } from '@inertiajs/react';

export default function DebtsIndex({ clients, filters, totalOwedToMe, totalIOwe, baseCurrency }) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('debts');

    const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
        router.get(route('erp.debts.index'), { q: e.target.value }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <ERPLayout 
            title={__('erp.debts_management')}
            menuItems={menuItems} 
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('erp.debts_management')} />
            
            <div className="max-w-6xl mx-auto space-y-6">
                <div>
                    <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('erp.debts_and_loans')}</h2>
                    <p className="text-sm text-slate-500 mt-1">{__('erp.debts_description')}</p>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card className="border-border shadow-sm bg-green-50/50">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                                <ArrowDownRight className="w-4 h-4 text-green-600" />
                                {__('erp.total_owed_to_me')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-700">
                                {formatCurrency(totalOwedToMe, baseCurrency)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border shadow-sm bg-red-50/50">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-slate-500 flex items-center gap-2">
                                <ArrowUpRight className="w-4 h-4 text-red-600" />
                                {__('erp.total_i_owe')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-700">
                                {formatCurrency(totalIOwe, baseCurrency)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Search */}
                <div className="relative max-w-sm">
                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" />
                    <Input 
                        placeholder={__('erp.search_clients')}
                        defaultValue={filters?.q || ''}
                        onChange={(e) => {
                            const val = e.target.value;
                            setTimeout(() => {
                                router.get(route('erp.debts.index'), { q: val }, { preserveState: true, replace: true });
                            }, 300);
                        }}
                        className="pl-9"
                    />
                </div>

                {/* Clients List */}
                <div className="bg-white rounded-lg border shadow-sm">
                    {clients.data.length > 0 ? (
                        <div className="divide-y">
                            {clients.data.map((client: any) => (
                                <Link 
                                    key={client.id}
                                    href={route('erp.debts.show', client.id)}
                                    className="block hover:bg-slate-50 transition-colors p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-4">
                                            <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold">
                                                {client.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <div className="font-medium text-slate-900">{client.name}</div>
                                                <div className="text-sm text-slate-500 flex items-center gap-1">
                                                    <Phone className="w-3 h-3" />
                                                    {client.phone || __('general.no_phone')}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            {client.debt_balance > 0 ? (
                                                <div className="text-green-600 font-medium flex items-center justify-end gap-1">
                                                    <ArrowDownRight className="w-4 h-4" />
                                                    {formatCurrency(client.debt_balance, baseCurrency)}
                                                </div>
                                            ) : client.debt_balance < 0 ? (
                                                <div className="text-red-600 font-medium flex items-center justify-end gap-1">
                                                    <ArrowUpRight className="w-4 h-4" />
                                                    {formatCurrency(Math.abs(client.debt_balance), baseCurrency)}
                                                </div>
                                            ) : (
                                                <div className="text-slate-500 font-medium">
                                                    {formatCurrency(0, baseCurrency)}
                                                </div>
                                            )}
                                            <div className="text-xs text-slate-400">
                                                {client.debt_balance > 0 ? __('erp.client_owes_you') : client.debt_balance < 0 ? __('erp.you_owe_client') : __('erp.settled')}
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center text-slate-500">
                            <Wallet className="w-12 h-12 mx-auto mb-3 text-slate-300" />
                            <p>{__('erp.no_debt_records_found')}</p>
                        </div>
                    )}
                </div>

                {/* Pagination placeholder, handled via standard mechanisms if needed */}
            </div>
        </ERPLayout>
    );
}
