import React, { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { Save, ArrowLeft } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import InputError from '@/Components/InputError';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';

export type RecurringKind = 'salary' | 'invoice';

export interface RecurringScheduleValues {
    [key: string]: any;
    user_id: string | number;
    title: string;
    amount: string;
    currency: string | number;
    reason: string;
    start_date: string;
    recurring: 'day' | 'week' | 'month' | 'year';
    recurring_times: number;
    recurring_times_week: string[];
    recurring_times_month: string[];
    recurring_times_year: string[];
}

export const EMPTY_RECURRING_FORM: RecurringScheduleValues = {
    user_id: '',
    title: '',
    amount: '',
    currency: '',
    reason: '',
    start_date: new Date().toISOString().slice(0, 10),
    recurring: 'month',
    recurring_times: 1,
    recurring_times_week: [],
    recurring_times_month: [],
    recurring_times_year: [],
};

const WEEK_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const MONTH_DAYS = Array.from({ length: 31 }, (_, i) => i + 1);
const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const INTERVAL_OPTIONS = Array.from({ length: 30 }, (_, i) => i + 1);

function getYearDaysList() {
    const list: { val: string; label: string }[] = [];
    MONTH_NAMES.forEach((month, mIdx) => {
        const daysInMonth = new Date(2024, mIdx + 1, 0).getDate();
        for (let d = 1; d <= daysInMonth; d++) {
            list.push({
                val: `${d}-${mIdx + 1}`,
                label: `${d.toString().padStart(2, '0')} - ${month}`,
            });
        }
    });
    return list;
}

export interface RecurringScheduleFormProps {
    kind: RecurringKind;
    mode: 'create' | 'edit';
    initialValues: RecurringScheduleValues;
    currencies: any[];
    users: any[];
    userOptions?: { value: string | number; label: string }[];
    searchUsersEndpoint?: string;
    errors?: Record<string, string>;
    submitting?: boolean;
    onSubmit: (values: RecurringScheduleValues) => void;
    backHref: string;
    backLabel?: string;
}

export function RecurringScheduleForm({
    kind,
    mode,
    initialValues,
    currencies,
    users,
    userOptions,
    searchUsersEndpoint,
    errors = {},
    submitting = false,
    onSubmit,
    backHref,
    backLabel,
}: RecurringScheduleFormProps) {
    const [form, setForm] = React.useState<RecurringScheduleValues>(initialValues);

    const yearDaysList = useMemo(() => getYearDaysList(), []);

    const titleKey = kind === 'salary' ? 'general.add_recurring_salary' : 'general.add_recurring_invoice';
    const editTitleKey = kind === 'salary' ? 'general.edit_recurring_salary_details' : 'general.edit_recurring_invoice_details';
    const subtitleKey = kind === 'salary'
        ? 'general.create_a_repeated_salary_payment_schedule_for_a_team_member'
        : 'general.create_a_repeated_salary_payment_schedule_for_a_team_member';

    const userLabel = kind === 'salary' ? __('general.employee_user') : __('general.user_user');
    const selectUserPlaceholder = kind === 'salary' ? __('general.select_employee') : __('general.select_user');

    const builtUserOptions = useMemo(() => {
        if (userOptions?.length) return userOptions;
        return users.map((u: any) => ({
            value: u.id,
            label: u.name ? `${u.name} (${u.email})` : u.email,
        }));
    }, [userOptions, users]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit({
            ...form,
            user_id: parseInt(String(form.user_id)) || form.user_id,
            currency: parseInt(String(form.currency)) || form.currency,
        });
    };

    const updateField = <K extends keyof RecurringScheduleValues>(key: K, value: RecurringScheduleValues[K]) => {
        setForm((prev) => ({ ...prev, [key]: value }));
    };

    const toggleMulti = (
        key: 'recurring_times_week' | 'recurring_times_month' | 'recurring_times_year',
        options: HTMLCollection,
    ) => {
        const vals: string[] = [];
        for (let i = 0; i < options.length; i++) {
            const opt = options[i] as HTMLOptionElement;
            if (opt.selected) vals.push(opt.value);
        }
        updateField(key, vals as any);
    };

    return (
        <form onSubmit={handleSubmit} className="p-6 space-y-6">
            <div className="space-y-2">
                <Label htmlFor="user_id">{userLabel}</Label>
                <PremiumCombobox
                    value={form.user_id ? String(form.user_id) : null}
                    onChange={(val) => updateField('user_id', val as any)}
                    options={builtUserOptions && builtUserOptions.length > 0 ? (builtUserOptions as any) : users.map((u: any) => ({ value: String(u.id), label: `${u.name} (${u.email})` }))}
                    asyncEndpoint={searchUsersEndpoint || undefined}
                    searchParam="q"
                    placeholder={selectUserPlaceholder}
                    searchPlaceholder={`${__('general.search') || 'Search'} ${userLabel.toLowerCase()}...`}
                    emptyText={__('general.no_results') || 'No clients found.'}
                />
                <InputError message={errors.user_id} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="title">{__('general.title_description')}</Label>
                <Input
                    id="title"
                    required
                    value={form.title}
                    onChange={(e) => updateField('title', e.target.value)}
                    placeholder={__('general.e_g_monthly_salary')}
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                    <Label htmlFor="amount">{__('general.amount')}</Label>
                    <Input
                        id="amount"
                        type="number"
                        step="any"
                        required
                        value={form.amount}
                        onChange={(e) => updateField('amount', e.target.value)}
                        placeholder="0.00"
                    />
                    <InputError message={errors.amount} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="currency">{__('general.currency')}</Label>
                    <select
                        id="currency"
                        required
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                        value={form.currency}
                        onChange={(e) => updateField('currency', e.target.value)}
                    >
                        <option value="">{__('general.select_currency') || 'Select currency'}</option>
                        {currencies.map((c: any) => (
                            <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>
                        ))}
                    </select>
                    <InputError message={errors.currency} />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="reason">Note / Custom Reason (Optional)</Label>
                <Textarea
                    id="reason"
                    rows={2}
                    value={form.reason}
                    onChange={(e) => updateField('reason', e.target.value)}
                    placeholder={__('general.e_g_senior_backend_dev_rate')}
                />
                <InputError message={errors.reason} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="start_date">{__('general.start_date')}</Label>
                <Input
                    id="start_date"
                    type="date"
                    required
                    value={form.start_date}
                    onChange={(e) => updateField('start_date', e.target.value)}
                />
                <InputError message={errors.start_date} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                    <Label htmlFor="frequency">{__('general.frequency')}</Label>
                    <select
                        id="frequency"
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                        value={form.recurring}
                        onChange={(e) => updateField('recurring', e.target.value as any)}
                    >
                        <option value="day">{__('general.daily')}</option>
                        <option value="week">{__('general.weekly')}</option>
                        <option value="month">{__('general.monthly')}</option>
                        <option value="year">{__('general.annually')}</option>
                    </select>
                </div>
                <div className="space-y-2">
                    <Label htmlFor="interval">Interval (Every N)</Label>
                    <select
                        id="interval"
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                        value={form.recurring_times}
                        onChange={(e) => updateField('recurring_times', parseInt(e.target.value) || 1)}
                    >
                        {INTERVAL_OPTIONS.map((num) => (
                            <option key={num} value={num}>{num}</option>
                        ))}
                    </select>
                </div>
            </div>

            {form.recurring === 'week' && (
                <div className="space-y-2">
                    <Label htmlFor="week-days">{__('general.specific_week_days')}</Label>
                    <select
                        id="week-days"
                        multiple
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                        value={form.recurring_times_week}
                        onChange={(e) => toggleMulti('recurring_times_week', e.target.selectedOptions)}
                    >
                        {WEEK_DAYS.map((wd) => <option key={wd} value={wd}>{wd}</option>)}
                    </select>
                    <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                    <InputError message={errors.recurring_times_week} />
                </div>
            )}

            {form.recurring === 'month' && (
                <div className="space-y-2">
                    <Label htmlFor="month-days">{__('general.specific_month_days')}</Label>
                    <select
                        id="month-days"
                        multiple
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                        value={form.recurring_times_month}
                        onChange={(e) => toggleMulti('recurring_times_month', e.target.selectedOptions)}
                    >
                        {MONTH_DAYS.map((d) => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                    </select>
                    <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                    <InputError message={errors.recurring_times_month} />
                </div>
            )}

            {form.recurring === 'year' && (
                <div className="space-y-2">
                    <Label htmlFor="year-days">{__('general.specific_year_dates')}</Label>
                    <select
                        id="year-days"
                        multiple
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                        value={form.recurring_times_year}
                        onChange={(e) => toggleMulti('recurring_times_year', e.target.selectedOptions)}
                    >
                        {yearDaysList.map((yd) => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                    </select>
                    <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                    <InputError message={errors.recurring_times_year} />
                </div>
            )}

            <div className="flex gap-4 pt-4 border-t">
                <Button type="submit" disabled={submitting} className="bg-black hover:bg-slate-800 text-white flex items-center gap-2">
                    <Save className="w-4 h-4" />
                    {mode === 'create'
                        ? (kind === 'salary' ? __('general.create_recurring_salary') : __('general.create_recurring_invoice'))
                        : __('general.save_changes')}
                </Button>
                <Link href={backHref}>
                    <Button type="button" variant="outline">{__('general.cancel')}</Button>
                </Link>
            </div>
        </form>
    );
}

function toArray(value: unknown): string[] {
    if (Array.isArray(value)) return value.map((v) => String(v));
    if (value == null || value === '') return [];
    if (typeof value === 'string') return value.split(',').map((v) => v.trim()).filter(Boolean);
    return [String(value)];
}

export function formatScheduleSummary(s: RecurringScheduleValues): string {
    const weekDays = toArray(s.recurring_times_week);
    const monthDays = toArray(s.recurring_times_month);
    const yearDays = toArray(s.recurring_times_year);
    let scheduleStr = `Every ${s.recurring_times} ${s.recurring}(s)`;
    if (s.recurring === 'week' && weekDays.length) {
        scheduleStr += ` on [${weekDays.join(', ')}]`;
    } else if (s.recurring === 'month' && monthDays.length) {
        scheduleStr += ` on day [${monthDays.join(', ')}]`;
    } else if (s.recurring === 'year' && yearDays.length) {
        scheduleStr += ` on [${yearDays.join(', ')}]`;
    }
    return scheduleStr;
}

export default RecurringScheduleForm;