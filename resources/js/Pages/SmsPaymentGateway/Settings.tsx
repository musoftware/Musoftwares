import { __ } from '@/lib/i18n';
import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { toast } from 'sonner';

interface Props {
    settings: {
        wallet_phone_number: string | null;
        instapay_phone_number: string | null;
        vodafone_cash_phone_number: string | null;
        is_instapay_enabled: boolean;
        is_vodafone_cash_enabled: boolean;
        whitelist_senders: string | null;
    };
}

export default function Settings({ settings }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        wallet_phone_number: settings?.wallet_phone_number || '',
        instapay_phone_number: settings?.instapay_phone_number || '',
        vodafone_cash_phone_number: settings?.vodafone_cash_phone_number || '',
        is_instapay_enabled: settings?.is_instapay_enabled ?? true,
        is_vodafone_cash_enabled: settings?.is_vodafone_cash_enabled ?? true,
        whitelist_senders: settings?.whitelist_senders || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('sms-payment-gateway.settings.store'), {
            preserveScroll: true,
            onSuccess: () => toast.success(__('Settings saved successfully')),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('Payment Gateway Settings')} />

            <div className="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">{__('Payment Gateway Settings')}</h1>
                    <p className="mt-2 text-sm text-gray-600">{__('Configure transfer number and available payment methods for your customers.')}</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>{__('Transfer Details')}</CardTitle>
                            <CardDescription>{__('This is the number that will appear to the buyer to transfer the amount to.')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            
                            <div className="space-y-2">
                                <Label htmlFor="wallet_phone_number">{__('Primary Number (for Wallets & Instapay)')}</Label>
                                <Input
                                    id="wallet_phone_number"
                                    type="text"
                                    dir="ltr"
                                    className="text-left font-mono max-w-md"
                                    value={data.wallet_phone_number}
                                    onChange={(e) => setData('wallet_phone_number', e.target.value)}
                                    placeholder={__('Example: 01012345678')}
                                />
                                <p className="text-xs text-gray-500 max-w-md">{__('This number is used by default if a specific number is not set for each payment method.')}</p>
                                {errors.wallet_phone_number && <p className="text-sm text-red-600">{errors.wallet_phone_number}</p>}
                            </div>

                            <hr className="my-6" />

                            <div className="space-y-4">
                                <h3 className="text-lg font-medium">{__('Available payment methods and dedicated numbers')}</h3>
                                
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">{__('Instapay')}</Label>
                                            <p className="text-sm text-gray-500">{__('Allow buyer to select Instapay as transfer method.')}</p>
                                        </div>
                                        <Switch
                                            checked={data.is_instapay_enabled}
                                            onCheckedChange={(checked) => setData('is_instapay_enabled', checked)}
                                        />
                                    </div>
                                    {data.is_instapay_enabled && (
                                        <div className="max-w-md p-4 border rounded-lg border-indigo-100 bg-indigo-50/30">
                                            <Label htmlFor="instapay_phone_number">{__('Dedicated Instapay Number/Address (optional)')}</Label>
                                            <Input
                                                id="instapay_phone_number"
                                                type="text"
                                                dir="ltr"
                                                className="text-left font-mono mt-2"
                                                value={data.instapay_phone_number}
                                                onChange={(e) => setData('instapay_phone_number', e.target.value)}
                                                placeholder={__('Example: user@instapay')}
                                            />
                                            {errors.instapay_phone_number && <p className="text-sm text-red-600 mt-1">{errors.instapay_phone_number}</p>}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-3 pt-4">
                                    <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">{__('Vodafone Cash / Wallets')}</Label>
                                            <p className="text-sm text-gray-500">{__('Allow selecting electronic wallets.')}</p>
                                        </div>
                                        <Switch
                                            checked={data.is_vodafone_cash_enabled}
                                            onCheckedChange={(checked) => setData('is_vodafone_cash_enabled', checked)}
                                        />
                                    </div>
                                    {data.is_vodafone_cash_enabled && (
                                        <div className="max-w-md p-4 border rounded-lg border-indigo-100 bg-indigo-50/30">
                                            <Label htmlFor="vodafone_cash_phone_number">{__('Dedicated Wallet Number (optional)')}</Label>
                                            <Input
                                                id="vodafone_cash_phone_number"
                                                type="text"
                                                dir="ltr"
                                                className="text-left font-mono mt-2"
                                                value={data.vodafone_cash_phone_number}
                                                onChange={(e) => setData('vodafone_cash_phone_number', e.target.value)}
                                                placeholder={__('Example: 01012345678')}
                                            />
                                            {errors.vodafone_cash_phone_number && <p className="text-sm text-red-600 mt-1">{errors.vodafone_cash_phone_number}</p>}
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            <hr className="my-6" />
                            
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium">{__('Allowed SMS Senders (Whitelist)')}</h3>
                                <div className="space-y-2">
                                    <Label htmlFor="whitelist_senders">{__('Allowed Senders (Comma-separated)')}</Label>
                                    <textarea
                                        id="whitelist_senders"
                                        className="w-full max-w-md min-h-[100px] flex rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        dir="ltr"
                                        value={data.whitelist_senders}
                                        onChange={(e) => setData('whitelist_senders', e.target.value)}
                                        placeholder="e& money, VF-Cash, CIB"
                                    />
                                    <p className="text-xs text-gray-500 max-w-md">{__('Enter the names of the senders you want to allow to process incoming messages from, separated by commas. If left empty, all default senders will be allowed.')}</p>
                                    {errors.whitelist_senders && <p className="text-sm text-red-600">{errors.whitelist_senders}</p>}
                                </div>
                            </div>

                            <div className="pt-6">
                                <Button type="submit" disabled={processing} className="w-full sm:w-auto">{__('Save Settings')}</Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
