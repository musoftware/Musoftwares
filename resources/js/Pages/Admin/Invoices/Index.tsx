import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import ClientActionsSheet from '@/Pages/Admin/Users/ClientActionsSheet';
import { MoreHorizontal, FileText, CheckCircle, XCircle, ChevronDown, Plus, List, Receipt, Clock, User, ClipboardList } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
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
import { __ } from '@/lib/i18n';
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

export default function Index({ invoices, currentTab, filters = {}, stats, projects = [] }: any) {
    const [selectedClient, setSelectedClient] = React.useState(null);
    const paginationLinks = invoices.meta?.links || invoices.links;

    const [searchTerm, setSearchTerm] = React.useState((filters as any).search || '');
    const [filterBy, setFilterBy] = React.useState((filters as any).filter_by || 'all');
    const [perPage, setPerPage] = React.useState((filters as any).per_page || '20');

    const [selectedInvoices, setSelectedInvoices] = useState({});
    const [selectAll, setSelectAll] = useState(false);
    const [bulkAction, setBulkAction] = useState('');
    const [bulkActionProject, setBulkActionProject] = useState('');
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

    const handleChangeJobStatus = () => {
        if (!jobStatusDialog || !newJobStatus) return;
        
        router.post(route('admin.invoices.change-job-status', (jobStatusDialog as any).id), {
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
                return <StatusBadge status="partially_paid" label={__('general.partially_paid')} className="bg-yellow-50 text-yellow-700 border-yellow-100" />;
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
                return <StatusBadge status="done" label={__('general.done')} className="bg-green-50 text-slate-900 border-green-100" />;
            case 'processing':
                return <StatusBadge status="processing" label={__('general.processing')} className="bg-yellow-50 text-yellow-700 border-yellow-100" />;
            default:
                return <StatusBadge status="pending" label={__('general.pending')} className="bg-slate-50 text-slate-700 border-slate-100" />;
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
        <AdminSidebarLayout title={__('general.platform_invoices')} header="Invoices Manager">
            
            {stats && (
                <div className="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">{__('general.total_invoices')}</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.total}</dd>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">{__('general.paid')}</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.paid}</dd>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">{__('general.unpaid')}</dt>
                            <dd className="mt-1 text-3xl font-semibold text-foreground">{stats.unpaid}</dd>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-5">
                            <dt className="text-sm font-medium text-muted-foreground truncate">{__('general.partially_paid')}</dt>
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
                    >{__('general.all_invoices')}</Link>
                    <Link
                        href={buildTabUrl('unpaid')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'unpaid' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >
                        {__('general.unpaid')}</Link>
                    <Link
                        href={buildTabUrl('archive')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'archive' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >{__('general.archived_cancelled')}</Link>
                </div>
            </div>

            {/* Header & Filters */}
            <Card className="mb-4 bg-white shadow-sm overflow-visible">
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 md:flex-row md:items-end">
                        {(filters as any).client_id && (
                            <Link href={`/admin/invoices?client_id=${(filters as any).client_id}${(filters as any).project_id ? `&project_id=${(filters as any).project_id}` : ''}`}>
                                <Button size="sm">
                                    <Plus className="me-2 h-4 w-4" />{__('general.add_invoice')}</Button>
                            </Link>
                        )}

                        <div className="w-full md:w-48">
                            <Label className="mb-2 block text-xs uppercase text-muted-foreground">{__('general.filter_by')}</Label>
                            <PremiumCombobox
                                value={filterBy}
                                onChange={(val) => { setFilterBy(String(val)); setTimeout(handleFilter, 50); }}
                                options={filterByOptions}
                                placeholder={__('general.select_filter')}
                            />
                        </div>

                        <div className="w-full md:w-32">
                            <Label className="mb-2 block text-xs uppercase text-muted-foreground">{__('general.show')}</Label>
                            <PremiumCombobox
                                value={perPage}
                                onChange={(val) => { setPerPage(String(val)); setTimeout(handleFilter, 50); }}
                                options={perPageOptions}
                                placeholder={__('general.per_page')}
                            />
                        </div>

                        <div className="w-full md:ms-auto md:w-64">
                            <Label className="mb-2 hidden text-xs uppercase text-muted-foreground md:block">&nbsp;</Label>
                            <Input
                                type="text"
                                className="h-9"
                                placeholder={__('general.search_invoices')}
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
                                    <TableHead className="uppercase text-xs">{__('general.customer')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.project')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.date')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.schedule_date')}</TableHead>
                                    <TableHead className="text-end uppercase text-xs">{__('general.total')}</TableHead>
                                    <TableHead className="text-center uppercase text-xs">{__('general.job_status')}</TableHead>
                                    <TableHead className="text-center uppercase text-xs">{__('general.invoice_status')}</TableHead>
                                    <TableHead className="text-end uppercase text-xs">{__('general.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(invoices.data as any).map((invoice) => (
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
                                        <TableCell data-label={__('general.customer')}>
                                            {invoice.user ? (
                                                <div className="flex items-center gap-3">
                                                    <Avatar className="h-10 w-10 border border-slate-200">
                                                        <AvatarImage src={invoice.user.avatar_url || ''} alt={invoice.user.name} />
                                                        <AvatarFallback className="bg-slate-50 text-slate-900">
                                                            <User className="h-5 w-5" />
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <button
                                                        type="button"
                                                        onClick={() => setSelectedClient(invoice.user)}
                                                        className="flex flex-col text-start group"
                                                    >
                                                        <span className="font-semibold text-slate-900 group-hover:text-slate-900 transition-colors flex items-center gap-1">
                                                            {invoice.user.name}
                                                            <ChevronDown className="h-3 w-3 text-muted-foreground hidden sm:inline-block" />
                                                        </span>
                                                        <span className="text-sm text-slate-500 font-normal">
                                                            {invoice.user.email}
                                                        </span>
                                                    </button>
                                                </div>
                                            ) : (
                                                <span className="font-semibold text-muted-foreground">{__('general.unknown')}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium text-foreground" data-label={__('general.project')}>
                                            {invoice.project ? invoice.project.project_name : '-'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm" data-label={__('general.date')}>
                                            {new Date(invoice.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm" data-label={__('general.schedule_date')}>
                                            {invoice.scheduled_start_date ? new Date(invoice.scheduled_start_date).toLocaleDateString() : '-'}
                                        </TableCell>
                                        <TableCell className="text-end" data-label={__('general.total')}>
                                            <div className="flex flex-col items-end gap-1">
                                                <span className="font-semibold text-slate-900">
                                                    {formatCurrency(invoice.amount, invoice.currency)}
                                                </span>
                                                {(invoice.business_currency && invoice.business_currency !== invoice.currency) && (
                                                    <span className="text-xs text-muted-foreground font-medium" title={__('general.business_currency')}>
                                                        ~ {formatCurrency(invoice.business_amount || invoice.amount, invoice.business_currency)}
                                                    </span>
                                                )}
                                                {invoice.status === 'partially_paid' && (
                                                    <span className="text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20 mt-1">
                                                        Paid {formatCurrency(invoice.paid_amount, invoice.currency)}
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-center" data-label={__('general.job_status')}>
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
                                        <TableCell className="text-center" data-label={__('general.invoice_status')}>
                                            <div className="inline-block">
                                                {getStatusBadge(invoice.status)}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-end" data-label={__('general.actions')}>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">{__('general.open_menu')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-56">
                                                    <DropdownMenuLabel>{__('general.invoice_actions')}</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('admin.invoices.show', invoice.id)} className="flex w-full items-center">
                                                            <FileText className="me-2 h-4 w-4 text-slate-900" />{__('general.view_details')}</Link>
                                                    </DropdownMenuItem>
                                                    
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/invoices/create?client_id=${invoice.user_id || invoice.user?.id}${(invoice.project_id || invoice.project?.id) ? `&project_id=${invoice.project_id || invoice.project?.id}` : ''}`} className="flex w-full items-center">
                                                            <Plus className="me-2 h-4 w-4 text-slate-900" />{__('general.new_invoice')}</Link>
                                                    </DropdownMenuItem>
                                                    
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/invoices?client_id=${invoice.user_id || invoice.user?.id}`} className="flex w-full items-center">
                                                            <List className="me-2 h-4 w-4 text-slate-500" />{__('general.all_invoices')}</Link>
                                                    </DropdownMenuItem>
                                                
                                                    <DropdownMenuSeparator />
                                                
                                                    <DropdownMenuLabel>{__('general.client_reports')}</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <a href={`/admin/users/${invoice.user_id || invoice.user?.id}/balance-sheet`} target="_blank" rel="noopener noreferrer" className="flex w-full items-center">
                                                            <Receipt className="me-2 h-4 w-4 text-slate-700" />{__('general.due_balance_sheet')}</a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <a href={`/admin/users/${invoice.user_id || invoice.user?.id}/reports`} target="_blank" rel="noopener noreferrer" className="flex w-full items-center">
                                                            <Clock className="me-2 h-4 w-4 text-yellow-600" />{__('general.timer_balance_sheet')}</a>
                                                    </DropdownMenuItem>
                                                
                                                    <DropdownMenuSeparator />
                                                
                                                    <DropdownMenuLabel>{__('general.profile_tasks')}</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <a href={`/admin/users/${invoice.user_id || invoice.user?.id}`} target="_blank" rel="noopener noreferrer" className="flex w-full items-center">
                                                            <User className="me-2 h-4 w-4 text-slate-900" />{__('general.user_profile')}</a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/users/${invoice.user_id || invoice.user?.id}/tasks/add`} className="flex w-full items-center">
                                                            <ClipboardList className="me-2 h-4 w-4 text-slate-900" />{__('general.add_tasks')}</Link>
                                                    </DropdownMenuItem>
                                                
                                                    <DropdownMenuSeparator />
                                                
                                                    {invoice.status !== 'paid' && invoice.status !== 'cancelled' && (
                                                        <DropdownMenuItem onClick={() => handleMarkPaid(invoice.id)}>
                                                            <CheckCircle className="me-2 h-4 w-4 text-slate-900" />{__('general.mark_as_paid')}</DropdownMenuItem>
                                                    )}
                                                    {invoice.status !== 'cancelled' && (
                                                        <DropdownMenuItem onClick={() => handleCancel(invoice.id)} className="text-red-600 focus:text-red-600">
                                                            <XCircle className="me-2 h-4 w-4" />{__('general.cancel_invoice')}</DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                
                                {((filters as any).client_id || (filters as any).search === 'unpaid_partial' || (filters as any).search === 'archived') && (invoices.data as any).length > 0 && (
                                    <TableRow className="bg-muted/30 font-semibold border-t">
                                        <TableCell colSpan={6} className="text-end hidden sm:table-cell pe-4" data-label={__('general.total')}>
                                            {__('general.total')}</TableCell>
                                        <TableCell className="text-end sm:hidden" data-label={__('general.total')}>
                                            {__('general.total')}</TableCell>
                                        <TableCell className="text-end" data-label={__('general.total_amount')}>
                                            {formatCurrency(
                                                invoices.data.reduce((sum, inv) => sum + (Number(inv.business_amount) || Number(inv.amount) || 0), 0),
                                                invoices.data[0]?.business_currency || invoices.data[0]?.currency
                                            )}
                                        </TableCell>
                                        <TableCell colSpan={3} className="hidden sm:table-cell"></TableCell>
                                    </TableRow>
                                )}

                                {(invoices.data as any).length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={9} className="h-24 text-center text-muted-foreground">{__('general.no_invoices_found')}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* Footer Actions & Pagination */}
            <Card className="mt-4 bg-white shadow-sm overflow-visible">
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center">
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="w-48">
                                <Select value={bulkAction} onValueChange={(val) => setBulkAction(val || '')}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={__('general.bulk_actions')} />
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
                                        options={(projects as any[]).map(p => ({ value: String(p.id), label: p.project_name }))}
                                        placeholder={__('general.select_project')}
                                    />
                                </div>
                            )}

                            <Button onClick={applyBulkAction} size="sm" className="h-9">
                                {__('general.apply')}</Button>
                        </div>

                        <div className="w-full md:ms-auto md:w-auto">
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
                                                } ${i === 0 ? 'rounded-s-md' : ''} ${i === paginationLinks.length - 1 ? 'rounded-e-md' : ''}`}
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
                onChangeRole={() => {}}
            />

            <Dialog open={!!jobStatusDialog} onOpenChange={(open) => !open && setJobStatusDialog(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.change_job_status')}</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>{__('general.status')}</Label>
                        <Select value={newJobStatus} onValueChange={(val) => setNewJobStatus(val || '')}>
                            <SelectTrigger className="mt-2">
                                <SelectValue placeholder={__('general.select_status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">{__('general.pending')}</SelectItem>
                                <SelectItem value="processing">{__('general.processing')}</SelectItem>
                                <SelectItem value="done">{__('general.done')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setJobStatusDialog(null)}>{__('general.cancel')}</Button>
                        <Button onClick={handleChangeJobStatus}>{__('general.save_status')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
