import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Eye, Trash2, CheckCircle, XCircle, AlertCircle } from 'lucide-react';

export default function Index({ jobs, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleSearch = (e: any) => {
        e.preventDefault();
        router.get(route('admin.freelance.jobs.index'), { search, status }, { preserveState: true });
    };

    const handleDelete = (id: any) => {
        if (confirm('Are you sure you want to delete this job permanently?')) {
            router.delete(route('admin.freelance.jobs.destroy', id));
        }
    };

    const updateStatus = (id: any, newStatus: any) => {
        router.post(route('admin.freelance.jobs.status', id), { status: newStatus }, {
            preserveScroll: true
        });
    };

    return (
        <AdminSidebarLayout title="Freelance Jobs" header="Manage Freelance Jobs">
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2 w-full max-w-2xl">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search jobs by title or client name/email..."
                        className="flex-1"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                    >
                        <option value="all">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <Button type="submit" variant="secondary">Filter</Button>
                    {(search || status !== 'all') && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); setStatus('all'); router.get(route('admin.freelance.jobs.index')); }}>
                            Clear
                        </Button>
                    )}
                </form>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Job Title</th>
                            <th className="p-4 font-medium text-gray-600">Client</th>
                            <th className="p-4 font-medium text-gray-600">Budget</th>
                            <th className="p-4 font-medium text-gray-600">Proposals</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600">Created At</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {jobs.data.map((job: any) => (
                            <tr key={job.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{job.title}</td>
                                <td className="p-4 text-gray-700">
                                    <div>{job.client?.name || 'Unknown'}</div>
                                    <div className="text-xs text-gray-500">{job.client?.email}</div>
                                </td>
                                <td className="p-4 font-medium text-slate-900">{job.budget} {job.currency_code}</td>
                                <td className="p-4">{job.proposals_count || 0}</td>
                                <td className="p-4 capitalize">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                        ${job.status === 'published' ? 'bg-green-100 text-green-800' : 
                                          job.status === 'suspended' ? 'bg-red-100 text-red-800' :
                                          job.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                          'bg-gray-100 text-gray-800'}`}>
                                        {job.status.replace('_', ' ')}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">{new Date(job.created_at).toLocaleDateString()}</td>
                                <td className="p-4 space-x-2 text-right">
                                    <Link href={route('admin.freelance.jobs.show', job.id)} className={buttonVariants({ variant: 'outline', size: 'sm' })}>
                                        <Eye className="h-4 w-4 mr-1" /> View
                                    </Link>
                                    
                                    {job.status === 'published' && (
                                        <Button variant="secondary" size="sm" className="bg-yellow-100 hover:bg-yellow-200 text-yellow-800" onClick={() => updateStatus(job.id, 'suspended')}>
                                            <AlertCircle className="h-4 w-4 mr-1" /> Suspend
                                        </Button>
                                    )}
                                    
                                    {job.status === 'suspended' && (
                                        <Button variant="secondary" size="sm" className="bg-green-100 hover:bg-green-200 text-green-800" onClick={() => updateStatus(job.id, 'published')}>
                                            <CheckCircle className="h-4 w-4 mr-1" /> Restore
                                        </Button>
                                    )}

                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(job.id)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {jobs.data.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-4 text-center text-gray-500">
                                    No jobs found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {jobs.links && jobs.links.length > 3 && (
                <div className="mt-4 flex justify-between items-center">
                    <div className="text-sm text-gray-500">
                        Showing {jobs.from || 0} to {jobs.to || 0} of {jobs.total} results
                    </div>
                    <div className="flex space-x-1">
                        {jobs.links.map((link: any, idx: number) => (
                            <Link 
                                key={idx}
                                href={link.url || '#'}
                                className={`px-3 py-1 rounded text-sm transition ${link.active ? 'bg-slate-900 text-white shadow-sm' : !link.url ? 'cursor-not-allowed opacity-50 text-slate-300 pointer-events-none' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}
