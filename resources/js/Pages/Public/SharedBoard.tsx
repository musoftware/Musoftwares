import React, { useState, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { LayoutDashboard, CalendarDays } from 'lucide-react';
import ProjectBoard, { type BoardCard } from '@/Pages/Client/Projects/Components/ProjectBoard';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Props {
    project: {
        id: number;
        name: string;
        description?: string;
        status?: string;
        client_name?: string;
        currency?: any;
    };
    date: string;
    lanes: string[];
    cards: BoardCard[];
}

type FilterKey = 'all' | 'note' | 'task' | 'report';

export default function SharedBoard({ project, date, lanes, cards }: Props) {
    const day = parseISO(date);
    const [filter, setFilter] = useState<FilterKey>('all');

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
            </header>

            <main className="w-full px-4 py-6 sm:px-6 lg:px-8 max-w-none">
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={false}
                    readOnly={true}
                    hideToolbar={true}
                    externalFilter={filter}
                />
            </main>
        </div>
    );
}
