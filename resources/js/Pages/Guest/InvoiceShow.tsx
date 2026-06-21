import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { CreditCard, Receipt, Clock, MapPin, User, FileText, Folder } from 'lucide-react';
import { useForm } from '@inertiajs/react';

export default function InvoiceShow({ invoice, pay_url }: { invoice: any, pay_url: string }) {
    const { data, setData, post, processing, errors } = useForm({
        guest_name: invoice.user?.name || '',
        guest_email: invoice.user?.email || '',
    });

    const handlePayment = (e: React.FormEvent) => {
        e.preventDefault();
        post(pay_url);
    };

    return (
        <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            <Head title={`Invoice #${invoice.id}`} />

            <div className="max-w-4xl mx-auto space-y-8">
                {/* Header */}
                <div className="text-center">
                    <h1 className="text-3xl font-bold text-gray-900">{__('general.invoice')} #{invoice.id}</h1>
                    <p className="mt-2 text-sm text-gray-500">
                        {__('general.issued_on')}: {new Date(invoice.created_at).toLocaleDateString()}
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Invoice Details */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl flex items-center">
                                    <FileText className="w-5 h-5 me-2 text-blue-600" />
                                    {__('general.invoice_items')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {invoice.items?.map((item: any) => (
                                        <div key={item.id} className="flex justify-between items-center py-3 border-b last:border-0 border-gray-100">
                                            <div>
                                                <h4 className="font-medium text-gray-900">{item.name}</h4>
                                                {item.description && <p className="text-sm text-gray-500">{item.description}</p>}
                                                <p className="text-xs text-gray-400 mt-1">
                                                    {item.quantity} x {formatCurrency(item.rate, invoice.currency)}
                                                </p>
                                            </div>
                                            <div className="font-semibold text-gray-900">
                                                {formatCurrency(item.total, invoice.currency)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                            <CardFooter className="bg-gray-50 border-t flex justify-between items-center py-4">
                                <span className="font-medium text-gray-700">{__('general.total')}</span>
                                <span className="text-2xl font-bold text-blue-600">
                                    {formatCurrency(invoice.total, invoice.currency)}
                                </span>
                            </CardFooter>
                        </Card>

                        {/* Client Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center">
                                    <User className="w-5 h-5 me-2 text-gray-400" />
                                    {__('general.client_details')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-gray-600">
                                <div className="flex items-center gap-2">
                                    <User className="w-4 h-4" /> 
                                    <span>{invoice.user?.name || __('general.n_a')}</span>
                                </div>
                                {invoice.project && (
                                    <div className="flex items-center gap-2">
                                        <Folder className="w-4 h-4" /> 
                                        <span>{invoice.project.name}</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Payment Section */}
                    <div>
                        <Card className="sticky top-6">
                            <CardHeader className="bg-blue-600 text-white rounded-t-xl">
                                <CardTitle className="flex items-center text-lg">
                                    <CreditCard className="w-5 h-5 me-2" />
                                    {__('general.payment')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="mb-6 space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{__('general.status')}</span>
                                        <span className={`font-semibold ${
                                            invoice.status === 'paid' ? 'text-green-600' : 
                                            invoice.status === 'cancelled' ? 'text-red-600' : 'text-orange-500'
                                        }`}>
                                            {__('general.status_' + invoice.status)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{__('general.unpaid')}</span>
                                        <span className="font-bold text-gray-900">{formatCurrency(invoice.unpaid_total || invoice.total, invoice.currency)}</span>
                                    </div>
                                </div>

                                {invoice.status !== 'paid' && invoice.status !== 'cancelled' ? (
                                    <form onSubmit={handlePayment} className="space-y-4 border-t pt-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="guest_name">{__('general.name')}</Label>
                                            <Input
                                                id="guest_name"
                                                value={data.guest_name}
                                                onChange={e => setData('guest_name', e.target.value)}
                                                required
                                            />
                                            {errors.guest_name && <p className="text-red-500 text-xs">{errors.guest_name}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="guest_email">{__('general.email')}</Label>
                                            <Input
                                                id="guest_email"
                                                type="email"
                                                value={data.guest_email}
                                                onChange={e => setData('guest_email', e.target.value)}
                                                required
                                            />
                                            {errors.guest_email && <p className="text-red-500 text-xs">{errors.guest_email}</p>}
                                        </div>

                                        <Button type="submit" className="w-full bg-blue-600 hover:bg-blue-700" disabled={processing}>
                                            {processing ? __('general.processing') : __('general.pay_now')}
                                        </Button>
                                    </form>
                                ) : (
                                    <div className="p-4 bg-green-50 text-green-700 rounded-md border border-green-100 text-center text-sm font-medium">
                                        {invoice.status === 'paid' ? __('general.invoice_already_paid') : __('general.invoice_cancelled')}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    );
}
