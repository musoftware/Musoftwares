import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import {
    AlertTriangle,
    CheckCircle,
    ShieldAlert,
    MessageCircle,
    ArrowLeft,
    FileText,
    Download,
    DollarSign,
    Lock,
    UserCheck,
    History,
    ShieldCheck
} from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Escrow {
    id: number;
    amount: number;
    currency_id: number;
    status: string;
    buyer_wallet_transaction_id?: number | null;
    seller_wallet_transaction_id?: number | null;
    released_at?: string | null;
    refunded_at?: string | null;
}

interface DeliveryFile {
    id: number;
    file_path: string;
    file_name: string;
    file_size?: number;
    note?: string;
    created_at: string;
}

interface MessageItem {
    id: number;
    sender_id: number;
    sender_name?: string | null;
    body: string;
    created_at?: string | null;
}

interface OrderDetail {
    id: number;
    buyer_id: number;
    seller_id: number;
    status: string;
    amount: number;
    commission_amount: number;
    seller_earnings: number;
    currency: string;
    notes?: string | null;
    created_at: string;
    updated_at: string;
    buyer?: { name?: string; email?: string };
    seller?: { name?: string; email?: string };
    package?: { service?: { title?: string } };
    escrow?: Escrow | null;
    delivery_files?: DeliveryFile[];
    messages?: MessageItem[];
}

