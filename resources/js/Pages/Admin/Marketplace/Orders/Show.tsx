import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { AlertTriangle, CheckCircle, ShieldAlert } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function Show({ order }) {
    const { post, processing } = useForm({
        action: '',
    });



    const handleAction = (actionType) => {
        if (!confirm(`Are you sure you want to ${actionType.replace('_', ' ')}? This action cannot be undone.`)) {
            return;
        }

        post(`/admin/marketplace/orders/${order.id}/dispute`, {
            data: { action: actionType },
            preserveScroll: true,
        });
    };

    return (
        <AdminSidebarLayout title={`Order #${order.id}`} header={`Manage Order #${order.id}`}>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {/* Order Details */}
                <div className="col-span-2 space-y-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div className="flex justify-between items-start mb-6 border-b pb-4">
                            <div>
                                <h2 className="text-xl font-bold font-sora text-slate-800">Order Information</h2>
                                <p className="text-sm text-slate-500 mt-1">{order.package?.service?.title || 'Unknown Service'}</p>
                            </div>
                            <div className="text-right">
                                <span className="block text-2xl font-bold font-jetbrains text-slate-900">
                                    {formatCurrency(order.amount, order.currency_code)}
                                </span>
                                <Badge className="mt-1" variant={order.status === 'disputed' ? 'destructive' : 'secondary'}>
                                    {order.status.toUpperCase()}
                                </Badge>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="text-slate-500 block">Buyer</span>
                                <span className="font-medium">{order.buyer?.name} ({order.buyer?.email})</span>
                            </div>
                            <div>
                                <span className="text-slate-500 block">Seller</span>
                                <span className="font-medium">{order.seller?.name} ({order.seller?.email})</span>
                            </div>
                            <div>
                                <span className="text-slate-500 block">Placed On</span>
                                <span>{new Date(order.created_at).toLocaleString()}</span>
                            </div>
                            <div>
                                <span className="text-slate-500 block">Last Updated</span>
                                <span>{new Date(order.updated_at).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 className="text-lg font-bold font-sora text-slate-800 mb-4 border-b pb-2">Order Chat & Files</h2>
                        <div className="p-8 text-center text-slate-500 border-2 border-dashed border-slate-200 rounded-lg">
                            <MessageCircle className="mx-auto h-8 w-8 mb-2 opacity-50" />
                            <p>Admin view of order communications will be rendered here.</p>
                        </div>
                    </div>
                </div>

                {/* Dispute Resolution Panel */}
                <div className="col-span-1">
                    <div className="bg-slate-50 rounded-xl shadow-sm border border-slate-200 p-6 sticky top-24">
                        <h2 className="text-lg font-bold font-sora text-slate-800 flex items-center gap-2 mb-4">
                            <ShieldAlert className="h-5 w-5 text-indigo-600" />
                            Admin Actions
                        </h2>

                        {(order.status === 'completed' || order.status === 'cancelled') ? (
                            <div className="bg-slate-100 text-slate-600 p-4 rounded-lg text-sm flex items-start gap-2">
                                <CheckCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                <p>This order is closed and no further administrative actions can be taken.</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-sm text-slate-600">
                                    If this order is under dispute, you can step in to forcibly resolve the transaction.
                                </p>
                                
                                <Button 
                                    className="w-full justify-start bg-green-600 hover:bg-green-700 text-white" 
                                    onClick={() => handleAction('release_to_seller')}
                                    disabled={processing}
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Release Funds to Seller
                                </Button>

                                <Button 
                                    className="w-full justify-start bg-red-600 hover:bg-red-700 text-white" 
                                    onClick={() => handleAction('refund_buyer')}
                                    disabled={processing}
                                >
                                    <AlertTriangle className="mr-2 h-4 w-4" />
                                    Refund Buyer (Cancel Order)
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

            </div>
        </AdminSidebarLayout>
    );
}
