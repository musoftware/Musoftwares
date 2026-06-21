import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    ArrowLeft, Beaker, Zap, Database, Trash2, Power, Send
} from 'lucide-react';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { __ } from '@/lib/i18n';

interface TestModeProps {
    testModeEnabled: boolean;
    webhook: any;
    testTransactionsCount: number;
    testOrdersCount: number;
    testTransactions: any[];
}

export default function TestMode({ testModeEnabled, webhook, testTransactionsCount, testOrdersCount, testTransactions }: TestModeProps) {
    const { data: smsData, setData: setSmsData, post: postSms, processing: processingSms } = useForm({
        sender: 'Test-Sender',
        sms_text: 'You have received 150.00 EGP from Test User',
    });

    const { data: hookData, setData: setHookData, post: postHook, processing: processingHook } = useForm({
        amount: '150.00',
        phone_number: '01012345678',
    });

    const handleToggle = () => {
        router.post(route('sms-payment-gateway.test-mode.toggle'));
    };

    const handleClear = () => {
        if (confirm('Delete all mock transactions generated in Test Mode?')) {
            router.delete(route('sms-payment-gateway.test-mode.clear-data'));
        }
    };

    const handleSmsSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postSms(route('sms-payment-gateway.test-mode.create-transaction'));
    };

    const handleHookSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postHook(route('sms-payment-gateway.test-mode.send-webhook'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.simulation_environment')}</h2>}>
            <Head title={__('general.test_mode_text_payment_gateway')} />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Beaker className="w-6 h-6 text-indigo-600" />{__('general.test_environment')}</h1>
                            <p className="text-slate-500 mt-1">{__('general.safely_simulate_incoming_sms_payloads_and_test_your_application_integrations')}</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 me-2" />{__('general.back_to_dashboard')}</Button>
                    </div>

                    <Card className="border-indigo-100 bg-indigo-50/30">
                        <CardContent className="p-6 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div className="flex items-center gap-4">
                                <div className={`w-14 h-14 rounded-full flex items-center justify-center ${testModeEnabled ? 'bg-amber-100 text-amber-600' : 'bg-slate-200 text-slate-500'}`}>
                                    <Power className="w-7 h-7" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900">Sandbox Status: {testModeEnabled ? 'Active' : 'Offline'}</h3>
                                    <p className="text-sm text-slate-600">{__('general.when_active_mock_payloads_are_permitted_and_explicitly_tagged_as_test_data')}</p>
                                </div>
                            </div>
                            <Button 
                                size="lg"
                                onClick={handleToggle}
                                className={testModeEnabled ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-slate-800 hover:bg-slate-900'}
                            >
                                {testModeEnabled ? 'Deactivate Test Mode' : 'Enable Test Mode'}
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card className={!testModeEnabled ? 'opacity-60 pointer-events-none' : ''}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Zap className="w-5 h-5 text-indigo-500" />{__('general.simulate_sms_arrival')}</CardTitle>
                                <CardDescription>{__('general.trigger_the_text_payment_gateway_extraction_engine_as_if_a_physical_device_received_a_text')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSmsSubmit} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="sender">{__('general.sms_sender_alias')}</Label>
                                        <Input
                                            id="sender"
                                            value={smsData.sender}
                                            onChange={e => setSmsData('sender', e.target.value)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="sms_text">{__('general.raw_message_text')}</Label>
                                        <textarea
                                            id="sms_text"
                                            value={smsData.sms_text}
                                            onChange={e => setSmsData('sms_text', e.target.value)}
                                            required
                                            className="w-full min-h-[100px] flex rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                    </div>
                                    <Button type="submit" disabled={processingSms} className="w-full bg-indigo-600 hover:bg-indigo-700">
                                        <Send className="w-4 h-4 me-2" />{__('general.dispatch_sms_payload')}</Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card className={!testModeEnabled ? 'opacity-60 pointer-events-none' : ''}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Zap className="w-5 h-5 text-emerald-500" />{__('general.simulate_webhook_fire')}</CardTitle>
                                <CardDescription>{__('general.bypass_extraction_and_directly_fire_a_synthetic_test_payload_to_your_active_webhook')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!webhook ? (
                                    <Alert className="bg-amber-50 text-amber-800 border-amber-200">
                                        <AlertDescription>{__('general.no_active_webhook_configuration_found_register_one_first')}</AlertDescription>
                                    </Alert>
                                ) : (
                                    <form onSubmit={handleHookSubmit} className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="amount">Mock Amount (EGP)</Label>
                                            <Input
                                                id="amount"
                                                type="number"
                                                step="0.01"
                                                value={hookData.amount}
                                                onChange={e => setHookData('amount', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="phone_number">{__('general.mock_target_identifier')}</Label>
                                            <Input
                                                id="phone_number"
                                                type="text"
                                                value={hookData.phone_number}
                                                onChange={e => setHookData('phone_number', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <Button type="submit" disabled={processingHook} className="w-full bg-emerald-600 hover:bg-emerald-700 text-white">
                                            <Send className="w-4 h-4 me-2" />{__('general.fire_webhook')}</Button>
                                    </form>
                                )}

                                <div className="mt-8 pt-6 border-t">
                                    <div className="flex items-center justify-between mb-4">
                                        <h4 className="font-semibold text-slate-800 flex items-center gap-2">
                                            <Database className="w-4 h-4" />{__('general.sandbox_metrics')}</h4>
                                        <Button variant="ghost" size="sm" onClick={handleClear} className="text-rose-600 hover:text-rose-700 hover:bg-rose-50">
                                            <Trash2 className="w-4 h-4 me-2" />{__('general.flush_test_data')}</Button>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b text-sm">
                                        <span className="text-slate-500">{__('general.simulated_transactions')}</span>
                                        <span className="font-bold text-slate-700">{testTransactionsCount}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 text-sm">
                                        <span className="text-slate-500">{__('general.test_payment_orders')}</span>
                                        <span className="font-bold text-slate-700">{testOrdersCount}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

