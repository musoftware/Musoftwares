import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Plus, Pencil, Trash2, Eye } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';

interface ReportItem {
    id: number;
    title: string;
    body: string | null;
    published_at: string | null;
    is_published: boolean;
    created_at: string | null;
}

interface Props {
    project: { id: number; name: string };
    reports: ReportItem[];
}

const empty = { id: null as number | null, title: '', body: '', published_at: '' };

export default function AdminProjectReportsIndex({ project, reports = [] }: Props) {
    const [open, setOpen] = useState(false);
    const [form, setForm] = useState({ ...empty });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const openCreate = () => { setForm({ ...empty }); setErrors({}); setOpen(true); };
    const openEdit = (r: ReportItem) => {
        setForm({
            id: r.id,
            title: r.title,
            body: r.body ?? '',
            published_at: r.published_at ? r.published_at.slice(0, 16) : '',
        });
        setErrors({});
        setOpen(true);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const url = form.id
            ? route('admin.projects.reports.update', { project: project.id, report: form.id })
            : route('admin.projects.reports.store', { project: project.id });
        const method = form.id ? 'put' : 'post';

        router[method](url, {
            title: form.title,
            body: form.body,
            published_at: form.published_at || null,
        }, {
            onSuccess: () => setOpen(false),
            onError: (e) => setErrors(e as Record<string, string>),
            preserveScroll: true,
        });
    };

    const destroy = (id: number) => {
        if (!confirm(__('general.delete_this_report'))) return;
        router.delete(route('admin.projects.reports.destroy', { project: project.id, report: id }), { preserveScroll: true });
    };

    return (
        <AdminSidebarLayout title={`${project.name} · ${__('general.reports')}`} header={`${project.name} — ${__('general.reports')}`}>
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <Link href={route('admin.projects.index')} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                        <ArrowLeft className="h-4 w-4" /> {__('general.back_to_projects')}
                    </Link>
                    <Button onClick={openCreate}><Plus className="me-1 h-4 w-4" /> {__('general.new_report')}</Button>
                </div>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">{__('general.title')}</th>
                                <th className="px-4 py-3">{__('general.publish_date')}</th>
                                <th className="px-4 py-3">{__('general.status')}</th>
                                <th className="px-4 py-3 text-end">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {reports.length === 0 ? (
                                <tr><td colSpan={4} className="px-4 py-10 text-center text-slate-400">{__('general.no_reports_yet')}</td></tr>
                            ) : reports.map((r) => (
                                <tr key={r.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3 font-medium text-slate-800">
                                        <span className="inline-flex items-center gap-2"><FileText className="h-4 w-4 text-slate-400" /> {r.title}</span>
                                    </td>
                                    <td className="px-4 py-3 text-slate-500">{r.published_at ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        {r.published_at === null
                                            ? <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{__('general.draft')}</span>
                                            : r.is_published
                                                ? <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">{__('general.published')}</span>
                                                : <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">{__('general.scheduled')}</span>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            {r.is_published && (
                                                <a href={route('client.projects.reports.show', { project: project.id, report: r.id })} target="_blank" rel="noreferrer" className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100" title={__('general.preview')}>
                                                    <Eye className="h-4 w-4" />
                                                </a>
                                            )}
                                            <button onClick={() => openEdit(r)} className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100" title={__('general.edit')}>
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                            <button onClick={() => destroy(r.id)} className="rounded-md p-1.5 text-rose-500 hover:bg-rose-50" title={__('general.delete')}>
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{form.id ? __('general.edit_report') : __('general.new_report')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="title">{__('general.title')}</Label>
                            <Input id="title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
                            {errors.title && <p className="mt-1 text-xs text-rose-600">{errors.title}</p>}
                        </div>
                        <div>
                            <Label htmlFor="body">{__('general.body')} <span className="text-xs text-slate-400">(Markdown)</span></Label>
                            <Textarea id="body" rows={8} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} />
                        </div>
                        <div>
                            <Label htmlFor="published_at">{__('general.publish_at')} <span className="text-xs text-slate-400">({__('general.leave_empty_for_draft')})</span></Label>
                            <Input id="published_at" type="datetime-local" value={form.published_at} onChange={(e) => setForm({ ...form, published_at: e.target.value })} />
                            {errors.published_at && <p className="mt-1 text-xs text-rose-600">{errors.published_at}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit">{__('general.save')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
