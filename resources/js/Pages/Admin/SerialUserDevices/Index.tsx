import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Shield, Search, Trash2, Plus, Users } from 'lucide-react';

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
    active:   'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    inactive: 'bg-zinc-700/50 text-zinc-400 border-zinc-600',
    blocked:  'bg-red-500/15 text-red-400 border-red-500/30',
};

export default function SerialUserDevicesIndex({ userDevices, filters, statuses, stats, perPageOptions }: Props) {
    const { auth } = usePage().props as any;
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (key: string, value: string) => {
        router.get(route('admin.serial-user-devices.index'), { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const updateStatus = (assignment: Assignment, status: string | null) => {
        if (!status) return;
        router.patch(route('admin.serial-user-devices.status', assignment.id), { status });
    };

    const destroy = (assignment: Assignment) => {
        if (!confirm(`Remove assignment for device "${assignment.device_id}"?`)) return;
        router.delete(route('admin.serial-user-devices.destroy', assignment.id));
    };

    return (
        <AdminSidebarLayout title={__('general.serial_user_devices')} header="Serial User Devices">
            <Head title={__('general.user_device_assignments')} />
            <div className="min-h-screen bg-zinc-950 p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">{__('general.user_device_assignments')}</h1>
                        <p className="text-zinc-400 text-sm mt-1">{__('general.map_devices_to_platform_users_for_license_activation')}</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('admin.serial-user-devices.by-user')}>
                            <Button variant="outline" size="sm" className="border-zinc-700 text-zinc-300 hover:text-white hover:bg-zinc-800 gap-2">
                                <Users className="w-4 h-4" />{__('general.by_user')}</Button>
                        </Link>
                        <Link href={route('admin.serial-user-devices.assign')}>
                            <Button size="sm" className="bg-violet-600 hover:bg-violet-500 text-white gap-2">
                                <Plus className="w-4 h-4" />{__('general.assign_device')}</Button>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Total Assignments', value: stats.total },
                        { label: 'Active', value: stats.active },
                        { label: 'Inactive', value: stats.inactive },
                    ].map(({ label, value }) => (
                        <Card key={label} className="bg-zinc-900 border-zinc-800">
                            <CardContent className="p-4">
                                <p className="text-2xl font-bold text-white">{value}</p>
                                <p className="text-xs text-zinc-500 mt-0.5">{label}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <div className="relative flex-1 min-w-48">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                        <Input
                            className="pl-9 bg-zinc-900 border-zinc-700 text-white placeholder:text-zinc-500"
                            placeholder={__('general.search_device_id_user')}
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilter('search', search)}
                        />
                    </div>
                    <Select onValueChange={v => applyFilter('status', v)} defaultValue={filters.status ?? 'all'}>
                        <SelectTrigger className="w-36 bg-zinc-900 border-zinc-700 text-white">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent className="bg-zinc-900 border-zinc-700">
                            <SelectItem value="all">{__('general.all_statuses')}</SelectItem>
                            {statuses.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <Card className="bg-zinc-900 border-zinc-800">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-zinc-800">
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">{__('general.device_id')}</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">{__('general.assigned_user')}</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Notes</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Assigned</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Status</th>
                                        <th className="text-right px-4 py-3 text-zinc-400 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {userDevices.data.length === 0 && (
                                        <tr><td colSpan={6} className="text-center py-12 text-zinc-500">{__('general.no_assignments_found')}</td></tr>
                                    )}
                                    {userDevices.data.map(a => (
                                        <tr key={a.id} className="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                                            <td className="px-4 py-3 font-mono text-xs text-violet-300">{a.device_id}</td>
                                            <td className="px-4 py-3">
                                                {a.user ? (
                                                    <div>
                                                        <p className="text-white text-sm">{a.user.name}</p>
                                                        <p className="text-zinc-500 text-xs">{a.user.email}</p>
                                                    </div>
                                                ) : <span className="text-zinc-500 text-xs">—</span>}
                                            </td>
                                            <td className="px-4 py-3 text-zinc-400 text-xs max-w-xs truncate">{a.notes ?? '—'}</td>
                                            <td className="px-4 py-3 text-zinc-500 text-xs">{a.created_at}</td>
                                            <td className="px-4 py-3">
                                                <Select value={a.status} onValueChange={v => updateStatus(a, v)}>
                                                    <SelectTrigger className="w-24 h-7 text-xs bg-zinc-800 border-zinc-700 text-white">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent className="bg-zinc-900 border-zinc-700">
                                                        {statuses.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button size="sm" variant="ghost"
                                                    className="text-red-400 hover:text-red-300 hover:bg-red-500/10 w-8 h-8 p-0"
                                                    onClick={() => destroy(a)}>
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
        </AdminSidebarLayout>
    );
}