export default function Show({ order }: { order: OrderDetail }) {
    const [processing, setProcessing] = useState(false);
    const [pendingAction, setPendingAction] = useState<string | null>(null);
    const [resolutionReason, setResolutionReason] = useState('');

    const handleAction = (actionType: string) => {
        setPendingAction(actionType);
        setResolutionReason('');
    };

    const confirmAction = () => {
        if (!pendingAction) return;
        setProcessing(true);
        router.post(
            `/admin/marketplace/orders/${order.id}/dispute`,
            {
                action: pendingAction,
                resolution_reason: resolutionReason,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setProcessing(false);
                    setPendingAction(null);
                    setResolutionReason('');
                    toast.success(__('general.action_completed') || 'Action completed successfully');
                },
                onError: (errors) => {
                    setProcessing(false);
                    const errMsg = errors?.error || __('general.error_occurred') || 'Something went wrong';
                    toast.error(errMsg);
                },
            }
        );
    };

    const actionLabels: Record<string, { title: string; description: string; confirmLabel: string; cls: string }> = {
        release_to_seller: {
            title: __('general.release_funds_to_seller') || 'Release Funds to Seller',
            description: __('general.confirm_release_funds_desc') || 'The escrowed funds will be credited to the seller wallet minus platform commission.',
            confirmLabel: __('general.release_funds') || 'Release Funds',
            cls: 'bg-emerald-600 hover:bg-emerald-700 text-white',
        },
        refund_buyer: {
            title: __('general.refund_buyer') || 'Refund Buyer & Cancel Order',
            description: __('general.confirm_refund_buyer_desc') || 'The escrowed funds will be fully refunded to the buyer wallet and order will be marked cancelled.',
            confirmLabel: __('general.refund_buyer') || 'Refund Buyer',
            cls: 'bg-rose-600 hover:bg-rose-700 text-white',
        },
    };

    const meta = pendingAction ? actionLabels[pendingAction] : null;
    const isClosed = order.status === 'completed' || order.status === 'cancelled';

    return (
        <AdminSidebarLayout title={`${__('general.order')} #${order.id}`} header={`${__('general.order')} #${order.id}`}>
            <Head title={`${__('general.order')} #${order.id}`} />

            <div className="mb-4">
                <Link href="/admin/marketplace/orders" className="text-sm text-slate-500 hover:text-black inline-flex items-center gap-1 font-medium">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_orders')}
                </Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Content Area */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Order Overview Card */}
                    <Card className="bg-white shadow-sm border border-slate-200">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
                                <div>
                                    <h2 className="text-xl font-bold text-slate-900">{__('general.order_information')}</h2>
                                    <p className="text-sm text-slate-500 mt-1">{order.package?.service?.title || __('general.unknown')}</p>
                                </div>
                                <div className="text-end">
                                    <span className="block text-2xl font-bold font-mono text-slate-900">
                                        {formatMoney(order.amount, order.currency)}
                                    </span>
                                    <div className="mt-1 flex justify-end">
                                        <StatusBadge status={order.status} />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div className="p-3 rounded-lg bg-slate-50 border border-slate-100">
                                    <span className="text-slate-500 block text-xs uppercase font-medium">{__('general.buyer')}</span>
                                    <span className="font-semibold text-slate-900 block mt-0.5">{order.buyer?.name || __('general.unknown')}</span>
                                    <span className="text-xs text-slate-500">{order.buyer?.email}</span>
                                </div>
                                <div className="p-3 rounded-lg bg-slate-50 border border-slate-100">
                                    <span className="text-slate-500 block text-xs uppercase font-medium">{__('general.seller')}</span>
                                    <span className="font-semibold text-slate-900 block mt-0.5">{order.seller?.name || __('general.unknown')}</span>
                                    <span className="text-xs text-slate-500">{order.seller?.email}</span>
                                </div>
                                <div>
                                    <span className="text-slate-500 block text-xs">{__('general.placed_on')}</span>
                                    <span className="font-medium text-slate-800">{new Date(order.created_at).toLocaleString()}</span>
                                </div>
                                <div>
                                    <span className="text-slate-500 block text-xs">{__('general.last_updated')}</span>
                                    <span className="font-medium text-slate-800">{new Date(order.updated_at).toLocaleString()}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Escrow Financial Ledger Card */}
                    <Card className="bg-white shadow-sm border border-slate-200">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                                <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <Lock className="w-5 h-5 text-indigo-600" />
                                    {__('general.escrow_summary')}
                                </h3>
                                {order.escrow && (
                                    <Badge className="uppercase font-mono text-xs bg-indigo-50 text-indigo-700 border-indigo-200">
                                        Escrow {order.escrow.status}
                                    </Badge>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                <div className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                    <span className="text-xs text-slate-500 block">{__('general.held_amount')}</span>
                                    <span className="text-lg font-bold font-mono text-slate-900">
                                        {formatMoney(order.amount, order.currency)}
                                    </span>
                                </div>
                                <div className="p-3 bg-amber-50/50 rounded-lg border border-amber-100">
                                    <span className="text-xs text-amber-700 block">{__('general.platform_commission')}</span>
                                    <span className="text-lg font-bold font-mono text-amber-900">
                                        {formatMoney(order.commission_amount, order.currency)}
                                    </span>
                                </div>
                                <div className="p-3 bg-emerald-50/50 rounded-lg border border-emerald-100">
                                    <span className="text-xs text-emerald-700 block">{__('general.net_seller_payout')}</span>
                                    <span className="text-lg font-bold font-mono text-emerald-900">
                                        {formatMoney(order.seller_earnings, order.currency)}
                                    </span>
                                </div>
                            </div>

                            {order.escrow && (
                                <div className="text-xs space-y-1 text-slate-500 font-mono pt-2 border-t border-slate-100">
                                    {order.escrow.buyer_wallet_transaction_id && (
                                        <div>{__('general.buyer_transaction_id')}: #{order.escrow.buyer_wallet_transaction_id}</div>
                                    )}
                                    {order.escrow.seller_wallet_transaction_id && (
                                        <div>{__('general.seller_transaction_id')}: #{order.escrow.seller_wallet_transaction_id}</div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Submitted Work & Deliverables */}
                    <Card className="bg-white shadow-sm border border-slate-200">
                        <CardContent className="p-6">
                            <h3 className="text-lg font-bold text-slate-900 mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                                <FileText className="w-5 h-5 text-blue-600" />
                                {__('general.submitted_deliverables')}
                            </h3>

                            {(!order.delivery_files || order.delivery_files.length === 0) ? (
                                <div className="p-8 text-center text-slate-500 border border-dashed border-slate-200 rounded-lg bg-slate-50/50">
                                    <FileText className="mx-auto h-8 w-8 mb-2 opacity-40" />
                                    <p className="text-sm">{__('general.no_deliverable_files')}</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {order.delivery_files.map((file) => (
                                        <div key={file.id} className="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                                            <div className="space-y-1">
                                                <div className="font-medium text-slate-900 text-sm flex items-center gap-2">
                                                    <FileText className="w-4 h-4 text-slate-500" />
                                                    {file.file_name || file.file_path}
                                                </div>
                                                {file.note && (
                                                    <p className="text-xs text-slate-600 italic ps-6">"{file.note}"</p>
                                                )}
                                                <span className="text-xs text-slate-400 ps-6 block">
                                                    {new Date(file.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <Button asChild variant="outline" size="sm">
                                                <a href={`/storage/${file.file_path}`} download target="_blank" rel="noopener noreferrer">
                                                    <Download className="w-3.5 h-3.5 me-1" />
                                                    {__('general.download_file') || 'Download'}
                                                </a>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Chat & Communication History Timeline */}
                    <Card className="bg-white shadow-sm border border-slate-200">
                        <CardContent className="p-6">
                            <h3 className="text-lg font-bold text-slate-900 mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                                <MessageCircle className="w-5 h-5 text-indigo-600" />
                                {__('general.order_chat_files')}
                            </h3>

                            {(!order.messages || order.messages.length === 0) ? (
                                <div className="p-8 text-center text-slate-500 border border-dashed border-slate-200 rounded-lg bg-slate-50/50">
                                    <MessageCircle className="mx-auto h-8 w-8 mb-2 opacity-40" />
                                    <p className="text-sm">{__('general.no_messages_yet')}</p>
                                </div>
                            ) : (
                                <div className="space-y-3 max-h-[400px] overflow-y-auto pe-2">
                                    {order.messages.map((msg) => {
                                        const isBuyer = msg.sender_id === order.buyer_id;
                                        return (
                                            <div
                                                key={msg.id}
                                                className={`p-3.5 rounded-xl border text-sm ${
                                                    isBuyer
                                                        ? 'bg-blue-50/60 border-blue-100 text-blue-950 me-6'
                                                        : 'bg-emerald-50/60 border-emerald-100 text-emerald-950 ms-6'
                                                }`}
                                            >
                                                <div className="flex justify-between items-center mb-1 text-xs text-slate-500 font-medium">
                                                    <span className="font-semibold text-slate-900">
                                                        {msg.sender_name || (isBuyer ? order.buyer?.name : order.seller?.name)}
                                                        <Badge variant="outline" className="ms-2 text-[10px] py-0">
                                                            {isBuyer ? __('general.buyer') : __('general.seller')}
                                                        </Badge>
                                                    </span>
                                                    {msg.created_at && <span>{new Date(msg.created_at).toLocaleString()}</span>}
                                                </div>
                                                <p className="whitespace-pre-wrap">{msg.body}</p>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Order Audit Notes */}
                    {order.notes && (
                        <Card className="bg-slate-900 text-slate-100 shadow-sm border border-slate-800">
                            <CardContent className="p-6">
                                <h3 className="text-md font-bold mb-3 flex items-center gap-2 text-slate-200">
                                    <History className="w-4 h-4 text-emerald-400" />
                                    {__('general.order_audit_notes')}
                                </h3>
                                <pre className="text-xs font-mono text-slate-300 whitespace-pre-wrap bg-slate-950/80 p-3 rounded-lg border border-slate-800">
                                    {order.notes}
                                </pre>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Sidebar Admin Resolution Panel */}
                <div className="lg:col-span-1">
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sticky top-24 space-y-4">
                        <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <ShieldAlert className="h-5 w-5 text-slate-900" />
                            {__('general.dispute_resolution_title')}
                        </h3>

                        {isClosed ? (
                            <div className="bg-slate-50 text-slate-600 p-4 rounded-lg text-sm flex items-start gap-2 border border-slate-200">
                                <ShieldCheck className="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
                                <p className="text-xs leading-relaxed">
                                    {__('general.this_order_is_closed_and_no_further_administrative_actions_can_be_taken')}
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-xs text-slate-600 leading-relaxed">
                                    {__('general.if_this_order_is_under_dispute_you_can_step_in_to_forcibly_resolve_the_transaction')}
                                </p>

                                <Button
                                    className="w-full justify-start bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5"
                                    onClick={() => handleAction('release_to_seller')}
                                    disabled={processing}
                                >
                                    <CheckCircle className="me-2 h-4 w-4" />
                                    {__('general.release_funds_to_seller')}
                                </Button>

                                <Button
                                    className="w-full justify-start bg-rose-600 hover:bg-rose-700 text-white font-medium py-2.5"
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

            {/* Resolution Confirmation Modal with Reason */}
            {pendingAction && meta && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-100">
                        <div className="flex items-center gap-3 border-b border-slate-100 pb-3">
                            <div className={`p-2.5 rounded-full ${pendingAction === 'refund_buyer' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}`}>
                                {pendingAction === 'refund_buyer' ? <AlertTriangle className="w-5 h-5" /> : <CheckCircle className="w-5 h-5" />}
                            </div>
                            <h3 className="text-lg font-bold text-slate-900">{meta.title}</h3>
                        </div>

                        <p className="text-sm text-slate-600 leading-relaxed">{meta.description}</p>

                        <div className="space-y-1.5">
                            <label className="text-xs font-semibold text-slate-700 block">
                                {__('general.resolution_reason')}
                            </label>
                            <Textarea
                                value={resolutionReason}
                                onChange={(e) => setResolutionReason(e.target.value)}
                                placeholder={__('general.enter_resolution_reason_placeholder')}
                                className="text-sm min-h-[90px]"
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setPendingAction(null);
                                    setResolutionReason('');
                                }}
                                disabled={processing}
                            >
                                {__('general.cancel')}
                            </Button>
                            <Button
                                className={meta.cls}
                                onClick={confirmAction}
                                disabled={processing}
                            >
                                {processing ? __('general.processing') : meta.confirmLabel}
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}