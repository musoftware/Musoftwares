import { __ } from '@/lib/i18n';
import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Info, CheckCircle2, XCircle, Send } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface Device {
    id: number;
    device_name: string;
    device_token: string;
    status: string;
}

interface Webhook {
    id: number;
    webhook_url: string;
    is_active: boolean;
}

interface Props {
    devices: Device[];
    webhook: Webhook | null;
    token: { token: string } | null;
}

export default function SmsSimulator({ devices, webhook, token }: Props) {
    const [sender, setSender] = useState('');
    const [message, setMessage] = useState('');
    const [selectedDeviceToken, setSelectedDeviceToken] = useState(devices.length > 0 ? devices[0].device_token : '');
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState<any>(null);

    const handleSimulate = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedDeviceToken) {
            toast.error(__('Please select a connected device to simulate the message for.'));
            return;
        }

        if (!sender.trim() || !message.trim()) {
            toast.error(__('Please enter both sender name and message content.'));
            return;
        }

        setLoading(true);
        setResult(null);

        try {
            // We use the exact same endpoint the Android app uses
            const response = await axios.post('/api/v1/sms-payment-gateway/sms', {
                sender: sender,
                name: sender,
                message: message,
                device_token: selectedDeviceToken,
                timestamp: Date.now(),
            });

            setResult({
                success: true,
                data: response.data,
            });
            toast.success(__('Message simulated successfully.'));
        } catch (error: any) {
            console.error(error);
            setResult({
                success: false,
                error: error.response?.data?.message || error.message || __('An error occurred while simulating the message.'),
                details: error.response?.data,
            });
            toast.error(__('Failed to simulate message.'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('SMS Simulator')} />

            <div className="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">{__('SMS Simulator')}</h1>
                    <p className="mt-2 text-sm text-gray-600">
                        {__('Manually simulate receiving an SMS to test if the parser and webhook are working correctly. This is exactly what the Android app does.')}
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-2">
                        <Card className="shadow-sm">
                            <CardHeader>
                                <CardTitle>{__('Simulate Incoming SMS')}</CardTitle>
                                <CardDescription>{__('Enter the details of the SMS you want to simulate.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSimulate} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="device">{__('Target Device')}</Label>
                                        <Select
                                            value={selectedDeviceToken}
                                            onValueChange={setSelectedDeviceToken}
                                        >
                                            <SelectTrigger id="device">
                                                <SelectValue placeholder={__('Select a connected device')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {devices.map(device => (
                                                    <SelectItem key={device.id} value={device.device_token}>
                                                        {device.device_name} ({device.id})
                                                    </SelectItem>
                                                ))}
                                                {devices.length === 0 && (
                                                    <SelectItem value="" disabled>
                                                        {__('No connected devices found')}
                                                    </SelectItem>
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            {__('The device token is required to authenticate the incoming SMS.')}
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="sender">{__('Sender Name / Phone Number')}</Label>
                                        <Input
                                            id="sender"
                                            type="text"
                                            value={sender}
                                            onChange={(e) => setSender(e.target.value)}
                                            placeholder="e& money, VF-Cash, CIB, etc."
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="message">{__('Message Content')}</Label>
                                        <Textarea
                                            id="message"
                                            value={message}
                                            onChange={(e) => setMessage(e.target.value)}
                                            placeholder={__('Paste the exact SMS content here...')}
                                            className="min-h-[150px]"
                                            required
                                        />
                                    </div>

                                    <Button type="submit" disabled={loading || devices.length === 0} className="w-full">
                                        {loading ? __('Simulating...') : (
                                            <>
                                                <Send className="w-4 h-4 mr-2" />
                                                {__('Simulate Incoming SMS')}
                                            </>
                                        )}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="shadow-sm">
                            <CardHeader>
                                <CardTitle>{__('System Status')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between p-3 border rounded-lg bg-gray-50/50">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-2 h-2 rounded-full ${devices.length > 0 ? 'bg-green-500' : 'bg-red-500'}`}></div>
                                        <span className="font-medium text-sm">{__('Connected Devices')}</span>
                                    </div>
                                    <span className="text-sm font-semibold">{devices.length}</span>
                                </div>
                                
                                <div className="flex flex-col gap-2 p-3 border rounded-lg bg-gray-50/50">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className={`w-2 h-2 rounded-full ${webhook ? 'bg-green-500' : 'bg-red-500'}`}></div>
                                            <span className="font-medium text-sm">{__('Active Webhook')}</span>
                                        </div>
                                        <span className="text-sm font-semibold">{webhook ? __('Configured') : __('Not Configured')}</span>
                                    </div>
                                    {webhook && (
                                        <p className="text-xs text-muted-foreground truncate" dir="ltr" title={webhook.webhook_url}>
                                            {webhook.webhook_url}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {result && (
                            <Card className="shadow-sm border-blue-100 bg-blue-50/30">
                                <CardHeader>
                                    <CardTitle className="text-blue-900 flex items-center gap-2">
                                        {result.success ? <CheckCircle2 className="w-5 h-5 text-green-600" /> : <XCircle className="w-5 h-5 text-red-600" />}
                                        {__('Simulation Result')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {result.success ? (
                                        <div className="space-y-4">
                                            <div className="p-3 bg-white border border-blue-100 rounded-md">
                                                <div className="text-sm font-medium text-gray-700 mb-1">{__('Status')}</div>
                                                <div className="text-green-600 font-semibold">{result.data?.message || __('Success')}</div>
                                            </div>
                                            
                                            {result.data?.transaction_detected && (
                                                <div className="p-3 bg-green-50 border border-green-200 rounded-md text-sm">
                                                    <div className="font-semibold text-green-800 mb-2">{__('Transaction Detected & Processed!')}</div>
                                                    <pre className="text-xs overflow-auto bg-white p-2 rounded border" dir="ltr">
                                                        {JSON.stringify(result.data?.transaction_data, null, 2)}
                                                    </pre>
                                                </div>
                                            )}
                                            
                                            {!result.data?.transaction_detected && (
                                                <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-md text-sm">
                                                    <div className="font-semibold text-yellow-800">{__('No Transaction Detected')}</div>
                                                    <p className="text-yellow-700 mt-1">{__('The system received the SMS but did not detect a valid financial transaction in it.')}</p>
                                                    {result.data?.debug && (
                                                         <pre className="text-xs overflow-auto bg-white p-2 rounded border mt-2 text-gray-700" dir="ltr">
                                                             {JSON.stringify(result.data?.debug, null, 2)}
                                                         </pre>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="p-3 bg-red-50 border border-red-200 rounded-md text-sm">
                                            <div className="font-semibold text-red-800 mb-2">{__('Error')}</div>
                                            <p className="text-red-700 mb-2">{result.error}</p>
                                            {result.details && (
                                                <pre className="text-xs overflow-auto bg-white p-2 rounded border" dir="ltr">
                                                    {JSON.stringify(result.details, null, 2)}
                                                </pre>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
