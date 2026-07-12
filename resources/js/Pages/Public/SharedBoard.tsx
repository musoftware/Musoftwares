import React, { useState, useMemo } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';
import { LayoutDashboard, CalendarDays, LogIn, Calendar as LucideCalendar } from 'lucide-react';
import ProjectBoard, { type BoardCard } from '@/Pages/Client/Projects/Components/ProjectBoard';
import CalendarSelector from '@/Components/CalendarSelector';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Props {
    project: {
        id: number;
        name: string;
        description?: string;
        status?: string;
        share_token: string;
        client_name?: string;
        currency?: any;
    };
    date: string;
    lanes: string[];
    cards: BoardCard[];
    activeDates: string[];
    hasEditAccess?: boolean;
}

type FilterKey = 'all' | 'note' | 'task' | 'report';

export default function SharedBoard({ project, date, lanes, cards, activeDates = [], hasEditAccess = false }: Props) {
    const day = parseISO(date);
    const [filter, setFilter] = useState<FilterKey>('all');
    const [calendarOpen, setCalendarOpen] = useState(false);
    const { auth } = usePage().props as any;
    const user = auth?.user;

    const counts = useMemo(
        () => ({
            all: cards.length,
            note: cards.filter((c) => c.type === 'note').length,
            task: cards.filter((c) => c.type === 'task').length,
            report: cards.filter((c) => c.type === 'report').length,
        }),
        [cards],
    );

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 antialiased">
            <Head title={`${project.name} · ${__('general.board')}`} />

            {/* Custom Premium Guest Top Nav */}
            <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm">
                <div className="mx-auto w-full px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="truncate text-base font-bold text-slate-900 sm:text-lg">
                                    {project.name}
                                </h1>
                                {project.client_name && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                        {__('general.board_client') || 'Client'}: {project.client_name}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                    <CalendarDays className="mr-1 h-3 w-3 text-indigo-400" />
                                    {format(day, 'MMMM d, yyyy')}
                                </span>
                            </div>
                        </div>

                        {/* Action buttons (Calendar, Login CTA, or Control Panel) */}
                        <div className="flex items-center gap-2">
                            {user && (
                                <button
                                    type="button"
                                    onClick={() => setCalendarOpen(true)}
                                    className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition-colors"
                                >
                                    <LucideCalendar className="h-3.5 w-3.5 text-indigo-500" />
                                    <span>{__('general.calendar') || 'Calendar'}</span>
                                </button>
                            )}

                            {user ? (
                                <Link
                                    href={user.isAdmin
                                        ? route('admin.projects.board', { project: project.id, date })
                                        : route('client.projects.calendar.date', { project: project.id, date })
                                    }
                                    className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-950 px-3 text-xs font-semibold text-white shadow hover:bg-slate-800 transition-colors"
                                >
                                    <LayoutDashboard className="h-3.5 w-3.5" />
                                    <span>{__('general.board_go_to_panel') || 'Go to Control Panel'}</span>
                                </Link>
                            ) : (
                                <Link
                                    href="/login"
                                    className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white shadow hover:bg-indigo-500 transition-colors"
                                >
                                    <LogIn className="h-3.5 w-3.5" />
                                    <span>{__('general.board_login_cta') || 'Login to Control Fully'}</span>
                                </Link>
                            )}

                            <div className="h-6 w-[1px] bg-slate-200 mx-1" />

                            {/* Top Filters (Restricted to specified date, no date nav) */}
                            <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                                {(['all', 'note', 'task', 'report'] as const).map((key) => {
                                    const count = counts[key];
                                    const isActive = filter === key;
                                    const label =
                                        key === 'all' ? __('general.all') :
                                        key === 'note' ? __('general.notes') :
                                        key === 'task' ? __('general.tasks') :
                                        __('general.reports');
                                    return (
                                        <button
                                            key={key}
                                            type="button"
                                            onClick={() => setFilter(key)}
                                            className={cn(
                                                'relative inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition-colors',
                                                isActive
                                                    ? 'bg-white text-slate-900 shadow-sm'
                                                    : 'text-slate-500 hover:text-slate-700'
                                            )}
                                        >
                                            <span>{label}</span>
                                            {count > 0 && (
                                                <span className={cn(
                                                    'rounded-full px-1.5 py-0 text-[10px] font-bold',
                                                    isActive ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-600'
                                                )}>
                                                    {count}
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main className="w-full px-4 py-6 sm:px-6 lg:px-8 max-w-none">
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={false}
                    readOnly={!hasEditAccess}
                    hideToolbar={!hasEditAccess}
                    externalFilter={filter}
                    guestMode={true}
                    shareToken={project.share_token}
                />
            </main>

            {/* Public Shared Board Calendar Selector */}
            <CalendarSelector
                open={calendarOpen}
                onOpenChange={setCalendarOpen}
                activeDates={activeDates}
                selectedDate={date}
                maxDate={format(new Date(), 'yyyy-MM-dd')}
                onSelectDate={(targetDate) => {
                    router.visit(route('shared-board.show', { token: project.share_token, date: targetDate }));
                }}
            />
        </div>
    );
}
