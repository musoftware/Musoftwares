import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Separator } from '@/Components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import Pagination from '@/Components/Pagination';
import { Search, Trash2, ToggleLeft, ToggleRight, ArrowUpDown, ChevronUp, ChevronDown, Monitor } from 'lucide-react';

interface Device {
    id: number;
    device_id: string;
    machine_name: string;
    user_name: string;
    user_domain: string;
    status: string;
    os_version: string | null;
    framework_version: string | null;
    is_64bit_os: boolean | null;
    is_64bit_process: boolean | null;
    current_directory: string | null;
    current_culture: string | null;
    current_ui_culture: string | null;
    last_check_date: string | null;
    last_check_date_full: string | null;
    created_at: string | null;
    software?: { id: number; name: string } | null;
    userDeviceAssignment?: { user?: { id: number; name: string; email: string } } | null;
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

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    active:   'default',
    inactive: 'secondary',
    blocked:  'destructive',
};

function SortIcon({ column, currentSort, direction }: { column: string; currentSort: string; direction: string }) {
    if (currentSort !== column) return <ArrowUpDown className="w-3 h-3 ml-1 text-muted-foreground/50" />;
    return direction === 'asc'
        ? <ChevronUp className="w-3 h-3 ml-1" />
        : <ChevronDown className="w-3 h-3 ml-1" />;
}

