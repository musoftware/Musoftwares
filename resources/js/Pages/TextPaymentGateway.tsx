import { __ } from '@/lib/i18n';
import React from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Smartphone, Webhook, Key, Activity, CreditCard, ArrowRight, ShieldCheck, Download, ExternalLink, HelpCircle, CheckCircle, ListOrdered } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/ui/accordion";
import { formatMoney } from '@/lib/utils';

interface DashboardProps {
    devices: any[];
    webhook: any | null;
    token: any | null;
    stats: {
        total_devices: number;
        connected_devices: number;
        total_transactions: number;
        webhook_configured: boolean;
    };
    recentTransactions: any[];
    androidAppUrl: string | null;
}

export default function TextPaymentGateway({ devices, webhook, token, stats, recentTransactions, androidAppUrl }: DashboardProps) {
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'verified':
            case 'paid':
            case 'matched':
                return <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100 border-none">{__('general.verified')}</Badge>;
            case 'pending':
                return <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">{__('general.pending')}</Badge>;
            case 'ignored':
            case 'spoofed':
                return <Badge variant="outline" className="text-slate-500 border-slate-200 bg-slate-50">{__('general.ignored')}</Badge>;
            default:
                return <Badge variant="outline">{status ? status : __('general.pending')}</Badge>;
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('sms_gateway.payment_gateway')}</h2>}>
            <Head title={__('sms_gateway.payment_gateway')} />

            <div className="py-8 md:py-12 bg-slate-50/50 min-h-[calc(100vh-65px)]">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    {/* Header Section */}
                    <div className="flex flex-col md:flex-row md:items-start justify-between gap-6">
                        <div>
                            <h1 className="text-3xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                <ShieldCheck className="w-8 h-8 text-rose-600" />
                                {__('sms_gateway.your_own_payment_gateway')}
                            </h1>
                            <p className="text-slate-500 mt-2 text-lg max-w-2xl">{__('erp.a_stripe_alternative_powered_by')}</p>
                        </div>

                        {/* Simple Download Button */}
                        <Link
                            href={route('sms-payment-gateway.install')}
                            className="inline-flex items-center gap-3 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all shadow-sm hover:shadow-md flex-shrink-0 border border-slate-700 group"
                        >
                            <Smartphone className="w-6 h-6 text-emerald-400" />
                            <div className="flex flex-col items-start text-start">
                                <span className="text-[10px] font-medium text-slate-300 uppercase tracking-wider leading-none">{__('admin.install_app')}</span>
                                <span className="text-sm font-bold leading-tight">{__('admin.setup_guide')}</span>
                            </div>
                            <Download className="w-4 h-4 ms-2 opacity-50 group-hover:opacity-100 transition-opacity rtl:me-2 rtl:ms-0" />
                        </Link>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card className="border-none shadow-sm bg-white/50 backdrop-blur-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-emerald-100 rounded-xl">
                                        <Smartphone className="w-6 h-6 text-emerald-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">{__('general.connected_devices')}</p>
                                        <h3 className="text-2xl font-bold text-slate-900">{stats.connected_devices} / {stats.total_devices}</h3>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-sm bg-white/50 backdrop-blur-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-indigo-100 rounded-xl">
                                        <Activity className="w-6 h-6 text-indigo-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">{__('general.total_payments')}</p>
                                        <h3 className="text-2xl font-bold text-slate-900">{stats.total_transactions}</h3>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-sm bg-white/50 backdrop-blur-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-purple-100 rounded-xl">
                                        <Webhook className="w-6 h-6 text-purple-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">{__('general.webhook_status')}</p>
                                        <h3 className="text-lg font-bold text-slate-900 mt-1">
                                            {stats.webhook_configured ? (
                                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 border-none">{__('general.active')}</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-slate-500 border-slate-200">{__('general.not_configured')}</Badge>
                                            )}
                                        </h3>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-sm bg-white/50 backdrop-blur-sm">
                            <CardContent className="p-6">
                                <div className="flex items-center gap-4">
                                    <div className="p-3 bg-amber-100 rounded-xl">
                                        <CreditCard className="w-6 h-6 text-amber-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">{__('general.api_access')}</p>
                                        <h3 className="text-lg font-bold text-slate-900 mt-1">
                                            {token ? (
                                                <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-200 border-none">{__('general.ready')}</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-rose-500 border-rose-200">{__('general.missing_token')}</Badge>
                                            )}
                                        </h3>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Main Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Smartphone className="w-5 h-5 text-rose-500" />{__('general.devices')}</CardTitle>
                                <CardDescription>{__('freelance.connect_android_phone_to_read')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button className="w-full bg-slate-900 hover:bg-slate-800" onClick={() => router.visit(route('sms-payment-gateway.devices'))}>{__('general.manage_devices')}<ArrowRight className="w-4 h-4 ms-2 rtl:rotate-180" />
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Key className="w-5 h-5 text-emerald-500" />{__('general.api_keys')}</CardTitle>
                                <CardDescription>{__('general.manage_your_secret_and_publishable')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-emerald-200 hover:bg-emerald-50 text-emerald-700" onClick={() => router.visit(route('sms-payment-gateway.api-keys'))}>{__('general.api_keys')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <CreditCard className="w-5 h-5 text-indigo-500" />{__('payment.payment_links')}</CardTitle>
                                <CardDescription>{__('payment.create_quick_payment_links_for')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-indigo-200 hover:bg-indigo-50" onClick={() => router.visit(route('sms-payment-gateway.payment-links'))}>{__('payment.create_payment_link')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Activity className="w-5 h-5 text-sky-500" />{__('general.api_sessions')}</CardTitle>
                                <CardDescription>{__('general.view_active_and_completed_checkout_sessions_created_via_your_api_keys')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-sky-200 hover:bg-sky-50" onClick={() => router.visit(route('sms-payment-gateway.checkout-sessions'))}>{__('general.view_api_sessions')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Activity className="w-5 h-5 text-amber-500" />{__('admin.settings')}</CardTitle>
                                <CardDescription>{__('payment.set_the_receiving_phone_number')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full" onClick={() => router.visit(route('sms-payment-gateway.settings'))}>{__('sms_gateway.gateway_settings')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <ExternalLink className="w-5 h-5 text-blue-500" />{__('general.integration_docs')}</CardTitle>
                                <CardDescription>{__('freelance.learn_how_to_connect_your')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-blue-200 hover:bg-blue-50 text-blue-700" onClick={() => router.visit(route('sms-payment-gateway.documentation'))}>{__('general.integration_docs')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Webhook className="w-5 h-5 text-purple-500" />{__('general.webhooks')}</CardTitle>
                                <CardDescription>{__('general.configure_webhook_endpoints_to_receive_real_time_payment_notifications_on_your_server')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-purple-200 hover:bg-purple-50 text-purple-700" onClick={() => router.visit(route('sms-payment-gateway.webhooks'))}>{__('general.configure_webhooks')}</Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Recent Transactions Table */}
                    <Card className="border border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-lg">{__('general.recent_payments')}</CardTitle>
                                    <CardDescription>{__('payment.realtime_payment_receipts_captured_from')}</CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" onClick={() => router.visit(route('sms-payment-gateway.transactions'))} className="text-indigo-600 hover:text-indigo-700">
                                    {__('general.view_all_payments')} <ArrowRight className="w-4 h-4 ms-1" />
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recentTransactions && recentTransactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-start">
                                        <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                                            <tr>
                                                <th className="px-6 py-4 font-medium">{__('general.date')}</th>
                                                <th className="px-6 py-4 font-medium">{__('general.sender')}</th>
                                                <th className="px-6 py-4 font-medium">{__('general.amount')}</th>
                                                <th className="px-6 py-4 font-medium">{__('general.status')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {recentTransactions.map((txn, index) => (
                                                <tr key={index} className="bg-white hover:bg-slate-50/50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap text-slate-600 font-medium">
                                                        {new Date(txn.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Badge variant="outline" className="font-mono text-xs text-slate-600 bg-slate-50">{txn.sender_name || txn.sender}</Badge>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap font-bold text-slate-900">
                                                        {txn.amount ? formatMoney(txn.amount, txn.currency) : '---'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(txn.status)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <Activity className="w-10 h-10 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500">{__('erp.no_transactions_recorded_yet')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Quick Setup Guide */}
                    <div className="mt-12 space-y-6">
                        <div className="flex items-center gap-2 mb-6">
                            <ListOrdered className="w-6 h-6 text-indigo-600" />
                            <h2 className="text-2xl font-bold text-slate-900">{__('general.quick_setup_guide')}</h2>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Card className="border-none shadow-sm bg-white hover:shadow-md transition-shadow">
                                <CardContent className="p-6 flex flex-col items-start gap-4">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">1</div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{__('general.download_install')}</h4>
                                        <p className="text-sm text-slate-500 mt-1 leading-relaxed">{__('general.download_the_android_apk_and')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="border-none shadow-sm bg-white hover:shadow-md transition-shadow">
                                <CardContent className="p-6 flex flex-col items-start gap-4">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">2</div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{__('general.link_your_device')}</h4>
                                        <p className="text-sm text-slate-500 mt-1 leading-relaxed">{__('general.go_to_manage_devices_scan')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="border-none shadow-sm bg-white hover:shadow-md transition-shadow">
                                <CardContent className="p-6 flex flex-col items-start gap-4">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">3</div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{__('erp.add_wallet_numbers')}</h4>
                                        <p className="text-sm text-slate-500 mt-1 leading-relaxed">{__('sms_gateway.in_gateway_settings_add_the')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="border-none shadow-sm bg-white hover:shadow-md transition-shadow">
                                <CardContent className="p-6 flex flex-col items-start gap-4">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">4</div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{__('general.create_api_keys')}</h4>
                                        <p className="text-sm text-slate-500 mt-1 leading-relaxed">{__('general.generate_your_publishable_and_secret')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="border-none shadow-sm bg-white hover:shadow-md transition-shadow">
                                <CardContent className="p-6 flex flex-col items-start gap-4">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">5</div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{__('general.read_integration_docs')}</h4>
                                        <p className="text-sm text-slate-500 mt-1 leading-relaxed">{__('payment.have_your_developer_read_the')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="border-none shadow-sm bg-white hover:shadow-md transition-shadow">
                                <CardContent className="p-6 flex flex-col items-start gap-4">
                                    <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">6</div>
                                    <div>
                                        <h4 className="font-semibold text-slate-900">{__('general.configure_webhooks')}</h4>
                                        <p className="text-sm text-slate-500 mt-1 leading-relaxed">{__('payment.set_up_your_webhook_endpoint')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* FAQs Section */}
                    <div className="mt-16 mb-8 space-y-6">
                        <div className="flex items-center gap-2 mb-6">
                            <HelpCircle className="w-6 h-6 text-rose-600" />
                            <h2 className="text-2xl font-bold text-slate-900">{__('general.frequently_asked_questions')}</h2>
                        </div>
                        <Accordion className="w-full bg-white rounded-lg shadow-sm border border-slate-100">
                            <AccordionItem value="item-1" className="px-4">
                                <AccordionTrigger className="text-slate-800 hover:text-indigo-600 font-medium">
                                    {__('general.is_it_secure')}
                                </AccordionTrigger>
                                <AccordionContent className="text-slate-600 leading-relaxed">
                                    {__('sms_gateway.yes_the_android_app_only')}
                                </AccordionContent>
                            </AccordionItem>
                            
                            <AccordionItem value="item-2" className="px-4">
                                <AccordionTrigger className="text-slate-800 hover:text-indigo-600 font-medium">
                                    {__("What happens if a payment isn't matched automatically?")}
                                </AccordionTrigger>
                                <AccordionContent className="text-slate-600 leading-relaxed">
                                    {__("You can manually review and match 'Pending' or 'Ignored' SMS messages from your dashboard to any unfulfilled checkout session.")}
                                </AccordionContent>
                            </AccordionItem>
                            
                            <AccordionItem value="item-3" className="px-4">
                                <AccordionTrigger className="text-slate-800 hover:text-indigo-600 font-medium">
                                    {__('general.how_are_payments_verified')}
                                </AccordionTrigger>
                                <AccordionContent className="text-slate-600 leading-relaxed">
                                    {__('sms_gateway.the_app_reads_incoming_sms')}
                                </AccordionContent>
                            </AccordionItem>
                            
                            <AccordionItem value="item-4" className="px-4">
                                <AccordionTrigger className="text-slate-800 hover:text-indigo-600 font-medium">
                                    {__('general.can_i_test_without_real')}
                                </AccordionTrigger>
                                <AccordionContent className="text-slate-600 leading-relaxed">
                                    {__('erp.yes_you_can_use_the')}
                                </AccordionContent>
                            </AccordionItem>

                            <AccordionItem value="item-5" className="px-4">
                                <AccordionTrigger className="text-slate-800 hover:text-indigo-600 font-medium">
                                    {__('general.do_i_need_a_specific')}
                                </AccordionTrigger>
                                <AccordionContent className="text-slate-600 leading-relaxed">
                                    {__('sms_gateway.any_android_80_device_works')}
                                </AccordionContent>
                            </AccordionItem>

                            <AccordionItem value="item-6" className="px-4 border-b-0">
                                <AccordionTrigger className="text-slate-800 hover:text-indigo-600 font-medium">
                                    {__('sms_gateway.how_to_allow_sms_permission')}
                                </AccordionTrigger>
                                <AccordionContent className="text-slate-600 leading-relaxed whitespace-pre-wrap">
                                    {__('sms_gateway.open_settings_apps_app_manager')}
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}


