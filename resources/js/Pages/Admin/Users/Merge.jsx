import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';

function previewToConflicts(report) {
    const conflicts = {};
    const counts = {};
    if (!report) return { conflicts, counts };
    for (const [field, vals] of Object.entries(report.field_conflicts || {})) {
        conflicts[field] = vals;
    }
    for (const [k, v] of Object.entries(report.child_counts || {})) {
        counts[k] = v;
    }
    return { conflicts, counts };
}

export default function Merge({ survivor, duplicates = [], reports = [] }) {
    const { confirm, confirmDialog } = useConfirm();
    const [resolutions, setResolutions] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const aggregate = useMemo(() => {
        const conflicts = {};
        const counts = {};
        reports.forEach((r) => {
            const { conflicts: c, counts: k } = previewToConflicts(r);
            for (const [f, v] of Object.entries(c)) conflicts[f] = v;
            for (const [k2, v2] of Object.entries(k)) counts[k2] = (counts[k2] ?? 0) + v2;
        });
        return { conflicts, counts };
    }, [reports]);

    const fields = Object.keys(aggregate.conflicts);

    const setResolution = (field, value) => {
        setResolutions((prev) => ({ ...prev, [field]: value }));
    };

    const submit = async (e) => {
        e.preventDefault();
        const ids = duplicates.map((d) => d.id).join(', ');
        const accepted = await confirm({
            title: __('admin.merge_confirm_title', { ids, id: survivor.id }),
            description: __('admin.action_cannot_be_undone'),
            variant: 'danger',
            confirmLabel: __('general.merge'),
        });
        if (!accepted) return;
        setSubmitting(true);
        router.post(`/admin/users/${survivor.id}/merge/confirm`, {
            duplicate_ids: duplicates.map((d) => d.id),
            resolutions,
        }, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AdminSidebarLayout auth={{ user: survivor }}>
            <Head title={__('admin.merge_accounts_into', { id: survivor.id })} />
            {confirmDialog}
            <div className="p-6 space-y-6 max-w-5xl">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{__('admin.merge_accounts')}</h1>
                    <Button asChild variant="outline">
                        <Link href={`/admin/users/${survivor.id}`}>{__('admin.merge_back_to_survivor')}</Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded border bg-green-50 p-4">
                        <div className="text-xs uppercase tracking-wide text-green-700">{__('admin.merge_primary_survivor')}</div>
                        <div className="font-mono">#{survivor.id}</div>
                        <div className="text-lg">{survivor.name}</div>
                        <div className="text-sm text-muted-foreground">{survivor.email}</div>
                    </div>
                    <div className="rounded border bg-red-50 p-4">
                        <div className="text-xs uppercase tracking-wide text-red-700">{__('admin.merge_duplicates_to_merge', { count: duplicates.length })}</div>
                        <ul className="mt-2 space-y-1">
                            {duplicates.map((d) => (
                                <li key={d.id} className="flex items-center justify-between gap-3">
                                    <span className="font-mono text-sm">#{d.id}</span>
                                    <span className="text-sm">{d.name || '—'}</span>
                                    <span className="font-mono text-xs text-muted-foreground truncate">{d.email}</span>
                                </li>
                            ))}
                        </ul>
                        <p className="mt-3 text-xs text-muted-foreground">
                            {__('admin.merge_email_preserved_note')}
                        </p>
                    </div>
                </div>

                <div>
                    <h2 className="text-lg font-medium">{__('admin.merge_conflicting_fields')}</h2>
                    {fields.length === 0 && (
                        <p className="text-sm text-muted-foreground">{__('admin.merge_no_conflicts')}</p>
                    )}
                    <form onSubmit={submit} className="space-y-4 mt-2">
                        {fields.map((field) => {
                            const v = aggregate.conflicts[field];
                            return (
                                <div key={field} className="rounded border p-4 space-y-2">
                                    <div className="font-medium">{field}</div>
                                    <div className="grid grid-cols-3 gap-2 text-sm">
                                        <div>
                                            <Label className="text-xs">{__('admin.merge_survivor')}</Label>
                                            <div className="rounded bg-muted px-2 py-1 font-mono">{String(v.survivor ?? '∅')}</div>
                                        </div>
                                        <div>
                                            <Label className="text-xs">{__('admin.merge_first_duplicate')}</Label>
                                            <div className="rounded bg-muted px-2 py-1 font-mono">{String(v.duplicate ?? '∅')}</div>
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs" htmlFor={`resolution-${field}`}>{__('admin.merge_resolution')}</Label>
                                            <select
                                                id={`resolution-${field}`}
                                                className="w-full rounded border px-2 py-1"
                                                value={resolutions[field] ?? 'survivor'}
                                                onChange={(e) => setResolution(field, e.target.value)}
                                            >
                                                <option value="survivor">{__('admin.merge_keep_survivor')}</option>
                                                <option value="duplicate">{__('admin.merge_use_duplicate')}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}

                        {Object.keys(aggregate.counts).length > 0 && (
                            <div className="rounded border p-4">
                                <div className="font-medium mb-2">{__('admin.merge_child_rows_reassigned')}</div>
                                <ul className="text-sm space-y-1 font-mono">
                                    {Object.entries(aggregate.counts).map(([k, n]) => (
                                        <li key={k}>{k}: <span className="font-semibold">{n}</span></li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="flex gap-2">
                            <Button type="submit" disabled={submitting || duplicates.length === 0}>
                                {submitting
                                    ? __('admin.merging')
                                    : __('admin.merge_accounts_count_into', { count: duplicates.length, id: survivor.id })}
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={`/admin/users/${survivor.id}`}>{__('general.cancel')}</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
