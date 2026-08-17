import React, { useState, useMemo } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { 
    ArrowRight, Save, Plus, Trash2, DollarSign, Server, 
    Code, Sparkles, CheckCircle2, FileText, ExternalLink
} from 'lucide-react';
import { toast } from 'sonner';

interface Currency {
    id: number;
    currency: string;
    name: string;
    symbol?: string;
}

interface ItemRow {
    id?: number;
    type: 'our_work' | 'indicative_cost';
    title: string;
    description: string;
    price: number | string;
    quantity: number | string;
    external_link?: string;
    link_label?: string;
}

interface Quotation {
    id: number;
    uuid: string;
    quotation_number: string;
    title: string;
    currency: string;
    currency_id?: number;
    deposit_percentage: number;
    valid_until?: string;
    status: string;
    scope_markdown?: string;
    notes?: string;
    items: ItemRow[];
}

interface EditProps {
    quotation: Quotation;
    currencies: Currency[];
}

export default function Edit({ quotation, currencies }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        title: quotation.title || '',
        currency: quotation.currency || 'USD',
        currency_id: quotation.currency_id || '',
        deposit_percentage: quotation.deposit_percentage || 50,
        valid_until: quotation.valid_until ? quotation.valid_until.split('T')[0] : '',
        status: quotation.status || 'active',
        scope_markdown: quotation.scope_markdown || '',
        notes: quotation.notes || '',
        items: (quotation.items && quotation.items.length > 0) ? quotation.items.map(it => ({
            type: it.type || 'our_work',
            title: it.title || '',
            description: it.description || '',
            price: it.price || 0,
            quantity: it.quantity || 1,
            external_link: it.external_link || '',
            link_label: it.link_label || '',
        })) : [] as ItemRow[],
    });

    const calculation = useMemo(() => {
        let devTotal = 0;
        let indTotal = 0;

        data.items.forEach(item => {
            const price = parseFloat(item.price as string) || 0;
            const qty = parseInt(item.quantity as string) || 1;
            const total = price * qty;

            if (item.type === 'indicative_cost') {
                indTotal += total;
            } else {
                devTotal += total;
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
            link_label: 'رابط المزود الخارجي',
        };

        setData('items', [...data.items, newItem]);
    };

    const handleRemoveItem = (index: number) => {
        if (data.items.length <= 1) {
            toast.error('يجب أن يحتوي العرض على بند واحد على الأقل.');
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

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.title.trim()) {
            toast.error('يرجى إدخال عنوان عرض السعر');
            return;
        }

        put(`/admin/marketplace/quotations/${quotation.id}`, {
            onSuccess: () => toast.success('تم تحديث عرض السعر بنجاح'),
            onError: () => toast.error('يرجى مراجعة الحقول المدخلة والتأكد من صحتها.'),
        });
    };

    const ourWorkItems = data.items.map((item, idx) => ({ ...item, originalIndex: idx })).filter(item => item.type === 'our_work');
    const indicativeItems = data.items.map((item, idx) => ({ ...item, originalIndex: idx })).filter(item => item.type === 'indicative_cost');

    return (
        <AdminSidebarLayout header={`تعديل عرض السعر (${quotation.quotation_number})`}>
            <Head title={`تعديل عرض السعر ${quotation.quotation_number} - Admin`} />

            <form onSubmit={submit} className="max-w-6xl mx-auto space-y-8 pb-16">
                {/* Header Back & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm sticky top-4 z-10">
                    <div className="flex items-center gap-3">
                        <Link
                            href={`/admin/marketplace/quotations/${quotation.id}`}
                            className="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                        >
                            <ArrowRight className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold text-slate-900">تعديل عرض السعر</h1>
                                <Badge variant="outline" className="font-mono text-xs font-bold">{quotation.quotation_number}</Badge>
                            </div>
                            <p className="text-xs text-slate-500">تعديل البنود ونطاق العمل والأسعار</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href={`/admin/marketplace/quotations/${quotation.id}`}>
                            <Button type="button" variant="outline" className="border-slate-300">
                                إلغاء
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="bg-slate-900 text-white hover:bg-slate-800 gap-2 px-6">
                            <Save className="w-4 h-4" />
                            {processing ? 'جاري الحفظ...' : 'حفظ التعديلات'}
                        </Button>
                    </div>
                </div>

                {/* Section 1: Basic Information */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <FileText className="w-4 h-4 text-indigo-600" />
                            المعلومات الأساسية والعملة
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Title */}
                            <div className="md:col-span-2 space-y-2">
                                <Label htmlFor="title" className="text-sm font-semibold text-slate-800">
                                    عنوان العرض <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
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
                                    العملة المعتمدة <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="currency"
                                    value={data.currency}
                                    onChange={(e) => handleCurrencyChange(e.target.value)}
                                    className="w-full h-10 px-3 text-sm bg-slate-50/50 border border-slate-200 rounded-lg text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    {currencies.map(c => (
                                        <option key={c.id} value={c.currency}>
                                            {c.currency} - {c.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Deposit Percentage */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="deposit_percentage" className="text-sm font-semibold text-slate-800">
                                        نسبة الدفعة المقدمة (%) <span className="text-red-500">*</span>
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
                            </div>

                            {/* Validity Date */}
                            <div className="space-y-2">
                                <Label htmlFor="valid_until" className="text-sm font-semibold text-slate-800">
                                    صلاحية العرض حتى
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
                                    حالة العرض
                                </Label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full h-10 px-3 text-sm bg-slate-50/50 border border-slate-200 rounded-lg text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    <option value="active">نشط (Active)</option>
                                    <option value="draft">مسودة (Draft)</option>
                                    <option value="archived">مؤرشف (Archived)</option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section 2: Items */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <Code className="w-4 h-4 text-emerald-600" />
                            جدول البنود والتسعير الهجين
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-6">
                        <Tabs defaultValue="our_work" className="w-full">
                            <TabsList className="grid grid-cols-2 bg-slate-100 p-1 mb-6">
                                <TabsTrigger value="our_work" className="gap-2 font-bold data-[state=active]:bg-white data-[state=active]:text-slate-900">
                                    <Code className="w-4 h-4 text-emerald-600" />
                                    أعمال التطوير والبرمجة ({ourWorkItems.length})
                                </TabsTrigger>
                                <TabsTrigger value="indicative_cost" className="gap-2 font-bold data-[state=active]:bg-white data-[state=active]:text-slate-900">
                                    <Server className="w-4 h-4 text-amber-600" />
                                    التكاليف الاسترشادية الخارجية ({indicativeItems.length})
                                </TabsTrigger>
                            </TabsList>

                            {/* Tab 1: Our Work */}
                            <TabsContent value="our_work" className="space-y-4">
                                <div className="flex items-center justify-between pb-2">
                                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        بنود أعمال وبرمجة الفريق (تحسب منها نسبة الـ {data.deposit_percentage}%)
                                    </span>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleAddItem('our_work')}
                                        className="gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        إضافة بند عمل جديد
                                    </Button>
                                </div>

                                {ourWorkItems.map((item) => (
                                    <div key={item.originalIndex} className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 space-y-3">
                                        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                            <div className="md:col-span-6 space-y-1">
                                                <Label className="text-xs text-slate-600">اسم البند / الميزة</Label>
                                                <Input
                                                    value={item.title}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'title', e.target.value)}
                                                    className="bg-white text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">السعر ({data.currency})</Label>
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
                                                <Label className="text-xs text-slate-600">الكمية</Label>
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
                                                <Label className="text-xs text-slate-600">الإجمالي</Label>
                                                <div className="h-10 px-3 flex items-center justify-between bg-slate-100 rounded-md font-mono text-sm font-bold text-slate-900">
                                                    <span>{((parseFloat(item.price as string) || 0) * (parseInt(item.quantity as string) || 1))}</span>
                                                    <span className="text-xs text-slate-500">{data.currency}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Input
                                                placeholder="وصف تفصيلي للبند ومخرجاته..."
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

                            {/* Tab 2: Indicative Costs */}
                            <TabsContent value="indicative_cost" className="space-y-4">
                                <div className="flex items-center justify-between pb-2">
                                    <div className="space-y-0.5">
                                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider block">
                                            التكاليف الاسترشادية والخدمات الخارجية
                                        </span>
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleAddItem('indicative_cost')}
                                        className="gap-1.5 text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 border-amber-200"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        إضافة بند استرشادي
                                    </Button>
                                </div>

                                {indicativeItems.map((item) => (
                                    <div key={item.originalIndex} className="p-4 rounded-xl border border-amber-200/80 bg-amber-50/20 space-y-3">
                                        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                            <div className="md:col-span-6 space-y-1">
                                                <Label className="text-xs text-slate-600">اسم الخدمة الاسترشادية</Label>
                                                <Input
                                                    value={item.title}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'title', e.target.value)}
                                                    className="bg-white text-sm"
                                                    required
                                                />
                                            </div>

                                            <div className="md:col-span-2 space-y-1">
                                                <Label className="text-xs text-slate-600">السعر التقديري ({data.currency})</Label>
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
                                                <Label className="text-xs text-slate-600">الكمية / الأشهر</Label>
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
                                                <Label className="text-xs text-slate-600">الإجمالي التقديري</Label>
                                                <div className="h-10 px-3 flex items-center justify-between bg-amber-100/60 rounded-md font-mono text-sm font-bold text-amber-900">
                                                    <span>{((parseFloat(item.price as string) || 0) * (parseInt(item.quantity as string) || 1))}</span>
                                                    <span className="text-xs text-amber-700">{data.currency}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                            <div className="md:col-span-6">
                                                <Input
                                                    placeholder="رابط المزود الخارجي (https://...)"
                                                    value={item.external_link || ''}
                                                    onChange={(e) => handleItemChange(item.originalIndex, 'external_link', e.target.value)}
                                                    className="bg-white text-xs font-mono"
                                                />
                                            </div>
                                            <div className="md:col-span-5">
                                                <Input
                                                    placeholder="نص الزر للعميل"
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
                                                    className="text-slate-400 hover:text-red-600 hover:bg-red-50 h-9 px-2"
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
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-slate-700/60">
                                <div className="space-y-1">
                                    <span className="text-xs text-slate-400">إجمالي أعمالنا البرمجية</span>
                                    <div className="text-xl font-bold font-mono text-white">
                                        {calculation.devTotal.toFixed(2)} <span className="text-xs font-normal text-slate-400">{data.currency}</span>
                                    </div>
                                </div>

                                <div className="space-y-1 pt-2 md:pt-0 md:pr-4">
                                    <span className="text-xs text-emerald-400 font-semibold flex items-center gap-1">
                                        <CheckCircle2 className="w-3.5 h-3.5" />
                                        الدفعة المقدمة ({data.deposit_percentage}%)
                                    </span>
                                    <div className="text-2xl font-extrabold font-mono text-emerald-400">
                                        {calculation.depositAmount.toFixed(2)} <span className="text-xs font-normal text-emerald-200">{data.currency}</span>
                                    </div>
                                </div>

                                <div className="space-y-1 pt-2 md:pt-0 md:pr-4">
                                    <span className="text-xs text-amber-300 font-medium">تكاليف استرشادية خارجية</span>
                                    <div className="text-xl font-bold font-mono text-amber-300">
                                        {calculation.indTotal.toFixed(2)} <span className="text-xs font-normal text-amber-200">{data.currency}</span>
                                    </div>
                                </div>

                                <div className="space-y-1 pt-2 md:pt-0 md:pr-4">
                                    <span className="text-xs text-slate-400">الإجمالي الشامل</span>
                                    <div className="text-xl font-bold font-mono text-slate-200">
                                        {calculation.grandTotal.toFixed(2)} <span className="text-xs font-normal text-slate-400">{data.currency}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section 3: Markdown */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <FileText className="w-4 h-4 text-indigo-600" />
                            نطاق العمل، الشروط، وخريطة الطريق (Markdown)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6" data-color-mode="light">
                        <div className="rounded-xl overflow-hidden border border-slate-200">
                            <MDEditor
                                value={data.scope_markdown}
                                onChange={(val) => setData('scope_markdown', val || '')}
                                height={380}
                                preview="live"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Section 4: Notes */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-sm font-bold text-slate-800">
                            ملاحظات داخلية خاصة بالإدارة
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <Textarea
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            className="bg-slate-50/50 text-sm"
                        />
                    </CardContent>
                </Card>

                {/* Bottom Submit Bar */}
                <div className="flex justify-end gap-3 pt-4">
                    <Link href={`/admin/marketplace/quotations/${quotation.id}`}>
                        <Button type="button" variant="outline" className="border-slate-300">
                            إلغاء
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing} className="bg-slate-900 text-white hover:bg-slate-800 gap-2 px-8">
                        <Save className="w-4 h-4" />
                        {processing ? 'جاري الحفظ...' : 'تحديث العرض'}
                    </Button>
                </div>
            </form>
        </AdminSidebarLayout>
    );
}
