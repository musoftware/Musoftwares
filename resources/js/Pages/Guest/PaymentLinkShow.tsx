import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function PaymentLinkShow({ paymentLink, pay_url }) {
    const { data, setData, post, processing, errors } = useForm({
        guest_name: '',
        guest_email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(pay_url);
    };

    return (
        <PublicLayout>
            <Head title={paymentLink.title} />
            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-md w-full">
                    <CardHeader className="text-center">
                        <CardTitle className="text-2xl font-bold">{paymentLink.title}</CardTitle>
                        <CardDescription>{__('general.payment_link_description', { default: 'Please enter your details to proceed with the payment.' })}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-6 text-center">
                            <span className="text-4xl font-extrabold text-gray-900">
                                {formatCurrency(paymentLink.amount, paymentLink.currency?.currency || 'EGP')}
                            </span>
                        </div>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <Label htmlFor="guest_name">{__('general.full_name', { default: 'Full Name' })}</Label>
                                <Input
                                    id="guest_name"
                                    type="text"
                                    value={data.guest_name}
                                    onChange={(e) => setData('guest_name', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.guest_name && <p className="text-red-500 text-sm mt-1">{errors.guest_name}</p>}
                            </div>

                            <div>
                                <Label htmlFor="guest_email">{__('general.email_address', { default: 'Email Address' })}</Label>
                                <Input
                                    id="guest_email"
                                    type="email"
                                    value={data.guest_email}
                                    onChange={(e) => setData('guest_email', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.guest_email && <p className="text-red-500 text-sm mt-1">{errors.guest_email}</p>}
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? __('general.processing', { default: 'Processing...' }) : __('general.pay_now', { default: 'Pay Now' })}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </PublicLayout>
    );
}
