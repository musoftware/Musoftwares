import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeft, Monitor, User, Clock } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

interface UserRow {
    id: number;
    name: string;
    email: string;
    total_devices: number;
    active_devices: number;
    inactive_devices: number;
}

interface Props {
    users: { data: UserRow[]; links: any[]; meta: any };
    filters: Record<string, any>;
}

export default function SerialUserDevicesByUser({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [pendingStatus, setPendingStatus] = useState<{ user: UserRow; status: string } | null>(null);

    const applyFilter = (key: string, value: string) => {
        router.get(route('admin.serial-user-devices.by-user'), { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const confirmUpdateAllStatus = () => {
        if (!pendingStatus) return;
        const { user, status } = pendingStatus;
        setPendingStatus(null);
        router.patch(route('admin.serial-user-devices.update-user-status', user.id), { status }, {
            onSuccess: () => toastSuccess(__('general.user_devices_updated') || `All devices for ${user.name} updated to ${status}.`),
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.devices_by_user_1')} header="Devices By User">
            <Head title={__('general.devices_by_user_1')} />
            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.serial-user-devices.index')} className="text-slate-500 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">{__('general.devices_by_user')}</h1>
                        <p className="text-slate-500 text-sm mt-1">{__('general.bulk_manage_all_device_assignments_per_user')}</p>
                    </div>
                </div>

                <div className="relative max-w-sm">
                    <Input
                        placeholder={__('general.search_users')}
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applyFilter('search', search)}
                    />
                </div>

                <div className="space-y-3">
                    {(users.data as any).length === 0 && (
                        <div className="text-center py-16 text-slate-500">{__('general.no_users_with_device_assignments')}</div>
                    )}
                    {(users.data as any).map(user => (
                        <Card key={user.id}>
                            <CardContent className="p-4 flex items-center gap-4 flex-wrap">
                                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                                    <User className="w-5 h-5 text-slate-700" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-slate-900 font-medium">{user.name}</p>
                                    <p className="text-slate-500 text-sm">{user.email}</p>
                                </div>
                                <div className="flex items-center gap-6 text-sm">
                                    <div className="text-center">
                                        <p className="text-slate-900 font-semibold">{user.total_devices}</p>
                                        <p className="text-slate-500 text-xs">{__('general.total')}</p>
                                    </div>
                                    <div className="text-center">
                                        <p className="text-emerald-700 font-semibold">{user.active_devices}</p>
                                        <p className="text-slate-500 text-xs">{__('general.active')}</p>
                                    </div>
                                    <div className="text-center">
                                        <p className="text-slate-500 font-semibold">{user.inactive_devices}</p>
                                        <p className="text-slate-500 text-xs">{__('general.inactive')}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 flex-wrap">
                                    <Button size="sm" variant="outline"
                                        className="text-xs h-8 px-3"
                                        onClick={() => setPendingStatus({ user, status: 'active' })}>
                                        {__('general.activate_all')}
                                    </Button>
                                    <Button size="sm" variant="outline"
                                        className="text-xs h-8 px-3 text-red-600 border-red-200 hover:bg-red-50"
                                        onClick={() => setPendingStatus({ user, status: 'inactive' })}>
                                        {__('general.deactivate_all')}
                                    </Button>
                                    <Link href={route('admin.serial-user-devices.index', { user_id: user.id })}>
                                        <Button size="sm" variant="outline" className="h-8 text-xs">
                                            {__('general.view_devices')}
                                        </Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingStatus !== null}
                title={__('general.confirm_update_all_devices') || 'Update all devices?'}
                description={
                    pendingStatus
                        ? `Set all devices for "${pendingStatus.user.name}" to ${pendingStatus.status}?`
                        : ''
                }
                confirmLabel={__('general.confirm')}
                cancelLabel={__('general.cancel')}
                onConfirm={confirmUpdateAllStatus}
                onCancel={() => setPendingStatus(null)}
            />
        </AdminSidebarLayout>
    );
}
