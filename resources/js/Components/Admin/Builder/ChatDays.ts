import { getLoadedLocale } from '@/lib/i18n';
import { t } from '@/Components/Admin/SoftwareStore/t';

/** Chats split by the day of their last activity (Cairo time, "YYYY-MM-DD HH:MM:SS"), keeping the list order. */
export function groupByDay<T extends { active_at: string }>(items: T[]): { day: string; items: T[] }[] {
    return items.reduce<{ day: string; items: T[] }[]>((groups, item) => {
        const day = item.active_at.slice(0, 10);
        const last = groups[groups.length - 1];
        return last && last.day === day
            ? [...groups.slice(0, -1), { day, items: [...last.items, item] }]
            : [...groups, { day, items: [item] }];
    }, []);
}

const shiftDay = (day: string, by: number) => {
    const date = new Date(`${day}T12:00:00Z`);
    date.setUTCDate(date.getUTCDate() + by);
    return date.toISOString().slice(0, 10);
};

/** "Today", "Yesterday", or the weekday and date. `today` comes from the server in Cairo time. */
export function dayLabel(day: string, today: string): string {
    if (day === today) return t('Today', 'النهارده');
    if (day === shiftDay(today, -1)) return t('Yesterday', 'امبارح');
    const sameYear = day.slice(0, 4) === today.slice(0, 4);
    return new Date(`${day}T12:00:00Z`).toLocaleDateString(getLoadedLocale() === 'ar' ? 'ar-EG' : 'en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', ...(sameYear ? {} : { year: 'numeric' }), timeZone: 'UTC',
    });
}

/** "14:05" from the Cairo time string. */
export const timeOf = (dateTime: string) => dateTime.slice(11, 16);
