import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import {
    ArrowLeft, Wallet, Plus, Trash2, Smartphone
} from 'lucide-react';

interface WalletProps {
    wallets: any[];
}

export default function Wallets({ wallets }: WalletProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        payment_type: 'Wallet',
        phone_number: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('sms-payment-gateway.wallets.store'), {
            onSuccess: () => reset('phone_number'),
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to remove this wallet?')) {
            router.delete(route('sms-payment-gateway.wallets.delete', id));
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.linked_wallets')}</h2>}
        >
            <Head title={__('general.wallets_payment_gateway')} />

            <div className="py-8 md:py-12">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header Actions */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Wallet className="w-6 h-6 text-indigo-600" />{__('general.payment_identifiers')}</h1>
                            <p className="text-slate-500 mt-1">{__('general.register_the_phone_numbers_or_aliases_where_you_receive_money')}</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />{__('general.back_to_dashboard')}</Button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Add Wallet Form */}
                        <div className="md:col-span-1">
                            <Card>
                                <form onSubmit={handleSubmit}>
                                    <CardHeader>
                                        <CardTitle>{__('general.add_identifier')}</CardTitle>
                                        <CardDescription>{__('general.add_a_new_e_wallet_or_instapay_alias')}</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="payment_type">{__('general.service_type')}</Label>
                                            <select
                                                id="payment_type"
                                                className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                value={data.payment_type}
                                                onChange={e => setData('payment_type', e.target.value)}
                                            >
                                                <option value="Wallet">E-Wallet (Vodafone/Etisalat/etc)</option>
                                                <option value="Instapay">{__('general.instapay_alias')}</option>
                                            </select>
                                            {errors.payment_type && <p className="text-sm text-red-600">{errors.payment_type}</p>}
                                        </div>
                                        
                                        <div className="space-y-2">
                                            <Label htmlFor="phone_number">Identifier (Number / Alias)</Label>
                                            <Input
                                                id="phone_number"
                                                type="text"
                                                placeholder={__('general.e_g_01012345678')}
                                                value={data.phone_number}
                                                onChange={e => setData('phone_number', e.target.value)}
                                                required
                                            />
                                            {errors.phone_number && <p className="text-sm text-red-600">{errors.phone_number}</p>}
                                        </div>
                                    </CardContent>
                                    <div className="p-4 border-t bg-slate-50">
                                        <Button type="submit" disabled={processing} className="w-full bg-indigo-600 hover:bg-indigo-700 text-white">
                                            <Plus className="w-4 h-4 mr-2" />{__('general.add_record')}</Button>
                                    </div>
                                </form>
                            </Card>
                        </div>

                        {/* Wallets List */}
                        <div className="md:col-span-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{__('general.registered_records')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {wallets.length === 0 ? (
                                        <div className="text-center py-8">
                                            <Wallet className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                            <p className="text-slate-500">{__('general.no_payment_identifiers_added_yet')}</p>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {wallets.map(wallet => (
                                                <div key={wallet.id} className="flex items-center justify-between p-4 border rounded-xl hover:shadow-sm transition-shadow bg-white">
                                                    <div className="flex items-center gap-3">
                                                        <div className={`w-10 h-10 rounded-full flex items-center justify-center ${wallet.payment_type === 'Instapay' ? 'bg-purple-100 text-purple-600' : 'bg-rose-100 text-rose-600'}`}>
                                                            {wallet.payment_type === 'Instapay' ? <span className="font-bold">IP</span> : <Smartphone className="w-5 h-5" />}
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-slate-800">{wallet.phone_number}</p>
                                                            <p className="text-xs text-slate-500 uppercase font-semibold tracking-wider">{wallet.payment_type}</p>
                                                        </div>
                                                    </div>
                                                    <Button variant="ghost" size="icon" onClick={() => handleDelete(wallet.id)} className="text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

