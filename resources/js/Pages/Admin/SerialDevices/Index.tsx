import React, { useState, useMemo } from 'react';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import Pagination from '@/Components/Pagination';
import { __ } from '@/lib/i18n';
import {
    Search,
    ArrowUpDown,
    ChevronUp,
    ChevronDown,
    Monitor,
    MoreHorizontal,
    Download,
    ChevronRight,
    X,
    Trash2,
    Filter,
    BarChart3,
} from 'lucide-react';

/* ─── Types ─────────────────────────────────────────────────────── */

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
interface DevicePerSoftware { id: number; name: string; devices_count: number; }
interface Stats {
    total: number;
    active: number;
    inactive: number;
    blocked: number;
    checked_in_today: number;
    new_this_week: number;
    new_this_month: number;
    never_checked_in: number;
    devices_per_software: DevicePerSoftware[];
}

interface Props {
    devices: { data: Device[]; links: any[]; meta: any };
    filters: Record<string, any>;
    statuses: string[];
    softwares: Software[];
    stats: Stats;
    perPageOptions: number[];
    osVersions: string[];
}

/* ─── Helpers ───────────────────────────────────────────────────── */

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

function truncateId(id: string, max = 20) {
    if (id.length <= max) return id;
    return `${id.substring(0, 8)}…${id.slice(-8)}`;
}

/* ─── Main Component ────────────────────────────────────────────── */

