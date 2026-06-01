import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Trash2, ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Show({ proposal }: { proposal: any }) {

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this proposal permanently?')) {
            router.delete(route('admin.freelance.proposals.destroy', proposal.id));
        }
    };

    return (
        <AdminSidebarLayout title={__('freelance.proposal_details', undefined, 'Proposal Details')} header={__('freelance.proposal_details', undefined, 'Proposal Details')}>
            <div className="mb-6">
                <Button variant="ghost" onClick={() => window.history.back()} className="flex items-center gap-2">
                    <ArrowLeft className="h-4 w-4" /> {__('freelance.back_to_proposals', undefined, 'Back to Proposals')}
                </Button>
            </div>

            <div className="bg-white rounded-lg shadow p-6 max-w-4xl">
                <div className="flex justify-between items-start border-b pb-4 mb-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 mb-1">
                            {proposal.job ? (
                                <Link href={route('admin.freelance.jobs.show', proposal.job.id)} className="text-blue-600 hover:underline">
                                    {__('freelance.job', undefined, 'Job:')} {proposal.job.title}
                                </Link>
                            ) : (
                                <span className="text-gray-500 italic">{__('freelance.job_deleted', undefined, 'Job Deleted')}</span>
                            )}
                        </h2>
                        <div className="text-sm text-gray-500">{__('freelance.submitted_on', undefined, 'Submitted on:')} {new Date(proposal.created_at).toLocaleDateString()}</div>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold leading-5 capitalize
                            ${proposal.status === 'accepted' ? 'bg-green-100 text-green-800' : 
                              proposal.status === 'rejected' ? 'bg-red-100 text-red-800' :
                              proposal.status === 'withdrawn' ? 'bg-gray-100 text-gray-800' :
                              'bg-yellow-100 text-yellow-800'}`}>
                            {proposal.status}
                        </span>
                        <Button variant="destructive" size="sm" onClick={handleDelete} className="flex items-center gap-2">
                            <Trash2 className="h-4 w-4" /> {__('freelance.delete', undefined, 'Delete')}
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div className="bg-gray-50 p-4 rounded-md">
                        <h3 className="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">{__('freelance.freelancer')}</h3>
                        {proposal.freelancer ? (
                            <>
                                <p className="font-medium text-gray-900">{proposal.freelancer.name}</p>
                                <p className="text-gray-600 text-sm">{proposal.freelancer.email}</p>
                            </>
                        ) : (
                            <span className="text-gray-500 italic">{__('freelance.user_deleted', undefined, 'User Deleted')}</span>
                        )}
                    </div>
                    
                    <div className="bg-gray-50 p-4 rounded-md">
                        <h3 className="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">{__('freelance.bid_details', undefined, 'Bid Details')}</h3>
                        <p className="text-2xl font-bold text-gray-900 mb-1">{proposal.formatted_bid_amount}</p>
                        <p className="text-sm text-gray-600">{__('freelance.estimated_duration', undefined, 'Estimated Duration:')} {proposal.estimated_duration || __('freelance.not_specified', undefined, 'Not specified')}</p>
                    </div>
                </div>

                <div>
                    <h3 className="text-lg font-bold text-gray-900 mb-3">{__('freelance.cover_letter', undefined, 'Cover Letter')}</h3>
                    <div className="bg-gray-50 p-5 rounded-lg whitespace-pre-wrap text-gray-700 leading-relaxed border border-gray-100 min-h-[150px]">
                        {proposal.cover_letter || <span className="text-gray-400 italic">{__('freelance.no_cover_letter', undefined, 'No cover letter provided.')}</span>}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
