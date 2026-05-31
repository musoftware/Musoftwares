import { __ } from '@/lib/i18n';
import React from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Smartphone, Webhook, Key, Activity, CreditCard, ArrowRight, ShieldCheck } from 'lucide-react';
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
}

export default function TextPaymentGateway({ devices, webhook, token, stats, recentTransactions }: DashboardProps) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Payment Gateway</h2>}>
            <Head title="Payment Gateway" />

            <div className="py-8 md:py-12 bg-slate-50/50 min-h-[calc(100vh-65px)]">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    {/* Header Section */}
                    <div>
                        <h1 className="text-3xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <ShieldCheck className="w-8 h-8 text-rose-600" />
                            Your Own Payment Gateway
                        </h1>
                        <p className="text-slate-500 mt-2 text-lg">A Stripe alternative powered by your Android phones. Receive local wallet payments automatically via SMS.</p>
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
                                        <p className="text-sm font-medium text-slate-500">Connected Devices</p>
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
                                        <p className="text-sm font-medium text-slate-500">Total Payments</p>
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
                                        <p className="text-sm font-medium text-slate-500">Webhook Status</p>
                                        <h3 className="text-lg font-bold text-slate-900 mt-1">
                                            {stats.webhook_configured ? (
                                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 border-none">Active</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-slate-500 border-slate-200">Not Configured</Badge>
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
                                        <p className="text-sm font-medium text-slate-500">API Access</p>
                                        <h3 className="text-lg font-bold text-slate-900 mt-1">
                                            {token ? (
                                                <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-200 border-none">Ready</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-rose-500 border-rose-200">Missing Token</Badge>
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
                                    <Activity className="w-5 h-5 text-amber-500" />{__('Settings')}</CardTitle>
                                <CardDescription>{__('Set the receiving phone number and enable available payment services.')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline" className="w-full" onClick={() => router.visit(route('sms-payment-gateway.settings'))}>{__('Gateway Settings')}</Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Recent Transactions Table */}
                    <Card className="border border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-lg">Recent Payments</CardTitle>
                                    <CardDescription>Real-time payment receipts captured from your devices.</CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" onClick={() => router.visit(route('sms-payment-gateway.transactions'))} className="text-indigo-600 hover:text-indigo-700">
                                    View All Payments <ArrowRight className="w-4 h-4 ml-1" />
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recentTransactions && recentTransactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                                            <tr>
                                                <th className="px-6 py-4 font-medium">Date</th>
                                                <th className="px-6 py-4 font-medium">Sender</th>
                                                <th className="px-6 py-4 font-medium">Amount</th>
                                                <th className="px-6 py-4 font-medium">Status</th>
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
                                                        {txn.is_valid ? (
                                                            <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200">Valid</Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">Pending</Badge>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <Activity className="w-10 h-10 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500">No transactions recorded yet.</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
