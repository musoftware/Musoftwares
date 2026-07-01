import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, CalendarDays, ListTodo, FileText, Paperclip, Wallet, PiggyBank, Clock } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import ProjectBoard, { type BoardCard } from '@/Pages/Client/Projects/Components/ProjectBoard';
import { format, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import type { BoardProject } from '@/types/project';

interface Props {
    project: BoardProject;
    date: string;
    lanes: string[];
    cards: BoardCard[];
}

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700',
    hold_on: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-200 text-slate-700',
};

export default function AdminProjectBoard({ project, date, lanes, cards }: Props) {
    const day = parseISO(date);
    const todayStr = format(new Date(), 'yyyy-MM-dd');
    const prev = format(new Date(day.getTime() - 86400000), 'yyyy-MM-dd');
    const next = format(new Date(day.getTime() + 86400000), 'yyyy-MM-dd');

    return (
        <AdminSidebarLayout title={`${project.name} · ${__('general.board')}`} header={`${project.name} — ${__('general.board')}`}>
            <Head title={`${project.name} · Board`} />
            <div className="space-y-6 p-6">
                {/* Header + date nav */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Link href={route('admin.projects.index')} className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                            <ChevronLeft className="h-4 w-4" /> {__('general.back_to_projects')}
                        </Link>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900">{project.name}</h1>
                            <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize', STATUS_STYLES[project.status ?? ''] ?? 'bg-slate-100 text-slate-600')}>
                                {project.status?.replace('_', ' ')}
                            </span>
                            {project.archived && (
                                <span className="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">
                                    {__('general.archived')}
                                </span>
                            )}
                            {project.client_name && (
                                <span className="text-sm text-slate-400">· {project.client_name}</span>
                            )}
                            {project.owner_name && (
                                <span className="text-sm text-slate-400">· {__('general.owner')}: {project.owner_name}</span>
                            )}
                        </div>
                        {project.description && (
                            <p className="mt-2 max-w-2xl text-sm text-slate-500 line-clamp-2">{project.description}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Link href={route('admin.projects.board', { project: project.id, date: prev })} preserveScroll className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                            <ChevronLeft className="h-4 w-4" />
                        </Link>
                        <Link
                            href={route('admin.projects.board', { project: project.id, date: todayStr })}
                            preserveScroll
                            className={cn(
                                'inline-flex h-9 items-center gap-1 rounded-lg border px-3 text-xs font-medium',
                                date === todayStr ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                            )}
                        >
                            <CalendarDays className="h-3.5 w-3.5" /> {format(day, 'EEE, MMM d, yyyy')}
                        </Link>
                        <Link href={route('admin.projects.board', { project: project.id, date: next })} preserveScroll className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    </div>
                </div>

                {/* Quick links + summary */}
                <div className="flex flex-wrap items-center gap-2 text-sm">
                    <span className="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600"><ListTodo className="h-4 w-4" /> {project.counts.tasks} {__('general.tasks')}</span>
                    <Link href={route('admin.projects.reports.index', project.id)} className="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600 hover:bg-slate-200"><FileText className="h-4 w-4" /> {project.counts.reports} {__('general.reports')}</Link>
                    <Link href={route('admin.projects.files.index', project.id)} className="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600 hover:bg-slate-200"><Paperclip className="h-4 w-4" /> {project.counts.files} {__('general.files')}</Link>
                    <span className="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-emerald-700"><PiggyBank className="h-4 w-4" /> {__('general.budget')}</span>
                    <span className="inline-flex items-center gap-1 rounded-lg bg-slate-50 px-2.5 py-1 text-slate-600"><Wallet className="h-4 w-4" /> {__('general.paid')}</span>
                    <span className="inline-flex items-center gap-1 rounded-lg bg-slate-50 px-2.5 py-1 text-slate-600"><Clock className="h-4 w-4" /> {Math.round(project.percentage)}%</span>
                </div>

                {/* The shared board (canvas + lanes + minimap) */}
                <ProjectBoard
                    projectId={project.id}
                    date={date}
                    lanes={lanes}
                    initialCards={cards}
                    hideFuture={false}
                />
            </div>
        </AdminSidebarLayout>
    );
}
