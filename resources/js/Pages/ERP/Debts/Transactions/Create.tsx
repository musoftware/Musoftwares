import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { ArrowLeft, Save, Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';


export default function CreateDebtTransaction({ baseCurrency, clients = [] }: any) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('debts');
    const [isNewClient, setIsNewClient] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        client_id: '',
        new_client_name: '',
        new_client_phone: '',
        type: 'given',
        amount: '',
        note: '',
        date: new Date().toISOString().split('T')[0],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.debts.transactions.store'));
    };

    return (
        <ERPLayout 
            title={__('erp.new_debt_transaction')}
            menuItems={menuItems} 
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('erp.new_debt_transaction')} />
            
            <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6 py-8">
                <div className="flex items-center justify-between">
                    <div>
                        <Link href={route('erp.debts.index')} className="inline-flex items-center text-sm text-slate-500 hover:text-slate-900 mb-2 transition-colors">
                            <ArrowLeft className="w-4 h-4 me-1" /> {__('general.back')}
                        </Link>
                        <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('erp.record_new_debt_transaction')}</h2>
                    </div>
                </div>

                <form onSubmit={submit}>
                    <Card className="border-border shadow-sm">
                        <CardHeader>
                            <CardTitle>{__('erp.transaction_details')}</CardTitle>
                            <CardDescription>{__('erp.record_debt_details_description')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Type Selection */}
                                <div className="space-y-2">
                                    <Label htmlFor="type">{__('erp.transaction_type')}</Label>
                                    <Select 
                                        value={data.type} 
                                        onValueChange={(val) => setData('type', val as string)}
                                    >
                                        <SelectTrigger className={errors.type ? 'border-red-500' : ''}>
                                            <SelectValue placeholder={__('erp.select_type')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="given">{__('erp.i_gave_money')}</SelectItem>
                                            <SelectItem value="received">{__('erp.i_received_money')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.type && <p className="text-sm text-red-600">{errors.type}</p>}
                                </div>

                                {/* Amount */}
                                <div className="space-y-2">
                                    <Label htmlFor="amount">{__('erp.amount')} ({baseCurrency?.currency || 'Base'})</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className={errors.amount ? 'border-red-500' : ''}
                                        placeholder="0.00"
                                    />
                                    {errors.amount && <p className="text-sm text-red-600">{errors.amount}</p>}
                                </div>

                                {/* Client Selection or Creation */}
                                <div className="space-y-4 md:col-span-2">
                                    <div className="flex items-center justify-between">
                                        <Label>{__('erp.client')}</Label>
                                        <Button 
                                            type="button" 
                                            variant="ghost" 
                                            size="sm" 
                                            onClick={() => {
                                                setIsNewClient(!isNewClient);
                                                setData('client_id', '');
                                                setData('new_client_name', '');
                                                setData('new_client_phone', '');
                                            }}
                                            className="text-primary h-auto py-1"
                                        >
                                            {isNewClient ? __('erp.select_existing_client') : (
                                                <><Plus className="w-3 h-3 me-1" /> {__('erp.add_new_client')}</>
                                            )}
                                        </Button>
                                    </div>
                                    
                                    {!isNewClient ? (
                                        <div className="space-y-2">
                                            {/* Note: This assumes passing 'clients' from controller if available. 
                                                If not passed, we might need a dynamic PremiumCombobox that fetches clients.
                                                For now we use a simple input or you could use a select if clients are passed. */}
                                            <Input
                                                id="client_id"
                                                type="text"
                                                placeholder={__('erp.client_id')}
                                                value={data.client_id}
                                                onChange={(e) => setData('client_id', e.target.value)}
                                                className={errors.client_id ? 'border-red-500' : ''}
                                            />
                                            <p className="text-xs text-slate-500">{__('erp.enter_client_id_or_create_new')}</p>
                                            {errors.client_id && <p className="text-sm text-red-600">{errors.client_id}</p>}
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-lg border border-slate-100">
                                            <div className="space-y-2">
                                                <Label htmlFor="new_client_name">{__('erp.client_name')} *</Label>
                                                <Input
                                                    id="new_client_name"
                                                    value={data.new_client_name}
                                                    onChange={(e) => setData('new_client_name', e.target.value)}
                                                    className={errors.new_client_name ? 'border-red-500 bg-white' : 'bg-white'}
                                                />
                                                {errors.new_client_name && <p className="text-sm text-red-600">{errors.new_client_name}</p>}
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="new_client_phone">{__('erp.client_phone')}</Label>
                                                <Input
                                                    id="new_client_phone"
                                                    value={data.new_client_phone}
                                                    onChange={(e) => setData('new_client_phone', e.target.value)}
                                                    className={errors.new_client_phone ? 'border-red-500 bg-white' : 'bg-white'}
                                                />
                                                {errors.new_client_phone && <p className="text-sm text-red-600">{errors.new_client_phone}</p>}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Date */}
                                <div className="space-y-2">
                                    <Label htmlFor="date">{__('erp.date')}</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={data.date}
                                        onChange={(e) => setData('date', e.target.value)}
                                        className={errors.date ? 'border-red-500' : ''}
                                    />
                                    {errors.date && <p className="text-sm text-red-600">{errors.date}</p>}
                                </div>

                                {/* Note */}
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="note">{__('erp.notes')} ({__('general.optional')})</Label>
                                    <Input
                                        id="note"
                                        value={data.note}
                                        onChange={(e) => setData('note', e.target.value)}
                                        className={errors.note ? 'border-red-500' : ''}
                                        placeholder={__('erp.transaction_note_placeholder')}
                                    />
                                    {errors.note && <p className="text-sm text-red-600">{errors.note}</p>}
                                </div>
                            </div>
                            
                            <div className="pt-4 flex justify-end">
                                <Button type="submit" disabled={processing} className="gap-2">
                                    <Save className="w-4 h-4" />
                                    {__('general.save')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </ERPLayout>
    );
}
