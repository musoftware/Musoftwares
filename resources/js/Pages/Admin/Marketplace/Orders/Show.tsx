import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { AlertTriangle, CheckCircle, ShieldAlert, MessageCircle, ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export default function Show({ order }) {
    const [processing, setProcessing] = useState(false);
    const [pendingAction, setPendingAction] = useState<string | null>(null);

    const handleAction = (actionType: string) => {
        setPendingAction(actionType);
    };

    const confirmAction = () => {
        if (!pendingAction) return;
        setProcessing(true);
        router.post(`/admin/marketplace/orders/${order.id}/dispute`, { action: pendingAction }, {
            preserveScroll: true,
            onSuccess: () => {
                setProcessing(false);
                setPendingAction(null);
                toast.success(__('general.action_completed') || 'Action completed');
            },
            onError: () => {
                setProcessing(false);
                setPendingAction(null);
                toast.error(__('general.error_occurred') || 'Something went wrong');
            },
        });
    };

    const actionLabels: Record<string, { title: string; description: string; confirmLabel: string; icon: React.ElementType; cls: string }> = {
        release_to_seller: {
            title: __('general.release_funds_to_seller') || 'Release funds to seller?',
            description: __('general.confirm_release_funds_desc') || 'The funds will be released to the seller. This action cannot be undone.',
            confirmLabel: __('general.release_funds'),
            icon: CheckCircle,
            cls: 'bg-emerald-600 hover:bg-emerald-700 text-white',
        },
        refund_buyer: {
            title: __('general.refund_buyer') || 'Refund buyer & cancel order?',
            description: __('general.confirm_refund_buyer_desc') || 'The buyer will be refunded and the order will be cancelled.',
            confirmLabel: __('general.refund_buyer'),
            icon: AlertTriangle,
            cls: 'bg-rose-600 hover:bg-rose-700 text-white',
        },
    };

    const meta = pendingAction ? actionLabels[pendingAction] : null;

    return (
        <AdminSidebarLayout title={`${__('general.order')} #${order.id}`} header={`${__('general.order')} #${order.id}`}>
            <div className="mb-4">
                <Link href="/admin/marketplace/orders" className="text-sm text-slate-500 hover:text-black inline-flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_orders')}
                </Link>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="col-span-2 space-y-6">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start mb-6 border-b pb-4">
                                <div>
                                    <h2 className="text-xl font-bold text-slate-800">{__('general.order_information')}</h2>
                                    <p className="text-sm text-slate-500 mt-1">{order.package?.service?.title || __('general.unknown')}</p>
                                </div>
                                <div className="text-end">
                                    <span className="block text-2xl font-bold font-mono text-slate-900">
                                        {formatMoney(order.amount, order.currency)}
                                    </span>
                                    <div className="mt-1">
                                        <StatusBadge status={order.status} />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="text-slate-500 block">{__('general.buyer')}</span>
                                    <span className="font-medium">{order.buyer?.name} ({order.buyer?.email})</span>
                                </div>
                                <div>
                                    <span className="text-slate-500 block">{__('general.seller')}</span>
                                    <span className="font-medium">{order.seller?.name} ({order.seller?.email})</span>
                                </div>
                                <div>
                                    <span className="text-slate-500 block">{__('general.placed_on')}</span>
                                    <span>{new Date(order.created_at).toLocaleString()}</span>
                                </div>
                                <div>
                                    <span className="text-slate-500 block">{__('general.last_updated')}</span>
                                    <span>{new Date(order.updated_at).toLocaleString()}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <h2 className="text-lg font-bold text-slate-800 mb-4 border-b pb-2">{__('general.order_chat_files')}</h2>
                            <div className="p-8 text-center text-slate-500 border-2 border-dashed border-slate-200 rounded-lg">
                                <MessageCircle className="mx-auto h-8 w-8 mb-2 opacity-50" />
                                <p>{__('general.admin_view_of_order_communications_will_be_rendered_here')}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="col-span-1">
                    <div className="bg-slate-50 rounded-xl shadow-sm border border-slate-200 p-6 sticky top-24">
                        <h2 className="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
                            <ShieldAlert className="h-5 w-5 text-slate-900" />{__('general.admin_actions')}
                        </h2>

                        {(order.status === 'completed' || order.status === 'cancelled') ? (
                            <div className="bg-slate-100 text-slate-600 p-4 rounded-lg text-sm flex items-start gap-2">
                                <CheckCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                <p>{__('general.this_order_is_closed_and_no_further_administrative_actions_can_be_taken')}</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-sm text-slate-600">{__('general.if_this_order_is_under_dispute_you_can_step_in_to_forcibly_resolve_the_transaction')}</p>

                                <Button
                                    className="w-full justify-start bg-emerald-600 hover:bg-emerald-700 text-white"
                                    onClick={() => handleAction('release_to_seller')}
                                    disabled={processing}
                                >
                                    <CheckCircle className="me-2 h-4 w-4" />{__('general.release_funds_to_seller')}
                                </Button>

                                <Button
                                    className="w-full justify-start bg-rose-600 hover:bg-rose-700 text-white"
                                    onClick={() => handleAction('refund_buyer')}
                                    disabled={processing}
                                >
                                    <AlertTriangle className="me-2 h-4 w-4" />
                                    {__('general.refund_buyer')} ({__('general.cancel_order')})
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingAction !== null}
                title={meta?.title ?? ''}
                description={meta?.description ?? ''}
                confirmLabel={meta?.confirmLabel ?? __('general.confirm')}
                cancelLabel={__('general.cancel')}
                variant={pendingAction === 'refund_buyer' ? 'danger' : 'default'}
                loading={processing}
                onConfirm={confirmAction}
                onCancel={() => setPendingAction(null)}
            />
        </AdminSidebarLayout>
    );
}