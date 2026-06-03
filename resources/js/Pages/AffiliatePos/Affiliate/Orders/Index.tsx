import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { __ } from '@/lib/i18n';

export default function AffiliateOrdersIndex({ orders, filters }: any) {
    const [status, setStatus] = useState(filters?.status || 'all');

    const handleSearch = () => {
        router.get(route('affiliate_pos.affiliate.orders.index'), { status: status !== 'all' ? status : undefined }, { preserveState: true });
    };

    return (
        <div className="p-6 max-w-[1600px] mx-auto space-y-6">
            <Head title={__('general.my_conversions')} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">{__('general.my_conversions')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.track_your_affiliate_sales_and_commissions')}</p>
                </div>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardHeader className="bg-gray-50/50 border-b p-4">
                    <Select value={status} onValueChange={(val) => { setStatus(val); handleSearch(); }}>
                        <SelectTrigger className="w-[200px] bg-white">
                            <SelectValue placeholder={__('general.filter_by_status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{__('general.all_orders')}</SelectItem>
                            <SelectItem value="new">New</SelectItem>
                            <SelectItem value="delivered">Delivered</SelectItem>
                            <SelectItem value="returned">Returned</SelectItem>
                        </SelectContent>
                    </Select>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">{__('general.order_id')}</TableHead>
                                <TableHead className="font-semibold text-gray-600">Customer</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Commission (EGP)</TableHead>
                                <TableHead className="font-semibold text-gray-600">Status</TableHead>
                                <TableHead className="font-semibold text-gray-600">Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {(orders.data as any).length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-32 text-center text-gray-500">{__('general.no_conversions_found')}</TableCell>
                                </TableRow>
                            ) : (
                                (orders.data as any).map((order: any) => (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-mono text-sm">#{order.unique_id}</TableCell>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{order.customer_name}</div>
                                            <div className="text-xs text-gray-500">{order.customer_governorate}</div>
                                        </TableCell>
                                        <TableCell className="text-right text-green-600 font-semibold text-lg">
                                            {order.commission.toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={order.status === 'delivered' ? 'default' : 'outline'} className="capitalize">
                                                {order.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-500">
                                            {new Date(order.created_at).toLocaleDateString()}
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
