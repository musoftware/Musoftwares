import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';

export default function Merge({ survivor, duplicate, conflicts = {}, counts = {} }) {
    const [resolutions, setResolutions] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const fields = Object.keys(conflicts);

    const setResolution = (field, value) => {
        setResolutions((prev) => ({ ...prev, [field]: value }));
    };

    const submit = (e) => {
        e.preventDefault();
        if (!confirm('Merge duplicate into survivor? This cannot be undone.')) return;
        setSubmitting(true);
        router.post(`/admin/users/${survivor.id}/merge/confirm`, {
            duplicate_id: duplicate.id,
            resolutions,
        }, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AdminSidebarLayout auth={{ user: survivor }}>
            <Head title={`Merge #${duplicate.id} → #${survivor.id}`} />
            <div className="p-6 space-y-6 max-w-4xl">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Merge accounts</h1>
                    <Button asChild variant="outline">
                        <Link href={`/admin/users/${survivor.id}`}>Back to survivor</Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded border bg-green-50 p-4">
                        <div className="text-xs uppercase tracking-wide text-green-700">Survivor</div>
                        <div className="font-mono">#{survivor.id}</div>
                        <div className="text-lg">{survivor.name}</div>
                        <div className="text-sm text-muted-foreground">{survivor.email}</div>
                    </div>
                    <div className="rounded border bg-red-50 p-4">
                        <div className="text-xs uppercase tracking-wide text-red-700">Duplicate (to be merged)</div>
                        <div className="font-mono">#{duplicate.id}</div>
                        <div className="text-lg">{duplicate.name}</div>
                        <div className="text-sm text-muted-foreground">{duplicate.email}</div>
                    </div>
                </div>

                <div>
                    <h2 className="text-lg font-medium">Conflicting fields</h2>
                    {fields.length === 0 && (
                        <p className="text-sm text-muted-foreground">No field conflicts detected — all values match.</p>
                    )}
                    <form onSubmit={submit} className="space-y-4 mt-2">
                        {fields.map((field) => {
                            const v = conflicts[field];
                            return (
                                <div key={field} className="rounded border p-4 space-y-2">
                                    <div className="font-medium">{field}</div>
                                    <div className="grid grid-cols-3 gap-2 text-sm">
                                        <div>
                                            <Label className="text-xs">Survivor</Label>
                                            <div className="rounded bg-muted px-2 py-1 font-mono">{String(v.survivor ?? '∅')}</div>
                                        </div>
                                        <div>
                                            <Label className="text-xs">Duplicate</Label>
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

                        {Object.keys(counts).length > 0 && (
                            <div className="rounded border p-4">
                                <div className="font-medium mb-2">Child rows to be reassigned</div>
                                <ul className="text-sm space-y-1 font-mono">
                                    {Object.entries(counts).map(([k, n]) => (
                                        <li key={k}>{k}: <span className="font-semibold">{n}</span></li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="flex gap-2">
                            <Button type="submit" disabled={submitting}>
                                {submitting ? 'Merging…' : 'Confirm merge'}
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
