import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import {
    ArrowLeft, Webhook, Activity, RefreshCw, Trash2, ShieldCheck, CheckCircle2, Play
} from 'lucide-react';

interface WebhooksProps {
    webhook: {
        id: number;
        webhook_url: string;
        webhook_secret: string;
        is_active: boolean;
        success_count: number;
        failure_count: number;
        last_triggered_at: string | null;
    } | null;
}

export default function Webhooks({ webhook }: WebhooksProps) {
    const { data, setData, post, processing, errors } = useForm({
        webhook_url: webhook?.webhook_url || '',
        webhook_secret: webhook?.webhook_secret || '',
    });

    const [testing, setTesting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('sms-payment-gateway.webhook.update'));
    };

    const handleDelete = () => {
        if (webhook && confirm('Are you sure you want to remove this webhook?')) {
            router.delete(route('sms-payment-gateway.webhook.delete', webhook.id));
        }
    };

    const handleTest = () => {
        setTesting(true);
        router.post(route('sms-payment-gateway.webhook.test'), {}, {
            onFinish: () => setTesting(false)
        });
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.webhook_configuration')}</h2>}
        >
            <Head title={__('general.webhooks_payment_gateway')} />

            <div className="py-8 md:py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header Actions */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Webhook className="w-6 h-6 text-indigo-600" />{__('general.webhook_endpoint')}</h1>
                            <p className="text-slate-500 mt-1">{__('general.receive_real_time_payment_notifications_on_your_server')}</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />{__('general.back_to_dashboard')}</Button>
                    </div>

                    <Card>
                        <form onSubmit={handleSubmit}>
                            <CardHeader>
                                <CardTitle>{__('general.endpoint_settings')}</CardTitle>
                                <CardDescription>{__('general.configure_where_payment_gateway_should_send_http_post_requests_when_a_payment_is_received')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="webhook_url">{__('general.payload_url')}</Label>
                                    <Input
                                        id="webhook_url"
                                        type="url"
                                        placeholder={__('general.https_your_domain_com_api_webhooks_autosms')}
                                        value={data.webhook_url}
                                        onChange={e => setData('webhook_url', e.target.value)}
                                        required
                                    />
                                    {errors.webhook_url && <p className="text-sm text-red-600">{errors.webhook_url}</p>}
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="webhook_secret">Secret Token (Optional)</Label>
                                    <Input
                                        id="webhook_secret"
                                        type="text"
                                        placeholder={__('general.used_to_compute_hmac_sha256_signature')}
                                        value={data.webhook_secret}
                                        onChange={e => setData('webhook_secret', e.target.value)}
                                    />
                                    <p className="text-xs text-slate-500">{__('general.if_left_blank_during_creation_a_secure_random_string_will_be_generated_automatically')}</p>
                                </div>
                            </CardContent>
                            <CardFooter className="flex justify-between border-t p-4 bg-slate-50">
                                {webhook ? (
                                    <Button type="button" variant="destructive" onClick={handleDelete} className="bg-rose-600 hover:bg-rose-700 text-white">
                                        <Trash2 className="w-4 h-4 mr-2" />{__('general.remove_webhook')}</Button>
                                ) : (
                                    <div />
                                )}
                                <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                    {processing ? <RefreshCw className="w-4 h-4 mr-2 animate-spin" /> : <ShieldCheck className="w-4 h-4 mr-2" />}
                                    {webhook ? 'Update Settings' : 'Register Webhook'}
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>

                    {webhook && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <Activity className="w-5 h-5 text-indigo-500" />{__('general.delivery_statistics')}</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex justify-between items-center py-2 border-b">
                                        <span className="text-sm font-medium text-slate-500">{__('general.successful_deliveries')}</span>
                                        <span className="font-bold text-emerald-600">{webhook.success_count}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b">
                                        <span className="text-sm font-medium text-slate-500">{__('general.failed_deliveries')}</span>
                                        <span className="font-bold text-rose-600">{webhook.failure_count}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2">
                                        <span className="text-sm font-medium text-slate-500">{__('general.last_triggered')}</span>
                                        <span className="font-bold text-slate-700">
                                            {webhook.last_triggered_at ? new Date(webhook.last_triggered_at).toLocaleString() : 'Never'}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">{__('general.test_connection')}</CardTitle>
                                    <CardDescription>{__('general.send_a_mock_payload_to_verify_your_endpoint_is_receiving_data')}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-slate-600 mb-4">{__('general.clicking_the_button_below_will_dispatch_an_http_post_request_immediately_to_your_configured_url')}</p>
                                    <Button 
                                        onClick={handleTest} 
                                        disabled={testing}
                                        className="w-full"
                                        variant="outline"
                                    >
                                        {testing ? (
                                            <RefreshCw className="w-4 h-4 mr-2 animate-spin text-indigo-600" />
                                        ) : (
                                            <Play className="w-4 h-4 mr-2 text-indigo-600" />
                                        )}
                                        {testing ? 'Dispatching...' : 'Send Ping Event'}
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

