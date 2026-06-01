import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Eye, Trash2, CheckCircle, XCircle, AlertCircle, MoreHorizontal, Edit, RefreshCw } from 'lucide-react';
import { __ } from '@/lib/i18n';

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

    const forceRefund = (id: any) => {
        if (confirm('Are you sure you want to cancel this job and force refund points to the client?')) {
            router.post(route('admin.freelance.jobs.force-refund', id), {}, {
                preserveScroll: true
            });
        }
    };

    return (
        <AdminSidebarLayout title={__('freelance.admin_jobs')} header={__('freelance.manage_jobs')}>
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2 w-full max-w-2xl">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={__('freelance.search_placeholder')}
                        className="flex-1"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                    >
                        <option value="all">{__('freelance.all_statuses')}</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <Button type="submit" variant="secondary">{__('freelance.filter')}</Button>
                    {(search || status !== 'all') && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); setStatus('all'); router.get(route('admin.freelance.jobs.index')); }}>
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
                            <th className="p-4 font-medium text-gray-600">{__('freelance.client')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.budget')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.proposals')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.status')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.created_at')}</th>
                            <th className="p-4 font-medium text-gray-600 text-right">{__('freelance.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {jobs.data.map((job: any) => (
                            <tr key={job.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{job.title}</td>
                                <td className="p-4 text-gray-700">
                                    <div>{job.client?.name || __('freelance.unknown_client')}</div>
                                    <div className="text-xs text-gray-500">{job.client?.email}</div>
                                </td>
                                <td className="p-4 font-medium text-slate-900">{job.formatted_budget}</td>
                                <td className="p-4">{job.proposals_count || 0}</td>
                                <td className="p-4 capitalize">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                        ${job.status === 'published' || job.status === 'open' ? 'bg-green-100 text-green-800' : 
                                          job.status === 'suspended' ? 'bg-red-100 text-red-800' :
                                          job.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                          'bg-gray-100 text-gray-800'}`}>
                                        {job.status.replace('_', ' ')}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">{new Date(job.created_at).toLocaleDateString()}</td>
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
                                                <Link href={route('admin.freelance.jobs.show', job.id)} className="cursor-pointer flex w-full items-center">
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.view')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link href={route('admin.freelance.jobs.edit', job.id)} className="cursor-pointer flex w-full items-center">
                                                    <Edit className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.edit')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            
                                            {(job.status === 'published' || job.status === 'open') && (
                                                <DropdownMenuItem onClick={() => updateStatus(job.id, 'suspended')} className="text-yellow-600 focus:text-yellow-600 focus:bg-yellow-50 cursor-pointer">
                                                    <AlertCircle className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.suspend')}</span>
                                                </DropdownMenuItem>
                                            )}
                                            
                                            {job.status === 'suspended' && (
                                                <DropdownMenuItem onClick={() => updateStatus(job.id, 'published')} className="text-green-600 focus:text-green-600 focus:bg-green-50 cursor-pointer">
                                                    <CheckCircle className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.restore')}</span>
                                                </DropdownMenuItem>
                                            )}

                                            {(job.status === 'published' || job.status === 'open' || job.status === 'suspended') && (
                                                <DropdownMenuItem onClick={() => forceRefund(job.id)} className="text-orange-600 focus:text-orange-600 focus:bg-orange-50 cursor-pointer">
                                                    <RefreshCw className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.force_refund')}</span>
                                                </DropdownMenuItem>
                                            )}

                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => handleDelete(job.id)} className="text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer">
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                <span>{__('freelance.delete')}</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {jobs.data.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-4 text-center text-gray-500">
                                    {__('freelance.no_jobs_found')}
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
