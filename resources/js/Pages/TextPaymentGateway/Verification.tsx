import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { ArrowLeft, KeySquare, ShieldCheck, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/Components/ui/alert';

interface VerificationProps {
    token: { name: string, created_at: string } | null;
}

export default function Verification({ token }: VerificationProps) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">API Verification</h2>}>
            <Head title="API Authentication - Text Payment Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <KeySquare className="w-6 h-6 text-indigo-600" />
                                API Authentication
                            </h1>
                            <p className="text-slate-500 mt-1">Manage Personal Access Tokens used to authenticate API requests.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('text-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Active Access Token</CardTitle>
                            <CardDescription>Your API requests to Text Payment Gateway must be authenticated with a Bearer token.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {token ? (
                                <div className="p-6 border rounded-xl bg-emerald-50/50 flex items-start gap-4">
                                    <ShieldCheck className="w-8 h-8 text-emerald-500 flex-shrink-0" />
                                    <div>
                                        <h3 className="font-semibold text-emerald-900">Token Active: {token.name}</h3>
                                        <p className="text-emerald-700 mt-1 text-sm">
                                            A valid API token is attached to your account. Include it in the `Authorization: Bearer` header 
                                            when making requests to the Text Payment Gateway gateway API.
                                        </p>
                                        <p className="text-emerald-600/70 text-xs mt-3">Created at: {new Date(token.created_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            ) : (
                                <Alert className="bg-amber-50 text-amber-800 border-amber-200">
                                    <AlertCircle className="h-4 w-4 text-amber-600" />
                                    <AlertDescription>
                                        No active Personal Access Token found. You must generate an API Token from your profile settings before 
                                        you can programmatically interact with the Text Payment Gateway gateway.
                                    </AlertDescription>
                                </Alert>
                            )}

                            <div className="mt-8 pt-6 border-t">
                                <h4 className="font-semibold text-slate-800 mb-4">Authentication Usage</h4>
                                <pre className="bg-slate-900 text-slate-300 p-4 rounded-xl text-sm overflow-x-auto">
{`// Example API Request
curl -X POST https://musoftwares.com/api/auto-sms/connect \\
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \\
  -H "Accept: application/json"`}
                                </pre>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

