import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeft, Trash2, ShieldAlert, CheckCircle, XCircle, AlertTriangle, Users, ArrowRightLeft } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function Show({ contract }: any) {
    const [confirmAction, setConfirmAction] = useState<{
        action: () => void,
        title: string,
        description: string,
        confirmLabel: string,
        variant?: "danger" | "default"
    } | null>(null);

    const executeAction = () => {
        if (confirmAction) {
            confirmAction.action();
            setConfirmAction(null);
        }
    };

    const handleDelete = () => {
        setConfirmAction({
            action: () => router.delete(route('admin.freelance.contracts.destroy', contract.id)),
            title: __('freelance.confirm_delete_contract', undefined, 'Delete Contract?'),
            description: __('freelance.confirm_delete_contract_msg', undefined, 'Are you sure you want to delete this contract permanently?'),
            confirmLabel: __('freelance.delete', undefined, 'Delete'),
            variant: 'danger'
        });
    };

    const updateStatus = (newStatus: string) => {
        setConfirmAction({
            action: () => router.post(route('admin.freelance.contracts.status', contract.id), { status: newStatus }, { preserveScroll: true }),
            title: __('freelance.confirm_status_change', { status: newStatus }, `Change status to ${newStatus}?`),
            description: __('freelance.confirm_status_change_msg', { status: newStatus }, `Are you sure you want to change the contract status to ${newStatus}?`),
            confirmLabel: __('general.confirm', undefined, 'Confirm'),
            variant: 'default'
        });
    };

    const resolveDispute = (resolution: string) => {
        setConfirmAction({
            action: () => router.post(route('admin.freelance.contracts.resolve-dispute', contract.id), { resolution }, { preserveScroll: true }),
            title: __('freelance.confirm_resolve_dispute', undefined, 'Resolve Dispute?'),
            description: __('freelance.confirm_resolve_dispute_msg', { resolution }, `Are you sure you want to resolve the dispute by: ${resolution}?`),
            confirmLabel: __('general.confirm', undefined, 'Confirm'),
            variant: 'default'
        });
    };

    return (
        <AdminSidebarLayout title={`${__('freelance.contract_details', undefined, 'Contract Details')}`} header={
            <div className="flex items-center space-x-2">
                <Link href={route('admin.freelance.contracts.index')} className="text-gray-500 hover:text-gray-900 me-2">
                    <ArrowLeft className="h-5 w-5" />
                </Link>
                <span>{__('freelance.contract_details', undefined, 'Contract Details')}</span>
            </div>
        }>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <div className="flex justify-between items-start mb-6">
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900">
                                    {__('freelance.contract_id', undefined, 'Contract #')}{contract.id}
                                </h2>
                                <p className="text-gray-500 text-sm mt-1">
                                    {__('freelance.linked_to_job', undefined, 'Linked to Job:')}{' '}
                                    {contract.job ? (
                                        <Link href={route('admin.freelance.jobs.show', contract.job.id)} className="text-slate-900 hover:underline">
                                            {contract.job.title}
                                        </Link>
                                    ) : __('freelance.job_deleted', undefined, 'Deleted')}
                                </p>
                            </div>
                            <Badge variant={contract.status === 'active' ? 'default' : 'secondary'} className={`capitalize text-sm ${contract.status === 'disputed' ? 'bg-red-100 text-red-800' : ''}`}>
                                {contract.status}
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-md mb-6">
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.contract_amount', undefined, 'Contract Amount')}</span>
                                <span className="font-semibold text-slate-900 text-lg">{formatCurrency(contract.amount, contract.currency || contract.job?.currency)}</span>
                                <div className="text-xs text-gray-500 mt-1">({contract.contract_points} pts)</div>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.started_at', undefined, 'Started At')}</span>
                                <span className="font-semibold text-gray-900">
                                    {contract.started_at ? new Date(contract.started_at).toLocaleString() : __('freelance.not_started', undefined, 'Not started')}
                                </span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.created_at', undefined, 'Created At')}</span>
                                <span className="font-semibold text-gray-900">
                                    {new Date(contract.created_at).toLocaleString()}
                                </span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.completed_at', undefined, 'Completed At')}</span>
                                <span className="font-semibold text-gray-900">
                                    {contract.completed_at ? new Date(contract.completed_at).toLocaleString() : __('freelance.not_completed', undefined, 'Not completed')}
                                </span>
                            </div>
                        </div>

                        {contract.proposal && (
                            <div className="mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-2">{__('freelance.original_proposal', undefined, 'Original Proposal Cover Letter')}</h3>
                                <div className="bg-gray-50 p-4 rounded-md whitespace-pre-wrap text-sm text-gray-700">
                                    {contract.proposal.cover_letter || __('freelance.no_cover_letter', undefined, 'No cover letter provided.')}
                                </div>
                            </div>
                        )}
                    </div>
                    
                    {contract.status === 'disputed' && (
                        <div className="bg-red-50 border border-red-200 shadow-sm rounded-lg p-6">
                            <h3 className="text-lg font-bold text-red-900 mb-4 flex items-center">
                                <AlertTriangle className="h-5 w-5 me-2" />
                                {__('freelance.dispute_resolution', undefined, 'Dispute Resolution')}
                            </h3>
                            <p className="text-red-700 text-sm mb-4">
                                {__('freelance.dispute_description', undefined, 'This contract is disputed. As an admin, you must decide how to distribute the locked points.')}
                            </p>
                            
                            <div className="grid grid-cols-1 gap-3">
                                <Button 
                                    className="w-full justify-start bg-white hover:bg-gray-50 text-gray-900 border border-gray-300" 
                                    variant="outline"
                                    onClick={() => resolveDispute('refund_client')}
                                >
                                    <ArrowLeft className="h-4 w-4 me-2 text-slate-900" />
                                    {__('freelance.refund_100_client', undefined, 'Refund 100% to Client')}
                                </Button>
                                
                                <Button 
                                    className="w-full justify-start bg-white hover:bg-gray-50 text-gray-900 border border-gray-300" 
                                    variant="outline"
                                    onClick={() => resolveDispute('split')}
                                >
                                    <ArrowRightLeft className="h-4 w-4 me-2 text-slate-900" />
                                    {__('freelance.split_50_50', undefined, 'Split 50/50 Between Both')}
                                </Button>
                                
                                <Button 
                                    className="w-full justify-start bg-white hover:bg-gray-50 text-gray-900 border border-gray-300" 
                                    variant="outline"
                                    onClick={() => resolveDispute('pay_freelancer')}
                                >
                                    <CheckCircle className="h-4 w-4 me-2 text-green-600" />
                                    {__('freelance.pay_100_freelancer', undefined, 'Pay 100% to Freelancer')}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                <div className="space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">{__('freelance.participants', undefined, 'Participants')}</h3>
                        
                        <div className="mb-4">
                            <span className="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2 block">{__('freelance.client')}</span>
                            {contract.client ? (
                                <div className="flex items-center space-x-3">
                                    <div className="bg-blue-100 text-slate-900 h-10 w-10 flex items-center justify-center rounded-full font-bold">
                                        {contract.client.name.charAt(0)}
                                    </div>
                                    <div>
                                        <div className="font-semibold text-gray-900">
                                            <Link href={route('admin.users.show', contract.client.id)} className="hover:underline">
                                                {contract.client.name}
                                            </Link>
                                        </div>
                                        <div className="text-xs text-gray-500">{contract.client.email}</div>
                                    </div>
                                </div>
                            ) : <span className="text-gray-500 italic">{__('freelance.user_deleted', undefined, 'User deleted')}</span>}
                        </div>
                        
                        <div>
                            <span className="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2 block">{__('freelance.freelancer')}</span>
                            {contract.freelancer ? (
                                <div className="flex items-center space-x-3">
                                    <div className="bg-green-100 text-green-700 h-10 w-10 flex items-center justify-center rounded-full font-bold">
                                        {contract.freelancer.name.charAt(0)}
                                    </div>
                                    <div>
                                        <div className="font-semibold text-gray-900">
                                            <Link href={route('admin.users.show', contract.freelancer.id)} className="hover:underline">
                                                {contract.freelancer.name}
                                            </Link>
                                        </div>
                                        <div className="text-xs text-gray-500">{contract.freelancer.email}</div>
                                    </div>
                                </div>
                            ) : <span className="text-gray-500 italic">{__('freelance.user_deleted', undefined, 'User deleted')}</span>}
                        </div>
                    </div>

                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <ShieldAlert className="h-5 w-5 me-2 text-red-500" /> 
                            {__('freelance.admin_actions')}
                        </h3>
                        
                        <div className="space-y-3">
                            {contract.status !== 'cancelled' && (
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => updateStatus('cancelled')}
                                >
                                    <XCircle className="h-4 w-4 me-2" />
                                    {__('freelance.force_cancel_contract', undefined, 'Force Cancel Contract')}
                                </Button>
                            )}
                            
                            {contract.status !== 'completed' && (
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => updateStatus('completed')}
                                >
                                    <CheckCircle className="h-4 w-4 me-2" />
                                    {__('freelance.force_complete_contract', undefined, 'Force Complete Contract')}
                                </Button>
                            )}
                            
                            {contract.status !== 'disputed' && (
                                <Button 
                                    className="w-full bg-red-100 text-red-800 hover:bg-red-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => updateStatus('disputed')}
                                >
                                    {__('freelance.mark_disputed', undefined, 'Mark as Disputed')}
                                </Button>
                            )}

                            <Button 
                                className="w-full justify-start" 
                                variant="destructive"
                                onClick={handleDelete}
                            >
                                <Trash2 className="h-4 w-4 me-2" />
                                {__('freelance.delete_permanent', undefined, 'Delete Permanently')}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmModal 
                isOpen={!!confirmAction} 
                onCancel={() => setConfirmAction(null)}
                onConfirm={executeAction}
                title={confirmAction?.title || ''}
                description={confirmAction?.description || ''}
                confirmLabel={confirmAction?.confirmLabel || ''}
                cancelLabel={__('general.cancel', undefined, 'Cancel')}
                variant={confirmAction?.variant || 'default'}
            />
        </AdminSidebarLayout>
    );
}
