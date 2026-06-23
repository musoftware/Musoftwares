import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { Plus, Trash2, Edit, Save, Loader2, X } from 'lucide-react';
import { formatMoney } from '@/lib/utils';

export default function PriceList({ items, currencies }) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editingItem, setEditingItem] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        default_price: '',
        currency_id: currencies[0]?.id || ''
    });

    const handleEdit = (item) => {
        setEditingItem(item.id);
        setFormData({
            name: item.name,
            description: item.description || '',
            default_price: item.default_price,
            currency_id: item.currency_id || currencies[0]?.id
        });
    };

    const handleCancel = () => {
        setEditingItem(null);
        setFormData({
            name: '',
            description: '',
            default_price: '',
            currency_id: currencies[0]?.id || ''
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        if (editingItem) {
            router.put(`/admin/contract-price-items/${editingItem}`, formData, {
                onSuccess: () => {
                    handleCancel();
                    setIsSubmitting(false);
                },
                onError: () => setIsSubmitting(false)
            });
        } else {
            router.post('/admin/contract-price-items', formData, {
                onSuccess: () => {
                    handleCancel();
                    setIsSubmitting(false);
                },
                onError: () => setIsSubmitting(false)
            });
        }
    };

    const handleDelete = (id) => {
        if (!confirm('Are you sure you want to delete this item?')) return;
        router.delete(`/admin/contract-price-items/${id}`);
    };

    return (
        <AdminSidebarLayout 
            title="Contract Price List" 
            header="Global Contract Price List"
        >
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>{editingItem ? 'Edit Item' : 'Add New Item'}</CardTitle>
                            <CardDescription>Items added here will be available to quickly select when creating contracts.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label>Item Name / Title</Label>
                                    <Input 
                                        required 
                                        value={formData.name} 
                                        onChange={e => setFormData({...formData, name: e.target.value})} 
                                        placeholder="e.g. Logo Design"
                                    />
                                </div>
                                <div>
                                    <Label>Description (Optional)</Label>
                                    <Input 
                                        value={formData.description} 
                                        onChange={e => setFormData({...formData, description: e.target.value})} 
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <Label>Default Price</Label>
                                        <Input 
                                            required 
                                            type="number" 
                                            step="0.01" 
                                            value={formData.default_price} 
                                            onChange={e => setFormData({...formData, default_price: e.target.value})} 
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
                                <div className="flex gap-2 pt-2">
                                    <Button type="submit" disabled={isSubmitting} className="flex-1">
                                        {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                        {editingItem ? 'Update Item' : 'Add Item'}
                                    </Button>
                                    {editingItem && (
                                        <Button type="button" variant="outline" onClick={handleCancel}>
                                            <X className="w-4 h-4" />
                                        </Button>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="md:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Existing Items</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {items.length === 0 ? (
                                <div className="text-center p-8 text-slate-500 border border-dashed rounded-lg bg-slate-50">
                                    No global price items exist yet.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {items.map(item => (
                                        <div key={item.id} className="flex items-start justify-between p-4 border rounded-md hover:bg-slate-50 transition-colors">
                                            <div>
                                                <h4 className="font-semibold text-slate-900">{item.name}</h4>
                                                {item.description && (
                                                    <p className="text-sm text-slate-500 mt-1">{item.description}</p>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <span className="font-semibold px-2 py-1 bg-slate-100 rounded text-sm text-slate-800">
                                                    {formatMoney(item.default_price, item.currency_id)}
                                                </span>
                                                <div className="flex items-center gap-1">
                                                    <Button variant="ghost" size="icon" onClick={() => handleEdit(item)}>
                                                        <Edit className="w-4 h-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" className="text-red-500 hover:text-red-600 hover:bg-red-50" onClick={() => handleDelete(item.id)}>
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
