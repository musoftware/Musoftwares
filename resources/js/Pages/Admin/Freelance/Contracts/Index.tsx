import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Eye, Trash2 } from 'lucide-react';

export default function Index({ contracts, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.freelance.contracts.index'), { search, status }, { preserveState: true });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this contract permanently?')) {
            router.delete(route('admin.freelance.contracts.destroy', id));
        }
    };

    return (
        <AdminSidebarLayout title="Freelance Contracts" header="Manage Contracts">
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2 w-full max-w-2xl">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by job title, client or freelancer..."
                        className="flex-1"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="disputed">Disputed</option>
                    </select>
                    <Button type="submit" variant="secondary">Filter</Button>
                    {(search || status !== 'all') && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); setStatus('all'); router.get(route('admin.freelance.contracts.index')); }}>
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
                            <th className="p-4 font-medium text-gray-600">Freelancer</th>
                            <th className="p-4 font-medium text-gray-600">Amount</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600">Created At</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {contracts.data.map((contract) => (
                            <tr key={contract.id} className="border-b hover:bg-gray-50">
                                <td className="p-4">
                                    {contract.job ? (
                                        <span className="font-medium text-gray-900">{contract.job.title}</span>
                                    ) : (
                                        <span className="text-gray-500 italic">Job Deleted</span>
                                    )}
                                </td>
                                <td className="p-4 text-gray-700">
                                    <div>{contract.client?.name || 'Unknown'}</div>
                                    <div className="text-xs text-gray-500">{contract.client?.email}</div>
                                </td>
                                <td className="p-4 text-gray-700">
                                    <div>{contract.freelancer?.name || 'Unknown'}</div>
                                    <div className="text-xs text-gray-500">{contract.freelancer?.email}</div>
                                </td>
                                <td className="p-4 font-medium text-blue-600">{contract.amount} {contract.currency_code}</td>
                                <td className="p-4 capitalize">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                        ${contract.status === 'active' ? 'bg-green-100 text-green-800' : 
                                          contract.status === 'disputed' ? 'bg-red-100 text-red-800' :
                                          contract.status === 'cancelled' ? 'bg-gray-100 text-gray-800' :
                                          'bg-blue-100 text-blue-800'}`}>
                                        {contract.status}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">{new Date(contract.created_at).toLocaleDateString()}</td>
                                <td className="p-4 space-x-2 text-right">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={route('admin.freelance.contracts.show', contract.id)}>
                                            <Eye className="h-4 w-4 mr-1" /> View
                                        </Link>
                                    </Button>
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(contract.id)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {contracts.data.length === 0 && (
                            <tr>
                                <td colSpan="7" className="p-4 text-center text-gray-500">
                                    No contracts found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex justify-between items-center">
                <div className="text-sm text-gray-500">
                    Showing {contracts.from || 0} to {contracts.to || 0} of {contracts.total} results
                </div>
                <div className="flex space-x-1">
                    {contracts.links.map((link, idx) => (
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