export default function SerialDevicesIndex({ devices, filters, statuses, softwares, stats, perPageOptions, osVersions }: Props) {
    const [search, setSearch]                     = useState(filters.search ?? '');
    const [user, setUser]                         = useState(filters.user ?? '');
    const [detail, setDetail]                     = useState<Device | null>(null);
    const [selectedIds, setSelectedIds]            = useState<number[]>([]);
    const [showAdvanced, setShowAdvanced]          = useState(false);
    const [showReports, setShowReports]            = useState(false);
    const [deleteConfirmId, setDeleteConfirmId]    = useState<number | null>(null);
    const [bulkDeleteConfirm, setBulkDeleteConfirm] = useState(false);
    const [bulkAction, setBulkAction]              = useState<string>('');

    /* ── Filter helpers ──────────────────────────────────────────── */

    const applyFilter = (key: string, value: string | number | null) => {
        router.get(
            route('admin.serial-devices.index'),
            { ...filters, [key]: value || undefined, page: 1 },
            { preserveState: true, replace: true },
        );
    };

    const applyFilters = (updates: Record<string, string | number | null>) => {
        const merged: Record<string, any> = { ...filters, ...updates, page: 1 };
        Object.keys(merged).forEach(k => { if (!merged[k]) merged[k] = undefined; });
        router.get(route('admin.serial-devices.index'), merged, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setSearch('');
        setUser('');
        router.get(route('admin.serial-devices.index'), {}, { preserveState: false });
    };

    const hasActiveFilters = useMemo(() => {
        return !!(
            filters.search || filters.user || filters.status || filters.software_id ||
            filters.date_from || filters.date_to || filters.last_check_from || filters.last_check_to ||
            filters.os_version || (filters.is_64bit !== undefined && filters.is_64bit !== null && filters.is_64bit !== '') ||
            filters.has_user
        );
    }, [filters]);

    /* ── Sort helpers ────────────────────────────────────────────── */

    const applySort = (column: string) => {
        const isSame = filters.sort_by === column;
        const newDir = isSame && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            route('admin.serial-devices.index'),
            { ...filters, sort_by: column, direction: newDir, sort: undefined },
            { preserveState: true, replace: true },
        );
    };

    const SortTh = ({ column, children, className }: { column: string; children: React.ReactNode; className?: string }) => (
        <TableHead
            className={`cursor-pointer select-none hover:bg-muted/50 transition-colors ${className ?? ''}`}
            onClick={() => applySort(column)}
        >
            <span className="flex items-center">
                {children}
                <SortIcon column={column} currentSort={filters.sort_by ?? ''} direction={filters.direction ?? 'desc'} />
            </span>
        </TableHead>
    );

    /* ── Row actions ─────────────────────────────────────────────── */

    const updateStatus = (device: Device, status: string) => {
        router.patch(route('admin.serial-devices.status', device.id), { status }, { preserveState: true });
    };

    const destroy = (device: Device) => {
        router.delete(route('admin.serial-devices.destroy', device.id), { preserveState: true });
        setDeleteConfirmId(null);
    };

    /* ── Bulk actions ────────────────────────────────────────────── */

    const allOnPageSelected = devices.data.length > 0 && devices.data.every(d => selectedIds.includes(d.id));

    const toggleAll = () => {
        if (allOnPageSelected) {
            setSelectedIds(prev => prev.filter(id => !devices.data.some(d => d.id === id)));
        } else {
            setSelectedIds(prev => [...new Set([...prev, ...devices.data.map(d => d.id)])]);
        }
    };

    const toggleOne = (id: number) => {
        setSelectedIds(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
    };

    const applyBulkAction = () => {
        if (selectedIds.length === 0) return;
        if (!bulkAction) {
            alert(__('general.please_select_a_bulk_action_first'));
            return;
        }

        if (bulkAction === 'delete') {
            setBulkDeleteConfirm(true);
            return;
        }

        router.post(route('admin.serial-devices.bulk-status'), { ids: selectedIds, status: bulkAction }, {
            preserveState: true,
            onSuccess: () => {
                setSelectedIds([]);
                setBulkAction('');
            },
        });
    };

    const bulkDelete = () => {
        if (selectedIds.length === 0) return;
        router.post(route('admin.serial-devices.bulk-delete'), { ids: selectedIds }, {
            preserveState: true,
            onSuccess: () => { 
                setSelectedIds([]); 
                setBulkDeleteConfirm(false); 
                setBulkAction('');
            },
        });
    };

    /* ── Export URL ───────────────────────────────────────────────── */

    const exportUrl = useMemo(() => {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, String(v)); });
        return route('admin.serial-devices.export') + (params.toString() ? `?${params}` : '');
    }, [filters]);

    /* ── Stats cards ─────────────────────────────────────────────── */

    const statsCards = [
        { label: __('general.total_devices'), value: stats.total, onClick: () => clearFilters() },
        { label: __('Active'), value: stats.active, className: 'text-green-600', onClick: () => applyFilter('status', 'active') },
        { label: __('Inactive'), value: stats.inactive, className: 'text-muted-foreground', onClick: () => applyFilter('status', 'inactive') },
        { label: __('general.blocked'), value: stats.blocked, className: 'text-red-600', onClick: () => applyFilter('status', 'blocked') },
        { label: __('general.checked_in_today'), value: stats.checked_in_today, onClick: () => {} },
        { label: __('general.new_this_week'), value: stats.new_this_week, onClick: () => {} },
    ];

    /* ── Reports: devices per software ───────────────────────────── */

    const maxDeviceCount = Math.max(1, ...stats.devices_per_software.map(s => s.devices_count));

    /* ── Render ──────────────────────────────────────────────────── */

    return (
        <AdminSidebarLayout title={__('general.serial_devices')} header={__('general.serial_devices')}>
            <Head title={__('general.serial_devices')} />

            <div className="p-4 md:p-6 space-y-6">

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{__('general.serial_devices')}</h1>
                        <p className="text-muted-foreground text-sm mt-0.5">{__('general.all_registered_client_machines')}</p>
                    </div>
                    <a href={exportUrl} className="inline-flex">
                        <Button variant="outline" size="sm">
                            <Download className="w-4 h-4 mr-1.5" />
                            {__('general.export_csv')}
                        </Button>
                    </a>
                </div>

                <Separator />

                {/* Stats Cards */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    {statsCards.map(({ label, value, className, onClick }) => (
                        <Card
                            key={label}
                            className="cursor-pointer hover:ring-1 hover:ring-foreground/10 transition-shadow"
                            onClick={onClick}
                        >
                            <CardContent className="p-4">
                                <p className={`text-2xl font-bold ${className ?? 'text-foreground'}`}>{value}</p>
                                <p className="text-xs text-muted-foreground mt-0.5">{label}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Basic Filters */}
                <div className="flex flex-wrap gap-3">
                    {/* Device/Machine search */}
                    <div className="relative flex-1 min-w-48">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            className="pl-9"
                            placeholder={__('general.device_id_or_machine_name')}
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilter('search', search)}
                        />
                    </div>
                    {/* User search */}
                    <div className="relative min-w-40">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            className="pl-9"
                            placeholder={__('Username...')}
                            value={user}
                            onChange={e => setUser(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && applyFilter('user', user)}
                        />
                    </div>
                    {/* Status */}
                    <Select onValueChange={v => applyFilter('status', v === 'all' ? null : v)} value={filters.status ?? 'all'}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder={__('Status')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{__('general.all_statuses')}</SelectItem>
                            {statuses.map(s => (
                                <SelectItem key={s} value={s} className="capitalize">{__(s.charAt(0).toUpperCase() + s.slice(1))}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {/* Software */}
                    <Select onValueChange={v => applyFilter('software_id', v || null)} value={String(filters.software_id ?? '')}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder={__('general.all_software')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">{__('general.all_software')}</SelectItem>
                            {softwares.map(sw => (
                                <SelectItem key={sw.id} value={String(sw.id)}>{sw.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {/* Per page */}
                    <Select onValueChange={v => applyFilter('per_page', Number(v))} value={String(filters.per_page ?? 20)}>
                        <SelectTrigger className="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {perPageOptions.map(n => (
                                <SelectItem key={n} value={String(n)}>{n} / {__('general.page')}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {/* Toggle advanced */}
                    <Button variant="outline" size="sm" onClick={() => setShowAdvanced(p => !p)} className="gap-1.5">
                        <Filter className="w-3.5 h-3.5" />
                        {showAdvanced ? __('general.hide_filters') : __('general.more_filters')}
                    </Button>

                    {/* Clear filters */}
                    {hasActiveFilters && (
                        <Button variant="ghost" size="sm" onClick={clearFilters} className="gap-1.5 text-muted-foreground">
                            <X className="w-3.5 h-3.5" />
                            {__('Clear Filters')}
                        </Button>
                    )}
                </div>

                {/* Advanced Filters (collapsible) */}
                {showAdvanced && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 p-4 border rounded-lg bg-muted/30">
                        {/* Date From */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.registered_from')}</label>
                            <input
                                type="date"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={filters.date_from ?? ''}
                                onChange={e => applyFilter('date_from', e.target.value || null)}
                            />
                        </div>
                        {/* Date To */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.registered_to')}</label>
                            <input
                                type="date"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={filters.date_to ?? ''}
                                onChange={e => applyFilter('date_to', e.target.value || null)}
                            />
                        </div>
                        {/* Last Check From */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.last_check_from')}</label>
                            <input
                                type="date"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={filters.last_check_from ?? ''}
                                onChange={e => applyFilter('last_check_from', e.target.value || null)}
                            />
                        </div>
                        {/* Last Check To */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.last_check_to')}</label>
                            <input
                                type="date"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={filters.last_check_to ?? ''}
                                onChange={e => applyFilter('last_check_to', e.target.value || null)}
                            />
                        </div>
                        {/* OS Version */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.os_version')}</label>
                            <Select onValueChange={v => applyFilter('os_version', v || null)} value={filters.os_version ?? ''}>
                                <SelectTrigger>
                                    <SelectValue placeholder={__('All')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">{__('All')}</SelectItem>
                                    {osVersions.map(os => (
                                        <SelectItem key={os} value={os}>{os}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        {/* 64-bit */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.64_bit_os')}</label>
                            <Select onValueChange={v => applyFilter('is_64bit', v || null)} value={filters.is_64bit !== undefined && filters.is_64bit !== null && filters.is_64bit !== '' ? String(filters.is_64bit) : ''}>
                                <SelectTrigger>
                                    <SelectValue placeholder={__('All')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">{__('All')}</SelectItem>
                                    <SelectItem value="1">{__('general.yes')}</SelectItem>
                                    <SelectItem value="0">{__('No')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        {/* Has Linked User */}
                        <div>
                            <label className="text-xs text-muted-foreground mb-1 block">{__('general.has_linked_user')}</label>
                            <Select onValueChange={v => applyFilter('has_user', v || null)} value={filters.has_user ?? ''}>
                                <SelectTrigger>
                                    <SelectValue placeholder={__('All')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">{__('All')}</SelectItem>
                                    <SelectItem value="yes">{__('general.yes')}</SelectItem>
                                    <SelectItem value="no">{__('No')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                )}

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10">
                                            <input
                                                type="checkbox"
                                                checked={allOnPageSelected}
                                                onChange={toggleAll}
                                                className="rounded border-muted-foreground/30"
                                            />
                                        </TableHead>
                                        <SortTh column="device_id">{__('general.device_id')}</SortTh>
                                        <SortTh column="machine_name">{__('general.machine')}</SortTh>
                                        <SortTh column="user_name">{__('User')}</SortTh>
                                        <SortTh column="serial_software_id">{__('general.software')}</SortTh>
                                        <SortTh column="last_check_date">{__('general.last_check')}</SortTh>
                                        <SortTh column="status">{__('Status')}</SortTh>
                                        <TableHead className="text-right w-12">{__('Actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {devices.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-12 text-muted-foreground">
                                                {__('general.no_devices_found')}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {devices.data.map(device => (
                                        <TableRow
                                            key={device.id}
                                            className={`cursor-pointer transition-colors ${selectedIds.includes(device.id) ? 'bg-muted/40' : ''}`}
                                            onClick={() => setDetail(device)}
                                        >
                                            <TableCell onClick={e => e.stopPropagation()}>
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(device.id)}
                                                    onChange={() => toggleOne(device.id)}
                                                    className="rounded border-muted-foreground/30"
                                                />
                                            </TableCell>
                                            <TableCell className="font-mono text-xs" title={device.device_id}>
                                                {truncateId(device.device_id)}
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
                                                    {__(device.status.charAt(0).toUpperCase() + device.status.slice(1))}
                                                </Badge>
                                            </TableCell>
                                            <TableCell onClick={e => e.stopPropagation()} className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger render={<Button variant="ghost" className="h-8 w-8 p-0" />}>
                                                        <span className="sr-only">{__('Open menu')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" side="bottom">
                                                        <DropdownMenuItem onClick={() => setDetail(device)}>
                                                            <Monitor className="w-4 h-4 mr-2" />
                                                            {__('general.view_details')}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        {device.status !== 'active' && (
                                                            <DropdownMenuItem onClick={() => updateStatus(device, 'active')}>
                                                                {__('general.set_active')}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {device.status !== 'inactive' && (
                                                            <DropdownMenuItem onClick={() => updateStatus(device, 'inactive')}>
                                                                {__('general.set_inactive')}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {device.status !== 'blocked' && (
                                                            <DropdownMenuItem onClick={() => updateStatus(device, 'blocked')}>
                                                                {__('general.set_blocked')}
                                                            </DropdownMenuItem>
                                                        )}
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            variant="destructive"
                                                            onClick={() => setDeleteConfirmId(device.id)}
                                                        >
                                                            <Trash2 className="w-4 h-4 mr-2" />
                                                            {__('Delete')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Footer Actions & Pagination */}
                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    {/* Bulk Action Bar */}
                    <div className="w-full md:w-auto">
                        {selectedIds.length > 0 && (
                            <div className="flex flex-wrap items-center gap-2 p-3 border rounded-lg bg-white shadow-sm">
                                <span className="text-sm font-medium">
                                    {__(':count selected', { count: String(selectedIds.length) })}
                                </span>
                                <Separator orientation="vertical" className="h-5" />
                                <div className="w-48">
                                    <Select value={bulkAction} onValueChange={(val) => setBulkAction(val)}>
                                        <SelectTrigger className="h-9">
                                            <SelectValue placeholder={__('general.bulk_actions')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">{__('general.set_active')}</SelectItem>
                                            <SelectItem value="inactive">{__('general.set_inactive')}</SelectItem>
                                            <SelectItem value="blocked">{__('general.set_blocked')}</SelectItem>
                                            <SelectItem value="delete" className="text-red-600 focus:text-red-600 focus:bg-red-50">{__('general.delete_selected')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button onClick={applyBulkAction} size="sm" className="h-9">
                                    {__('Apply')}
                                </Button>
                                <Button size="sm" variant="ghost" className="text-muted-foreground" onClick={() => { setSelectedIds([]); setBulkAction(''); }}>
                                    {__('general.deselect_all')}
                                </Button>
                            </div>
                        )}
                    </div>
                    
                    {/* Pagination */}
                    <div className="w-full md:w-auto">
                        <Pagination links={devices.links} />
                    </div>
                </div>

                {/* Reports Panel (collapsible) */}
                <div className="border rounded-lg">
                    <button
                        type="button"
                        className="w-full flex items-center justify-between p-4 text-sm font-medium hover:bg-muted/30 transition-colors"
                        onClick={() => setShowReports(p => !p)}
                    >
                        <span className="flex items-center gap-2">
                            <BarChart3 className="w-4 h-4" />
                            {__('general.devices_per_software')}
                        </span>
                        <ChevronRight className={`w-4 h-4 transition-transform ${showReports ? 'rotate-90' : ''}`} />
                    </button>
                    {showReports && (
                        <div className="px-4 pb-4 space-y-2">
                            <Separator />
                            {stats.devices_per_software.length === 0 && (
                                <p className="text-sm text-muted-foreground py-2">{__('general.no_data_available')}</p>
                            )}
                            {stats.devices_per_software.map(sw => (
                                <div key={sw.id} className="flex items-center gap-3">
                                    <span className="text-sm min-w-32 truncate">{sw.name}</span>
                                    <div className="flex-1 bg-muted rounded-full h-2.5 overflow-hidden">
                                        <div
                                            className="bg-foreground/70 h-full rounded-full transition-all"
                                            style={{ width: `${(sw.devices_count / maxDeviceCount) * 100}%` }}
                                        />
                                    </div>
                                    <span className="text-xs text-muted-foreground font-mono w-10 text-right">{sw.devices_count}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Device Detail Dialog */}
            <Dialog open={!!detail} onOpenChange={open => !open && setDetail(null)}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Monitor className="w-4 h-4" />
                            {__('general.device_details')}
                        </DialogTitle>
                    </DialogHeader>
                    {detail && (
                        <div className="mt-6 space-y-4 text-sm">
                            <Row label={__('general.device_id')}    value={<span className="font-mono text-xs">{detail.device_id}</span>} />
                            <Row label={__('general.machine_name')} value={detail.machine_name} />
                            <Row label={__('general.user_name')}    value={detail.user_name} />
                            <Row label={__('general.user_domain')}  value={detail.user_domain} />
                            <Row label={__('general.software')}     value={detail.software?.name ?? '—'} />
                            <Row label={__('Status')} value={
                                <Badge variant={statusVariant[detail.status] ?? 'outline'} className="capitalize text-xs">
                                    {__(detail.status.charAt(0).toUpperCase() + detail.status.slice(1))}
                                </Badge>
                            } />
                            <Separator />
                            <Row label={__('general.os_version')}     value={detail.os_version ?? '—'} />
                            <Row label={__('general.framework')}      value={detail.framework_version ?? '—'} />
                            <Row label={__('general.64_bit_os')}      value={detail.is_64bit_os == null ? '—' : detail.is_64bit_os ? __('general.yes') : __('No')} />
                            <Row label={__('general.64_bit_process')} value={detail.is_64bit_process == null ? '—' : detail.is_64bit_process ? __('general.yes') : __('No')} />
                            <Row label={__('general.culture')}        value={detail.current_culture ?? '—'} />
                            <Row label={__('general.ui_culture')}     value={detail.current_ui_culture ?? '—'} />
                            <Row label={__('general.directory')}      value={<span className="font-mono text-xs break-all">{detail.current_directory ?? '—'}</span>} />
                            <Separator />
                            <Row label={__('general.last_check')}  value={detail.last_check_date_full ?? '—'} />
                            <Row label={__('general.registered')}  value={detail.created_at ?? '—'} />
                            {detail.userDeviceAssignment?.user && (
                                <>
                                    <Separator />
                                    <Row label={__('general.linked_user')} value={
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

            {/* Delete Confirm Dialog */}
            <Dialog open={deleteConfirmId !== null} onOpenChange={open => !open && setDeleteConfirmId(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>{__('general.confirm_delete')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground mt-2">
                        {__('general.are_you_sure_you_want_to_delete_this_device_this_action_cannot_be_undone')}
                    </p>
                    <div className="flex justify-end gap-2 mt-4">
                        <Button variant="outline" size="sm" onClick={() => setDeleteConfirmId(null)}>
                            {__('Cancel')}
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => {
                                const device = devices.data.find(d => d.id === deleteConfirmId);
                                if (device) destroy(device);
                            }}
                        >
                            {__('Delete')}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Bulk Delete Confirm Dialog */}
            <Dialog open={bulkDeleteConfirm} onOpenChange={open => !open && setBulkDeleteConfirm(false)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>{__('general.confirm_bulk_delete')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground mt-2">
                        {__('Are you sure you want to delete :count device(s)? This action cannot be undone.', { count: String(selectedIds.length) })}
                    </p>
                    <div className="flex justify-end gap-2 mt-4">
                        <Button variant="outline" size="sm" onClick={() => setBulkDeleteConfirm(false)}>
                            {__('Cancel')}
                        </Button>
                        <Button variant="destructive" size="sm" onClick={bulkDelete}>
                            {__('general.delete_all')}
                        </Button>
                    </div>
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
