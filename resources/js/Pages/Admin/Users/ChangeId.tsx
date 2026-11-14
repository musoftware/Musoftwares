import React, { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';

interface Move {
    table: string;
    column: string;
    rows: number;
}

interface Props {
    client: { id: number; name: string; email: string };
    wanted: number | null;
    free: boolean | null;
    moves: Move[];
}

/** Whether the ID the admin typed can be used, after they asked. */
function Availability({ wanted, free }: { wanted: number | null; free: boolean | null }) {
    if (wanted === null || free === null) return null;
    return free
        ? <p className="text-sm text-green-700">{__('admin.user_id_free', { id: wanted })}</p>
        : <p className="text-sm text-red-700">{__('admin.user_id_taken')}</p>;
}

export default function ChangeId({ client, wanted, free, moves }: Props) {
    const [typed, setTyped] = useState(wanted ? String(wanted) : '');
    const form = useForm({ new_id: wanted ?? 0 });
    const check = (event: FormEvent) => {
        event.preventDefault();
        router.get(`/admin/users/${client.id}/change-id`, { to: typed }, { preserveScroll: true });
    };
    const change = () => {
        if (!window.confirm(__('admin.user_id_confirm', { from: client.id, to: wanted ?? '' }))) return;
        form.transform(() => ({ new_id: wanted }));
        form.post(`/admin/users/${client.id}/change-id`);
    };
    const rows = moves.reduce((sum, move) => sum + move.rows, 0);

    return (
        <AdminSidebarLayout>
            <Head title={__('admin.user_id_change_title')} />
            <div className="mx-auto max-w-2xl min-w-0 space-y-6 overflow-x-clip p-4 sm:p-6">
                <div>
                    <Link href={`/admin/users/${client.id}`} className="inline-flex min-h-11 items-center text-sm text-blue-600 underline">{client.name}</Link>
                    <h1 className="text-2xl font-bold">{__('admin.user_id_change_title')}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">{__('admin.user_id_now', { id: client.id })} · <span dir="ltr">{client.email}</span></p>
                </div>

                <form onSubmit={check} className="space-y-2">
                    <Label htmlFor="new-id">{__('admin.user_id_new')}</Label>
                    <div className="flex flex-wrap gap-2">
                        <Input id="new-id" type="number" min={1} inputMode="numeric" dir="ltr" className="w-40" value={typed} onChange={(event) => setTyped(event.target.value)} required />
                        <Button type="submit" variant="outline" className="min-h-11">{__('admin.user_id_check')}</Button>
                    </div>
                    <Availability wanted={wanted} free={free} />
                    {form.errors.new_id && <p className="text-sm text-red-700">{form.errors.new_id}</p>}
                </form>

                <section className="space-y-2">
                    <h2 className="font-semibold">{__('admin.user_id_moves', { rows })}</h2>
                    <p className="text-sm text-muted-foreground">{__('admin.user_id_moves_hint')}</p>
                    {moves.length > 0 && (
                        <ul className="divide-y border-y text-sm">
                            {moves.map((move) => (
                                <li key={`${move.table}.${move.column}`} className="flex justify-between gap-4 py-2" dir="ltr">
                                    <span className="break-all">{move.table}.{move.column}</span>
                                    <span className="shrink-0 tabular-nums">{move.rows}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {free && wanted !== null && (
                    <Button type="button" onClick={change} disabled={form.processing} className="min-h-11">
                        {__('admin.user_id_change_to', { id: wanted })}
                    </Button>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
