import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import {
    Building2,
    CreditCard,
    MessageCircle,
    Clock,
    AlertTriangle,
    Save,
    RefreshCw,
    Lightbulb,
} from 'lucide-react';
import { CurrencySelect } from '@/Components/CurrencySelect';

interface Currency {
    id: number;
    currency: string;
    symbol?: string;
}

interface WhatsAppChannel {
    id: number;
    name: string;
    phone_number?: string;
    user?: { id: number; name: string };
}

interface SettingsData {
    business_currency: string | null;
    business_name: string | null;
    business_phone: string | null;
    business_address: string | null;
    business_tax: string | null;
    business_email: string | null;
    overhead_cost_default: string | null;
    ownwallet: boolean;
    payoneer_active: boolean;
    paymob_active: boolean;
    paymob_token: string | null;
    paymob_card_integration: string | null;
    paymob_wallet_integration: string | null;
    paymob_card_iframe: string | null;
    gumroad: string | null;
    whatsapp_default_channel_id: string | null;
    friday_work_allowed: boolean;
    max_devices_per_tenant: number;
    gemini_api_keys: string | null;
    expected_monthly_income: string | null;
    work_days_per_month: string | null;
    hours_per_day: string | null;
}

interface Props {
    currencies: Currency[];
    whatsappChannels: WhatsAppChannel[];
    settings: SettingsData;
}

function SectionCard({ title, icon: Icon, children }: { title: string; icon: React.ElementType; children: React.ReactNode }) {
    return (
        <div className="rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50">
                <Icon className="h-4 w-4 text-gray-500" />
                <h2 className="text-sm font-semibold text-gray-800 uppercase tracking-wide">{title}</h2>
            </div>
            <div className="p-5">{children}</div>
        </div>
    );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex flex-col gap-1">
            <Label className="text-xs font-medium text-gray-600">{label}</Label>
            {children}
        </div>
    );
}

function SelectField({
    label,
    name,
    value,
    onChange,
    children,
}: {
    label: string;
    name: string;
    value: string;
    onChange: (v: string) => void;
    children: React.ReactNode;
}) {
    return (
        <Field label={label}>
            <select
                id={name}
                name={name}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black bg-white"
            >
                {children}
            </select>
        </Field>
    );
}

function Toggle({
    label,
    id,
    checked,
    onChange,
    description,
}: {
    label: string;
    id: string;
    checked: boolean;
    onChange: (v: boolean) => void;
    description?: string;
}) {
    return (
        <div className="flex items-start gap-3">
            <button
                type="button"
                id={id}
                role="switch"
                aria-checked={checked}
                onClick={() => onChange(!checked)}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 ${
                    checked ? 'bg-black' : 'bg-gray-200'
                }`}
            >
                <span
                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                        checked ? 'translate-x-5' : 'translate-x-0'
                    }`}
                />
            </button>
            <div>
                <Label htmlFor={id} className="text-sm font-medium text-gray-800 cursor-pointer">
                    {label}
                </Label>
                {description && <p className="text-xs text-gray-500 mt-0.5">{description}</p>}
            </div>
        </div>
    );
}

