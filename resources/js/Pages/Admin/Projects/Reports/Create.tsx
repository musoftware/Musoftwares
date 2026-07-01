import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, FileText, AlertCircle, Sparkles, Calendar, ListChecks, Megaphone } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

interface Props {
    project: { id: number; name: string };
    types: Record<string, string>;
    priorities: Record<string, string>;
}

interface FormState {
    title: string;
    type: string;
    priority: string;
    summary: string;
    body: string;
    period_start: string;
    period_end: string;
    published_at: string;
    notify_client: boolean;
}

const EMPTY_FORM: FormState = {
    title: '',
    type: 'progress',
    priority: 'normal',
    summary: '',
    body: '',
    period_start: '',
    period_end: '',
    published_at: '',
    notify_client: false,
};

const PRIORITY_DOT: Record<string, string> = {
    low: 'bg-slate-300',
    normal: 'bg-sky-500',
    high: 'bg-amber-500',
    urgent: 'bg-rose-500',
};

export default function Create({ project, types, priorities }: Props) {
    const pageErrors = (usePage().props as { errors?: Record<string, string> }).errors ?? {};
    const [form, setForm] = useState<FormState>(EMPTY_FORM);
    const [submitting, setSubmitting] = useState(false);

    const set = <K extends keyof FormState>(key: K, value: FormState[K]) =>
        setForm((prev) => ({ ...prev, [key]: value }));

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);

        const payload = {
            ...form,
            published_at: form.published_at || null,
            period_start: form.period_start || null,
            period_end: form.period_end || null,
            summary: form.summary || null,
            body: form.body || null,
        };

        router.post(
            route('admin.projects.reports.store', { project: project.id }),
            payload,
            {
                onFinish: () => setSubmitting(false),
                preserveScroll: true,
            },
        );
    };

    const errorFor = (key: keyof FormState) => pageErrors[key];

    return (
        <AdminSidebarLayout
            title={`${__('general.new_report')} · ${project.name}`}
            header={`${project.name} — ${__('general.reports')}`}
        >
            <Head title={`${__('general.new_report')} · ${project.name}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <div>
                    <Link
                        href={route('admin.projects.reports.index', { project: project.id })}
                        className="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"
                    >
                        <ChevronLeft className="h-4 w-4" /> {__('general.back_to_reports')}
                    </Link>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                                <Sparkles className="h-5 w-5 text-emerald-600" />
                                {__('general.new_report')}
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                {__('general.new_report_subtitle')}
                            </p>
                        </div>
                    </div>
                </div>

                {Object.keys(pageErrors).length > 0 && (
                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                        <div>
                            <p className="font-semibold">{__('general.please_fix_the_following')}</p>
                            <ul className="mt-1 list-disc space-y-0.5 ps-4">
                                {Object.entries(pageErrors).map(([key, message]) => (
                                    <li key={key}>
                                        <span className="font-medium">{key}:</span> {message}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* ── Section: Core ── */}
                    <section className="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <header className="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <FileText className="h-4 w-4 text-slate-500" />
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-600">
                                {__('general.report_section_core')}
                            </h2>
                        </header>

                        <div className="space-y-2">
                            <Label htmlFor="title">
                                {__('general.title')} <span className="text-rose-500">*</span>
                            </Label>
                            <Input
                                id="title"
                                value={form.title}
                                onChange={(e) => set('title', e.target.value)}
                                maxLength={255}
                                required
                                placeholder={__('general.report_title_placeholder')}
                            />
                            {errorFor('title') && (
                                <p className="text-xs text-rose-600">{errorFor('title')}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="summary">{__('general.report_summary')}</Label>
                            <Textarea
                                id="summary"
                                rows={2}
                                value={form.summary}
                                onChange={(e) => set('summary', e.target.value)}
                                maxLength={1000}
                                placeholder={__('general.report_summary_placeholder')}
                            />
                            <p className="text-xs text-slate-400">
                                {__('general.report_summary_help')}
                            </p>
                            {errorFor('summary') && (
                                <p className="text-xs text-rose-600">{errorFor('summary')}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="body">
                                {__('general.body')}{' '}
                                <span className="text-xs font-normal text-slate-400">(Markdown)</span>
                            </Label>
                            <Textarea
                                id="body"
                                rows={12}
                                value={form.body}
                                onChange={(e) => set('body', e.target.value)}
                                placeholder={__('general.report_body_placeholder')}
                                className="font-mono text-sm"
                            />
                            {errorFor('body') && (
                                <p className="text-xs text-rose-600">{errorFor('body')}</p>
                            )}
                        </div>
                    </section>

                    {/* ── Section: Classification ── */}
                    <section className="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <header className="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <ListChecks className="h-4 w-4 text-slate-500" />
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-600">
                                {__('general.report_section_classification')}
                            </h2>
                        </header>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="type">{__('general.report_type')}</Label>
                                <select
                                    id="type"
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                                    value={form.type}
                                    onChange={(e) => set('type', e.target.value)}
                                >
                                    {Object.entries(types).map(([key, label]) => (
                                        <option key={key} value={key}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                                {errorFor('type') && (
                                    <p className="text-xs text-rose-600">{errorFor('type')}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="priority">{__('general.report_priority')}</Label>
                                <select
                                    id="priority"
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                                    value={form.priority}
                                    onChange={(e) => set('priority', e.target.value)}
                                >
                                    {Object.entries(priorities).map(([key, label]) => (
                                        <option key={key} value={key}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                                <div className="flex items-center gap-1.5 pt-1">
                                    <span className={`inline-block h-2 w-2 rounded-full ${PRIORITY_DOT[form.priority] ?? 'bg-slate-300'}`} />
                                    <span className="text-xs text-slate-500">
                                        {priorities[form.priority]}
                                    </span>
                                </div>
                                {errorFor('priority') && (
                                    <p className="text-xs text-rose-600">{errorFor('priority')}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="period_start">{__('general.report_period_start')}</Label>
                                <Input
                                    id="period_start"
                                    type="date"
                                    value={form.period_start}
                                    onChange={(e) => set('period_start', e.target.value)}
                                />
                                {errorFor('period_start') && (
                                    <p className="text-xs text-rose-600">{errorFor('period_start')}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="period_end">{__('general.report_period_end')}</Label>
                                <Input
                                    id="period_end"
                                    type="date"
                                    value={form.period_end}
                                    onChange={(e) => set('period_end', e.target.value)}
                                />
                                {errorFor('period_end') && (
                                    <p className="text-xs text-rose-600">{errorFor('period_end')}</p>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* ── Section: Publishing ── */}
                    <section className="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <header className="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <Megaphone className="h-4 w-4 text-slate-500" />
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-600">
                                {__('general.report_section_publishing')}
                            </h2>
                        </header>

                        <div className="space-y-2">
                            <Label htmlFor="published_at">
                                {__('general.publish_at')}{' '}
                                <span className="text-xs font-normal text-slate-400">
                                    ({__('general.leave_empty_for_draft')})
                                </span>
                            </Label>
                            <div className="relative">
                                <Calendar className="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    id="published_at"
                                    type="datetime-local"
                                    value={form.published_at}
                                    onChange={(e) => set('published_at', e.target.value)}
                                    className="ps-9"
                                />
                            </div>
                            <p className="text-xs text-slate-400">
                                {__('general.report_publish_help')}
                            </p>
                            {errorFor('published_at') && (
                                <p className="text-xs text-rose-600">{errorFor('published_at')}</p>
                            )}
                        </div>

                        <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 transition hover:bg-slate-100">
                            <input
                                type="checkbox"
                                className="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                checked={form.notify_client}
                                onChange={(e) => set('notify_client', e.target.checked)}
                            />
                            <div>
                                <p className="text-sm font-medium text-slate-800">
                                    {__('general.report_notify_client')}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {__('general.report_notify_client_help')}
                                </p>
                            </div>
                        </label>
                    </section>

                    {/* ── Actions ── */}
                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" asChild disabled={submitting}>
                            <Link href={route('admin.projects.reports.index', { project: project.id })}>
                                {__('general.cancel')}
                            </Link>
                        </Button>
                        <Button type="submit" disabled={submitting} className="gap-2 bg-emerald-600 hover:bg-emerald-700">
                            {submitting ? __('general.saving') : __('general.create_report')}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}