import { __, getLoadedLocale } from '@/lib/i18n';

interface RecurringScheduleSource {
    recurring: string;
    recurring_times: number | string;
    recurring_times_week?: unknown;
    recurring_times_month?: unknown;
    recurring_times_year?: unknown;
}

const UNIT_LABEL_KEYS: Record<string, string> = {
    day: 'admin.recurring_unit_day',
    week: 'admin.recurring_unit_week',
    month: 'admin.recurring_unit_month',
    year: 'admin.recurring_unit_year',
};

const DETAIL_BY_UNIT: Record<string, { field: keyof RecurringScheduleSource; labelKey: string }> = {
    week: { field: 'recurring_times_week', labelKey: 'admin.recurring_schedule_on' },
    month: { field: 'recurring_times_month', labelKey: 'admin.recurring_schedule_on_day' },
    year: { field: 'recurring_times_year', labelKey: 'admin.recurring_schedule_on' },
};

/** Human readable schedule, e.g. "Every 2 month(s) on day [5]". */
export function formatRecurringSchedule(source: RecurringScheduleSource): string {
    const unitKey = UNIT_LABEL_KEYS[source.recurring];
    const unit = unitKey ? __(unitKey) : source.recurring;
    const base = __('admin.recurring_schedule_every', { count: source.recurring_times, unit });

    const detail = DETAIL_BY_UNIT[source.recurring];
    const detailValue = detail ? source[detail.field] : null;
    if (!detail || !detailValue) return base;

    return `${base} ${__(detail.labelKey, { value: String(detailValue) })}`;
}

/** Week day values stored by the server. Labels are localized for display only. */
export const WEEK_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

export const MONTH_DAYS = Array.from({ length: 31 }, (_, i) => i + 1);

const activeLocale = (): string => getLoadedLocale() || 'en';

/** 2000-01-02 was a Sunday, so index 0 maps to Sunday. */
function weekdayLabel(index: number): string {
    return new Date(2000, 0, 2 + index).toLocaleString(activeLocale(), { weekday: 'long' });
}

export function weekDayOptions(): { value: string; label: string }[] {
    return WEEK_DAYS.map((value, index) => ({ value, label: weekdayLabel(index) }));
}

/** Every "day-month" pair of a leap year (to support Feb 29), labelled "05 - March". */
export function yearDayOptions(): { val: string; label: string }[] {
    const locale = activeLocale();
    return Array.from({ length: 12 }, (_, monthIndex) => {
        const monthName = new Date(2024, monthIndex, 1).toLocaleString(locale, { month: 'long' });
        const daysInMonth = new Date(2024, monthIndex + 1, 0).getDate();
        return Array.from({ length: daysInMonth }, (_, i) => ({
            val: `${i + 1}-${monthIndex + 1}`,
            label: `${String(i + 1).padStart(2, '0')} - ${monthName}`,
        }));
    }).flat();
}
