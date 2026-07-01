import React, { useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText } from 'lucide-react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Props {
    project: { id: number; name: string };
    report: { id: number; title: string; body: string | null; published_at: string | null };
}

export default function ProjectReport({ project, report }: Props) {
    const html = useMemo(() => {
        if (!report.body) return '';
        const raw = marked.parse(report.body, { async: false }) as string;
        return DOMPurify.sanitize(raw);
    }, [report.body]);

    return (
        <AuthenticatedLayout>
            <Head title={report.title} />
            <div className="mx-auto w-full max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <Link href={route('client.projects.show', project.id)} className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                        <ArrowLeft className="h-4 w-4" /> {project.name}
                    </Link>
                    <div className="flex items-center gap-2 text-emerald-600">
                        <FileText className="h-5 w-5" />
                        <span className="text-xs font-semibold uppercase tracking-wide">{__('general.report')}</span>
                    </div>
                    <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-900">{report.title}</h1>
                    {report.published_at && (
                        <p className="mt-1 text-sm text-slate-400">{formatDate(report.published_at)}</p>
                    )}
                </div>

                <article
                    className="prose prose-slate max-w-none prose-headings:font-heading prose-a:text-emerald-600"
                    dangerouslySetInnerHTML={{ __html: html }}
                />
            </div>
        </AuthenticatedLayout>
    );
}
