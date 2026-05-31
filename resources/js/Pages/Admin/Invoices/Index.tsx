import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import ClientActionsSheet from '@/Pages/Admin/Users/ClientActionsSheet';
import { MoreHorizontal, FileText, CheckCircle, XCircle, ChevronDown, Plus, List, Receipt, Clock, User, ClipboardList } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';

const filterByOptions = [
    { value: 'all', label: 'All' },
    { value: 'id', label: 'ID' },
    { value: 'client_name', label: 'Customer Name' },
    { value: 'date', label: 'Date' },
    { value: 'total', label: 'Total' },
    { value: 'status', label: 'Invoice Status' },
    { value: 'unlinked', label: 'Unlinked Projects' }
];

const perPageOptions = [
    { value: '12', label: '12' },
    { value: '20', label: '20' },
    { value: '50', label: '50' },
    { value: '100', label: '100' }
];

const bulkActionOptions = [
    { value: 'bill_invoice', label: 'Bill Invoice' },
    { value: 'fix_calc', label: 'Fix Calc' },
    { value: 'merge', label: 'Merge' },
    { value: 'split', label: 'Split Invoice' },
    { value: 'change_project', label: 'Change Project' },
    { value: 'archive', label: 'Archive' },
    { value: 'unarchive', label: 'Unarchive' },
    { value: 'convert_to_transaction', label: 'Convert to Transaction' },
    { value: 'send_whatsapp_reminder', label: 'Send WhatsApp Reminder' },
    { value: 'delete', label: 'Delete' }
];

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
    const [statusDialog, setStatusDialog] = useState(null);
    const [newStatus, setNewStatus] = useState('');
    const [jobStatusDialog, setJobStatusDialog] = useState(null);
    const [newJobStatus, setNewJobStatus] = useState('');

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

    const handleChangeStatus = () => {
        if (!statusDialog || !newStatus) return;
        
        router.post(route('admin.invoices.change-status', statusDialog.id), {
            status: newStatus
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setStatusDialog(null);
            }
        });
    };

    const handleChangeJobStatus = () => {
        if (!jobStatusDialog || !newJobStatus) return;
        
        router.post(route('admin.invoices.change-job-status', jobStatusDialog.id), {
            job_status: newJobStatus
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setJobStatusDialog(null);
            }
        });
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
                return <StatusBadge status="paid" />;
            case 'partially_paid':
                return <StatusBadge status="partially_paid" label="Partially Paid" className="bg-amber-50 text-amber-700 border-amber-100" />;
            case 'cancelled':
                return <StatusBadge status="cancelled" />;
            case 'unpaid':
            default:
                return <StatusBadge status="unpaid" className="bg-red-50 text-red-700 border-red-100" />;
        }
    };

    const getJobStatusBadge = (status) => {
        switch (status) {
            case 'done':
                return <StatusBadge status="done" label="Done" className="bg-emerald-50 text-emerald-700 border-emerald-100" />;
            case 'processing':
                return <StatusBadge status="processing" label="Processing" className="bg-amber-50 text-amber-700 border-amber-100" />;
            default:
                return <StatusBadge status="pending" label="Pending" className="bg-slate-50 text-slate-700 border-slate-100" />;
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
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">Total Invoices</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.total}</dd>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">Paid</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.paid}</dd>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">Unpaid</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.unpaid}</dd>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">Partially Paid</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.partially_paid}</dd>
                        </CardContent>
                    </Card>
                </div>
            )}

            <div className="mb-4 flex items-center justify-between">
                <div className="flex space-x-2">
                    <Link
                        href={buildTabUrl('all')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'all' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >
                        All Invoices
                    </Link>
                    <Link
                        href={buildTabUrl('unpaid')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'unpaid' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >
                        Unpaid
                    </Link>
                    <Link
                        href={buildTabUrl('archive')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'archive' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >
                        Archived / Cancelled
                    </Link>
                </div>
            </div>

            {/* Header & Filters */}
            <Card className="mb-4 bg-white shadow-sm">
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 md:flex-row md:items-end">
                        {filters.client_id && (
                            <Link href={route('admin.invoices.create', { user: filters.client_id, project: filters.project_id })}>
                                <Button size="sm">
                                    <Plus className="mr-2 h-4 w-4" /> Add Invoice
                                </Button>
                            </Link>
                        )}

                        <div className="w-full md:w-48">
                            <Label className="mb-2 block text-xs uppercase text-muted-foreground">Filter By</Label>
                            <PremiumCombobox
                                value={filterBy}
                                onChange={(val) => { setFilterBy(String(val)); setTimeout(handleFilter, 50); }}
                                options={filterByOptions}
                                placeholder="Select filter..."
                            />
                        </div>

                        <div className="w-full md:w-32">
                            <Label className="mb-2 block text-xs uppercase text-muted-foreground">Show</Label>
                            <PremiumCombobox
                                value={perPage}
                                onChange={(val) => { setPerPage(String(val)); setTimeout(handleFilter, 50); }}
                                options={perPageOptions}
                                placeholder="Per page"
                            />
                        </div>

                        <div className="w-full md:ml-auto md:w-64">
                            <Label className="mb-2 hidden text-xs uppercase text-muted-foreground md:block">&nbsp;</Label>
                            <Input
                                type="text"
                                className="h-9"
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
                </CardContent>
            </Card>

            {/* Table Card */}
            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <Card className="bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div className="table-responsive">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50 border-b">
                                    <TableHead className="w-12 hidden sm:table-cell text-center">
                                        <input 
                                            type="checkbox" 
                                            className="rounded border-gray-300 text-primary focus:ring-primary shadow-sm" 
                                            checked={selectAll}
                                            onChange={(e) => setSelectAll(e.target.checked)}
                                        />
                                    </TableHead>
                                    <TableHead className="hidden sm:table-cell uppercase text-xs">ID</TableHead>
                                    <TableHead className="uppercase text-xs">Customer</TableHead>
                                    <TableHead className="uppercase text-xs">Project</TableHead>
                                    <TableHead className="uppercase text-xs">Date</TableHead>
                                    <TableHead className="uppercase text-xs">Schedule Date</TableHead>
                                    <TableHead className="text-right uppercase text-xs">Total</TableHead>
                                    <TableHead className="text-center uppercase text-xs">Job Status</TableHead>
                                    <TableHead className="text-center uppercase text-xs">Invoice Status</TableHead>
                                    <TableHead className="text-right uppercase text-xs">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="hidden sm:table-cell text-center" data-label="">
                                            <input 
                                                type="checkbox" 
                                                className="rounded border-gray-300 text-primary focus:ring-primary shadow-sm" 
                                                checked={!!selectedInvoices[invoice.id]}
                                                onChange={(e) => handleSelectInvoice(invoice.id, e.target.checked)}
                                            />
                                        </TableCell>
                                        <TableCell className="font-medium hidden sm:table-cell" data-label="ID">
                                            <Link href={route('admin.invoices.show', invoice.id)} className="text-primary hover:underline font-semibold">
                                                {invoice.invoice_number}
                                            </Link>
                                        </TableCell>
                                        <TableCell data-label="Customer">
                                            {invoice.user ? (
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedClient(invoice.user)}
                                                    className="flex items-center gap-1 font-semibold text-foreground hover:text-primary transition-colors"
                                                >
                                                    {invoice.user.name}
                                                    <ChevronDown className="h-3 w-3 text-muted-foreground hidden sm:inline-block" />
                                                </button>
                                            ) : (
                                                <span className="font-semibold text-muted-foreground">Unknown</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium text-foreground" data-label="Project">
                                            {invoice.project ? invoice.project.project_name : '-'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm" data-label="Date">
                                            {new Date(invoice.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm" data-label="Schedule Date">
                                            {invoice.scheduled_start_date ? new Date(invoice.scheduled_start_date).toLocaleDateString() : '-'}
                                        </TableCell>
                                        <TableCell className="text-right" data-label="Total">
                                            <div className="flex flex-col items-end gap-1">
                                                <span className="font-semibold text-foreground">
                                                    {formatCurrency(invoice.business_amount || invoice.amount, invoice.business_currency || invoice.currency)}
                                                </span>
                                                {invoice.status === 'partially_paid' && (
                                                    <span className="text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20">
                                                        Paid {formatCurrency(invoice.paid_amount, invoice.currency)}
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-center" data-label="Job Status">
                                            <button 
                                                type="button" 
                                                className="hover:opacity-80 transition-opacity focus:outline-none"
                                                onClick={() => {
                                                    setJobStatusDialog(invoice);
                                                    setNewJobStatus(invoice.job_status || 'pending');
                                                }}
                                            >
                                                {getJobStatusBadge(invoice.job_status)}
                                            </button>
                                        </TableCell>
                                        <TableCell className="text-center" data-label="Invoice Status">
                                            <button 
                                                type="button" 
                                                className="hover:opacity-80 transition-opacity focus:outline-none"
                                                onClick={() => {
                                                    setStatusDialog(invoice);
                                                    setNewStatus(invoice.status);
                                                }}
                                            >
                                                {getStatusBadge(invoice.status)}
                                            </button>
                                        </TableCell>
                                        <TableCell className="text-right" data-label="Actions">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">Open menu</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-56">
                                                    <DropdownMenuLabel>Invoice Actions</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('admin.invoices.show', invoice.id)} className="flex w-full items-center">
                                                            <FileText className="mr-2 h-4 w-4 text-blue-500" />
                                                            View Details
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/invoices/create?client_id=${invoice.user_id}${invoice.project_id ? `&project_id=${invoice.project_id}` : ''}`} className="flex w-full items-center">
                                                            <Plus className="mr-2 h-4 w-4 text-emerald-500" />
                                                            New Invoice
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/invoices?client_id=${invoice.user_id}`} className="flex w-full items-center">
                                                            <List className="mr-2 h-4 w-4 text-slate-500" />
                                                            All Invoices
                                                        </Link>
                                                    </DropdownMenuItem>
                                                
                                                    <DropdownMenuSeparator />
                                                
                                                    <DropdownMenuLabel>Client Reports</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <a href={`/clients/balance_sheet_print?user=${invoice.user_id}`} target="_blank" rel="noopener noreferrer" className="flex w-full items-center">
                                                            <Receipt className="mr-2 h-4 w-4 text-sky-500" />
                                                            Due Balance Sheet
                                                        </a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <a href={`/clients/timer_sheet_print?user=${invoice.user_id}`} target="_blank" rel="noopener noreferrer" className="flex w-full items-center">
                                                            <Clock className="mr-2 h-4 w-4 text-amber-500" />
                                                            Timer Balance Sheet
                                                        </a>
                                                    </DropdownMenuItem>
                                                
                                                    <DropdownMenuSeparator />
                                                
                                                    <DropdownMenuLabel>Profile & Tasks</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <a href={`/admin/users/${invoice.user_id}`} target="_blank" rel="noopener noreferrer" className="flex w-full items-center">
                                                            <User className="mr-2 h-4 w-4 text-blue-500" />
                                                            User Profile
                                                        </a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/users/${invoice.user_id}/tasks/assign`} className="flex w-full items-center">
                                                            <ClipboardList className="mr-2 h-4 w-4 text-emerald-500" />
                                                            Add Tasks
                                                        </Link>
                                                    </DropdownMenuItem>
                                                
                                                    <DropdownMenuSeparator />
                                                
                                                    {invoice.status !== 'paid' && invoice.status !== 'cancelled' && (
                                                        <DropdownMenuItem onClick={() => handleMarkPaid(invoice.id)}>
                                                            <CheckCircle className="mr-2 h-4 w-4 text-emerald-600" />
                                                            Mark as Paid
                                                        </DropdownMenuItem>
                                                    )}
                                                    {invoice.status !== 'cancelled' && (
                                                        <DropdownMenuItem onClick={() => handleCancel(invoice.id)} className="text-red-600 focus:text-red-600">
                                                            <XCircle className="mr-2 h-4 w-4" />
                                                            Cancel Invoice
                                                        </DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                
                                {(filters.client_id || filters.search === 'unpaid_partial' || filters.search === 'archived') && invoices.data.length > 0 && (
                                    <TableRow className="bg-muted/30 font-semibold border-t">
                                        <TableCell colSpan={6} className="text-right hidden sm:table-cell pr-4" data-label="Total">
                                            Total
                                        </TableCell>
                                        <TableCell className="text-right sm:hidden" data-label="Total">
                                            Total
                                        </TableCell>
                                        <TableCell className="text-right" data-label="Total Amount">
                                            {formatCurrency(
                                                invoices.data.reduce((sum, inv) => sum + (Number(inv.business_amount) || Number(inv.amount) || 0), 0),
                                                invoices.data[0]?.business_currency || invoices.data[0]?.currency || 'USD'
                                            )}
                                        </TableCell>
                                        <TableCell colSpan={3} className="hidden sm:table-cell"></TableCell>
                                    </TableRow>
                                )}

                                {invoices.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={9} className="h-24 text-center text-muted-foreground">
                                            No invoices found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* Footer Actions & Pagination */}
            <Card className="mt-4 bg-white shadow-sm">
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center">
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="w-48">
                                <Select value={bulkAction} onValueChange={(val) => setBulkAction(val)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Bulk Actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bulkActionOptions.map((opt) => (
                                            <SelectItem key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {bulkAction === 'change_project' && (
                                <div className="w-48">
                                    <PremiumCombobox
                                        value={bulkActionProject}
                                        onChange={(val) => setBulkActionProject(String(val || ''))}
                                        options={projects.map(p => ({ value: String(p.id), label: p.project_name }))}
                                        placeholder="Select Project"
                                    />
                                </div>
                            )}

                            <Button onClick={applyBulkAction} size="sm" className="h-9">
                                Apply
                            </Button>
                        </div>

                        <div className="w-full md:ml-auto md:w-auto">
                            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                                <div className="flex justify-center md:justify-end">
                                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                                        {paginationLinks.map((link, i) => (
                                            <Link
                                                key={i}
                                                href={link.url || '#'}
                                                className={`px-3 py-2 text-sm border ${
                                                    link.active 
                                                        ? 'z-10 bg-primary border-primary text-primary-foreground font-medium' 
                                                        : 'bg-background border-input text-muted-foreground hover:bg-muted'
                                                } ${i === 0 ? 'rounded-l-md' : ''} ${i === paginationLinks.length - 1 ? 'rounded-r-md' : ''}`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <ClientActionsSheet
                client={selectedClient}
                isOpen={!!selectedClient}
                onClose={() => setSelectedClient(null)}
                onLoginAs={handleLoginAs}
                onResetPassword={handleResetPassword}
            />

            <Dialog open={!!statusDialog} onOpenChange={(open) => !open && setStatusDialog(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Change Invoice Status</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>Status</Label>
                        <Select value={newStatus} onValueChange={setNewStatus}>
                            <SelectTrigger className="mt-2">
                                <SelectValue placeholder="Select Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="unpaid">Unpaid</SelectItem>
                                <SelectItem value="partially_paid">Partially Paid</SelectItem>
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setStatusDialog(null)}>Cancel</Button>
                        <Button onClick={handleChangeStatus}>Save Status</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!jobStatusDialog} onOpenChange={(open) => !open && setJobStatusDialog(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Change Job Status</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>Status</Label>
                        <Select value={newJobStatus} onValueChange={setNewJobStatus}>
                            <SelectTrigger className="mt-2">
                                <SelectValue placeholder="Select Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="processing">Processing</SelectItem>
                                <SelectItem value="done">Done</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setJobStatusDialog(null)}>Cancel</Button>
                        <Button onClick={handleChangeJobStatus}>Save Status</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
