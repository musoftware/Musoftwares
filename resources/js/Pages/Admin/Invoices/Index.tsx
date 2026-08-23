import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import ClientActionsSheet from '@/Pages/Admin/Users/ClientActionsSheet';
import {
    MoreHorizontal,
    FileText,
    CheckCircle,
    XCircle,
    ChevronDown,
    Plus,
    List,
    Receipt,
    Clock,
    User,
    ClipboardList,
    CreditCard,
    Filter,
    RotateCcw,
    Search,
    Briefcase,
    DollarSign,
    Copy,
    MessageSquare,
    AlertCircle,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Wallet,
    ExternalLink,
} from 'lucide-react';
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
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toast } from 'sonner';

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
    { value: 'suspend', label: 'Suspend' },
    { value: 'unsuspend', label: 'Unsuspend' },
    { value: 'archive', label: 'Archive' },
    { value: 'unarchive', label: 'Unarchive' },
    { value: 'convert_to_transaction', label: 'Convert to Transaction' },
    { value: 'send_whatsapp_reminder', label: 'Send WhatsApp Reminder' },
    { value: 'delete', label: 'Delete' }
];

export default function Index({ invoices, currentTab, filters = {}, stats, projects = [], clients = [] }: any) {
    const filterByOptions = [
        { value: 'all', label: __('general.all') || 'All' },
        { value: 'id', label: 'ID' },
        { value: 'client_name', label: __('general.customer_or_username') || 'Customer / Username' },
        { value: 'item_title', label: __('general.item_title') || 'Item Title' },
        { value: 'project_name', label: __('general.project_name') || 'Project Name' },
        { value: 'date', label: __('general.date') || 'Date' },
        { value: 'status', label: __('general.invoice_status') || 'Invoice Status' },
        { value: 'unlinked', label: __('general.unlinked_projects') || 'Unlinked Projects' }
    ];

    const [selectedClient, setSelectedClient] = React.useState(null);
    const paginationLinks = invoices.meta?.links || invoices.links;

    const [clientId, setClientId] = useState<string>((filters as any).client_id ? String((filters as any).client_id) : '');
    const [projectId, setProjectId] = useState<string>((filters as any).project_id ? String((filters as any).project_id) : '');
    const [dateFrom, setDateFrom] = useState<string>((filters as any).date_from || (filters as any).from || '');
    const [dateTo, setDateTo] = useState<string>((filters as any).date_to || (filters as any).to || '');
    const [minAmount, setMinAmount] = useState<string>((filters as any).min_amount || (filters as any).amount_from || '');
    const [maxAmount, setMaxAmount] = useState<string>((filters as any).max_amount || (filters as any).amount_to || '');
    const [status, setStatus] = useState<string>((filters as any).status || 'all');
    const [jobStatus, setJobStatus] = useState<string>((filters as any).job_status || 'all');
    const [searchTerm, setSearchTerm] = useState<string>((filters as any).search || '');
    const [filterBy, setFilterBy] = useState<string>((filters as any).filter_by || 'all');
    const [perPage, setPerPage] = useState<string>((filters as any).per_page || '20');
    const [sortBy, setSortBy] = useState<string>((filters as any).sort_by || 'created_at');
    const [sortDir, setSortDir] = useState<string>((filters as any).sort_dir || 'desc');

    const [selectedInvoices, setSelectedInvoices] = useState<Record<string, boolean>>({});
    const [selectAll, setSelectAll] = useState(false);
    const [bulkAction, setBulkAction] = useState('');
    const [bulkActionProject, setBulkActionProject] = useState('');
    const [jobStatusDialog, setJobStatusDialog] = useState(null);
    const [newJobStatus, setNewJobStatus] = useState('');
    const [pendingAction, setPendingAction] = useState<{ type: 'mark_paid' | 'cancel' | 'bulk' | 'bill_balance'; id?: any } | null>(null);

    useEffect(() => {
        setClientId((filters as any).client_id ? String((filters as any).client_id) : '');
        setProjectId((filters as any).project_id ? String((filters as any).project_id) : '');
        setDateFrom((filters as any).date_from || (filters as any).from || '');
        setDateTo((filters as any).date_to || (filters as any).to || '');
        setMinAmount((filters as any).min_amount || (filters as any).amount_from || '');
        setMaxAmount((filters as any).max_amount || (filters as any).amount_to || '');
        setStatus((filters as any).status || 'all');
        setJobStatus((filters as any).job_status || 'all');
        setSearchTerm((filters as any).search || '');
        setFilterBy((filters as any).filter_by || 'all');
        setPerPage((filters as any).per_page || '20');
        setSortBy((filters as any).sort_by || 'created_at');
        setSortDir((filters as any).sort_dir || 'desc');
    }, [filters]);

    useEffect(() => {
        if (selectAll) {
            const newSelected: Record<string, boolean> = {};
            invoices.data.forEach((inv: any) => {
                newSelected[inv.id] = true;
            });
            setSelectedInvoices(newSelected);
        } else {
            setSelectedInvoices({});
        }
    }, [selectAll, invoices.data]);

    const handleSelectInvoice = (id: any, checked: boolean) => {
        setSelectedInvoices(prev => ({ ...prev, [id]: checked }));
    };

    const selectedInvoiceIds = useMemo(() => {
        return Object.keys(selectedInvoices).filter(id => selectedInvoices[id]);
    }, [selectedInvoices]);

    const selectedCount = selectedInvoiceIds.length;

    const selectedTotalAmount = useMemo(() => {
        return invoices.data
            .filter((inv: any) => selectedInvoices[inv.id])
            .reduce((sum: number, inv: any) => sum + (Number(inv.unpaid_amount) || Number(inv.amount) || 0), 0);
    }, [selectedInvoices, invoices.data]);

    const handleFilter = (overrides: Record<string, any> = {}) => {
        const queryParams: Record<string, any> = {
            client_id: clientId || undefined,
            project_id: projectId || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            min_amount: minAmount || undefined,
            max_amount: maxAmount || undefined,
            status: (status && status !== 'all') ? status : undefined,
            job_status: (jobStatus && jobStatus !== 'all') ? jobStatus : undefined,
            search: searchTerm || undefined,
            filter_by: (filterBy && filterBy !== 'all') ? filterBy : undefined,
            per_page: perPage !== '20' ? perPage : undefined,
            sort_by: sortBy !== 'created_at' ? sortBy : undefined,
            sort_dir: sortDir !== 'desc' ? sortDir : undefined,
            ...overrides,
        };

        const cleanParams: Record<string, any> = {};
        Object.keys(queryParams).forEach((k) => {
            if (queryParams[k] !== undefined && queryParams[k] !== '' && queryParams[k] !== 'all') {
                cleanParams[k] = queryParams[k];
            }
        });

        router.get(
            route(`admin.invoices.${currentTab === 'all' ? 'index' : currentTab}`),
            cleanParams,
            { preserveState: true }
        );
    };

    const handleSort = (column: string) => {
        let nextDir = 'asc';
        if (sortBy === column) {
            nextDir = sortDir === 'asc' ? 'desc' : 'asc';
        }
        setSortBy(column);
        setSortDir(nextDir);
        handleFilter({ sort_by: column, sort_dir: nextDir });
    };

    const renderSortHeader = (title: string, column: string, className = '') => {
        const isActive = sortBy === column;
        return (
            <button
                type="button"
                onClick={() => handleSort(column)}
                className={`flex items-center gap-1 uppercase text-xs font-semibold hover:text-slate-900 transition-colors group ${className}`}
            >
                <span>{title}</span>
                {isActive ? (
                    sortDir === 'asc' ? (
                        <ArrowUp className="h-3.5 w-3.5 text-primary" />
                    ) : (
                        <ArrowDown className="h-3.5 w-3.5 text-primary" />
                    )
                ) : (
                    <ArrowUpDown className="h-3 w-3 opacity-30 group-hover:opacity-100 transition-opacity" />
                )}
            </button>
        );
    };

    const handleClearFilters = () => {
        setClientId('');
        setProjectId('');
        setDateFrom('');
        setDateTo('');
        setMinAmount('');
        setMaxAmount('');
        setStatus('all');
        setJobStatus('all');
        setSearchTerm('');
        setFilterBy('all');
        setPerPage('20');
        setSortBy('created_at');
        setSortDir('desc');

        router.get(
            route(`admin.invoices.${currentTab === 'all' ? 'index' : currentTab}`),
            {},
            { preserveState: true }
        );
    };

    const hasActiveFilters = Boolean(
        clientId ||
        projectId ||
        dateFrom ||
        dateTo ||
        minAmount ||
        maxAmount ||
        (status && status !== 'all') ||
        (jobStatus && jobStatus !== 'all') ||
        searchTerm ||
        (filterBy && filterBy !== 'all') ||
        (perPage && perPage !== '20') ||
        (sortBy && sortBy !== 'created_at')
    );

    const availableProjects = clientId
        ? (projects as any[]).filter((p: any) => String(p.user_id) === String(clientId))
        : (projects as any[]);

    const clientOptions = [
        { value: '', label: __('general.all_clients') || 'All Clients' },
        ...(clients as any[]).map((c: any) => ({
            value: String(c.id),
            label: `${c.name}${c.email ? ` (${c.email})` : ''}`,
        })),
    ];

    const projectOptions = [
        { value: '', label: __('general.all_projects') || 'All Projects' },
        ...availableProjects.map((p: any) => ({
            value: String(p.id),
            label: p.project_name,
        })),
    ];

    const statusOptions = currentTab === 'unpaid'
        ? [
            { value: 'all', label: __('admin.all_unpaid_partial') || 'All Unpaid & Partial' },
            { value: 'unpaid', label: __('admin.unpaid_only') || 'Unpaid Only' },
            { value: 'partially_paid', label: __('admin.partially_paid_only') || 'Partially Paid Only' },
        ]
        : [
            { value: 'all', label: __('admin.all_statuses') || __('general.all_statuses') || 'All Statuses' },
            { value: 'unpaid', label: __('general.unpaid') || 'Unpaid' },
            { value: 'partially_paid', label: __('general.partially_paid') || 'Partially Paid' },
            { value: 'paid', label: __('general.paid') || 'Paid' },
            { value: 'cancelled', label: __('general.archived_cancelled') || 'Cancelled / Archived' },
        ];

    const jobStatusOptions = [
        { value: 'all', label: __('general.all_job_statuses') || 'All Job Statuses' },
        { value: 'pending', label: __('general.pending') || 'Pending' },
        { value: 'processing', label: __('general.processing') || 'Processing' },
        { value: 'done', label: __('general.done') || 'Done' },
    ];

    const handleLoginAs = (id: any) => {
        router.post(route('admin.users.login-as', id));
    };

    const handleResetPassword = (id: any) => {
        router.post(route('admin.users.reset-password', id));
    };

    const handleMarkPaid = (id: any) => setPendingAction({ type: 'mark_paid', id });
    const handleBillBalance = (id: any) => setPendingAction({ type: 'bill_balance', id });

    const handleCopyLink = (invoice: any, e?: React.MouseEvent) => {
        e?.stopPropagation();
        if (invoice.public_url) {
            navigator.clipboard.writeText(invoice.public_url);
            toast.success(__('admin.invoice_link_copied') || 'Invoice link copied to clipboard');
        }
    };

    const handleWhatsAppReminder = (invoice: any, e?: React.MouseEvent) => {
        e?.stopPropagation();
        const phone = invoice.user?.phone_number || invoice.user?.phone;
        if (!phone) {
            toast.error(__('general.no_phone_number') || 'Client has no phone number recorded');
            return;
        }
        const cleanPhone = phone.replace(/[^0-9]/g, '');
        const amountText = invoice.unpaid_amount_str || formatCurrency(invoice.unpaid_amount || invoice.amount, invoice.currency);
        const text = encodeURIComponent(
            `مرحباً ${invoice.user?.name || ''}، تذكير بفاتورتكم #${invoice.invoice_number} بمبلغ متبقي ${amountText}. رابط الفاتورة: ${invoice.public_url}`
        );
        window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank');
    };

    const confirmMarkPaid = () => {
        if (!pendingAction || pendingAction.type !== 'mark_paid') return;
        router.post(route('admin.invoices.external-pay', pendingAction.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.invoice_marked_paid') || 'Invoice marked as paid');
                setPendingAction(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingAction(null);
            },
        });
    };

    const confirmBillBalance = () => {
        if (!pendingAction || pendingAction.type !== 'bill_balance') return;
        router.post(route('admin.invoices.mark-paid', pendingAction.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.invoice_marked_paid') || 'Invoice marked as paid');
                setPendingAction(null);
            },
            onError: (errors: any) => {
                const errMsg = errors?.message || Object.values(errors).join(', ') || __('general.error_occurred');
                toast.error(errMsg);
                setPendingAction(null);
            },
        });
    };

    const handleCancel = (id: any) => setPendingAction({ type: 'cancel', id });

    const handleToggleSuspend = (id: any) => {
        router.post(route('admin.invoices.toggle-suspend', id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('admin.invoice_updated') || 'Invoice updated');
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
            },
        });
    };

    const confirmCancel = () => {
        if (!pendingAction || pendingAction.type !== 'cancel') return;
        router.post(route('admin.invoices.cancel', pendingAction.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.invoice_cancelled') || 'Invoice cancelled');
                setPendingAction(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingAction(null);
            },
        });
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
        if (selectedInvoiceIds.length === 0) {
            toast.error(__('general.select_at_least_one_invoice') || 'Please select at least one invoice.');
            return;
        }
        if (!bulkAction) {
            toast.error(__('general.select_bulk_action_first') || 'Select a bulk action first.');
            return;
        }

        const messages: Record<string, string> = {
            convert_to_transaction: __('general.confirm_convert_to_transactions') || 'Convert these invoices to transactions?',
            delete: __('general.confirm_delete_invoices') || 'Permanently delete the selected invoices?',
            send_whatsapp_reminder: __('general.confirm_send_whatsapp_reminders') || 'Send WhatsApp reminders for the selected invoices?',
            suspend: __('admin.confirm_suspend_invoices') || 'Are you sure you want to suspend the selected invoices?',
            unsuspend: __('admin.confirm_unsuspend_invoices') || 'Are you sure you want to unsuspend the selected invoices?',
            bill_invoice: __('general.confirm_bill_balance') || 'Bill selected invoices from client balance?',
        };

        setPendingAction({ type: 'bulk', id: { action: bulkAction, ids: selectedInvoiceIds, projectId: bulkActionProject, message: messages[bulkAction] || __('general.confirm_bulk_action') || 'Apply this bulk action?' } });
    };

    const confirmBulkAction = () => {
        if (!pendingAction || pendingAction.type !== 'bulk') return;
        const { action, ids, projectId } = pendingAction.id;
        router.post(route('admin.invoices.bulk-action'), {
            action,
            invoices: ids,
            project_id: projectId
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.bulk_action_applied') || 'Bulk action applied');
                setSelectedInvoices({});
                setSelectAll(false);
                setBulkAction('');
                setBulkActionProject('');
                setPendingAction(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingAction(null);
            },
        });
    };

    const getStatusBadge = (invoiceStatus: string) => {
        switch (invoiceStatus) {
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

    const getJobStatusBadge = (statusValue: string) => {
        switch (statusValue) {
            case 'done':
                return <StatusBadge status="done" label={__('general.done')} className="bg-green-50 text-slate-900 border-green-100" />;
            case 'processing':
                return <StatusBadge status="processing" label={__('general.processing')} className="bg-yellow-50 text-yellow-700 border-yellow-100" />;
            default:
                return <StatusBadge status="pending" label={__('general.pending')} className="bg-slate-50 text-slate-700 border-slate-100" />;
        }
    };

    const buildTabUrl = (tab: string) => {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('page'); 
        const queryString = urlParams.toString();
        const routeName = tab === 'all' ? 'index' : tab;
        return `${route(`admin.invoices.${routeName}`)}${queryString ? `?${queryString}` : ''}`;
    };

    return (
        <AdminSidebarLayout title={__('general.platform_invoices')} header="Invoices Manager">
            
            {/* Always-on Stats Cards */}
            {stats && (
                <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <Card className="border-slate-200 shadow-sm">
                        <CardContent className="p-4">
                            <dt className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.total_invoices')}</dt>
                            <dd className="mt-1 text-2xl font-bold text-slate-900">{stats.total}</dd>
                        </CardContent>
                    </Card>
                    <Card className="border-red-100 bg-red-50/20 shadow-sm">
                        <CardContent className="p-4">
                            <dt className="text-xs font-semibold text-red-700 uppercase tracking-wider">{__('general.unpaid')}</dt>
                            <dd className="mt-1 text-2xl font-bold text-red-700">{stats.unpaid}</dd>
                        </CardContent>
                    </Card>
                    <Card className="border-amber-200 bg-amber-50/30 shadow-sm">
                        <CardContent className="p-4">
                            <dt className="text-xs font-semibold text-amber-800 uppercase tracking-wider">{__('admin.total_outstanding_amount') || 'Total Outstanding'}</dt>
                            <dd className="mt-1 text-2xl font-bold text-amber-900">
                                {stats.total_unpaid_amount_str || stats.total_unpaid_amount || '0.00'}
                            </dd>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200 shadow-sm">
                        <CardContent className="p-4">
                            <dt className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.partially_paid')}</dt>
                            <dd className="mt-1 text-2xl font-bold text-slate-900">{stats.partially_paid}</dd>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200 shadow-sm">
                        <CardContent className="p-4">
                            <dt className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('admin.suspended_invoices') || 'Suspended Invoices'}</dt>
                            <dd className="mt-1 text-2xl font-bold text-slate-900">{stats.suspended || 0}</dd>
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
                        href={buildTabUrl('suspended')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'suspended' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >
                        {__('admin.suspended_invoices') || 'Suspended Invoices'}</Link>
                    <Link
                        href={buildTabUrl('archive')}
                        className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'archive' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                    >{__('general.archived_cancelled')}</Link>
                </div>
            </div>

            {/* Header & Filters */}
            <Card className="mb-4 bg-white shadow-sm overflow-visible border border-slate-200">
                <CardContent className="p-4 space-y-3">
                    {/* Primary Filters Row */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {/* Client Selector */}
                        <div>
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('general.customer') || 'Client'}</Label>
                            <PremiumCombobox
                                value={clientId}
                                onChange={(val) => {
                                    const nextClientId = String(val || '');
                                    setClientId(nextClientId);
                                    let nextProjectId = projectId;
                                    if (nextClientId && projectId) {
                                        const proj = (projects as any[]).find(p => String(p.id) === String(projectId));
                                        if (proj && String(proj.user_id) !== nextClientId) {
                                            nextProjectId = '';
                                            setProjectId('');
                                        }
                                    }
                                    setTimeout(() => handleFilter({ client_id: nextClientId, project_id: nextProjectId }), 50);
                                }}
                                options={clientOptions}
                                placeholder={__('general.all_clients') || 'All Clients'}
                                searchPlaceholder={__('general.search') || 'Search clients...'}
                            />
                        </div>

                        {/* Project Selector */}
                        <div>
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('general.project') || 'Project'}</Label>
                            <PremiumCombobox
                                value={projectId}
                                onChange={(val) => {
                                    const nextProjectId = String(val || '');
                                    setProjectId(nextProjectId);
                                    setTimeout(() => handleFilter({ project_id: nextProjectId }), 50);
                                }}
                                options={projectOptions}
                                placeholder={__('general.all_projects') || 'All Projects'}
                                searchPlaceholder={__('general.search') || 'Search projects...'}
                            />
                        </div>

                        {/* Status Selector */}
                        <div>
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('general.invoice_status') || 'Status'}</Label>
                            <PremiumCombobox
                                value={status}
                                onChange={(val) => {
                                    const nextStatus = String(val || 'all');
                                    setStatus(nextStatus);
                                    setTimeout(() => handleFilter({ status: nextStatus }), 50);
                                }}
                                options={statusOptions}
                                placeholder={__('admin.all_statuses') || 'All Statuses'}
                            />
                        </div>

                        {/* Job Status Selector */}
                        <div>
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('general.job_status') || 'Job Status'}</Label>
                            <PremiumCombobox
                                value={jobStatus}
                                onChange={(val) => {
                                    const nextJobStatus = String(val || 'all');
                                    setJobStatus(nextJobStatus);
                                    setTimeout(() => handleFilter({ job_status: nextJobStatus }), 50);
                                }}
                                options={jobStatusOptions}
                                placeholder={__('general.all_job_statuses') || 'All Job Statuses'}
                            />
                        </div>
                    </div>

                    {/* Secondary Filters Row: Date Range & Amount Range */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end pt-1">
                        {/* Date Range: From */}
                        <div>
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('admin.date_from') || 'Date From'}</Label>
                            <Input
                                type="date"
                                className="h-9 text-xs"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            />
                        </div>

                        {/* Date Range: To */}
                        <div>
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('admin.date_to') || 'Date To'}</Label>
                            <Input
                                type="date"
                                className="h-9 text-xs"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            />
                        </div>

                        {/* Amount Range: Min & Max */}
                        <div className="lg:col-span-2">
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('general.amount') || 'Amount Range'}</Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    type="number"
                                    step="any"
                                    placeholder={__('general.min') || 'Min'}
                                    className="h-9 text-xs"
                                    value={minAmount}
                                    onChange={(e) => setMinAmount(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                />
                                <span className="text-muted-foreground text-xs font-medium">-</span>
                                <Input
                                    type="number"
                                    step="any"
                                    placeholder={__('general.max') || 'Max'}
                                    className="h-9 text-xs"
                                    value={maxAmount}
                                    onChange={(e) => setMaxAmount(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Third Filters Row: Search & Actions */}
                    <div className="flex flex-col sm:flex-row items-stretch sm:items-end justify-between gap-3 pt-1">
                        {/* Filter by field + Search */}
                        <div className="flex-1 max-w-xl">
                            <Label className="mb-1.5 block text-xs font-semibold text-slate-700">{__('general.search') || 'Search'}</Label>
                            <div className="flex items-center gap-2">
                                <div className="w-36 sm:w-44 flex-shrink-0">
                                    <PremiumCombobox
                                        value={filterBy}
                                        onChange={(val) => { setFilterBy(String(val)); setTimeout(() => handleFilter({ filter_by: String(val) }), 50); }}
                                        options={filterByOptions}
                                        placeholder={__('general.select_filter')}
                                    />
                                </div>
                                <div className="relative flex-1">
                                    <Input
                                        type="text"
                                        className="h-9 text-xs pe-7"
                                        placeholder={__('general.search_invoices') || 'Search...'}
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                handleFilter();
                                            }
                                        }}
                                    />
                                    {searchTerm && (
                                        <button
                                            type="button"
                                            onClick={() => { setSearchTerm(''); setTimeout(() => handleFilter({ search: '' }), 50); }}
                                            className="absolute end-2 top-2.5 text-muted-foreground hover:text-foreground"
                                        >
                                            <XCircle className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Actions: Per Page, Filter, Reset & Create */}
                        <div className="flex flex-wrap items-center gap-2 justify-end sm:self-end">
                            <div className="w-20">
                                <PremiumCombobox
                                    value={perPage}
                                    onChange={(val) => { setPerPage(String(val)); setTimeout(() => handleFilter({ per_page: String(val) }), 50); }}
                                    options={perPageOptions}
                                    placeholder={__('general.per_page')}
                                />
                            </div>

                            <Button size="sm" className="h-9 px-3.5" onClick={() => handleFilter()} title={__('general.filter') || 'Filter'}>
                                <Filter className="h-4 w-4 me-1.5" />
                                {__('general.filter') || 'Filter'}
                            </Button>

                            {hasActiveFilters && (
                                <Button variant="outline" size="sm" className="h-9 px-2.5 text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200" onClick={handleClearFilters} title={__('general.clear_filters') || 'Clear Filters'}>
                                    <RotateCcw className="h-4 w-4" />
                                </Button>
                            )}

                            <Link href={`/admin/invoices/create${clientId ? `?client_id=${clientId}${projectId ? `&project_id=${projectId}` : ''}` : ''}`}>
                                <Button size="sm" variant="secondary" className="h-9 px-3.5 whitespace-nowrap" title={__('general.add_invoice')}>
                                    <Plus className="h-4 w-4 me-1" />
                                    {__('general.add_invoice') || 'Add Invoice'}
                                </Button>
                            </Link>
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
                                    <TableHead className="hidden sm:table-cell">
                                        {renderSortHeader('ID', 'id')}
                                    </TableHead>
                                    <TableHead>
                                        <span className="uppercase text-xs font-semibold">{__('general.customer')}</span>
                                    </TableHead>
                                    <TableHead>
                                        <span className="uppercase text-xs font-semibold">{__('general.project')}</span>
                                    </TableHead>
                                    <TableHead>
                                        {renderSortHeader(__('general.date') || 'Date', 'created_at')}
                                    </TableHead>
                                    <TableHead>
                                        {renderSortHeader(__('admin.due_date') || 'Due Date', 'due_date')}
                                    </TableHead>
                                    <TableHead className="text-end">
                                        {renderSortHeader(__('general.total') || 'Total', 'amount', 'justify-end')}
                                    </TableHead>
                                    <TableHead className="text-center">
                                        <span className="uppercase text-xs font-semibold">{__('general.job_status')}</span>
                                    </TableHead>
                                    <TableHead className="text-center">
                                        {renderSortHeader(__('general.invoice_status') || 'Status', 'status', 'justify-center')}
                                    </TableHead>
                                    <TableHead className="text-end">
                                        <span className="uppercase text-xs font-semibold">{__('general.actions')}</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(invoices.data as any).map((invoice: any) => (
                                    <TableRow key={invoice.id} className="hover:bg-slate-50/70 transition-colors">
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
                                                    <Avatar className="h-10 w-10 border border-slate-200 flex-shrink-0">
                                                        <AvatarImage src={invoice.user.avatar_url || ''} alt={invoice.user.name} />
                                                        <AvatarFallback className="bg-slate-50 text-slate-900 font-semibold">
                                                            <User className="h-5 w-5" />
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="flex flex-col text-start">
                                                        <button
                                                            type="button"
                                                            onClick={() => setSelectedClient(invoice.user)}
                                                            className="font-semibold text-slate-900 hover:text-primary transition-colors flex items-center gap-1 text-start"
                                                        >
                                                            <span>{invoice.user.name}</span>
                                                            <ChevronDown className="h-3 w-3 text-muted-foreground hidden sm:inline-block" />
                                                        </button>
                                                        <span className="text-xs text-slate-500 font-normal">
                                                            {invoice.user.email}
                                                        </span>
                                                        {Number(invoice.user.balance) > 0 && (
                                                            <div className="mt-1">
                                                                <span 
                                                                    className="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded shadow-xs" 
                                                                    title={__('admin.sufficient_balance') || 'Client Wallet Balance'}
                                                                >
                                                                    <Wallet className="h-2.5 w-2.5" />
                                                                    {__('admin.client_wallet') || 'Wallet'}: {invoice.user.balance_str || formatCurrency(invoice.user.balance, invoice.currency)}
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="font-semibold text-muted-foreground">{__('general.unknown')}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium text-foreground" data-label={__('general.project')}>
                                            {invoice.project ? (
                                                <Link
                                                    href={route('admin.projects.board.index', invoice.project.id)}
                                                    className="text-primary hover:underline font-semibold text-sm"
                                                >
                                                    {invoice.project.project_name}
                                                </Link>
                                            ) : (
                                                <span className="text-slate-400 text-sm">-</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs whitespace-nowrap" data-label={__('general.date')}>
                                            {new Date(invoice.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-sm whitespace-nowrap" data-label={__('admin.due_date')}>
                                            {invoice.due_date ? (
                                                <div className="flex flex-col items-start gap-0.5">
                                                    <span className="text-xs text-slate-700">{new Date(invoice.due_date).toLocaleDateString()}</span>
                                                    {invoice.is_overdue && (
                                                        <span className="inline-flex items-center gap-1 text-[10px] font-bold text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">
                                                            <AlertCircle className="h-2.5 w-2.5" />
                                                            {invoice.days_overdue > 0 
                                                                ? (__('admin.overdue_by_days', { days: invoice.days_overdue }) || `Overdue ${invoice.days_overdue}d`)
                                                                : (__('admin.overdue') || 'Overdue')}
                                                        </span>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-slate-400 text-xs">-</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-end" data-label={__('general.total')}>
                                            <div className="flex flex-col items-end gap-0.5">
                                                <span className="font-semibold text-slate-900 text-sm">
                                                    {formatCurrency(invoice.amount, invoice.currency)}
                                                </span>
                                                
                                                {(invoice.status === 'unpaid' || invoice.status === 'partially_paid') && (
                                                    <span className="text-[11px] font-bold text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">
                                                        {__('admin.remaining_due') || 'Due'}: {invoice.unpaid_amount_str || formatCurrency(invoice.unpaid_amount, invoice.currency)}
                                                    </span>
                                                )}

                                                {(invoice.business_currency && invoice.business_currency !== invoice.currency) && (
                                                    <span className="text-[10px] text-muted-foreground font-medium" title={__('general.business_currency')}>
                                                        ~ {formatCurrency(invoice.business_amount || invoice.amount, invoice.business_currency)}
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
                                            <div className="flex flex-col items-center gap-1 justify-center">
                                                {getStatusBadge(invoice.status)}
                                                {invoice.is_suspended && (
                                                    <span className="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded uppercase leading-none">
                                                        {__('admin.suspended') || 'Suspended'}
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-end" data-label={__('general.actions')}>
                                            <div className="flex items-center justify-end gap-1">
                                                {/* 1-Click Quick Bill from Wallet if sufficient balance */}
                                                {(invoice.status === 'unpaid' || invoice.status === 'partially_paid') && 
                                                 !invoice.is_suspended && 
                                                 Number(invoice.user?.balance) >= Number(invoice.unpaid_amount) && 
                                                 Number(invoice.unpaid_amount) > 0 && (
                                                    <Button
                                                        size="icon"
                                                        variant="outline"
                                                        className="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 border-emerald-200"
                                                        title={__('admin.bill_now') || 'Bill from Wallet Balance'}
                                                        onClick={(e) => { e.stopPropagation(); handleBillBalance(invoice.id); }}
                                                    >
                                                        <CreditCard className="h-3.5 w-3.5" />
                                                    </Button>
                                                )}

                                                {/* 1-Click Quick WhatsApp Reminder */}
                                                {(invoice.status === 'unpaid' || invoice.status === 'partially_paid') && (
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        className="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50"
                                                        title={__('admin.send_whatsapp') || 'Send WhatsApp Reminder'}
                                                        onClick={(e) => handleWhatsAppReminder(invoice, e)}
                                                    >
                                                        <MessageSquare className="h-3.5 w-3.5" />
                                                    </Button>
                                                )}

                                                {/* 1-Click Copy Public URL */}
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className="h-8 w-8 text-slate-500 hover:text-slate-900"
                                                    title={__('admin.copy_invoice_link') || 'Copy Public Invoice Link'}
                                                    onClick={(e) => handleCopyLink(invoice, e)}
                                                >
                                                    <Copy className="h-3.5 w-3.5" />
                                                </Button>

                                                {/* Full Dropdown Menu */}
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

                                                        {invoice.public_url && (
                                                            <DropdownMenuItem onClick={() => window.open(invoice.public_url, '_blank')}>
                                                                <ExternalLink className="me-2 h-4 w-4 text-slate-700" />
                                                                {__('admin.view_link') || 'Open Public Link'}
                                                            </DropdownMenuItem>
                                                        )}
                                                        
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
                                                            <>
                                                                <DropdownMenuItem onClick={() => handleToggleSuspend(invoice.id)}>
                                                                    {invoice.is_suspended ? (
                                                                        <>
                                                                            <CheckCircle className="me-2 h-4 w-4 text-slate-900" />{__('admin.unsuspend') || 'Unsuspend'}
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <XCircle className="me-2 h-4 w-4 text-slate-900" />{__('admin.suspend') || 'Suspend'}
                                                                        </>
                                                                    )}
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => handleMarkPaid(invoice.id)}>
                                                                    <CheckCircle className="me-2 h-4 w-4 text-slate-900" />{__('general.mark_as_paid')}</DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => handleBillBalance(invoice.id)}>
                                                                    <CreditCard className="me-2 h-4 w-4 text-slate-900" />{__('general.bill_from_balance')}</DropdownMenuItem>
                                                            </>
                                                        )}
                                                        {invoice.status !== 'cancelled' && (
                                                            <DropdownMenuItem onClick={() => handleCancel(invoice.id)} className="text-red-600 focus:text-red-600">
                                                                <XCircle className="me-2 h-4 w-4" />{__('general.cancel_invoice')}</DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                
                                {/* Always-Visible Summary Footer Row */}
                                {(invoices.data as any).length > 0 && (
                                    <TableRow className="bg-slate-50 font-bold border-t border-slate-200">
                                        <TableCell colSpan={6} className="text-end hidden sm:table-cell pe-4 text-xs uppercase text-slate-600">
                                            {__('general.total')} ({__('general.page') || 'Page'})
                                        </TableCell>
                                        <TableCell className="text-end sm:hidden text-xs uppercase text-slate-600">
                                            {__('general.total')}
                                        </TableCell>
                                        <TableCell className="text-end font-bold text-slate-900 text-sm">
                                            <div className="flex flex-col items-end">
                                                <span>
                                                    {formatCurrency(
                                                        invoices.data.reduce((sum: number, inv: any) => sum + (Number(inv.business_amount) || Number(inv.amount) || 0), 0),
                                                        invoices.data[0]?.business_currency || invoices.data[0]?.currency
                                                    )}
                                                </span>
                                                <span className="text-xs text-red-600 font-bold">
                                                    {__('admin.remaining_due') || 'Due'}: {formatCurrency(
                                                        invoices.data.reduce((sum: number, inv: any) => sum + (Number(inv.unpaid_amount) || 0), 0),
                                                        invoices.data[0]?.business_currency || invoices.data[0]?.currency
                                                    )}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell colSpan={3} className="hidden sm:table-cell"></TableCell>
                                    </TableRow>
                                )}

                                {(invoices.data as any).length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={10} className="h-24 text-center text-muted-foreground">{__('general.no_invoices_found')}</TableCell>
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
                                {__('general.apply')}
                            </Button>
                        </div>

                        <div className="w-full md:ms-auto md:w-auto">
                            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                                <div className="flex justify-center md:justify-end">
                                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                                        {paginationLinks.map((link: any, i: number) => (
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

            {/* Floating Sticky Bulk Action Bar */}
            {selectedCount > 0 && (
                <div className="fixed bottom-6 start-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-slate-900 text-white px-5 py-3 rounded-full shadow-2xl border border-slate-700 animate-in fade-in slide-in-from-bottom-5">
                    <span className="text-xs font-semibold whitespace-nowrap">
                        {__('admin.selected_invoices_count', { count: selectedCount }) || `${selectedCount} invoices selected`}
                    </span>
                    <span className="text-xs text-slate-300 font-medium whitespace-nowrap hidden sm:inline">
                        ({__('admin.selected_total_amount') || 'Total:'} {formatCurrency(selectedTotalAmount, invoices.data[0]?.business_currency || invoices.data[0]?.currency)})
                    </span>
                    <div className="h-4 w-px bg-slate-700 mx-1 hidden sm:block" />
                    <div className="w-40 sm:w-48 text-slate-900">
                        <Select value={bulkAction} onValueChange={(val) => setBulkAction(val || '')}>
                            <SelectTrigger className="h-8 text-xs bg-white text-slate-900 border-0">
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
                        <div className="w-36 sm:w-44 text-slate-900">
                            <PremiumCombobox
                                value={bulkActionProject}
                                onChange={(val) => setBulkActionProject(String(val || ''))}
                                options={(projects as any[]).map(p => ({ value: String(p.id), label: p.project_name }))}
                                placeholder={__('general.select_project')}
                            />
                        </div>
                    )}
                    <Button onClick={applyBulkAction} size="sm" className="h-8 px-4 text-xs font-semibold bg-primary hover:bg-primary/90 text-primary-foreground">
                        {__('general.apply')}
                    </Button>
                    <button 
                        type="button" 
                        onClick={() => { setSelectedInvoices({}); setSelectAll(false); }} 
                        className="text-xs text-slate-400 hover:text-white underline ms-1"
                    >
                        {__('admin.clear_selection') || 'Clear'}
                    </button>
                </div>
            )}

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

            <ConfirmModal
                isOpen={pendingAction?.type === 'mark_paid'}
                title={__('general.mark_as_paid') || 'Mark as paid?'}
                description={__('general.confirm_mark_paid_desc') || 'This will adjust balances directly.'}
                confirmLabel={__('general.mark_as_paid')}
                cancelLabel={__('general.cancel')}
                onConfirm={confirmMarkPaid}
                onCancel={() => setPendingAction(null)}
            />

            <ConfirmModal
                isOpen={pendingAction?.type === 'bill_balance'}
                title={__('general.bill_from_balance') || 'Bill from balance?'}
                description={__('general.confirm_bill_balance') || 'Are you sure you want to bill this invoice from the client\'s balance?'}
                confirmLabel={__('general.bill_from_balance') || 'Bill from Balance'}
                cancelLabel={__('general.cancel')}
                onConfirm={confirmBillBalance}
                onCancel={() => setPendingAction(null)}
            />

            <ConfirmModal
                isOpen={pendingAction?.type === 'cancel'}
                title={__('general.cancel_invoice') || 'Cancel invoice?'}
                description={__('general.confirm_cancel_invoice_desc') || 'If it was partially paid, the user will be refunded their wallet balance.'}
                confirmLabel={__('general.cancel_invoice')}
                cancelLabel={__('general.keep_invoice')}
                variant="danger"
                onConfirm={confirmCancel}
                onCancel={() => setPendingAction(null)}
            />

            <ConfirmModal
                isOpen={pendingAction?.type === 'bulk'}
                title={__('general.confirm_bulk_action_title') || 'Confirm bulk action'}
                description={pendingAction?.type === 'bulk' ? pendingAction.id?.message : ''}
                confirmLabel={__('general.apply')}
                cancelLabel={__('general.cancel')}
                variant={pendingAction?.type === 'bulk' && pendingAction.id?.action === 'delete' ? 'danger' : 'default'}
                onConfirm={confirmBulkAction}
                onCancel={() => setPendingAction(null)}
            />
        </AdminSidebarLayout>
    );
}
