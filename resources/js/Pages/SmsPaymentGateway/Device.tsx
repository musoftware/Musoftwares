import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Smartphone, ArrowLeft, ShieldCheck, ShieldAlert, Clock, Database, Eraser, Eye, EyeOff, AlertTriangle, CheckCircle, Code, Activity, Settings, Plus, Trash2
} from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

interface DeviceProps {
    device: {
        id: number;
        device_name: string;
        phone_number: string | null;
        sim1_number?: string | null;
        sim2_number?: string | null;
        status: string;
        enable_spoof_detection: boolean;
        metadata?: {
            sim1_configs?: { allowed_sender: string }[];
            sim2_configs?: { allowed_sender: string }[];
        };
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

    const { data, setData, put, processing, errors } = useForm({
        device_name: device.device_name || '',
        sim1_configs: device.metadata?.sim1_configs || [],
        sim2_configs: device.metadata?.sim2_configs || [],
    });

    const addConfig = (sim: 'sim1_configs' | 'sim2_configs') => {
        setData(sim, [...data[sim], { allowed_sender: '' }]);
    };

    const updateConfig = (sim: 'sim1_configs' | 'sim2_configs', index: number, field: string, value: string) => {
        const newConfigs = [...data[sim]];
        newConfigs[index] = { ...newConfigs[index], [field]: value };
        setData(sim, newConfigs);
    };

    const removeConfig = (sim: 'sim1_configs' | 'sim2_configs', index: number) => {
        const newConfigs = [...data[sim]];
        newConfigs.splice(index, 1);
        setData(sim, newConfigs);
    };

    const submitSettings = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('sms-payment-gateway.update-device', device.id));
    };

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
                                    <div className="flex flex-col gap-1 mt-1">
                                        <p className="text-sm font-medium text-slate-500">S1: <span className="font-bold text-slate-800">{device.sim1_number ? mask(device.sim1_number, 'phone') : __('Not set')}</span></p>
                                        <p className="text-sm font-medium text-slate-500">S2: <span className="font-bold text-slate-800">{device.sim2_number ? mask(device.sim2_number, 'phone') : __('Not set')}</span></p>
                                    </div>
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

                    {/* Device Settings Form */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Settings className="w-5 h-5 text-indigo-500" />
                                {__('Device Configuration')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitSettings} className="space-y-6">
                                {Object.keys(errors).length > 0 && (
                                    <div className="bg-red-50 text-red-600 p-4 rounded-md text-sm border border-red-200">
                                        <p className="font-semibold mb-2">Please fix the following errors:</p>
                                        <ul className="list-disc pl-5">
                                            {Object.entries(errors).map(([key, error]) => (
                                                <li key={key}>{key}: {error}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                <div className="space-y-2 max-w-md">
                                    <Label>{__('Device Name')}</Label>
                                    <Input
                                        value={data.device_name}
                                        onChange={(e) => setData('device_name', e.target.value)}
                                        placeholder={__('e.g. Branch 1 Phone')}
                                    />
                                    {errors.device_name && <p className="text-sm text-red-500">{errors.device_name}</p>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* SIM 1 Config */}
                                    <div className="space-y-4 p-4 border rounded-lg bg-slate-50">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h3 className="font-semibold text-slate-800">{__('SIM 1 Configuration')}</h3>
                                                <p className="text-xs text-slate-500 mt-0.5">{device.sim1_number ? mask(device.sim1_number, 'phone') : __('Not set')}</p>
                                            </div>
                                            <Button type="button" variant="outline" size="sm" onClick={() => addConfig('sim1_configs')}>
                                                <Plus className="w-4 h-4 mr-2" />
                                                {__('Add Sender')}
                                            </Button>
                                        </div>
                                        {data.sim1_configs.length === 0 && (
                                            <p className="text-sm text-slate-500 text-center py-4">{__('No senders configured for SIM 1.')}</p>
                                        )}
                                        {data.sim1_configs.map((conf, idx) => (
                                            <div key={idx} className="bg-white p-3 border rounded-md shadow-sm space-y-3 relative">
                                                <Button 
                                                    type="button" 
                                                    variant="ghost" 
                                                    size="icon" 
                                                    className="absolute top-2 right-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50"
                                                    onClick={() => removeConfig('sim1_configs', idx)}
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                                <div className="pr-8 space-y-3">
                                                    <div className="space-y-1">
                                                        <Label className="text-xs text-slate-500">{__('Allowed Sender')}</Label>
                                                        <Select
                                                            value={conf.allowed_sender || ''}
                                                            onValueChange={(val) => updateConfig('sim1_configs', idx, 'allowed_sender', val)}
                                                        >
                                                            <SelectTrigger className="h-8">
                                                                <SelectValue placeholder={__('Select Sender')} />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="VF-Cash">{__('Vodafone Cash (VF-Cash)')}</SelectItem>
                                                                <SelectItem value="EtisalatCash">{__('e& money (Etisalat)')}</SelectItem>
                                                                <SelectItem value="OrangeCash">{__('Orange Cash')}</SelectItem>
                                                                <SelectItem value="WEPay">{__('WE Pay')}</SelectItem>
                                                                <SelectItem value="NBE">{__('NBE (Al Ahly)')}</SelectItem>
                                                                <SelectItem value="CIB">{__('CIB')}</SelectItem>
                                                                <SelectItem value="BM">{__('Banque Misr')}</SelectItem>
                                                                <SelectItem value="QNB">{__('QNB')}</SelectItem>
                                                                <SelectItem value="BDC">{__('Banque du Caire')}</SelectItem>
                                                                <SelectItem value="ADIB">{__('ADIB')}</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* SIM 2 Config */}
                                    <div className="space-y-4 p-4 border rounded-lg bg-slate-50">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h3 className="font-semibold text-slate-800">{__('SIM 2 Configuration')}</h3>
                                                <p className="text-xs text-slate-500 mt-0.5">{device.sim2_number ? mask(device.sim2_number, 'phone') : __('Not set')}</p>
                                            </div>
                                            <Button type="button" variant="outline" size="sm" onClick={() => addConfig('sim2_configs')}>
                                                <Plus className="w-4 h-4 mr-2" />
                                                {__('Add Sender')}
                                            </Button>
                                        </div>
                                        {data.sim2_configs.length === 0 && (
                                            <p className="text-sm text-slate-500 text-center py-4">{__('No senders configured for SIM 2.')}</p>
                                        )}
                                        {data.sim2_configs.map((conf, idx) => (
                                            <div key={idx} className="bg-white p-3 border rounded-md shadow-sm space-y-3 relative">
                                                <Button 
                                                    type="button" 
                                                    variant="ghost" 
                                                    size="icon" 
                                                    className="absolute top-2 right-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50"
                                                    onClick={() => removeConfig('sim2_configs', idx)}
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                                <div className="pr-8 space-y-3">
                                                    <div className="space-y-1">
                                                        <Label className="text-xs text-slate-500">{__('Allowed Sender')}</Label>
                                                        <Select
                                                            value={conf.allowed_sender || ''}
                                                            onValueChange={(val) => updateConfig('sim2_configs', idx, 'allowed_sender', val)}
                                                        >
                                                            <SelectTrigger className="h-8">
                                                                <SelectValue placeholder={__('Select Sender')} />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="VF-Cash">{__('Vodafone Cash (VF-Cash)')}</SelectItem>
                                                                <SelectItem value="EtisalatCash">{__('e& money (Etisalat)')}</SelectItem>
                                                                <SelectItem value="OrangeCash">{__('Orange Cash')}</SelectItem>
                                                                <SelectItem value="WEPay">{__('WE Pay')}</SelectItem>
                                                                <SelectItem value="NBE">{__('NBE (Al Ahly)')}</SelectItem>
                                                                <SelectItem value="CIB">{__('CIB')}</SelectItem>
                                                                <SelectItem value="BM">{__('Banque Misr')}</SelectItem>
                                                                <SelectItem value="QNB">{__('QNB')}</SelectItem>
                                                                <SelectItem value="BDC">{__('Banque du Caire')}</SelectItem>
                                                                <SelectItem value="ADIB">{__('ADIB')}</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? __('Saving...') : __('Save Configuration')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

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