export default function SerialDevicesIndex({ devices, filters, statuses, softwares, stats, perPageOptions }: Props) {
    const [search, setSearch]     = useState(filters.search ?? '');
    const [user, setUser]         = useState(filters.user ?? '');
    const [detail, setDetail]     = useState<Device | null>(null);

    const applyFilter = (key: string, value: string | number | null) => {
        router.get(
            route('admin.serial-devices.index'),
            { ...filters, [key]: value || undefined },
            { preserveState: true, replace: true }
        );
    };

    const applySort = (column: string) => {
        const isSame = filters.sort_by === column;
        const newDir = isSame && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            route('admin.serial-devices.index'),
            { ...filters, sort_by: column, direction: newDir, sort: undefined },
            { preserveState: true, replace: true }
        );
    };

    const updateStatus = (device: Device, status: string) => {
        router.patch(route('admin.serial-devices.status', device.id), { status });
    };

    const nextStatus = (current: string): string => {
        if (current === 'active') return 'inactive';
        if (current === 'inactive') return 'active';
        return 'active'; // blocked → active
    };

    const destroy = (device: Device) => {
        if (!confirm(`Delete device "${device.device_id}"?`)) return;
        router.delete(route('admin.serial-devices.destroy', device.id));
    };

    const SortTh = ({ column, children }: { column: string; children: React.ReactNode }) => (
        <TableHead
            className="cursor-pointer select-none hover:bg-muted/50 transition-colors"
            onClick={() => applySort(column)}
        >
            <span className="flex items-center">
                {children}
                <SortIcon column={column} currentSort={filters.sort_by ?? ''} direction={filters.direction ?? 'desc'} />
            </span>
        </TableHead>
    );

    return (
        <AdminSidebarLayout title="Serial Devices" header="Serial Devices">
            <Head title="Serial Devices" />

            <div className="p-6 space-y-6">

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Serial Devices</h1>
                    <p className="text-muted-foreground text-sm mt-1">All registered client machines</p>
                </div>

                <Separator />

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Total Devices', value: stats.total,    className: 'text-foreground' },
                        { label: 'Active',         value: stats.active,   className: 'text-green-600' },
                        { label: 'Inactive',       value: stats.inactive, className: 'text-muted-foreground' },
                    ].map(({ label, value, className }) => (
                        <Card key={label}>
                            <CardContent className="p-4">
                                <p className={`text-2xl font-bold ${className}`}>{value}</p>
                                <p className="text-xs text-muted-foreground mt-0.5">{label}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters Row */}
                <div className="flex flex-wrap gap-3">
                    {/* Device/Machine search */}
                    <div className="relative flex-1 min-w-48">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            className="pl-9"
                            placeholder="Device ID or machine name..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilter('search', search)}
                        />
                    </div>
                    {/* User search — was missing */}
                    <div className="relative min-w-40">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            className="pl-9"
                            placeholder="Username..."
                            value={user}
                            onChange={e => setUser(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilter('user', user)}
                        />
                    </div>
                    {/* Status */}
                    <Select onValueChange={v => applyFilter('status', v === 'all' ? null : v)} defaultValue={filters.status ?? 'all'}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            {statuses.map(s => (
                                <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {/* Software */}
                    <Select onValueChange={v => applyFilter('software_id', v)} defaultValue={String(filters.software_id ?? '')}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="All Software" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Software</SelectItem>
                            {softwares.map(sw => (
                                <SelectItem key={sw.id} value={String(sw.id)}>{sw.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {/* Per page — was missing */}
                    <Select
                        onValueChange={v => applyFilter('per_page', Number(v))}
                        defaultValue={String(filters.per_page ?? 20)}
                    >
                        <SelectTrigger className="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {perPageOptions.map(n => (
                                <SelectItem key={n} value={String(n)}>{n} / page</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <SortTh column="device_id">Device ID</SortTh>
                                    <SortTh column="machine_name">Machine</SortTh>
                                    <SortTh column="user_name">User</SortTh>
                                    <SortTh column="serial_software_id">Software</SortTh>
                                    <SortTh column="last_check_date">Last Check</SortTh>
                                    <SortTh column="status">Status</SortTh>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {devices.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-12 text-muted-foreground">
                                            No devices found.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {devices.data.map(device => (
                                    <TableRow
                                        key={device.id}
                                        className="cursor-pointer"
                                        onClick={() => setDetail(device)}
                                    >
                                        <TableCell className="font-mono text-xs" title={device.device_id}>
                                            {device.device_id.length > 20
                                                ? `${device.device_id.substring(0, 8)}...${device.device_id.slice(-8)}`
                                                : device.device_id}
                                        </TableCell>
                                        <TableCell>
                                            <p className="text-sm font-medium">{device.machine_name}</p>
                                            <p className="text-xs text-muted-foreground">{device.user_domain}</p>
                                        </TableCell>
                                        <TableCell>
                                            <p className="text-sm">{device.user_name}</p>
                                            {device.userDeviceAssignment?.user && (
                                                <Link
                                                    href={route('admin.users.show', device.userDeviceAssignment.user.id)}
                                                    className="text-xs text-blue-600 hover:underline"
                                                    onClick={e => e.stopPropagation()}
                                                >
                                                    {device.userDeviceAssignment.user.email}
                                                </Link>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs">
                                            {device.software?.name ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs" title={device.last_check_date_full ?? ''}>
                                            {device.last_check_date ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={statusVariant[device.status] ?? 'outline'} className="capitalize text-xs">
                                                {device.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell onClick={e => e.stopPropagation()}>
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 px-2 text-xs"
                                                    title={`Set ${nextStatus(device.status)}`}
                                                    onClick={() => updateStatus(device, nextStatus(device.status))}
                                                >
                                                    {device.status === 'active'
                                                        ? <ToggleRight className="w-4 h-4 text-green-600" />
                                                        : <ToggleLeft className="w-4 h-4 text-muted-foreground" />
                                                    }
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="text-red-600 hover:text-red-700 hover:bg-red-50 w-8 h-8 p-0"
                                                    onClick={() => destroy(device)}
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination — was missing */}
                <Pagination links={devices.links} />

            </div>

            {/* Device Detail Modal */}
            <Dialog open={!!detail} onOpenChange={open => !open && setDetail(null)}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Monitor className="w-4 h-4" />
                            Device Details
                        </DialogTitle>
                    </DialogHeader>
                    {detail && (
                        <div className="mt-6 space-y-4 text-sm">
                            <Row label="Device ID"        value={<span className="font-mono text-xs">{detail.device_id}</span>} />
                            <Row label="Machine Name"     value={detail.machine_name} />
                            <Row label="User Name"        value={detail.user_name} />
                            <Row label="User Domain"      value={detail.user_domain} />
                            <Row label="Software"         value={detail.software?.name ?? '—'} />
                            <Row label="Status"           value={
                                <Badge variant={statusVariant[detail.status] ?? 'outline'} className="capitalize text-xs">
                                    {detail.status}
                                </Badge>
                            } />
                            <Separator />
                            <Row label="OS Version"       value={detail.os_version ?? '—'} />
                            <Row label="Framework"        value={detail.framework_version ?? '—'} />
                            <Row label="64-bit OS"        value={detail.is_64bit_os == null ? '—' : detail.is_64bit_os ? 'Yes' : 'No'} />
                            <Row label="64-bit Process"   value={detail.is_64bit_process == null ? '—' : detail.is_64bit_process ? 'Yes' : 'No'} />
                            <Row label="Culture"          value={detail.current_culture ?? '—'} />
                            <Row label="UI Culture"       value={detail.current_ui_culture ?? '—'} />
                            <Row label="Directory"        value={<span className="font-mono text-xs break-all">{detail.current_directory ?? '—'}</span>} />
                            <Separator />
                            <Row label="Last Check"       value={detail.last_check_date_full ?? '—'} />
                            <Row label="Registered"       value={detail.created_at ?? '—'} />
                            {detail.userDeviceAssignment?.user && (
                                <>
                                    <Separator />
                                    <Row label="Linked User" value={
                                        <Link
                                            href={route('admin.users.show', detail.userDeviceAssignment.user.id)}
                                            className="text-blue-600 hover:underline"
                                        >
                                            {detail.userDeviceAssignment.user.name} ({detail.userDeviceAssignment.user.email})
                                        </Link>
                                    } />
                                </>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground shrink-0">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}
