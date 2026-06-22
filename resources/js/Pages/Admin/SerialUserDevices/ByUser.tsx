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
    const { auth } = usePage().props as any;
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (key: string, value: string) => {
        router.get(route('admin.serial-user-devices.by-user'), { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const updateAllStatus = (user: UserRow, status: string) => {
        if (!confirm(`Set all devices for "${user.name}" to ${status}?`)) return;
        router.patch(route('admin.serial-user-devices.update-user-status', user.id), { status });
    };

    return (
        <AdminSidebarLayout title={__('general.devices_by_user_1')} header="Devices By User">
            <Head title={__('general.devices_by_user_1')} />
            <div className="min-h-screen bg-zinc-950 p-6 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.serial-user-devices.index')} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">{__('general.devices_by_user')}</h1>
                        <p className="text-zinc-400 text-sm mt-1">{__('general.bulk_manage_all_device_assignments_per_user')}</p>
                    </div>
                </div>

                {/* Search */}
                <div className="relative max-w-sm">
                    <Input
                        className="bg-zinc-900 border-zinc-700 text-white placeholder:text-zinc-500"
                        placeholder={__('general.search_users')}
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applyFilter('search', search)}
                    />
                </div>

                {/* User Cards */}
                <div className="space-y-3">
                    {(users.data as any).length === 0 && (
                        <div className="text-center py-16 text-zinc-500">{__('general.no_users_with_device_assignments')}</div>
                    )}
                    {(users.data as any).map(user => (
                        <Card key={user.id} className="bg-zinc-900 border-zinc-800">
                            <CardContent className="p-4 flex items-center gap-4">
                                <div className="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center shrink-0">
                                    <User className="w-5 h-5 text-zinc-400" />
                                </div>
                                <div className="flex-1">
                                    <p className="text-white font-medium">{user.name}</p>
                                    <p className="text-zinc-400 text-sm">{user.email}</p>
                                </div>
                                <div className="flex items-center gap-6 text-sm">
                                    <div className="text-center">
                                        <p className="text-white font-semibold">{user.total_devices}</p>
                                        <p className="text-zinc-500 text-xs">{__('general.total')}</p>
                                    </div>
                                    <div className="text-center">
                                        <p className="text-emerald-400 font-semibold">{user.active_devices}</p>
                                        <p className="text-zinc-500 text-xs">{__('general.active')}</p>
                                    </div>
                                    <div className="text-center">
                                        <p className="text-zinc-400 font-semibold">{user.inactive_devices}</p>
                                        <p className="text-zinc-500 text-xs">{__('general.inactive')}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button size="sm" variant="ghost"
                                        className="text-emerald-400 hover:bg-emerald-500/10 text-xs h-8 px-3"
                                        onClick={() => updateAllStatus(user, 'active')}>{__('general.activate_all')}</Button>
                                    <Button size="sm" variant="ghost"
                                        className="text-red-400 hover:bg-red-500/10 text-xs h-8 px-3"
                                        onClick={() => updateAllStatus(user, 'inactive')}>{__('general.deactivate_all')}</Button>
                                    <Link href={route('admin.serial-user-devices.index', { user_id: user.id })}>
                                        <Button size="sm" variant="outline" className="border-zinc-700 text-zinc-300 hover:text-white hover:bg-zinc-800 h-8 text-xs">{__('general.view_devices')}</Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
