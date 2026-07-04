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
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Package } from 'lucide-react';
import { toast } from 'sonner';

export default function PriceList({ items, currencies }) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editingItem, setEditingItem] = useState<number | null>(null);
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        default_price: '',
        currency_id: currencies[0]?.id || '',
    });

    const handleEdit = (item) => {
        setEditingItem(item.id);
        setFormData({
            name: item.name,
            description: item.description || '',
            default_price: item.default_price,
            currency_id: item.currency_id || currencies[0]?.id,
        });
    };

    const handleCancel = () => {
        setEditingItem(null);
        setFormData({ name: '', description: '', default_price: '', currency_id: currencies[0]?.id || '' });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);

        const onFinish = (success: boolean) => {
            setIsSubmitting(false);
            if (success) {
                toast.success(editingItem ? __('general.updated') || 'Updated' : __('general.created') || 'Created');
                handleCancel();
            } else {
                toast.error(__('general.error_occurred') || 'Something went wrong');
            }
        };

        if (editingItem) {
            router.put(`/admin/contract-price-items/${editingItem}`, formData, {
                onSuccess: () => onFinish(true),
                onError: () => onFinish(false),
            });
        } else {
            router.post('/admin/contract-price-items', formData, {
                onSuccess: () => onFinish(true),
                onError: () => onFinish(false),
            });
        }
    };

    const handleDelete = () => {
        if (!pendingDelete) return;
        router.delete(`/admin/contract-price-items/${pendingDelete}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.deleted') || 'Deleted');
                setPendingDelete(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingDelete(null);
            },
        });
    };

    return (
        <AdminSidebarLayout
            title={__('general.contract_price_list')}
            header={__('general.global_contract_price_list')}
        >
            <Head title={__('general.contract_price_list')} />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>{editingItem ? __('general.edit_item') : __('general.add_new_item')}</CardTitle>
                            <CardDescription>{__('general.price_list_help') || 'Items added here will be available to quickly select when creating contracts.'}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label>{__('general.item_name')}</Label>
                                    <Input required value={formData.name} onChange={e => setFormData({ ...formData, name: e.target.value })} placeholder={__('general.e_g_logo_design')} />
                                </div>
                                <div>
                                    <Label>{__('general.description_optional')}</Label>
                                    <Input value={formData.description} onChange={e => setFormData({ ...formData, description: e.target.value })} />
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <Label>{__('general.default_price')}</Label>
                                        <Input required type="number" step="0.01" value={formData.default_price} onChange={e => setFormData({ ...formData, default_price: e.target.value })} />
                                    </div>
                                    <div>
                                        <Label>{__('general.currency')}</Label>
                                        <select
                                            className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                            value={formData.currency_id}
                                            onChange={e => setFormData({ ...formData, currency_id: e.target.value })}
                                            required
                                        >
                                            {currencies.map((c: any) => (
                                                <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="flex gap-2 pt-2">
                                    <Button type="submit" disabled={isSubmitting} className="flex-1">
                                        {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Save className="w-4 h-4 me-2" />}
                                        {editingItem ? __('general.update_item') : __('general.add_item')}
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
                            <CardTitle>{__('general.existing_items')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {items.length === 0 ? (
                                <EmptyState
                                    icon={Package}
                                    title={__('general.no_price_items_yet') || 'No price items yet'}
                                    description={__('general.add_items_to_quickly_select_when_creating_contracts') || 'Add items to quickly select when creating contracts.'}
                                />
                            ) : (
                                <div className="space-y-3">
                                    {items.map((item: any) => (
                                        <div key={item.id} className="flex items-start justify-between gap-4 p-4 border rounded-md hover:bg-slate-50 transition-colors">
                                            <div className="min-w-0">
                                                <h4 className="font-semibold text-slate-900">{item.name}</h4>
                                                {item.description && (
                                                    <p className="text-sm text-slate-500 mt-1">{item.description}</p>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-3 shrink-0">
                                                <span className="font-semibold px-2 py-1 bg-slate-100 rounded text-sm font-mono text-slate-800">
                                                    {formatMoney(item.default_price, item.currency_id)}
                                                </span>
                                                <Button variant="ghost" size="icon" onClick={() => handleEdit(item)} aria-label={__('general.edit')}>
                                                    <Edit className="w-4 h-4" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="text-red-500 hover:text-red-600 hover:bg-red-50" onClick={() => setPendingDelete(item.id)} aria-label={__('general.delete')}>
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete') || 'Delete?'}
                description={__('general.confirm_delete_price_item') || 'This item will be removed from the global price list.'}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}