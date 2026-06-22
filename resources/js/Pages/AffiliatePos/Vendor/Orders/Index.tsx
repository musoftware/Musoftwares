import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Package, Truck, CheckCircle2, Clock, XCircle, Eye } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function VendorOrdersIndex({ orders, filters }: any) {
    const [status, setStatus] = useState(filters?.status || 'all');

    const handleSearch = () => {
        router.get(route('affiliate_pos.vendor.orders.index'), { status: status !== 'all' ? status : undefined }, { preserveState: true });
    };

    return (
        <div className="p-6 max-w-[1600px] mx-auto space-y-6">
            <Head title={__('general.vendor_orders')} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">{__('general.sales_orders')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.track_orders_containing_your_products')}</p>
                </div>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardHeader className="bg-gray-50/50 border-b p-4">
                    <div className="flex gap-4">
                        <Select value={status} onValueChange={(val) => { setStatus(val); handleSearch(); }}>
                            <SelectTrigger className="w-[200px] bg-white">
                                <SelectValue placeholder={__('general.filter_by_status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{__('general.all_orders')}</SelectItem>
                                <SelectItem value="new">New</SelectItem>
                                <SelectItem value="preparing">{__('general.preparing')}</SelectItem>
                                <SelectItem value="shipping">{__('general.shipping')}</SelectItem>
                                <SelectItem value="delivered">{__('general.delivered')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">{__('general.order_id')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">{__('general.customer')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">{__('general.location')}</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-end">{__('general.items')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">{__('general.status')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">{__('general.date')}</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-end">{__('general.action')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {(orders.data as any).length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-32 text-center text-gray-500">{__('general.no_orders_found')}</TableCell>
                                </TableRow>
                            ) : (
                                (orders.data as any).map((order: any) => (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-mono text-sm">#{order.unique_id}</TableCell>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{order.customer_name}</div>
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-600">{order.customer_governorate}</TableCell>
                                        <TableCell className="text-end font-medium">{order.items?.length || 0} items</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="capitalize">{order.status}</Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-500">
                                            {new Date(order.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-end">
                                            <Link href={route('affiliate_pos.vendor.orders.show', order.id)}>
                                                <Button variant="ghost" size="sm" className="text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                                                    <Eye className="w-4 h-4 me-1" /> {__('general.view')}</Button>
                                            </Link>
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
