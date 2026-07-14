import React, { useState, useMemo, useEffect } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { formatMoney, cn } from '@/lib/utils';
import {
    MoreHorizontal, Plus, Copy, Trash, Search, FilterX, ExternalLink,
    DollarSign, Clock, Eye, CheckCircle, XCircle, CalendarOff, Pencil,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/Components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { Checkbox } from '@/Components/ui/checkbox';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Input } from '@/Components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface PaymentLink {
    id: number;
    uuid: string;
    title: string;
    description?: string | null;
    amount: string | number;
    status: 'pending' | 'paid' | 'cancelled' | 'expired';
    currency?: any;
    user?: { id: number; name: string } | null;
    client?: { id: number; name: string } | null;
    paid_at?: string | null;
    expires_at?: string | null;
    cancelled_at?: string | null;
    created_at: string;
}

interface Filters {
    search?: string;
    status?: string;
    currency_id?: string | number;
    date_from?: string;
    date_to?: string;
    per_page?: number;
}

export default function Index({
    paymentLinks,
    currencies,
    clients = [],
    filters = {},
    stats,
    canForceMarkPaid = false,
}: {
    paymentLinks: any;
    currencies: any[];
    clients?: any[];
    filters?: Filters;
    stats?: { total: number; paid: number; pending: number; cancelled: number; expired: number };
    canForceMarkPaid?: boolean;
}) {
    const createForm = useForm({
        title: '',
        description: '',
        amount: '',
        currency_id: '',
        client_id: '',
        expires_at: '',
    });

    const editForm = useForm({
        title: '',
        description: '',
        amount: '',
        currency_id: '',
        expires_at: '',
    });

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [editing, setEditing] = useState<PaymentLink | null>(null);
    const [pendingDelete, setPendingDelete] = useState<PaymentLink | null>(null);
    const [pendingCancel, setPendingCancel] = useState<PaymentLink | null>(null);
    const [pendingMarkPaid, setPendingMarkPaid] = useState<PaymentLink | null>(null);
    const [pendingBulkDelete, setPendingBulkDelete] = useState(false);
    const [selected, setSelected] = useState<number[]>([]);
    const [localSearch, setLocalSearch] = useState<string>(filters.search ?? '');

    useEffect(() => {
        const timer = setTimeout(() => {
            if ((filters.search ?? '') !== localSearch) {
                applyFilters({ search: localSearch });
            }
        }, 350);
        return () => clearTimeout(timer);
    }, [localSearch]);

    function applyFilters(partial: Partial<Filters>) {
        router.get(
            route('admin.payment-links.index'),
            { ...filters, ...partial, page: 1 },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }

    const filteredData: PaymentLink[] = useMemo(() => {
        return (paymentLinks?.data ?? []) as PaymentLink[];
    }, [paymentLinks]);

    const totalSelected = selected.length;
    const allOnPageSelected = filteredData.length > 0 && filteredData.every((l) => selected.includes(l.id));

    function toggleSelectAll() {
        if (allOnPageSelected) {
            setSelected(selected.filter((id) => !filteredData.some((l) => l.id === id)));
        } else {
            setSelected(Array.from(new Set([...selected, ...filteredData.map((l) => l.id)])));
        }
    }

    function toggleOne(id: number) {
        setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    }

    function openCreate() {
        createForm.reset();
        createForm.clearErrors();
        setIsCreateModalOpen(true);
    }

    function handleCreate(e: React.FormEvent) {
        e.preventDefault();
        createForm.post(route('admin.payment-links.store'), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                createForm.reset();
                toast.success(__('admin.payment_link_created') || 'Payment link created');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    }

    function openEdit(link: PaymentLink) {
        editForm.setData({
            title: link.title,
            description: link.description ?? '',
            amount: String(link.amount),
            currency_id: link.currency?.id ?? '',
            expires_at: link.expires_at ? link.expires_at.substring(0, 16) : '',
        });
        editForm.clearErrors();
        setEditing(link);
    }

    function handleEdit(e: React.FormEvent) {
        e.preventDefault();
        if (!editing) return;
        editForm.put(route('admin.payment-links.update', editing.id), {
            onSuccess: () => {
                setEditing(null);
                toast.success(__('admin.payment_link_updated') || 'Updated');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    }

    function doDelete() {
        if (!pendingDelete) return;
        router.delete(route('admin.payment-links.destroy', pendingDelete.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.deleted') || 'Deleted');
                setPendingDelete(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingDelete(null);
            },
        });
    }

    function doCancel() {
        if (!pendingCancel) return;
        router.put(route('admin.payment-links.cancel', pendingCancel.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('admin.payment_link_cancelled') || 'Cancelled');
                setPendingCancel(null);
            },
            onError: (errs) => {
                const msg = errs?.error || __('general.error_occurred') || 'Something went wrong';
                toast.error(msg);
                setPendingCancel(null);
            },
        });
    }

    function doMarkPaid() {
        if (!pendingMarkPaid) return;
        router.post(route('admin.payment-links.mark-paid', pendingMarkPaid.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('admin.payment_link_marked_paid') || 'Marked paid');
                setPendingMarkPaid(null);
            },
            onError: (errs) => {
                const msg = errs?.error || __('general.error_occurred') || 'Something went wrong';
                toast.error(msg);
                setPendingMarkPaid(null);
            },
        });
    }

    function doBulkDelete() {
        if (selected.length === 0) return;
        router.post(route('admin.payment-links.bulk-destroy'), { ids: selected }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('admin.bulk_deleted', { count: selected.length }) || 'Deleted');
                setSelected([]);
                setPendingBulkDelete(false);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingBulkDelete(false);
            },
        });
    }

    const copyToClipboard = (uuid: string) => {
        const url = route('guest.payment-links.show', uuid);
        navigator.clipboard.writeText(url);
        toast.success(__('admin.copied_to_clipboard') || 'Copied to clipboard');
    };

    const paginationLinks = paymentLinks?.links;
    const hasActiveFilters =
        !!filters.search || (filters.status && filters.status !== 'all') || !!filters.currency_id || !!filters.date_from || !!filters.date_to;

    function clearFilters() {
        setLocalSearch('');
        router.get(route('admin.payment-links.index'), {}, { preserveState: false, replace: true });
    }

    const statsBlock = stats ?? { total: 0, paid: 0, pending: 0, cancelled: 0, expired: 0 };
    const totalAll = paymentLinks?.total ?? statsBlock.total ?? filteredData.length;
    const firstItem = (paymentLinks?.from ?? 1) as number;
    const lastItem = (paymentLinks?.to ?? filteredData.length) as number;

    return (
        <AdminSidebarLayout title={__('admin.payment_links')} header={__('admin.payment_links')}>
            <Head title={__('admin.payment_links')} />

            <div className="mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <MetricCard label={__('admin.total_links')} value={statsBlock.total} icon={DollarSign} />
                <MetricCard label={__('admin.paid')} value={statsBlock.paid} icon={CheckCircle} />
                <MetricCard label={__('admin.pending')} value={statsBlock.pending} icon={Clock} />
                <MetricCard label={__('admin.cancelled')} value={statsBlock.cancelled} icon={XCircle} />
                <MetricCard label={__('admin.expired')} value={statsBlock.expired} icon={CalendarOff} />
            </div>

            <Card className="mb-4 bg-white shadow-sm overflow-visible">
                <CardContent className="p-4 flex flex-wrap items-center gap-3 justify-between">
                    <h2 className="me-auto text-lg font-semibold">{__('admin.payment_links')}</h2>

                    <div className="relative">
                        <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                        <Input
                            placeholder={__('admin.search_links')}
                            value={localSearch}
                            onChange={(e) => setLocalSearch(e.target.value)}
                            className="ps-8 h-9 w-[200px]"
                        />
                    </div>

                    <select
                        value={filters.status ?? 'all'}
                        onChange={(e) => applyFilters({ status: e.target.value })}
                        className="h-9 rounded-md border border-gray-300 px-2 text-sm bg-white"
                    >
                        <option value="all">{__('admin.all_statuses')}</option>
                        <option value="paid">{__('admin.paid')}</option>
                        <option value="pending">{__('admin.pending')}</option>
                        <option value="cancelled">{__('admin.cancelled')}</option>
                        <option value="expired">{__('admin.expired')}</option>
                    </select>

                    <Input
                        type="date"
                        value={filters.date_from ?? ''}
                        onChange={(e) => applyFilters({ date_from: e.target.value })}
                        className="h-9 w-[150px]"
                        placeholder={__('admin.date_from')}
                    />
                    <Input
                        type="date"
                        value={filters.date_to ?? ''}
                        onChange={(e) => applyFilters({ date_to: e.target.value })}
                        className="h-9 w-[150px]"
                        placeholder={__('admin.date_to')}
                    />

                    {hasActiveFilters && (
                        <Button variant="ghost" size="sm" onClick={clearFilters} className="h-9 text-gray-500">
                            <FilterX className="w-4 h-4 me-1" />{__('admin.clear')}
                        </Button>
                    )}

                    <Button onClick={openCreate} className="h-9">
                        <Plus className="me-2 h-4 w-4" />{__('admin.create_payment_link', { default: 'Create Link' })}
                    </Button>
                </CardContent>
            </Card>

            {totalSelected > 0 && (
                <Card className="mb-4 bg-slate-50 border-slate-200">
                    <CardContent className="p-3 flex items-center gap-3">
                        <span className="text-sm font-medium">{__('admin.bulk_actions')}: {totalSelected}</span>
                        <Button variant="destructive" size="sm" onClick={() => setPendingBulkDelete(true)}>
                            <Trash className="w-4 h-4 me-1" />{__('admin.bulk_delete')}
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => setSelected([])}>
                            {__('general.cancel')}
                        </Button>
                    </CardContent>
                </Card>
            )}

            {filteredData.length === 0 ? (
                <EmptyState
                    icon={DollarSign}
                    title={hasActiveFilters
                        ? __('admin.no_results', { default: 'No results match your filters.' })
                        : __('admin.no_payment_links_found', { default: 'No payment links found.' })}
                    description={__('admin.create_a_payment_link_cta') || 'Create a payment link to get paid by anyone.'}
                    onClick={openCreate}
                    actionLabel={__('admin.create_payment_link', { default: 'Create Link' })}
                    actionIcon={Plus}
                />
            ) : (
                <>
                <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                    <Card className="bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div className="table-responsive">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50 hover:bg-muted/50 border-b">
                                        <TableHead className="w-10">
                                            <Checkbox checked={allOnPageSelected} onCheckedChange={toggleSelectAll} />
                                        </TableHead>
                                        <TableHead className="uppercase text-xs">ID</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.title')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.amount')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.currency')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.status')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('admin.client')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('admin.created_by')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.date')}</TableHead>
                                        <TableHead className="text-end uppercase text-xs">{__('general.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredData.map((link) => (
                                        <TableRow key={link.id}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={selected.includes(link.id)}
                                                    onCheckedChange={() => toggleOne(link.id)}
                                                />
                                            </TableCell>
                                            <TableCell data-label="ID" className="font-medium">#{link.id}</TableCell>
                                            <TableCell data-label={__('general.title')}>
                                                <div className="font-medium text-slate-900">{link.title}</div>
                                                {link.description && (
                                                    <div className="text-xs text-slate-500 line-clamp-1">{link.description}</div>
                                                )}
                                                {link.uuid && (
                                                    <code className="text-xs text-slate-400 font-mono">/{link.uuid.slice(0, 8)}…</code>
                                                )}
                                            </TableCell>
                                            <TableCell data-label={__('general.amount')} className="font-semibold text-slate-900 font-mono">
                                                {formatMoney(link.amount, link.currency)}
                                            </TableCell>
                                            <TableCell data-label={__('general.currency')} className="text-slate-600 text-sm">
                                                {typeof link.currency === 'object' ? link.currency?.currency : link.currency}
                                            </TableCell>
                                            <TableCell data-label={__('general.status')}>
                                                <StatusBadge status={link.status} />
                                            </TableCell>
                                            <TableCell data-label={__('admin.client')}>
                                                {link.client?.name || '-'}
                                            </TableCell>
                                            <TableCell data-label={__('admin.created_by')}>
                                                {link.user?.name || '-'}
                                            </TableCell>
                                            <TableCell data-label={__('general.date')} className="text-muted-foreground text-sm">
                                                {new Date(link.created_at).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell data-label={__('general.actions')} className="text-end">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0">
                                                            <span className="sr-only">{__('general.open_menu')}</span>
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem onClick={() => copyToClipboard(link.uuid)}>
                                                            <Copy className="me-2 h-4 w-4" />{__('admin.copy_link', { default: 'Copy Link' })}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <a href={route('guest.payment-links.show', link.uuid)} target="_blank" rel="noopener noreferrer" className="flex items-center w-full">
                                                                <ExternalLink className="me-2 h-4 w-4" />{__('admin.view_link', { default: 'View Link' })}
                                                            </a>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        {link.status === 'pending' && (
                                                            <>
                                                                <DropdownMenuItem onClick={() => openEdit(link)}>
                                                                    <Pencil className="me-2 h-4 w-4" />{__('admin.edit_payment_link', { default: 'Edit' })}
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => setPendingCancel(link)} className="text-amber-600 focus:text-amber-600">
                                                                    <XCircle className="me-2 h-4 w-4" />{__('admin.cancel_payment_link', { default: 'Cancel Link' })}
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                        {canForceMarkPaid && link.status === 'pending' && (
                                                            <DropdownMenuItem onClick={() => setPendingMarkPaid(link)} className="text-emerald-600 focus:text-emerald-600">
                                                                <CheckCircle className="me-2 h-4 w-4" />{__('admin.mark_paid_manually', { default: 'Mark as Paid' })}
                                                            </DropdownMenuItem>
                                                        )}
                                                        <DropdownMenuItem onClick={() => setPendingDelete(link)} className="text-red-600 focus:text-red-600">
                                                            <Trash className="me-2 h-4 w-4" />{__('general.delete')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </Card>
                </div>
                <div className="mt-3 text-sm text-muted-foreground">
                    {__('admin.pagination_summary', { from: firstItem, to: lastItem, total: totalAll, default: `Showing ${firstItem}-${lastItem} of ${totalAll}` })}
                </div>
                </>
            )}

            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                <div className="mt-4 flex justify-center md:justify-end">
                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                        {paginationLinks.map((link: any, i: number) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={cn(
                                    'px-3 py-2 text-sm border',
                                    link.active
                                        ? 'z-10 bg-primary border-primary text-primary-foreground font-medium'
                                        : 'bg-background border-input text-muted-foreground hover:bg-muted',
                                    i === 0 ? 'rounded-s-md' : '',
                                    i === paginationLinks.length - 1 ? 'rounded-e-md' : '',
                                )}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('admin.create_payment_link', { default: 'Create Payment Link' })}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCreate}>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>{__('general.title')}</Label>
                                <Input
                                    value={createForm.data.title}
                                    onChange={(e) => createForm.setData('title', e.target.value)}
                                    required
                                    placeholder={__('admin.payment_link_title_placeholder', { default: 'e.g. Website Maintenance' })}
                                />
                                {createForm.errors.title && <p className="text-sm text-destructive mt-1">{createForm.errors.title}</p>}
                            </div>
                            <div>
                                <Label>{__('admin.description')}</Label>
                                <Input
                                    value={createForm.data.description}
                                    onChange={(e) => createForm.setData('description', e.target.value)}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label>{__('general.amount')}</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={createForm.data.amount}
                                        onChange={(e) => createForm.setData('amount', e.target.value)}
                                        required
                                    />
                                    {createForm.errors.amount && <p className="text-sm text-destructive mt-1">{createForm.errors.amount}</p>}
                                </div>
                                <CurrencySelect
                                    label={__('general.currency')}
                                    currencies={currencies}
                                    value={createForm.data.currency_id}
                                    onChange={(val) => createForm.setData('currency_id', val)}
                                    error={createForm.errors.currency_id}
                                />
                            </div>
                            {clients.length > 0 && (
                                <div>
                                    <Label>{__('admin.select_client')}</Label>
                                    <select
                                        value={createForm.data.client_id}
                                        onChange={(e) => createForm.setData('client_id', e.target.value)}
                                        className="w-full h-9 rounded-md border border-gray-300 px-2 text-sm bg-white"
                                    >
                                        <option value="">—</option>
                                        {clients.map((c: any) => (
                                            <option key={c.id} value={c.id}>{c.name} ({c.email})</option>
                                        ))}
                                    </select>
                                </div>
                            )}
                            <div>
                                <Label>{__('general.expires_at') || 'Expires At'}</Label>
                                <Input
                                    type="datetime-local"
                                    value={createForm.data.expires_at}
                                    onChange={(e) => createForm.setData('expires_at', e.target.value)}
                                />
                                {createForm.errors.expires_at && <p className="text-sm text-destructive mt-1">{createForm.errors.expires_at}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? __('general.saving') : __('general.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('admin.edit_payment_link', { default: 'Edit Payment Link' })}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEdit}>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>{__('general.title')}</Label>
                                <Input
                                    value={editForm.data.title}
                                    onChange={(e) => editForm.setData('title', e.target.value)}
                                    required
                                />
                                {editForm.errors.title && <p className="text-sm text-destructive mt-1">{editForm.errors.title}</p>}
                            </div>
                            <div>
                                <Label>{__('admin.description')}</Label>
                                <Input
                                    value={editForm.data.description}
                                    onChange={(e) => editForm.setData('description', e.target.value)}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label>{__('general.amount')}</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={editForm.data.amount}
                                        onChange={(e) => editForm.setData('amount', e.target.value)}
                                        required
                                    />
                                </div>
                                <CurrencySelect
                                    label={__('general.currency')}
                                    currencies={currencies}
                                    value={editForm.data.currency_id}
                                    onChange={(val) => editForm.setData('currency_id', val)}
                                    error={editForm.errors.currency_id}
                                />
                            </div>
                            <div>
                                <Label>{__('general.expires_at') || 'Expires At'}</Label>
                                <Input
                                    type="datetime-local"
                                    value={editForm.data.expires_at}
                                    onChange={(e) => editForm.setData('expires_at', e.target.value)}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditing(null)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? __('general.saving') : __('general.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete') || 'Delete?'}
                description={__('admin.confirm_delete_payment_link', { default: 'Are you sure you want to delete this payment link?' })}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={doDelete}
                onCancel={() => setPendingDelete(null)}
            />

            <ConfirmModal
                isOpen={pendingCancel !== null}
                title={__('admin.cancel_payment_link', { default: 'Cancel Link' })}
                description={__('admin.only_pending_can_be_cancelled', { default: 'Only pending links can be cancelled.' })}
                confirmLabel={__('general.confirm') || 'Confirm'}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={doCancel}
                onCancel={() => setPendingCancel(null)}
            />

            <ConfirmModal
                isOpen={pendingMarkPaid !== null}
                title={__('admin.mark_paid_manually', { default: 'Mark as Paid' })}
                description={__('admin.only_super_admin_can_mark_paid', { default: 'Only super admins can mark a link as paid manually.' })}
                confirmLabel={__('general.confirm') || 'Confirm'}
                cancelLabel={__('general.cancel')}
                onConfirm={doMarkPaid}
                onCancel={() => setPendingMarkPaid(null)}
            />

            <ConfirmModal
                isOpen={pendingBulkDelete}
                title={__('admin.bulk_delete')}
                description={__('admin.confirm_bulk_delete_payment_links', { count: totalSelected, default: `Delete ${totalSelected} selected payment links?` })}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={doBulkDelete}
                onCancel={() => setPendingBulkDelete(false)}
            />
        </AdminSidebarLayout>
    );
}