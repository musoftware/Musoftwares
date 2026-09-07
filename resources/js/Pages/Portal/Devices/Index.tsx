import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Laptop,
    Plus,
    Search,
    RefreshCw,
    Clock,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Calendar,
    MoreVertical,
    Trash2,
    Shield,
    Layers,
    User,
    ChevronRight,
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface AllocatedSoftware {
    id: number;
    serial_software_id: number;
    software_name: string;
    max_devices: number | null;
    active_devices_count: number;
    remaining_quota: number | null;
    is_unlimited: boolean;
    status: string;
}

interface Stats {
    total_softwares: number;
    total_devices: number;
    active_devices: number;
    expired_devices: number;
    expiring_soon: number;
}

interface AvailableDevice {
    id: number;
    serial_software_id: number;
    device_id: string;
    machine_name: string | null;
    user_name: string | null;
    os_version: string | null;
    last_check_date: string | null;
    software?: {
        id: number;
        name: string;
    };
}

interface DeviceAssignment {
    id: number;
    user_id: number;
    reseller_id: number;
    device_id: string;
    status: 'active' | 'inactive';
    expires_at: string | null;
    notes: string | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
    };
    devices?: Array<{
        id: number;
        serial_software_id: number;
        device_id: string;
        machine_name: string | null;
        user_name: string | null;
        os_version: string | null;
        last_check_date: string | null;
        software?: {
            id: number;
            name: string;
        };
    }>;
}

interface Props {
    allocatedSoftwares: AllocatedSoftware[];
    stats: Stats;
    devices: {
        data: DeviceAssignment[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
    availableDevices: AvailableDevice[];
    filters: {
        search?: string;
        software_id?: string;
        status?: string;
        per_page?: number;
    };
}

export default function ResellerDevicesIndex({
    allocatedSoftwares,
    stats,
    devices,
    availableDevices,
    filters,
}: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedSoftware, setSelectedSoftware] = useState(filters.software_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    // Modals
    const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
    const [renewModalDevice, setRenewModalDevice] = useState<DeviceAssignment | null>(null);

    // Form for assigning new device
    const assignForm = useForm({
        serial_software_id: allocatedSoftwares[0]?.serial_software_id ? String(allocatedSoftwares[0].serial_software_id) : '',
        device_id: '',
        customer_user_id: '',
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        duration_preset: '1_month',
        custom_expires_at: '',
        notes: '',
    });

    // Form for renewing
    const renewForm = useForm({
        duration_preset: '1_month',
        custom_expires_at: '',
    });

    const handleFilter = (key: string, value: string) => {
        const newFilters = {
            search: searchTerm,
            software_id: selectedSoftware,
            status: selectedStatus,
            [key]: value,
        };

        router.get(window.location.pathname, newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        handleFilter('search', searchTerm);
    };

    const handleAssignSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        assignForm.post('/portal/devices', {
            onSuccess: () => {
                setIsAssignModalOpen(false);
                assignForm.reset();
            },
        });
    };

    const handleQuickRenew = (device: DeviceAssignment) => {
        if (confirm(`Extend license by 1 Month for customer ${device.user?.name || device.device_id}?`)) {
            router.post(`/portal/devices/${device.id}/renew`, {
                duration_preset: '1_month',
            });
        }
    };

    const handleRenewSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!renewModalDevice) return;

        renewForm.post(`/portal/devices/${renewModalDevice.id}/renew`, {
            onSuccess: () => {
                setRenewModalDevice(null);
                renewForm.reset();
            },
        });
    };

    const handleToggleStatus = (device: DeviceAssignment) => {
        const nextStatus = device.status === 'active' ? 'inactive' : 'active';
        router.patch(`/portal/devices/${device.id}/status`, {
            status: nextStatus,
        });
    };

    const handleUnassign = (device: DeviceAssignment) => {
        if (confirm(`Are you sure you want to unassign device ${device.device_id}? The customer will lose software access.`)) {
            router.delete(`/portal/devices/${device.id}`);
        }
    };

    // Calculate expiry indicator
    const getExpiryInfo = (expiresAtStr: string | null, status: string) => {
        if (!expiresAtStr) {
            return {
                label: 'Lifetime',
                isExpired: false,
                isExpiringSoon: false,
                colorClass: 'text-zinc-600 dark:text-zinc-400',
            };
        }

        const expiryDate = new Date(expiresAtStr);
        const now = new Date();
        const diffMs = expiryDate.getTime() - now.getTime();
        const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) {
            return {
                label: `Expired (${expiryDate.toLocaleDateString()})`,
                isExpired: true,
                isExpiringSoon: false,
                colorClass: 'text-red-600 dark:text-red-400 font-semibold',
            };
        }

