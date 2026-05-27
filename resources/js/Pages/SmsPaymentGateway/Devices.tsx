import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Smartphone, ArrowLeft, Trash2, Eye } from 'lucide-react';

interface DevicesProps {
    devices: any[];
}

export default function Devices({ devices }: DevicesProps) {
    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to disconnect and remove this device?')) {
            router.delete(route('sms-payment-gateway.delete-device', id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Android Devices</h2>}>
            <Head title="Devices - Payment Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Smartphone className="w-6 h-6 text-indigo-600" />
                                Android Devices
                            </h1>
                            <p className="text-slate-500 mt-1">Manage all Android phones connected to your Payment Gateway to read SMS receipts.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Connected Devices</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {devices.length === 0 ? (
                                <div className="text-center py-8">
                                    <Smartphone className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500">No Android devices connected.</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {devices.map(device => (
                                        <div key={device.id} className="flex flex-col sm:flex-row justify-between p-5 border rounded-xl hover:shadow-md transition-shadow bg-white">
                                            <div className="flex items-start gap-4">
                                                <div className={`mt-1 w-3 h-3 rounded-full flex-shrink-0 ${device.status === 'connected' ? 'bg-emerald-500' : 'bg-slate-300'}`} />
                                                <div>
                                                    <h3 className="font-bold text-lg text-slate-800">{device.device_name || 'Generic Android'}</h3>
                                                    <p className="text-sm font-mono text-slate-500 mt-1">{device.phone_number || 'No SIM/Number'}</p>
                                                    <p className="text-xs text-slate-400 mt-2">Added: {new Date(device.created_at).toLocaleDateString()}</p>
                                                </div>
                                            </div>
                                            <div className="flex sm:flex-col gap-2 mt-4 sm:mt-0 items-end">
                                                <Button size="sm" variant="outline" className="w-full" onClick={() => router.visit(route('sms-payment-gateway.device', device.id))}>
                                                    <Eye className="w-4 h-4 mr-2" /> Inspect
                                                </Button>
                                                <Button size="sm" variant="destructive" className="w-full" onClick={() => handleDelete(device.id)}>
                                                    <Trash2 className="w-4 h-4 mr-2" /> Remove
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

