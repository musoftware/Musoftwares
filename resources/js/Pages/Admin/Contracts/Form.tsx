import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import MDEditor from '@uiw/react-md-editor';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { Loader2, Plus, Trash2, Save, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';

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
            toast.error(__('general.title_and_price_required'));
            return;
        }

        router.post('/admin/contract-price-items', {
            name: itemData.item,
            description: itemData.description,
            default_price: itemData.price,
            currency_id: itemData.currency_id || formData.currency_id,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const created = (page?.props as any)?.flash?.item ?? null;
                if (created) setGlobalItems([...globalItems, created]);
                toast.success(__('general.added_to_global_price_list'));
            },
            onError: () => toast.error(__('general.failed_save_global_item')),
        });
    };

    return (
        <AdminSidebarLayout 
            title={contract ? __('general.edit_contract') : __('general.create_contract')} 
            header={contract ? __('general.edit_contract') : __('admin.contract_create_new')}
        >
            <div className="mb-6">
                <Link href="/admin/contracts" className="inline-flex items-center gap-1 text-slate-900 hover:underline">
                    <ArrowLeft className="h-4 w-4 rtl:rotate-180" />
                    {__('general.back_to_contracts')}
                </Link>
            </div>

            <form onSubmit={onSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.general_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>{__('admin.contract_title_project_name')}</Label>
                                <Input 
                                    value={formData.project_name} 
                                    onChange={e => setFormData('project_name', e.target.value)}
                                    placeholder={__('admin.contract_title_placeholder')}
                                    required
                                />
                                {errors.project_name && <div className="mt-1 text-xs text-red-500">{errors.project_name}</div>}
                            </div>
                            <div data-color-mode="light">
                                <Label>{__('general.description')}</Label>
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
                                    <Label>{__('general.total_amount')}</Label>
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
                                    <Label>{__('general.currency')}</Label>
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
                                    <Label>{__('admin.contract_duration_weeks')}</Label>
                                    <Input 
                                        type="number" 
                                        min="1"
                                        value={formData.duration} 
                                        onChange={e => setFormData('duration', e.target.value)}
                                        placeholder={__('admin.contract_duration_placeholder')}
                                    />
                                    {errors.duration && <div className="mt-1 text-xs text-red-500">{errors.duration}</div>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>{__('general.pricing_items')}</CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={addPricing}>
                                <Plus className="w-4 h-4 me-1" /> {__('admin.contract_add_custom_item')}
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {formData.content.pricing_items.map((item: any, idx: number) => (
                                <div key={idx} className="flex items-start gap-4 p-4 border rounded-md bg-slate-50">
                                    <div className="flex-1 space-y-3">
                                        <div className="grid grid-cols-3 gap-3">
                                            <div className="col-span-2">
                                                <Label className="text-xs">{__('general.item_name')}</Label>
                                                <Input 
                                                    placeholder={__('admin.contract_item_name_placeholder')} 
                                                    value={item.item} 
                                                    onChange={e => updatePricing(idx, 'item', e.target.value)} 
                                                />
                                            </div>
                                            <div className="flex gap-2">
                                            <div className="flex-1">
                                                <Label className="text-xs">{__('general.price')}</Label>
                                                <Input 
                                                    type="number" 
                                                    placeholder="0" 
                                                    value={item.price} 
                                                    onChange={e => updatePricing(idx, 'price', e.target.value)} 
                                                />
                                            </div>
                                            <div className="w-[100px]">
                                                <Label className="text-xs">{__('general.currency')}</Label>
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
                                            <Label className="text-xs">{__('general.description')}</Label>
                                            <Input 
                                                placeholder={__('admin.contract_item_description_placeholder')} 
                                                value={item.description} 
                                                onChange={e => updatePricing(idx, 'description', e.target.value)} 
                                            />
                                        </div>
                                    </div>
                                    <div className="flex flex-col gap-2 pt-5">
                                        <Button type="button" variant="ghost" size="icon" onClick={() => removePricing(idx)} className="text-red-500 hover:bg-red-100" aria-label={__('admin.contract_remove_item')} title={__('admin.contract_remove_item')}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" onClick={() => saveAsGlobalItem(idx)} title={__('admin.contract_save_to_global_price_list')} aria-label={__('admin.contract_save_to_global_price_list')}>
                                            <Save className="w-3 h-3" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                            {formData.content.pricing_items.length === 0 && (
                                <div className="text-center p-6 border-2 border-dashed rounded-md text-slate-500">
                                    {__('admin.contract_no_items_yet')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('admin.contract_global_price_list')}</CardTitle>
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
                                                    {isConverted && <div className="text-[10px] text-slate-400 font-normal leading-tight">({__('general.converted')})</div>}
                                                </div>
                                            </div>
                                            <div className="text-xs text-slate-500 line-clamp-2">{item.description}</div>
                                            <Button type="button" variant="secondary" size="sm" className="w-full mt-2" onClick={() => addFromGlobalItem(item)}>
                                                {__('admin.contract_add_to_contract')}
                                            </Button>
                                        </div>
                                    );
                                })}
                                {globalItems.length === 0 && (
                                    <div className="text-sm text-slate-500">{__('admin.contract_global_price_list_empty')}</div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.actions')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>{__('general.status')}</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                    value={formData.status}
                                    onChange={e => setFormData('status', e.target.value)}
                                >
                                    <option value="draft">{__('general.status_draft')}</option>
                                    <option value="sent">{__('general.status_sent')}</option>
                                    <option value="signed">{__('general.signed')}</option>
                                    <option value="active">{__('general.status_active')}</option>
                                    <option value="completed">{__('general.status_completed')}</option>
                                </select>
                                {errors.status && <div className="mt-1 text-xs text-red-500">{errors.status}</div>}
                            </div>
                        </CardContent>
                        <CardFooter>
                            <Button type="submit" className="w-full" disabled={isLoading}>
                                {isLoading ? <Loader2 className="w-4 h-4 animate-spin me-2" /> : null}
                                {contract ? __('admin.contract_update_version') : __('admin.contract_save_contract')}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </AdminSidebarLayout>
    );
}
