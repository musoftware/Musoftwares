import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Bell, Settings2 } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { NOTICES_MANAGER_OPEN_EVENT } from './NoticesManager';

export type RecurringNoticeToday = {
    id: number;
    title: string;
    message: string | null;
    type: 'info' | 'success' | 'warning' | 'danger';
};

const STORAGE_KEY = 'board_notices_collapsed';

const TYPE_BORDER: Record<RecurringNoticeToday['type'], string> = {
    info: 'border-l-blue-500',
    success: 'border-l-green-500',
    warning: 'border-l-amber-500',
    danger: 'border-l-red-500',
};

const TYPE_BADGE: Record<RecurringNoticeToday['type'], string> = {
    info: 'bg-blue-100 text-blue-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-red-100 text-red-700',
};

export default function BoardNoticesRail() {
    const notices = (usePage<any>().props.recurring_notices_today as RecurringNoticeToday[]) ?? [];

    const [collapsed, setCollapsed] = useState<boolean>(false);
    const [hydrated, setHydrated] = useState<boolean>(false);

    useEffect(() => {
        try {
            const stored = window.localStorage.getItem(STORAGE_KEY);
            if (stored === '1') {
                setCollapsed(true);
            }
        } catch {
            // ignore storage errors (private mode, etc.)
        }
        setHydrated(true);
    }, []);

    const toggleCollapsed = () => {
        const next = !collapsed;
        setCollapsed(next);
        try {
            window.localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
        } catch {
            // ignore
        }
    };

    // Never render the rail when there is nothing due today.
    if (notices.length === 0) {
        return null;
    }

    // Avoid hydration flicker: keep rail out of first paint until localStorage is read,
    // so the collapsed/expanded state matches what the user last chose.
    if (!hydrated) {
        return null;
    }

    if (collapsed) {
        return (
            <aside className="hidden lg:flex sticky top-0 h-screen w-10 shrink-0 flex-col items-center gap-3 border-r bg-white py-3">
                <button
                    type="button"
                    onClick={toggleCollapsed}
                    aria-label={__('general.expand')}
                    title={__('general.expand')}
                    className="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                >
                    <ChevronRight className="h-4 w-4" />
                </button>
                <span className="flex h-6 min-w-6 items-center justify-center rounded-full bg-slate-900 px-1.5 text-xs font-semibold text-white">
                    {notices.length}
                </span>
                <div className="mt-1 [writing-mode:vertical-rl] rotate-180 text-[10px] font-medium uppercase tracking-wider text-slate-400">
                    {__('general.todays_notices')}
                </div>
            </aside>
        );
    }

    return (
        <aside className="hidden lg:flex sticky top-0 h-screen w-72 shrink-0 flex-col border-r bg-white">
            <div className="flex items-center justify-between border-b px-4 py-3">
                <div className="flex items-center gap-2">
                    <Bell className="h-4 w-4 text-slate-700" />
                    <h2 className="text-sm font-semibold text-slate-900">
                        {__('general.todays_notices')}
                    </h2>
                    <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-900 px-1.5 text-[11px] font-semibold text-white">
                        {notices.length}
                    </span>
                </div>
                <button
                    type="button"
                    onClick={toggleCollapsed}
                    aria-label={__('general.collapse')}
                    title={__('general.collapse')}
                    className="flex h-7 w-7 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                >
                    <ChevronLeft className="h-4 w-4" />
                </button>
            </div>

            <div className="flex-1 space-y-3 overflow-y-auto p-3">
                {notices.map((notice) => (
                    <div
                        key={notice.id}
                        className={`rounded-md border border-l-4 ${TYPE_BORDER[notice.type]} bg-slate-50 p-3 shadow-sm`}
                    >
                        <div className="mb-1 flex items-start justify-between gap-2">
                            <h3 className="text-sm font-bold leading-snug text-slate-900">
                                {notice.title}
                            </h3>
                            <span
                                className={`shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase ${TYPE_BADGE[notice.type]}`}
                            >
                                {notice.type}
                            </span>
                        </div>
                        {notice.message && (
                            <p className="line-clamp-4 text-xs leading-relaxed text-slate-600">
                                {notice.message}
                            </p>
                        )}
                    </div>
                ))}
            </div>

            <div className="border-t p-3">
                <button
                    type="button"
                    onClick={() => window.dispatchEvent(new CustomEvent(NOTICES_MANAGER_OPEN_EVENT))}
                    className="flex w-full items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900"
                >
                    <Settings2 className="h-3.5 w-3.5" />
                    {__('general.manage_notices')}
                </button>
            </div>
        </aside>
    );
}
