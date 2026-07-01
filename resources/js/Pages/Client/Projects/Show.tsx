import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft, ListTodo, CalendarDays, FileText, Paperclip, Wallet, PiggyBank, Clock,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney, formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface ProjectDetail {
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

interface RecentReport {
    id: number;
    title: string;
    published_at: string | null;
}

interface Props {
    project: ProjectDetail;
    recentReports: RecentReport[];
}

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700',
    hold_on: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-200 text-slate-700',
};

export default function ProjectShow({ project, recentReports = [] }: Props) {
    const today = new Date().toISOString().slice(0, 10);

    return (
        <AuthenticatedLayout>
            <Head title={project.name} />
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <Link href={route('client.projects.index')} className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                        <ArrowLeft className="h-4 w-4" /> {__('general.all_projects')}
                    </Link>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900">{project.name}</h1>
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLES[project.status] ?? 'bg-slate-100 text-slate-600'}`}>
                            {project.status?.replace('_', ' ')}
                        </span>
                    </div>
                </div>

                {/* Financial summary */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <MetricCard label={__('general.budget')} value={formatMoney(project.budget, project.currency)} icon={PiggyBank} />
                    <MetricCard label={__('general.paid')} value={formatMoney(project.total_paid, project.currency)} icon={Wallet} />
                    <MetricCard label={__('general.remaining_balance')} value={formatMoney(project.project_balance, project.currency)} icon={Wallet} />
                    <MetricCard label={__('general.progress')} value={`${Math.round(project.percentage)}%`} icon={Clock} />
                </div>

                {/* Progress bar + dates */}
                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="p-5">
                        <div className="mb-1 flex items-center justify-between text-sm">
                            <span className="font-medium text-slate-700">{__('general.completion')}</span>
                            <span className="font-mono font-semibold text-slate-900">{Math.round(project.percentage)}%</span>
                        </div>
                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${Math.min(100, Math.max(0, project.percentage))}%` }} />
                        </div>
                        {(project.date_start || project.date_end) && (
                            <p className="mt-3 text-xs text-slate-500">
                                <Clock className="me-1 inline h-3.5 w-3.5" />
                                {project.date_start ? formatDate(project.date_start) : '—'} → {project.date_end ? formatDate(project.date_end) : '…'}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Quick navigation */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        { icon: ListTodo, label: __('general.tasks'), count: project.counts.tasks, route: 'client.projects.tasks.index', params: { project: project.id } },
                        { icon: CalendarDays, label: __('general.day_board'), count: null, route: 'client.projects.calendar.date', params: { project: project.id, date: today } },
                        { icon: FileText, label: __('general.reports'), count: project.counts.reports, route: null, params: null },
                        { icon: Paperclip, label: __('general.files'), count: project.counts.files, route: 'client.projects.files.index', params: { project: project.id } },
                    ].map((item) => {
                        const Inner = (
                            <div className="flex h-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition-colors hover:border-slate-300 hover:bg-slate-50">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <item.icon className="h-5 w-5" />
                                </div>
                                <div className="flex-1">
                                    <p className="font-medium text-slate-900">{item.label}</p>
                                    {item.count !== null && <p className="text-xs text-slate-400">{item.count}</p>}
                                </div>
                            </div>
                        );
                        return item.route ? (
                            <Link key={item.label} href={route(item.route, item.params)}>{Inner}</Link>
                        ) : (
                            <div key={item.label}>{Inner}</div>
                        );
                    })}
                </div>

                {/* Recent reports */}
                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="p-5">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{__('general.recent_reports')}</h2>
                        {recentReports.length === 0 ? (
                            <p className="py-6 text-center text-sm text-slate-400">{__('general.no_reports_yet')}</p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {recentReports.map((r) => (
                                    <li key={r.id}>
                                        <Link href={route('client.projects.reports.show', { project: project.id, report: r.id })} className="flex items-center justify-between py-3 hover:bg-slate-50">
                                            <span className="flex items-center gap-2 font-medium text-slate-800">
                                                <FileText className="h-4 w-4 text-slate-400" /> {r.title}
                                            </span>
                                            <span className="text-xs text-slate-400">{r.published_at ? formatDate(r.published_at) : '—'}</span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
