import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { Plus, Trash2, Edit, Save, Loader2, X, Clock, Sparkles } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Package } from 'lucide-react';
import { toast } from 'sonner';

const COMPLEXITY_LABEL_KEYS: Record<string, string> = {
    low: 'general.priority_low',
    medium: 'general.priority_medium',
    high: 'general.priority_high',
};

export default function PriceList({ items, currencies, system_hourly_rate = 25 }: any) {
    const hourlyRate = system_hourly_rate || 25;
    const isLocalCurrencyRate = hourlyRate > 100;
    const hourlyRateUsd = isLocalCurrencyRate ? (hourlyRate / 50) : hourlyRate;

    const formatItemPrice = (hours: number) => {
        if (isLocalCurrencyRate) {
            const egpCost = Math.round(hours * hourlyRate);
            const usdCost = Math.round(hours * hourlyRateUsd);
            return `~${egpCost} EGP (~$${usdCost})`;
        }
        const usdCost = Math.round(hours * hourlyRateUsd);
        return `~$${usdCost}`;
    };

    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editingItem, setEditingItem] = useState<number | null>(null);
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        standalone_hours: 4,
        marginal_hours: 2,
        complexity: 'medium',
        currency_id: currencies[0]?.id || 1,
    });

    const handleEdit = (item: any) => {
        setEditingItem(item.id);
        setFormData({
            name: item.name,
            description: item.description || '',
            standalone_hours: item.standalone_hours || 4,
            marginal_hours: item.marginal_hours || 2,
            complexity: item.complexity || 'medium',
            currency_id: item.currency_id || currencies[0]?.id || 1,
        });
    };

    const handleCancel = () => {
        setEditingItem(null);
        setFormData({ name: '', description: '', standalone_hours: 4, marginal_hours: 2, complexity: 'medium', currency_id: currencies[0]?.id || 1 });
    };

    const handleSubmit = (e: any) => {
        e.preventDefault();
        setIsSubmitting(true);

        const onFinish = (success: boolean) => {
            setIsSubmitting(false);
            if (success) {
                toast.success(editingItem ? __('general.updated') : __('general.created'));
                handleCancel();
            } else {
                toast.error(__('general.error_occurred'));
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
                toast.success(__('general.deleted'));
                setPendingDelete(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred'));
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
                    <Card className="border border-slate-200 shadow-sm">
                        <CardHeader className="bg-slate-900 text-white rounded-t-lg py-4">
                            <CardTitle className="text-base font-bold flex items-center gap-2">
                                <Sparkles className="w-4 h-4 text-amber-400" />
                                {editingItem ? __('admin.price_item_edit_title') : __('admin.price_item_add_title')}
                            </CardTitle>
                            <CardDescription className="text-slate-300 text-xs">
                                {__('admin.price_item_auto_price_hint', { rate: hourlyRate })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-5">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label className="text-xs font-bold text-slate-700">{__('admin.price_item_name_label')} *</Label>
                                    <Input required value={formData.name} onChange={e => setFormData({ ...formData, name: e.target.value })} placeholder={__('admin.price_item_name_placeholder')} className="mt-1" />
                                </div>
                                <div>
                                    <Label className="text-xs font-bold text-slate-700">{__('admin.price_item_description_label')}</Label>
                                    <Input value={formData.description} onChange={e => setFormData({ ...formData, description: e.target.value })} placeholder={__('admin.price_item_description_placeholder')} className="mt-1" />
                                </div>
                                
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs font-bold text-slate-700">{__('admin.price_item_standalone_hours')}</Label>
                                        <Input required type="number" min="1" value={formData.standalone_hours} onChange={e => setFormData({ ...formData, standalone_hours: parseInt(e.target.value) || 1 })} className="mt-1" />
                                    </div>
                                    <div>
                                        <Label className="text-xs font-bold text-slate-700">{__('admin.price_item_marginal_hours')}</Label>
                                        <Input required type="number" min="1" value={formData.marginal_hours} onChange={e => setFormData({ ...formData, marginal_hours: parseInt(e.target.value) || 1 })} className="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <Label className="text-xs font-bold text-slate-700">{__('admin.price_item_complexity')}</Label>
                                    <select
                                        className="mt-1 flex h-10 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                        value={formData.complexity}
                                        onChange={e => setFormData({ ...formData, complexity: e.target.value })}
                                        required
                                    >
                                        <option value="low">{__('general.priority_low')}</option>
                                        <option value="medium">{__('general.priority_medium')}</option>
                                        <option value="high">{__('general.priority_high')}</option>
                                    </select>
                                </div>

                                <div className="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs space-y-1">
                                    <p className="font-bold text-slate-700">{__('admin.price_item_estimated_price', { rate: hourlyRate })}</p>
                                    <p className="text-slate-900 font-mono">
                                        {__('admin.price_item_standalone')}: <strong className="text-emerald-700">${formData.standalone_hours * hourlyRate}</strong> | {__('admin.price_item_in_project')}: <strong className="text-blue-700">${formData.marginal_hours * hourlyRate}</strong>
                                    </p>
                                </div>

                                <div className="flex gap-2 pt-2">
                                    <Button type="submit" disabled={isSubmitting} className="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-5 rounded-lg">
                                        {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin me-2" /> : <Save className="w-4 h-4 me-2" />}
                                        {editingItem ? __('admin.price_item_update') : __('admin.price_item_save')}
                                    </Button>
                                    {editingItem && (
                                        <Button type="button" variant="outline" onClick={handleCancel} aria-label={__('general.cancel')} title={__('general.cancel')}>
                                            <X className="w-4 h-4" />
                                        </Button>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="md:col-span-2">
                    <Card className="border border-slate-200 shadow-sm">
                        <CardHeader className="bg-white border-b py-4">
                            <CardTitle className="text-base font-bold text-slate-900">{__('admin.price_item_list_title', { count: items.length })}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-5">
                            {items.length === 0 ? (
                                <EmptyState
                                    icon={Package}
                                    title={__('admin.price_item_empty_title')}
                                    description={__('admin.price_item_empty_description')}
                                />
                            ) : (
                                <div className="space-y-3">
                                    {items.map((item: any) => (
                                        <div key={item.id} className="flex items-center justify-between gap-4 p-4 border rounded-xl hover:bg-slate-50 transition-colors bg-white">
                                            <div className="min-w-0">
                                                <h4 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                    {item.name_ar || item.name}
                                                    <span className={`text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full ${
                                                        item.complexity === 'high' ? 'bg-rose-100 text-rose-700' : item.complexity === 'low' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'
                                                    }`}>
                                                        {__(COMPLEXITY_LABEL_KEYS[item.complexity] ?? 'general.priority_medium')}
                                                    </span>
                                                </h4>
                                                {item.description && (
                                                    <p className="text-xs text-slate-500 mt-1">{item.description}</p>
                                                )}
                                                <div className="flex items-center gap-4 mt-2 text-xs text-slate-600">
                                                    <span className="flex items-center gap-1 font-medium"><Clock className="w-3.5 h-3.5 text-slate-400" /> {__('admin.price_item_standalone')}: <strong>{item.standalone_hours || 4}h</strong> ({formatItemPrice(item.standalone_hours || 4)})</span>
                                                    <span className="flex items-center gap-1 font-medium"><Clock className="w-3.5 h-3.5 text-slate-400" /> {__('admin.price_item_in_project')}: <strong>{item.marginal_hours || 2}h</strong> ({formatItemPrice(item.marginal_hours || 2)})</span>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 shrink-0">
                                                <Button variant="ghost" size="icon" onClick={() => handleEdit(item)} aria-label={__('general.edit')} title={__('general.edit')}>
                                                    <Edit className="w-4 h-4 text-slate-600" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="text-rose-500 hover:text-rose-600 hover:bg-rose-50" onClick={() => setPendingDelete(item.id)} aria-label={__('general.delete')} title={__('general.delete')}>
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
                title={__('general.confirm_delete')}
                description={__('admin.price_item_delete_confirm')}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}