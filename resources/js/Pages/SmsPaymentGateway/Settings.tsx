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
        vodafone_cash_device_id: number | null;
        vodafone_cash_allowed_sender: string | null;
        instapay_device_id: number | null;
        instapay_allowed_sender: string | null;
        brand_name: string | null;
        hide_method_name: boolean;
        checkout_language: string;
        custom_logos?: {
            vodafone?: string;
            orange?: string;
            etisalat?: string;
            we?: string;
            instapay?: string;
        } | null;
    };
    devices: {
        id: number;
        device_name: string;
    }[];
}

export default function Settings({ settings, devices }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        wallet_phone_number: settings?.wallet_phone_number || '',
        instapay_phone_number: settings?.instapay_phone_number || '',
        vodafone_cash_phone_number: settings?.vodafone_cash_phone_number || '',
        is_instapay_enabled: settings?.is_instapay_enabled ?? true,
        is_vodafone_cash_enabled: settings?.is_vodafone_cash_enabled ?? true,
        whitelist_senders: settings?.whitelist_senders || '',
        vodafone_cash_device_id: settings?.vodafone_cash_device_id || '',
        vodafone_cash_allowed_sender: settings?.vodafone_cash_allowed_sender || '',
        instapay_device_id: settings?.instapay_device_id || '',
        instapay_allowed_sender: settings?.instapay_allowed_sender || '',
        brand_name: settings?.brand_name || '',
        hide_method_name: settings?.hide_method_name ?? false,
        checkout_language: settings?.checkout_language || 'ar',
        custom_logos: settings?.custom_logos || {
            vodafone: '',
            orange: '',
            etisalat: '',
            we: '',
            instapay: '',
        },
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('sms-payment-gateway.settings.store'), {
            preserveScroll: true,
            onSuccess: () => toast.success(__('admin.settings_saved_successfully')),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('sms_gateway.payment_gateway_settings')} />

            <div className="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">{__('sms_gateway.payment_gateway_settings')}</h1>
                    <p className="mt-2 text-sm text-gray-600">{__('general.configure_transfer_number_and_available_payment_methods_for_your_customers')}</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>{__('general.transfer_details')}</CardTitle>
                            <CardDescription>{__('general.this_is_the_number_that_will_appear_to_the_buyer_to_transfer_the_amount_to')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            
                            <div className="space-y-2">
                                <Label htmlFor="wallet_phone_number">{__('erp.primary_number_for_wallets_instapay')}</Label>
                                <Input
                                    id="wallet_phone_number"
                                    type="text"
                                    dir="ltr"
                                    className="text-left font-mono max-w-md"
                                    value={data.wallet_phone_number}
                                    onChange={(e) => setData('wallet_phone_number', e.target.value)}
                                    placeholder={__('general.example_01012345678')}
                                />
                                <p className="text-xs text-gray-500 max-w-md">{__('general.this_number_is_used_by_default_if_a_specific_number_is_not_set_for_each_payment_method')}</p>
                                {errors.wallet_phone_number && <p className="text-sm text-red-600">{errors.wallet_phone_number}</p>}
                            </div>

                            <hr className="my-6" />

                            <div className="space-y-4">
                                <h3 className="text-lg font-medium">{__('sms_gateway.checkout_page_customization')}</h3>
                                <div className="space-y-2">
                                    <Label htmlFor="brand_name">{__('sms_gateway.brand_name')}</Label>
                                    <Input
                                        id="brand_name"
                                        type="text"
                                        className="max-w-md"
                                        value={data.brand_name}
                                        onChange={(e) => setData('brand_name', e.target.value)}
                                        placeholder={__('sms_gateway.brand_name_hint')}
                                    />
                                    {errors.brand_name && <p className="text-sm text-red-600">{errors.brand_name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="checkout_language">{__('sms_gateway.checkout_language')}</Label>
                                    <select
                                        id="checkout_language"
                                        className="flex h-10 w-full max-w-md items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        value={data.checkout_language}
                                        onChange={(e) => setData('checkout_language', e.target.value)}
                                    >
                                        <option value="ar">{__('sms_gateway.language_arabic')}</option>
                                        <option value="en">{__('sms_gateway.language_english')}</option>
                                    </select>
                                    {errors.checkout_language && <p className="text-sm text-red-600">{errors.checkout_language}</p>}
                                </div>
                                <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50 mt-4">
                                    <div className="space-y-0.5">
                                        <Label className="text-base">{__('sms_gateway.hide_method_name')}</Label>
                                        <p className="text-sm text-gray-500">{__('sms_gateway.hide_method_name_desc')}</p>
                                    </div>
                                    <Switch
                                        checked={data.hide_method_name}
                                        onCheckedChange={(checked) => setData('hide_method_name', checked)}
                                    />
                                </div>
                            </div>

                            <hr className="my-6" />

                            <div className="space-y-4">
                                <h3 className="text-lg font-medium">{__('payment.available_payment_methods_and_dedicated')}</h3>
                                
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50">
                                        <div className="space-y-0.5">
                                            <Label className="text-base">{__('general.instapay')}</Label>
                                            <p className="text-sm text-gray-500">{__('general.allow_buyer_to_select_instapay_as_transfer_method')}</p>
                                        </div>
                                        <Switch
                                            checked={data.is_instapay_enabled}
                                            onCheckedChange={(checked) => setData('is_instapay_enabled', checked)}
                                        />
                                    </div>
                                    {data.is_instapay_enabled && (
                                        <div className="max-w-md p-4 border rounded-lg border-indigo-100 bg-indigo-50/30">
                                            <Label htmlFor="instapay_phone_number">{__('general.dedicated_instapay_numberaddress_optional')}</Label>
                                            <Input
                                                id="instapay_phone_number"
                                                type="text"
                                                dir="ltr"
                                                className="text-left font-mono mt-2"
                                                value={data.instapay_phone_number}
                                                onChange={(e) => setData('instapay_phone_number', e.target.value)}
                                                placeholder={__('general.example_userinstapay')}
                                            />
                                            {errors.instapay_phone_number && <p className="text-sm text-red-600 mt-1">{errors.instapay_phone_number}</p>}
                                            
                                            <div className="mt-4 pt-4 border-t border-indigo-100">
                                                <Label htmlFor="instapay_device_id">{__('general.dedicated_device_for_validation_internal')}</Label>
                                                <select
                                                    id="instapay_device_id"
                                                    className="w-full mt-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                    value={data.instapay_device_id}
                                                    onChange={(e) => setData('instapay_device_id', e.target.value)}
                                                >
                                                    <option value="">{__('general.any_device')}</option>
                                                    {devices?.map(device => (
                                                        <option key={device.id} value={device.id}>{device.device_name}</option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="mt-4">
                                                <Label htmlFor="instapay_allowed_sender">{__('general.dedicated_allowed_sender_internal')}</Label>
                                                <Input
                                                    id="instapay_allowed_sender"
                                                    type="text"
                                                    dir="ltr"
                                                    className="text-left font-mono mt-2"
                                                    value={data.instapay_allowed_sender}
                                                    onChange={(e) => setData('instapay_allowed_sender', e.target.value)}
                                                    placeholder={__('general.example_instapay')}
                                                />
                                                <p className="text-xs text-gray-500 mt-1">{__('general.only_match_transactions_from_this_specific_sender')}</p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-3 pt-4">
                                    <div className="flex items-center justify-between max-w-md p-4 border rounded-lg bg-gray-50/50">
                                        <div className="space-y-2">
                                            <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                                                <Label className="text-base">{__('erp.vodafone_cash_wallets')}</Label>
                                                <div className="flex items-center gap-1.5">
                                                    <img src="/assets/images/gateways/vodafone-cash.svg" alt="Vodafone" className="h-5 object-contain" />
                                                    <img src="/assets/images/gateways/orange-cash.svg" alt="Orange" className="h-5 rounded-sm object-contain" />
                                                    <img src="/assets/images/gateways/etisalat-cash.svg" alt="Etisalat" className="h-5 rounded-sm object-contain" />
                                                    <img src="/assets/images/gateways/we-pay.svg" alt="WE" className="h-5 rounded-sm object-contain" />
                                                </div>
                                            </div>
                                            <p className="text-sm text-gray-500">{__('general.allow_selecting_electronic_wallets')}</p>
                                        </div>
                                        <Switch
                                            checked={data.is_vodafone_cash_enabled}
                                            onCheckedChange={(checked) => setData('is_vodafone_cash_enabled', checked)}
                                        />
                                    </div>
                                    {data.is_vodafone_cash_enabled && (
                                        <div className="max-w-md p-4 border rounded-lg border-indigo-100 bg-indigo-50/30">
                                            <Label htmlFor="vodafone_cash_phone_number">{__('erp.dedicated_wallet_number_optional')}</Label>
                                            <Input
                                                id="vodafone_cash_phone_number"
                                                type="text"
                                                dir="ltr"
                                                className="text-left font-mono mt-2"
                                                value={data.vodafone_cash_phone_number}
                                                onChange={(e) => setData('vodafone_cash_phone_number', e.target.value)}
                                                placeholder={__('general.example_01012345678')}
                                            />
                                            {errors.vodafone_cash_phone_number && <p className="text-sm text-red-600 mt-1">{errors.vodafone_cash_phone_number}</p>}
                                            
                                            <div className="mt-4 pt-4 border-t border-indigo-100">
                                                <Label htmlFor="vodafone_cash_device_id">{__('general.dedicated_device_for_validation_internal')}</Label>
                                                <select
                                                    id="vodafone_cash_device_id"
                                                    className="w-full mt-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                    value={data.vodafone_cash_device_id}
                                                    onChange={(e) => setData('vodafone_cash_device_id', e.target.value)}
                                                >
                                                    <option value="">{__('general.any_device')}</option>
                                                    {devices?.map(device => (
                                                        <option key={device.id} value={device.id}>{device.device_name}</option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="mt-4">
                                                <Label htmlFor="vodafone_cash_allowed_sender">{__('general.dedicated_allowed_sender_internal')}</Label>
                                                <Input
                                                    id="vodafone_cash_allowed_sender"
                                                    type="text"
                                                    dir="ltr"
                                                    className="text-left font-mono mt-2"
                                                    value={data.vodafone_cash_allowed_sender}
                                                    onChange={(e) => setData('vodafone_cash_allowed_sender', e.target.value)}
                                                    placeholder={__('general.example_vodafone_cash')}
                                                />
                                                <p className="text-xs text-gray-500 mt-1">{__('general.only_match_transactions_from_this_specific_sender')}</p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            <hr className="my-6" />
                            
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium">{__('sms_gateway.allowed_sms_senders_whitelist')}</h3>
                                <div className="space-y-2">
                                    <Label htmlFor="whitelist_senders">{__('general.allowed_senders_commaseparated')}</Label>
                                    <textarea
                                        id="whitelist_senders"
                                        className="w-full max-w-md min-h-[100px] flex rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        dir="ltr"
                                        value={data.whitelist_senders}
                                        onChange={(e) => setData('whitelist_senders', e.target.value)}
                                        placeholder={__('general.e_money_vf_cash_cib')}
                                    />
                                    <p className="text-xs text-gray-500 max-w-md">{__('general.enter_the_names_of_the_senders_you_want_to_allow_to_process_incoming_messages_from_separated_by_commas_if_left_empty_all_default_senders_will_be_allowed')}</p>
                                    {errors.whitelist_senders && <p className="text-sm text-red-600">{errors.whitelist_senders}</p>}
                                </div>
                            </div>
                            
                            <hr className="my-6" />

                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-medium">{__('sms_gateway.custom_wallet_logos')}</h3>
                                    <p className="text-sm text-gray-500 mt-1">{__('sms_gateway.custom_wallet_logos_hint')}</p>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label>{__('sms_gateway.vodafone_cash_logo')}</Label>
                                        <Input
                                            type="url"
                                            dir="ltr"
                                            className="text-left font-mono"
                                            value={data.custom_logos?.vodafone || ''}
                                            onChange={(e) => setData('custom_logos', { ...data.custom_logos, vodafone: e.target.value })}
                                            placeholder="https://example.com/logo.png"
                                        />
                                        {errors['custom_logos.vodafone'] && <p className="text-sm text-red-600">{errors['custom_logos.vodafone']}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('sms_gateway.orange_cash_logo')}</Label>
                                        <Input
                                            type="url"
                                            dir="ltr"
                                            className="text-left font-mono"
                                            value={data.custom_logos?.orange || ''}
                                            onChange={(e) => setData('custom_logos', { ...data.custom_logos, orange: e.target.value })}
                                            placeholder="https://example.com/logo.png"
                                        />
                                        {errors['custom_logos.orange'] && <p className="text-sm text-red-600">{errors['custom_logos.orange']}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('sms_gateway.etisalat_cash_logo')}</Label>
                                        <Input
                                            type="url"
                                            dir="ltr"
                                            className="text-left font-mono"
                                            value={data.custom_logos?.etisalat || ''}
                                            onChange={(e) => setData('custom_logos', { ...data.custom_logos, etisalat: e.target.value })}
                                            placeholder="https://example.com/logo.png"
                                        />
                                        {errors['custom_logos.etisalat'] && <p className="text-sm text-red-600">{errors['custom_logos.etisalat']}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('sms_gateway.we_pay_logo')}</Label>
                                        <Input
                                            type="url"
                                            dir="ltr"
                                            className="text-left font-mono"
                                            value={data.custom_logos?.we || ''}
                                            onChange={(e) => setData('custom_logos', { ...data.custom_logos, we: e.target.value })}
                                            placeholder="https://example.com/logo.png"
                                        />
                                        {errors['custom_logos.we'] && <p className="text-sm text-red-600">{errors['custom_logos.we']}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('general.instapay_logo')}</Label>
                                        <Input
                                            type="url"
                                            dir="ltr"
                                            className="text-left font-mono"
                                            value={data.custom_logos?.instapay || ''}
                                            onChange={(e) => setData('custom_logos', { ...data.custom_logos, instapay: e.target.value })}
                                            placeholder="https://example.com/logo.png"
                                        />
                                        {errors['custom_logos.instapay'] && <p className="text-sm text-red-600">{errors['custom_logos.instapay']}</p>}
                                    </div>
                                </div>
                            </div>

                            <div className="pt-6">
                                <Button type="submit" disabled={processing} className="w-full sm:w-auto">{__('admin.save_settings')}</Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
