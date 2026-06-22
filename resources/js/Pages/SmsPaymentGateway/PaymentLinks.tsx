import { __ } from '@/lib/i18n';
import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { toast } from 'sonner';
import { Copy, Plus, ExternalLink } from 'lucide-react';

interface PaymentOrder {
    id: number;
    uuid: string;
    amount: string;
    status: string;
    created_at: string;
    currency?: { symbol: string };
    metadata: {
        order_number?: string;
        customer_name?: string;
    } | null;
}

interface Props {
    links: {
        data: PaymentOrder[];
        links: any[];
    };
}

export default function PaymentLinks({ links }: Props) {
    const [isCreating, setIsCreating] = useState(false);
    const { data, setData, post, processing, reset, errors } = useForm({
        amount: '',
        customer_name: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('sms-payment-gateway.payment-links.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.link_created_successfully'));
                reset();
                setIsCreating(false);
            },
        });
    };

    const copyToClipboard = (url: string) => {
        navigator.clipboard.writeText(url);
        toast.success(__('general.link_copied'));
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('payment.payment_links')} />

            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">{__('payment.quick_payment_links')}</h1>
                        <p className="mt-2 text-sm text-gray-600">{__('general.create_instant_payment_links_to_share_with_your_customers')}</p>
                    </div>
                    <Button onClick={() => setIsCreating(!isCreating)} className="flex items-center gap-2">
                        <Plus className="w-4 h-4" />{__('general.create_new_link')}</Button>
                </div>

                {isCreating && (
                    <Card className="mb-8 border-indigo-100 shadow-sm animate-in fade-in slide-in-from-top-4">
                        <CardHeader className="bg-indigo-50/50 pb-4">
                            <CardTitle className="text-indigo-800">{__('general.new_link')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <form onSubmit={submit} className="flex flex-col md:flex-row gap-4 items-end">
                                <div className="space-y-2 flex-1 w-full">
                                    <Label htmlFor="amount">{__('general.required_amount_egp')}</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        required
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        placeholder={__('general.example_500')}
                                    />
                                    {errors.amount && <p className="text-sm text-red-600">{errors.amount}</p>}
                                </div>
                                <div className="space-y-2 flex-1 w-full">
                                    <Label htmlFor="customer_name">{__('general.customer_name_optional')}</Label>
                                    <Input
                                        id="customer_name"
                                        type="text"
                                        value={data.customer_name}
                                        onChange={(e) => setData('customer_name', e.target.value)}
                                        placeholder={__('general.example_ahmed_mohamed')}
                                    />
                                </div>
                                <div className="w-full md:w-auto flex gap-2">
                                    <Button type="button" variant="outline" onClick={() => setIsCreating(false)}>{__('general.cancel')}</Button>
                                    <Button type="submit" disabled={processing} className="w-full md:w-auto">{__('general.generate_link')}</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th scope="col" className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{__('general.order_number')}</th>
                                <th scope="col" className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{__('general.amount')}</th>
                                <th scope="col" className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{__('general.customer')}</th>
                                <th scope="col" className="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{__('general.status')}</th>
                                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {(links.data as any).length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-gray-500">{__('general.no_previous_payment_links_create_your_first_link_now')}</td>
                                </tr>
                            ) : (
                                (links.data as any).map((link) => {
                                    const checkoutUrl = route('sms-payment-gateway.widget.show', { uuid: link.uuid });
                                    return (
                                        <tr key={link.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                                {link.metadata?.order_number || ('ORD-' + link.id)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                                {Number(link.amount).toFixed(2)} {link.currency?.symbol || ''}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {link.metadata?.customer_name || '—'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                    link.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                    {link.status === 'paid' ? __('general.paid') : __('general.pending')}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button variant="outline" size="sm" onClick={() => copyToClipboard(checkoutUrl)} className="flex items-center gap-1">
                                                        <Copy className="w-3 h-3" />{__('general.copy_link')}</Button>
                                                    <a href={checkoutUrl} target="_blank" rel="noreferrer" className="text-gray-400 hover:text-indigo-600 transition">
                                                        <ExternalLink className="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
