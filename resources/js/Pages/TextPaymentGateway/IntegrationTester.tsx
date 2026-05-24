import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    ArrowLeft, TestTube, Code, CheckCircle, XCircle, Play, Server, FileJson
} from 'lucide-react';
import { Alert, AlertDescription } from '@/Components/ui/alert';

interface IntegrationTesterProps {
    webhook: any;
    token: { plainTextToken: string } | null;
    verificationSecret: string;
}

export default function IntegrationTester({ webhook, token, verificationSecret }: IntegrationTesterProps) {
    const [endpoint, setEndpoint] = useState(webhook?.webhook_url || '');
    const [phone, setPhone] = useState('');
    const [authToken, setAuthToken] = useState(token?.plainTextToken || '');
    const [testing, setTesting] = useState(false);
    const [result, setResult] = useState<any>(null);

    const handleRunTest = async (e: React.FormEvent) => {
        e.preventDefault();
        setTesting(true);
        setResult(null);

        try {
            const response = await fetch(route('text-payment-gateway.integration-tester.run'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    endpoint_url: endpoint,
                    phone_number: phone,
                    auth_token: authToken,
                })
            });

            const data = await response.json();
            setResult(data);
        } catch (error) {
            setResult({ success: false, message: 'Network error or invalid response from server.' });
        } finally {
            setTesting(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Integration Tester</h2>}
        >
            <Head title="Integration Tester - Text Payment Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <TestTube className="w-6 h-6 text-indigo-600" />
                                Integration Sandbox
                            </h1>
                            <p className="text-slate-500 mt-1">Simulate API calls to verify your application's handling of Text Payment Gateway payloads.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('text-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card>
                            <form onSubmit={handleRunTest}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Server className="w-5 h-5 text-indigo-500" />
                                        Target Configuration
                                    </CardTitle>
                                    <CardDescription>Setup the destination and credentials for the simulated request.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="endpoint">Endpoint URL</Label>
                                        <Input
                                            id="endpoint"
                                            type="url"
                                            value={endpoint}
                                            onChange={e => setEndpoint(e.target.value)}
                                            required
                                            placeholder="https://your-server.com/api/payment/callback"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Target Phone Number</Label>
                                        <Input
                                            id="phone"
                                            type="text"
                                            value={phone}
                                            onChange={e => setPhone(e.target.value)}
                                            required
                                            placeholder="e.g. 01012345678"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="auth">Bearer Token (Optional)</Label>
                                        <Input
                                            id="auth"
                                            type="text"
                                            value={authToken}
                                            onChange={e => setAuthToken(e.target.value)}
                                            placeholder="ey..."
                                        />
                                    </div>
                                    
                                    <Alert className="bg-slate-50 border-slate-200 mt-6">
                                        <Code className="h-4 w-4 text-slate-600" />
                                        <AlertDescription className="text-slate-600 font-mono text-xs mt-2 space-y-2">
                                            <p className="font-semibold text-slate-900 mb-1">Generated Request Headers:</p>
                                            <p>Content-Type: application/json</p>
                                            <p>User-Agent: Text Payment Gateway-Integration-Tester/1.0</p>
                                            {authToken && <p>Authorization: Bearer {authToken.substring(0, 10)}...</p>}
                                        </AlertDescription>
                                    </Alert>
                                </CardContent>
                                <div className="p-4 border-t bg-slate-50 flex justify-end">
                                    <Button type="submit" disabled={testing || !endpoint || !phone} className="bg-indigo-600 hover:bg-indigo-700">
                                        {testing ? <span className="animate-pulse">Running Simulation...</span> : <span className="flex items-center"><Play className="w-4 h-4 mr-2" /> Dispatch Mock Payload</span>}
                                    </Button>
                                </div>
                            </form>
                        </Card>

                        <Card className="flex flex-col h-full bg-slate-900 border-slate-800 text-slate-300">
                            <CardHeader className="border-b border-slate-800">
                                <CardTitle className="flex items-center gap-2 text-slate-100">
                                    <FileJson className="w-5 h-5 text-indigo-400" />
                                    Execution Output
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0 flex-1 overflow-auto bg-slate-950 font-mono text-xs">
                                {!result ? (
                                    <div className="flex items-center justify-center h-full min-h-[300px] text-slate-600">
                                        Awaiting execution...
                                    </div>
                                ) : (
                                    <div className="p-4 space-y-6">
                                        <div>
                                            <h3 className="text-slate-400 mb-2 uppercase tracking-wider font-semibold border-b border-slate-800 pb-1">Status</h3>
                                            {result.success && result.status >= 200 && result.status < 300 ? (
                                                <div className="flex items-center text-emerald-400 gap-2 text-sm">
                                                    <CheckCircle className="w-4 h-4" /> HTTP {result.status} OK
                                                </div>
                                            ) : (
                                                <div className="flex items-center text-rose-400 gap-2 text-sm">
                                                    <XCircle className="w-4 h-4" /> 
                                                    {result.success ? `HTTP ${result.status} Failed` : `Error: ${result.message}`}
                                                </div>
                                            )}
                                        </div>

                                        {result.payload_sent && (
                                            <div>
                                                <h3 className="text-slate-400 mb-2 uppercase tracking-wider font-semibold border-b border-slate-800 pb-1">Payload Sent</h3>
                                                <pre className="text-indigo-300 overflow-x-auto whitespace-pre-wrap break-all">
                                                    {JSON.stringify(result.payload_sent, null, 2)}
                                                </pre>
                                            </div>
                                        )}

                                        {result.response_body !== undefined && (
                                            <div>
                                                <h3 className="text-slate-400 mb-2 uppercase tracking-wider font-semibold border-b border-slate-800 pb-1">Server Response</h3>
                                                <pre className="text-emerald-300 overflow-x-auto whitespace-pre-wrap break-all">
                                                    {typeof result.response_body === 'object' 
                                                        ? JSON.stringify(result.response_body, null, 2) 
                                                        : result.response_body || '(Empty Response)'}
                                                </pre>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