        if (diffDays <= 7) {
            return {
                label: `${diffDays} days left (${expiryDate.toLocaleDateString()})`,
                isExpired: false,
                isExpiringSoon: true,
                colorClass: 'text-amber-600 dark:text-amber-400 font-medium',
            };
        }

        return {
            label: `${diffDays} days left (${expiryDate.toLocaleDateString()})`,
            isExpired: false,
            isExpiringSoon: false,
            colorClass: 'text-zinc-700 dark:text-zinc-300',
        };
    };

    return (
        <AuthenticatedLayout>
            <Head title="Software Devices & Reseller Portal" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header Banner */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-6">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">
                            <Shield className="w-4 h-4 text-[#0071e3]" />
                            <span>Reseller Portal</span>
                        </div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            Software Devices & License Control
                        </h1>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            Manage customer software assignments, control device activations, and renew 1-month or multi-month licenses.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            onClick={() => setIsAssignModalOpen(true)}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white shadow-sm font-medium flex items-center gap-2"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Assign Device</span>
                        </Button>
                    </div>
                </div>

                {/* Top Metrics Cards */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm">
                        <div className="text-xs font-medium text-zinc-500">Allocated Software</div>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {stats.total_softwares}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm">
                        <div className="text-xs font-medium text-zinc-500">Total Devices</div>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {stats.total_devices}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm">
                        <div className="text-xs font-medium text-emerald-600 dark:text-emerald-400">Active Devices</div>
                        <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                            {stats.active_devices}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm">
                        <div className="text-xs font-medium text-amber-600 dark:text-amber-400">Expiring (&lt; 7 Days)</div>
                        <div className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {stats.expiring_soon}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm col-span-2 lg:col-span-1">
                        <div className="text-xs font-medium text-red-600 dark:text-red-400">Expired Licenses</div>
                        <div className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                            {stats.expired_devices}
                        </div>
                    </div>
                </div>

                {/* Allocated Softwares Breakdown */}
                {allocatedSoftwares.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 flex items-center gap-2">
                            <Layers className="w-4 h-4 text-zinc-500" />
                            <span>My Software Quotas</span>
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {allocatedSoftwares.map((software) => (
                                <div
                                    key={software.id}
                                    className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm space-y-2"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                            {software.software_name}
                                        </span>
                                        <span className="text-xs font-medium px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                            {software.status}
                                        </span>
                                    </div>
                                    <div className="flex items-baseline justify-between text-sm text-zinc-500 dark:text-zinc-400">
                                        <span>Active / Quota</span>
                                        <span className="font-medium text-zinc-900 dark:text-zinc-100">
                                            {software.active_devices_count} / {software.is_unlimited ? 'Unlimited' : software.max_devices}
                                        </span>
                                    </div>
                                    {!software.is_unlimited && software.max_devices && (
                                        <div className="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                                            <div
                                                className="bg-[#0071e3] h-2 rounded-full transition-all"
                                                style={{
                                                    width: `${Math.min(100, Math.round((software.active_devices_count / software.max_devices) * 100))}%`,
                                                }}
                                            />
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Filters & Search Toolbar */}
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-zinc-900/70 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <form onSubmit={handleSearchSubmit} className="relative flex-1 max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" />
                        <Input
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search by customer name, email, or device ID..."
                            className="pl-9 h-10 text-sm bg-zinc-50 dark:bg-zinc-800/50 border-zinc-200 dark:border-zinc-700"
                        />
                    </form>

                    <div className="flex items-center gap-2">
                        {allocatedSoftwares.length > 1 && (
                            <select
                                value={selectedSoftware}
                                onChange={(e) => {
                                    setSelectedSoftware(e.target.value);
                                    handleFilter('software_id', e.target.value);
                                }}
                                className="h-10 text-sm rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3"
                            >
                                <option value="">All Software</option>
                                {allocatedSoftwares.map((sw) => (
                                    <option key={sw.id} value={sw.serial_software_id}>
                                        {sw.software_name}
                                    </option>
                                ))}
                            </select>
                        )}

                        <select
                            value={selectedStatus}
                            onChange={(e) => {
                                setSelectedStatus(e.target.value);
                                handleFilter('status', e.target.value);
                            }}
                            className="h-10 text-sm rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active (Non-Expired)</option>
                            <option value="expiring_soon">Expiring Soon (&lt; 7 Days)</option>
                            <option value="expired">Expired</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                {/* Device Table */}
                <div className="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    <th className="px-5 py-3">Customer</th>
                                    <th className="px-5 py-3">Software & Device</th>
                                    <th className="px-5 py-3">Expiration & Term</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                {devices.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-5 py-12 text-center text-zinc-500">
                                            <Laptop className="w-8 h-8 mx-auto text-zinc-400 mb-2" />
                                            <p className="font-medium text-zinc-700 dark:text-zinc-300">No devices found</p>
                                            <p className="text-xs mt-1">Assign software to your customers using the button above.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    devices.data.map((device) => {
                                        const expiryInfo = getExpiryInfo(device.expires_at, device.status);
                                        const softwareName = device.devices?.[0]?.software?.name || 'Assigned Software';
                                        const machineName = device.devices?.[0]?.machine_name;
                                        const lastCheck = device.devices?.[0]?.last_check_date;

                                        return (
                                            <tr key={device.id} className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                                <td className="px-5 py-4">
                                                    <div className="font-medium text-zinc-900 dark:text-zinc-100">
                                                        {device.user?.name || 'Unknown User'}
                                                    </div>
                                                    <div className="text-xs text-zinc-500 dark:text-zinc-400">
                                                        {device.user?.email}
                                                    </div>
                                                    {device.user?.phone && (
                                                        <div className="text-xs text-zinc-400 font-mono mt-0.5">
                                                            {device.user.phone}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className="font-medium text-zinc-900 dark:text-zinc-100">
                                                        {softwareName}
                                                    </div>
                                                    <div className="text-xs font-mono text-zinc-500 break-all max-w-xs">
                                                        {device.device_id}
                                                    </div>
                                                    {machineName && (
                                                        <div className="text-xs text-zinc-400 mt-0.5">
                                                            Machine: {machineName}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className={`text-xs flex items-center gap-1.5 ${expiryInfo.colorClass}`}>
                                                        {expiryInfo.isExpired ? (
                                                            <XCircle className="w-3.5 h-3.5 text-red-500 shrink-0" />
                                                        ) : expiryInfo.isExpiringSoon ? (
                                                            <AlertTriangle className="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                        ) : (
                                                            <Clock className="w-3.5 h-3.5 text-zinc-400 shrink-0" />
                                                        )}
                                                        <span>{expiryInfo.label}</span>
                                                    </div>
                                                    {lastCheck && (
                                                        <div className="text-[11px] text-zinc-400 mt-1">
                                                            Last check: {new Date(lastCheck).toLocaleDateString()}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    {device.status === 'active' && !expiryInfo.isExpired ? (
                                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 px-2 py-0.5 rounded-md">
                                                            <CheckCircle2 className="w-3 h-3" /> Active
                                                        </span>
                                                    ) : expiryInfo.isExpired ? (
                                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 px-2 py-0.5 rounded-md">
                                                            <XCircle className="w-3 h-3" /> Expired
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-2 py-0.5 rounded-md">
                                                            Inactive
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {/* 1-Click +1 Month Renew button */}
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handleQuickRenew(device)}
                                                            className="h-8 text-xs font-medium border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50"
                                                        >
                                                            <RefreshCw className="w-3.5 h-3.5 mr-1" />
                                                            +1 Month
                                                        </Button>

                                                        {/* More Options Dropdown */}
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button size="sm" variant="ghost" className="h-8 w-8 p-0">
                                                                    <MoreVertical className="w-4 h-4 text-zinc-500" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end" className="w-48">
                                                                <DropdownMenuItem
                                                                    onClick={() => {
                                                                        setRenewModalDevice(device);
                                                                        renewForm.setData('duration_preset', '1_month');
                                                                    }}
                                                                    className="text-xs cursor-pointer"
                                                                >
                                                                    <Calendar className="w-3.5 h-3.5 mr-2 text-zinc-500" />
                                                                    Custom Renewal Term...
                                                                </DropdownMenuItem>

                                                                <DropdownMenuItem
                                                                    onClick={() => handleToggleStatus(device)}
                                                                    className="text-xs cursor-pointer"
                                                                >
                                                                    {device.status === 'active' ? (
                                                                        <>
                                                                            <XCircle className="w-3.5 h-3.5 mr-2 text-amber-500" />
                                                                            Deactivate Device
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <CheckCircle2 className="w-3.5 h-3.5 mr-2 text-emerald-500" />
                                                                            Activate Device
                                                                        </>
                                                                    )}
                                                                </DropdownMenuItem>

                                                                <DropdownMenuItem
                                                                    onClick={() => handleUnassign(device)}
                                                                    className="text-xs text-red-600 dark:text-red-400 cursor-pointer"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5 mr-2" />
                                                                    Unassign Device
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {devices.total > 20 && (
                        <div className="flex items-center justify-between px-5 py-3 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500">
                            <span>Showing {devices.data.length} of {devices.total} device assignments</span>
                            <div className="flex items-center gap-1">
                                {devices.links.map((link: any, idx: number) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 rounded border text-xs ${
                                            link.active
                                                ? 'bg-[#0071e3] text-white border-[#0071e3]'
                                                : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        } disabled:opacity-40 disabled:cursor-not-allowed`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Assign Device Dialog */}
            <Dialog open={isAssignModalOpen} onOpenChange={setIsAssignModalOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">Assign Software Device</DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Allocate a software license to an end-user customer device with an expiration duration.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAssignSubmit} className="space-y-4 pt-2">
                        {/* Select Software */}
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Software Product</Label>
                            <select
                                value={assignForm.data.serial_software_id}
                                onChange={(e) => assignForm.setData('serial_software_id', e.target.value)}
                                className="w-full h-10 text-sm rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3"
                                required
                            >
                                {allocatedSoftwares.map((sw) => (
                                    <option key={sw.id} value={sw.serial_software_id}>
                                        {sw.software_name} (Remaining Quota: {sw.is_unlimited ? 'Unlimited' : sw.remaining_quota})
                                    </option>
                                ))}
                            </select>
                            {assignForm.errors.serial_software_id && (
                                <p className="text-xs text-red-600">{assignForm.errors.serial_software_id}</p>
                            )}
                        </div>

                        {/* Customer Information */}
                        <div className="space-y-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <Label className="text-xs font-semibold">Customer Details</Label>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div className="space-y-1">
                                    <Input
                                        placeholder="Full Name"
                                        value={assignForm.data.customer_name}
                                        onChange={(e) => assignForm.setData('customer_name', e.target.value)}
                                        required
                                        className="h-9 text-xs"
                                    />
                                    {assignForm.errors.customer_name && (
                                        <p className="text-[11px] text-red-600">{assignForm.errors.customer_name}</p>
                                    )}
                                </div>
                                <div className="space-y-1">
                                    <Input
                                        type="email"
                                        placeholder="Email Address"
                                        value={assignForm.data.customer_email}
                                        onChange={(e) => assignForm.setData('customer_email', e.target.value)}
                                        required
                                        className="h-9 text-xs"
                                    />
                                    {assignForm.errors.customer_email && (
                                        <p className="text-[11px] text-red-600">{assignForm.errors.customer_email}</p>
                                    )}
                                </div>
                            </div>
                            <div className="space-y-1">
                                <Input
                                    placeholder="Phone / WhatsApp Number (Optional)"
                                    value={assignForm.data.customer_phone}
                                    onChange={(e) => assignForm.setData('customer_phone', e.target.value)}
                                    className="h-9 text-xs"
                                />
                            </div>
                        </div>

                        {/* Device ID Selection */}
                        <div className="space-y-1.5 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <div className="flex items-center justify-between">
                                <Label className="text-xs font-semibold">Device ID (Hardware Fingerprint)</Label>
                                {availableDevices.length > 0 && (
                                    <span className="text-[11px] text-zinc-500">
                                        {availableDevices.length} unassigned detected
                                    </span>
                                )}
                            </div>

                            {availableDevices.length > 0 ? (
                                <div className="space-y-2">
                                    <select
                                        value={assignForm.data.device_id}
                                        onChange={(e) => assignForm.setData('device_id', e.target.value)}
                                        className="w-full h-10 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 font-mono"
                                    >
                                        <option value="">-- Choose detected device or enter below --</option>
                                        {availableDevices.map((d) => (
                                            <option key={d.id} value={d.device_id}>
                                                {d.device_id.substring(0, 18)}... ({d.machine_name || 'Machine'} - {d.software?.name})
                                            </option>
                                        ))}
                                    </select>
                                    <Input
                                        placeholder="Or paste Device ID manually"
                                        value={assignForm.data.device_id}
                                        onChange={(e) => assignForm.setData('device_id', e.target.value)}
                                        required
                                        className="h-9 text-xs font-mono"
                                    />
                                </div>
                            ) : (
                                <Input
                                    placeholder="Paste Device ID reported by client software"
                                    value={assignForm.data.device_id}
                                    onChange={(e) => assignForm.setData('device_id', e.target.value)}
                                    required
                                    className="h-9 text-xs font-mono"
                                />
                            )}
                            {assignForm.errors.device_id && (
                                <p className="text-xs text-red-600">{assignForm.errors.device_id}</p>
                            )}
                        </div>

                        {/* Duration Preset Selector */}
                        <div className="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <Label className="text-xs font-semibold">License Duration</Label>
                            <div className="grid grid-cols-3 gap-2">
                                {[
                                    { id: '1_month', label: '1 Month' },
                                    { id: '3_months', label: '3 Months' },
                                    { id: '6_months', label: '6 Months' },
                                    { id: '1_year', label: '1 Year' },
                                    { id: 'lifetime', label: 'Lifetime' },
                                    { id: 'custom', label: 'Custom Date' },
                                ].map((preset) => (
                                    <button
                                        type="button"
                                        key={preset.id}
                                        onClick={() => assignForm.setData('duration_preset', preset.id)}
                                        className={`px-3 py-2 rounded-lg text-xs font-medium border transition-colors ${
                                            assignForm.data.duration_preset === preset.id
                                                ? 'bg-[#0071e3] text-white border-[#0071e3]'
                                                : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100'
                                        }`}
                                    >
                                        {preset.label}
                                    </button>
                                ))}
                            </div>

                            {assignForm.data.duration_preset === 'custom' && (
                                <div className="pt-2">
                                    <Input
                                        type="date"
                                        value={assignForm.data.custom_expires_at}
                                        onChange={(e) => assignForm.setData('custom_expires_at', e.target.value)}
                                        required
                                        className="h-9 text-xs"
                                    />
                                </div>
                            )}
                        </div>

                        {/* Notes */}
                        <div className="space-y-1 pt-2">
                            <Label className="text-xs font-semibold">Notes (Branch / License reference)</Label>
                            <Textarea
                                placeholder="Optional customer notes or branch name..."
                                value={assignForm.data.notes}
                                onChange={(e) => assignForm.setData('notes', e.target.value)}
                                rows={2}
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-4 border-t border-zinc-100 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAssignModalOpen(false)}
                                className="text-xs"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={assignForm.processing}
                                className="bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs"
                            >
                                {assignForm.processing ? 'Assigning...' : 'Assign & Activate Device'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Custom Renew Dialog */}
            <Dialog open={renewModalDevice !== null} onOpenChange={(open) => !open && setRenewModalDevice(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">Renew Device License</DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Extend the active license term for {renewModalDevice?.user?.name || renewModalDevice?.device_id}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleRenewSubmit} className="space-y-4 pt-2">
                        <div className="space-y-2">
                            <Label className="text-xs font-semibold">Select Renewal Duration</Label>
                            <div className="grid grid-cols-3 gap-2">
                                {[
                                    { id: '1_month', label: '+1 Month' },
                                    { id: '3_months', label: '+3 Months' },
                                    { id: '6_months', label: '+6 Months' },
                                    { id: '1_year', label: '+1 Year' },
                                    { id: 'lifetime', label: 'Lifetime' },
                                    { id: 'custom', label: 'Custom Date' },
                                ].map((preset) => (
                                    <button
                                        type="button"
                                        key={preset.id}
                                        onClick={() => renewForm.setData('duration_preset', preset.id)}
                                        className={`px-3 py-2 rounded-lg text-xs font-medium border transition-colors ${
                                            renewForm.data.duration_preset === preset.id
                                                ? 'bg-[#0071e3] text-white border-[#0071e3]'
                                                : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100'
                                        }`}
                                    >
                                        {preset.label}
                                    </button>
                                ))}
                            </div>

                            {renewForm.data.duration_preset === 'custom' && (
                                <div className="pt-2">
                                    <Input
                                        type="date"
                                        value={renewForm.data.custom_expires_at}
                                        onChange={(e) => renewForm.setData('custom_expires_at', e.target.value)}
                                        required
                                        className="h-9 text-xs"
                                    />
                                </div>
                            )}
                        </div>

                        <DialogFooter className="pt-4 border-t border-zinc-100 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setRenewModalDevice(null)}
                                className="text-xs"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={renewForm.processing}
                                className="bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs"
                            >
                                {renewForm.processing ? 'Renewing...' : 'Confirm Renewal'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
