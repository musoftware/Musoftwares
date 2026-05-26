import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Trash2 } from 'lucide-react';

export default function Index({ proposals, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.freelance.proposals.index'), { search, status }, { preserveState: true });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this proposal permanently?')) {
            router.delete(route('admin.freelance.proposals.destroy', id));
        }
    };

    return (
        <AdminSidebarLayout title="Freelance Proposals" header="Manage Proposals">
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2 w-full max-w-2xl">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by job title or freelancer name/email..."
                        className="flex-1"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                    <Button type="submit" variant="secondary">Filter</Button>
                    {(search || status !== 'all') && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); setStatus('all'); router.get(route('admin.freelance.proposals.index')); }}>
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
                            <th className="p-4 font-medium text-gray-600">Freelancer</th>
                            <th className="p-4 font-medium text-gray-600">Bid Amount</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600">Submitted At</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {proposals.data.map((proposal) => (
                            <tr key={proposal.id} className="border-b hover:bg-gray-50">
                                <td className="p-4">
                                    {proposal.job ? (
                                        <Link href={route('admin.freelance.jobs.show', proposal.job.id)} className="font-medium text-blue-600 hover:underline">
                                            {proposal.job.title}
                                        </Link>
                                    ) : (
                                        <span className="text-gray-500 italic">Job Deleted</span>
                                    )}
                                </td>
                                <td className="p-4 text-gray-700">
                                    <div>{proposal.freelancer?.name || 'Unknown'}</div>
                                    <div className="text-xs text-gray-500">{proposal.freelancer?.email}</div>
                                </td>
                                <td className="p-4 font-medium text-gray-900">{proposal.bid_amount} {proposal.currency_code}</td>
                                <td className="p-4 capitalize">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                        ${proposal.status === 'accepted' ? 'bg-green-100 text-green-800' : 
                                          proposal.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                          proposal.status === 'withdrawn' ? 'bg-gray-100 text-gray-800' :
                                          'bg-yellow-100 text-yellow-800'}`}>
                                        {proposal.status}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">{new Date(proposal.created_at).toLocaleDateString()}</td>
                                <td className="p-4 text-right">
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(proposal.id)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {proposals.data.length === 0 && (
                            <tr>
                                <td colSpan="6" className="p-4 text-center text-gray-500">
                                    No proposals found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex justify-between items-center">
                <div className="text-sm text-gray-500">
                    Showing {proposals.from || 0} to {proposals.to || 0} of {proposals.total} results
                </div>
                <div className="flex space-x-1">
                    {proposals.links.map((link, idx) => (
                        <Link 
                            key={idx}
                            href={link.url || '#'}
                            className={`px-3 py-1 rounded text-sm ${link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'} ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
