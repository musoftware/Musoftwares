import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Eye, Trash2, CheckCircle, XCircle, AlertCircle, MoreHorizontal, Edit, RefreshCw, Plus } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function Index({ jobs, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    
    // Alert Dialog States
    const [deleteConfirm, setDeleteConfirm] = useState<{ open: boolean; id: any }>({ open: false, id: null });
    const [refundConfirm, setRefundConfirm] = useState<{ open: boolean; id: any }>({ open: false, id: null });

    const handleSearch = (e: any) => {
        e.preventDefault();
        router.get(route('admin.freelance.jobs.index'), { search, status }, { preserveState: true });
    };

    const handleDelete = () => {
        if (deleteConfirm.id) {
            router.delete(route('admin.freelance.jobs.destroy', deleteConfirm.id));
            setDeleteConfirm({ open: false, id: null });
        }
    };

    const updateStatus = (id: any, newStatus: any) => {
        router.post(route('admin.freelance.jobs.status', id), { status: newStatus }, {
            preserveScroll: true
        });
    };

    const forceRefund = () => {
        if (refundConfirm.id) {
            router.post(route('admin.freelance.jobs.force-refund', refundConfirm.id), {}, {
                preserveScroll: true
            });
            setRefundConfirm({ open: false, id: null });
        }
    };

    return (
        <AdminSidebarLayout 
            title={__('freelance.admin_jobs')} 
            header={__('freelance.manage_jobs')}
            {...({ actions: (
                <Link href={route('admin.freelance.jobs.create')}>
                    <Button size="sm" className="shadow-none">
                        <Plus className="me-1.5 h-3.5 w-3.5" />
                        {__('freelance.create_job')}
                    </Button>
                </Link>
            ) }) as any}
        >
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
                        <option value="draft">{__('freelance.draft')}</option>
                        <option value="published">{__('freelance.published')}</option>
                        <option value="open">{__('freelance.open')}</option>
                        <option value="in_progress">{__('freelance.in_progress')}</option>
                        <option value="completed">{__('freelance.completed')}</option>
                        <option value="cancelled">{__('freelance.cancelled')}</option>
                        <option value="suspended">{__('freelance.suspended')}</option>
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
                <table className="w-full text-start text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.job_title')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.client')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.budget')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.proposals')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.status')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.created_at')}</th>
                            <th className="p-4 font-medium text-gray-600 text-end">{__('freelance.actions')}</th>
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
                                <td className="p-4 font-medium text-slate-900">{formatCurrency(job.budget, job.currency)}</td>
                                <td className="p-4">{job.proposals_count || 0}</td>
                                <td className="p-4 capitalize">
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold leading-5 
                                        ${job.status === 'published' || job.status === 'open' ? 'bg-green-100 text-green-800' : 
                                          job.status === 'suspended' ? 'bg-red-100 text-red-800' :
                                          job.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                          job.status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' :
                                          'bg-gray-100 text-gray-800'}`}>
                                        {__('freelance.' + job.status) || job.status.replace('_', ' ')}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">{new Date(job.created_at).toLocaleDateString()}</td>
                                <td className="p-4 text-end">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">{__('general.open_menu')}</span>
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-56">
                                            <DropdownMenuLabel>{__('freelance.actions')}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem asChild>
                                                <Link href={route('admin.freelance.jobs.show', job.id)} className="cursor-pointer flex w-full items-center">
                                                    <Eye className="me-2 h-4 w-4" />
                                                    <span>{__('freelance.view')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link href={route('admin.freelance.jobs.edit', job.id)} className="cursor-pointer flex w-full items-center">
                                                    <Edit className="me-2 h-4 w-4" />
                                                    <span>{__('freelance.edit')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            
                                            {(job.status === 'published' || job.status === 'open') && (
                                                <DropdownMenuItem onClick={() => updateStatus(job.id, 'suspended')} className="text-yellow-600 focus:text-yellow-600 focus:bg-yellow-50 cursor-pointer">
                                                    <AlertCircle className="me-2 h-4 w-4" />
                                                    <span>{__('freelance.suspend')}</span>
                                                </DropdownMenuItem>
                                            )}
                                            
                                            {job.status === 'suspended' && (
                                                <DropdownMenuItem onClick={() => updateStatus(job.id, 'published')} className="text-green-600 focus:text-green-600 focus:bg-green-50 cursor-pointer">
                                                    <CheckCircle className="me-2 h-4 w-4" />
                                                    <span>{__('freelance.restore')}</span>
                                                </DropdownMenuItem>
                                            )}

                                            {(job.status === 'published' || job.status === 'open' || job.status === 'suspended') && (
                                                <DropdownMenuItem onClick={() => setRefundConfirm({ open: true, id: job.id })} className="text-orange-600 focus:text-orange-600 focus:bg-orange-50 cursor-pointer">
                                                    <RefreshCw className="me-2 h-4 w-4" />
                                                    <span>{__('freelance.force_refund')}</span>
                                                </DropdownMenuItem>
                                            )}

                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => setDeleteConfirm({ open: true, id: job.id })} className="text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer">
                                                <Trash2 className="me-2 h-4 w-4" />
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
                        {__('freelance.showing_results', { from: jobs.from || 0, to: jobs.to || 0, total: jobs.total })}
                    </div>
                    <div className="flex space-x-1">
                        {jobs.links.map((link: any, idx: number) => {
                            let label = link.label;
                            if (label.includes('Previous')) label = '&laquo;';
                            if (label.includes('Next')) label = '&raquo;';
                            return (
                                <Link 
                                    key={idx}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded text-sm transition ${link.active ? 'bg-slate-900 text-white shadow-sm' : !link.url ? 'cursor-not-allowed opacity-50 text-slate-300 pointer-events-none' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                                    dangerouslySetInnerHTML={{ __html: label }}
                                />
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            <ConfirmModal 
                isOpen={deleteConfirm.open} 
                onCancel={() => setDeleteConfirm({ open: false, id: null })}
                onConfirm={handleDelete}
                title={__('freelance.confirm_delete_job')}
                description={__('freelance.confirm_delete_job_msg')}
                confirmLabel={__('freelance.delete')}
                cancelLabel={__('freelance.cancel')}
                variant="danger"
            />

            {/* Refund Confirmation Modal */}
            <ConfirmModal 
                isOpen={refundConfirm.open} 
                onCancel={() => setRefundConfirm({ open: false, id: null })}
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
