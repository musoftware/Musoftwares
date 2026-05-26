import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeft, Trash2, ShieldAlert, CheckCircle, XCircle } from 'lucide-react';

export default function Show({ contract }) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this contract permanently?')) {
            router.delete(route('admin.freelance.contracts.destroy', contract.id));
        }
    };

    const updateStatus = (newStatus) => {
        if (confirm(`Are you sure you want to change the status to ${newStatus}?`)) {
            router.post(route('admin.freelance.contracts.status', contract.id), { status: newStatus }, {
                preserveScroll: true
            });
        }
    };

    return (
        <AdminSidebarLayout title={`Contract Details`} header={
            <div className="flex items-center space-x-2">
                <Link href={route('admin.freelance.contracts.index')} className="text-gray-500 hover:text-gray-900 mr-2">
                    <ArrowLeft className="h-5 w-5" />
                </Link>
                <span>Contract Details</span>
            </div>
        }>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <div className="flex justify-between items-start mb-6">
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900">
                                    Contract #{contract.id}
                                </h2>
                                <p className="text-gray-500 text-sm mt-1">
                                    Linked to Job:{' '}
                                    {contract.job ? (
                                        <Link href={route('admin.freelance.jobs.show', contract.job.id)} className="text-blue-600 hover:underline">
                                            {contract.job.title}
                                        </Link>
                                    ) : 'Deleted'}
                                </p>
                            </div>
                            <Badge variant={contract.status === 'active' ? 'default' : 'secondary'} className="capitalize text-sm">
                                {contract.status}
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-md mb-6">
                            <div>
                                <span className="text-gray-500 block mb-1">Contract Amount</span>
                                <span className="font-semibold text-blue-600 text-lg">{contract.amount} {contract.currency_code}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Started At</span>
                                <span className="font-semibold text-gray-900">
                                    {contract.started_at ? new Date(contract.started_at).toLocaleString() : 'Not started'}
                                </span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Created At</span>
                                <span className="font-semibold text-gray-900">
                                    {new Date(contract.created_at).toLocaleString()}
                                </span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Completed At</span>
                                <span className="font-semibold text-gray-900">
                                    {contract.completed_at ? new Date(contract.completed_at).toLocaleString() : 'Not completed'}
                                </span>
                            </div>
                        </div>

                        {contract.proposal && (
                            <div className="mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Original Proposal Cover Letter</h3>
                                <div className="bg-gray-50 p-4 rounded-md whitespace-pre-wrap text-sm text-gray-700">
                                    {contract.proposal.cover_letter || 'No cover letter provided.'}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Participants</h3>
                        
                        <div className="mb-4">
                            <span className="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2 block">Client</span>
                            {contract.client ? (
                                <div className="flex items-center space-x-3">
                                    <div className="bg-blue-100 text-blue-700 h-10 w-10 flex items-center justify-center rounded-full font-bold">
                                        {contract.client.name.charAt(0)}
                                    </div>
                                    <div>
                                        <div className="font-semibold text-gray-900">{contract.client.name}</div>
                                        <div className="text-xs text-gray-500">{contract.client.email}</div>
                                    </div>
                                </div>
                            ) : <span className="text-gray-500 italic">User deleted</span>}
                        </div>
                        
                        <div>
                            <span className="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2 block">Freelancer</span>
                            {contract.freelancer ? (
                                <div className="flex items-center space-x-3">
                                    <div className="bg-green-100 text-green-700 h-10 w-10 flex items-center justify-center rounded-full font-bold">
                                        {contract.freelancer.name.charAt(0)}
                                    </div>
                                    <div>
                                        <div className="font-semibold text-gray-900">{contract.freelancer.name}</div>
                                        <div className="text-xs text-gray-500">{contract.freelancer.email}</div>
                                    </div>
                                </div>
                            ) : <span className="text-gray-500 italic">User deleted</span>}
                        </div>
                    </div>

                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <ShieldAlert className="h-5 w-5 mr-2 text-red-500" /> 
                            Admin Actions
                        </h3>
                        
                        <div className="space-y-3">
                            {contract.status !== 'cancelled' && (
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => updateStatus('cancelled')}
                                >
                                    <XCircle className="h-4 w-4 mr-2" />
                                    Cancel Contract
                                </Button>
                            )}
                            
                            {contract.status !== 'completed' && (
                                <Button 
                                    className="w-full justify-start" 
                                    variant="outline"
                                    onClick={() => updateStatus('completed')}
                                >
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Mark as Completed
                                </Button>
                            )}
                            
                            {contract.status !== 'disputed' && (
                                <Button 
                                    className="w-full bg-red-100 text-red-800 hover:bg-red-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => updateStatus('disputed')}
                                >
                                    Mark as Disputed
                                </Button>
                            )}

                            <Button 
                                className="w-full justify-start" 
                                variant="destructive"
                                onClick={handleDelete}
                            >
                                <Trash2 className="h-4 w-4 mr-2" />
                                Delete Permanently
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
