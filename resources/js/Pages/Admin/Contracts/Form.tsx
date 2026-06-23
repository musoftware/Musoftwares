import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { Loader2, Plus, Trash2, Save, FileText } from 'lucide-react';
import axios from 'axios';

export default function Form({ contract, priceItems, currencies }) {
    const [isLoading, setIsLoading] = useState(false);
    const [formData, setFormData] = useState({
        project_name: contract?.project_name || '',
        description: contract?.description || '',
        total_amount: contract?.total_amount || '',
        currency_id: contract?.currency_id || currencies[0]?.id || '',
        status: contract?.status || 'draft',
        content: {
            pricing_items: contract?.content?.pricing_items || []
        }
    });

    const [isPriceItemModalOpen, setIsPriceItemModalOpen] = useState(false);
    const [globalItems, setGlobalItems] = useState(priceItems || []);

    const onSubmit = (e) => {
        e.preventDefault();
        setIsLoading(true);
        
        const action = contract ? router.put : router.post;
        const url = contract ? `/admin/contracts/${contract.id}` : '/admin/contracts';

        action(url, formData, {
            onSuccess: () => {
                setIsLoading(false);
            },
            onError: () => {
                setIsLoading(false);
            }
        });
    };

    const addPricing = () => {
        setFormData(p => ({
            ...p,
            content: {
                ...p.content,
                pricing_items: [...p.content.pricing_items, { item: '', description: '', price: 0 }]
            }
        }));
    };

    const updatePricing = (i, field, val) => {
        setFormData(p => {
            const np = [...p.content.pricing_items];
            np[i][field] = val;
            return { ...p, content: { ...p.content, pricing_items: np } };
        });
    };

    const removePricing = (i) => {
        setFormData(p => ({
            ...p,
            content: {
                ...p.content,
                pricing_items: p.content.pricing_items.filter((_, idx) => idx !== i)
            }
        }));
    };

    const addFromGlobalItem = (item) => {
        setFormData(p => ({
            ...p,
            content: {
                ...p.content,
                pricing_items: [
                    ...p.content.pricing_items,
                    { item: item.name, description: item.description || '', price: item.default_price }
                ]
            }
        }));
    };

    const saveAsGlobalItem = async (index) => {
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
                currency_id: formData.currency_id,
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
                                    onChange={e => setFormData({...formData, project_name: e.target.value})}
                                    placeholder="e.g. E-Commerce Website Development"
                                    required
                                />
                            </div>
                            <div>
                                <Label>Description</Label>
                                <Textarea 
                                    value={formData.description} 
                                    onChange={e => setFormData({...formData, description: e.target.value})}
                                    rows={4}
                                    placeholder="Executive summary of the contract..."
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Total Amount</Label>
                                    <Input 
                                        type="number" 
                                        step="0.01" 
                                        value={formData.total_amount} 
                                        onChange={e => setFormData({...formData, total_amount: e.target.value})}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label>Currency</Label>
                                    <select 
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                        value={formData.currency_id}
                                        onChange={e => setFormData({...formData, currency_id: e.target.value})}
                                        required
                                    >
                                        {currencies.map(c => (
                                            <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>
                                        ))}
                                    </select>
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
                            {formData.content.pricing_items.map((item, idx) => (
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
                                            <div>
                                                <Label className="text-xs">Price</Label>
                                                <Input 
                                                    type="number" 
                                                    placeholder="0.00" 
                                                    value={item.price} 
                                                    onChange={e => updatePricing(idx, 'price', e.target.value)} 
                                                />
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
                                {globalItems.map(item => (
                                    <div key={item.id} className="p-3 border rounded-md hover:bg-slate-50 flex flex-col gap-2">
                                        <div className="flex justify-between items-start">
                                            <div className="font-semibold text-sm">{item.name}</div>
                                            <div className="text-sm font-medium">{item.default_price}</div>
                                        </div>
                                        <div className="text-xs text-slate-500 line-clamp-2">{item.description}</div>
                                        <Button type="button" variant="secondary" size="sm" className="w-full mt-2" onClick={() => addFromGlobalItem(item)}>
                                            Add to Contract
                                        </Button>
                                    </div>
                                ))}
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
                                    onChange={e => setFormData({...formData, status: e.target.value})}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="signed">Signed</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                </select>
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
