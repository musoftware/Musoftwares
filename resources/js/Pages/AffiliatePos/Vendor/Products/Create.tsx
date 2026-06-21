import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Save, PackagePlus } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function VendorProductsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        category_id: 1, // Defaulting for simple prototype
        price: '',
        commission: '',
        stock: ''
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('affiliate_pos.vendor.products.store'));
    };

    return (
        <div className="p-6 max-w-4xl mx-auto space-y-6 font-sans">
            <Head title={__('general.add_product')} />

            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" onClick={() => window.history.back()} className="rounded-full bg-white shadow-sm border border-gray-200">
                    <ArrowLeft className="w-5 h-5 text-gray-600" />
                </Button>
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 tracking-tight">{__('general.add_new_product')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.create_a_new_simple_product_for_the_affiliate_catalog')}</p>
                </div>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <Card className="shadow-sm border-gray-200 bg-white">
                    <CardHeader className="bg-gray-50/50 border-b p-5">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <PackagePlus className="w-5 h-5 text-gray-500" />{__('general.basic_information')}</CardTitle>
                        <CardDescription>{__('general.details_that_affiliates_will_see_when_browsing_products')}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-2 col-span-2">
                                <label className="text-sm font-medium text-gray-700">{__('general.product_name')}</label>
                                <Input 
                                    value={data.name} 
                                    onChange={e => setData('name', e.target.value)} 
                                    placeholder={__('general.e_g_wireless_noise_cancelling_headphones')}
                                    className="bg-gray-50/50 focus:bg-white"
                                />
                                {errors.name && <div className="text-sm text-red-600">{errors.name}</div>}
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">Price (EGP)</label>
                                <Input 
                                    type="number" 
                                    value={data.price} 
                                    onChange={e => setData('price', e.target.value)} 
                                    placeholder="0.00"
                                    className="bg-gray-50/50 focus:bg-white"
                                />
                                {errors.price && <div className="text-sm text-red-600">{errors.price}</div>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">Affiliate Commission (EGP)</label>
                                <Input 
                                    type="number" 
                                    value={data.commission} 
                                    onChange={e => setData('commission', e.target.value)} 
                                    placeholder="0.00"
                                    className="bg-gray-50/50 focus:bg-white"
                                />
                                {errors.commission && <div className="text-sm text-red-600">{errors.commission}</div>}
                            </div>

                            <div className="space-y-2 col-span-2 md:col-span-1">
                                <label className="text-sm font-medium text-gray-700">{__('general.initial_stock_quantity')}</label>
                                <Input 
                                    type="number" 
                                    value={data.stock} 
                                    onChange={e => setData('stock', e.target.value)} 
                                    placeholder="100"
                                    className="bg-gray-50/50 focus:bg-white"
                                />
                                {errors.stock && <div className="text-sm text-red-600">{errors.stock}</div>}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-3">
                    <Button type="button" variant="outline" onClick={() => window.history.back()} disabled={processing}>
                        Cancel
                    </Button>
                    <Button type="submit" className="bg-blue-600 hover:bg-blue-700 shadow-md" disabled={processing}>
                        <Save className="w-4 h-4 me-2" />{__('general.save_product')}</Button>
                </div>
            </form>
        </div>
    );
}
