import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/use-toast';
import { ArrowLeft, User, Wallet, Calendar, FileText, CheckCircle, XCircle, Clock } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface WithdrawRequest {
    id: number;
    status: string;
    amount: number;
    notes: string | null;
    created_at: string;
    updated_at: string;
    user: { id: number; name: string; email: string } | null;
    method: { id: number; name: string } | null;
}

interface Props {
    withdrawRequest: WithdrawRequest;
}

const statusStyles: Record<string, { cls: string; label: string }> = {
    pending:   { cls: 'bg-yellow-100 text-yellow-800 border-yellow-200', label: 'Pending' },
    reviewing: { cls: 'bg-blue-100 text-blue-800 border-blue-200',       label: 'Reviewing' },
    approved:  { cls: 'bg-green-100 text-green-800 border-green-200',    label: 'Approved' },
    declined:  { cls: 'bg-red-100 text-red-800 border-red-200',          label: 'Declined' },
};

export default function Show({ withdrawRequest }: Props) {
    const { settings } = usePage<any>().props;
    const base_currency = settings?.base_currency;
    const { toast } = useToast();
    const [loading, setLoading] = useState(false);

    const updateStatus = (status: string) => {
        if (!confirm(`Change status to "${status}"?`)) return;
        setLoading(true);
        router.patch(
            `/admin/withdraw-requests/${withdrawRequest.id}`,
            { status },
            {
                onSuccess: () => toast({ title: `Status updated to "${status}".` }),
                onError: () => toast({ title: 'Failed to update status.', variant: 'destructive' }),
                onFinish: () => setLoading(false),
            }
        );
    };

    const badge = statusStyles[withdrawRequest.status] ?? { cls: 'bg-slate-100 text-slate-700', label: withdrawRequest.status };

    return (
        <AdminSidebarLayout title={__('general.withdraw_request')} header="Withdraw Request Detail">
            <Head title={`Withdraw Request #${withdrawRequest.id}`} />

            {/* Back link */}
            <div className="mb-6">
                <Link
                    href="/admin/withdraw-requests"
                    className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" />{__('general.back_to_withdraw_requests')}</Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main detail card */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <div className="flex items-center justify-between mb-5">
                            <h2 className="text-base font-semibold text-slate-800">
                                Request #{withdrawRequest.id}
                            </h2>
                            <span
                                className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold capitalize ${badge.cls}`}
                            >
                                {badge.label}
                            </span>
                        </div>

                        <dl className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <dt className="text-slate-500 flex items-center gap-1.5 mb-1">
                                    <Wallet className="h-3.5 w-3.5" /> {__('general.amount')}</dt>
                                <dd className="text-2xl font-bold text-slate-900">
                                    {formatCurrency(withdrawRequest.amount, base_currency)}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-slate-500 flex items-center gap-1.5 mb-1">
                                    <FileText className="h-3.5 w-3.5" />{__('general.payment_method')}</dt>
                                <dd className="font-medium text-slate-800">
                                    {withdrawRequest.method?.name ?? '—'}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-slate-500 flex items-center gap-1.5 mb-1">
                                    <Calendar className="h-3.5 w-3.5" />{__('general.requested_at')}</dt>
                                <dd className="text-slate-700">
                                    {new Date(withdrawRequest.created_at).toLocaleString()}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-slate-500 flex items-center gap-1.5 mb-1">
                                    <Clock className="h-3.5 w-3.5" />{__('general.last_updated')}</dt>
                                <dd className="text-slate-700">
                                    {new Date(withdrawRequest.updated_at).toLocaleString()}
                                </dd>
                            </div>

                            {withdrawRequest.notes && (
                                <div className="col-span-2">
                                    <dt className="text-slate-500 mb-1">{__('general.notes')}</dt>
                                    <dd className="text-slate-700 whitespace-pre-wrap bg-slate-50 rounded-lg p-3 border border-slate-200">
                                        {withdrawRequest.notes}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>
                </div>

                {/* Sidebar: user + actions */}
                <div className="space-y-4">
                    {/* User card */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                            {__('general.user')}</h3>
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                <User className="h-4 w-4 text-slate-900" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-slate-800">
                                    {withdrawRequest.user?.name ?? '—'}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {withdrawRequest.user?.email ?? ''}
                                </p>
                            </div>
                        </div>
                        {withdrawRequest.user && (
                            <Link
                                href={`/admin/users/${withdrawRequest.user.id}`}
                                className="mt-3 block text-center text-xs text-slate-900 hover:text-indigo-800 font-medium transition-colors"
                            >
                                View User Profile →
                            </Link>
                        )}
                    </div>

                    {/* Actions card */}
                    {!['approved', 'declined'].includes(withdrawRequest.status) && (
                        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                            <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                {__('general.actions')}</h3>
                            <div className="space-y-2">
                                <Button
                                    className="w-full justify-start gap-2 bg-green-600 hover:bg-green-700 text-white"
                                    disabled={loading || withdrawRequest.status === 'approved'}
                                    onClick={() => updateStatus('approved')}
                                >
                                    <CheckCircle className="h-4 w-4" /> {__('general.approve')}</Button>
                                <Button
                                    variant="outline"
                                    className="w-full justify-start gap-2 border-red-200 text-red-600 hover:bg-red-50"
                                    disabled={loading || withdrawRequest.status === 'declined'}
                                    onClick={() => updateStatus('declined')}
                                >
                                    <XCircle className="h-4 w-4" /> {__('general.decline')}</Button>
                                {withdrawRequest.status === 'pending' && (
                                    <Button
                                        variant="outline"
                                        className="w-full justify-start gap-2"
                                        disabled={loading}
                                        onClick={() => updateStatus('reviewing')}
                                    >
                                        <Clock className="h-4 w-4" />{__('general.mark_as_reviewing')}</Button>
                                )}
                            </div>
                        </div>
                    )}

                    {['approved', 'declined'].includes(withdrawRequest.status) && (
                        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                            <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                {__('general.actions')}</h3>
                            <Button
                                variant="outline"
                                className="w-full justify-start gap-2"
                                disabled={loading}
                                onClick={() => updateStatus('reviewing')}
                            >
                                <Clock className="h-4 w-4" />{__('general.reopen_as_reviewing')}</Button>
                        </div>
                    )}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}

