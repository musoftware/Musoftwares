import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Shield, Search, Trash2, Plus, Users } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

interface Assignment {
    id: number;
    device_id: string;
    status: string;
    notes: string | null;
    created_at: string;
    user?: { id: number; name: string; email: string };
}

interface Stats { total: number; active: number; inactive: number; }

interface Props {
    userDevices: { data: Assignment[]; links: any[]; meta: any };
    filters: Record<string, any>;
    statuses: string[];
    stats: Stats;
    perPageOptions: number[];
}

const statusColor: Record<string, string> = {
    active:   'bg-slate-900/15 text-slate-900 border-green-500/30',
    inactive: 'bg-zinc-700/50 text-zinc-400 border-zinc-600',
    blocked:  'bg-red-500/15 text-red-400 border-red-500/30',
};

export default function SerialUserDevicesIndex({ userDevices, filters, statuses, stats, perPageOptions }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [pendingDelete, setPendingDelete] = useState<Assignment | null>(null);

    const applyFilter = (key: string, value: string) => {
        router.get(route('admin.serial-user-devices.index'), { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const updateStatus = (assignment: Assignment, status: string | null) => {
        if (!status) return;
        router.patch(route('admin.serial-user-devices.status', assignment.id), { status }, {
            onSuccess: () => toastSuccess(__('general.status_updated') || 'Status updated'),
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        const id = pendingDelete.id;
        setPendingDelete(null);
        router.delete(route('admin.serial-user-devices.destroy', id), {
            onSuccess: () => toastSuccess(__('general.removed') || 'Assignment removed'),
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.serial_user_devices')} header="Serial User Devices">
            <Head title={__('general.user_device_assignments')} />
            <div className="space-y-6">
                <div className="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">{__('general.user_device_assignments')}</h1>
                        <p className="text-slate-500 text-sm mt-1">{__('general.map_devices_to_platform_users_for_license_activation')}</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('admin.serial-user-devices.by-user')}>
                            <Button variant="outline" size="sm" className="gap-2">
                                <Users className="w-4 h-4" />{__('general.by_user')}</Button>
                        </Link>
                        <Link href={route('admin.serial-user-devices.assign')}>
                            <Button size="sm" className="gap-2">
                                <Plus className="w-4 h-4" />{__('general.assign_device')}</Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {[
                        { label: __('general.total') || 'Total Assignments', value: stats.total },
                        { label: __('general.active'), value: stats.active },
                        { label: __('general.inactive'), value: stats.inactive },
                    ].map(({ label, value }) => (
                        <Card key={label}>
                            <CardContent className="p-4">
                                <p className="text-2xl font-bold text-slate-900">{value}</p>
                                <p className="text-xs text-slate-500 mt-0.5 uppercase tracking-wider">{label}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="flex flex-wrap gap-3">
                    <div className="relative flex-1 min-w-48">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <Input
                            className="ps-9"
                            placeholder={__('general.search_device_id_user')}
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilter('search', search)}
                        />
                    </div>
                    <Select onValueChange={v => applyFilter('status', v)} defaultValue={filters.status ?? 'all'}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder={__('general.status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{__('general.all_statuses')}</SelectItem>
                            {statuses.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50">
                                        <th className="text-start px-4 py-3 text-slate-500 font-semibold uppercase tracking-wider text-xs">{__('general.device_id')}</th>
                                        <th className="text-start px-4 py-3 text-slate-500 font-semibold uppercase tracking-wider text-xs">{__('general.assigned_user')}</th>
                                        <th className="text-start px-4 py-3 text-slate-500 font-semibold uppercase tracking-wider text-xs">{__('general.notes')}</th>
                                        <th className="text-start px-4 py-3 text-slate-500 font-semibold uppercase tracking-wider text-xs">{__('general.assigned')}</th>
                                        <th className="text-start px-4 py-3 text-slate-500 font-semibold uppercase tracking-wider text-xs">{__('general.status')}</th>
                                        <th className="text-end px-4 py-3 text-slate-500 font-semibold uppercase tracking-wider text-xs">{__('general.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(userDevices.data as any).length === 0 && (
                                        <tr><td colSpan={6} className="text-center py-12 text-slate-500">{__('general.no_assignments_found')}</td></tr>
                                    )}
                                    {(userDevices.data as any).map(a => (
                                        <tr key={a.id} className="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                            <td className="px-4 py-3 font-mono text-xs text-slate-900">{a.device_id}</td>
                                            <td className="px-4 py-3">
                                                {a.user ? (
                                                    <div>
                                                        <p className="text-slate-900 text-sm font-medium">{a.user.name}</p>
                                                        <p className="text-slate-500 text-xs">{a.user.email}</p>
                                                    </div>
                                                ) : <span className="text-slate-400 text-xs">—</span>}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600 text-xs max-w-xs truncate">{a.notes ?? '—'}</td>
                                            <td className="px-4 py-3 text-slate-500 text-xs">{a.created_at}</td>
                                            <td className="px-4 py-3">
                                                <Select value={a.status} onValueChange={v => updateStatus(a, v)}>
                                                    <SelectTrigger className="w-24 h-7 text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {statuses.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-4 py-3 text-end">
                                                <Button size="sm" variant="ghost"
                                                    className="text-red-600 hover:text-red-700 hover:bg-red-50 w-8 h-8 p-0"
                                                    onClick={() => setPendingDelete(a)}
                                                    aria-label={__('general.delete')}>
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.remove_assignment') || 'Remove assignment?'}
                description={__('general.confirm_remove_assignment_desc') || `This will remove the assignment for device "${pendingDelete?.device_id}".`}
                confirmLabel={__('general.remove')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}
