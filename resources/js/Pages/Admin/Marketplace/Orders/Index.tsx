import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function Index({ orders }) {


    const getStatusBadge = (status) => {
        switch (status) {
            case 'completed': return <Badge variant="success" className="bg-green-100 text-green-800">Completed</Badge>;
            case 'in_progress': return <Badge variant="secondary" className="bg-blue-100 text-blue-800">{__('general.in_progress')}</Badge>;
            case 'disputed': return <Badge variant="destructive" className="bg-red-100 text-red-800">Disputed</Badge>;
            case 'cancelled': return <Badge variant="outline" className="text-gray-500 border-gray-300">Cancelled</Badge>;
            default: return <Badge variant="outline" className="text-gray-600">{status}</Badge>;
        }
    };

    return (
        <AdminSidebarLayout title={__('general.marketplace_orders')} header="Marketplace Orders">
            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">ID</th>
                            <th className="p-4 font-medium text-gray-600">Service</th>
                            <th className="p-4 font-medium text-gray-600">Buyer</th>
                            <th className="p-4 font-medium text-gray-600">Seller</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Amount</th>
                            <th className="p-4 font-medium text-gray-600 text-center">Status</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.data.map((order) => (
                            <tr key={order.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 text-gray-500">#{order.id}</td>
                                <td className="p-4 font-medium text-gray-900">
                                    {order.package?.service?.title || 'Unknown Service'}
                                </td>
                                <td className="p-4 text-gray-600">{order.buyer?.name || 'Unknown'}</td>
                                <td className="p-4 text-gray-600">{order.seller?.name || 'Unknown'}</td>
                                <td className="p-4 text-right font-jetbrains font-medium text-gray-900">
                                    {order.formatted_amount}
                                </td>
                                <td className="p-4 text-center">
                                    {getStatusBadge(order.status)}
                                </td>
                                <td className="p-4 text-right">
                                    <Link
                                        href={`/admin/marketplace/orders/${order.id}`}
                                        className="text-indigo-600 hover:text-indigo-900 hover:underline font-medium text-sm"
                                    >
                                        Manage
                                    </Link>
                                </td>
                            </tr>
                        ))}
                        {orders.data.length === 0 && (
                            <tr>
                                <td colSpan="7" className="p-8 text-center text-gray-500">{__('general.no_marketplace_orders_found')}</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {orders.total > orders.per_page && (
                <div className="mt-4 flex justify-between text-sm text-gray-600">
                    <div>
                        Showing {orders.from || 0} to {orders.to || 0} of {orders.total} entries
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}