export default function Index({ currencies, whatsappChannels, settings }: Props) {
    const { props } = usePage<any>();
    const flash = props.flash as { success?: string } | undefined;

    const [form, setForm] = useState<SettingsData>({ ...settings });
    const [bulkCurrency, setBulkCurrency] = useState(currencies[0]?.id?.toString() ?? '');
    const [updateProjects, setUpdateProjects] = useState(true);

    const computedRate = React.useMemo(() => {
        const income = parseFloat(form.expected_monthly_income ?? '0');
        const days = parseFloat(form.work_days_per_month ?? '0');
        const hours = parseFloat(form.hours_per_day ?? '0');
        if (income > 0 && days > 0 && hours > 0) {
            return (income / (days * hours)).toFixed(2);
        }
        return '0.00';
    }, [form.expected_monthly_income, form.work_days_per_month, form.hours_per_day]);

    const set = <K extends keyof SettingsData>(key: K, value: SettingsData[K]) =>
        setForm((prev) => ({ ...prev, [key]: value }));

    const handleSettingsSave = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.settings.store'), form as any);
    };

    const handleBulkPriceUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!confirm('Are you sure you want to update prices for ALL clients?')) return;
        router.post(route('admin.settings.do-update-prices'), {
            hour_rate: computedRate,
            currency: bulkCurrency,
            update_projects: updateProjects ? '1' : '0',
        });
    };

    return (
        <AdminSidebarLayout title="Settings" header="System Settings">
            <Head title={__('general.admin_settings')} />

            {/* Flash message */}
            {flash?.success && (
                <div className="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {flash.success}
                </div>
            )}

            <form onSubmit={handleSettingsSave} className="space-y-6">
                {/* Row 1: Business + Work */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Business Information */}
                    <SectionCard title={__('general.business_information')} icon={Building2}>
                        <div className="space-y-4">
                            <Field label={__('general.business_name')}>
                                <Input
                                    id="business_name"
                                    value={form.business_name ?? ''}
                                    onChange={(e) => set('business_name', e.target.value)}
                                    placeholder={__('general.e_g_musoftware')}
                                />
                            </Field>
                            <Field label={__('general.business_address')}>
                                <Input
                                    id="business_address"
                                    value={form.business_address ?? ''}
                                    onChange={(e) => set('business_address', e.target.value)}
                                    placeholder={__('general.e_g_suez_egypt')}
                                />
                            </Field>
                            <div className="grid grid-cols-2 gap-4">
                                <Field label={__('general.business_phone')}>
                                    <Input
                                        id="business_phone"
                                        value={form.business_phone ?? ''}
                                        onChange={(e) => set('business_phone', e.target.value)}
                                        placeholder="+201..."
                                    />
                                </Field>
                                <Field label={__('general.business_email')}>
                                    <Input
                                        id="business_email"
                                        type="email"
                                        value={form.business_email ?? ''}
                                        onChange={(e) => set('business_email', e.target.value)}
                                        placeholder={__('general.admin_example_com')}
                                    />
                                </Field>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <Field label={__('general.business_tax')}>
                                    <Input
                                        id="business_tax"
                                        type="number"
                                        step="0.01"
                                        value={form.business_tax ?? ''}
                                        onChange={(e) => set('business_tax', e.target.value)}
                                        placeholder="22.5"
                                    />
                                </Field>
                                <CurrencySelect 
                                    label={__('general.client_default_currency')}
                                    currencies={currencies} 
                                    value={form.business_currency ?? ''}
                                    onChange={(v) => set('business_currency', v)}
                                    valueKey="currency"
                                />
                            </div>
                        </div>
                    </SectionCard>

                    {/* Project & Work Settings */}
                    <div className="space-y-6">
                        <SectionCard title={__('general.project_work_settings')} icon={Clock}>
                            <div className="space-y-5">
                                <Field label={__('general.default_overhead_cost')}>
                                    <Input
                                        id="overhead_cost_default"
                                        type="number"
                                        value={form.overhead_cost_default ?? ''}
                                        onChange={(e) => set('overhead_cost_default', e.target.value)}
                                        placeholder="150"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">{__('general.the_derived_overhead_hourly_rate_egp_is_cached_use_recalculate_below_if_needed')}</p>
                                </Field>
                                <Field label={__('general.max_devices_per_tenant_default')}>
                                    <Input
                                        id="max_devices_per_tenant"
                                        type="number"
                                        min="1"
                                        step="1"
                                        value={form.max_devices_per_tenant ?? ''}
                                        onChange={(e) => set('max_devices_per_tenant', parseInt(e.target.value) || 1)}
                                        placeholder="1"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">{__('general.the_global_default_number_of_allowed_devices_per_user_can_be_overridden_in_user_edit_page')}</p>
                                </Field>
                                <Toggle
                                    id="friday_work_allowed"
                                    label={__('general.friday_work_allowed')}
                                    checked={form.friday_work_allowed}
                                    onChange={(v) => set('friday_work_allowed', v)}
                                    description={__('general.when_disabled_friday_is_treated_as_a_non_working_day_for_timers_and_scheduling')}
                                />
                            </div>
                        </SectionCard>

                        {/* WhatsApp Integration */}
                        <SectionCard title={__('general.whatsapp_integration')} icon={MessageCircle}>
                            <SelectField
                                label={__('general.default_whatsapp_channel')}
                                name="whatsapp_default_channel_id"
                                value={form.whatsapp_default_channel_id ?? ''}
                                onChange={(v) => set('whatsapp_default_channel_id', v)}
                            >
                                <option value="">Round-robin (use channels in rotation)</option>
                                {(whatsappChannels ?? []).map((ch) => (
                                    <option key={ch.id} value={String(ch.id)}>
                                        {ch.name}{ch.phone_number ? ` (${ch.phone_number})` : ''}
                                    </option>
                                ))}
                            </SelectField>
                            <p className="text-xs text-gray-500 mt-2">{__('general.used_for_invoice_reminders_payment_confirmations_and_automated_notifications')}</p>
                        </SectionCard>

                        {/* AI Integrations */}
                        <SectionCard title={__('general.ai_integrations')} icon={Lightbulb}>
                            <Field label={__('general.gemini_api_keys')}>
                                <textarea
                                    id="gemini_api_keys"
                                    value={form.gemini_api_keys ?? ''}
                                    onChange={(e) => set('gemini_api_keys', e.target.value)}
                                    placeholder={__('general.paste_multiple_keys_separated_by_commas_e_g_aiza_aiza')}
                                    rows={3}
                                    className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black bg-white"
                                />
                                <p className="text-xs text-gray-500 mt-1">{__('general.provide_one_or_more_gemini_api_keys_comma_separated_for_load_balancing_ai_features_across_multiple_free_tier_accounts')}</p>
                            </Field>
                        </SectionCard>
                    </div>
                </div>

                {/* Payment & Checkout Settings */}
                <SectionCard title={__('general.payment_checkout_settings')} icon={CreditCard}>
                    <div className="space-y-5">
                        {/* Gateway toggles */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-5 pb-5 border-b border-gray-100">
                            <Toggle
                                id="ownwallet"
                                label={__('general.own_wallet_active')}
                                checked={form.ownwallet}
                                onChange={(v) => set('ownwallet', v)}
                            />
                            <Toggle
                                id="payoneer_active"
                                label={__('general.payoneer_active')}
                                checked={form.payoneer_active}
                                onChange={(v) => set('payoneer_active', v)}
                            />
                            <Toggle
                                id="paymob_active"
                                label={__('general.paymob_active')}
                                checked={form.paymob_active}
                                onChange={(v) => set('paymob_active', v)}
                            />
                        </div>

                        {/* Gateway credentials */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Field label={__('general.gumroad_url')}>
                                <Input
                                    id="gumroad"
                                    value={form.gumroad ?? ''}
                                    onChange={(e) => set('gumroad', e.target.value)}
                                    placeholder={__('general.https_gumroad_com')}
                                />
                            </Field>
                            <Field label={__('general.paymob_secret_token')}>
                                <Input
                                    id="paymob_token"
                                    value={form.paymob_token ?? ''}
                                    onChange={(e) => set('paymob_token', e.target.value)}
                                    placeholder={__('general.paymob_api_token')}
                                />
                            </Field>
                        </div>

                        {/* PayMob details */}
                        <div>
                            <p className="text-xs font-semibold uppercase text-gray-400 tracking-widest mb-3 border-b border-gray-100 pb-2">{__('general.paymob_integration_details')}</p>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <Field label={__('general.card_iframe_id')}>
                                    <Input
                                        id="paymob_card_iframe"
                                        value={form.paymob_card_iframe ?? ''}
                                        onChange={(e) => set('paymob_card_iframe', e.target.value)}
                                        placeholder={__('general.iframe_id')}
                                    />
                                </Field>
                                <Field label={__('general.card_integration_id')}>
                                    <Input
                                        id="paymob_card_integration"
                                        value={form.paymob_card_integration ?? ''}
                                        onChange={(e) => set('paymob_card_integration', e.target.value)}
                                        placeholder={__('general.integration_id')}
                                    />
                                </Field>
                                <Field label={__('general.wallet_integration_id')}>
                                    <Input
                                        id="paymob_wallet_integration"
                                        value={form.paymob_wallet_integration ?? ''}
                                        onChange={(e) => set('paymob_wallet_integration', e.target.value)}
                                        placeholder={__('general.wallet_integration_id')}
                                    />
                                </Field>
                            </div>
                        </div>
                    </div>
                </SectionCard>

                {/* Save button */}
                <div className="flex justify-center py-2">
                    <Button type="submit" size="lg" className="px-10 gap-2">
                        <Save className="h-4 w-4" />{__('general.save_all_settings')}</Button>
                </div>
            </form>

            <hr className="my-8 border-gray-200" />

            {/* Danger / Utility zone */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Bulk Hourly Rate Update */}
                <SectionCard title={__('general.bulk_hourly_rate_update')} icon={AlertTriangle}>
                    <p className="text-sm font-semibold text-red-600 mb-4 flex items-center gap-1">
                        <AlertTriangle className="h-4 w-4 shrink-0" />{__('general.warning_this_will_update_the_hourly_rate_for_all_clients_and_optionally_all_open_projects')}</p>
                    <form onSubmit={handleBulkPriceUpdate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                        <div className="grid grid-cols-2 gap-4">
                            <CurrencySelect
                                label="Currency"
                                currencies={currencies}
                                value={bulkCurrency}
                                onChange={setBulkCurrency}
                            />
                            <div className="flex flex-col justify-end">
                                <span className="text-sm font-medium text-gray-700">{__('admin.calculated_hourly_rate')}</span>
                                <div className="mt-1 h-9 flex items-center px-3 bg-gray-50 border border-gray-200 rounded-md text-gray-900 font-semibold tabular-nums">
                                    {computedRate}
                                </div>
                            </div>
                        </div>
                        </div>
                        <div className="bg-gray-50 p-3 rounded-md border border-gray-100 flex items-center justify-between">
                            <span className="text-sm font-medium text-gray-700">{__('admin.calculated_hourly_rate')}</span>
                            <span className="text-lg font-bold text-gray-900">{computedRate}</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="bulk_update_projects"
                                checked={updateProjects}
                                onChange={(e) => setUpdateProjects(e.target.checked)}
                                className="rounded border-gray-300 text-black focus:ring-black"
                            />
                            <Label htmlFor="bulk_update_projects" className="text-sm font-medium cursor-pointer">{__('general.update_open_projects_too')}</Label>
                        </div>
                        <Button type="submit" variant="destructive" className="w-full">{__('general.update_all')}</Button>
                    </form>
                </SectionCard>

                {/* Recalculate Overhead Rate */}
                <SectionCard title={__('general.overhead_hourly_rate')} icon={RefreshCw}>
                    <p className="text-sm font-medium text-gray-700 mb-4">{__('general.clears_the_daily_server_cache_and_recomputes_the_overhead_hourly_rate_egp_from_latest_cost_data_last_6_months')}</p>
                    <form onSubmit={(e) => {
                        e.preventDefault();
                        if (!confirm('Are you sure you want to recalculate the overhead hourly rate?')) return;
                        router.post(route('admin.settings.recalculate-overhead-hourly-rate'));
                    }} className="space-y-4">
                        <Button type="submit" variant="outline" className="w-full">{__('general.recalculate_rate')}</Button>
                    </form>
                </SectionCard>
            </div>
        </AdminSidebarLayout>
    );
}
