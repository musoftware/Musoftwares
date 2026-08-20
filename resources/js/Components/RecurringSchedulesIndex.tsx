import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Trash2, Edit, Plus, User, Clock, Calendar, ArrowLeft, Eye, MoreHorizontal } from 'lucide-react';
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
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export type RecurringKind = 'salary' | 'invoice';

export interface RecurringScheduleRow {
    id: number;
    title: string;
    amount: string | number;
    currency: any;
    is_active: boolean;
    reason?: string | null;
    recurring?: string;
    recurring_times?: number;
    recurring_times_week?: string[] | null;
    recurring_times_month?: (string | number)[] | null;
    recurring_times_year?: string[] | null;
    start_date: string;
    current_date?: string;
    next_date?: string;
    user?: { id: number; name?: string; email?: string };
    details?: string;
}

export interface RecurringSchedulesIndexProps {
    kind: RecurringKind;
    items: { data: RecurringScheduleRow[]; links?: any[]; from?: number; to?: number; total?: number };
    pageTitleKey: string;
    pageHeader: string;
    descriptionKey: string;
    backHref: string;
    backLabelKey: string;
    toggleRoute: string;
    deleteRoute: string;
    viewRoute: string;
    editRoute: string;
    createRoute: string;
    createLabelKey: string;
    titleKey: string;
    headerTitleKey: string;
    headerSubtitleKey: string;
    amountColorClass?: string;
}

function pluralize(times: number, unit: string) {
    return `Every ${times} ${unit}${times === 1 ? '' : 's'}`;
}

function toArray(value: unknown): string[] {
    if (Array.isArray(value)) return value.map((v) => String(v));
    if (value == null || value === '') return [];
    if (typeof value === 'string') return value.split(',').map((v) => v.trim()).filter(Boolean);
    return [String(value)];
}

export function formatScheduleSummary(row: RecurringScheduleRow): string {
    const unit = row.recurring ?? 'month';
    const times = row.recurring_times ?? 1;
    let str = pluralize(times, unit);
    const weekDays = toArray(row.recurring_times_week);
    const monthDays = toArray(row.recurring_times_month);
    const yearDays = toArray(row.recurring_times_year);
    if (unit === 'week' && weekDays.length) {
        str += ` on [${weekDays.join(', ')}]`;
    } else if (unit === 'month' && monthDays.length) {
        str += ` on day [${monthDays.join(', ')}]`;
    } else if (unit === 'year' && yearDays.length) {
        str += ` on [${yearDays.join(', ')}]`;
    }
    return str;
}

