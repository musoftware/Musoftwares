import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, ChevronLeft, ChevronRight, CalendarDays, ListTodo, FileText,
    Paperclip, Clock, LayoutDashboard, Calendar as LucideCalendar
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProjectBoard, { type BoardCard } from './Components/ProjectBoard';
import { Breadcrumbs } from '@/Components/ui/Breadcrumbs';
import { format, parseISO } from 'date-fns';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import CalendarSelector from '@/Components/CalendarSelector';

interface Props {
    date: string;
    lanes: string[];
    cards: BoardCard[];
    categories: any[];
    isAdmin?: boolean;
    projects: { id: number; name: string }[];
}

export default function AllProjectsBoard({ date, lanes, cards, categories, isAdmin = false, projects = [] }: Props) {
    const day = parseISO(date);
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');
    const isToday = date === todayStr;

    const [calendarOpen, setCalendarOpen] = useState(false);

    const goToDate = (target: string) => {
        if (!target || target === date) return;
        router.visit(route('client.projects.all-projects-board.date', { date: target }), { preserveScroll: true });
    };

    const breadcrumbs = [
        { label: __('general.dashboard'), href: route('dashboard') },
        { label: __('general.my_projects'), href: route('client.projects.index') },
        { label: __('general.all_projects_board') || 'All Projects Board' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.all_projects_board') || 'All Projects Board'} · ${format(day, 'MMM d, yyyy')}`} />

            <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <Breadcrumbs items={breadcrumbs} />

                {/* Hero header */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="min-w-0 flex-1">
                            <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-slate-700 text-white shadow-sm">
                                    <LayoutDashboard className="h-4 w-4" />
                                </span>
                                {__('general.all_projects_board') || 'All Projects Board'}
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                {__('admin.all_projects_board_intro') || 'View and manage board items across all your active projects in one place.'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Tabs Navigation */}
                <div className="border-b border-slate-200">
                    <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                        <Link
                            href={route('client.projects.index')}
                            className="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-slate-500 hover:border-slate-300 hover:text-slate-700 transition-colors"
                        >
                            {__('general.projects_list') || 'Projects List'}
                        </Link>
                        <Link
                            href={route('client.projects.all-projects-board.index')}
                            className="border-b-2 border-slate-900 py-4 px-1 text-sm font-semibold text-slate-950 transition-colors flex items-center gap-2"
                        >
                            <CalendarDays className="w-4 h-4" />
                            {__('general.all_projects_board') || 'All Projects Board'}
                        </Link>
                    </nav>
                </div>

                {/* Date Controls */}
                <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="flex items-center gap-1.5">
                        <button
                            onClick={() => goToDate(prev)}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 cursor-pointer"
                            title={__('general.previous_day') || 'Previous Day'}
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </button>

                        <button
                            onClick={() => setCalendarOpen(!calendarOpen)}
                            className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 cursor-pointer"
                        >
                            <LucideCalendar className="h-4 w-4 opacity-60" />
                            {formatDate(date)}
                        </button>

                        <button
                            onClick={() => goToDate(next)}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 cursor-pointer"
                            title={__('general.next_day') || 'Next Day'}
                        >
                            <ChevronRight className="h-4 w-4" />
                        </button>

                        {!isToday && (
                            <button
                                onClick={() => goToDate(todayStr)}
                                className="ms-2 inline-flex h-9 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white transition-colors hover:bg-slate-800 cursor-pointer"
                            >
                                {__('general.today') || 'Today'}
                            </button>
                        )}
                    </div>
                </div>

                {/* Calendar Dialog */}
                <CalendarSelector
                    open={calendarOpen}
                    onOpenChange={setCalendarOpen}
                    activeDates={[]}
                    selectedDate={date}
                    onSelectDate={(d) => {
                        goToDate(d);
                        setCalendarOpen(false);
                    }}
                />

                {/* Consolidated Board */}
                <div className="relative">
                    <ProjectBoard
                        projectId="all"
                        date={date}
                        lanes={lanes}
                        initialCards={cards}
                        categories={categories}
                        isConsolidated={true}
                        projects={projects}
                        hideFuture={false}
                        readOnly={false}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
