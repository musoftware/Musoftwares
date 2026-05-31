import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Smartphone, ArrowLeft, Trash2, Eye, Plus, QrCode } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';

interface DevicesProps {
    devices: any[];
}

export default function Devices({ devices }: DevicesProps) {
    const { toast } = useToast();
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    const [qrData, setQrData] = useState<{ qr_code: string; connection_code: string; expires_at: string } | null>(null);

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to disconnect and remove this device?')) {
            router.delete(route('sms-payment-gateway.delete-device', id));
        }
    };

    const handleAddDevice = async () => {
        setIsGenerating(true);
        setQrData(null);
        setIsAddModalOpen(true);

        try {
            const response = await axios.post(route('sms-payment-gateway.generate-qr'), {}, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.data && response.data.success) {
                setQrData(response.data);
            } else {
                toast({
                    title: 'Error',
                    description: 'Failed to generate connection code.',
                    variant: 'destructive',
                });
                setIsAddModalOpen(false);
            }
        } catch (error: any) {
            toast({
                title: 'Error',
                description: error.response?.data?.message || 'Failed to generate QR code.',
                variant: 'destructive',
            });
            setIsAddModalOpen(false);
        } finally {
            setIsGenerating(false);
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
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Back
                            </Button>
                            <Button onClick={handleAddDevice}>
                                <Plus className="w-4 h-4 mr-2" />
                                Add Device
                            </Button>
                        </div>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Connected Devices</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {devices.length === 0 ? (
                                <div className="text-center py-8">
                                    <Smartphone className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500 mb-4">No Android devices connected.</p>
                                    <Button onClick={handleAddDevice}>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Connect New Device
                                    </Button>
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

            {/* Add Device Modal */}
            <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <QrCode className="w-5 h-5 text-indigo-600" /> Connect New Device
                        </DialogTitle>
                        <DialogDescription>
                            Scan this QR code from the Musoftware Payment Gateway Android App to link your device.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="flex flex-col items-center justify-center py-6">
                        {isGenerating ? (
                            <div className="flex flex-col items-center">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
                                <p className="text-slate-500">Generating secure connection code...</p>
                            </div>
                        ) : qrData ? (
                            <div className="flex flex-col items-center space-y-6 w-full">
                                <div className="p-4 bg-white border-2 border-slate-100 rounded-2xl shadow-sm">
                                    <img src={qrData.qr_code} alt="Connection QR Code" className="w-64 h-64" />
                                </div>
                                
                                <div className="w-full text-center">
                                    <p className="text-sm text-slate-500 mb-2">Or enter this code manually:</p>
                                    <div className="bg-slate-100 p-3 rounded-lg font-mono text-2xl font-bold tracking-widest text-slate-800">
                                        {qrData.connection_code}
                                    </div>
                                    <p className="text-xs text-amber-600 mt-3 font-medium">
                                        Code expires at: {new Date(qrData.expires_at).toLocaleString()}
                                    </p>
                                </div>
                                
                                <Button variant="outline" className="w-full mt-4" onClick={() => setIsAddModalOpen(false)}>
                                    Close
                                </Button>
                            </div>
                        ) : null}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

