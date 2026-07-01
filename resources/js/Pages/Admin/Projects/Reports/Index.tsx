import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Plus, Pencil, Trash2, Eye } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

interface ReportItem {
    id: number;
    title: string;
    type: string;
    priority: string;
    summary: string | null;
    published_at: string | null;
    is_published: boolean;
    period_start: string | null;
    period_end: string | null;
    notify_client: boolean;
    created_at: string | null;
}

interface Props {
    project: { id: number; name: string };
    reports: ReportItem[];
}

const TYPE_LABELS: Record<string, string> = {
    progress: 'Progress Update',
    milestone: 'Milestone',
    issue: 'Issue / Risk',
    summary: 'Weekly Summary',
    financial: 'Financial',
    final: 'Final Report',
};

const PRIORITY_STYLES: Record<string, { dot: string; label: string }> = {
    low: { dot: 'bg-slate-300', label: 'bg-slate-100 text-slate-600' },
    normal: { dot: 'bg-sky-500', label: 'bg-sky-50 text-sky-700' },
    high: { dot: 'bg-amber-500', label: 'bg-amber-50 text-amber-700' },
    urgent: { dot: 'bg-rose-500', label: 'bg-rose-50 text-rose-700' },
};

const PRIORITY_LABELS: Record<string, string> = {
    low: 'Low',
    normal: 'Normal',
    high: 'High',
    urgent: 'Urgent',
};

export default function AdminProjectReportsIndex({ project, reports = [] }: Props) {
    const destroy = (id: number) => {
        if (!confirm(__('general.delete_this_report'))) return;
        router.delete(
            route('admin.projects.reports.destroy', { project: project.id, report: id }),
            { preserveScroll: true },
        );
    };

    return (
        <AdminSidebarLayout
            title={`${project.name} · ${__('general.reports')}`}
            header={`${project.name} — ${__('general.reports')}`}
        >
            <Head title={`${project.name} · ${__('general.reports')}`} />
            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <Link
                            href={route('admin.projects.index')}
                            className="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                        >
                            <ArrowLeft className="h-4 w-4" /> {__('general.back_to_projects')}
                        </Link>
                        <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                            <FileText className="h-5 w-5 text-emerald-600" />
                            {__('general.progress_reports')}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {__('general.reports_index_subtitle')}
                        </p>
                    </div>
                    <Button asChild className="gap-2 bg-emerald-600 hover:bg-emerald-700">
                        <Link href={route('admin.projects.reports.create', { project: project.id })}>
                            <Plus className="h-4 w-4" /> {__('general.new_report')}
                        </Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">{__('general.title')}</th>
                                <th className="px-4 py-3">{__('general.report_type')}</th>
                                <th className="px-4 py-3">{__('general.report_priority')}</th>
                                <th className="px-4 py-3">{__('general.publish_date')}</th>
                                <th className="px-4 py-3">{__('general.status')}</th>
                                <th className="px-4 py-3 text-end">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {reports.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-10 text-center text-slate-400">
                                        {__('general.no_reports_yet')}
                                    </td>
                                </tr>
                            ) : (
                                reports.map((r) => {
                                    const pStyle = PRIORITY_STYLES[r.priority] ?? PRIORITY_STYLES.normal;
                                    return (
                                        <tr key={r.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-slate-800">
                                                    <span className="inline-flex items-center gap-2">
                                                        <FileText className="h-4 w-4 text-slate-400" />
                                                        {r.title}
                                                    </span>
                                                </div>
                                                {r.summary && (
                                                    <p className="mt-0.5 line-clamp-1 ps-6 text-xs text-slate-500">
                                                        {r.summary}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {TYPE_LABELS[r.type] ?? r.type}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ${pStyle.label}`}
                                                >
                                                    <span className={`h-1.5 w-1.5 rounded-full ${pStyle.dot}`} />
                                                    {PRIORITY_LABELS[r.priority] ?? r.priority}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-slate-500">
                                                {r.published_at ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.published_at === null ? (
                                                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                                        {__('general.draft')}
                                                    </span>
                                                ) : r.is_published ? (
                                                    <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                                        {__('general.published')}
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                        {__('general.scheduled')}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    {r.is_published && (
                                                        <a
                                                            href={route('client.projects.reports.show', {
                                                                project: project.id,
                                                                report: r.id,
                                                            })}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100"
                                                            title={__('general.preview')}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </a>
                                                    )}
                                                    <Link
                                                        href={route('admin.projects.reports.edit', {
                                                            project: project.id,
                                                            report: r.id,
                                                        })}
                                                        className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100"
                                                        title={__('general.edit')}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => destroy(r.id)}
                                                        className="rounded-md p-1.5 text-rose-500 hover:bg-rose-50"
                                                        title={__('general.delete')}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}