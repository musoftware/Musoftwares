import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import MDEditor from '@uiw/react-md-editor';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { Loader2, Plus, Trash2, Save, FileText } from 'lucide-react';
import axios from 'axios';

export default function Form({ contract, priceItems, currencies, exchangeRates }: any) {
    const { data: formData, setData: setFormData, post, put, processing: isLoading, errors } = useForm({
        project_name: contract?.project_name || '',
        description: contract?.description || '',
        total_amount: contract?.total_amount || '',
        currency_id: contract?.currency_id || currencies[0]?.id || '',
        duration: contract?.duration || '',
        status: contract?.status || 'draft',
        content: {
            pricing_items: contract?.content?.pricing_items || []
        }
    });

    const [isPriceItemModalOpen, setIsPriceItemModalOpen] = useState(false);
    const [globalItems, setGlobalItems] = useState(priceItems || []);

    const onSubmit = (e: any) => {
        e.preventDefault();
        
        const url = contract ? `/admin/contracts/${contract.id}` : '/admin/contracts';

        if (contract) {
            put(url);
        } else {
            post(url);
        }
    };

    const addPricing = () => {
        setFormData('content', {
            ...formData.content,
            pricing_items: [...formData.content.pricing_items, { item: '', description: '', price: 0, currency_id: formData.currency_id }]
        });
    };

    const updatePricing = (i: number, field: string, val: any) => {
        const np = [...formData.content.pricing_items];
        np[i] = { ...np[i], [field]: val };
        setFormData('content', { ...formData.content, pricing_items: np });
    };

    const removePricing = (i: number) => {
        setFormData('content', {
            ...formData.content,
            pricing_items: formData.content.pricing_items.filter((_, idx) => idx !== i)
        });
    };

    const addFromGlobalItem = (item: any) => {
        const rate = exchangeRates?.[item.currency_id]?.[formData.currency_id] || 1;
        const convertedPrice = Math.ceil((item.default_price * rate) / 5) * 5;

        setFormData('content', {
            ...formData.content,
            pricing_items: [
                ...formData.content.pricing_items,
                { item: item.name, description: item.description || '', price: convertedPrice, currency_id: formData.currency_id }
            ]
        });
    };

    const saveAsGlobalItem = async (index: number) => {
        const itemData = formData.content.pricing_items[index];
        if (!itemData.item || !itemData.price) {
            alert('Title and Price are required to save to the global price list.');
            return;
        }

        try {
            const res = await axios.post('/admin/contract-price-items', {
                name: itemData.item,
                description: itemData.description,
                default_price: itemData.price,
                currency_id: itemData.currency_id || formData.currency_id,
            });
            setGlobalItems([...globalItems, res.data]);
            alert('Item added to Global Price List.');
        } catch (e) {
            console.error(e);
            alert('Failed to save global item.');
        }
    };

    return (
        <AdminSidebarLayout 
            title={contract ? 'Edit Contract' : 'Create Contract'} 
            header={contract ? 'Edit Contract' : 'Create New Contract'}
        >
            <div className="mb-6">
                <Link href="/admin/contracts" className="text-slate-900 hover:underline">
                    &larr; Back to Contracts
                </Link>
            </div>

            <form onSubmit={onSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>General Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>Title / Project Name</Label>
                                <Input 
                                    value={formData.project_name} 
                                    onChange={e => setFormData('project_name', e.target.value)}
                                    placeholder="e.g. E-Commerce Website Development"
                                    required
                                />
                                {errors.project_name && <div className="mt-1 text-xs text-red-500">{errors.project_name}</div>}
                            </div>
                            <div data-color-mode="light">
                                <Label>Description</Label>
                                <MDEditor 
                                    value={formData.description} 
                                    onChange={val => setFormData('description', val || '')}
                                    height={200}
                                    className="mt-1"
                                />
                                {errors.description && <div className="mt-1 text-xs text-red-500">{errors.description}</div>}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Total Amount</Label>
                                    <Input 
                                        type="number" 
                                        step="0.01" 
                                        value={formData.total_amount} 
                                        onChange={e => setFormData('total_amount', e.target.value)}
                                        required
                                    />
                                    {errors.total_amount && <div className="mt-1 text-xs text-red-500">{errors.total_amount}</div>}
                                </div>
                                <div>
                                    <Label>Currency</Label>
                                    <select 
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                        value={formData.currency_id}
                                        onChange={e => setFormData('currency_id', e.target.value)}
                                        required
                                    >
                                        {currencies.map((c: any) => (
                                            <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>
                                        ))}
                                    </select>
                                    {errors.currency_id && <div className="mt-1 text-xs text-red-500">{errors.currency_id}</div>}
                                </div>
                                <div>
                                    <Label>Duration (Weeks)</Label>
                                    <Input 
                                        type="number" 
                                        min="1"
                                        value={formData.duration} 
                                        onChange={e => setFormData('duration', e.target.value)}
                                        placeholder="e.g. 4"
                                    />
                                    {errors.duration && <div className="mt-1 text-xs text-red-500">{errors.duration}</div>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Pricing Items</CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={addPricing}>
                                <Plus className="w-4 h-4 mr-1" /> Add Custom Item
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {formData.content.pricing_items.map((item: any, idx: number) => (
                                <div key={idx} className="flex items-start gap-4 p-4 border rounded-md bg-slate-50">
                                    <div className="flex-1 space-y-3">
                                        <div className="grid grid-cols-3 gap-3">
                                            <div className="col-span-2">
                                                <Label className="text-xs">Item Name</Label>
                                                <Input 
                                                    placeholder="e.g. Landing Page Design" 
                                                    value={item.item} 
                                                    onChange={e => updatePricing(idx, 'item', e.target.value)} 
                                                />
                                            </div>
                                            <div className="flex gap-2">
                                            <div className="flex-1">
                                                <Label className="text-xs">Price</Label>
                                                <Input 
                                                    type="number" 
                                                    placeholder="0" 
                                                    value={item.price} 
                                                    onChange={e => updatePricing(idx, 'price', e.target.value)} 
                                                />
                                            </div>
                                            <div className="w-[100px]">
                                                <Label className="text-xs">Currency</Label>
                                                <select 
                                                    className="flex h-9 w-full rounded-md border border-slate-200 bg-transparent px-2 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-slate-950"
                                                    value={item.currency_id || formData.currency_id}
                                                    onChange={e => updatePricing(idx, 'currency_id', e.target.value)}
                                                >
                                                    {currencies.map((c: any) => (
                                                        <option key={c.id} value={c.id}>{c.currency}</option>
                                                    ))}
                                                </select>
                                            </div>
                                        </div>
                                        </div>
                                        <div>
                                            <Label className="text-xs">Description</Label>
                                            <Input 
                                                placeholder="Optional details" 
                                                value={item.description} 
                                                onChange={e => updatePricing(idx, 'description', e.target.value)} 
                                            />
                                        </div>
                                    </div>
                                    <div className="flex flex-col gap-2 pt-5">
                                        <Button type="button" variant="ghost" size="icon" onClick={() => removePricing(idx)} className="text-red-500 hover:bg-red-100">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" onClick={() => saveAsGlobalItem(idx)} title="Save to Global Price List">
                                            <Save className="w-3 h-3" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                            {formData.content.pricing_items.length === 0 && (
                                <div className="text-center p-6 border-2 border-dashed rounded-md text-slate-500">
                                    No items added yet.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Global Price List</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-[400px] overflow-y-auto pr-2">
                                {globalItems.map((item: any) => {
                                    const rate = exchangeRates?.[item.currency_id]?.[formData.currency_id] || 1;
                                    const displayPrice = Math.ceil((item.default_price * rate) / 5) * 5;
                                    const isConverted = item.currency_id != formData.currency_id;

                                    return (
                                        <div key={item.id} className="p-3 border rounded-md hover:bg-slate-50 flex flex-col gap-2">
                                            <div className="flex justify-between items-start">
                                                <div className="font-semibold text-sm">{item.name}</div>
                                                <div className="text-sm font-medium text-end">
                                                    {displayPrice}
                                                    {isConverted && <div className="text-[10px] text-slate-400 font-normal leading-tight">(Converted)</div>}
                                                </div>
                                            </div>
                                            <div className="text-xs text-slate-500 line-clamp-2">{item.description}</div>
                                            <Button type="button" variant="secondary" size="sm" className="w-full mt-2" onClick={() => addFromGlobalItem(item)}>
                                                Add to Contract
                                            </Button>
                                        </div>
                                    );
                                })}
                                {globalItems.length === 0 && (
                                    <div className="text-sm text-slate-500">No items in the global price list yet. Add a custom item and save it.</div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>Status</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                    value={formData.status}
                                    onChange={e => setFormData('status', e.target.value)}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="signed">Signed</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                </select>
                                {errors.status && <div className="mt-1 text-xs text-red-500">{errors.status}</div>}
                            </div>
                        </CardContent>
                        <CardFooter>
                            <Button type="submit" className="w-full" disabled={isLoading}>
                                {isLoading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                {contract ? 'Update Version' : 'Save Contract'}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </AdminSidebarLayout>
    );
}
