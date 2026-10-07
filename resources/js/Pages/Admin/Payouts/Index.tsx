import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { MoreHorizontal, FileText, CheckCircle, XCircle, Plus, User } from 'lucide-react';
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
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { useState } from 'react';
import { __ } from '@/lib/i18n';
import Pagination from '@/Components/Pagination';

export default function Index({ payouts, filters = {}, projects = [] }: any) {
    const paginationLinks = payouts.meta?.links || payouts.links;
    const [pendingMarkPaid, setPendingMarkPaid] = useState<any>(null);
    const [pendingDelete, setPendingDelete] = useState<any>(null);

    const confirmMarkPaid = () => {
        if (!pendingMarkPaid) return;
        const id = pendingMarkPaid;
        setPendingMarkPaid(null);
        router.post(route('admin.payouts.mark-paid', id), {}, {
            preserveScroll: true,
            onSuccess: () => toastSuccess(__('general.payout_marked_paid')),
            onError: () => toastError(__('general.error_occurred')),
        });
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        const id = pendingDelete;
        setPendingDelete(null);
        router.delete(route('admin.payouts.destroy', id), {
            preserveScroll: true,
            onSuccess: () => toastSuccess(__('general.deleted')),
            onError: () => toastError(__('general.error_occurred')),
        });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'paid':
                return <StatusBadge status="paid" />;
            case 'pending':
            default:
                return <StatusBadge status="pending" label={__('general.pending')} className="bg-yellow-50 text-yellow-700 border-yellow-100" />;
        }
    };

    return (
        <AdminSidebarLayout title={__('admin.payouts_title')} header={__('admin.payouts_header')}>
            
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-2xl font-bold">{__('admin.payouts_title')}</h1>
                <Link href={route('admin.users.index')}>
                    <Button>{__('admin.payouts_create_from_users')}</Button>
                </Link>
            </div>

            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <Card className="bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div className="table-responsive">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50 border-b">
                                    <TableHead className="hidden sm:table-cell uppercase text-xs">{__('admin.payouts_col_id')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.customer')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.project')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.date')}</TableHead>
                                    <TableHead className="text-end uppercase text-xs">{__('general.total')}</TableHead>
                                    <TableHead className="text-center uppercase text-xs">{__('general.status')}</TableHead>
                                    <TableHead className="text-end uppercase text-xs">{__('general.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(payouts.data as any).map((payout) => (
                                    <TableRow key={payout.id}>
                                        <TableCell className="font-medium hidden sm:table-cell" data-label={__('admin.payouts_col_id')}>
                                            <Link href={route('admin.payouts.show', payout.id)} className="text-primary hover:underline font-semibold">
                                                #{payout.id}
                                            </Link>
                                        </TableCell>
                                        <TableCell data-label={__('general.customer')}>
                                            {payout.user ? (
                                                <div className="flex items-center gap-3">
                                                    <Avatar className="h-10 w-10 border border-slate-200">
                                                        <AvatarImage src={payout.user.avatar_url || ''} alt={payout.user.name} />
                                                        <AvatarFallback className="bg-slate-50 text-slate-900">
                                                            <User className="h-5 w-5" />
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="flex flex-col text-start">
                                                        <span className="font-semibold text-slate-900 flex items-center gap-1">
                                                            {payout.user.name}
                                                        </span>
                                                        <span className="text-sm text-slate-500 font-normal">
                                                            {payout.user.email}
                                                        </span>
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="font-semibold text-muted-foreground">{__('general.unknown')}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium text-foreground" data-label={__('general.project')}>
                                            {payout.project ? payout.project.project_name : '-'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm" data-label={__('general.date')}>
                                            {new Date(payout.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-end" data-label={__('general.total')}>
                                            <span className="font-semibold text-slate-900">
                                                {formatCurrency(payout.total, payout.currency_id)}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-center" data-label={__('general.status')}>
                                            <div className="inline-block">
                                                {getStatusBadge(payout.status)}
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
                                                    <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('admin.payouts.show', payout.id)} className="flex w-full items-center">
                                                            <FileText className="me-2 h-4 w-4 text-slate-900" />{__('general.view_details')}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    {payout.status !== 'paid' && (
                                                        <DropdownMenuItem onClick={() => setPendingMarkPaid(payout.id)}>
                                                            <CheckCircle className="me-2 h-4 w-4 text-slate-900" />{__('general.mark_as_paid')}
                                                        </DropdownMenuItem>
                                                    )}

                                                    {payout.status !== 'paid' && (
                                                        <DropdownMenuItem onClick={() => setPendingDelete(payout.id)} className="text-red-600 focus:text-red-600">
                                                            <XCircle className="me-2 h-4 w-4" />{__('general.delete_payout')}
                                                        </DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(payouts.data as any).length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-24 text-center text-muted-foreground">{__('admin.payouts_empty')}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* Pagination */}
            <Pagination links={Array.isArray(paginationLinks) ? paginationLinks : []} />

            <ConfirmModal
                isOpen={pendingMarkPaid !== null}
                title={__('general.mark_as_paid')}
                description={__('general.confirm_mark_payout_paid_desc')}
                confirmLabel={__('general.mark_as_paid')}
                cancelLabel={__('general.cancel')}
                onConfirm={confirmMarkPaid}
                onCancel={() => setPendingMarkPaid(null)}
            />

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete_payout')}
                description={__('general.confirm_delete_payout_desc')}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}
