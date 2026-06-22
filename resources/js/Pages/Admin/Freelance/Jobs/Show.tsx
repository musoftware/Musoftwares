import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeft, Trash2, ShieldAlert, Edit, RefreshCw, FileText } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function Show({ job }: any) {
    const [deleteConfirm, setDeleteConfirm] = React.useState(false);
    const [refundConfirm, setRefundConfirm] = React.useState(false);
    const [deleteProposalConfirm, setDeleteProposalConfirm] = React.useState<any>(null);
    const handleDelete = () => {
        router.delete(route('admin.freelance.jobs.destroy', job.id));
        setDeleteConfirm(false);
    };

    const handleDeleteProposal = () => {
        if (deleteProposalConfirm) {
            router.delete(route('admin.freelance.proposals.destroy', deleteProposalConfirm), {
                preserveScroll: true
            });
            setDeleteProposalConfirm(null);
        }
    };

    const updateStatus = (newStatus: any) => {
        router.post(route('admin.freelance.jobs.status', job.id), { status: newStatus }, {
            preserveScroll: true
        });
    };

    const forceRefund = () => {
        router.post(route('admin.freelance.jobs.force-refund', job.id), {}, {
            preserveScroll: true
        });
        setRefundConfirm(false);
    };

    return (
        <AdminSidebarLayout title={`${__('freelance.job_title')}: ${job.title}`} header={
            <div className="flex items-center space-x-2">
                <Link href={route('admin.freelance.jobs.index')} className="text-gray-500 hover:text-gray-900 me-2">
                    <ArrowLeft className="h-5 w-5" />
                </Link>
                <span>{__('freelance.job_details')}</span>
            </div>
        }>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <div className="flex justify-between items-start mb-4">
                            <h2 className="text-2xl font-bold text-gray-900">{job.title}</h2>
                            <Badge variant={job.status === 'published' || job.status === 'open' ? 'default' : 'secondary'} className="capitalize">
                                {job.status.replace('_', ' ')}
                            </Badge>
                        </div>
                        
                        <div className="prose max-w-none text-gray-700 whitespace-pre-wrap mb-6">
                            {job.description}
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-md">
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.budget')}</span>
                                <span className="font-semibold text-gray-900">{formatCurrency(job.budget, job.currency)}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.duration')}</span>
                                <span className="font-semibold text-gray-900">{job.duration || __('freelance.not_specified')}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.job_type')}</span>
                                <span className="font-semibold text-gray-900 capitalize">{job.type || 'Standard'}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">{__('freelance.created_on')}</span>
                                <span className="font-semibold text-gray-900">{new Date(job.created_at).toLocaleString()}</span>
                            </div>
                        </div>

                        {job.skills && job.skills.length > 0 && (
                            <div className="mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-2">{__('freelance.required_skills')}</h3>
                                <div className="flex flex-wrap gap-2">
                                    {job.skills.map((skill: any) => (
                                        <Badge key={skill.id} variant="outline" className="bg-slate-50 text-slate-900">
                                            {skill.name}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">{__('freelance.proposals')} ({job.proposals?.length || 0})</h3>
                        {job.proposals && job.proposals.length > 0 ? (
                            <div className="space-y-4">
                                {job.proposals.map((proposal: any) => (
                                    <div key={proposal.id} className="border rounded-md p-4 bg-gray-50 flex justify-between items-center group">
                                        <div>
                                            <div className="font-medium text-gray-900">{proposal.freelancer?.name}</div>
                                            <div className="text-xs text-gray-500">{proposal.freelancer?.email}</div>
                                        </div>
                                        <div className="text-end flex items-center space-x-4">
                                            <div>
                                                <div className="font-semibold text-green-600">{formatCurrency(proposal.bid_amount, proposal.currency || job.currency)}</div>
                                                <Badge variant="outline" className="capitalize text-xs">{proposal.status}</Badge>
                                            </div>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                className="text-red-500 hover:text-red-700 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity"
                                                onClick={() => setDeleteProposalConfirm(proposal.id)}
                                                title={__('freelance.delete_proposal')}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-gray-500 text-sm">{__('freelance.no_proposals')}</div>
                        )}
                    </div>

                    {/* Job Contracts Monitoring */}
                    <div className="bg-white shadow rounded-lg p-6 mt-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <FileText className="h-5 w-5 me-2 text-slate-500" />
                            {__('freelance.job_contracts', undefined, 'Job Contracts')}
                        </h3>
                        {job.contracts && job.contracts.length > 0 ? (
                            <div className="space-y-4">
                                {job.contracts.map((contract: any) => (
                                    <div key={contract.id} className="border rounded-md p-4 bg-gray-50 flex justify-between items-center">
                                        <div>
                                            <div className="font-medium text-gray-900">{__('freelance.contract')} #{contract.id}</div>
                                            <div className="text-xs text-gray-500 mt-1">
                                                {__('freelance.freelancer')}: <span className="font-semibold text-slate-700">{contract.freelancer?.name}</span>
                                            </div>
                                        </div>
                                        <div className="text-end flex items-center space-x-4">
                                            <div>
                                                <div className="font-semibold text-green-600">{formatCurrency(contract.amount, contract.currency || job.currency)}</div>
                                                <Badge variant="outline" className="capitalize text-xs">{__('freelance.' + contract.status) || contract.status}</Badge>
                                            </div>
                                            <Link href={route('admin.freelance.contracts.show', contract.id)}>
                                                <Button variant="outline" size="sm">
                                                    {__('freelance.view_contract', undefined, 'View Contract')}
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-gray-500 text-sm">{__('freelance.no_contracts_yet', undefined, 'No contracts associated with this job yet.')}</div>
                        )}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">{__('freelance.client_information')}</h3>
                        {job.client ? (
                            <div className="space-y-3 text-sm">
                                <div>
                                    <span className="text-gray-500 block">{__('freelance.name')}</span>
                                    <Link href={route('admin.users.show', job.client.id)} className="font-semibold text-slate-900 hover:underline">
                                        {job.client.name}
                                    </Link>
                                </div>
                                <div>
                                    <span className="text-gray-500 block">{__('freelance.email')}</span>
                                    <span className="text-gray-900">{job.client.email}</span>
                                </div>
                            </div>
                        ) : (
                            <span className="text-gray-500 italic">{__('freelance.client_removed')}</span>
                        )}
                    </div>

                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <ShieldAlert className="h-5 w-5 me-2 text-red-500" /> 
                            {__('freelance.admin_actions')}
                        </h3>
                        
                        <div className="space-y-3">
                            <Link 
                                href={route('admin.freelance.jobs.edit', job.id)} 
                                className={buttonVariants({ variant: 'outline', className: 'w-full justify-start' })}
                            >
                                <Edit className="h-4 w-4 me-2 text-slate-900" />
                                {__('freelance.edit')}
                            </Link>

                            {job.status !== 'suspended' && (
                                <Button 
                                    className="w-full bg-yellow-100 text-yellow-800 hover:bg-yellow-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => updateStatus('suspended')}
                                >
                                    {__('freelance.suspend_job')}
                                </Button>
                            )}
                            
                            {job.status === 'suspended' && (
                                <Button 
                                    className="w-full bg-green-100 text-green-800 hover:bg-green-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => updateStatus('published')}
                                >
                                    {__('freelance.restore_job')}
                                </Button>
                            )}

                            {(job.status === 'published' || job.status === 'open' || job.status === 'suspended') && (
                                <Button 
                                    className="w-full bg-yellow-100 text-yellow-800 hover:bg-yellow-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => setRefundConfirm(true)}
                                >
                                    <RefreshCw className="h-4 w-4 me-2" />
                                    {__('freelance.force_refund')}
                                </Button>
                            )}

                            <Button 
                                className="w-full justify-start" 
                                variant="destructive"
                                onClick={() => setDeleteConfirm(true)}
                            >
                                <Trash2 className="h-4 w-4 me-2" />
                                {__('freelance.delete_job_permanent')}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete Proposal Modal */}
            <ConfirmModal 
                isOpen={!!deleteProposalConfirm} 
                onCancel={() => setDeleteProposalConfirm(null)}
                onConfirm={handleDeleteProposal}
                title={__('freelance.confirm_delete_proposal')}
                description={__('freelance.confirm_delete_proposal_msg')}
                confirmLabel={__('freelance.delete')}
                cancelLabel={__('freelance.cancel')}
                variant="danger"
            />

            {/* Delete Job Modal */}
            <ConfirmModal 
                isOpen={deleteConfirm} 
                onCancel={() => setDeleteConfirm(false)}
                onConfirm={handleDelete}
                title={__('freelance.confirm_delete_job')}
                description={__('freelance.confirm_delete_job_msg')}
                confirmLabel={__('freelance.delete')}
                cancelLabel={__('freelance.cancel')}
                variant="danger"
            />

            {/* Refund Job Modal */}
            <ConfirmModal 
                isOpen={refundConfirm} 
                onCancel={() => setRefundConfirm(false)}
                onConfirm={forceRefund}
                title={__('freelance.confirm_force_refund')}
                description={__('freelance.confirm_force_refund_msg')}
                confirmLabel={__('freelance.force_refund')}
                cancelLabel={__('freelance.cancel')}
                variant="danger"
            />
        </AdminSidebarLayout>
    );
}
