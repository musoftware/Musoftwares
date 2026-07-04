import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Search, FilterX, ExternalLink } from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { EmptyState } from '@/Components/ui/EmptyState';
import { MetricCard } from '@/Components/ui/MetricCard';
import { ShoppingBag, CheckCircle2, AlertCircle, XCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Index({ orders, stats }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const filtered = (orders.data ?? []).filter((o: any) => {
        const q = search.toLowerCase().trim();
        const matchSearch = !q ||
            o.id.toString().includes(q) ||
            o.package?.service?.title?.toLowerCase().includes(q) ||
            o.buyer?.name?.toLowerCase().includes(q) ||
            o.seller?.name?.toLowerCase().includes(q);
        const matchStatus = statusFilter === 'all' || o.status === statusFilter;
        return matchSearch && matchStatus;
    });

    const paginationLinks = orders.meta?.links || orders.links;

    return (
        <AdminSidebarLayout title={__('general.marketplace_orders')} header={__('general.marketplace_orders')}>
            <Head title={__('general.marketplace_orders')} />

            {stats && (
                <div className="mb-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <MetricCard label={__('general.total')} value={stats.total ?? orders.total ?? 0} icon={ShoppingBag} />
                    <MetricCard label={__('general.completed')} value={stats.completed ?? 0} icon={CheckCircle2} />
                    <MetricCard label={__('general.in_progress')} value={stats.in_progress ?? 0} icon={AlertCircle} />
                    <MetricCard label={__('general.disputed')} value={stats.disputed ?? 0} icon={XCircle} />
                </div>
            )}

            <Card className="mb-4 bg-white shadow-sm">
                <CardContent className="p-4 flex flex-wrap gap-3 items-center">
                    <div className="relative flex-1 min-w-[200px]">
                        <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                        <Input
                            placeholder={__('general.search_orders')}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="ps-8 h-9"
                        />
                    </div>

                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="h-9 rounded-md border border-gray-300 px-2 text-sm bg-white"
                    >
                        <option value="all">{__('general.all_statuses')}</option>
                        <option value="completed">{__('general.completed')}</option>
                        <option value="in_progress">{__('general.in_progress')}</option>
                        <option value="disputed">{__('general.disputed')}</option>
                        <option value="cancelled">{__('general.cancelled')}</option>
                    </select>

                    {(search || statusFilter !== 'all') && (
                        <Button variant="ghost" size="sm" onClick={() => { setSearch(''); setStatusFilter('all'); }} className="h-9">
                            <FilterX className="w-4 h-4 me-1" />{__('general.clear')}
                        </Button>
                    )}
                </CardContent>
            </Card>

            {filtered.length === 0 ? (
                <EmptyState
                    icon={ShoppingBag}
                    title={__('general.no_marketplace_orders_found')}
                    description={__('general.orders_will_appear_here') || 'Marketplace orders will appear here.'}
                />
            ) : (
                <Card className="bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50 border-b">
                                <TableHead className="uppercase text-xs">ID</TableHead>
                                <TableHead className="uppercase text-xs">{__('general.service')}</TableHead>
                                <TableHead className="uppercase text-xs">{__('general.buyer')}</TableHead>
                                <TableHead className="uppercase text-xs">{__('general.seller')}</TableHead>
                                <TableHead className="text-end uppercase text-xs">{__('general.amount')}</TableHead>
                                <TableHead className="text-center uppercase text-xs">{__('general.status')}</TableHead>
                                <TableHead className="text-end uppercase text-xs">{__('general.actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filtered.map((order: any) => (
                                <TableRow key={order.id}>
                                    <TableCell className="font-mono text-slate-500">#{order.id}</TableCell>
                                    <TableCell className="font-medium text-slate-900 max-w-[260px] truncate" title={order.package?.service?.title}>
                                        {order.package?.service?.title || __('general.unknown')}
                                    </TableCell>
                                    <TableCell className="text-slate-600">{order.buyer?.name || __('general.unknown')}</TableCell>
                                    <TableCell className="text-slate-600">{order.seller?.name || __('general.unknown')}</TableCell>
                                    <TableCell className="text-end font-mono font-medium text-slate-900">
                                        {formatMoney(order.amount, order.currency)}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <StatusBadge status={order.status} />
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <Button asChild variant="ghost" size="sm">
                                            <Link href={`/admin/marketplace/orders/${order.id}`}>
                                                {__('general.manage')}
                                                <ExternalLink className="w-3 h-3 ms-1" />
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Card>
            )}

            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                <div className="mt-4 flex justify-end">
                    <div className="text-sm text-slate-500">
                        {__('general.showing')} {orders.from || 0} {__('general.to')} {orders.to || 0} {__('general.of')} {orders.total} {__('general.entries')}
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}