import { usePage } from '@inertiajs/react';
import { CalendarDays, FileText, ListTodo, Paperclip, Plus } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, TextAction } from '@/Components/Client/ClientPage';
import SafeLink from '@/Components/SafeLink';
import { ProjectBudgetRow } from '@/Components/ProjectBudgetRow';
import Pagination from '@/Components/Pagination';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface ProjectItem {
    id: number;
    name: string;
    status: string;
    archived: boolean;
    percentage: number;
    date_start: string | null;
    date_end: string | null;
    budget: string;
    total_paid: string;
    hide_future_tasks: boolean;
    currency: { currency: string; symbol: string; string_format?: string } | null;
    counts: { tasks: number; reports: number; files: number };
}

/** The client's projects: one card each with its status, progress and what is inside. */
export default function ProjectsIndex({ projects }: { projects: { data: ProjectItem[]; links: any[]; meta?: any } }) {
    const { locale } = usePage().props as any;
    const list = projects?.data ?? [];
    const formatDate = (date: string | null) =>
        date ? new Date(date.slice(0, 10) + 'T00:00:00').toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    return (
        <ClientPage
            title={__('client_work.projects.title')}
            hint={__('client_work.projects.hint')}
            width="lg"
            action={<PrimaryAction href={route('client.projects.create-new')} icon={<Plus className="h-4 w-4" />}>{__('client_work.projects.new')}</PrimaryAction>}
        >
            <div className="flex flex-wrap items-center gap-4 text-sm">
                <span className="font-semibold">{__('client_work.projects.list')}</span>
                <TextAction href={route('client.projects.all-projects-board.index')}>{__('client_work.projects.board')}</TextAction>
                <TextAction href={route('client.projects.all-tasks')}>{__('client_work.projects.all_tasks')}</TextAction>
            </div>

            {list.length === 0 ? (
                <EmptyNote action={<TextAction href={route('client.projects.create-new')}>{__('client_work.projects.new')}</TextAction>}>
                    {__('client_work.projects.empty')}
                </EmptyNote>
            ) : (
                <ul className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {list.map((project) => (
                        <li key={project.id}>
                            <Card className="flex h-full flex-col gap-4">
                                <div>
                                    <SafeLink href={route('client.projects.show', project.id)} className="text-lg font-bold leading-snug hover:text-[#0071e3]">
                                        {project.name}
                                    </SafeLink>
                                    <p className={cn('mt-1 text-sm font-medium',
                                        project.status === 'open' ? 'text-emerald-700 dark:text-emerald-400' : project.status === 'hold_on' ? 'text-amber-700 dark:text-amber-400' : 'text-[#1d1d1f]/60 dark:text-zinc-400')}>
                                        {__(`general.status_${project.status}`)}
                                        {project.date_start && (
                                            <span className="font-normal text-[#1d1d1f]/50 dark:text-zinc-500">
                                                {' · '}{formatDate(project.date_start)} – {project.date_end ? formatDate(project.date_end) : __('client_work.projects.ongoing')}
                                            </span>
                                        )}
                                    </p>
                                </div>

                                <div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_work.projects.progress')}</span>
                                        <span className="font-semibold tabular-nums" dir="ltr">{Math.round(project.percentage)}%</span>
                                    </div>
                                    <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-[#f5f5f7] dark:bg-zinc-800">
                                        <div className="h-full rounded-full bg-[#0071e3]" style={{ width: `${Math.min(100, Math.max(0, project.percentage))}%` }} />
                                    </div>
                                </div>

                                <div className="rounded-2xl bg-[#f5f5f7] p-3 dark:bg-zinc-800">
                                    <ProjectBudgetRow budget={project.budget} totalPaid={project.total_paid} currency={project.currency} />
                                </div>

                                <div className="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-black/5 pt-3 text-sm dark:border-white/10">
                                    <span className="flex items-center gap-4 text-[#1d1d1f]/60 dark:text-zinc-400">
                                        <span className="inline-flex items-center gap-1" title={__('client_work.projects.tasks')}><ListTodo className="h-4 w-4" />{project.counts.tasks}</span>
                                        <span className="inline-flex items-center gap-1" title={__('client_work.projects.reports')}><FileText className="h-4 w-4" />{project.counts.reports}</span>
                                        <span className="inline-flex items-center gap-1" title={__('client_work.projects.files')}><Paperclip className="h-4 w-4" />{project.counts.files}</span>
                                    </span>
                                    <span className="flex items-center gap-4">
                                        <TextAction href={route('client.projects.calendar.date', { project: project.id, date: new Date().toISOString().slice(0, 10) })}>
                                            <span className="inline-flex items-center gap-1"><CalendarDays className="h-4 w-4" />{__('client_work.projects.today')}</span>
                                        </TextAction>
                                        <TextAction href={route('client.projects.show', project.id)}>{__('client_work.projects.open')}</TextAction>
                                    </span>
                                </div>
                            </Card>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination links={projects?.meta?.links ?? projects?.links} />
        </ClientPage>
    );
}
