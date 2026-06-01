import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Eye, Trash2, MoreHorizontal } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Index({ proposals, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleSearch = (e: any) => {
        e.preventDefault();
        router.get(route('admin.freelance.proposals.index'), { search, status }, { preserveState: true });
    };

    const handleDelete = (id: any) => {
        if (confirm('Are you sure you want to delete this proposal permanently?')) {
            router.delete(route('admin.freelance.proposals.destroy', id));
        }
    };

    return (
        <AdminSidebarLayout title={__('freelance.admin_proposals', undefined, 'Freelance Proposals')} header={__('freelance.manage_proposals', undefined, 'Manage Proposals')}>
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2 w-full max-w-2xl">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={__('freelance.search_proposals_placeholder', undefined, 'Search by job title or freelancer name/email...')}
                        className="flex-1"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                    >
                        <option value="all">{__('freelance.all_statuses')}</option>
                        <option value="pending">{__('freelance.pending', undefined, 'Pending')}</option>
                        <option value="accepted">{__('freelance.accepted', undefined, 'Accepted')}</option>
                        <option value="rejected">{__('freelance.rejected', undefined, 'Rejected')}</option>
                        <option value="withdrawn">{__('freelance.withdrawn', undefined, 'Withdrawn')}</option>
                    </select>
                    <Button type="submit" variant="secondary">{__('freelance.filter')}</Button>
                    {(search || status !== 'all') && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); setStatus('all'); router.get(route('admin.freelance.proposals.index')); }}>
                            {__('freelance.clear')}
                        </Button>
                    )}
                </form>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.job_title')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.freelancer')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.bid_amount', undefined, 'Bid Amount')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.status')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.submitted_at', undefined, 'Submitted At')}</th>
                            <th className="p-4 font-medium text-gray-600 text-right">{__('freelance.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {proposals.data.map((proposal: any) => (
                            <tr key={proposal.id} className="border-b hover:bg-gray-50">
                                <td className="p-4">
                                    {proposal.job ? (
                                        <Link href={route('admin.freelance.jobs.show', proposal.job.id)} className="font-medium text-blue-600 hover:underline">
                                            {proposal.job.title}
                                        </Link>
                                    ) : (
                                        <span className="text-gray-500 italic">{__('freelance.job_deleted', undefined, 'Job Deleted')}</span>
                                    )}
                                </td>
                                <td className="p-4 text-gray-700">
                                    <div>{proposal.freelancer?.name || __('freelance.unknown_freelancer')}</div>
                                    <div className="text-xs text-gray-500">{proposal.freelancer?.email}</div>
                                </td>
                                <td className="p-4 font-medium text-slate-900">{proposal.formatted_bid_amount}</td>
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
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">Open menu</span>
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-56">
                                            <DropdownMenuLabel>{__('freelance.actions')}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem asChild>
                                                <Link href={route('admin.freelance.proposals.show', proposal.id)} className="cursor-pointer flex w-full items-center">
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.view_proposal', undefined, 'View Proposal')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => handleDelete(proposal.id)} className="text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer">
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                <span>{__('freelance.delete')}</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {proposals.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-4 text-center text-gray-500">
                                    {__('freelance.no_proposals_found', undefined, 'No proposals found.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {proposals.links && proposals.links.length > 3 && (
                <div className="mt-4 flex justify-between items-center">
                    <div className="text-sm text-gray-500">
                        Showing {proposals.from || 0} to {proposals.to || 0} of {proposals.total} results
                    </div>
                    <div className="flex space-x-1">
                        {proposals.links.map((link: any, idx: number) => (
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
