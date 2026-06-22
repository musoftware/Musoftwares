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
            toast.error(__('general.please_select_a_connected_device_to_simulate_the_message_for'));
            return;
        }

        if (!sender.trim() || !message.trim()) {
            toast.error(__('general.please_enter_both_sender_name_and_message_content'));
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
            toast.success(__('general.message_simulated_successfully'));
        } catch (error: any) {
            console.error(error);
            setResult({
                success: false,
                error: (error as any).response?.data?.message || error.message || __('general.an_error_occurred_while_simulating_the_message'),
                details: (error as any).response?.data,
            });
            toast.error(__('general.failed_to_simulate_message'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('sms_gateway.sms_simulator')} />

            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">{__('sms_gateway.sms_simulator')}</h1>
                    <p className="mt-2 text-sm text-gray-600">
                        {__('general.manually_simulate_receiving_an_sms_to_test_if_the_parser_and_webhook_are_working_correctly_this_is_exactly_what_the_android_app_does')}
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-2">
                        <Card className="shadow-sm">
                            <CardHeader>
                                <CardTitle>{__('sms_gateway.simulate_incoming_sms')}</CardTitle>
                                <CardDescription>{__('general.enter_the_details_of_the_sms_you_want_to_simulate')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSimulate} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="device">{__('general.target_device')}</Label>
                                        <Select
                                            value={selectedDeviceToken}
                                            onValueChange={(val) => setSelectedDeviceToken(val || '')}
                                        >
                                            <SelectTrigger id="device">
                                                <SelectValue placeholder={__('general.select_a_connected_device')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {devices.map(device => (
                                                    <SelectItem key={device.id} value={device.device_token}>
                                                        {device.device_name} ({device.id})
                                                    </SelectItem>
                                                ))}
                                                {devices.length === 0 && (
                                                    <SelectItem value="" disabled>
                                                        {__('general.no_connected_devices_found')}
                                                    </SelectItem>
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            {__('general.the_device_token_is_required_to_authenticate_the_incoming_sms')}
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="sender">{__('general.sender_name_phone_number')}</Label>
                                        <Input
                                            id="sender"
                                            type="text"
                                            value={sender}
                                            onChange={(e) => setSender(e.target.value)}
                                            placeholder={__('general.e_money_vf_cash_cib_etc')}
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="message">{__('general.message_content')}</Label>
                                        <Textarea
                                            id="message"
                                            value={message}
                                            onChange={(e) => setMessage(e.target.value)}
                                            placeholder={__('general.paste_the_exact_sms_content_here')}
                                            className="min-h-[150px]"
                                            required
                                        />
                                    </div>

                                    <Button type="submit" disabled={loading || devices.length === 0} className="w-full">
                                        {loading ? __('general.simulating') : (
                                            <>
                                                <Send className="w-4 h-4 me-2" />
                                                {__('sms_gateway.simulate_incoming_sms')}
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
                                <CardTitle>{__('general.system_status')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between p-3 border rounded-lg bg-gray-50/50">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-2 h-2 rounded-full ${devices.length > 0 ? 'bg-green-500' : 'bg-red-500'}`}></div>
                                        <span className="font-medium text-sm">{__('general.connected_devices')}</span>
                                    </div>
                                    <span className="text-sm font-semibold">{devices.length}</span>
                                </div>
                                
                                <div className="flex flex-col gap-2 p-3 border rounded-lg bg-gray-50/50">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className={`w-2 h-2 rounded-full ${webhook ? 'bg-green-500' : 'bg-red-500'}`}></div>
                                            <span className="font-medium text-sm">{__('general.active_webhook')}</span>
                                        </div>
                                        <span className="text-sm font-semibold">{webhook ? __('general.configured') : __('general.not_configured')}</span>
                                    </div>
                                    {webhook && (
                                        <p className="text-xs text-muted-foreground truncate"  title={webhook.webhook_url}>
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
                                        {__('general.simulation_result')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {result.success ? (
                                        <div className="space-y-4">
                                            <div className="p-3 bg-white border border-blue-100 rounded-md">
                                                <div className="text-sm font-medium text-gray-700 mb-1">{__('general.status')}</div>
                                                <div className="text-green-600 font-semibold">{result.data?.message || __('general.success')}</div>
                                            </div>
                                            
                                            {result.data?.transaction_detected && (
                                                <div className="p-3 bg-green-50 border border-green-200 rounded-md text-sm">
                                                    <div className="font-semibold text-green-800 mb-2">{__('erp.transaction_detected_processed')}</div>
                                                    <pre className="text-xs overflow-auto bg-white p-2 rounded border" >
                                                        {JSON.stringify(result.data?.transaction_data, null, 2)}
                                                    </pre>
                                                </div>
                                            )}
                                            
                                            {!result.data?.transaction_detected && (
                                                <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-md text-sm">
                                                    <div className="font-semibold text-yellow-800">{__('erp.no_transaction_detected')}</div>
                                                    <p className="text-yellow-700 mt-1">{__('general.the_system_received_the_sms_but_did_not_detect_a_valid_financial_transaction_in_it')}</p>
                                                    {result.data?.debug && (
                                                         <pre className="text-xs overflow-auto bg-white p-2 rounded border mt-2 text-gray-700" >
                                                             {JSON.stringify(result.data?.debug, null, 2)}
                                                         </pre>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="p-3 bg-red-50 border border-red-200 rounded-md text-sm">
                                            <div className="font-semibold text-red-800 mb-2">{__('general.error')}</div>
                                            <p className="text-red-700 mb-2">{result.error}</p>
                                            {result.details && (
                                                <pre className="text-xs overflow-auto bg-white p-2 rounded border" >
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
