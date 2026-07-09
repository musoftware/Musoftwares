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
    Info,
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatCurrency } from '@/lib/utils';

export default function CostsEdit() {
    const { cost, users, projects, currencies, businessCurrency, paymentMethods, categories, attachment_url, business_currency_code } = usePage<any>().props;

    const { data, setData, put, processing, errors } = useForm({
        amount: cost?.amount ? String(cost.amount) : '',
        currency_id: cost?.currency_id ? String(cost.currency_id) : (cost?.currency ? String(cost.currency) : (businessCurrency?.id ? String(businessCurrency.id) : '')),
        reason: cost?.reason || '',
        created_at: cost?.created_at ? new Date(cost.created_at).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
        user_id: cost?.user_id ? String(cost.user_id) : '',
        project_id: cost?.project_id ? String(cost.project_id) : '',
        category: cost?.category || '',
        category_text: '',
        payment_method: cost?.payment_method || '',
        tax_amount: cost?.tax_amount ? String(cost.tax_amount) : '0',
        tax_rate: cost?.tax_rate ? String(cost.tax_rate) : '0',
        is_billable: !!cost?.is_billable,
        notes: cost?.notes || '',
        attachment: null as File | null,
        remove_attachment: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.costs.update', cost.id), {
            forceFormData: true,
        });
    };

    const existingAttachment = cost?.attachment_path && !data.remove_attachment;
    const knownCategory = (categories || []).find((c: any) => c.value === data.category);

    return (
        <AdminSidebarLayout
            title={__('general.edit_cost')}
            header={__('general.edit_cost')}
        >
            <Head title={__('general.edit_cost')} />
            <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('admin.costs.index'))}
                        className="hover:bg-slate-100"
                    >
                        <ArrowLeft className="w-4 h-4 me-2" />
                        {__('general.back')}
                    </Button>
                    {cost?.trashed?.() && (
                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700">
                            {__('general.deleted')}
                        </span>
                    )}
                </div>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-4 border-b border-slate-100 bg-slate-50/50 rounded-t-xl">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-rose-100 text-rose-700 rounded-xl shadow-sm">
                                <Receipt className="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle className="text-xl font-bold text-slate-900">
                                    {__('general.edit_direct_cost')}
                                </CardTitle>
                                <CardDescription className="text-sm mt-1">
                                    {__('general.edit_direct_cost_description')}
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
                                        className="h-11 font-mono text-base bg-white"
                                        required
                                    />
                                    {cost?.business_amount && (
                                        <p className="text-xs text-slate-500 flex items-center gap-1 mt-1">
                                            <Info className="h-3 w-3" />
                                            {__('general.equivalent_in_business_currency')}: {formatCurrency(Math.abs(cost.business_amount), business_currency_code || businessCurrency?.currency)}
                                        </p>
                                    )}
                                    {errors.amount && <p className="text-xs text-rose-600 mt-1">{errors.amount}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <DollarSign className="w-4 h-4 text-slate-400" />
                                        {__('general.currency')} <span className="text-rose-600">*</span>
                                    </Label>
                                    <PremiumCombobox
                                        value={data.currency_id}
                                        onChange={(val) => setData('currency_id', val as string)}
                                        options={currencies.map((c: any) => ({
                                            value: String(c.id),
                                            label: `${c.currency} (${c.symbol})`,
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
                                        value={knownCategory ? data.category : (data.category ? '__new__' : '')}
                                        onChange={(val) => {
                                            if (val === '__new__') {
                                                setData('category', data.category);
                                                setData('category_text', data.category);
                                            } else {
                                                setData('category', val as string);
                                                setData('category_text', '');
                                            }
                                        }}
                                        options={[
                                            { value: '', label: __('general.none') },
                                            ...(categories || []),
                                            { value: '__new__', label: __('general.custom_label') },
                                        ]}
                                        placeholder={__('general.select_category')}
                                    />
                                    {(!knownCategory && data.category) && (
                                        <Input
                                            placeholder={__('general.new_category_name')}
                                            className="h-10 mt-2"
                                            value={data.category_text || data.category}
                                            onChange={(e) => {
                                                setData('category', e.target.value);
                                                setData('category_text', e.target.value);
                                            }}
                                            maxLength={80}
                                        />
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold flex items-center gap-2">
                                        <CreditCard className="w-4 h-4 text-slate-400" />
                                        {__('general.payment_method')}
                                    </Label>
                                    <PremiumCombobox
                                        value={data.payment_method}
                                        onChange={(val) => setData('payment_method', val as string)}
                                        options={[{ value: '', label: __('general.none') }, ...(paymentMethods || [])]}
                                        placeholder={__('general.select_payment_method')}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold">{__('general.tax_rate_percent')}</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={data.tax_rate}
                                        onChange={(e) => setData('tax_rate', e.target.value)}
                                        className="h-11 bg-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-slate-700 font-semibold">{__('general.tax_amount')}</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.tax_amount}
                                        onChange={(e) => setData('tax_amount', e.target.value)}
                                        className="h-11 bg-white"
                                    />
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
                                </div>

                                <div className="md:col-span-2 flex items-center gap-2">
                                    <Checkbox
                                        id="is_billable_e"
                                        checked={data.is_billable}
                                        onCheckedChange={(v) => setData('is_billable', !!v)}
                                    />
                                    <Label htmlFor="is_billable_e" className="text-sm cursor-pointer">
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
                                        <Label className="text-slate-700 text-sm">{__('general.client_user')}</Label>
                                        <PremiumCombobox
                                            value={data.user_id}
                                            onChange={(val) => setData('user_id', val as string)}
                                            options={[{ value: '', label: __('general.none') }, ...users.map((u: any) => ({ value: String(u.id), label: u.name }))]}
                                            placeholder={__('general.search_client')}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 text-sm">{__('general.project')}</Label>
                                        <PremiumCombobox
                                            value={data.project_id}
                                            onChange={(val) => setData('project_id', val as string)}
                                            options={[{ value: '', label: __('general.none') }, ...projects.map((p: any) => ({ value: String(p.id), label: p.name }))]}
                                            placeholder={__('general.search_project')}
                                        />
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
                                            className="min-h-[100px] bg-white"
                                            maxLength={5000}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 text-sm">{__('general.attachment')}</Label>
                                        {existingAttachment && (
                                            <div className="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50">
                                                <a
                                                    href={attachment_url || '#'}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-sm text-slate-700 hover:text-slate-900 underline truncate"
                                                >
                                                    {cost?.attachment_path}
                                                </a>
                                                <label className="flex items-center gap-2 ms-3 text-xs text-rose-600 cursor-pointer">
                                                    <Checkbox
                                                        checked={data.remove_attachment}
                                                        onCheckedChange={(v) => setData('remove_attachment', !!v)}
                                                    />
                                                    <span>{__('general.remove')}</span>
                                                </label>
                                            </div>
                                        )}
                                        <Input
                                            type="file"
                                            accept=".jpg,.jpeg,.png,.pdf,.webp"
                                            onChange={(e: any) => {
                                                setData('attachment', e.target.files?.[0] ?? null);
                                                setData('remove_attachment', false);
                                            }}
                                            className="h-11 bg-white"
                                        />
                                        <p className="text-xs text-slate-500">{__('general.attachment_help')}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 rounded-b-xl">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.visit(route('admin.costs.show', cost.id))}
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
                                    __('general.save_changes')
                                )}
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
