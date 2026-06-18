import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, ArrowLeft, Info, AlertTriangle, CheckCircle } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { __ } from '@/lib/i18n';

export default function Create({ currencies = [], plans = [], statuses = [], roles = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        full_name: '',
        email: '',
        password: '',
        facebook: '',
        skype: '',
        phone_number: '',
        phone_number2: '',
        whatsapp_number: '',
        disable_unpaid_balance_whatsapp: false,
        job: '',
        address: '',
        hour_rate_currency: '',
        hour_rate: '',
        booking_rate_currency: '',
        booking_rate: '',
        booking_rate_expires_at: '',
        salary: '',
        usd_type: 'bank_usd',
        currency: '',
        subscription_date: '',
        subscription_plan: '',
        postpaid_limit: '',
        subscription_force: false,
        client_taxable: false,
        invoice_taxable: false,
        timer_taxable: false,
        allow_referral_system: false,
        allow_view_times: false,
        allow_postpaid: false,
        kyc_verified: false,
        kyc_notes: '',
        affiliate_commission_percentage: 1.00,
        add_commission_to_total: false,
        ref_user_id: '',
        slug: '',
        role: 'client',
        account_status: 'active',
        block_reason: '',
        max_devices: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/admin/users`);
    };

    const handleCheckboxChange = (e) => {
        setData(e.target.name, e.target.checked);
    };

    return (
        <AdminSidebarLayout>
            <Head title={__('general.create_account') || 'Create Account'} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <p className="text-sm text-gray-500 font-medium tracking-wider uppercase">{__('whatsapp.ui.system')}</p>
                        <h1 className="text-3xl font-bold text-gray-900">{__('general.create_account') || 'Create Account'}</h1>
                        <p className="text-gray-500 mt-1">{__('general.create_a_new_user_account_in_the_platform') || 'Create a new user account in the platform.'}</p>
                    </div>
                    <Link href={`/admin/users`}>
                        <Button variant="outline">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            {__('general.back')}
                        </Button>
                    </Link>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <form onSubmit={handleSubmit} className="p-6 md:p-8">
                        
                        {/* User Details */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.user_details')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label htmlFor="name">{__('general.name')} <span className="text-red-500">*</span></Label>
                                    <Input id="name" value={data.name} onChange={e => setData('name', e.target.value)} placeholder={__('general.name')} required />
                                    <p className="text-xs text-gray-500">Name must include a last name.</p>
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
                                    <Label htmlFor="password">{__('general.password')}</Label>
                                    <Input id="password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} placeholder={__('general.leave_blank_for_random')} />
                                    <p className="text-xs text-gray-500">{__('general.leave_blank_for_random') || 'Leave empty to generate a random password'}</p>
                                    {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Connection */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.connection')}</h5>
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
                                <input type="checkbox" id="disable_unpaid_balance_whatsapp" name="disable_unpaid_balance_whatsapp" checked={data.disable_unpaid_balance_whatsapp} onChange={handleCheckboxChange} className="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600" />
                                <div>
                                    <Label htmlFor="disable_unpaid_balance_whatsapp" className="font-bold cursor-pointer text-gray-900">{__('general.disable_unpaid_balance_whatsapp_notifications')}</Label>
                                    <p className="text-sm text-gray-500">{__('general.if_checked_this_user_will_not_receive_whatsapp_notifications_for_unpaid_balances')}</p>
                                </div>
                            </div>
                        </div>

                        {/* Info */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.info')}</h5>
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
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div className="space-y-2">
                                    <Label htmlFor="hour_rate_currency">{__('general.hour_rate_currency')}</Label>
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
                                        {__('general.hour_rate')}
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
                                    <Label htmlFor="currency">{__('general.client_default_currency')}</Label>
                                    <CurrencySelect
                                        id="currency"
                                        currencies={currencies}
                                        value={data.currency}
                                        onChange={val => setData('currency', val)}
                                        valueKey="id"
                                    />
                                    {errors.currency && <p className="text-sm text-red-600">{errors.currency}</p>}
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
                                <input type="checkbox" id="subscription_force" name="subscription_force" checked={data.subscription_force} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600" />
                                <Label htmlFor="subscription_force" className="font-bold cursor-pointer text-gray-900">{__('general.force_subscription')}</Label>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
                            {/* Tax Settings */}
                            <div>
                                <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.tax_settings')}</h5>
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="client_taxable" name="client_taxable" checked={data.client_taxable} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <Label htmlFor="client_taxable" className="cursor-pointer font-medium">{__('general.client_taxable')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="invoice_taxable" name="invoice_taxable" checked={data.invoice_taxable} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <Label htmlFor="invoice_taxable" className="cursor-pointer font-medium">{__('general.invoice_taxable')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="timer_taxable" name="timer_taxable" checked={data.timer_taxable} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <Label htmlFor="timer_taxable" className="cursor-pointer font-medium">{__('general.timer_taxable')}</Label>
                                    </div>
                                </div>
                            </div>

                            {/* General Settings */}
                            <div>
                                <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.general_settings')}</h5>
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="allow_referral_system" name="allow_referral_system" checked={data.allow_referral_system} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <Label htmlFor="allow_referral_system" className="cursor-pointer font-medium">{__('general.allow_referral_system')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="allow_view_times" name="allow_view_times" checked={data.allow_view_times} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <Label htmlFor="allow_view_times" className="cursor-pointer font-medium">{__('general.allow_view_work_times')}</Label>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <input type="checkbox" id="allow_postpaid" name="allow_postpaid" checked={data.allow_postpaid} onChange={handleCheckboxChange} className="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <Label htmlFor="allow_postpaid" className="cursor-pointer font-medium">{__('general.allow_postpaid')}</Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* KYC Verification */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.kyc_verification')}</h5>
                            <div className="grid grid-cols-1 gap-6">
                                <div>
                                    <div className="flex items-start space-x-3 mb-2">
                                        <input type="checkbox" id="kyc_verified" name="kyc_verified" checked={data.kyc_verified} onChange={handleCheckboxChange} className="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <div>
                                            <Label htmlFor="kyc_verified" className="font-bold cursor-pointer text-gray-900">{__('general.kyc_verified')}</Label>
                                            <p className="text-sm text-gray-500">{__('general.check_this_to_manually_verify_the_user_s_kyc_status')}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="kyc_notes">{__('general.kyc_admin_notes')}</Label>
                                    <textarea id="kyc_notes" name="kyc_notes" value={data.kyc_notes} onChange={e => setData('kyc_notes', e.target.value)} rows="3" className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" placeholder={__('general.internal_notes_about_kyc_verification_not_visible_to_user')}></textarea>
                                </div>
                            </div>
                        </div>

                        {/* Affiliate & Permissions */}
                        <div className="mb-10">
                            <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.affiliate_commission_settings')}</h5>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                                <div className="space-y-2">
                                    <Label htmlFor="affiliate_commission_percentage">{__('general.commission_percentage_percent')}</Label>
                                    <Input id="affiliate_commission_percentage" type="number" step="0.01" min="0" max="100" value={data.affiliate_commission_percentage} onChange={e => setData('affiliate_commission_percentage', e.target.value)} placeholder="1.00" />
                                    <p className="text-xs text-gray-500">{__('general.enter_the_commission_percentage_e_g_1_00_for_1_percent')}</p>
                                </div>
                                <div className="flex items-start space-x-3 md:pt-8">
                                    <input type="checkbox" id="add_commission_to_total" name="add_commission_to_total" checked={data.add_commission_to_total} onChange={handleCheckboxChange} className="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600" />
                                    <div>
                                        <Label htmlFor="add_commission_to_total" className="font-bold cursor-pointer text-gray-900">{__('general.add_commission_to_invoice_total')}</Label>
                                        <p className="text-sm text-gray-500">{__('general.if_checked_commission_will_be_added_to_invoice_total_instead_of_deducted')}</p>
                                    </div>
                                </div>
                            </div>

                            <h5 className="text-lg font-bold text-blue-600 mb-6">{__('general.referral_and_permissions')}</h5>
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
                                <Save className="mr-2 h-4 w-4" />{__('general.save')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
