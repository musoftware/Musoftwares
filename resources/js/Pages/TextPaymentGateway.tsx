import { __ } from '@/lib/i18n';
import React from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Smartphone, Webhook, Key, Activity, CreditCard, ArrowRight, ShieldCheck, Download, ExternalLink } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
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
                return <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100 border-none">{__('Verified')}</Badge>;
            case 'pending':
                return <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">{__('Pending')}</Badge>;
            case 'ignored':
            case 'spoofed':
                return <Badge variant="outline" className="text-slate-500 border-slate-200 bg-slate-50">{__('Ignored')}</Badge>;
            default:
                return <Badge variant="outline">{status ? status : __('Pending')}</Badge>;
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('Payment Gateway')}</h2>}>
            <Head title={__('Payment Gateway')} />

            <div className="py-8 md:py-12 bg-slate-50/50 min-h-[calc(100vh-65px)]">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    {/* Header Section */}
                    <div className="flex flex-col md:flex-row md:items-start justify-between gap-6">
                        <div>
                            <h1 className="text-3xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                <ShieldCheck className="w-8 h-8 text-rose-600" />
                                {__('Your Own Payment Gateway')}
                            </h1>
                            <p className="text-slate-500 mt-2 text-lg max-w-2xl">{__('A Stripe alternative powered by your Android phones. Receive local wallet payments automatically via SMS.')}</p>
                        </div>

                        {/* Simple Download Button */}
                        {androidAppUrl && (
                            <a
                                href={androidAppUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-3 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all shadow-sm hover:shadow-md flex-shrink-0 border border-slate-700 group"
                            >
                                <Smartphone className="w-6 h-6 text-emerald-400" />
                                <div className="flex flex-col items-start text-left">
                                    <span className="text-[10px] font-medium text-slate-300 uppercase tracking-wider leading-none">{__('Download App')}</span>
                                    <span className="text-sm font-bold leading-tight">{__('Android APK')}</span>
                                </div>
                                <Download className="w-4 h-4 ml-2 opacity-50 group-hover:opacity-100 transition-opacity rtl:mr-2 rtl:ml-0" />
                            </a>
                        )}
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
                                        <p className="text-sm font-medium text-slate-500">{__('Connected Devices')}</p>
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
                                        <p className="text-sm font-medium text-slate-500">{__('Total Payments')}</p>
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
                                        <p className="text-sm font-medium text-slate-500">{__('Webhook Status')}</p>
                                        <h3 className="text-lg font-bold text-slate-900 mt-1">
                                            {stats.webhook_configured ? (
                                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 border-none">{__('Active')}</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-slate-500 border-slate-200">{__('Not Configured')}</Badge>
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
                                        <p className="text-sm font-medium text-slate-500">{__('API Access')}</p>
                                        <h3 className="text-lg font-bold text-slate-900 mt-1">
                                            {token ? (
                                                <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-200 border-none">{__('Ready')}</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-rose-500 border-rose-200">{__('Missing Token')}</Badge>
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
                                    <Smartphone className="w-5 h-5 text-rose-500" />{__('Devices')}</CardTitle>
                                <CardDescription>{__('Connect Android phone to read Vodafone Cash and Instapay messages.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button className="w-full bg-slate-900 hover:bg-slate-800" onClick={() => router.visit(route('sms-payment-gateway.devices'))}>{__('Manage Devices')}<ArrowRight className="w-4 h-4 ml-2 rtl:rotate-180" />
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Key className="w-5 h-5 text-emerald-500" />{__('API Keys')}</CardTitle>
                                <CardDescription>{__('Manage your secret and publishable keys for API integration.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-emerald-200 hover:bg-emerald-50 text-emerald-700" onClick={() => router.visit(route('sms-payment-gateway.api-keys'))}>{__('API Keys')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <CreditCard className="w-5 h-5 text-indigo-500" />{__('Payment Links')}</CardTitle>
                                <CardDescription>{__('Create quick payment links for customers to collect required amounts.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-indigo-200 hover:bg-indigo-50" onClick={() => router.visit(route('sms-payment-gateway.payment-links'))}>{__('Create Payment Link')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Activity className="w-5 h-5 text-sky-500" />{__('API Sessions')}</CardTitle>
                                <CardDescription>{__('View active and completed checkout sessions created via your API keys.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-sky-200 hover:bg-sky-50" onClick={() => router.visit(route('sms-payment-gateway.checkout-sessions'))}>{__('View API Sessions')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Activity className="w-5 h-5 text-amber-500" />{__('Settings')}</CardTitle>
                                <CardDescription>{__('Set the receiving phone number and enable available payment services.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full" onClick={() => router.visit(route('sms-payment-gateway.settings'))}>{__('Gateway Settings')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <ExternalLink className="w-5 h-5 text-blue-500" />{__('Integration Docs')}</CardTitle>
                                <CardDescription>{__('Learn how to connect your application to the gateway.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-blue-200 hover:bg-blue-50 text-blue-700" onClick={() => router.visit(route('sms-payment-gateway.documentation'))}>{__('Integration Docs')}</Button>
                            </CardContent>
                        </Card>

                        <Card className="border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Webhook className="w-5 h-5 text-purple-500" />{__('Webhooks')}</CardTitle>
                                <CardDescription>{__('Configure webhook endpoints to receive real-time payment notifications on your server.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full border-purple-200 hover:bg-purple-50 text-purple-700" onClick={() => router.visit(route('sms-payment-gateway.webhooks'))}>{__('Configure Webhooks')}</Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Recent Transactions Table */}
                    <Card className="border border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-lg">{__('Recent Payments')}</CardTitle>
                                    <CardDescription>{__('Real-time payment receipts captured from your devices.')}</CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" onClick={() => router.visit(route('sms-payment-gateway.transactions'))} className="text-indigo-600 hover:text-indigo-700">
                                    {__('View All Payments')} <ArrowRight className="w-4 h-4 ml-1" />
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recentTransactions && recentTransactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                                            <tr>
                                                <th className="px-6 py-4 font-medium">{__('Date')}</th>
                                                <th className="px-6 py-4 font-medium">{__('Sender')}</th>
                                                <th className="px-6 py-4 font-medium">{__('Amount')}</th>
                                                <th className="px-6 py-4 font-medium">{__('Status')}</th>
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
                                                        {txn.amount ? formatMoney(txn.amount, txn.currency || 'EGP') : '---'}
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
                                    <p className="text-slate-500">{__('No transactions recorded yet.')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

