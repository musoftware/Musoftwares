import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Eye, Webhook, CheckCircle2, Clock, XCircle, Search, FilterX } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
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
    filters?: { q?: string; status?: string; source?: string };
}

export default function Index({ webhooks, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    const getStatusBadge = (s: string) => {
        switch (s) {
            case 'processed':
                return <Badge className="bg-green-100 text-green-800"><CheckCircle2 className="w-3 h-3 me-1" /> {__('general.processed')}</Badge>;
            case 'failed':
                return <Badge className="bg-red-100 text-red-800"><XCircle className="w-3 h-3 me-1" /> {__('general.failed')}</Badge>;
            default:
                return <Badge className="bg-yellow-100 text-yellow-800"><Clock className="w-3 h-3 me-1" /> {__('general.pending')}</Badge>;
        }
    };

    const applyFilters = () => {
        router.get(route('admin.settings.incoming-webhooks.index'), {
            q: search || undefined,
            status: status === 'all' ? undefined : status,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
        router.get(route('admin.settings.incoming-webhooks.index'));
    };

    const data = webhooks.data ?? [];
    const hasActiveFilters = !!(search || status !== 'all');

    return (
        <AdminSidebarLayout title={__('general.incoming_webhooks')} header="Incoming Webhooks">
            <Head title={__('general.incoming_webhooks')} />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                            <Webhook className="w-6 h-6 text-slate-900" /> {__('general.incoming_webhooks')}
                        </h1>
                        <p className="text-slate-500 mt-1">{__('general.monitor_webhooks_received_from_external')}</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{__('general.recent_webhooks')}</CardTitle>
                        <CardDescription>{__('general.a_log_of_all_incoming_requests_sent_to_t')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap items-center gap-3 mb-4">
                            <div className="relative flex-1 min-w-48 max-w-sm">
                                <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <Input
                                    placeholder={__('general.search_webhooks') || 'Search by source or event...'}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    className="ps-9"
                                />
                            </div>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="h-9 rounded-md border border-slate-200 px-3 text-sm bg-white"
                            >
                                <option value="all">{__('general.all_statuses')}</option>
                                <option value="processed">{__('general.processed')}</option>
                                <option value="pending">{__('general.pending')}</option>
                                <option value="failed">{__('general.failed')}</option>
                            </select>
                            <Button onClick={applyFilters} variant="outline" size="sm">{__('general.apply') || 'Apply'}</Button>
                            {hasActiveFilters && (
                                <Button onClick={clearFilters} variant="ghost" size="sm">
                                    <FilterX className="w-3.5 h-3.5 me-1" />{__('general.clear')}
                                </Button>
                            )}
                        </div>

                        {data.length === 0 ? (
                            <EmptyState
                                icon={Webhook}
                                title={__('general.no_incoming_webhooks_logged_yet') || 'No webhooks yet'}
                                description={__('general.webhook_will_appear_here') || 'Incoming webhook events will appear here.'}
                            />
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>ID</TableHead>
                                        <TableHead>{__('general.source')}</TableHead>
                                        <TableHead>{__('general.event_type')}</TableHead>
                                        <TableHead>{__('general.status')}</TableHead>
                                        <TableHead>{__('general.received_at')}</TableHead>
                                        <TableHead className="text-end">{__('general.action')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.map((webhook) => (
                                        <TableRow key={webhook.id}>
                                            <TableCell className="font-medium">#{webhook.id}</TableCell>
                                            <TableCell><Badge variant="outline">{webhook.source}</Badge></TableCell>
                                            <TableCell className="text-slate-600">{webhook.event_type || '—'}</TableCell>
                                            <TableCell>{getStatusBadge(webhook.status)}</TableCell>
                                            <TableCell className="text-slate-500 text-sm">{new Date(webhook.created_at).toLocaleString()}</TableCell>
                                            <TableCell className="text-end">
                                                <Button asChild variant="ghost" size="sm">
                                                    <Link href={route('admin.settings.incoming-webhooks.show', webhook.id)}>
                                                        <Eye className="w-4 h-4 me-1" /> {__('general.view')}
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}