import React, { useState, useEffect } from 'react';
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

export default function Index({ invoices, currentTab, filters = {}, stats, projects = [] }) {
    const [selectedClient, setSelectedClient] = React.useState(null);
    const paginationLinks = invoices.meta?.links || invoices.links;

    const [searchTerm, setSearchTerm] = React.useState(filters.search || '');
    const [filterBy, setFilterBy] = React.useState(filters.filter_by || 'all');
    const [perPage, setPerPage] = React.useState(filters.per_page || '20');

    const [selectedInvoices, setSelectedInvoices] = useState({});
    const [selectAll, setSelectAll] = useState(false);
    const [bulkAction, setBulkAction] = useState('');
    const [bulkActionProject, setBulkActionProject] = useState('');

    useEffect(() => {
        if (selectAll) {
            const newSelected = {};
            invoices.data.forEach(inv => {
                newSelected[inv.id] = true;
            });
            setSelectedInvoices(newSelected);
        } else {
            setSelectedInvoices({});
        }
    }, [selectAll, invoices.data]);

    const handleSelectInvoice = (id, checked) => {
        setSelectedInvoices(prev => ({ ...prev, [id]: checked }));
    };

    const handleFilter = () => {
        router.get(route(`admin.invoices.${currentTab === 'all' ? 'index' : currentTab}`), {
            ...filters,
            search: searchTerm,
            filter_by: filterBy,
            per_page: perPage,
        }, { preserveState: true });
    };

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

    const applyBulkAction = () => {
        const selectedIds = Object.keys(selectedInvoices).filter(id => selectedInvoices[id]);
        if (selectedIds.length === 0) {
            alert('Please select at least one invoice.');
            return;
        }
        if (!bulkAction) {
            alert('Select Bulk Action first');
            return;
        }

        const messages = {
            convert_to_transaction: 'Are you sure you want to convert these invoices to transactions?',
            delete: 'Are you sure you want to delete selected invoices permanently?',
            send_whatsapp_reminder: 'Are you sure you want to send WhatsApp reminders for selected invoices?'
        };

        if (messages[bulkAction]) {
            if (!confirm(messages[bulkAction])) return;
        } else {
            if (!confirm(`Are you sure you want to apply this bulk action?`)) return;
        }

        router.post(route('admin.invoices.bulk-action'), {
            action: bulkAction,
            invoices: selectedIds,
            project_id: bulkActionProject
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedInvoices({});
                setSelectAll(false);
                setBulkAction('');
                setBulkActionProject('');
            }
        });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'paid':
                return <span className="badge rounded-pill bg-success text-white">Paid</span>;
            case 'partially_paid':
                return <span className="badge rounded-pill bg-warning text-dark">Partially Paid</span>;
            case 'cancelled':
                return <span className="badge rounded-pill bg-gray-500 text-white">Cancelled</span>;
            case 'unpaid':
            default:
                return <span className="badge rounded-pill bg-danger text-white">Unpaid</span>;
        }
    };

    const getJobStatusBadge = (status) => {
        switch (status) {
            case 'done':
                return <span className="badge bg-success bg-opacity-10 text-success">Done</span>;
            case 'processing':
                return <span className="badge bg-warning bg-opacity-10 text-warning">Processing</span>;
            default:
                return <span className="badge bg-secondary bg-opacity-10 text-secondary">Pending</span>;
        }
    };

    const buildTabUrl = (tab) => {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('page'); 
        const queryString = urlParams.toString();
        const routeName = tab === 'all' ? 'index' : tab;
        return `${route(`admin.invoices.${routeName}`)}${queryString ? `?${queryString}` : ''}`;
    };

    return (
        <AdminSidebarLayout title="Platform Invoices" header="Invoices Manager">
            
            {stats && (
                <div className="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-100 p-5">
                        <dt className="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                        <dd className="mt-1 text-3xl font-semibold text-gray-900">{stats.total}</dd>
                    </div>
                    <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-100 p-5">
                        <dt className="text-sm font-medium text-gray-500 truncate">Paid</dt>
                        <dd className="mt-1 text-3xl font-semibold text-green-600">{stats.paid}</dd>
                    </div>
                    <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-100 p-5">
                        <dt className="text-sm font-medium text-gray-500 truncate">Unpaid</dt>
                        <dd className="mt-1 text-3xl font-semibold text-red-600">{stats.unpaid}</dd>
                    </div>
                    <div className="bg-white overflow-hidden shadow rounded-lg border border-gray-100 p-5">
                        <dt className="text-sm font-medium text-gray-500 truncate">Partially Paid</dt>
                        <dd className="mt-1 text-3xl font-semibold text-yellow-500">{stats.partially_paid}</dd>
                    </div>
                </div>
            )}

            <div className="mb-4 flex items-center justify-between">
                <div className="flex space-x-2">
                    <Link
                        href={buildTabUrl('all')}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        All Invoices
                    </Link>
                    <Link
                        href={buildTabUrl('unpaid')}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'unpaid' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        Unpaid
                    </Link>
                    <Link
                        href={buildTabUrl('archive')}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'archive' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        Archived / Cancelled
                    </Link>
                </div>
            </div>

            {/* Header & Filters */}
            <div className="at-card border p-4 mb-4 invoice-index-filters bg-white shadow-sm">
                <div className="row g-3 align-items-center">
                    {filters.client_id && (
                        <div className="col-12 col-md-auto">
                            <Link href={route('admin.invoices.create', { user: filters.client_id, project: filters.project_id })}
                                className="at-btn at-btn-primary at-btn-sm px-4">
                                <i className="ti ti-plus me-1"></i> Add Invoice
                            </Link>
                        </div>
                    )}

                    <div className="col-12 col-md-3">
                        <label className="form-label small text-muted text-uppercase fw-bold mb-1">Filter By</label>
                        <select className="form-select form-select-sm invoice-index-select" value={filterBy} onChange={(e) => { setFilterBy(e.target.value); setTimeout(handleFilter, 50); }}>
                            <option value="all">All</option>
                            <option value="id">ID</option>
                            <option value="client_name">Customer Name</option>
                            <option value="date">Date</option>
                            <option value="total">Total</option>
                            <option value="status">Invoice Status</option>
                            <option value="unlinked">Unlinked Projects</option>
                        </select>
                    </div>

                    <div className="col-6 col-md-2">
                        <label className="form-label small text-muted text-uppercase fw-bold mb-1">Show</label>
                        <select className="form-select form-select-sm invoice-index-select" value={perPage} onChange={(e) => { setPerPage(e.target.value); setTimeout(handleFilter, 50); }}>
                            <option value="12">12</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div className="col-6 col-md ms-auto">
                        <div className="relative">
                            <input
                                type="text"
                                className="form-control form-control-sm border-gray-300 rounded"
                                placeholder="Search invoices..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleFilter();
                                    }
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Table Card */}
            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <div className="at-card bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div className="table-responsive">
                        <table className="table table-hover align-middle mb-0 invoice-index-table w-full">
                            <thead className="border-b bg-gray-50">
                                <tr>
                                    <th width="40" className="ps-3 hidden sm:table-cell">
                                        <input 
                                            type="checkbox" 
                                            className="form-check-input" 
                                            checked={selectAll}
                                            onChange={(e) => setSelectAll(e.target.checked)}
                                        />
                                    </th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold hidden sm:table-cell">ID</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold">Customer</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold">Project</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold">Date</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold text-right">Total</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold text-center">Job Status</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold text-center">Invoice Status</th>
                                    <th className="p-3 text-xs text-gray-500 uppercase font-bold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {invoices.data.map((invoice) => (
                                    <tr key={invoice.id} className="hover:bg-gray-50">
                                        <td className="ps-3 hidden sm:table-cell" data-label="">
                                            <input 
                                                type="checkbox" 
                                                className="form-check-input" 
                                                checked={!!selectedInvoices[invoice.id]}
                                                onChange={(e) => handleSelectInvoice(invoice.id, e.target.checked)}
                                            />
                                        </td>
                                        <td className="p-3 font-medium text-blue-600 hidden sm:table-cell" data-label="ID">
                                            <Link href={route('admin.invoices.show', invoice.id)} className="hover:underline fw-bold">
                                                {invoice.invoice_number}
                                            </Link>
                                        </td>
                                        <td className="p-3" data-label="Customer">
                                            {invoice.user ? (
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedClient(invoice.user)}
                                                    className="btn btn-link p-0 text-dark text-decoration-none fw-semibold flex items-center gap-1"
                                                >
                                                    {invoice.user.name}
                                                    <i className="ti ti-chevron-down ms-1 small text-muted hidden sm:inline-block"></i>
                                                </button>
                                            ) : (
                                                <div className="fw-semibold text-gray-900">Unknown</div>
                                            )}
                                        </td>
                                        <td className="p-3 fw-semibold text-dark" data-label="Project">
                                            {invoice.project ? invoice.project.project_name : '-'}
                                        </td>
                                        <td className="p-3 text-muted small" data-label="Date">
                                            {new Date(invoice.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="p-3 text-right" data-label="Total">
                                            <div className="flex flex-col items-end gap-1">
                                                <span className="fw-bold text-dark">{formatCurrency(invoice.business_amount || invoice.amount, invoice.business_currency || invoice.currency)}</span>
                                                {invoice.status === 'partially_paid' && (
                                                    <span className="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                                        Paid {formatCurrency(invoice.paid_amount, invoice.currency)}
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="p-3 text-center" data-label="Job Status">
                                            {getJobStatusBadge(invoice.job_status)}
                                        </td>
                                        <td className="p-3 text-center" data-label="Invoice Status">
                                            {getStatusBadge(invoice.status)}
                                        </td>
                                        <td className="p-3 text-right" data-label="Actions">
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
                                
                                {(filters.client_id || filters.search === 'unpaid_partial' || filters.search === 'archived') && invoices.data.length > 0 && (
                                    <tr className="bg-gray-50 fw-bold border-t border-gray-200">
                                        <td colSpan="5" className="p-3 text-right pe-3 hidden sm:table-cell" data-label="Total">
                                            Total
                                        </td>
                                        <td className="p-3 text-right text-gray-900 sm:hidden" data-label="Total">
                                            Total
                                        </td>
                                        <td className="p-3 text-right" data-label="Total Amount">
                                            {formatCurrency(
                                                invoices.data.reduce((sum, inv) => sum + (Number(inv.business_amount) || Number(inv.amount) || 0), 0),
                                                invoices.data[0]?.business_currency || invoices.data[0]?.currency || 'USD'
                                            )}
                                        </td>
                                        <td colSpan="3" className="hidden sm:table-cell"></td>
                                    </tr>
                                )}

                                {invoices.data.length === 0 && (
                                    <tr>
                                        <td colSpan="9" className="p-8 text-center text-gray-500">
                                            No invoices found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Footer Actions & Pagination */}
            <div className="at-card border p-4 mt-4 invoice-index-footer bg-white shadow-sm rounded-lg">
                <div className="row g-3 align-items-center">
                    <div className="col-12 col-md-auto">
                        <div className="d-flex gap-2 flex-wrap">
                            <select 
                                className="form-select form-select-sm invoice-index-select invoice-index-bulk-select" 
                                value={bulkAction}
                                onChange={(e) => setBulkAction(e.target.value)}
                            >
                                <option value="">Bulk Actions</option>
                                <optgroup label="Simple">
                                    <option value="bill_invoice">Bill Invoice</option>
                                    <option value="fix_calc">Fix Calc</option>
                                    <option value="merge">Merge</option>
                                    <option value="split">Split Invoice</option>
                                    <option value="change_project">Change Project</option>
                                    <option value="archive">Archive</option>
                                    <option value="unarchive">Unarchive</option>
                                    <option value="convert_to_transaction">Convert to Transaction</option>
                                    <option value="send_whatsapp_reminder">Send WhatsApp Reminder</option>
                                </optgroup>
                                <optgroup label="Advanced">
                                    <option value="delete">Delete</option>
                                </optgroup>
                            </select>

                            {bulkAction === 'change_project' && (
                                <select 
                                    className="form-select form-select-sm invoice-index-select invoice-index-bulk-select"
                                    value={bulkActionProject}
                                    onChange={(e) => setBulkActionProject(e.target.value)}
                                >
                                    <option value="">Select Project</option>
                                    {projects.map(project => (
                                        <option key={project.id} value={project.id}>{project.project_name}</option>
                                    ))}
                                </select>
                            )}

                            <Button onClick={applyBulkAction} className="at-btn at-btn-primary at-btn-sm px-4 h-8 bg-slate-900 text-white hover:bg-slate-800">
                                Apply
                            </Button>
                        </div>
                    </div>

                    <div className="col-12 col-md ms-auto">
                        {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                            <div className="d-flex justify-content-md-end">
                                <div className="inline-flex -space-x-px rounded-md shadow-sm">
                                    {paginationLinks.map((link, i) => (
                                        <Link
                                            key={i}
                                            href={link.url || '#'}
                                            className={`px-3 py-1 text-sm border ${link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 font-bold' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} ${i === 0 ? 'rounded-l-md' : ''} ${i === paginationLinks.length - 1 ? 'rounded-r-md' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

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
