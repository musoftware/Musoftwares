import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Eye, Trash2, MoreHorizontal } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';

export default function Index({ contracts, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleSearch = (e: any) => {
        e.preventDefault();
        router.get(route('admin.freelance.contracts.index'), { search, status }, { preserveState: true });
    };

    const [deleteConfirm, setDeleteConfirm] = useState<any>(null);

    const handleDelete = () => {
        if (deleteConfirm) {
            router.delete(route('admin.freelance.contracts.destroy', deleteConfirm));
            setDeleteConfirm(null);
        }
    };

    return (
        <AdminSidebarLayout title={__('freelance.admin_contracts', undefined, 'Freelance Contracts')} header={__('freelance.manage_contracts', undefined, 'Manage Contracts')}>
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2 w-full max-w-2xl">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={__('freelance.search_contracts_placeholder', undefined, 'Search by job title, client or freelancer...')}
                        className="flex-1"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                    >
                        <option value="all">{__('freelance.all_statuses')}</option>
                        <option value="active">{__('freelance.active', undefined, 'Active')}</option>
                        <option value="completed">{__('freelance.completed', undefined, 'Completed')}</option>
                        <option value="cancelled">{__('freelance.cancelled', undefined, 'Cancelled')}</option>
                        <option value="disputed">{__('freelance.disputed', undefined, 'Disputed')}</option>
                    </select>
                    <Button type="submit" variant="secondary">{__('freelance.filter')}</Button>
                    {(search || status !== 'all') && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); setStatus('all'); router.get(route('admin.freelance.contracts.index')); }}>
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
                            <th className="p-4 font-medium text-gray-600">{__('freelance.freelancer', undefined, 'Freelancer')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.amount', undefined, 'Amount')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.status')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.created_at')}</th>
                            <th className="p-4 font-medium text-gray-600 text-right">{__('freelance.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(contracts.data as any).map((contract: any) => (
                            <tr key={contract.id} className="border-b hover:bg-gray-50">
                                <td className="p-4">
                                    {contract.job ? (
                                        <span className="font-medium text-gray-900">{contract.job.title}</span>
                                    ) : (
                                        <span className="text-gray-500 italic">{__('freelance.job_deleted', undefined, 'Job Deleted')}</span>
                                    )}
                                </td>
                                <td className="p-4 text-gray-700">
                                    <div>{contract.client?.name || __('freelance.unknown_client')}</div>
                                    <div className="text-xs text-gray-500">{contract.client?.email}</div>
                                </td>
                                <td className="p-4 text-gray-700">
                                    <div>{contract.freelancer?.name || __('freelance.unknown_freelancer')}</div>
                                    <div className="text-xs text-gray-500">{contract.freelancer?.email}</div>
                                </td>
                                <td className="p-4 font-medium text-slate-900">{contract.formatted_amount}</td>
                                <td className="p-4 capitalize">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                        ${contract.status === 'active' ? 'bg-green-100 text-green-800' : 
                                          contract.status === 'disputed' ? 'bg-red-100 text-red-800' :
                                          contract.status === 'cancelled' ? 'bg-gray-100 text-gray-800' :
                                          'bg-blue-100 text-blue-800'}`}>
                                        {__('freelance.' + contract.status) || contract.status}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">{new Date(contract.created_at).toLocaleDateString()}</td>
                                <td className="p-4 text-right">
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
                                                <Link href={route('admin.freelance.contracts.show', contract.id)} className="cursor-pointer flex w-full items-center">
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    <span>{__('freelance.view')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => setDeleteConfirm(contract.id)} className="text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer">
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                <span>{__('freelance.delete')}</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {(contracts.data as any).length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-4 text-center text-gray-500">
                                    {__('freelance.no_contracts_found', undefined, 'No contracts found.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {contracts.links && contracts.links.length > 3 && (
                <div className="mt-4 flex justify-between items-center">
                    <div className="text-sm text-gray-500">
                        {__('freelance.showing_results_of', {
                            first: contracts.from || 0,
                            last: contracts.to || 0,
                            total: contracts.total
                        })}
                    </div>
                    <div className="flex space-x-1">
                        {contracts.links.map((link: any, idx: number) => (
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

            <ConfirmModal 
                isOpen={!!deleteConfirm} 
                onCancel={() => setDeleteConfirm(null)}
                onConfirm={handleDelete}
                title={__('freelance.confirm_delete_contract', undefined, 'Delete Contract?')}
                description={__('freelance.confirm_delete_contract_msg', undefined, 'Are you sure you want to delete this contract permanently?')}
                confirmLabel={__('freelance.delete')}
                cancelLabel={__('freelance.cancel')}
                variant="danger"
            />
        </AdminSidebarLayout>
    );
}