export function RecurringSchedulesIndex({
    kind,
    items,
    pageTitleKey,
    pageHeader,
    descriptionKey,
    backHref,
    backLabelKey,
    toggleRoute,
    deleteRoute,
    viewRoute,
    editRoute,
    createRoute,
    createLabelKey,
    titleKey,
    headerTitleKey,
    headerSubtitleKey,
    amountColorClass = 'text-rose-600 bg-rose-50 border-rose-200',
}: RecurringSchedulesIndexProps) {
    const [pendingDelete, setPendingDelete] = useState<RecurringScheduleRow | null>(null);

    const handleToggleActive = (id: number, nextActive: boolean) => {
        router.post(
            route(toggleRoute, id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(nextActive ? __('general.activated') || 'Activated' : __('general.deactivated') || 'Deactivated');
                },
                onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
            },
        );
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        router.delete(route(deleteRoute, pendingDelete.id), {
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

    const userLabel = kind === 'salary' ? __('general.employee_user') : __('general.user_user');
    const data = items.data ?? [];

    return (
        <AdminSidebarLayout title={__(pageTitleKey)} header={pageHeader}>
            <Head title={__(pageTitleKey)} />

            <div className="mb-4">
                <Link href={backHref} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__(backLabelKey)}
                </Link>
            </div>

            <div className="flex justify-end gap-4 items-center mb-6">
                <div className="me-auto">
                    <h2 className="text-xl font-bold text-slate-900">{__(headerTitleKey)}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__(headerSubtitleKey)}</p>
                </div>
                <Link href={route(createRoute)}>
                    <Button className="bg-black hover:bg-slate-800 text-white h-9">
                        <Plus className="w-4 h-4 me-2" />{__(createLabelKey)}
                    </Button>
                </Link>
            </div>

            {data.length === 0 ? (
                <EmptyState
                    icon={Calendar}
                    title={__(kind === 'salary' ? 'general.no_recurring_salaries_found' : 'general.no_recurring_invoices_found')}
                    description={__(descriptionKey)}
                    action={route(createRoute)}
                    actionLabel={__(createLabelKey)}
                    actionIcon={Plus}
                />
            ) : (
                <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-gray-50">
                                <TableRow>
                                    <TableHead className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{userLabel}</TableHead>
                                    <TableHead className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.title_schedule')}</TableHead>
                                    <TableHead className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.start_date')}</TableHead>
                                    <TableHead className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</TableHead>
                                    <TableHead className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.active')}</TableHead>
                                    <TableHead className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.note')}</TableHead>
                                    <TableHead className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.map((row) => (
                                    <TableRow key={row.id} className="hover:bg-gray-50">
                                        <TableCell className="px-6 py-4">
                                            <div className="flex items-center">
                                                <div className="bg-slate-100 border p-2 rounded-full me-3 text-slate-650">
                                                    <User className="w-4 h-4" />
                                                </div>
                                                <div>
                                                    <div className="text-sm font-semibold text-gray-900">
                                                        {row.user?.name || (kind === 'salary' ? 'Unknown Employee' : 'Unknown user')}
                                                    </div>
                                                    <div className="text-xs text-gray-500">{row.user?.email || 'N/A'}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="px-6 py-4">
                                            <div className="text-sm font-semibold text-gray-900">{row.title}</div>
                                            <div className="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                                <Clock className="w-3 h-3" /> {formatScheduleSummary(row)}
                                            </div>
                                        </TableCell>
                                        <TableCell className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-900">{new Date(row.start_date).toLocaleDateString()}</div>
                                            {(row.next_date || row.current_date) && (
                                                <div className="text-xs text-gray-500 mt-0.5">Next: {new Date(row.next_date || row.current_date!).toLocaleDateString()}</div>
                                            )}
                                        </TableCell>
                                        <TableCell className="px-6 py-4 whitespace-nowrap text-center">
                                            <span className={`text-sm font-bold border px-2 py-1 rounded ${amountColorClass}`}>
                                                {formatMoney(row.amount, row.currency)}
                                            </span>
                                        </TableCell>
                                        <TableCell className="px-6 py-4 whitespace-nowrap text-center">
                                            <div className="flex flex-col items-center gap-1">
                                                <Switch
                                                    checked={row.is_active}
                                                    onCheckedChange={() => handleToggleActive(row.id, !row.is_active)}
                                                    aria-label={__('general.toggle_active_status')}
                                                />
                                                <div className="text-[10px] text-gray-500">
                                                    {row.is_active ? __('general.active') : __('general.inactive')}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="px-6 py-4 whitespace-nowrap text-center">
                                            <span className="text-xs text-gray-600 italic">
                                                {row.reason || __('general.no_notes')}
                                            </span>
                                        </TableCell>
                                        <TableCell className="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500 hover:text-black" aria-label={__('general.actions')}>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route(viewRoute, row.id)} className="flex items-center gap-2">
                                                            <Eye className="w-4 h-4" /> {__('general.view_details')}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route(editRoute, row.id)} className="flex items-center gap-2">
                                                            <Edit className="w-4 h-4" /> {__('general.edit')}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => setPendingDelete(row)}
                                                        className="text-red-600 focus:text-red-600"
                                                    >
                                                        <Trash2 className="w-4 h-4 me-2" /> {__('general.delete_schedule')}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            )}

            {/* Pagination */}
            {items.links && items.links.length > 3 && (
                <div className="flex justify-end gap-4 items-center mt-6">
                    <div className="me-auto text-sm text-gray-500">
                        {__('general.showing')} {items.from} {__('general.to')} {items.to} {__('general.of')} {items.total} {__('general.entries')}
                    </div>
                    <div className="flex space-x-1">
                        {items.links.map((link: any, idx: number) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                className={`px-3 py-2 border rounded text-sm ${link.active ? 'bg-black text-white border-black font-semibold' : 'bg-white text-gray-700 hover:bg-gray-50'} ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete_schedule') || 'Delete schedule?'}
                description={__('general.confirm_delete_schedule_desc') || `This will permanently delete "${pendingDelete?.title}".`}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}

export default RecurringSchedulesIndex;