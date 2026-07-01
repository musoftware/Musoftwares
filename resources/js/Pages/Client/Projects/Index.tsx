import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { FolderKanban, ListTodo, FileText, Paperclip, CalendarDays } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney, formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface ProjectItem {
    id: number;
    name: string;
    status: string;
    archived: boolean;
    percentage: number;
    date_start: string | null;
    date_end: string | null;
    budget: string;
    project_balance: string;
    total_paid: string;
    hide_future_tasks: boolean;
    currency: { currency: string; symbol: string; string_format?: string } | null;
    counts: { tasks: number; reports: number; files: number };
}

interface Props {
    projects: { data: ProjectItem[]; links: any[]; meta?: any };
}

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700',
    hold_on: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-200 text-slate-700',
};

export default function ProjectsIndex({ projects }: Props) {
    const list = projects?.data ?? [];
    const totalTasks = list.reduce((s, p) => s + (p.counts?.tasks ?? 0), 0);

    return (
        <AuthenticatedLayout>
            <Head title={__('general.my_projects')} />
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight text-slate-900">
                        <FolderKanban className="h-7 w-7 text-slate-400" />
                        {__('general.my_projects')}
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">{__('general.projects_portal_intro')}</p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <MetricCard label={__('general.total_projects')} value={list.length} icon={FolderKanban} />
                    <MetricCard label={__('general.open_tasks')} value={totalTasks} icon={ListTodo} />
                    <MetricCard label={__('general.reports')} value={list.reduce((s, p) => s + (p.counts?.reports ?? 0), 0)} icon={FileText} />
                </div>

                {list.length === 0 ? (
                    <EmptyState
                        icon={FolderKanban}
                        title={__('general.no_projects_yet')}
                        description={__('general.no_projects_yet_desc')}
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {list.map((project) => (
                            <Card key={project.id} className="flex flex-col overflow-hidden rounded-xl border border-slate-200 shadow-sm transition-shadow hover:shadow-md">
                                <CardContent className="flex flex-1 flex-col p-5">
                                    <div className="mb-3 flex items-start justify-between gap-2">
                                        <Link
                                            href={route('client.projects.show', project.id)}
                                            className="text-lg font-semibold text-slate-900 hover:underline"
                                        >
                                            {project.name}
                                        </Link>
                                        <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLES[project.status] ?? 'bg-slate-100 text-slate-600'}`}>
                                            {project.status?.replace('_', ' ')}
                                        </span>
                                    </div>

                                    {/* Progress */}
                                    <div className="mb-4">
                                        <div className="mb-1 flex items-center justify-between text-xs text-slate-500">
                                            <span>{__('general.progress')}</span>
                                            <span className="font-semibold text-slate-700">{Math.round(project.percentage)}%</span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div className="h-full rounded-full bg-emerald-500" style={{ width: `${Math.min(100, Math.max(0, project.percentage))}%` }} />
                                        </div>
                                    </div>

                                    {/* Budget vs paid */}
                                    <div className="mb-4 grid grid-cols-2 gap-2 text-sm">
                                        <div className="rounded-lg bg-slate-50 p-2">
                                            <p className="text-[11px] uppercase tracking-wide text-slate-400">{__('general.budget')}</p>
                                            <p className="font-mono font-semibold text-slate-800">{formatMoney(project.budget, project.currency)}</p>
                                        </div>
                                        <div className="rounded-lg bg-emerald-50 p-2">
                                            <p className="text-[11px] uppercase tracking-wide text-emerald-500/70">{__('general.paid')}</p>
                                            <p className="font-mono font-semibold text-emerald-700">{formatMoney(project.total_paid, project.currency)}</p>
                                        </div>
                                    </div>

                                    {project.date_start && (
                                        <p className="mb-4 text-xs text-slate-400">
                                            {formatDate(project.date_start)} → {project.date_end ? formatDate(project.date_end) : '…'}
                                        </p>
                                    )}

                                    <div className="mt-auto flex items-center gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                                        <span className="inline-flex items-center gap-1"><ListTodo className="h-3.5 w-3.5" /> {project.counts.tasks}</span>
                                        <span className="inline-flex items-center gap-1"><FileText className="h-3.5 w-3.5" /> {project.counts.reports}</span>
                                        <span className="inline-flex items-center gap-1"><Paperclip className="h-3.5 w-3.5" /> {project.counts.files}</span>
                                        <Link
                                            href={route('client.projects.calendar.date', { project: project.id, date: new Date().toISOString().slice(0, 10) })}
                                            className="ms-auto inline-flex items-center gap-1 rounded-lg bg-slate-900 px-2.5 py-1 font-medium text-white hover:bg-slate-800"
                                        >
                                            <CalendarDays className="h-3.5 w-3.5" /> {__('general.board')}
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {projects?.links && projects.links.length > 3 && (
                    <div className="flex justify-center gap-1">
                        {projects.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                preserveScroll
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`rounded-md border px-3 py-1.5 text-sm ${link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
