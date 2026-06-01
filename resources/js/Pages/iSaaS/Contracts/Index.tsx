import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { MoreHorizontal, FileText, Send, CheckCircle, XCircle, Trash2 } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function Index({ contracts, currentTab }) {

    const handleStatusUpdate = (id, status) => {
        router.post(route('isaas.contracts.update-status', id), { status });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this contract?')) {
            router.delete(route('isaas.contracts.destroy', id));
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'signed':
                return <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Signed</span>;
            case 'sent':
                return <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Sent</span>;
            case 'cancelled':
                return <span className="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Cancelled</span>;
            case 'draft':
            default:
                return <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Draft</span>;
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.contracts_manager')} />
            
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 mb-3 border border-slate-200">{__('general.freelance_tools')}</span>
                    <div className="flex items-baseline gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900">{__('general.contracts_manager')}</h1>
                        <span className="text-slate-500 font-medium">/ iSAAS</span>
                    </div>
                </div>

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex space-x-4">
                        <Link
                            href={route('isaas.contracts.index', { status: 'all' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'all' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                        >{__('general.all_contracts')}</Link>
                        <Link
                            href={route('isaas.contracts.index', { status: 'draft' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'draft' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                        >
                            Drafts
                        </Link>
                        <Link
                            href={route('isaas.contracts.index', { status: 'sent' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'sent' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                        >
                            Sent
                        </Link>
                        <Link
                            href={route('isaas.contracts.index', { status: 'signed' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'signed' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                        >
                            Signed
                        </Link>
                    </div>
                    <Link href={route('isaas.contracts.create')}>
                        <Button className="bg-slate-900 hover:bg-slate-800 text-white">
                            + Create Contract
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow border border-slate-200">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50/55">
                            <tr>
                                <th className="p-4 font-semibold text-slate-600">Reference</th>
                                <th className="p-4 font-semibold text-slate-600">{__('general.client_user')}</th>
                                <th className="p-4 font-semibold text-slate-600">Project</th>
                                <th className="p-4 font-semibold text-slate-600 text-right">Amount</th>
                                <th className="p-4 font-semibold text-slate-600 text-center">Status</th>
                                <th className="p-4 font-semibold text-slate-600 text-center">{__('general.valid_until')}</th>
                                <th className="p-4 font-semibold text-slate-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {contracts.data.map((contract) => (
                                <tr key={contract.id} className="hover:bg-gray-50/30">
                                    <td className="p-4 font-medium text-gray-900">{contract.reference || `CTR-${contract.id}`}</td>
                                    <td className="p-4">
                                        <div className="font-medium text-gray-900">{contract.client_name || 'Unknown'}</div>
                                    </td>
                                    <td className="p-4 text-gray-700">
                                        <div className="font-medium">{contract.project_name || 'N/A'}</div>
                                    </td>
                                    <td className="p-4 text-right font-medium text-gray-900">
                                        {contract.total_amount ? `${parseFloat(contract.total_amount).toFixed(2)} ${contract.currency}` : '-'}
                                    </td>
                                    <td className="p-4 text-center">
                                        {getStatusBadge(contract.status)}
                                    </td>
                                    <td className="p-4 text-center text-gray-500">
                                        {contract.valid_until ? new Date(contract.valid_until).toLocaleDateString() : '-'}
                                    </td>
                                    <td className="p-4 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuItem onClick={() => router.get(route('isaas.contracts.edit', contract.id))}>
                                                    <FileText className="mr-2 h-4 w-4" />{__('general.view_edit')}</DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {contract.status === 'draft' && (
                                                    <DropdownMenuItem onClick={() => handleStatusUpdate(contract.id, 'sent')}>
                                                        <Send className="mr-2 h-4 w-4 text-blue-600" />{__('general.mark_as_sent')}</DropdownMenuItem>
                                                )}
                                                {contract.status === 'sent' && (
                                                    <DropdownMenuItem onClick={() => handleStatusUpdate(contract.id, 'signed')}>
                                                        <CheckCircle className="mr-2 h-4 w-4 text-green-600" />{__('general.mark_as_signed')}</DropdownMenuItem>
                                                )}
                                                {contract.status !== 'cancelled' && (
                                                    <DropdownMenuItem onClick={() => handleStatusUpdate(contract.id, 'cancelled')} className="text-yellow-600">
                                                        <XCircle className="mr-2 h-4 w-4" />{__('general.cancel_contract')}</DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem onClick={() => handleDelete(contract.id)} className="text-red-600">
                                                    <Trash2 className="mr-2 h-4 w-4" />{__('general.delete_contract')}</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                            {contracts.data.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="p-8 text-center text-gray-500">{__('general.no_contracts_found')}</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                {contracts.links && contracts.links.length > 3 && (
                    <div className="mt-4 flex justify-center">
                        <div className="inline-flex -space-x-px rounded-md shadow-sm">
                            {contracts.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    className={`px-4 py-2 text-sm font-medium border ${link.active ? 'z-10 bg-slate-100 border-slate-500 text-slate-800' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} ${i === 0 ? 'rounded-l-md' : ''} ${i === contracts.links.length - 1 ? 'rounded-r-md' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
