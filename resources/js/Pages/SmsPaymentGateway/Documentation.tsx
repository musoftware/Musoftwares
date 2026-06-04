import React, { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { ArrowLeft, BookOpen, Code, Terminal, Webhook, Download, AlertTriangle } from 'lucide-react';

export default function Documentation() {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.smsgatewayintegrationdocs')}</h2>}>
            <Head title={__('general.smsgatewayintegrationdocs')} />

            <div className="py-8 md:py-12">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <BookOpen className="w-6 h-6 text-indigo-600" />
                                {__('general.smsgatewayintegrationdocs')}
                            </h1>
                            <p className="text-slate-500 mt-1">{__('general.smsgatewaydocssubtitle')}</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            {__('general.smsgatewaybacktodashboard')}
                        </Button>
                    </div>

                    <Tabs defaultValue="simple" className="w-full">
                        <TabsList className="mb-4">
                            <TabsTrigger value="simple"><Code className="w-4 h-4 mr-2"/> Simple</TabsTrigger>
                            <TabsTrigger value="advanced"><Terminal className="w-4 h-4 mr-2"/> Advanced</TabsTrigger>
                        </TabsList>

                        {/* SIMPLE */}
                        <TabsContent value="simple">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{__('general.smspay_js_widget')}</CardTitle>
                                    <CardDescription>{__('general.embed_the_payment_flow_in_your_frontend_without_redirecting_the_user')}</CardDescription>
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
                                        <p className="text-slate-600 text-sm">{__('general.use_the_session_id_you_created_on_your_backend_and_your')}<strong>{__('general.publishable_key')}</strong>.</p>
                                        
                                        <div className="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                                            <pre className="text-slate-300 text-sm">
<code>{`<button id="pay-button">{__('general.pay_now')}</button>

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

                        {/* ADVANCED */}
                        <TabsContent value="advanced">
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>SDK Integration (Backend)</CardTitle>
                                        <CardDescription>{__('general.integrate_the_sms_payment_gateway_directly_into_your_backend_to_create_sessions')}</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-bold text-slate-800">1. Download the SDK</h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="bg-slate-900 rounded-lg p-4 flex flex-col justify-between items-start gap-4">
                                                    <div>
                                                        <div className="text-xs text-slate-400 font-bold mb-2">{__('general.php_sdk')}</div>
                                                        <p className="text-slate-300 text-sm">{__('general.download_the_php_sdk_to_integrate_with_your_php_backend')}</p>
                                                    </div>
                                                    <Button variant="secondary" size="sm" asChild>
                                                        <a href={`/sdks/smspay-php.zip?ts=${Date.now()}`} download>
                                                            <Download className="w-4 h-4 mr-2" />{__('general.download_php_sdk')}</a>
                                                    </Button>
                                                </div>
                                                <div className="bg-slate-900 rounded-lg p-4 flex flex-col justify-between items-start gap-4">
                                                    <div>
                                                        <div className="text-xs text-slate-400 font-bold mb-2">{__('general.node_js_sdk')}</div>
                                                        <p className="text-slate-300 text-sm">{__('general.download_the_node_js_sdk_to_integrate_with_your_node_application')}</p>
                                                    </div>
                                                    <Button variant="secondary" size="sm" asChild>
                                                        <a href={`/sdks/smspay-node.zip?ts=${Date.now()}`} download>
                                                            <Download className="w-4 h-4 mr-2" />{__('general.download_node_js_sdk')}</a>
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <h3 className="text-lg font-bold text-slate-800">2. Create a Checkout Session</h3>
                                            <p className="text-slate-600 text-sm">{__('general.when_your_customer_wants_to_pay_create_a_checkout_session_on_your_server_using_your')}<strong>{__('general.secret_key')}</strong>.</p>
                                            
                                            <Tabs defaultValue="php" className="w-full mt-2">
                                                <TabsList className="h-8">
                                                    <TabsTrigger value="php" className="text-xs">PHP</TabsTrigger>
                                                    <TabsTrigger value="node" className="text-xs">{__('general.node_js')}</TabsTrigger>
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

                                <Card>
                                    <CardHeader>
                                        <CardTitle>{__('general.verifying_webhooks')}</CardTitle>
                                        <CardDescription>{__('general.securely_receive_payment_confirmations')}</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-slate-600 mb-4">{__('general.to_securely_confirm_that_a_webhook_was_actually_sent_by_our_gateway_you_must_verify_its_cryptographic_signature_using_your_webhook_secret')}<br/><br/>
                                            <strong>Never</strong>{__('general.fulfill_an_order_without_verifying_the_webhook_signature')}</p>
                                        
                                        <Tabs defaultValue="php" className="w-full">
                                            <TabsList className="h-8">
                                                <TabsTrigger value="php" className="text-xs">PHP</TabsTrigger>
                                                <TabsTrigger value="node" className="text-xs">{__('general.node_js')}</TabsTrigger>
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

                                <Card>
                                    <CardHeader>
                                        <CardTitle>{__('sms_gateway.wordpress_integration')}</CardTitle>
                                        <CardDescription>{__('sms_gateway.wordpress_integration_desc')}</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-bold text-slate-800">1. Download the Plugin</h3>
                                            <div className="bg-slate-900 rounded-lg p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                                <div>
                                                    <div className="text-xs text-slate-400 font-bold mb-2">{__('sms_gateway.wordpress_plugin')}</div>
                                                    <p className="text-slate-300 text-sm">{__('sms_gateway.wordpress_plugin_desc')}</p>
                                                </div>
                                                <Button variant="secondary" size="sm" asChild>
                                                    <a href={`/downloads/musoftware-sms-gateway.zip?ts=${Date.now()}`} download>
                                                        <Download className="w-4 h-4 mr-2" />{__('sms_gateway.download_wp_plugin')}</a>
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-bold text-slate-800">2. {__('sms_gateway.setup_instructions')}</h3>
                                            <p className="text-slate-600 text-sm">{__('sms_gateway.setup_instructions_step1')}</p>
                                            <p className="text-slate-600 text-sm">{__('sms_gateway.setup_instructions_step2')}</p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>
                    </Tabs>

                    <Card className="border-amber-200 bg-amber-50/50 dark:bg-amber-950/20 dark:border-amber-900 mt-8">
                        <CardHeader>
                            <CardTitle className="text-amber-800 dark:text-amber-500 flex items-center gap-2">
                                <AlertTriangle className="w-5 h-5" /> 
                                Security Best Practices / ممارسات الأمان
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6 text-sm text-amber-900/80 dark:text-amber-200/80">
                            <div>
                                <h4 className="font-bold mb-1 text-amber-900 dark:text-amber-400">English</h4>
                                <p>
                                    <strong>{__('general.never_rely_on_the_frontend')}<code>onSuccess</code>{__('general.callback_to_fulfill_orders')}</strong><br/>{__('general.client_side_code_can_be_easily_manipulated_or_bypassed_by_users_hijacking_the')}<code>onSuccess</code>{__('general.event_is_strictly_for_ui_purposes_such_as_redirecting_the_user_to_a_thank_you_page_to_securely_verify_that_a_payment_was_successful_you_must_use')}<strong>Webhooks</strong>{__('general.or_server_side_api_verification_exactly_as_implemented_by_major_gateways_like_stripe')}</p>
                            </div>
                            <div dir="rtl" className="text-right font-sans">
                                <h4 className="font-bold mb-1 text-amber-900 dark:text-amber-400">عربي</h4>
                                <p>
                                    <strong>لا تعتمد أبداً على دالة <code>onSuccess</code> في واجهة المستخدم لتأكيد الطلبات.</strong><br/>
                                    يمكن للمستخدمين التلاعب بسهولة بالكود من جهة المتصفح وتخطي عملية الدفع. حدث <code>onSuccess</code> مصمم فقط لتحسين تجربة المستخدم (مثل توجيهه لصفحة شكر). لكي تتأكد بأمان تام من نجاح الدفع، يجب عليك استخدام <strong>Webhooks</strong> أو التحقق من جانب السيرفر (Server-Side Verification)، تماماً كما تفعل بوابات الدفع العالمية مثل Stripe.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

