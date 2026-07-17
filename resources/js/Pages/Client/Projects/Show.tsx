import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, FileText, ListTodo, Paperclip } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { AvatarStack, AvatarStackMember } from '@/Components/ui/AvatarStack';
import { ProjectBudgetRow } from '@/Components/ProjectBudgetRow';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

import ProjectTasksTab, { TabTask } from './tabs/Tasks';
import ProjectDiscussionsTab, { TabDiscussion } from './tabs/Discussions';
import ProjectFilesTab, { TabFile } from './tabs/Files';
import ProjectFinancialsTab, { TabFinancials } from './tabs/Financials';

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700',
    hold_on: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-200 text-slate-700',
};

interface ProjectDetail {
    id: number;
    name: string;
    status: string;
    archived: boolean;
    percentage: number;
    date_start: string | null;
    date_end: string | null;
    budget: string;
    cost: string;
    paid_invoices: string;
    pending_invoices: string;
    total_paid: string;
    hide_future_tasks: boolean;
    currency: { id: number; currency: string; symbol: string; string_format?: string } | null;
    counts: { tasks: number; reports: number; files: number };
}

interface RecentReport {
    id: number;
    title: string;
    published_at: string | null;
}

interface Props {
    project: ProjectDetail;
    recentReports?: RecentReport[];
    team?: AvatarStackMember[];
    activeTab?: 'tasks' | 'discussions' | 'files' | 'financials';
    tabContent?: {
        tasks?: TabTask[];
        discussions?: TabDiscussion[];
        files?: TabFile[];
        financials?: TabFinancials;
    };
}

const QUICK_ACTIONS = (project: ProjectDetail, today: string) =>
    [
        {
            icon: ListTodo,
            label: __('general.tasks'),
            count: project.counts.tasks,
            href: route('client.projects.tasks.index', { project: project.id }),
        },
        {
            icon: CalendarClock,
            label: __('general.day_board'),
            count: null,
            href: route('client.projects.calendar.date', { project: project.id, date: today }),
        },
        {
            icon: FileText,
            label: __('general.reports'),
            count: project.counts.reports,
            href: route('client.projects.show', project.id),
        },
        {
            icon: Paperclip,
            label: __('general.files'),
            count: project.counts.files,
            href: route('client.projects.files.index', { project: project.id }),
        },
    ];

export default function ProjectShow({
    project,
    recentReports = [],
    team = [],
    activeTab = 'tasks',
    tabContent = {},
}: Props) {
    const today = new Date().toISOString().slice(0, 10);

    const onTabChange = React.useCallback(
        (next: string) => {
            if (next === activeTab) return;
            if (typeof window === 'undefined') return;
            const target = new URL(window.location.href);
            target.searchParams.set('tab', next);
            router.get(target.pathname + target.search, { tab: next }, {
                only: ['tabContent'],
                preserveScroll: true,
                preserveState: true,
            });
        },
        [activeTab],
    );

    const quickActions = QUICK_ACTIONS(project, today);

    return (
        <AuthenticatedLayout>
            <Head title={project.name} />
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <header className="space-y-3">
                    <Link
                        href={route('client.projects.index')}
                        className="inline-flex items-center gap-1 text-sm text-slate-500 transition-colors hover:text-slate-800"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" /> {__('general.all_projects')}
                    </Link>
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0 flex-1 space-y-2">
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="truncate text-3xl font-bold tracking-tight text-slate-900">
                                    {project.name}
                                </h1>
                                <span
                                    className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset ${
                                        STATUS_STYLES[project.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'
                                    }`}
                                >
                                    {project.status?.replace('_', ' ')}
                                </span>
                            </div>
                            <AvatarStack members={team} max={5} size="sm" />
                        </div>
                    </div>
                </header>

                <ProjectBudgetRow
                    budget={project.budget}
                    totalPaid={project.total_paid}
                    currency={project.currency}
                    orientation="row"
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {quickActions.map((action) => (
                        <Link
                            key={action.label}
                            href={action.href}
                            className="flex h-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <action.icon className="h-5 w-5" aria-hidden="true" />
                            </div>
                            <div className="flex-1">
                                <p className="font-medium text-slate-900">{action.label}</p>
                                {action.count !== null && (
                                    <p className="text-xs text-slate-400">{action.count}</p>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>

                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="p-5">
                        <Tabs value={activeTab} onValueChange={onTabChange} className="space-y-4">
                            <TabsList className="inline-flex h-10 w-full items-stretch justify-start gap-1 rounded-lg border border-slate-200 bg-white p-1 sm:w-auto">
                                <TabsTrigger
                                    value="tasks"
                                    className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                >
                                    <ListTodo className="h-4 w-4" aria-hidden="true" /> {__('general.tasks')}
                                </TabsTrigger>
                                <TabsTrigger
                                    value="discussions"
                                    className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                >
                                    {__('general.discussions') || 'Discussions'}
                                </TabsTrigger>
                                <TabsTrigger
                                    value="files"
                                    className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                >
                                    <Paperclip className="h-4 w-4" aria-hidden="true" /> {__('general.files')}
                                </TabsTrigger>
                                <TabsTrigger
                                    value="financials"
                                    className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 transition-colors data-[state=active]:bg-slate-900 data-[state=active]:text-white"
                                >
                                    {__('general.financials') || 'Financials'}
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="tasks" className="mt-4">
                                <ProjectTasksTab tasks={tabContent.tasks ?? []} />
                            </TabsContent>
                            <TabsContent value="discussions" className="mt-4">
                                <ProjectDiscussionsTab discussions={tabContent.discussions ?? []} />
                            </TabsContent>
                            <TabsContent value="files" className="mt-4">
                                <ProjectFilesTab files={tabContent.files ?? []} projectId={project.id} />
                            </TabsContent>
                            <TabsContent value="financials" className="mt-4">
                                <ProjectFinancialsTab
                                    financials={tabContent.financials ?? null}
                                    currency={project.currency}
                                />
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>

                {recentReports.length > 0 && (
                    <Card className="rounded-xl border border-slate-200">
                        <CardContent className="p-5">
                            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                                {__('general.recent_reports')}
                            </h2>
                            <ul className="divide-y divide-slate-100">
                                {recentReports.map((report) => (
                                    <li key={report.id}>
                                        <Link
                                            href={route('client.projects.reports.show', {
                                                project: project.id,
                                                report: report.id,
                                            })}
                                            className="flex items-center justify-between py-3 transition-colors hover:bg-slate-50"
                                        >
                                            <span className="flex items-center gap-2 font-medium text-slate-800">
                                                <FileText className="h-4 w-4 text-slate-400" aria-hidden="true" />
                                                {report.title}
                                            </span>
                                            <span className="text-xs text-slate-400">
                                                {report.published_at ? formatDate(report.published_at) : '—'}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
