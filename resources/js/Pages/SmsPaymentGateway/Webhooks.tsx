import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Webhook Configuration</h2>}
        >
            <Head title="Webhooks - Payment Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header Actions */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Webhook className="w-6 h-6 text-indigo-600" />
                                Webhook Endpoint
                            </h1>
                            <p className="text-slate-500 mt-1">Receive real-time payment notifications on your server.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card>
                        <form onSubmit={handleSubmit}>
                            <CardHeader>
                                <CardTitle>Endpoint Settings</CardTitle>
                                <CardDescription>Configure where Payment Gateway should send HTTP POST requests when a payment is received.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="webhook_url">Payload URL</Label>
                                    <Input
                                        id="webhook_url"
                                        type="url"
                                        placeholder="https://your-domain.com/api/webhooks/autosms"
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
                                        placeholder="Used to compute HMAC-SHA256 signature"
                                        value={data.webhook_secret}
                                        onChange={e => setData('webhook_secret', e.target.value)}
                                    />
                                    <p className="text-xs text-slate-500">
                                        If left blank during creation, a secure random string will be generated automatically.
                                    </p>
                                </div>
                            </CardContent>
                            <CardFooter className="flex justify-between border-t p-4 bg-slate-50">
                                {webhook ? (
                                    <Button type="button" variant="destructive" onClick={handleDelete} className="bg-rose-600 hover:bg-rose-700 text-white">
                                        <Trash2 className="w-4 h-4 mr-2" />
                                        Remove Webhook
                                    </Button>
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
                                        <Activity className="w-5 h-5 text-indigo-500" />
                                        Delivery Statistics
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex justify-between items-center py-2 border-b">
                                        <span className="text-sm font-medium text-slate-500">Successful Deliveries</span>
                                        <span className="font-bold text-emerald-600">{webhook.success_count}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b">
                                        <span className="text-sm font-medium text-slate-500">Failed Deliveries</span>
                                        <span className="font-bold text-rose-600">{webhook.failure_count}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2">
                                        <span className="text-sm font-medium text-slate-500">Last Triggered</span>
                                        <span className="font-bold text-slate-700">
                                            {webhook.last_triggered_at ? new Date(webhook.last_triggered_at).toLocaleString() : 'Never'}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Test Connection</CardTitle>
                                    <CardDescription>Send a mock payload to verify your endpoint is receiving data.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-slate-600 mb-4">
                                        Clicking the button below will dispatch an HTTP POST request immediately to your configured URL.
                                    </p>
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

