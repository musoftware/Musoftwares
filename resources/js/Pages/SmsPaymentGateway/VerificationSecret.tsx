import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { ShieldAlert, ArrowLeft, RefreshCw, KeyRound, Copy, Check } from 'lucide-react';
import { Alert, AlertDescription } from '@/Components/ui/alert';

interface VerificationSecretProps {
    secret: string;
}

export default function VerificationSecret({ secret }: VerificationSecretProps) {
    const [copied, setCopied] = React.useState(false);

    const handleCopy = () => {
        navigator.clipboard.writeText(secret);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleRegenerate = () => {
        if (confirm('Are you sure you want to regenerate this secret? This will break existing webhook verifications until you update them.')) {
            router.post(route('text-payment-gateway.verification-secret.regenerate'));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Security Credentials</h2>}>
            <Head title="Security Secret - Text Payment Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <KeyRound className="w-6 h-6 text-indigo-600" />
                                HMAC Verification Secret
                            </h1>
                            <p className="text-slate-500 mt-1">Used to verify that webhook requests actually originated from Text Payment Gateway.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('text-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card className="border-indigo-100 shadow-lg shadow-indigo-100/50">
                        <CardHeader className="bg-indigo-50/50 border-b border-indigo-100 pb-8">
                            <CardTitle className="text-indigo-900">Your Signature Key</CardTitle>
                            <CardDescription className="text-indigo-700/80">
                                When we send an HTTP POST to your webhook endpoint, we include an <code>X-Text Payment Gateway-Signature</code> header. 
                                Compute an HMAC SHA256 signature of the raw JSON payload using this secret to verify authenticity.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-8">
                            <div className="flex flex-col md:flex-row gap-4 items-center mb-8">
                                <div className="flex-1 w-full relative">
                                    <input 
                                        type="text" 
                                        readOnly 
                                        value={secret} 
                                        className="w-full font-mono text-center text-lg md:text-xl py-4 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none text-slate-800 tracking-wider"
                                    />
                                </div>
                                <div className="flex gap-2 w-full md:w-auto">
                                    <Button onClick={handleCopy} size="lg" className="flex-1 md:flex-none h-14 bg-slate-900 hover:bg-slate-800">
                                        {copied ? <Check className="w-5 h-5 mr-2 text-emerald-400" /> : <Copy className="w-5 h-5 mr-2" />}
                                        {copied ? 'Copied' : 'Copy'}
                                    </Button>
                                    <Button onClick={handleRegenerate} size="lg" variant="outline" className="flex-1 md:flex-none h-14 border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                                        <RefreshCw className="w-5 h-5" />
                                    </Button>
                                </div>
                            </div>

                            <Alert className="bg-amber-50 border-amber-200 text-amber-800">
                                <ShieldAlert className="h-4 w-4 text-amber-600" />
                                <AlertDescription>
                                    <strong className="block mb-1">Never expose this secret publicly.</strong>
                                    Do not commit this secret to client-side code like JavaScript or mobile apps. It must only reside on your backend server.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

