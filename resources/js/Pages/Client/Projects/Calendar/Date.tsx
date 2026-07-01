import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, ChevronRight, CalendarDays } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProjectBoard, { type BoardCard } from '../Components/ProjectBoard';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Props {
    project: { id: number; name: string; hide_future_tasks?: boolean };
    date: string;
    lanes: string[];
    cards: BoardCard[];
    hideFuture: boolean;
}

export default function ProjectCalendarDate({ project, date, lanes, cards, hideFuture }: Props) {
    const day = parseISO(date);
    const today = new Date();
    const todayStr = format(today, 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');

    return (
        <AuthenticatedLayout>
            <Head title={`${project.name} · ${date}`} />
            <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <Link
                            href={route('client.projects.show', project.id)}
                            className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                        >
                            <ArrowLeft className="h-4 w-4" /> {project.name}
                        </Link>
                        <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                            <CalendarDays className="h-6 w-6 text-slate-400" />
                            {format(day, 'EEEE, MMM d, yyyy')}
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={route('client.projects.calendar.date', { project: project.id, date: prev })}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                            preserveScroll
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Link>
                        <Link
                            href={route('client.projects.calendar.date', { project: project.id, date: todayStr })}
                            preserveScroll
                            className={cn(
                                'inline-flex h-9 items-center justify-center rounded-lg border px-3 text-xs font-medium',
                                date === todayStr ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                            )}
                        >
                            {__('general.today')}
                        </Link>
                        <Link
                            href={route('client.projects.calendar.date', { project: project.id, date: next })}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                            preserveScroll
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    </div>
                </div>

                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={hideFuture}
                />
            </div>
        </AuthenticatedLayout>
    );
}
