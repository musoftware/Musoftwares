import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
    CardFooter
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Loader2,
    ArrowLeft,
    Receipt,
    DollarSign,
    FileText,
    Calendar,
    Building2,
    User,
    CreditCard,
    Tag,
    Paperclip,
    RotateCw,
} from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function CostsCreate() {
    const { users, projects, currencies, businessCurrency, paymentMethods, categories } = usePage<any>().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        currency_id: businessCurrency?.id || '',
        reason: '',
        created_at: new Date().toISOString().split('T')[0],
        user_id: '',
        project_id: '',
        category: '',
        category_text: '',
        payment_method: '',
        tax_amount: '0',
        tax_rate: '0',
        is_billable: false,
        notes: '',
        attachment: null as File | null,
        make_recurring: false,
        recurring: 'month',
        recurring_times: '1',
        recurring_title: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.costs.store'), {
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <AdminSidebarLayout
            title={__('general.add_cost')}
            header={__('general.add_cost')}
        >
            <Head title={__('general.add_cost')} />
            <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('admin.costs.index'))}
                        className="me-4 hover:bg-slate-100"
                    >
                        <ArrowLeft className="w-4 h-4 me-2" />
                        {__('general.back')}
                    </Button>
                </div>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-4 border-b border-slate-100 bg-slate-50/50 rounded-t-xl">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-rose-100 text-rose-700 rounded-xl shadow-sm">
                                <Receipt className="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle className="text-xl font-bold text-slate-900">
                                    {__('general.add_direct_cost')}
                                </CardTitle>
                                <CardDescription className="text-sm mt-1">
                                    {__('general.add_direct_cost_description')}
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <form onSubmit={handleSubmit}>
                        <CardContent className="p-6 space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <DollarSign className="w-4 h-4 text-slate-400" />
                                        {__('general.amount')} <span className="text-rose-600">*</span>
                                    </Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        placeholder="0.00"
                                        className="h-11 font-mono text-base bg-white"
                                        required
                                    />
                                    {errors.amount && <p className="text-xs text-rose-600 mt-1">{errors.amount}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <DollarSign className="w-4 h-4 text-slate-400" />
                                        {__('general.currency')} <span className="text-rose-600">*</span>
                                    </Label>
                                    <PremiumCombobox
                                        value={data.currency_id ? String(data.currency_id) : ''}
                                        onChange={(val) => setData('currency_id', val as string)}
                                        options={currencies.map((c: any) => ({
                                            value: String(c.id),
                                            label: `${c.currency} (${c.symbol})`
                                        }))}
                                        placeholder={__('general.select_currency')}
                                    />
                                    {errors.currency_id && <p className="text-xs text-rose-600 mt-1">{errors.currency_id}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <FileText className="w-4 h-4 text-slate-400" />
                                        {__('general.reason_description')} <span className="text-rose-600">*</span>
                                    </Label>
                                    <Input
                                        value={data.reason}
                                        onChange={(e) => setData('reason', e.target.value)}
                                        placeholder={__('general.cost_reason_placeholder')}
                                        className="h-11 bg-white"
                                        maxLength={500}
                                        required
                                    />
                                    {errors.reason && <p className="text-xs text-rose-600 mt-1">{errors.reason}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <Tag className="w-4 h-4 text-slate-400" />
                                        {__('general.category')}
                                    </Label>
                                    <PremiumCombobox
                                        value={data.category}
                                        onChange={(val) => setData('category', val as string)}
                                        options={[
                                            ...(categories || []),
                                            { value: '__new__', label: __('general.new_category') },
                                        ]}
                                        placeholder={__('general.select_category')}
                                    />
                                    {data.category === '__new__' && (
                                        <Input
                                            placeholder={__('general.new_category_name')}
                                            className="h-10 mt-2"
                                            value={data.category_text}
                                            onChange={(e) => setData('category_text', e.target.value)}
                                            maxLength={80}
                                        />
                                    )}
                                    {errors.category && <p className="text-xs text-rose-600 mt-1">{errors.category}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <CreditCard className="w-4 h-4 text-slate-400" />
                                        {__('general.payment_method')}
                                    </Label>
                                    <PremiumCombobox
                                        value={data.payment_method}
                                        onChange={(val) => setData('payment_method', val as string)}
                                        options={paymentMethods || []}
                                        placeholder={__('general.select_payment_method')}
                                    />
                                    {errors.payment_method && <p className="text-xs text-rose-600 mt-1">{errors.payment_method}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        {__('general.tax_rate_percent')}
                                    </Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={data.tax_rate}
                                        onChange={(e) => setData('tax_rate', e.target.value)}
                                        className="h-11 bg-white"
                                    />
                                    {errors.tax_rate && <p className="text-xs text-rose-600 mt-1">{errors.tax_rate}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        {__('general.tax_amount')}
                                    </Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.tax_amount}
                                        onChange={(e) => setData('tax_amount', e.target.value)}
                                        className="h-11 bg-white"
                                    />
                                    {errors.tax_amount && <p className="text-xs text-rose-600 mt-1">{errors.tax_amount}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2 lg:col-span-1">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <Calendar className="w-4 h-4 text-slate-400" />
                                        {__('general.date')}
                                    </Label>
                                    <Input
                                        type="date"
                                        value={data.created_at}
                                        onChange={(e) => setData('created_at', e.target.value)}
                                        className="h-11 bg-white"
                                    />
                                    {errors.created_at && <p className="text-xs text-rose-600 mt-1">{errors.created_at}</p>}
                                </div>

                                <div className="md:col-span-2 flex items-center gap-2">
                                    <Checkbox
                                        id="is_billable"
                                        checked={data.is_billable}
                                        onCheckedChange={(v) => setData('is_billable', !!v)}
                                    />
                                    <Label htmlFor="is_billable" className="text-sm cursor-pointer">
                                        {__('general.mark_as_billable')}
                                    </Label>
                                </div>
                            </div>

                            <div className="md:col-span-2 border-t border-slate-100 pt-6">
                                <h3 className="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                                    <Building2 className="w-4 h-4 text-slate-400" />
                                    {__('general.association_optional')}
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 text-sm flex items-center gap-2">
                                            <User className="w-4 h-4 text-slate-400" />
                                            {__('general.client_user')}
                                        </Label>
                                        <PremiumCombobox
                                            value={data.user_id ? String(data.user_id) : ''}
                                            onChange={(val) => setData('user_id', val as string)}
                                            options={users.map((u: any) => ({ value: String(u.id), label: u.name }))}
                                            placeholder={__('general.search_client')}
                                        />
                                        {errors.user_id && <p className="text-xs text-rose-600 mt-1">{errors.user_id}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-slate-700 text-sm flex items-center gap-2">
                                            <Building2 className="w-4 h-4 text-slate-400" />
                                            {__('general.project')}
                                        </Label>
                                        <PremiumCombobox
                                            value={data.project_id ? String(data.project_id) : ''}
                                            onChange={(val) => setData('project_id', val as string)}
                                            options={projects.map((p: any) => ({ value: String(p.id), label: p.name }))}
                                            placeholder={__('general.search_project')}
                                        />
                                        {errors.project_id && <p className="text-xs text-rose-600 mt-1">{errors.project_id}</p>}
                                    </div>
                                </div>
                            </div>

                            <div className="md:col-span-2 border-t border-slate-100 pt-6">
                                <h3 className="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                                    <Paperclip className="w-4 h-4 text-slate-400" />
                                    {__('general.notes_and_attachment')}
                                </h3>
                                <div className="grid grid-cols-1 gap-6">
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 text-sm">{__('general.notes')}</Label>
                                        <Textarea
                                            value={data.notes}
                                            onChange={(e: any) => setData('notes', e.target.value)}
                                            placeholder={__('general.notes_placeholder')}
                                            className="min-h-[100px] bg-white"
                                            maxLength={5000}
                                        />
                                        {errors.notes && <p className="text-xs text-rose-600 mt-1">{errors.notes}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 text-sm">{__('general.attachment_optional')}</Label>
                                        <Input
                                            type="file"
                                            accept=".jpg,.jpeg,.png,.pdf,.webp"
                                            onChange={(e: any) => setData('attachment', e.target.files?.[0] ?? null)}
                                            className="h-11 bg-white"
                                        />
                                        <p className="text-xs text-slate-500">{__('general.attachment_help')}</p>
                                        {errors.attachment && <p className="text-xs text-rose-600 mt-1">{errors.attachment}</p>}
                                    </div>
                                </div>
                            </div>

                            <div className="md:col-span-2 border-t border-slate-100 pt-6">
                                <h3 className="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                                    <RotateCw className="w-4 h-4 text-slate-400" />
                                    {__('general.recurring_optional')}
                                </h3>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <Checkbox
                                        id="make_recurring"
                                        checked={data.make_recurring}
                                        onCheckedChange={(v) => setData('make_recurring', !!v)}
                                    />
                                    <span className="text-sm">{__('general.schedule_as_recurring')}</span>
                                </label>
                                {data.make_recurring && (
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                        <div className="space-y-2">
                                            <Label className="text-slate-700 text-sm">{__('general.recurring_title')}</Label>
                                            <Input
                                                value={data.recurring_title}
                                                onChange={(e) => setData('recurring_title', e.target.value)}
                                                placeholder={__('general.recurring_title_placeholder')}
                                                className="h-10 bg-white"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label className="text-slate-700 text-sm">{__('general.recurring_frequency')}</Label>
                                            <PremiumCombobox
                                                value={data.recurring}
                                                onChange={(val) => setData('recurring', val as string)}
                                                options={[
                                                    { value: 'day', label: __('general.daily') },
                                                    { value: 'week', label: __('general.weekly') },
                                                    { value: 'month', label: __('general.monthly') },
                                                    { value: 'year', label: __('general.yearly') },
                                                ]}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label className="text-slate-700 text-sm">{__('general.every_n')}</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={data.recurring_times}
                                                onChange={(e) => setData('recurring_times', e.target.value)}
                                                className="h-10 bg-white"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                        <CardFooter className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 rounded-b-xl">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.visit(route('admin.costs.index'))}
                            >
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing} className="bg-slate-900 hover:bg-slate-900 text-white min-w-[120px]">
                                {processing ? (
                                    <>
                                        <Loader2 className="me-2 h-4 w-4 animate-spin" />
                                        {__('general.saving')}
                                    </>
                                ) : (
                                    __('general.save_cost')
                                )}
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
