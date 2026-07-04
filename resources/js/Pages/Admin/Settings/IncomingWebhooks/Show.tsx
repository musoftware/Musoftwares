import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeft, CheckCircle2, Clock, XCircle, FileJson } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Webhook {
    id: number;
    source: string;
    event_type: string | null;
    payload: any;
    headers: any;
    status: string;
    error_message: string | null;
    created_at: string;
    processed_at: string | null;
}

interface Props {
    webhook: Webhook;
}

export default function Show({ webhook }: Props) {
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'processed':
                return <Badge className="bg-green-100 text-green-800"><CheckCircle2 className="w-4 h-4 me-1" /> {__('general.processed')}</Badge>;
            case 'failed':
                return <Badge className="bg-red-100 text-red-800"><XCircle className="w-4 h-4 me-1" /> {__('general.failed')}</Badge>;
            default:
                return <Badge className="bg-yellow-100 text-yellow-800"><Clock className="w-4 h-4 me-1" /> {__('general.pending')}</Badge>;
        }
    };

    return (
        <AdminSidebarLayout title={`Webhook #${webhook.id}`} header="Webhook Details">
            <Head title={`Webhook #${webhook.id}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('admin.settings.incoming-webhooks.index')} className="text-slate-500 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <h1 className="text-2xl font-bold text-slate-900">
                            Webhook #{webhook.id}
                        </h1>
                        {getStatusBadge(webhook.status)}
                    </div>
                </div>

                {webhook.error_message && (
                    <div className="bg-red-50 border-s-4 border-red-500 p-4 rounded-e-md">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <XCircle className="h-5 w-5 text-slate-900" aria-hidden="true" />
                            </div>
                            <div className="ms-3">
                                <h3 className="text-sm font-medium text-red-800">{__('general.processing_failed')}</h3>
                                <div className="mt-2 text-sm text-slate-900 font-mono bg-white/50 p-2 rounded">
                                    {webhook.error_message}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">{__('general.metadata')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-slate-500">{__('general.source')}</p>
                                    <p className="font-semibold text-slate-900 mt-1 uppercase">{webhook.source}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-slate-500">{__('general.event_type')}</p>
                                    <p className="font-semibold text-slate-900 mt-1">{webhook.event_type || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-slate-500">{__('general.received_at')}</p>
                                    <p className="font-semibold text-slate-900 mt-1">{new Date(webhook.created_at).toLocaleString()}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-slate-500">{__('general.processed_at')}</p>
                                    <p className="font-semibold text-slate-900 mt-1">
                                        {webhook.processed_at ? new Date(webhook.processed_at).toLocaleString() : 'Not processed yet'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <FileJson className="w-5 h-5 text-slate-400" /> {__('general.headers')}
                            </CardTitle>
                            <button
                                type="button"
                                onClick={() => {
                                    navigator.clipboard.writeText(JSON.stringify(webhook.headers, null, 2));
                                }}
                                className="text-xs text-slate-500 hover:text-slate-900"
                            >
                                {__('general.copy') || 'Copy'}
                            </button>
                        </CardHeader>
                        <CardContent>
                            <div className="bg-slate-900 rounded-md p-4 overflow-x-auto">
                                <pre className="text-slate-100 text-xs font-mono">
                                    {JSON.stringify(webhook.headers, null, 2)}
                                </pre>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <FileJson className="w-5 h-5 text-slate-400" /> {__('general.payload')}
                        </CardTitle>
                        <button
                            type="button"
                            onClick={() => {
                                navigator.clipboard.writeText(JSON.stringify(webhook.payload, null, 2));
                            }}
                            className="text-xs text-slate-500 hover:text-slate-900"
                        >
                            {__('general.copy') || 'Copy'}
                        </button>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-slate-900 rounded-md p-4 overflow-x-auto">
                            <pre className="text-slate-100 text-xs font-mono leading-relaxed">
                                {JSON.stringify(webhook.payload, null, 2)}
                            </pre>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
