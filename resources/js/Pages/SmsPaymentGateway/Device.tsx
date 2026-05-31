import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Smartphone, ArrowLeft, ShieldCheck, ShieldAlert, Clock, Database, Eraser, Eye, EyeOff, AlertTriangle, CheckCircle, Code, Activity
} from 'lucide-react';

interface DeviceProps {
    device: {
        id: number;
        device_name: string;
        phone_number: string | null;
        sim1_number?: string | null;
        sim2_number?: string | null;
        status: string;
        enable_spoof_detection: boolean;
        last_seen_at: string;
        created_at: string;
    };
    transactions: {
        data: any[];
        current_page: number;
        last_page: number;
        total: number;
        links: any[];
    };
}

export default function Device({ device, transactions }: DeviceProps) {
    const [maskData, setMaskData] = useState(true);

    const handleClearTransactions = () => {
        if (confirm('Wipe all logs for this terminal?')) {
            router.delete(route('sms-payment-gateway.clear-transactions', device.id));
        }
    };

    const handleToggleSpoof = () => {
        router.patch(route('sms-payment-gateway.toggle-spoof-detection', device.id));
    };

    const mask = (value: string | null | undefined, type: string) => {
        if (!maskData) return value || '---';
        if (!value) return '---';

        switch (type) {
            case 'phone':
                const digits = value.replace(/\D/g, '');
                return digits.length > 6 ? digits.substring(0, 3) + '****' + digits.substring(digits.length - 3) : '******';
            case 'name':
                const name = value.trim();
                return name.length > 2 ? name.charAt(0) + '*'.repeat(Math.min(name.length - 2, 8)) + name.charAt(name.length - 1) : '**';
            case 'sender':
                return value.length > 4 ? value.substring(0, 3) + '***' : '***';
            case 'amount':
                const currencyMatch = value.match(/[A-Z]{3,4}$/);
                const currency = currencyMatch ? currencyMatch[0] : '';
                return '•,•••.** ' + currency;
            default:
                return '••••••';
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Device Details</h2>}
        >
            <Head title={`Device - ${device.device_name || 'Terminal'}`} />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header Actions */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Smartphone className="w-6 h-6 text-indigo-600" />
                                {device.device_name || 'Linked Device'}
                            </h1>
                            <p className="text-slate-500 mt-1">Comprehensive diagnostic data</p>
                        </div>
                        <div className="flex gap-2">
                            <Button 
                                variant={maskData ? "default" : "outline"}
                                onClick={() => setMaskData(!maskData)}
                            >
                                {maskData ? <EyeOff className="w-4 h-4 mr-2" /> : <Eye className="w-4 h-4 mr-2" />}
                                {maskData ? 'Show Stats' : 'Hide Stats'}
                            </Button>
                            <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Back
                            </Button>
                        </div>
                    </div>

                    {/* Device Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card>
                            <CardContent className="p-4 flex items-center gap-4">
                                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                                    <Smartphone className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 uppercase">Network Identity</p>
                                    {(device.sim1_number || device.sim2_number) ? (
                                        <div className="flex flex-col gap-1 mt-1">
                                            {device.sim1_number && <p className="text-sm font-bold text-slate-800">S1: {mask(device.sim1_number, 'phone')}</p>}
                                            {device.sim2_number && <p className="text-sm font-bold text-slate-800">S2: {mask(device.sim2_number, 'phone')}</p>}
                                        </div>
                                    ) : (
                                        <p className="text-sm font-bold text-slate-800">{mask(device.phone_number, 'phone')}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4 flex items-center gap-4">
                                <div className={`w-10 h-10 rounded-full flex items-center justify-center ${device.status === 'connected' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'}`}>
                                    <Activity className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 uppercase">Live Status</p>
                                    <p className="text-sm font-bold capitalize">{device.status}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-4 flex items-center gap-4">
                                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                                    <Clock className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 uppercase">Last Sync Pulse</p>
                                    <p className="text-sm font-bold">{device.last_seen_at ? new Date(device.last_seen_at).toLocaleString() : 'Never'}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Transactions Log */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Database className="w-5 h-5 text-indigo-500" />
                                Diagnostic Log & Transactions
                            </CardTitle>
                            {transactions.total > 0 && (
                                <Button variant="destructive" size="sm" onClick={handleClearTransactions}>
                                    <Eraser className="w-4 h-4 mr-2" />
                                    Clear History
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent>
                            {transactions.total === 0 ? (
                                <div className="text-center py-8">
                                    <Database className="w-12 h-12 text-slate-200 mx-auto mb-3" />
                                    <p className="text-slate-500">No terminal data available yet.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm text-slate-600">
                                        <thead className="bg-slate-50 text-slate-500 text-xs uppercase border-b border-t">
                                            <tr>
                                                <th className="px-4 py-3 font-semibold">Event Date</th>
                                                <th className="px-4 py-3 font-semibold">Data Stream</th>
                                                <th className="px-4 py-3 font-semibold">Financial Impact</th>
                                                <th className="px-4 py-3 font-semibold">Integrity</th>
                                                <th className="px-4 py-3 font-semibold text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {transactions.data.map(tx => (
                                                <tr key={tx.id} className="hover:bg-slate-50 transition-colors">
                                                    <td className="px-4 py-3">
                                                        <div className="font-semibold text-slate-900">{new Date(tx.created_at).toLocaleDateString()}</div>
                                                        <div className="text-xs">{new Date(tx.created_at).toLocaleTimeString()}</div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="font-semibold text-slate-900">Incoming Payload</div>
                                                        <div className="text-xs">{mask(tx.sender, 'sender')}</div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="font-semibold text-emerald-600">
                                                            +{mask(`${tx.amount} ${tx.currency}`, 'amount')}
                                                        </div>
                                                        {tx.balance && (
                                                            <div className="text-xs">Bal: {mask(`${tx.balance} ${tx.currency}`, 'amount')}</div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {tx.is_spoofed ? (
                                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                                                <AlertTriangle className="w-3 h-3 mr-1" /> Anomalous
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                                <CheckCircle className="w-3 h-3 mr-1" /> Verified
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button variant="ghost" size="icon" onClick={() => alert(JSON.stringify(tx, null, 2))}>
                                                            <Code className="w-4 h-4 text-indigo-500" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}

