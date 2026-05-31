import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { ArrowLeft, BookOpen, Code, Terminal, Webhook } from 'lucide-react';

export default function Documentation() {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('sms_gateway.integration_docs')}</h2>}>
            <Head title={__('sms_gateway.integration_docs')} />

            <div className="py-8 md:py-12">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <BookOpen className="w-6 h-6 text-indigo-600" />
                                {__('sms_gateway.integration_docs')}
                            </h1>
                            <p className="text-slate-500 mt-1">{__('sms_gateway.docs_subtitle')}</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            {__('sms_gateway.back_to_dashboard')}
                        </Button>
                    </div>

                    <Tabs defaultValue="quickstart" className="w-full">
                        <TabsList className="mb-4">
                            <TabsTrigger value="quickstart"><Terminal className="w-4 h-4 mr-2"/> {__('sms_gateway.quick_start')}</TabsTrigger>
                            <TabsTrigger value="widget"><Code className="w-4 h-4 mr-2"/> Javascript Widget</TabsTrigger>
                            <TabsTrigger value="webhooks"><Webhook className="w-4 h-4 mr-2"/> {__('sms_gateway.webhooks')}</TabsTrigger>
                        </TabsList>

                        {/* QUICK START */}
                        <TabsContent value="quickstart">
                            <Card>
                                <CardHeader>
                                    <CardTitle>SDK Integration</CardTitle>
                                    <CardDescription>Integrate the SMS Payment Gateway directly into your backend.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-bold text-slate-800">1. Install the SDK</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="bg-slate-900 rounded-lg p-4">
                                                <div className="text-xs text-slate-400 font-bold mb-2">PHP (Composer)</div>
                                                <pre className="text-emerald-400 text-sm overflow-x-auto">
                                                    <code>composer require musoftware/smspay-php</code>
                                                </pre>
                                            </div>
                                            <div className="bg-slate-900 rounded-lg p-4">
                                                <div className="text-xs text-slate-400 font-bold mb-2">Node.js (NPM)</div>
                                                <pre className="text-emerald-400 text-sm overflow-x-auto">
                                                    <code>npm install @musoftware/smspay</code>
                                                </pre>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <h3 className="text-lg font-bold text-slate-800">2. Create a Checkout Session</h3>
                                        <p className="text-slate-600 text-sm">When your customer wants to pay, create a Checkout Session on your server using your <strong>Secret Key</strong>.</p>
                                        
                                        <Tabs defaultValue="php" className="w-full mt-2">
                                            <TabsList className="h-8">
                                                <TabsTrigger value="php" className="text-xs">PHP</TabsTrigger>
                                                <TabsTrigger value="node" className="text-xs">Node.js</TabsTrigger>
                                            </TabsList>
                                            <TabsContent value="php">
                                                <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                                    <pre className="text-slate-300 text-sm">
<code>{`$smspay = new \\SmsPay\\SmsPay('sk_live_YOUR_SECRET_KEY');

$session = $smspay->checkoutSessions->create([
    'amount' => 150.00,
    'currency' => 'EGP',
    'success_url' => 'https://yourwebsite.com/success?session_id={SESSION_ID}',
    'cancel_url' => 'https://yourwebsite.com/cancel',
    'metadata' => [
        'order_id' => 'ORD-1234'
    ],
]);

// Send the session.id to your frontend
echo json_encode(['id' => $session->id]);`}</code>
                                                    </pre>
                                                </div>
                                            </TabsContent>
                                            <TabsContent value="node">
                                                <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                                    <pre className="text-slate-300 text-sm">
