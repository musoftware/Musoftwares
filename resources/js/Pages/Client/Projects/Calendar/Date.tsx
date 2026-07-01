import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft, ChevronLeft, ChevronRight, CalendarDays, ListTodo, FileText,
    Paperclip, Wallet, PiggyBank, Clock, LayoutDashboard, Sparkles,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProjectBoard, { type BoardCard } from '../Components/ProjectBoard';
import { Breadcrumbs } from '@/Components/ui/Breadcrumbs';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Props {
    project: { id: number; name: string; hide_future_tasks?: boolean; status?: string; archived?: boolean; percentage?: number };
    date: string;
    lanes: string[];
    cards: BoardCard[];
    hideFuture: boolean;
    isAdmin?: boolean;
}

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    hold_on: 'bg-amber-100 text-amber-700 ring-amber-200',
    closed: 'bg-slate-200 text-slate-700 ring-slate-300',
};

export default function ProjectCalendarDate({ project, date, lanes, cards, hideFuture, isAdmin = false }: Props) {
    const day = parseISO(date);
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');
    const isToday = date === todayStr;
    const dateInputKey = `board-date-${project.id}`;

    const breadcrumbs = [
        { label: __('general.dashboard'), href: route('dashboard') },
        { label: __('general.my_projects'), href: route('client.projects.index') },
        { label: project.name, href: route('client.projects.show', project.id) },
        { label: format(day, 'EEE, MMM d, yyyy') },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`${project.name} · ${__('general.board')} · ${format(day, 'MMM d, yyyy')}`} />

            <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <Breadcrumbs items={breadcrumbs} />

                {/* Hero header */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="min-w-0 flex-1">
                            <Link
                                href={route('client.projects.show', project.id)}
                                className="mb-3 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 transition-colors hover:bg-slate-200"
                            >
                                <ArrowLeft className="h-3 w-3" /> {project.name}
                            </Link>

                            <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-slate-700 text-white shadow-sm">
                                    <LayoutDashboard className="h-4 w-4" />
                                </span>
                                {__('general.day_board')}
                            </h1>

                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                <span className="inline-flex items-center gap-1.5 text-slate-700">
                                    <CalendarDays className="h-3.5 w-3.5 text-slate-400" />
                                    <span className="font-medium">{format(day, 'EEEE, MMMM d, yyyy')}</span>
                                </span>
                                {project.status && (
                                    <span className={cn('rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize ring-1 ring-inset', STATUS_STYLES[project.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200')}>
                                        {project.status?.replace('_', ' ')}
                                    </span>
                                )}
                                {project.archived && (
                                    <span className="rounded-full bg-slate-200 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                        {__('general.archived')}
                                    </span>
                                )}
                                {typeof project.percentage === 'number' && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Clock className="h-3.5 w-3.5 text-slate-400" />
                                        {Math.round(project.percentage)}% {__('general.completion')}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 self-start lg:self-center">
                            {isAdmin && (
                                <Link
                                    href={route('client.projects.calendar.date', { project: project.id, date: prev })}
                                    preserveScroll
                                    aria-label={__('general.previous_day')}
                                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Link>
                            )}

                            <div
                                className={cn(
                                    'inline-flex h-10 items-center gap-2 rounded-xl border px-3 text-sm font-medium',
                                    isToday
                                        ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                        : 'border-slate-200 bg-white text-slate-700',
                                )}
                            >
                                <CalendarDays className="h-4 w-4" />
                                <span>{format(day, 'EEE, MMM d')}</span>
                                {isToday && (
                                    <span className="rounded-full bg-white/20 px-1.5 py-0 text-[10px] font-bold uppercase tracking-wider">{__('general.today')}</span>
                                )}
                            </div>

                            {isAdmin && (
                                <Link
                                    href={route('client.projects.calendar.date', { project: project.id, date: next })}
                                    preserveScroll
                                    aria-label={__('general.next_day')}
                                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            )}

                            {!isAdmin && !isToday && (
                                <Link
                                    href={route('client.projects.calendar.date', { project: project.id, date: todayStr })}
                                    preserveScroll
                                    className="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold uppercase tracking-wide text-slate-700 transition-colors hover:bg-slate-50"
                                >
                                    {__('general.today')}
                                </Link>
                            )}

                            {isAdmin && (
                                <form
                                    className="hidden sm:block"
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const v = (document.getElementById(dateInputKey) as HTMLInputElement | null)?.value;
                                        if (v) {
                                            window.location.href = route('client.projects.calendar.date', { project: project.id, date: v });
                                        }
                                    }}
                                >
                                    <input
                                        id={dateInputKey}
                                        type="date"
                                        defaultValue={date}
                                        className="h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                                    />
                                </form>
                            )}
                        </div>
                    </div>

                    {/* Friendly client welcome strip */}
                    <div className="flex items-start gap-3 border-t border-slate-100 bg-gradient-to-r from-slate-50 to-white p-4">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-white">
                            <Sparkles className="h-4 w-4" />
                        </div>
                        <div className="flex-1 text-sm text-slate-600">
                            <p className="font-semibold text-slate-800">{__('general.board_welcome_title')}</p>
                            <p className="mt-0.5 text-xs text-slate-500">{__('general.board_welcome_body')}</p>
                        </div>
                    </div>
                </div>

                {/* Quick navigation row */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <QuickTile
                        href={route('client.projects.show', project.id)}
                        icon={LayoutDashboard}
                        label={__('general.overview')}
                        accent="bg-slate-900 text-white"
                        filled
                    />
                    <QuickTile
                        href={route('client.projects.tasks.index', project.id)}
                        icon={ListTodo}
                        label={__('general.tasks')}
                        accent="bg-sky-50 text-sky-700"
                    />
                    <QuickTile
                        href={route('client.projects.files.index', project.id)}
                        icon={Paperclip}
                        label={__('general.files')}
                        accent="bg-amber-50 text-amber-700"
                    />
                    <QuickTile
                        href={route('client.projects.calendar.date', { project: project.id, date: todayStr })}
                        icon={CalendarDays}
                        label={__('general.day_board')}
                        accent="bg-emerald-50 text-emerald-700"
                    />
                </div>

                {/* The shared board canvas */}
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={hideFuture}
                />

                <p className="text-center text-xs text-slate-400">{__('general.board_persistence_hint')}</p>
            </div>
        </AuthenticatedLayout>
    );
}

function QuickTile({
    href, icon: Icon, label, accent, filled = false,
}: {
    href: string; icon: React.ElementType; label: string; accent: string; filled?: boolean;
}) {
    return (
        <Link
            href={href}
            className={cn(
                'group flex items-center gap-3 rounded-xl border p-4 transition-all hover:-translate-y-0.5 hover:shadow-md',
                filled
                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm hover:bg-slate-800'
                    : 'border-slate-200 bg-white hover:border-slate-300',
            )}
        >
            <div className={cn('flex h-10 w-10 items-center justify-center rounded-lg', accent)}>
                <Icon className="h-5 w-5" />
            </div>
            <p className={cn('flex-1 text-sm font-semibold', filled ? 'text-white' : 'text-slate-900')}>{label}</p>
            <ChevronRight className={cn('h-4 w-4 transition-colors', filled ? 'text-white/60' : 'text-slate-300 group-hover:text-slate-600')} />
        </Link>
    );
}
