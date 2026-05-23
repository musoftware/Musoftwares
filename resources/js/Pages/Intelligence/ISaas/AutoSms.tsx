import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Smartphone, Plus, Download, ShieldCheck, Activity, Radio, AlertCircle
} from 'lucide-react';
import { Alert, AlertDescription } from "@/Components/ui/alert";

interface PageProps {
    devices: any[];
    webhook: any;
    token: any;
    stats: {
        total_devices: number;
        connected_devices: number;
        total_transactions: number;
        webhook_configured: boolean;
    };
    recentTransactions: any[];
}

export default function AutoSms({ devices, webhook, token, stats, recentTransactions }: PageProps) {
    const [generating, setGenerating] = useState(false);

    const handleConnectDevice = () => {
        setGenerating(true);
        // This would call an API or route to generate the QR code
        router.post(route('intelligence.isaas.autosms.generate-qr'));
        // For demonstration, reset immediately
        setTimeout(() => setGenerating(false), 2000);
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">iSAAS Auto SMS Gateway</h2>}
        >
            <Head title="iSAAS Auto SMS Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Hero Header */}
                    <div className="relative mb-8 p-6 sm:p-8 rounded-2xl bg-gradient-to-br from-indigo-900 to-purple-900 text-white shadow-xl overflow-hidden">
                        <div className="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-3xl" />
                        <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-purple-500/20 rounded-full blur-2xl" />
                        
                        <div className="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div className="flex items-center gap-4">
                                <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20">
                                    <Smartphone className="w-7 h-7 text-white" />
                                </div>
                                <div>
                                    <h1 className="text-2xl sm:text-3xl font-bold tracking-tight">Auto SMS Gateway</h1>
                                    <p className="text-indigo-200 mt-1">
                                        Connect your Android device as an intelligent SMS gateway for your applications.
                                    </p>
                                </div>
                            </div>
                            
                            <div className="flex flex-col sm:flex-row gap-3">
                                <a href="/sms-cash.apk" target="_blank" rel="noopener noreferrer">
                                    <Button variant="outline" className="bg-white/10 border-white/20 text-white hover:bg-white/20">
                                        <Download className="w-4 h-4 mr-2" /> Download APK
                                    </Button>
                                </a>
                                <Button 
                                    onClick={handleConnectDevice} 
                                    disabled={generating}
                                    className="bg-white text-indigo-900 hover:bg-indigo-50 font-semibold shadow-lg shadow-black/20"
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    {generating ? 'Generating QR...' : 'Connect Device'}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Stats Overview */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">Connected Devices</p>
                                        <p className="text-2xl font-bold text-slate-900">{stats.connected_devices} / {stats.total_devices}</p>
                                    </div>
                                    <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <Radio className="w-5 h-5 text-emerald-600" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">Total SMS</p>
                                        <p className="text-2xl font-bold text-slate-900">{stats.total_transactions.toLocaleString()}</p>
                                    </div>
                                    <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <Activity className="w-5 h-5 text-blue-600" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">Webhook Status</p>
                                        <p className="text-lg font-bold text-slate-900">
                                            {stats.webhook_configured ? 'Active' : 'Not Configured'}
                                        </p>
                                    </div>
                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center ${stats.webhook_configured ? 'bg-emerald-100' : 'bg-amber-100'}`}>
                                        <ShieldCheck className={`w-5 h-5 ${stats.webhook_configured ? 'text-emerald-600' : 'text-amber-600'}`} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Devices List */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Devices</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {devices.length === 0 ? (
                                <div className="text-center py-8">
                                    <Smartphone className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500">No devices connected yet.</p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {devices.map(device => (
                                        <div key={device.id} className="flex items-center justify-between p-4 border rounded-xl hover:bg-slate-50 transition-colors">
                                            <div className="flex items-center gap-4">
                                                <div className={`w-3 h-3 rounded-full ${device.status === 'connected' ? 'bg-emerald-500' : 'bg-slate-300'}`} />
                                                <div>
                                                    <p className="font-semibold text-slate-800">{device.device_name || 'Unknown Device'}</p>
                                                    <p className="text-xs text-slate-500">Last seen: {new Date(device.last_seen_at).toLocaleString()}</p>
                                                </div>
                                            </div>
                                            <div className="text-sm font-mono text-slate-600">
                                                {device.phone_number || 'No Number Linked'}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* API Integration Alert */}
                    {!token && (
                        <Alert variant="default" className="bg-indigo-50 border-indigo-200">
                            <AlertCircle className="h-4 w-4 text-indigo-600" />
                            <AlertDescription className="text-indigo-800">
                                You need to generate an API Token from your profile settings to fully utilize the programmatic SMS gateway endpoints.
                            </AlertDescription>
                        </Alert>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
