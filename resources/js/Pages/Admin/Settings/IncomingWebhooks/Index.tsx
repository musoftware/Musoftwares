import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Eye, Webhook, CheckCircle2, Clock, XCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Webhook {
    id: number;
    source: string;
    event_type: string | null;
    status: string;
    created_at: string;
    processed_at: string | null;
}

interface Props {
    webhooks: {
        data: Webhook[];
        links: any[];
    };
}

export default function Index({ webhooks }: Props) {
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'processed':
                return <Badge className="bg-emerald-100 text-emerald-800"><CheckCircle2 className="w-3 h-3 me-1" /> {__('general.processed')}</Badge>;
            case 'failed':
                return <Badge className="bg-rose-100 text-rose-800"><XCircle className="w-3 h-3 me-1" /> {__('general.failed')}</Badge>;
            default:
                return <Badge className="bg-amber-100 text-amber-800"><Clock className="w-3 h-3 me-1" /> {__('general.pending')}</Badge>;
        }
    };

    return (
        <AdminSidebarLayout title={__('general.incoming_webhooks')} header="Incoming Webhooks">
            <Head title={__('general.incoming_webhooks')} />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                            <Webhook className="w-6 h-6 text-slate-900" /> {__('general.incoming_webhooks')}</h1>
                        <p className="text-slate-500 mt-1">{__('general.monitor_webhooks_received_from_external')}</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{__('general.recent_webhooks')}</CardTitle>
                        <CardDescription>{__('general.a_log_of_all_incoming_requests_sent_to_t')}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-start">
                                <thead className="bg-slate-50 text-slate-500 uppercase text-xs">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">ID</th>
                                        <th className="px-6 py-3 font-medium">{__('general.source')}</th>
                                        <th className="px-6 py-3 font-medium">{__('general.event_type')}</th>
                                        <th className="px-6 py-3 font-medium">{__('general.status')}</th>
                                        <th className="px-6 py-3 font-medium">{__('general.received_at')}</th>
                                        <th className="px-6 py-3 font-medium text-end">{__('general.action')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {webhooks.data.length > 0 ? (
                                        webhooks.data.map((webhook) => (
                                            <tr key={webhook.id} className="hover:bg-slate-50 transition-colors">
                                                <td className="px-6 py-4 font-medium text-slate-900">#{webhook.id}</td>
                                                <td className="px-6 py-4"><Badge variant="outline">{webhook.source}</Badge></td>
                                                <td className="px-6 py-4 text-slate-600">{webhook.event_type || 'Unknown'}</td>
                                                <td className="px-6 py-4">{getStatusBadge(webhook.status)}</td>
                                                <td className="px-6 py-4 text-slate-500">{new Date(webhook.created_at).toLocaleString()}</td>
                                                <td className="px-6 py-4 text-end">
                                                    <Link 
                                                        href={route('admin.settings.incoming-webhooks.show', webhook.id)}
                                                        className="inline-flex items-center text-slate-900 hover:text-indigo-900"
                                                    >
                                                        <Eye className="w-4 h-4 me-1" /> {__('general.view')}</Link>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={6} className="px-6 py-8 text-center text-slate-500">
                                                {__('general.no_incoming_webhooks_logged_yet')}</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