<code>{`const SmsPay = require('@musoftware/smspay');
const smspay = new SmsPay('sk_live_YOUR_SECRET_KEY');

const session = await smspay.checkoutSessions.create({
    amount: 150.00,
    currency: 'EGP',
    successUrl: 'https://yourwebsite.com/success?session_id={SESSION_ID}',
    cancelUrl: 'https://yourwebsite.com/cancel',
    metadata: {
        orderId: 'ORD-1234'
    },
});

// Send the session.id to your frontend
res.json({ id: session.id });`}</code>
                                                    </pre>
                                                </div>
                                            </TabsContent>
                                        </Tabs>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* WIDGET */}
                        <TabsContent value="widget">
                            <Card>
                                <CardHeader>
                                    <CardTitle>SmsPay.js Widget</CardTitle>
                                    <CardDescription>Embed the payment flow in your frontend without redirecting the user.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-bold text-slate-800">1. Include the Script</h3>
                                        <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                            <pre className="text-emerald-400 text-sm">
                                                <code>{`<script src="${window.location.origin}/js/smspay.js"></script>`}</code>
                                            </pre>
                                        </div>

                                        <h3 className="text-lg font-bold text-slate-800 mt-6">2. Trigger Checkout</h3>
                                        <p className="text-slate-600 text-sm">Use the session ID you created on your backend and your <strong>Publishable Key</strong>.</p>
                                        
                                        <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                            <pre className="text-slate-300 text-sm">
<code>{`<button id="pay-button">Pay Now</button>

<script>
  // Initialize with your PUBLISHABLE KEY
  const smspay = SmsPay('pk_live_YOUR_PUBLISHABLE_KEY');

  document.getElementById('pay-button').addEventListener('click', () => {
    // Open the payment modal
    smspay.checkout({
      sessionId: 'cs_xyz123', // ID generated on your backend
      onSuccess: function(result) {
        console.log('Payment successful!', result);
        window.location.href = '/success';
      },
      onCancel: function() {
        console.log('User closed the modal');
      },
      onError: function(error) {
        console.error('Payment error', error);
      }
    });
  });
</script>`}</code>
                                            </pre>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* WEBHOOKS */}
                        <TabsContent value="webhooks">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Verifying Webhooks</CardTitle>
                                    <CardDescription>Securely receive payment confirmations.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-slate-600 mb-4">
                                        To securely confirm that a webhook was actually sent by our gateway, you must verify its cryptographic signature using your Webhook Secret.
                                        <br/><br/>
                                        <strong>Never</strong> fulfill an order without verifying the webhook signature.
                                    </p>
                                    
                                    <Tabs defaultValue="php" className="w-full">
                                        <TabsList className="h-8">
                                            <TabsTrigger value="php" className="text-xs">PHP</TabsTrigger>
                                            <TabsTrigger value="node" className="text-xs">Node.js</TabsTrigger>
                                        </TabsList>
                                        <TabsContent value="php">
                                            <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                                <pre className="text-slate-300 text-sm">
<code>{`// 1. Get raw payload and headers
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SMSPAY_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_SMSPAY_TIMESTAMP'];
$secret = 'whsec_YOUR_WEBHOOK_SECRET';

try {
    // 2. Verify signature
    $event = \\SmsPay\\Webhook::constructEvent($payload, $signature, $timestamp, $secret);

    // 3. Handle event
    if ($event->type === 'checkout.session.completed') {
        $session = $event->data;
        $orderId = $session->metadata->order_id;
        // Fulfill the order...
    }
    
    http_response_code(200);
} catch(\\UnexpectedValueException $e) {
    http_response_code(400); // Invalid payload
} catch(\\SmsPay\\Exception\\SignatureVerificationException $e) {
    http_response_code(400); // Invalid signature
}`}</code>
                                                </pre>
                                            </div>
                                        </TabsContent>
                                        <TabsContent value="node">
                                            <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                                <pre className="text-slate-300 text-sm">
<code>{`const express = require('express');
const SmsPay = require('@musoftware/smspay');
const smspay = new SmsPay('sk_live_YOUR_SECRET_KEY');

const app = express();
const endpointSecret = 'whsec_YOUR_WEBHOOK_SECRET';

// IMPORTANT: Use raw body parsing for webhooks
app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
  const signature = req.headers['x-smspay-signature'];
  const timestamp = req.headers['x-smspay-timestamp'];
  let event;

  try {
    event = smspay.webhooks.constructEvent(req.body, signature, timestamp, endpointSecret);
  } catch (err) {
    return res.status(400).send(\`Webhook Error: \${err.message}\`);
  }

  if (event.type === 'checkout.session.completed') {
    const session = event.data;
    // Fulfill the order...
  }

  res.send();
});`}</code>
                                                </pre>
                                            </div>
                                        </TabsContent>
                                    </Tabs>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

