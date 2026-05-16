import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

export default function Index() {
    const ordersAsBuyer = [
        {
            id: 'ORD-1029',
            serviceTitle: 'Logo Design for Startups',
            package: 'Pro Package',
            otherParty: '@sara_design',
            amount: 99.00,
            status: 'in_progress',
            deadline: 'In 3 days',
            serviceId: 1
        },
        {
            id: 'ORD-1015',
            serviceTitle: 'SEO Optimization & Marketing',
            package: 'Standard',
            otherParty: '@marketing_pro',
            amount: 50.00,
            status: 'completed',
            deadline: 'Oct 12, 2023',
            serviceId: 3
        }
    ];

    const getStatusBadge = (status) => {
        switch (status) {
            case 'pending': return <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-200">Pending</Badge>;
            case 'in_progress': return <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">In Progress</Badge>;
            case 'delivered': return <Badge variant="outline" className="bg-purple-50 text-purple-700 border-purple-200">Delivered</Badge>;
            case 'completed': return <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Completed</Badge>;
            case 'cancelled': return <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200">Cancelled</Badge>;
            default: return <Badge variant="secondary">Unknown</Badge>;
        }
    };

    return (
        <MarketplaceLayout>
            <Head title="My Orders" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex justify-between items-end mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Manage Orders</h1>
                        <p className="text-gray-500 mt-1">Track and manage your marketplace purchases and sales.</p>
                    </div>
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Tabs defaultValue="buyer" className="w-full">
                            <TabsList className="w-full justify-start border-b rounded-none h-14 px-4 bg-gray-50/50">
                                <TabsTrigger value="buyer" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md px-6 py-2">
                                    As Buyer
                                </TabsTrigger>
                                <TabsTrigger value="seller" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md px-6 py-2">
                                    As Seller
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="buyer" className="m-0 border-0 p-0">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                                            <tr>
                                                <th className="px-6 py-4 font-medium">Order ID</th>
                                                <th className="px-6 py-4 font-medium">Service & Package</th>
                                                <th className="px-6 py-4 font-medium">Seller</th>
                                                <th className="px-6 py-4 font-medium">Amount</th>
                                                <th className="px-6 py-4 font-medium">Status</th>
                                                <th className="px-6 py-4 font-medium">Deadline</th>
                                                <th className="px-6 py-4 font-medium text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200">
                                            {ordersAsBuyer.map((order) => (
                                                <tr key={order.id} className="bg-white hover:bg-gray-50 transition-colors">
                                                    <td className="px-6 py-4 font-mono text-xs text-gray-500">{order.id}</td>
                                                    <td className="px-6 py-4">
                                                        <div className="font-medium text-gray-900">{order.serviceTitle}</div>
                                                        <div className="text-gray-500 mt-0.5">{order.package}</div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <span className="inline-flex items-center text-indigo-600 hover:underline cursor-pointer">
                                                            {order.otherParty}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 font-medium text-gray-900">
                                                        ${order.amount.toFixed(2)}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {getStatusBadge(order.status)}
                                                    </td>
                                                    <td className="px-6 py-4 text-gray-500">
                                                        {order.deadline}
                                                    </td>
                                                    <td className="px-6 py-4 text-right">
                                                        <Link href={route('marketplace.orders.show', order.id)}>
                                                            <Button variant="outline" size="sm">
                                                                View Order &rarr;
                                                            </Button>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                            {ordersAsBuyer.length === 0 && (
                                                <tr>
                                                    <td colSpan="7" className="px-6 py-12 text-center text-gray-500">
                                                        No orders found as a buyer.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </TabsContent>

                            <TabsContent value="seller" className="m-0 border-0 p-0">
                                <div className="p-12 text-center text-gray-500">
                                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                        <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-1">No sales yet</h3>
                                    <p>When someone buys your services, the orders will appear here.</p>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>
        </MarketplaceLayout>
    );
}
