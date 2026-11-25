import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { LastCheckIn, PriceText, StateText, SystemSummary } from '@/Components/ClientSystems/shared';

interface Props {
    systems: { data: SystemSummary[]; links: { url: string | null; label: string; active: boolean }[] };
}

export default function Index({ systems }: Props) {
    return (
        <AdminSidebarLayout>
            <Head title={__('client_systems.index_title')} />
            <div className="mx-auto max-w-5xl min-w-0 space-y-6 overflow-x-clip p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-bold">{__('client_systems.index_title')}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">{__('client_systems.index_hint')}</p>
                </div>

                {systems.data.length === 0 ? (
                    <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">{__('client_systems.index_empty')}</p>
                ) : (
                    <ul className="divide-y rounded-md border">
                        {systems.data.map((system) => (
                            <li key={system.id} className="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                                <div className="min-w-0 space-y-0.5">
                                    <Link href={`/admin/client-systems/${system.id}`} className="font-semibold text-blue-700 underline">{system.name}</Link>
                                    <div className="text-muted-foreground">{system.user?.name} · <PriceText system={system} /></div>
                                    <div className="text-muted-foreground">{__('client_systems.last_check_in')}: <LastCheckIn at={system.last_check_in_at} /></div>
                                    {system.has_alert && <div className="font-semibold text-red-700">{__('client_systems.alert_changed_short')}</div>}
                                </div>
                                <StateText state={system.state} />
                            </li>
                        ))}
                    </ul>
                )}

                {systems.links.length > 3 && <nav aria-label={__('client_systems.pages')} className="flex flex-wrap gap-1">
                    {systems.links.filter((link) => link.url).map((link) => (
                        <Link key={link.label} href={link.url as string} className={`min-h-11 rounded px-3 py-2 text-sm ${link.active ? 'bg-slate-900 text-white' : 'border'}`} dangerouslySetInnerHTML={{ __html: link.label }} />
                    ))}
                </nav>}
            </div>
        </AdminSidebarLayout>
    );
}
