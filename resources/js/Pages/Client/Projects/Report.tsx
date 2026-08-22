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
            <Head title={`${report.title} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto space-y-2">
                        <Link
                            href={route('client.projects.show', project.id)}
                            className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                        >
                            <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                            {project.name}
                        </Link>
                        <div className="flex items-center gap-2 text-[#0071e3]">
                            <FileText className="h-4 w-4" />
                            <span className="text-xs font-bold uppercase tracking-wider font-mono">{__('general.report')}</span>
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                            {report.title}
                        </h1>
                        {report.published_at && (
                            <p className="text-xs text-[#1d1d1f]/50 font-sans">{formatDate(report.published_at)}</p>
                        )}
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[900px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* Report Article Card */}
                    <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                        <article
                            className="prose prose-slate max-w-none prose-headings:font-sans prose-headings:font-bold prose-a:text-[#0071e3] text-xs sm:text-sm leading-relaxed"
                            dangerouslySetInnerHTML={{ __html: html }}
                        />
                    </div>

                    {/* Comments section */}
                    <section className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm space-y-6">
                        <h2 className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#1d1d1f]/60 font-mono pb-2 border-b border-black/5">
                            <MessageSquare className="h-4 w-4 text-[#0071e3]" /> {__('general.comments')}
                        </h2>

                        {thread.length === 0 ? (
                            <p className="py-4 text-center text-xs text-[#1d1d1f]/40 italic">{__('general.no_comments_yet')}</p>
                        ) : (
                            <ul className="space-y-3">
                                {thread.map((c) => (
                                    <li key={c.id} className="rounded-2xl bg-[#f5f5f7] border border-black/5 p-4 space-y-1">
                                        <div className="flex items-center justify-between text-xs text-[#1d1d1f]/50">
                                            <span className="font-bold text-[#1d1d1f]">{c.author_name ?? __('general.anonymous')}</span>
                                            {c.created_at && <span>{formatDate(c.created_at)}</span>}
                                        </div>
                                        <p className="whitespace-pre-wrap break-words text-xs sm:text-sm text-[#1d1d1f]/80">{c.body}</p>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <form onSubmit={submitComment} className="flex items-end gap-3 pt-2">
                            <textarea
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                placeholder={__('general.write_comment_or_feedback') || 'Write comment...'}
                                rows={2}
                                className="flex-1 rounded-xl bg-white border border-black/10 px-3.5 py-2 text-xs sm:text-sm text-[#1d1d1f] focus:outline-none focus:ring-2 focus:ring-[#0071e3]"
                            />
                            <button
                                type="submit"
                                disabled={sending || !draft.trim()}
                                className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] disabled:opacity-50 text-white rounded-[980px] text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
                            >
                                <Send className="h-3.5 w-3.5" />
                                <span>{__('general.post') || 'Post'}</span>
                            </button>
                        </form>
                    </section>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
