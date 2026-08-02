import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Save, ArrowLeft, Info, AlertTriangle, CheckCircle, Mail, Plus, Trash2 } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { Switch } from '@/Components/ui/switch';
import { __ } from '@/lib/i18n';

export default function Edit({ user, currencies = [], plans = [], statuses = [], roles = [], emails = [] }) {
    const [newAliasEmail, setNewAliasEmail] = useState('');
    const [newAliasVerified, setNewAliasVerified] = useState(true);
    const [addingAlias, setAddingAlias] = useState(false);

    const handleAddAlias = (e) => {
        e.preventDefault();
        if (!newAliasEmail) return;
        setAddingAlias(true);
        router.post(`/admin/users/${user.id}/emails`, {
            email: newAliasEmail.trim(),
            verified_at: newAliasVerified ? 1 : 0,
        }, {
            onFinish: () => {
                setAddingAlias(false);
                setNewAliasEmail('');
            },
            preserveScroll: true,
        });
    };

    const handleRemoveAlias = (alias) => {
        if (!confirm(__('general.confirm_remove_alias', { email: alias.email }) || `Are you sure you want to remove ${alias.email}?`)) return;
        router.delete(`/admin/users/${user.id}/emails/${alias.id}`, {
            preserveScroll: true,
        });
    };

    const handleMakePrimaryAlias = (alias) => {
        if (!confirm(__('general.confirm_make_primary_alias', { email: alias.email }) || `Make ${alias.email} the primary email for this account?`)) return;
        router.post(`/admin/users/${user.id}/emails/${alias.id}/make-primary`, {}, {
            preserveScroll: true,
        });
    };

    const { data, setData, put, processing, errors } = useForm({
        name: user.name || '',
        full_name: user.full_name || '',
        email: user.email || '',
        password: '',
        facebook: user.facebook || '',
        skype: user.skype || '',
        phone_number: user.phone_number || '',
        phone_number2: user.phone_number2 || '',
        whatsapp_number: user.whatsapp_number || '',
        disable_unpaid_balance_whatsapp: user.disable_unpaid_balance_whatsapp || false,
        job: user.job || '',
        address: user.address || '',
        enable_custom_hour_rate: !!user.enable_custom_hour_rate,
        hour_rate_currency: user.hour_rate_currency || '',
        hour_rate: user.hour_rate || '',
        booking_rate_currency: user.booking_rate_currency || '',
        booking_rate: user.booking_rate || '',
        booking_rate_expires_at: user.booking_rate_expires_at || '',
        salary: user.salary || '',
        usd_type: user.usd_type || 'bank_usd',
        currency: user.currency || '',
        subscription_date: user.subscription_date || '',
        subscription_plan: user.subscription_plan || '',
        postpaid_limit: user.postpaid_limit || '',
        subscription_force: user.subscription_force || false,
        client_taxable: user.client_taxable || false,
        invoice_taxable: user.invoice_taxable || false,
        timer_taxable: user.timer_taxable || false,
        allow_referral_system: user.allow_referral_system || false,
        allow_view_times: user.allow_view_times || false,
        allow_postpaid: user.allow_postpaid || false,
        enable_notifications: user.enable_notifications ?? true,
        kyc_verified: user.kyc_verified || false,
        kyc_notes: user.kyc_notes || '',
        affiliate_commission_percentage: user.affiliate_commission_percentage ?? 1.00,
        add_commission_to_total: user.add_commission_to_total || false,
        ref_user_id: user.ref_user_id || '',
        slug: user.slug || '',
        role: user.role || 'client',
        account_status: user.account_status || 'active',
        block_reason: user.block_reason || '',
        max_devices: user.max_devices || '',
        enable_3d_dashboard: user.enable_3d_dashboard ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    const handleCheckboxChange = (e) => {
        setData(e.target.name, e.target.checked);
    };

    return (
        <AdminSidebarLayout>
            <Head title={`${__('general.edit_account')}: ${user.name}`} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <p className="text-sm text-gray-500 font-medium tracking-wider uppercase">{__('whatsapp.ui.system')}</p>
                        <h1 className="text-3xl font-bold text-gray-900">{__('general.edit_account')}</h1>
                        <p className="text-gray-500 mt-1">{user.name}</p>
                    </div>
                    <Link href={`/admin/users/${user.id}`}>
                        <Button variant="outline">
                            <ArrowLeft className="me-2 h-4 w-4" />
                            {__('general.back')}
                        </Button>
                    </Link>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <form onSubmit={handleSubmit} className="p-6 md:p-8">
                        
                        {/* User Details */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.user_details')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="name">{__('general.name')} <span className="text-red-500">*</span></Label>
                                    <Input id="name" value={data.name} onChange={e => setData('name', e.target.value)} placeholder={__('general.name')} required />
                                    {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="full_name">{__('general.full_name')}</Label>
                                    <Input id="full_name" value={data.full_name} onChange={e => setData('full_name', e.target.value)} placeholder={__('general.full_name')} />
                                    {errors.full_name && <p className="text-sm text-red-600">{errors.full_name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="email">{__('general.email_address')}<span className="text-red-500">*</span></Label>
                                    <Input id="email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} placeholder={__('general.enter_email')} required />
                                    {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('general.password')}</Label>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button type="button" variant="outline" className="w-full justify-start text-start font-normal text-muted-foreground">
                                                {data.password ? __('general.password_entered_ready_to_save') : __('general.leave_blank_to_keep_current')}
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>{__('general.update_password')}</DialogTitle>
                                                <DialogDescription>
                                                    {__('general.enter_a_new_password_for_this_user_leave_it_empty_to_keep_the_current_password')}
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="space-y-4 py-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="password">{__('general.new_password')}</Label>
                                                    <Input id="password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} placeholder={__('general.leave_blank_to_keep_current')} />
                                                    {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                                                </div>
                                            </div>
                                            <DialogFooter showCloseButton={true}>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>

                                {/* Additional Email Addresses (Email Aliases) */}
                                <div className="md:col-span-2 mt-2 p-4 rounded-xl border border-slate-200 bg-slate-50/70 space-y-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <h6 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                                <Mail className="h-4 w-4 text-slate-700" />
                                                {__('general.additional_email_addresses') || 'عناوين البريد الإلكتروني الإضافية (Email Aliases)'}
                                            </h6>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                {__('general.additional_emails_desc') || 'يمكن للمستخدم تسجيل الدخول بنفس كلمة المرور واستلام الإشعارات باستخدام أي من عناوين البريد المضافة.'}
                                            </p>
                                        </div>
                                        <Link href={`/admin/users/${user.id}/emails`}>
                                            <Button type="button" variant="outline" size="sm" className="text-xs">
                                                {__('general.manage_all_aliases') || 'إدارة ودمج الحسابات'}
                                            </Button>
                                        </Link>
                                    </div>

                                    {/* Existing Aliases List */}
                                    {emails && emails.length > 0 ? (
                                        <div className="space-y-2">
                                            {emails.map((alias) => (
                                                <div key={alias.id} className="flex items-center justify-between p-2.5 bg-white rounded-lg border border-slate-200 text-sm">
                                                    <div className="flex items-center gap-2.5 min-w-0">
                                                        <span className="font-mono text-slate-800 text-xs sm:text-sm truncate">{alias.email}</span>
                                                        <span className={`text-[10px] uppercase px-2 py-0.5 rounded-full font-semibold ${
                                                            alias.verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                                                        }`}>
                                                            {alias.verified ? (__('general.verified') || 'مؤكد') : (__('general.pending') || 'معلق')}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-1 shrink-0">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 text-xs text-blue-600 hover:text-blue-700 hover:bg-blue-50 px-2"
                                                            onClick={() => handleMakePrimaryAlias(alias)}
                                                        >
                                                            {__('general.make_primary') || 'جعله رئيسي'}
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 px-2"
                                                            onClick={() => handleRemoveAlias(alias)}
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-xs text-slate-500 italic">
                                            {__('general.no_additional_emails') || 'لا توجد عناوين بريد إلكتروني إضافية مضافة لهذا الحساب.'}
                                        </p>
                                    )}

                                    {/* Add New Email Inline */}
                                    <div className="pt-3 border-t border-slate-200/80">
                                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                            <Input
                                                type="email"
                                                value={newAliasEmail}
                                                onChange={(e) => setNewAliasEmail(e.target.value)}
                                                placeholder={__('general.add_another_email_placeholder') || 'أدخل بريد إلكتروني إضافي... (مثال: secondary@example.com)'}
                                                className="grow text-xs sm:text-sm bg-white"
                                            />
                                            <div className="flex items-center gap-2 shrink-0 justify-between sm:justify-start">
                                                <label className="inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer select-none px-1">
                                                    <input
                                                        type="checkbox"
                                                        checked={newAliasVerified}
                                                        onChange={(e) => setNewAliasVerified(e.target.checked)}
                                                        className="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-3.5 w-3.5"
                                                    />
                                                    {__('general.mark_verified') || 'مؤكد'}
                                                </label>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    disabled={addingAlias || !newAliasEmail}
                                                    onClick={handleAddAlias}
                                                    className="text-xs"
                                                >
                                                    <Plus className="h-3.5 w-3.5 me-1" />
                                                    {addingAlias ? (__('general.adding') || 'جاري الإضافة...') : (__('general.add_email') || 'إضافة بريد')}
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Connection */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.connection')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div className="space-y-2">
                                    <Label htmlFor="facebook">{__('general.facebook')}</Label>
                                    <Input id="facebook" value={data.facebook} onChange={e => setData('facebook', e.target.value)} placeholder={__('general.facebook_profile')} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="skype">{__('general.skype')}</Label>
                                    <Input id="skype" value={data.skype} onChange={e => setData('skype', e.target.value)} placeholder={__('general.skype_id')} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone_number">{__('general.phone_number')}</Label>
                                    <Input id="phone_number" value={data.phone_number} onChange={e => setData('phone_number', e.target.value)} placeholder={__('general.phone_number')} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone_number2">{__('general.phone_number_2')}</Label>
                                    <Input id="phone_number2" value={data.phone_number2} onChange={e => setData('phone_number2', e.target.value)} placeholder={__('general.secondary_phone_number')} />
                                </div>
                                <div className="md:col-span-2 space-y-2">
                                    <Label htmlFor="whatsapp_number">{__('general.whatsapp_number')}</Label>
                                    <Input id="whatsapp_number" value={data.whatsapp_number} onChange={e => setData('whatsapp_number', e.target.value)} placeholder={__('general.whatsapp_number')} />
                                    <p className="text-xs text-gray-500">{__('general.you_can_enter_multiple_numbers_separated_by_commas_e_g_2010_2012')}</p>
                                </div>
                            </div>
                            <div className="flex items-start space-x-3 mt-4">
                                <input type="checkbox" id="disable_unpaid_balance_whatsapp" name="disable_unpaid_balance_whatsapp" checked={data.disable_unpaid_balance_whatsapp} onChange={handleCheckboxChange} className="mt-1 h-4 w-4 rounded border-gray-300 text-slate-900 focus:ring-slate-900" />
                                <div>
                                    <Label htmlFor="disable_unpaid_balance_whatsapp" className="font-bold cursor-pointer text-gray-900">{__('general.disable_unpaid_balance_whatsapp_notifications')}</Label>
                                    <p className="text-sm text-gray-500">{__('general.if_checked_this_user_will_not_receive_whatsapp_notifications_for_unpaid_balances')}</p>
                                </div>
                            </div>
                        </div>

                        {/* Info */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.info')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="job">{__('general.job')}</Label>
                                    <Input id="job" value={data.job} onChange={e => setData('job', e.target.value)} placeholder={__('general.job_title')} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="address">{__('general.address')}</Label>
                                    <Input id="address" value={data.address} onChange={e => setData('address', e.target.value)} placeholder={__('general.address')} />
                                </div>
                            </div>
                        </div>

                        {/* Financial / Rates */}
                        <div className="mb-10 border-t pt-8">
                            <div className="flex items-center justify-between p-4 mb-6 rounded-xl border bg-slate-50/50">
                                <div>
                                    <Label htmlFor="enable_custom_hour_rate" className="font-semibold text-sm cursor-pointer">
                                        {__('general.enable_custom_hour_rate') || 'تفعيل سعر ساعة مخصص'}
                                    </Label>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        {data.enable_custom_hour_rate
                                            ? (__('general.custom_hour_rate_active_desc') || 'سيتم استخدام سعر الساعة المخصص أدناه في الفواتير والمؤقتات بدلاً من سعر النظام.')
                                            : (__('general.custom_hour_rate_inactive_desc') || 'عند الإيقاف، سيتم استخدام سعر النظام الأساسي تلقائيًا.')}
                                    </p>
                                </div>
                                <Switch
                                    id="enable_custom_hour_rate"
                                    checked={data.enable_custom_hour_rate}
                                    onCheckedChange={checked => setData('enable_custom_hour_rate', checked)}
                                />
                            </div>

                            <div className={`grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 transition-all ${data.enable_custom_hour_rate ? 'opacity-100' : 'opacity-60'}`}>
                                <div className="space-y-2">
                                    <Label htmlFor="hour_rate_currency">{__('general.hour_rate_currency')}<span className="text-red-500">*</span></Label>
                                    <CurrencySelect
                                        id="hour_rate_currency"
                                        currencies={currencies}
                                        value={data.hour_rate_currency}
                                        onChange={val => setData('hour_rate_currency', val)}
                                        valueKey="id"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="hour_rate">
                                        {__('general.hour_rate')} {user.hour_rate_cur ? `(${user.hour_rate_cur})` : '(—)'}
                                    </Label>
                                    <Input id="hour_rate" type="number" step="0.01" value={data.hour_rate} onChange={e => setData('hour_rate', e.target.value)} placeholder="0.00" />
                                    <p className="text-xs text-gray-500">{__('general.for_invoices_and_timers_only')}</p>
                                </div>
                            </div>

                            <div className="mb-4 pt-4 border-t border-gray-100">
                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.task_booking_rate')} ({__('general.focus_page')})</label>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div className="space-y-2">
                                    <Label htmlFor="booking_rate_currency">{__('general.booking_rate_currency')}</Label>
                                    <CurrencySelect
                                        id="booking_rate_currency"
                                        currencies={currencies}
                                        value={data.booking_rate_currency}
                                        onChange={val => setData('booking_rate_currency', val)}
                                        valueKey="id"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="booking_rate">{__('general.booking_rate')}</Label>
                                    <Input id="booking_rate" type="number" step="0.01" value={data.booking_rate} onChange={e => setData('booking_rate', e.target.value)} placeholder={__('general.e_g_143')} />
                                    <p className="text-xs text-gray-500">{__('general.leave_empty_for_standard_rate_discount')}</p>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="booking_rate_expires_at">{__('general.booking_rate_valid_until')}</Label>
                                    <Input id="booking_rate_expires_at" type="date" value={data.booking_rate_expires_at} onChange={e => setData('booking_rate_expires_at', e.target.value)} />
                                    <p className="text-xs text-gray-500">{__('general.optional_after_this_date_standard_rate_plan_discount_applies')}</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="salary">{__('general.salary_monthly')}</Label>
                                    <Input id="salary" type="number" step="0.01" value={data.salary} onChange={e => setData('salary', e.target.value)} placeholder={__('general.employee_salary')} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="usd_type">{__('general.hour_rate_usd_type_egp')} <span className="text-red-500">*</span></Label>
                                    <select id="usd_type" value={data.usd_type} onChange={e => setData('usd_type', e.target.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" required>
                                        <option value="bank_usd">{__('general.min_usd')}</option>
                                        <option value="mix_usd">{__('general.mid_usd')}</option>
                                        <option value="gold_usd">{__('general.max_usd')}</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="currency">{__('general.client_default_currency')}<span className="text-red-500">*</span></Label>
                                    <CurrencySelect
                                        id="currency"
                                        currencies={currencies}
                                        value={data.currency}
                                        onChange={val => setData('currency', val)}
                                        valueKey="id"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Subscription */}
                        <div className="mb-10">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                                <div className="space-y-2">
                                    <Label htmlFor="subscription_date">{__('general.subscription_date')}</Label>
                                    <Input id="subscription_date" type="date" value={data.subscription_date} onChange={e => setData('subscription_date', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="subscription_plan">{__('general.subscription_plan')}</Label>
                                    <select id="subscription_plan" value={data.subscription_plan} onChange={e => setData('subscription_plan', e.target.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                        <option value="">-- {__('general.select')} --</option>
                                        {plans.map(p => (
                                            <option key={p.id} value={p.id}>{p.plan_name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="postpaid_limit">{__('general.postpaid_limit')}</Label>
                                    <Input id="postpaid_limit" type="number" step="0.01" value={data.postpaid_limit} onChange={e => setData('postpaid_limit', e.target.value)} placeholder={__('general.postpaid_limit')} />
                                </div>
                            </div>
                            <div className="flex items-center space-x-2 mt-4">
                                <input type="checkbox" id="subscription_force" name="subscription_force" checked={data.subscription_force} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900 focus:ring-slate-900" />
                                <Label htmlFor="subscription_force" className="font-bold cursor-pointer text-gray-900">{__('general.force_subscription')}</Label>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
                            {/* Tax Settings */}
                            <div>
                                <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.tax_settings')}</h5>
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="client_taxable" name="client_taxable" checked={data.client_taxable} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="client_taxable" className="cursor-pointer font-medium">{__('general.client_taxable')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="invoice_taxable" name="invoice_taxable" checked={data.invoice_taxable} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="invoice_taxable" className="cursor-pointer font-medium">{__('general.invoice_taxable')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="timer_taxable" name="timer_taxable" checked={data.timer_taxable} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="timer_taxable" className="cursor-pointer font-medium">{__('general.timer_taxable')}</Label>
                                    </div>
                                </div>
                            </div>

                            {/* General Settings */}
                            <div>
                                <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.general_settings')}</h5>
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="allow_referral_system" name="allow_referral_system" checked={data.allow_referral_system} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="allow_referral_system" className="cursor-pointer font-medium">{__('general.allow_referral_system')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="allow_view_times" name="allow_view_times" checked={data.allow_view_times} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="allow_view_times" className="cursor-pointer font-medium">{__('general.allow_view_work_times')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="allow_postpaid" name="allow_postpaid" checked={data.allow_postpaid} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="allow_postpaid" className="cursor-pointer font-medium">{__('general.allow_postpaid')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="enable_notifications" name="enable_notifications" checked={data.enable_notifications} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="enable_notifications" className="cursor-pointer font-medium">{__('general.enable_notifications')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="enable_3d_dashboard" name="enable_3d_dashboard" checked={data.enable_3d_dashboard} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <Label htmlFor="enable_3d_dashboard" className="cursor-pointer font-medium">{__('general.enable_3d_dashboard') || 'Enable 3D Holographic Dashboard'}</Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* KYC Verification */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.kyc_verification')}</h5>
                            <div className="grid grid-cols-1 gap-6">
                                <div>
                                    <div className="flex items-start space-x-3 mb-2">
                                        <input type="checkbox" id="kyc_verified" name="kyc_verified" checked={data.kyc_verified} onChange={handleCheckboxChange} className="mt-1 h-4 w-4 rounded border-gray-300 text-slate-900" />
                                        <div>
                                            <Label htmlFor="kyc_verified" className="font-bold cursor-pointer text-gray-900">{__('general.kyc_verified')}</Label>
                                            <p className="text-sm text-gray-500">{__('general.check_this_to_manually_verify_the_user_s_kyc_status')}</p>
                                        </div>
                                    </div>
                                    {user.kyc_verified_at && (
                                        <div className="ms-7 mt-2 text-sm text-green-600 font-medium flex items-center">
                                            <CheckCircle className="h-4 w-4 me-1.5" />
                                            {__('general.verified_on')} {user.kyc_verified_at}
                                            {user.kyc_verifier && ` ${__('general.by')} ${user.kyc_verifier.name}`}
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="kyc_notes">{__('general.kyc_admin_notes')}</Label>
                                    <textarea id="kyc_notes" name="kyc_notes" value={data.kyc_notes} onChange={e => setData('kyc_notes', e.target.value)} rows="3" className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" placeholder={__('general.internal_notes_about_kyc_verification_not_visible_to_user')}></textarea>
                                </div>
                                
                                <div className="mt-2">
                                    {user.kyc_documents_count > 0 ? (
                                        <div className="bg-slate-100 border border-slate-200 text-slate-900 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div className="flex items-start">
                                                <Info className="h-5 w-5 me-2 text-slate-900 shrink-0 mt-0.5" />
                                                <div>
                                                    <strong className="font-bold">{__('general.kyc_documents')}:</strong> {__('general.this_user_has_n_kyc_documents', { count: user.kyc_documents_count })}
                                                </div>
                                            </div>
                                            <a href={`/admin/users/${user.id}/kyc/documents`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-slate-900 text-white hover:bg-slate-900 h-9 px-4 py-2 shrink-0">
                                                {__('general.view_documents')}
                                            </a>
                                        </div>
                                    ) : (
                                        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4 flex items-start">
                                            <AlertTriangle className="h-5 w-5 me-2 text-yellow-600 shrink-0 mt-0.5" />
                                            <div>
                                                {__('general.this_user_has_not_uploaded_any_kyc_documents_yet')}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Affiliate & Permissions */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.affiliate_commission_settings')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                                <div className="space-y-2">
                                    <Label htmlFor="affiliate_commission_percentage">{__('general.commission_percentage_percent')}</Label>
                                    <Input id="affiliate_commission_percentage" type="number" step="0.01" min="0" max="100" value={data.affiliate_commission_percentage} onChange={e => setData('affiliate_commission_percentage', e.target.value)} placeholder="1.00" />
                                    <p className="text-xs text-gray-500">{__('general.enter_the_commission_percentage_e_g_1_00_for_1_percent')}</p>
                                </div>
                                <div className="flex items-start space-x-3 md:pt-8">
                                    <input type="checkbox" id="add_commission_to_total" name="add_commission_to_total" checked={data.add_commission_to_total} onChange={handleCheckboxChange} className="mt-1 h-4 w-4 rounded border-gray-300 text-slate-900" />
                                    <div>
                                        <Label htmlFor="add_commission_to_total" className="font-bold cursor-pointer text-gray-900">{__('general.add_commission_to_invoice_total')}</Label>
                                        <p className="text-sm text-gray-500">{__('general.if_checked_commission_will_be_added_to_invoice_total_instead_of_deducted')}</p>
                                    </div>
                                </div>
                            </div>

                            <h5 className="text-lg font-bold text-slate-900 mb-6">{__('general.referral_and_permissions')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="ref_user_id">{__('general.referral_user_id')}</Label>
                                    <Input id="ref_user_id" value={data.ref_user_id} onChange={e => setData('ref_user_id', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="role">{__('general.permission')}</Label>
                                    <select id="role" value={data.role} onChange={e => setData('role', e.target.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                        <option value="client">{__('general.client')}</option>
                                        <option value="user">{__('general.user')}</option>
                                        <option value="admin">{__('general.admin')}</option>
                                        <option value="manager">{__('general.manager')}</option>
                                        <option value="employee">{__('general.employee')}</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="account_status">{__('general.account_status')}</Label>
                                    <select id="account_status" value={data.account_status} onChange={e => setData('account_status', e.target.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                        <option value="active">{__('general.active')}</option>
                                        <option value="blocked">{__('general.blocked')}</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="block_reason">{__('general.block_reason')}</Label>
                                    <textarea id="block_reason" name="block_reason" value={data.block_reason} onChange={e => setData('block_reason', e.target.value)} rows="2" className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end pt-6 border-t">
                            <Button type="submit" disabled={processing} className="w-full md:w-auto">
                                <Save className="me-2 h-4 w-4" />{__('general.save_changes')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
