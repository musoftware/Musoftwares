import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import ClientLayout from '@/Layouts/ClientLayout';
import { Button } from '@/Components/ui/button';
import { MoreHorizontal, Trash2, Edit2, Mail, Phone, Users } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function Index({ leads, currentTab }) {
    const { auth } = usePage().props;

    const handleStatusUpdate = (id, status) => {
        router.post(route('crm.leads.update-status', id), { status });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this lead?')) {
            router.delete(route('crm.leads.destroy', id));
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'converted':
                return <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Converted</span>;
            case 'contacted':
                return <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Contacted</span>;
            case 'dead':
                return <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Dead</span>;
            case 'new':
            default:
                return <span className="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">New</span>;
        }
    };

    return (
        <ClientLayout 
            user={auth.user} 
            hasErpSubscription={auth.active_modules?.erp}
            hasCrmSubscription={auth.active_modules?.crm}
        >
            <Head title="CRM Leads" />
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight flex items-center">
                        <Users className="mr-2 h-6 w-6 text-indigo-600" />
                        Leads CRM
                    </h2>
                </div>
            </div>

            <div className="mb-6 flex items-center justify-between">
                <div className="flex space-x-4">
                    <Link
                        href={route('crm.leads.index', { status: 'all' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'all' ? 'bg-indigo-600 text-white' : 'bg-white shadow text-gray-700 hover:bg-gray-50'}`}
                    >
                        All Leads
                    </Link>
                    <Link
                        href={route('crm.leads.index', { status: 'new' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'new' ? 'bg-indigo-600 text-white' : 'bg-white shadow text-gray-700 hover:bg-gray-50'}`}
                    >
                        New
                    </Link>
                    <Link
                        href={route('crm.leads.index', { status: 'contacted' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'contacted' ? 'bg-indigo-600 text-white' : 'bg-white shadow text-gray-700 hover:bg-gray-50'}`}
                    >
                        Contacted
                    </Link>
                    <Link
                        href={route('crm.leads.index', { status: 'converted' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'converted' ? 'bg-indigo-600 text-white' : 'bg-white shadow text-gray-700 hover:bg-gray-50'}`}
                    >
                        Converted
                    </Link>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Name</th>
                            <th className="p-4 font-medium text-gray-600">Contact</th>
                            <th className="p-4 font-medium text-gray-600">Company</th>
                            <th className="p-4 font-medium text-gray-600">Message Snippet</th>
                            <th className="p-4 font-medium text-gray-600 text-center">Status</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {leads.data.map((lead) => (
                            <tr key={lead.id} className="hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{lead.name || 'Unknown'}</td>
                                <td className="p-4">
                                    <div className="flex flex-col space-y-1">
                                        {lead.email && (
                                            <a href={`mailto:${lead.email}`} className="text-blue-600 hover:underline flex items-center">
                                                <Mail className="h-3 w-3 mr-1" /> {lead.email}
                                            </a>
                                        )}
                                        {lead.phone && (
                                            <a href={`tel:${lead.phone}`} className="text-gray-600 hover:underline flex items-center">
                                                <Phone className="h-3 w-3 mr-1" /> {lead.phone}
                                            </a>
                                        )}
                                    </div>
                                </td>
                                <td className="p-4 text-gray-700">{lead.company || '-'}</td>
                                <td className="p-4 text-gray-600 max-w-xs truncate" title={lead.message}>
                                    {lead.message || '-'}
                                </td>
                                <td className="p-4 text-center">
                                    {getStatusBadge(lead.status)}
                                </td>
                                <td className="p-4 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">Open menu</span>
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>Update Status</DropdownMenuLabel>
                                            {['new', 'contacted', 'converted', 'dead'].map((status) => (
                                                lead.status !== status && (
                                                    <DropdownMenuItem key={status} onClick={() => handleStatusUpdate(lead.id, status)}>
                                                        Mark as {status.charAt(0).toUpperCase() + status.slice(1)}
                                                    </DropdownMenuItem>
                                                )
                                            ))}
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => handleDelete(lead.id)} className="text-red-600">
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Delete Lead
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {leads.data.length === 0 && (
                            <tr>
                                <td colSpan="6" className="p-8 text-center text-gray-500">
                                    No leads found for this status.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination Controls */}
            {leads.links && leads.links.length > 3 && (
                <div className="mt-4 flex justify-center">
                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                        {leads.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-4 py-2 text-sm font-medium border ${link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} ${i === 0 ? 'rounded-l-md' : ''} ${i === leads.links.length - 1 ? 'rounded-r-md' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </ClientLayout>
    );
}
