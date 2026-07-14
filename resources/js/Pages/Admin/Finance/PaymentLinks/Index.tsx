import React, { useState, useMemo } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { formatMoney, cn } from '@/lib/utils';
import { MoreHorizontal, Plus, Copy, Trash, Search, FilterX, ExternalLink, DollarSign, Calendar, Eye, Clock } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
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

export default function Index({ paymentLinks, currencies, stats }: any) {
    const { data, setData, post, processing, reset, errors } = useForm({
        title: '',
        amount: '',
        currency_id: '',
    });

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<any | null>(null);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const filtered = useMemo(() => {
        const q = search.toLowerCase().trim();
        return (paymentLinks.data ?? []).filter((l: any) => {
            const matchSearch = !q || l.title?.toLowerCase().includes(q) || l.user?.name?.toLowerCase().includes(q);
            const matchStatus = statusFilter === 'all' || l.status === statusFilter;
            return matchSearch && matchStatus;
        });
    }, [paymentLinks.data, search, statusFilter]);

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.payment-links.store'), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
                toast.success(__('admin.payment_link_created') || 'Payment link created');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const handleDelete = () => {
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
    };

    const copyToClipboard = (uuid: string) => {
        const url = route('guest.payment-links.show', uuid);
        navigator.clipboard.writeText(url);
        toast.success(__('admin.copied_to_clipboard') || 'Copied to clipboard');
    };

    const paginationLinks = paymentLinks.meta?.links || paymentLinks.links;
    const hasActiveFilters = search !== '' || statusFilter !== 'all';

    const clearFilters = () => {
        setSearch('');
        setStatusFilter('all');
    };

    return (
        <AdminSidebarLayout title={__('admin.payment_links')} header={__('admin.payment_links')}>
            <Head title={__('admin.payment_links')} />

            {stats && (
                <div className="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <MetricCard label={__('admin.total_links')} value={stats.total ?? paymentLinks.total ?? 0} icon={DollarSign} />
                    <MetricCard label={__('admin.paid')} value={stats.paid ?? 0} icon={Copy} />
                    <MetricCard label={__('admin.pending')} value={stats.pending ?? 0} icon={Clock} />
                </div>
            )}

            <Card className="mb-4 bg-white shadow-sm overflow-visible">
                <CardContent className="p-4 flex flex-wrap items-center gap-3 justify-between">
                    <h2 className="me-auto text-lg font-semibold">{__('admin.payment_links')}</h2>

                    <div className="relative">
                        <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                        <Input
                            placeholder={__('admin.search_links')}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="ps-8 h-9 w-[200px]"
                        />
                    </div>

                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="h-9 rounded-md border border-gray-300 px-2 text-sm bg-white"
                    >
                        <option value="all">{__('admin.all_statuses')}</option>
                        <option value="paid">{__('admin.paid')}</option>
                        <option value="pending">{__('admin.pending')}</option>
                    </select>

                    {hasActiveFilters && (
                        <Button variant="ghost" size="sm" onClick={clearFilters} className="h-9 text-gray-500">
                            <FilterX className="w-4 h-4 me-1" />{__('admin.clear')}
                        </Button>
                    )}

                    <Button onClick={() => setIsCreateModalOpen(true)} className="h-9">
                        <Plus className="me-2 h-4 w-4" />{__('admin.create_payment_link', { default: 'Create Link' })}
                    </Button>
                </CardContent>
            </Card>

            {filtered.length === 0 ? (
                <EmptyState
                    icon={DollarSign}
                    title={__('admin.no_payment_links_found', { default: 'No payment links found.' })}
                    description={__('admin.create_a_payment_link_cta') || 'Create a payment link to get paid by anyone.'}
                    action={route('admin.payment-links.index')}
                    actionLabel={__('admin.create_payment_link', { default: 'Create Link' })}
                    actionIcon={Plus}
                />
            ) : (
                <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                    <Card className="bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div className="table-responsive">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50 hover:bg-muted/50 border-b">
                                        <TableHead className="uppercase text-xs">ID</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.title')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.amount')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.currency')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.status')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('admin.created_by')}</TableHead>
                                        <TableHead className="uppercase text-xs">{__('general.date')}</TableHead>
                                        <TableHead className="text-end uppercase text-xs">{__('general.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filtered.map((link: any) => (
                                        <TableRow key={link.id}>
                                            <TableCell data-label="ID" className="font-medium">#{link.id}</TableCell>
                                            <TableCell data-label={__('general.title')}>
                                                <div className="font-medium text-slate-900">{link.title}</div>
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
                                                        {link.status === 'pending' && (
                                                            <>
                                                                <DropdownMenuItem
                                                                    onClick={() => setPendingDelete(link)}
                                                                    className="text-red-600 focus:text-red-600"
                                                                >
                                                                    <Trash className="me-2 h-4 w-4" />{__('general.delete')}
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
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
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    required
                                    placeholder={__('admin.payment_link_title_placeholder', { default: 'e.g. Website Maintenance' })}
                                />
                                {errors.title && <p className="text-sm text-destructive mt-1">{errors.title}</p>}
                            </div>
                            <div>
                                <Label>{__('general.amount')}</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)}
                                    required
                                />
                                {errors.amount && <p className="text-sm text-destructive mt-1">{errors.amount}</p>}
                            </div>
                            <CurrencySelect
                                label={__('general.currency')}
                                currencies={currencies}
                                value={data.currency_id}
                                onChange={(val) => setData('currency_id', val)}
                                error={errors.currency_id}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? __('general.saving') : __('general.save')}
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
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}