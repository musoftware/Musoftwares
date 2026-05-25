import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { ArrowLeft, BookOpen } from 'lucide-react';

export default function Documentation() {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">API Documentation</h2>}>
            <Head title="Documentation - Text Payment Gateway" />

            <div className="py-8 md:py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <BookOpen className="w-6 h-6 text-indigo-600" />
                                Integration Documentation
                            </h1>
                            <p className="text-slate-500 mt-1">Learn how to connect and automate your systems with Text Payment Gateway.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('text-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Coming Soon</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-slate-600 mb-4">The full Text Payment Gateway developer API documentation is currently being ported to the new interface. In the meantime, please check the Integration Tester to understand the webhook payload structure.</p>
                            
                            <h3 className="text-lg font-bold text-slate-800 mt-6 mb-2">Embed Payment Widget (Iframe)</h3>
                            <p className="text-slate-600 mb-4">You can easily accept payments on your website by redirecting users to our hosted widget or embedding it as an iframe.</p>
                            
                            <div className="bg-slate-900 rounded-lg p-4 mb-6 overflow-x-auto text-sm text-green-400">
                                <pre><code>
{`<!-- Embed as Iframe -->
<iframe 
  src="${window.location.origin}/sms-payment-gateway/pay?key=YOUR_VERIFICATION_SECRET&amount=150.50&reference=order_12345&redirect_url=https://yourwebsite.com/success" 
  width="100%" 
  height="600" 
  frameborder="0">
</iframe>

<!-- Or simply Redirect the user to the link -->`}
                                </code></pre>
                            </div>
                            
                            <p className="text-slate-600 text-sm mb-4">
                                <strong>Parameters:</strong><br />
                                - <code>key</code>: Your account verification secret (Find it in Security settings)<br />
                                - <code>amount</code>: The amount to be paid<br />
                                - <code>reference</code>: (Optional) A unique reference ID the user must type in the transfer reason<br />
                                - <code>phone</code>: (Optional) Expect payment strictly from this sender phone number<br />
                                - <code>redirect_url</code>: (Optional) URL to redirect the user after a successful payment
                            </p>

                            <Button className="mt-6 bg-indigo-600 hover:bg-indigo-700" onClick={() => router.visit(route('text-payment-gateway.integration-tester'))}>
                                Go to Integration Tester
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

