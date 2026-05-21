import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { MoreHorizontal, FileText, CheckCircle, XCircle } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function Index({ invoices, currentTab }) {

    const handleMarkPaid = (id) => {
        if (confirm('Are you sure you want to mark this invoice as paid manually? This will adjust balances directly.')) {
            router.post(route('admin.invoices.mark-paid', id));
        }
    };

    const handleCancel = (id) => {
        if (confirm('Are you sure you want to cancel this invoice? If it was partially paid, the user will be refunded their wallet balance.')) {
            router.post(route('admin.invoices.cancel', id));
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'paid':
                return <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Paid</span>;
            case 'partially_paid':
                return <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Partially Paid</span>;
            case 'cancelled':
                return <span className="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Cancelled</span>;
            case 'unpaid':
            default:
                return <span className="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Unpaid</span>;
        }
    };

    return (
        <AdminSidebarLayout title="Platform Invoices" header="Invoices Manager">
            <div className="mb-6 flex items-center justify-between">
                <div className="flex space-x-4">
                    <Link
                        href={route('admin.invoices.index')}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        All Invoices
                    </Link>
                    <Link
                        href={route('admin.invoices.unpaid')}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'unpaid' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        Unpaid
                    </Link>
                    <Link
                        href={route('admin.invoices.archive')}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'archive' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        Archived / Cancelled
                    </Link>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Invoice #</th>
                            <th className="p-4 font-medium text-gray-600">User</th>
                            <th className="p-4 font-medium text-gray-600">Title</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Amount</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Paid Amount</th>
                            <th className="p-4 font-medium text-gray-600 text-center">Status</th>
                            <th className="p-4 font-medium text-gray-600 text-center">Due Date</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {invoices.data.map((invoice) => (
                            <tr key={invoice.id} className="hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{invoice.invoice_number}</td>
                                <td className="p-4">
                                    <div className="font-medium text-gray-900">{invoice.user?.name || 'Unknown'}</div>
                                    <div className="text-xs text-gray-500">{invoice.user?.email}</div>
                                </td>
                                <td className="p-4 text-gray-700">{invoice.title}</td>
                                <td className="p-4 text-right font-medium text-gray-900">
                                    {invoice.amount ? `${parseFloat(invoice.amount).toFixed(2)} ${invoice.currency}` : '-'}
                                </td>
                                <td className="p-4 text-right text-gray-600">
                                    {invoice.paid_amount ? `${parseFloat(invoice.paid_amount).toFixed(2)} ${invoice.currency}` : '0.00 ' + invoice.currency}
                                </td>
                                <td className="p-4 text-center">
                                    {getStatusBadge(invoice.status)}
                                </td>
                                <td className="p-4 text-center text-gray-500">
                                    {invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : '-'}
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
                                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                            <DropdownMenuItem onClick={() => alert('View Details coming soon.')}>
                                                <FileText className="mr-2 h-4 w-4" />
                                                View Details
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            {invoice.status !== 'paid' && invoice.status !== 'cancelled' && (
                                                <DropdownMenuItem onClick={() => handleMarkPaid(invoice.id)}>
                                                    <CheckCircle className="mr-2 h-4 w-4 text-green-600" />
                                                    Mark as Paid
                                                </DropdownMenuItem>
                                            )}
                                            {invoice.status !== 'cancelled' && (
                                                <DropdownMenuItem onClick={() => handleCancel(invoice.id)} className="text-red-600">
                                                    <XCircle className="mr-2 h-4 w-4" />
                                                    Cancel Invoice
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {invoices.data.length === 0 && (
                            <tr>
                                <td colSpan="8" className="p-8 text-center text-gray-500">
                                    No invoices found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination Controls */}
            {invoices.links && invoices.links.length > 3 && (
                <div className="mt-4 flex justify-center">
                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                        {invoices.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-4 py-2 text-sm font-medium border ${link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} ${i === 0 ? 'rounded-l-md' : ''} ${i === invoices.links.length - 1 ? 'rounded-r-md' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}
