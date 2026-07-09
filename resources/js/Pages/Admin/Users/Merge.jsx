import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';

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

    const submit = (e) => {
        e.preventDefault();
        const ids = duplicates.map((d) => d.id).join(', ');
        if (!confirm(`Merge [${ids}] into #${survivor.id}? This cannot be undone.`)) return;
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
            <Head title={`Merge accounts into #${survivor.id}`} />
            <div className="p-6 space-y-6 max-w-5xl">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Merge accounts</h1>
                    <Button asChild variant="outline">
                        <Link href={`/admin/users/${survivor.id}`}>Back to survivor</Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded border bg-green-50 p-4">
                        <div className="text-xs uppercase tracking-wide text-green-700">Primary (survivor)</div>
                        <div className="font-mono">#{survivor.id}</div>
                        <div className="text-lg">{survivor.name}</div>
                        <div className="text-sm text-muted-foreground">{survivor.email}</div>
                    </div>
                    <div className="rounded border bg-red-50 p-4">
                        <div className="text-xs uppercase tracking-wide text-red-700">Duplicates to merge ({duplicates.length})</div>
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
                            The email address of each duplicate will be preserved as a verified alias on the survivor automatically.
                        </p>
                    </div>
                </div>

                <div>
                    <h2 className="text-lg font-medium">Conflicting fields</h2>
                    {fields.length === 0 && (
                        <p className="text-sm text-muted-foreground">No field conflicts detected across the selected duplicates.</p>
                    )}
                    <form onSubmit={submit} className="space-y-4 mt-2">
                        {fields.map((field) => {
                            const v = aggregate.conflicts[field];
                            return (
                                <div key={field} className="rounded border p-4 space-y-2">
                                    <div className="font-medium">{field}</div>
                                    <div className="grid grid-cols-3 gap-2 text-sm">
                                        <div>
                                            <Label className="text-xs">Survivor</Label>
                                            <div className="rounded bg-muted px-2 py-1 font-mono">{String(v.survivor ?? '∅')}</div>
                                        </div>
                                        <div>
                                            <Label className="text-xs">First duplicate</Label>
                                            <div className="rounded bg-muted px-2 py-1 font-mono">{String(v.duplicate ?? '∅')}</div>
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs">Resolution</Label>
                                            <select
                                                className="w-full rounded border px-2 py-1"
                                                value={resolutions[field] ?? 'survivor'}
                                                onChange={(e) => setResolution(field, e.target.value)}
                                            >
                                                <option value="survivor">Keep survivor</option>
                                                <option value="duplicate">Use duplicate</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}

                        {Object.keys(aggregate.counts).length > 0 && (
                            <div className="rounded border p-4">
                                <div className="font-medium mb-2">Total child rows to be reassigned</div>
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
                                    ? 'Merging…'
                                    : `Merge ${duplicates.length} account(s) into #${survivor.id}`}
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={`/admin/users/${survivor.id}`}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
