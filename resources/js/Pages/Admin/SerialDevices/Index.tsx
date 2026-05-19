import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Monitor, Search, Trash2, ToggleLeft, ToggleRight, Cpu } from 'lucide-react';
import { useState } from 'react';

interface Device {
    id: number;
    device_id: string;
    machine_name: string;
    user_name: string;
    user_domain: string;
    status: string;
    last_check_date: string;
    created_at: string;
    software?: { id: number; name: string };
    userDeviceAssignment?: { user?: { id: number; name: string; email: string } };
}

interface Software { id: number; name: string; }
interface Stats { total: number; active: number; inactive: number; }

interface Props {
    devices: { data: Device[]; links: any[]; meta: any };
    filters: Record<string, any>;
    statuses: string[];
    softwares: Software[];
    stats: Stats;
    perPageOptions: number[];
}

const statusColor: Record<string, string> = {
    active:   'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    inactive: 'bg-zinc-700/50 text-zinc-400 border-zinc-600',
    blocked:  'bg-red-500/15 text-red-400 border-red-500/30',
};

export default function SerialDevicesIndex({ devices, filters, statuses, softwares, stats, perPageOptions }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (key: string, value: string) => {
        router.get(route('admin.serial-devices.index'), { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const updateStatus = (device: Device, status: string) => {
        router.patch(route('admin.serial-devices.status', device.id), { status });
    };

    const destroy = (device: Device) => {
        if (!confirm(`Delete device "${device.device_id}"?`)) return;
        router.delete(route('admin.serial-devices.destroy', device.id));
    };

    return (
        <AdminLayout>
            <Head title="Serial Devices" />
            <div className="min-h-screen bg-zinc-950 p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-white tracking-tight">Serial Devices</h1>
                    <p className="text-zinc-400 text-sm mt-1">All registered client machines</p>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Total Devices', value: stats.total, color: 'text-zinc-300' },
                        { label: 'Active', value: stats.active, color: 'text-emerald-400' },
                        { label: 'Inactive', value: stats.inactive, color: 'text-zinc-400' },
                    ].map(({ label, value, color }) => (
                        <Card key={label} className="bg-zinc-900 border-zinc-800">
                            <CardContent className="p-4">
                                <p className={`text-2xl font-bold ${color}`}>{value}</p>
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
                            placeholder="Search device ID, machine..."
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
                            <SelectItem value="all">All Statuses</SelectItem>
                            {statuses.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select onValueChange={v => applyFilter('software_id', v)} defaultValue={String(filters.software_id ?? '')}>
                        <SelectTrigger className="w-48 bg-zinc-900 border-zinc-700 text-white">
                            <SelectValue placeholder="All Software" />
                        </SelectTrigger>
                        <SelectContent className="bg-zinc-900 border-zinc-700">
                            <SelectItem value="">All Software</SelectItem>
                            {softwares.map(sw => <SelectItem key={sw.id} value={String(sw.id)}>{sw.name}</SelectItem>)}
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
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Device ID</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Machine</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">User</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Software</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Last Check</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Status</th>
                                        <th className="text-right px-4 py-3 text-zinc-400 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {devices.data.length === 0 && (
                                        <tr><td colSpan={7} className="text-center py-12 text-zinc-500">No devices found.</td></tr>
                                    )}
                                    {devices.data.map(device => (
                                        <tr key={device.id} className="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                                            <td className="px-4 py-3 font-mono text-xs text-violet-300">{device.device_id}</td>
                                            <td className="px-4 py-3">
                                                <p className="text-white text-sm">{device.machine_name}</p>
                                                <p className="text-zinc-500 text-xs">{device.user_domain}</p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p className="text-zinc-300 text-sm">{device.user_name}</p>
                                                {device.userDeviceAssignment?.user && (
                                                    <p className="text-zinc-500 text-xs">{device.userDeviceAssignment.user.email}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-zinc-400 text-xs">{device.software?.name ?? '—'}</td>
                                            <td className="px-4 py-3 text-zinc-400 text-xs">{device.last_check_date}</td>
                                            <td className="px-4 py-3">
                                                <Badge className={`text-xs border capitalize ${statusColor[device.status] ?? 'bg-zinc-700/50 text-zinc-400'}`}>
                                                    {device.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button size="sm" variant="ghost"
                                                        className="text-zinc-400 hover:text-emerald-400 hover:bg-emerald-500/10 h-8 px-2 text-xs"
                                                        onClick={() => updateStatus(device, device.status === 'active' ? 'inactive' : 'active')}>
                                                        {device.status === 'active' ? <ToggleRight className="w-4 h-4" /> : <ToggleLeft className="w-4 h-4" />}
                                                    </Button>
                                                    <Button size="sm" variant="ghost"
                                                        className="text-red-400 hover:text-red-300 hover:bg-red-500/10 w-8 h-8 p-0"
                                                        onClick={() => destroy(device)}>
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
