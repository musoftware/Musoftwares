import React from 'react';
import { Head, router } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Package, Truck, CheckCircle2, Clock, MapPin, User, ArrowLeft, RefreshCw } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function OrderShow({ order }: any) {
    const handleStatusUpdate = (newStatus: string) => {
        if (confirm(`Change global status to ${newStatus}?`)) {
            router.patch(route('affiliate_pos.admin.orders.status', { order: order.id }), { status: newStatus }, { preserveScroll: true });
        }
    };



    const TimelineStep = ({ title, active, isLast }: { title: string, active: boolean, isLast?: boolean }) => (
        <div className="flex flex-col items-center">
            <div className={`w-10 h-10 rounded-full flex items-center justify-center ${active ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-400'}`}>
                <CheckCircle2 className="w-5 h-5" />
            </div>
            <div className={`text-xs mt-2 font-medium ${active ? 'text-blue-700' : 'text-gray-500'}`}>{title}</div>
        </div>
    );

    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6 font-sans">
            <Head title={`Order #${order.unique_id}`} />

            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" onClick={() => window.history.back()} className="rounded-full bg-white shadow-sm border border-gray-200">
                        <ArrowLeft className="w-5 h-5 text-gray-600" />
                    </Button>
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-gray-900 tracking-tight">Order #{order.unique_id}</h1>
                            <Badge className="bg-blue-100 text-blue-800 hover:bg-blue-100 border-none px-3 capitalize">
                                {order.status}
                            </Badge>
                        </div>
                        <p className="text-sm text-gray-500 mt-1">Placed on {new Date(order.created_at).toLocaleString()}</p>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <Select onValueChange={(val) => handleStatusUpdate(String(val))}>
                        <SelectTrigger className="w-[180px] bg-white">
                            <SelectValue placeholder={__('general.update_status_1')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="preparing">{__('general.preparing')}</SelectItem>
                            <SelectItem value="shipping">{__('general.shipping')}</SelectItem>
                            <SelectItem value="delivered">{__('general.delivered')}</SelectItem>
                            <SelectItem value="cancelled">{__('general.cancelled')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {/* Timeline */}
            <Card className="shadow-sm border-gray-200 bg-white">
                <CardContent className="p-6">
                    <div className="flex justify-between items-center relative px-8">
                        <div className="absolute top-5 start-16 end-16 h-0.5 bg-gray-100 z-0"></div>
                        <div className="absolute top-5 start-16 end-16 h-0.5 bg-blue-600 z-0 transition-all duration-500" style={{ width: order.status === 'new' ? '0%' : order.status === 'preparing' ? '33%' : order.status === 'shipping' ? '66%' : '100%' }}></div>
                        
                        <div className="relative z-10"><TimelineStep title={__('general.new_order')} active={true} /></div>
                        <div className="relative z-10"><TimelineStep title={__('general.preparing')} active={['preparing', 'shipping', 'delivered'].includes(order.status)} /></div>
                        <div className="relative z-10"><TimelineStep title={__('general.shipping')} active={['shipping', 'delivered'].includes(order.status)} /></div>
                        <div className="relative z-10"><TimelineStep title={__('general.delivered')} active={order.status === 'delivered'} isLast={true} /></div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Customer Info */}
                <Card className="shadow-sm border-gray-200 col-span-1">
                    <CardHeader className="bg-gray-50/50 border-b p-4">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <User className="w-5 h-5 text-gray-500" />{__('general.customer_details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-5 space-y-4">
                        <div>
                            <div className="text-sm text-gray-500">{__('general.name')}</div>
                            <div className="font-medium text-gray-900">{order.customer_name}</div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-500">{__('general.phone')}</div>
                            <div className="font-mono text-gray-900">{order.customer_phone}</div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-500 flex items-center gap-1 mt-4">
                                <MapPin className="w-4 h-4" />{__('general.shipping_address')}</div>
                            <div className="font-medium text-gray-900 mt-1">{order.customer_governorate}</div>
                            <div className="text-sm text-gray-600 mt-1 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                {order.customer_address}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Items */}
                <Card className="shadow-sm border-gray-200 col-span-1 md:col-span-2">
                    <CardHeader className="bg-gray-50/50 border-b p-4 flex flex-row items-center justify-between">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <Package className="w-5 h-5 text-gray-500" />{__('general.order_items')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-gray-50/80">
                                    <TableHead className="font-semibold text-gray-600">{__('general.product')}</TableHead>
                                    <TableHead className="font-semibold text-gray-600 text-center">Qty</TableHead>
                                    <TableHead className="font-semibold text-gray-600 text-end">{__('general.price')}</TableHead>
                                    <TableHead className="font-semibold text-gray-600 text-end">{__('general.commission')}</TableHead>
                                    <TableHead className="font-semibold text-gray-600 text-end">{__('general.total')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {order.items.map((item: any) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{item.product_name}</div>
                                            <div className="text-xs text-gray-500 font-mono mt-0.5">SKU: {item.sku_id}</div>
                                            {item.status !== 'delivered' && (
                                                <Badge variant="outline" className="mt-2 text-[10px] uppercase">
                                                    {item.status}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-center font-medium">{item.quantity}</TableCell>
                                        <TableCell className="text-end text-gray-600">{item.price}</TableCell>
                                        <TableCell className="text-end text-green-600 font-medium">{item.commission}</TableCell>
                                        <TableCell className="text-end font-semibold text-gray-900">
                                            {(item.price * item.quantity).toLocaleString()} EGP
                                        </TableCell>
                                    </TableRow>
                                ))}
                                <TableRow className="bg-gray-50/50">
                                    <TableCell colSpan={4} className="text-end font-medium text-gray-500">{__('general.subtotal')}</TableCell>
                                    <TableCell className="text-end font-bold text-gray-900">{order.subtotal.toLocaleString()} EGP</TableCell>
                                </TableRow>
                                <TableRow className="bg-gray-50/50">
                                    <TableCell colSpan={4} className="text-end font-medium text-gray-500">{__('general.shipping_fees')}</TableCell>
                                    <TableCell className="text-end font-bold text-gray-900">{order.shipping_fees.toLocaleString()} EGP</TableCell>
                                </TableRow>
                                <TableRow className="bg-blue-50/30">
                                    <TableCell colSpan={4} className="text-end font-semibold text-blue-900">{__('general.grand_total')}</TableCell>
                                    <TableCell className="text-end font-bold text-blue-700 text-lg">{order.total.toLocaleString()} EGP</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
