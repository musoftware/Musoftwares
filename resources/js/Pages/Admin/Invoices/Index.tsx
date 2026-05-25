import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import ClientActionsSheet from '@/Pages/Admin/Users/ClientActionsSheet';
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
    const [selectedClient, setSelectedClient] = React.useState(null);
    const paginationLinks = invoices.meta?.links || invoices.links;

    const handleLoginAs = (id) => {
        router.post(route('admin.users.login-as', id));
    };

    const handleResetPassword = (id) => {
        router.post(route('admin.users.reset-password', id));
    };

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
                return <span className="inline-flex items-center rounded-full bg-green-500 px-2.5 py-0.5 text-xs font-medium text-white">Paid</span>;
            case 'partially_paid':
                return <span className="inline-flex items-center rounded-full bg-yellow-400 px-2.5 py-0.5 text-xs font-medium text-black">Partially Paid</span>;
            case 'cancelled':
                return <span className="inline-flex items-center rounded-full bg-gray-500 px-2.5 py-0.5 text-xs font-medium text-white">Cancelled</span>;
            case 'unpaid':
            default:
                return <span className="inline-flex items-center rounded-full bg-red-500 px-2.5 py-0.5 text-xs font-medium text-white">Unpaid</span>;
        }
    };

    const getJobStatusBadge = (status) => {
        switch (status) {
            case 'done':
                return <span className="inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Done</span>;
            case 'processing':
                return <span className="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">Processing</span>;
            default:
                return <span className="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">Pending</span>;
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
                            <th className="p-4 font-medium text-gray-600">ID</th>
                            <th className="p-4 font-medium text-gray-600">Customer</th>
                            <th className="p-4 font-medium text-gray-600">Project</th>
                            <th className="p-4 font-medium text-gray-600">Date</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Total</th>
                            <th className="p-4 font-medium text-gray-600 text-center">Job Status</th>
                            <th className="p-4 font-medium text-gray-600 text-center">Invoice Status</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {invoices.data.map((invoice) => (
                            <tr key={invoice.id} className="hover:bg-gray-50">
                                <td className="p-4 font-medium text-blue-600">
                                    <Link href={route('admin.invoices.show', invoice.id)} className="hover:underline">
                                        {invoice.invoice_number}
                                    </Link>
                                </td>
                                <td className="p-4">
                                    {invoice.user ? (
                                        <button
                                            type="button"
                                            onClick={() => setSelectedClient(invoice.user)}
                                            className="text-left font-medium text-gray-900 hover:text-blue-600 hover:underline focus:outline-none flex items-center gap-1"
                                        >
                                            {invoice.user.name}
                                            <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                    ) : (
                                        <div className="font-medium text-gray-900">Unknown</div>
                                    )}
                                    {invoice.user?.email && <div className="text-xs text-gray-500">{invoice.user.email}</div>}
                                </td>
                                <td className="p-4 font-medium text-gray-700">
                                    {invoice.project ? invoice.project.project_name : '-'}
                                </td>
                                <td className="p-4 text-gray-500 text-sm">
                                    {new Date(invoice.created_at).toLocaleDateString()}
                                </td>
                                <td className="p-4 text-right font-medium text-gray-900">
                                    <div className="flex flex-col items-end gap-1">
                                        <span>{formatCurrency(invoice.amount, invoice.currency)}</span>
                                        {invoice.status === 'partially_paid' && (
                                            <span className="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                                Paid {formatCurrency(invoice.paid_amount, invoice.currency)}
                                            </span>
                                        )}
                                    </div>
                                </td>
                                <td className="p-4 text-center">
                                    {getJobStatusBadge(invoice.job_status)}
                                </td>
                                <td className="p-4 text-center">
                                    {getStatusBadge(invoice.status)}
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
                                            <DropdownMenuItem asChild>
                                                <Link href={route('admin.invoices.show', invoice.id)} className="flex w-full items-center">
                                                    <FileText className="mr-2 h-4 w-4" />
                                                    View Details
                                                </Link>
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
            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                <div className="mt-4 flex justify-center">
                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                        {paginationLinks.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-4 py-2 text-sm font-medium border ${link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} ${i === 0 ? 'rounded-l-md' : ''} ${i === paginationLinks.length - 1 ? 'rounded-r-md' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            <ClientActionsSheet
                client={selectedClient}
                isOpen={!!selectedClient}
                onClose={() => setSelectedClient(null)}
                onLoginAs={handleLoginAs}
                onResetPassword={handleResetPassword}
            />
        </AdminSidebarLayout>
    );
}
