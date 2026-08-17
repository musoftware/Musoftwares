import React, { useState, useMemo } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import MDEditor from '@uiw/react-md-editor';
import { 
    ArrowRight, Save, Plus, Trash2, Code, Server, 
    Sparkles, DollarSign, CheckCircle2, 
    FileText
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Currency {
    id: number;
    currency: string;
    symbol?: string;
}

interface ItemRow {
    type: 'our_work' | 'indicative_cost';
    title: string;
    description: string;
    price: number | string;
    quantity: number | string;
    external_link?: string;
    link_label?: string;
}

interface CreateProps {
    currencies: Currency[];
    defaultCurrencyId?: number;
    defaultCurrencyCode?: string;
    defaultMarkdown?: string;
}

export default function Create({ currencies, defaultCurrencyId, defaultCurrencyCode, defaultMarkdown }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        currency_id: defaultCurrencyId || (currencies[0]?.id || ''),
        currency: defaultCurrencyCode || (currencies[0]?.currency || 'USD'),
        deposit_percentage: 50,
        status: 'active',
        valid_until: '',
        scope_markdown: defaultMarkdown || '',
        notes: '',
        items: [
            {
                type: 'our_work',
                title: '',
                description: '',
                price: 0,
                quantity: 1,
                external_link: '',
                link_label: '',
            },
            {
                type: 'indicative_cost',
                title: '',
                description: '',
                price: 0,
                quantity: 1,
                external_link: '',
                link_label: '',
            }
        ] as ItemRow[],
    });

    const calculation = useMemo(() => {
        let devTotal = 0;
        let indTotal = 0;

        data.items.forEach(item => {
            const price = parseFloat(item.price as string) || 0;
            const qty = parseInt(item.quantity as string) || 1;
            const total = price * qty;

            if (item.type === 'our_work') {
                devTotal += total;
            } else {
                indTotal += total;
            }
        });

        const depositPct = parseFloat(data.deposit_percentage as any) || 50;
        const depositAmount = devTotal * (depositPct / 100);
        const remainingAmount = devTotal - depositAmount;
        const grandTotal = devTotal + indTotal;

        return {
            devTotal,
            indTotal,
            depositAmount,
            remainingAmount,
            grandTotal,
        };
    }, [data.items, data.deposit_percentage]);

    const handleAddItem = (type: 'our_work' | 'indicative_cost') => {
        const newItem: ItemRow = type === 'our_work' ? {
            type: 'our_work',
            title: '',
            description: '',
            price: 0,
            quantity: 1,
            external_link: '',
            link_label: '',
        } : {
            type: 'indicative_cost',
            title: '',
            description: '',
            price: 0,
            quantity: 1,
            external_link: '',
            link_label: '',
        };

        setData('items', [...data.items, newItem]);
    };

    const handleRemoveItem = (index: number) => {
        if (data.items.length <= 1) {
            toast.error(__('validation.required'));
            return;
        }
        const updated = data.items.filter((_, i) => i !== index);
        setData('items', updated);
    };

    const handleItemChange = (index: number, field: keyof ItemRow, value: any) => {
        const updated = [...data.items];
        updated[index] = {
            ...updated[index],
            [field]: value,
        };
        setData('items', updated);
    };

    const handleCurrencyChange = (currCode: string) => {
        const found = currencies.find(c => c.currency === currCode);
        setData(prev => ({
            ...prev,
            currency: currCode,
            currency_id: found ? found.id : '',
        }));
    };

    const insertTemplate = (templateType: string) => {
        let snippet = '';
        if (templateType === 'saas') {
            snippet = `\n\n### 🚀 SaaS Features\n- Subscription billing and gateway integration.\n- Admin dashboard for user management.\n- Continuous support and security updates.`;
        } else if (templateType === 'ecommerce') {
            snippet = `\n\n### 🛍️ Store Specifications\n- Products, inventory, and order management.\n- Seamless 1-step checkout.\n- Multiple payment gateways integration.`;
        } else if (templateType === 'payment') {
            snippet = `\n\n### 💳 Payment Milestones\n- **1st Milestone:** ${data.deposit_percentage}% upfront upon contract.\n- **2nd Milestone:** ${100 - (Number(data.deposit_percentage) || 50)}% upon final delivery.`;
        }
        setData('scope_markdown', (data.scope_markdown || '') + snippet);
        toast.success(__('common.success'));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.title.trim()) {
            toast.error(__('validation.required'));
            return;
        }

        if (data.items.length === 0) {
            toast.error(__('validation.required'));
            return;
        }

        post('/admin/marketplace/quotations', {
            onError: (errs) => {
                console.error(errs);
                toast.error(__('validation.required'));
            },
        });
    };

    const ourWorkItems = data.items.map((item, idx) => ({ ...item, originalIndex: idx })).filter(item => item.type === 'our_work');
    const indicativeItems = data.items.map((item, idx) => ({ ...item, originalIndex: idx })).filter(item => item.type === 'indicative_cost');

    return (
        <AdminSidebarLayout header={__('quotations.create_new')}>
            <Head title={`${__('quotations.create_new')} - ${__('quotations.admin_title')}`} />

            <form onSubmit={submit} className="max-w-6xl mx-auto space-y-8 pb-16">
                {/* Header Back & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm sticky top-4 z-10">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/admin/marketplace/quotations"
                            className="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                        >
                            <ArrowRight className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">{__('quotations.create_new')}</h1>
                            <p className="text-xs text-slate-500">{__('quotations.management_desc')}</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href="/admin/marketplace/quotations">
                            <Button type="button" variant="outline" className="border-slate-300">
                                {__('quotations.cancel')}
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="bg-slate-900 text-white hover:bg-slate-800 gap-2 px-6">
                            <Save className="w-4 h-4" />
                            {processing ? __('common.saving') : __('quotations.save_and_generate')}
                        </Button>
                    </div>
                </div>

                {/* Section 1: Basic Information */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <FileText className="w-4 h-4 text-indigo-600" />
                            {__('quotations.section_basic')}
                        </CardTitle>
                        <CardDescription>
                            {__('quotations.section_basic_desc')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Title */}
                            <div className="md:col-span-2 space-y-2">
                                <Label htmlFor="title" className="text-sm font-semibold text-slate-800">
                                    {__('quotations.field_title')} <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    placeholder={__('quotations.field_title_placeholder')}
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="bg-slate-50/50 text-base"
                                    required
                                />
                                {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                            </div>

                            {/* Currency */}
                            <div className="space-y-2">
                                <Label htmlFor="currency" className="text-sm font-semibold text-slate-800">
                                    {__('quotations.currency_label')} <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="currency"
                                    value={data.currency}
                                    onChange={(e) => handleCurrencyChange(e.target.value)}
                                    className="w-full h-10 px-3 text-sm bg-slate-50/50 border border-slate-200 rounded-lg text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    {currencies.map(c => (
                                        <option key={c.id} value={c.currency}>
                                            {c.currency} {c.symbol ? `(${c.symbol})` : ''}
                                        </option>
                                    ))}
                                </select>
                                {errors.currency && <p className="text-xs text-red-500">{errors.currency}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Deposit Percentage */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="deposit_percentage" className="text-sm font-semibold text-slate-800">
                                        {__('quotations.field_deposit_percentage')} <span className="text-red-500">*</span>
                                    </Label>
                                    <Badge variant="outline" className="font-mono text-emerald-600 bg-emerald-50 border-emerald-200">
                                        {data.deposit_percentage}%
                                    </Badge>
                                </div>
                                <Input
                                    id="deposit_percentage"
                                    type="number"
                                    min="1"
                                    max="100"
                                    step="1"
                                    value={data.deposit_percentage}
                                    onChange={(e) => setData('deposit_percentage', Number(e.target.value))}
                                    className="bg-slate-50/50 font-mono text-base"
                                    required
                                />
                                <p className="text-[11px] text-slate-500">
                                    {__('quotations.field_deposit_percentage_desc')}
                                </p>
                            </div>

                            {/* Validity Date */}
                            <div className="space-y-2">
                                <Label htmlFor="valid_until" className="text-sm font-semibold text-slate-800">
                                    {__('quotations.field_valid_until')}
                                </Label>
                                <Input
                                    id="valid_until"
                                    type="date"
                                    value={data.valid_until}
                                    onChange={(e) => setData('valid_until', e.target.value)}
                                    className="bg-slate-50/50 text-sm"
                                />
                            </div>

                            {/* Status */}
                            <div className="space-y-2">
                                <Label htmlFor="status" className="text-sm font-semibold text-slate-800">
                                    {__('quotations.field_status')}
                                </Label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full h-10 px-3 text-sm bg-slate-50/50 border border-slate-200 rounded-lg text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    <option value="active">{__('quotations.status_active')}</option>
                                    <option value="draft">{__('quotations.status_draft')}</option>
                                    <option value="archived">{__('quotations.status_archived')}</option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section 2: Hybrid Items & Pricing Breakdown */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <Code className="w-4 h-4 text-emerald-600" />
                                    {__('quotations.section_items')}
                                </CardTitle>
                                <CardDescription>
                                    {__('quotations.section_items_desc')}
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-6 space-y-6">
                        <Tabs defaultValue="our_work" className="w-full">
                            <TabsList className="grid grid-cols-2 bg-slate-100 p-1 mb-6">
                                <TabsTrigger value="our_work" className="gap-2 font-bold data-[state=active]:bg-white data-[state=active]:text-slate-900">
                                    <Code className="w-4 h-4 text-emerald-600" />
                                    {__('quotations.tab_our_work')} ({ourWorkItems.length})
                                </TabsTrigger>
                                <TabsTrigger value="indicative_cost" className="gap-2 font-bold data-[state=active]:bg-white data-[state=active]:text-slate-900">
                                    <Server className="w-4 h-4 text-amber-600" />
                                    {__('quotations.tab_indicative_cost')} ({indicativeItems.length})
                                </TabsTrigger>
                            </TabsList>

                            {/* Tab 1: Our Work */}
                            <TabsContent value="our_work" className="space-y-4">
                                <div className="flex items-center justify-between pb-2">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        {__('quotations.tab_our_work')}
                                    </span>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleAddItem('our_work')}
                                        className="gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        {__('quotations.add_our_work_item')}
                                    </Button>
                                </div>

                                {ourWorkItems.map((item) => (
                                    <div key={item.originalIndex} className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 space-y-3 relative group">
                                        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                            <div className="md:col-span-6 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_title')}</Label>
                                                <Input
                                                    placeholder={__('quotations.item_title_placeholder')}
                                                    value={item.title}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'title', e.target.value)}
                                                    className="bg-white text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_price')} ({data.currency})</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="any"
                                                    value={item.price}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'price', e.target.value)}
                                                    className="bg-white font-mono text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_qty')}</Label>
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    value={item.quantity}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'quantity', e.target.value)}
                                                    className="bg-white font-mono text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_total')}</Label>
                                                <div className="h-10 px-3 flex items-center justify-between bg-slate-100 rounded-md font-mono text-sm font-bold text-slate-900">
                                                    <span>{((parseFloat(item.price as string) || 0) * (parseInt(item.quantity as string) || 1))}</span>
                                                    <span className="text-xs text-slate-500">{data.currency}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Input
                                                placeholder={__('quotations.item_description')}
                                                value={item.description || ''}
                                                onChange={(e) => handleItemChange(item.originalIndex, 'description', e.target.value)}
                                                className="bg-white text-xs"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => handleRemoveItem(item.originalIndex)}
                                                className="text-slate-400 hover:text-red-600 hover:bg-red-50 shrink-0 h-9 px-2"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </TabsContent>

                            {/* Tab 2: Indicative External Costs */}
                            <TabsContent value="indicative_cost" className="space-y-4">
                                <div className="flex items-center justify-between pb-2">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        {__('quotations.tab_indicative_cost')}
                                    </span>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleAddItem('indicative_cost')}
                                        className="gap-1.5 text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 border-amber-200"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        {__('quotations.add_indicative_item')}
                                    </Button>
                                </div>

                                {indicativeItems.map((item) => (
                                    <div key={item.originalIndex} className="p-4 rounded-xl border border-amber-200/80 bg-amber-50/20 space-y-3 relative group">
                                        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                            <div className="md:col-span-6 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_title')}</Label>
                                                <Input
                                                    placeholder={__('quotations.item_title_placeholder')}
                                                    value={item.title}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'title', e.target.value)}
                                                    className="bg-white text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_price')} ({data.currency})</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="any"
                                                    value={item.price}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'price', e.target.value)}
                                                    className="bg-white font-mono text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_qty')}</Label>
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    value={item.quantity}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'quantity', e.target.value)}
                                                    className="bg-white font-mono text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">{__('quotations.item_total')}</Label>
                                                <div className="h-10 px-3 flex items-center justify-between bg-amber-100/60 rounded-md font-mono text-sm font-bold text-slate-900">
                                                    <span>{((parseFloat(item.price as string) || 0) * (parseInt(item.quantity as string) || 1))}</span>
                                                    <span className="text-xs text-slate-500">{data.currency}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                            <div className="md:col-span-6">
                                                <Input
                                                    placeholder={__('quotations.external_link_placeholder')}
                                                    value={item.external_link || ''}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'external_link', e.target.value)}
                                                    className="bg-white text-xs"
                                                />
                                            </div>
                                            <div className="md:col-span-5">
                                                <Input
                                                    placeholder={__('quotations.link_label_placeholder')}
                                                    value={item.link_label || ''}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'link_label', e.target.value)}
                                                    className="bg-white text-xs"
                                                />
                                            </div>
                                            <div className="md:col-span-1 flex justify-end">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => handleRemoveItem(item.originalIndex)}
                                                    className="text-slate-400 hover:text-red-600 hover:bg-red-50 shrink-0 h-9 px-2"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </TabsContent>
                        </Tabs>

                        {/* Live Financial Summary Box */}
                        <div className="p-6 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white shadow-md">
                            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                                <DollarSign className="w-4 h-4 text-emerald-400" />
                                {__('quotations.calc_summary_title')}
                            </h4>

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-slate-700/60">
                                <div className="space-y-1">
                                    <span className="text-xs text-slate-400">{__('quotations.calc_dev_total')}</span>
                                    <div className="text-xl font-bold font-mono text-white">
                                        {calculation.devTotal.toFixed(2)} <span className="text-xs font-normal text-slate-400">{data.currency}</span>
                                    </div>
                                </div>

                                <div className="space-y-1 pt-2 md:pt-0 md:pr-4">
                                    <span className="text-xs text-emerald-400 font-semibold flex items-center gap-1">
                                        <CheckCircle2 className="w-3.5 h-3.5" />
                                        {__('quotations.calc_deposit', { pct: data.deposit_percentage })}
                                    </span>
                                    <div className="text-2xl font-extrabold font-mono text-emerald-400">
                                        {calculation.depositAmount.toFixed(2)} <span className="text-xs font-normal text-emerald-200">{data.currency}</span>
                                    </div>
                                    <span className="text-[10px] text-slate-400 block">{__('quotations.calc_deposit_sub')}</span>
                                </div>

                                <div className="space-y-1 pt-2 md:pt-0 md:pr-4">
                                    <span className="text-xs text-amber-300 font-medium">{__('quotations.calc_indicative_total')}</span>
                                    <div className="text-xl font-bold font-mono text-amber-300">
                                        {calculation.indTotal.toFixed(2)} <span className="text-xs font-normal text-amber-200">{data.currency}</span>
                                    </div>
                                    <span className="text-[10px] text-slate-400 block">{__('quotations.calc_indicative_sub')}</span>
                                </div>

                                <div className="space-y-1 pt-2 md:pt-0 md:pr-4">
                                    <span className="text-xs text-slate-400">{__('quotations.calc_grand_total')}</span>
                                    <div className="text-xl font-bold font-mono text-slate-200">
                                        {calculation.grandTotal.toFixed(2)} <span className="text-xs font-normal text-slate-400">{data.currency}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section 3: Rich Markdown Scope & Deliverables */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-indigo-600" />
                                    {__('quotations.section_scope')}
                                </CardTitle>
                                <CardDescription>
                                    {__('quotations.section_scope_desc')}
                                </CardDescription>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => insertTemplate('saas')}
                                    className="text-xs gap-1 text-slate-700 bg-slate-50 hover:bg-slate-100"
                                >
                                    <Sparkles className="w-3 h-3 text-indigo-600" />
                                    {__('quotations.template_saas')}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => insertTemplate('ecommerce')}
                                    className="text-xs gap-1 text-slate-700 bg-slate-50 hover:bg-slate-100"
                                >
                                    <Sparkles className="w-3 h-3 text-emerald-600" />
                                    {__('quotations.template_ecommerce')}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => insertTemplate('payment')}
                                    className="text-xs gap-1 text-slate-700 bg-slate-50 hover:bg-slate-100"
                                >
                                    <Sparkles className="w-3 h-3 text-amber-600" />
                                    {__('quotations.template_payment')}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-6 space-y-4" data-color-mode="light">
                        <MDEditor
                            value={data.scope_markdown}
                            onChange={(val) => setData('scope_markdown', val || '')}
                            height={380}
                            preview="edit"
                            className="rounded-xl overflow-hidden border border-slate-200"
                        />
                        {errors.scope_markdown && <p className="text-xs text-red-500">{errors.scope_markdown}</p>}
                    </CardContent>
                </Card>

                {/* Section 4: Internal Notes */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <FileText className="w-4 h-4 text-slate-600" />
                            {__('quotations.field_notes')}
                        </CardTitle>
                        <CardDescription>
                            {__('quotations.field_notes_placeholder')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <Textarea
                            rows={3}
                            placeholder={__('quotations.field_notes_placeholder')}
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            className="bg-slate-50/50 text-sm"
                        />
                    </CardContent>
                </Card>

                {/* Sticky Submit Bar */}
                <div className="flex items-center justify-end gap-3 pt-4">
                    <Link href="/admin/marketplace/quotations">
                        <Button type="button" variant="outline" className="border-slate-300">
                            {__('quotations.cancel')}
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing} className="bg-slate-900 text-white hover:bg-slate-800 gap-2 px-8">
                        <Save className="w-4 h-4" />
                        {processing ? __('common.saving') : __('quotations.save_and_generate')}
                    </Button>
                </div>
            </form>
        </AdminSidebarLayout>
    );
}
