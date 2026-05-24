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
        router.post(route('autosms.test-mode.toggle'));
    };

    const handleClear = () => {
        if (confirm('Delete all mock transactions generated in Test Mode?')) {
            router.delete(route('autosms.test-mode.clear-data'));
        }
    };

    const handleSmsSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postSms(route('autosms.test-mode.create-transaction'));
    };

    const handleHookSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postHook(route('autosms.test-mode.send-webhook'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Simulation Environment</h2>}>
            <Head title="Test Mode - AutoSMS" />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Beaker className="w-6 h-6 text-indigo-600" />
                                Test Environment
                            </h1>
                            <p className="text-slate-500 mt-1">Safely simulate incoming SMS payloads and test your application integrations.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('autosms.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card className="border-indigo-100 bg-indigo-50/30">
                        <CardContent className="p-6 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div className="flex items-center gap-4">
                                <div className={`w-14 h-14 rounded-full flex items-center justify-center ${testModeEnabled ? 'bg-amber-100 text-amber-600' : 'bg-slate-200 text-slate-500'}`}>
                                    <Power className="w-7 h-7" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900">Sandbox Status: {testModeEnabled ? 'Active' : 'Offline'}</h3>
                                    <p className="text-sm text-slate-600">When active, mock payloads are permitted and explicitly tagged as test data.</p>
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
                                    <Zap className="w-5 h-5 text-indigo-500" />
                                    Simulate SMS Arrival
                                </CardTitle>
                                <CardDescription>Trigger the AutoSMS extraction engine as if a physical device received a text.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSmsSubmit} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="sender">SMS Sender Alias</Label>
                                        <Input
                                            id="sender"
                                            value={smsData.sender}
                                            onChange={e => setSmsData('sender', e.target.value)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="sms_text">Raw Message Text</Label>
                                        <textarea
                                            id="sms_text"
                                            value={smsData.sms_text}
                                            onChange={e => setSmsData('sms_text', e.target.value)}
                                            required
                                            className="w-full min-h-[100px] flex rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                    </div>
                                    <Button type="submit" disabled={processingSms} className="w-full bg-indigo-600 hover:bg-indigo-700">
                                        <Send className="w-4 h-4 mr-2" /> Dispatch SMS Payload
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card className={!testModeEnabled ? 'opacity-60 pointer-events-none' : ''}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Zap className="w-5 h-5 text-emerald-500" />
                                    Simulate Webhook Fire
                                </CardTitle>
                                <CardDescription>Bypass extraction and directly fire a synthetic test payload to your active webhook.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!webhook ? (
                                    <Alert className="bg-amber-50 text-amber-800 border-amber-200">
                                        <AlertDescription>No active webhook configuration found. Register one first.</AlertDescription>
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
                                            <Label htmlFor="phone_number">Mock Target Identifier</Label>
                                            <Input
                                                id="phone_number"
                                                type="text"
                                                value={hookData.phone_number}
                                                onChange={e => setHookData('phone_number', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <Button type="submit" disabled={processingHook} className="w-full bg-emerald-600 hover:bg-emerald-700 text-white">
                                            <Send className="w-4 h-4 mr-2" /> Fire Webhook
                                        </Button>
                                    </form>
                                )}

                                <div className="mt-8 pt-6 border-t">
                                    <div className="flex items-center justify-between mb-4">
                                        <h4 className="font-semibold text-slate-800 flex items-center gap-2">
                                            <Database className="w-4 h-4" /> Sandbox Metrics
                                        </h4>
                                        <Button variant="ghost" size="sm" onClick={handleClear} className="text-rose-600 hover:text-rose-700 hover:bg-rose-50">
                                            <Trash2 className="w-4 h-4 mr-2" /> Flush Test Data
                                        </Button>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b text-sm">
                                        <span className="text-slate-500">Simulated Transactions</span>
                                        <span className="font-bold text-slate-700">{testTransactionsCount}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 text-sm">
                                        <span className="text-slate-500">Test Payment Orders</span>
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

