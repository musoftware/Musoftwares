import React, { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Smartphone, ArrowLeft, Trash2, Eye, Plus, QrCode, Download, ExternalLink } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';

interface DevicesProps {
    devices: any[];
    androidAppUrl: string | null;
}

export default function Devices({ devices, androidAppUrl }: DevicesProps) {
    const { toast } = useToast();
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    const [qrData, setQrData] = useState<{ qr_code: string; connection_code: string; expires_at: string } | null>(null);

    const handleDelete = (id: number) => {
        if (confirm(__('general.are_you_sure_you_want_4'))) {
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
                    title: __('general.error'),
                    description: __('general.failed_to_generate_connection_code'),
                    variant: 'destructive',
                });
                setIsAddModalOpen(false);
            }
        } catch (error: any) {
            toast({
                title: __('general.error'),
                description: (error as any).response?.data?.message || __('general.failed_to_generate_qr_code'),
                variant: 'destructive',
            });
            setIsAddModalOpen(false);
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.android_devices')}</h2>}>
            <Head title={__('sms_gateway.devices_payment_gateway')} />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Smartphone className="w-6 h-6 text-indigo-600" />
                                {__('general.android_devices')}
                            </h1>
                            <p className="text-slate-500 mt-1">{__('general.manage_all_android_phones_connected_to_your_payment_gateway_to_read_sms_receipts')}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                                <ArrowLeft className="w-4 h-4 me-2 rtl:me-0 rtl:ms-2 rtl:rotate-180" />
                                {__('general.back')}
                            </Button>
                            <Button onClick={handleAddDevice}>
                                <Plus className="w-4 h-4 me-2" />
                                {__('general.add_device')}
                            </Button>
                        </div>
                    </div>

                    {/* Download App Banner */}
                    {androidAppUrl && (
                        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-100">
                            <div className="flex items-center gap-3 flex-1 min-w-0">
                                <div className="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <Download className="w-5 h-5 text-indigo-600" />
                                </div>
                                <div className="min-w-0">
                                    <p className="font-semibold text-slate-800 text-sm">{__('general.need_the_android_app')}</p>
                                    <p className="text-xs text-slate-500 truncate">{__('general.download_and_install_the_companion_app_to_connect_your_phone')}</p>
                                </div>
                            </div>
                            <a
                                href={androidAppUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex-shrink-0"
                            >
                                <Download className="w-4 h-4" />
                                {__('general.download_apk')}
                                <ExternalLink className="w-3.5 h-3.5 opacity-60" />
                            </a>
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.connected_devices')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {devices.length === 0 ? (
                                <div className="text-center py-8">
                                    <Smartphone className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500 mb-4">{__('general.no_android_devices_connected')}</p>
                                    <Button onClick={handleAddDevice}>
                                        <Plus className="w-4 h-4 me-2" />
                                        {__('general.connect_new_device')}
                                    </Button>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {devices.map(device => (
                                        <div key={device.id} className="flex flex-col sm:flex-row justify-between p-5 border rounded-xl hover:shadow-md transition-shadow bg-white">
                                            <div className="flex items-start gap-4">
                                                <div className={`mt-1 w-3 h-3 rounded-full flex-shrink-0 ${device.status === 'connected' ? 'bg-emerald-500' : 'bg-slate-300'}`} />
                                                <div>
                                                    <h3 className="font-bold text-lg text-slate-800">{device.device_name || __('general.generic_android')}</h3>
                                                    <div className="mt-1 space-y-1">
                                                        <p className="text-sm font-medium text-slate-700">SIM 1: <span className="font-mono text-slate-500">{device.sim1_number || __('general.not_set')}</span></p>
                                                        <p className="text-sm font-medium text-slate-700">SIM 2: <span className="font-mono text-slate-500">{device.sim2_number || __('general.not_set')}</span></p>
                                                    </div>
                                                    <p className="text-xs text-slate-400 mt-2">{__('general.added')}: {new Date(device.created_at).toLocaleDateString()}</p>
                                                </div>
                                            </div>
                                            <div className="flex sm:flex-col gap-2 mt-4 sm:mt-0 items-end">
                                                <Button size="sm" variant="outline" className="w-full" onClick={() => router.visit(route('sms-payment-gateway.device', device.id))}>
                                                    <Eye className="w-4 h-4 me-2" /> {__('general.inspect')}
                                                </Button>
                                                <Button size="sm" variant="destructive" className="w-full" onClick={() => handleDelete(device.id)}>
                                                    <Trash2 className="w-4 h-4 me-2" /> {__('general.remove')}
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
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <QrCode className="w-5 h-5 text-indigo-600" /> {__('general.connect_new_device')}
                        </DialogTitle>
                        <DialogDescription>
                            {__('general.scan_this_qr_code_from_the_musoftware_payment_gateway_android_app_to_link_your_device')}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="flex flex-col items-center justify-center py-6">
                        {isGenerating ? (
                            <div className="flex flex-col items-center">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
                                <p className="text-slate-500">{__('general.generating_secure_connection_code')}</p>
                            </div>
                        ) : qrData ? (
                            <div className="flex flex-col items-center space-y-6 w-full">
                                <div className="p-4 bg-white border-2 border-slate-100 rounded-2xl shadow-sm">
                                    <img src={qrData.qr_code} alt={__('general.connection_qr_code')} className="w-64 h-64" />
                                </div>
                                
                                <div className="w-full text-center">
                                    <p className="text-sm text-slate-500 mb-2">{__('general.or_enter_this_code_manually')}</p>
                                    <div className="bg-slate-100 p-3 rounded-lg font-mono text-2xl font-bold tracking-widest text-slate-800 break-all">
                                        {qrData.connection_code}
                                    </div>
                                    <p className="text-xs text-amber-600 mt-3 font-medium">
                                        {__('general.code_expires_at')}: {new Date(qrData.expires_at).toLocaleString()}
                                    </p>
                                </div>

                                {/* Download link inside QR modal */}
                                {androidAppUrl && (
                                    <a
                                        href={androidAppUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
                                    >
                                        <Download className="w-4 h-4" />
                                        {__("general.dont_have_the_app_download")}
                                    </a>
                                )}
                                
                                <Button variant="outline" className="w-full mt-4" onClick={() => setIsAddModalOpen(false)}>
                                    {__('general.close')}
                                </Button>
                            </div>
                        ) : null}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

