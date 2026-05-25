import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Save, Edit3 } from 'lucide-react';

export default function VendorProductsEdit({ product }: any) {
    const { data, setData, put, processing, errors } = useForm({
        name: product.name,
        price: product.price,
        commission: product.commission,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        // Since we only have stock update API currently, let's assume a full update method exists or will be added.
        // I will just stub it to a generic update route.
        alert('Product details update logic will trigger here');
    };

    return (
        <div className="p-6 max-w-4xl mx-auto space-y-6 font-sans">
            <Head title={`Edit ${product.name}`} />

            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" onClick={() => window.history.back()} className="rounded-full bg-white shadow-sm border border-gray-200">
                    <ArrowLeft className="w-5 h-5 text-gray-600" />
                </Button>
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 tracking-tight">Edit Product</h1>
                    <p className="text-sm text-gray-500 mt-1">Update details for {product.name}</p>
                </div>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <Card className="shadow-sm border-gray-200 bg-white">
                    <CardHeader className="bg-gray-50/50 border-b p-5">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <Edit3 className="w-5 h-5 text-gray-500" /> Basic Information
                        </CardTitle>
                        <CardDescription>Details that affiliates will see when browsing products.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-2 col-span-2">
                                <label className="text-sm font-medium text-gray-700">Product Name</label>
                                <Input 
                                    value={data.name} 
                                    onChange={e => setData('name', e.target.value)} 
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
                                    className="bg-gray-50/50 focus:bg-white"
                                />
                                {errors.commission && <div className="text-sm text-red-600">{errors.commission}</div>}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-3">
                    <Button type="button" variant="outline" onClick={() => window.history.back()} disabled={processing}>
                        Cancel
                    </Button>
                    <Button type="submit" className="bg-blue-600 hover:bg-blue-700 shadow-md" disabled={processing}>
                        <Save className="w-4 h-4 mr-2" />
                        Update Product
                    </Button>
                </div>
            </form>
        </div>
    );
}
