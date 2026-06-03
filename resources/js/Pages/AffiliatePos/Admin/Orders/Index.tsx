import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Truck, Package, Clock, XCircle, CheckCircle2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function OrdersIndex({ orders, filters }: any) {
    const [search, setSearch] = useState(filters?.search || '');
    const [status, setStatus] = useState(filters?.status || 'all');

    const handleSearch = () => {
        router.get(route('affiliate_pos.admin.orders.index'), { search, status: status !== 'all' ? status : undefined }, { preserveState: true });
    };

    const handleStatusUpdate = (id: number, newStatus: string) => {
        if (confirm(`Change status to ${newStatus}?`)) {
            router.patch(route('affiliate_pos.admin.orders.status', { order: id }), { status: newStatus }, { preserveScroll: true });
        }
    };

    const StatusBadge = ({ status }: { status: string }) => {
        const variants: any = {
            'new': { color: 'bg-blue-100 text-blue-800', icon: Clock },
            'preparing': { color: 'bg-amber-100 text-amber-800', icon: Package },
            'shipping': { color: 'bg-indigo-100 text-indigo-800', icon: Truck },
            'delivered': { color: 'bg-green-100 text-green-800', icon: CheckCircle2 },
            'cancelled': { color: 'bg-red-100 text-red-800', icon: XCircle },
            'returning': { color: 'bg-orange-100 text-orange-800', icon: Truck },
            'returned': { color: 'bg-gray-100 text-gray-800', icon: Package },
        };
        const config = variants[status] || { color: 'bg-gray-100 text-gray-800', icon: Package };
        const Icon = config.icon;
        
        return (
            <Badge className={`${config.color} border-none flex w-fit items-center gap-1.5 capitalize px-2.5 py-0.5`}>
                <Icon className="w-3.5 h-3.5" />
                {status}
            </Badge>
        );
    };

    return (
        <div className="p-6 max-w-[1600px] mx-auto space-y-6 font-sans">
            <Head title={__('general.order_management')} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">{__('general.order_management')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_and_track_all_affiliate_and_store_orders')}</p>
                </div>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardHeader className="bg-gray-50/50 border-b p-4">
                    <div className="flex items-center gap-4">
                        <div className="flex-1 max-w-sm">
                            <Input 
                                placeholder={__('general.search_by_order_id_name_or_phone')} 
                                value={search}
                                onChange={(e) => setSearch(String(e.target.value))}
                                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                className="bg-white"
                            />
                        </div>
                        <Select value={status} onValueChange={(val) => { setStatus(val); handleSearch(); }}>
                            <SelectTrigger className="w-[180px] bg-white">
                                <SelectValue placeholder={__('general.filter_by_status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{__('general.all_orders')}</SelectItem>
                                <SelectItem value="new">New</SelectItem>
                                <SelectItem value="preparing">Preparing</SelectItem>
                                <SelectItem value="shipping">Shipping</SelectItem>
                                <SelectItem value="delivered">Delivered</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" onClick={handleSearch} className="ml-auto">{__('general.apply_filters')}</Button>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80 hover:bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">{__('general.order_id')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">{__('general.customer_details')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">Location</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Total (EGP)</TableHead>
                                <TableHead className="font-semibold text-gray-600">Status</TableHead>
                                <TableHead className="font-semibold text-gray-600">Date</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {(orders.data as any).length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-32 text-center text-gray-500">{__('general.no_orders_found')}</TableCell>
                                </TableRow>
                            ) : (
                                (orders.data as any).map((order: any) => (
                                    <TableRow key={order.id} className="group">
                                        <TableCell>
                                            <div className="font-mono text-sm font-medium text-gray-900">#{order.unique_id}</div>
                                            <div className="text-xs text-gray-500 mt-1">ID: {order.id}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{order.customer_name}</div>
                                            <div className="text-sm text-gray-500 font-mono">{order.customer_phone}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-gray-900">{order.customer_governorate}</div>
                                            <div className="text-sm text-gray-500 truncate max-w-[150px]" title={order.customer_address}>
                                                {order.customer_address}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="font-semibold text-gray-900">{order.total.toLocaleString()}</div>
                                            <div className="text-xs text-green-600 mt-0.5">Comm: {order.commission.toLocaleString()}</div>
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge status={order.status} />
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-gray-600">
                                                {new Date(order.created_at).toLocaleDateString('en-GB')}
                                            </div>
                                            <div className="text-xs text-gray-400">
                                                {new Date(order.created_at).toLocaleTimeString('en-GB')}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Select onValueChange={(val) => handleStatusUpdate(order.id, String(val))}>
                                                    <SelectTrigger className="w-[130px] h-8 text-xs">
                                                        <SelectValue placeholder={__('general.update')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="preparing">Preparing</SelectItem>
                                                        <SelectItem value="shipping">Shipping</SelectItem>
                                                        <SelectItem value="delivered">Delivered</SelectItem>
                                                        <SelectItem value="cancelled">Cancelled</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <Link href={route('affiliate_pos.admin.orders.show', { order: order.id })}>
                                                    <Button size="sm" variant="secondary" className="h-8">View</Button>
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
