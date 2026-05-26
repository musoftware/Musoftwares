import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeft, Trash2, ShieldAlert } from 'lucide-react';

export default function Show({ job }) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this job permanently?')) {
            router.delete(route('admin.freelance.jobs.destroy', job.id));
        }
    };

    const updateStatus = (newStatus) => {
        router.post(route('admin.freelance.jobs.status', job.id), { status: newStatus }, {
            preserveScroll: true
        });
    };

    return (
        <AdminSidebarLayout title={`Job: ${job.title}`} header={
            <div className="flex items-center space-x-2">
                <Link href={route('admin.freelance.jobs.index')} className="text-gray-500 hover:text-gray-900 mr-2">
                    <ArrowLeft className="h-5 w-5" />
                </Link>
                <span>Job Details</span>
            </div>
        }>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <div className="flex justify-between items-start mb-4">
                            <h2 className="text-2xl font-bold text-gray-900">{job.title}</h2>
                            <Badge variant={job.status === 'published' ? 'default' : 'secondary'} className="capitalize">
                                {job.status.replace('_', ' ')}
                            </Badge>
                        </div>
                        
                        <div className="prose max-w-none text-gray-700 whitespace-pre-wrap mb-6">
                            {job.description}
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-md">
                            <div>
                                <span className="text-gray-500 block mb-1">Budget</span>
                                <span className="font-semibold text-gray-900">{job.budget} {job.currency_code}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Duration</span>
                                <span className="font-semibold text-gray-900">{job.duration || 'Not specified'}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Job Type</span>
                                <span className="font-semibold text-gray-900 capitalize">{job.type || 'Standard'}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Created On</span>
                                <span className="font-semibold text-gray-900">{new Date(job.created_at).toLocaleString()}</span>
                            </div>
                        </div>

                        {job.skills && job.skills.length > 0 && (
                            <div className="mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Required Skills</h3>
                                <div className="flex flex-wrap gap-2">
                                    {job.skills.map(skill => (
                                        <Badge key={skill.id} variant="outline" className="bg-blue-50 text-blue-700">
                                            {skill.name}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Proposals ({job.proposals?.length || 0})</h3>
                        {job.proposals && job.proposals.length > 0 ? (
                            <div className="space-y-4">
                                {job.proposals.map(proposal => (
                                    <div key={proposal.id} className="border rounded-md p-4 bg-gray-50 flex justify-between items-center">
                                        <div>
                                            <div className="font-medium text-gray-900">{proposal.freelancer?.name}</div>
                                            <div className="text-xs text-gray-500">{proposal.freelancer?.email}</div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-semibold text-green-600">{proposal.bid_amount} {proposal.currency_code}</div>
                                            <Badge variant="outline" className="capitalize text-xs">{proposal.status}</Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-gray-500 text-sm">No proposals submitted yet.</div>
                        )}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Client Information</h3>
                        {job.client ? (
                            <div className="space-y-3 text-sm">
                                <div>
                                    <span className="text-gray-500 block">Name</span>
                                    <Link href={route('admin.users.show', job.client.id)} className="font-semibold text-blue-600 hover:underline">
                                        {job.client.name}
                                    </Link>
                                </div>
                                <div>
                                    <span className="text-gray-500 block">Email</span>
                                    <span className="text-gray-900">{job.client.email}</span>
                                </div>
                            </div>
                        ) : (
                            <span className="text-gray-500 italic">Client removed</span>
                        )}
                    </div>

                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <ShieldAlert className="h-5 w-5 mr-2 text-red-500" /> 
                            Admin Actions
                        </h3>
                        
                        <div className="space-y-3">
                            {job.status !== 'suspended' && (
                                <Button 
                                    className="w-full bg-yellow-100 text-yellow-800 hover:bg-yellow-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => updateStatus('suspended')}
                                >
                                    Suspend Job (TOS Violation)
                                </Button>
                            )}
                            
                            {job.status === 'suspended' && (
                                <Button 
                                    className="w-full bg-green-100 text-green-800 hover:bg-green-200 justify-start" 
                                    variant="secondary"
                                    onClick={() => updateStatus('published')}
                                >
                                    Restore Job (Remove Suspension)
                                </Button>
                            )}

                            <Button 
                                className="w-full justify-start" 
                                variant="destructive"
                                onClick={handleDelete}
                            >
                                <Trash2 className="h-4 w-4 mr-2" />
                                Delete Job Permanently
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
