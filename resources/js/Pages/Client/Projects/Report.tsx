import React, { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText, MessageSquare, Send } from 'lucide-react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import axios from 'axios';
import { toast } from 'sonner';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Comment {
    id: number;
    body: string;
    author_name?: string | null;
    created_at: string | null;
}

interface Props {
    project: { id: number; name: string };
    report: { id: number; title: string; body: string | null; published_at: string | null };
    comments?: Comment[];
}

export default function ProjectReport({ project, report, comments = [] }: Props) {
    const [thread, setThread] = useState<Comment[]>(comments);
    const [draft, setDraft] = useState('');
    const [sending, setSending] = useState(false);

    const html = useMemo(() => {
        if (!report.body) return '';
        const raw = marked.parse(report.body, { async: false }) as string;
        return DOMPurify.sanitize(raw);
    }, [report.body]);

    const submitComment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!draft.trim() || sending) return;
        setSending(true);
        axios
            .post(route('client.projects.comments.store', { project: project.id }), {
                type: 'report',
                commentable_id: report.id,
                body: draft.trim(),
            })
            .then(({ data }) => {
                if (data?.comment) setThread((t) => [...t, data.comment]);
                setDraft('');
            })
            .catch(() => toast.error(__('general.could_not_post_comment')))
            .finally(() => setSending(false));
    };

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

                {/* Comments / feedback */}
                <section className="space-y-4 rounded-xl border border-slate-200 bg-white p-5">
                    <h2 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
                        <MessageSquare className="h-4 w-4" /> {__('general.comments')}
                    </h2>

                    {thread.length === 0 ? (
                        <p className="py-4 text-center text-sm text-slate-400">{__('general.no_comments_yet')}</p>
                    ) : (
                        <ul className="space-y-3">
                            {thread.map((c) => (
                                <li key={c.id} className="rounded-lg bg-slate-50 p-3">
                                    <div className="mb-0.5 flex items-center justify-between text-xs text-slate-400">
                                        <span className="font-semibold text-slate-600">{c.author_name ?? __('general.anonymous')}</span>
                                        {c.created_at && <span>{formatDate(c.created_at)}</span>}
                                    </div>
                                    <p className="whitespace-pre-wrap break-words text-sm text-slate-700">{c.body}</p>
                                </li>
                            ))}
                        </ul>
                    )}

                    <form onSubmit={submitComment} className="flex items-end gap-2">
                        <textarea
                            value={draft}
                            onChange={(e) => setDraft(e.target.value)}
                            rows={2}
                            placeholder={__('general.write_a_comment')}
                            className="flex-1 resize-none rounded-lg border border-slate-200 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                        />
                        <button
                            type="submit"
                            disabled={sending || !draft.trim()}
                            className="inline-flex h-9 items-center gap-1 rounded-lg bg-slate-900 px-3 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-40"
                        >
                            <Send className="h-3.5 w-3.5" /> {__('general.send')}
                        </button>
                    </form>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
