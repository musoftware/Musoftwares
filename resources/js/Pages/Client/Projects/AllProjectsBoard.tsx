import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, ChevronLeft, ChevronRight, CalendarDays,
    LayoutDashboard, Calendar as LucideCalendar
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
            <Head title={`${__('general.all_projects_board') || 'All Projects Board'} · ${format(day, 'MMM d, yyyy')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto space-y-4">
                        <Breadcrumbs items={breadcrumbs} />

                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div className="space-y-1.5">
                                <h1 className="flex items-center gap-3 text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#0071e3]/10 text-[#0071e3]">
                                        <LayoutDashboard className="h-5 w-5" />
                                    </span>
                                    {__('general.all_projects_board') || 'All Projects Board'}
                                </h1>
                                <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                    {__('admin.all_projects_board_intro') || 'View and manage board items across all your active projects in one place.'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-6">
                    
                    {/* View Switcher Pills */}
                    <div className="flex items-center gap-2 border-b border-black/5 pb-4">
                        <Link
                            href={route('client.projects.index')}
                            className="px-4 py-2 rounded-full text-xs font-semibold bg-white text-[#1d1d1f]/70 border border-black/5 hover:bg-[#f5f5f7] hover:text-[#1d1d1f] transition-colors"
                        >
                            {__('general.projects_list') || 'Projects List'}
                        </Link>
                        <Link
                            href={route('client.projects.all-projects-board.index')}
                            className="px-4 py-2 rounded-full text-xs font-semibold bg-[#1d1d1f] text-white shadow-xs flex items-center gap-1.5"
                        >
                            <CalendarDays className="w-3.5 h-3.5" />
                            <span>{__('general.all_projects_board') || 'All Projects Board'}</span>
                        </Link>
                    </div>

                    {/* Date Controls Bar */}
                    <div className="flex flex-wrap items-center justify-between gap-4 rounded-[20px] border border-black/5 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => goToDate(prev)}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-black/10 bg-[#f5f5f7] text-[#1d1d1f] transition-colors hover:bg-black/5 cursor-pointer"
                                title={__('general.previous_day') || 'Previous Day'}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>

                            <button
                                onClick={() => setCalendarOpen(!calendarOpen)}
                                className="inline-flex h-9 items-center gap-2 rounded-xl border border-black/10 bg-white px-3.5 text-xs font-bold text-[#1d1d1f] transition-colors hover:bg-[#f5f5f7] cursor-pointer"
                            >
                                <LucideCalendar className="h-3.5 w-3.5 text-[#0071e3]" />
                                {formatDate(date)}
                            </button>

                            <button
                                onClick={() => goToDate(next)}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-black/10 bg-[#f5f5f7] text-[#1d1d1f] transition-colors hover:bg-black/5 cursor-pointer"
                                title={__('general.next_day') || 'Next Day'}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>

                            {!isToday && (
                                <button
                                    onClick={() => goToDate(todayStr)}
                                    className="ms-2 inline-flex h-9 items-center rounded-full bg-[#1d1d1f] px-3.5 text-xs font-semibold text-white transition-colors hover:bg-black cursor-pointer"
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

            </div>
        </AuthenticatedLayout>
    );
}
