import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
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
    CreditCard,
    Sparkles,
    Monitor,
    Package as PackageIcon,
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface SoftwarePackage {
    id: number;
    name: string;
    price: number;
    reseller_price: number;
    currency: string;
    billing_cycle: string;
    billing_days: number | null;
    is_default: boolean;
    description: string | null;
}

interface AllocatedSoftware {
    id: number;
    serial_software_id: number;
    software_name: string;
    pricing_type: string;
    requires_payment: boolean;
    price: number | null;
    reseller_price: number | null;
    currency: string;
    billing_cycle: string;
    billing_days: number | null;
    packages: SoftwarePackage[];
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
    package_id?: number | null;
    package?: {
        id: number;
        name: string;
        price: number;
        reseller_price: number;
        currency: string;
        billing_cycle: string;
    } | null;
    has_used_trial?: boolean;
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
    reseller?: {
        id: number;
        name: string;
        email: string;
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
    canViewAllDevices?: boolean;
    walletBalance?: number;
    walletCurrency?: string;
}

export default function ResellerDevicesIndex({
    allocatedSoftwares = [],
    stats,
    devices,
    availableDevices = [],
    filters,
    canViewAllDevices = false,
    walletBalance = 0,
    walletCurrency = 'USD',
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
        package_id: '',
        customer_user_id: '',
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        duration_preset: '1_day_trial',
        custom_expires_at: '',
        notes: '',
    });

    // Form for renewing
    const renewForm = useForm({
        duration_preset: '1_month',
        package_id: '',
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
        setRenewModalDevice(device);
        renewForm.setData({
            duration_preset: '1_month',
            package_id: device.package_id ? String(device.package_id) : '',
            custom_expires_at: '',
        });
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

    // Helper for Assign modal cost calculation
    const currentAssignSoftware = allocatedSoftwares.find(
        (s) => String(s.serial_software_id) === String(assignForm.data.serial_software_id)
    );

    const getAssignCost = () => {
        if (!currentAssignSoftware) return { cost: 0, customerPrice: 0, currency: 'USD', isTrial: false };

        if (assignForm.data.duration_preset === '1_day_trial') {
            return { cost: 0, customerPrice: 0, currency: currentAssignSoftware.currency || 'USD', isTrial: true };
        }

        if (assignForm.data.package_id && currentAssignSoftware.packages && currentAssignSoftware.packages.length > 0) {
            const pkg = currentAssignSoftware.packages.find((p) => String(p.id) === String(assignForm.data.package_id));
            if (pkg) {
                return {
                    cost: pkg.reseller_price !== null && pkg.reseller_price !== undefined ? pkg.reseller_price : pkg.price,
                    customerPrice: pkg.price,
                    currency: pkg.currency || currentAssignSoftware.currency || 'USD',
                    isTrial: false,
                };
            }
        }

        if (!currentAssignSoftware.requires_payment && currentAssignSoftware.pricing_type === 'free') {
            return { cost: 0, customerPrice: 0, currency: currentAssignSoftware.currency || 'USD', isTrial: false };
        }

        const baseReseller = currentAssignSoftware.reseller_price !== null && currentAssignSoftware.reseller_price !== undefined
            ? currentAssignSoftware.reseller_price
            : (currentAssignSoftware.price || 0);
        const baseCustomer = currentAssignSoftware.price || 0;

        const mult = assignForm.data.duration_preset === '3_months' ? 3
            : assignForm.data.duration_preset === '6_months' ? 6
            : assignForm.data.duration_preset === '1_year' ? 12 : 1;

        return {
            cost: baseReseller * mult,
            customerPrice: baseCustomer * mult,
            currency: currentAssignSoftware.currency || 'USD',
            isTrial: false,
        };
    };

    // Helper for Renew modal cost calculation
    const getRenewCost = () => {
        if (!renewModalDevice) return { cost: 0, customerPrice: 0, currency: 'USD' };
        const firstDevice = renewModalDevice.devices?.[0];
        const sw = allocatedSoftwares.find((s) => s.serial_software_id === firstDevice?.serial_software_id);
        if (!sw) return { cost: 0, customerPrice: 0, currency: 'USD' };

        if (renewForm.data.package_id && sw.packages && sw.packages.length > 0) {
            const pkg = sw.packages.find((p) => String(p.id) === String(renewForm.data.package_id));
            if (pkg) {
                return {
                    cost: pkg.reseller_price !== null && pkg.reseller_price !== undefined ? pkg.reseller_price : pkg.price,
                    customerPrice: pkg.price,
                    currency: pkg.currency || sw.currency || 'USD',
                };
            }
        }

        if (!sw.requires_payment && sw.pricing_type === 'free') {
            return { cost: 0, customerPrice: 0, currency: sw.currency || 'USD' };
        }

        const baseReseller = sw.reseller_price !== null && sw.reseller_price !== undefined
            ? sw.reseller_price
            : (sw.price || 0);
        const baseCustomer = sw.price || 0;

        const mult = renewForm.data.duration_preset === '3_months' ? 3
            : renewForm.data.duration_preset === '6_months' ? 6
            : renewForm.data.duration_preset === '1_year' ? 12 : 1;

        return {
            cost: baseReseller * mult,
            customerPrice: baseCustomer * mult,
            currency: sw.currency || 'USD',
        };
    };

    const assignCostInfo = getAssignCost();
    const renewCostInfo = getRenewCost();

    return (
        <AuthenticatedLayout>
            <Head title="Reseller Device Management" />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                {/* Header with Title and Reseller Balance */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded bg-[#0071e3]/10 text-[#0071e3]">
                                <Shield className="w-3.5 h-3.5" />
                                Reseller Portal
                            </span>
                            {canViewAllDevices && (
                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    Full Scope (All Allocated Software Devices)
                                </span>
                            )}
                        </div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            Software Devices & License Control
                        </h1>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            Manage customer software assignments, activate 1-day free trials, and renew paid licenses.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Wallet Balance Badge */}
                        <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs text-xs">
                            <CreditCard className="w-4 h-4 text-[#0071e3]" />
                            <span className="text-zinc-500 font-medium">Balance:</span>
                            <span className="font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                {walletBalance.toFixed(2)} {walletCurrency}
                            </span>
                            <Link
                                href="/wallet"
                                className="text-[11px] font-semibold text-[#0071e3] hover:underline ms-1"
                            >
                                Recharge
                            </Link>
                        </div>

                        <Button
                            onClick={() => setIsAssignModalOpen(true)}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white shadow-xs font-medium flex items-center gap-2"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Assign Device</span>
                        </Button>
                    </div>
                </div>

                {/* Top Metrics Cards */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="text-xs font-medium text-zinc-500">Allocated Software</div>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {stats.total_softwares}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="text-xs font-medium text-zinc-500">Total Devices</div>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {stats.total_devices}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="text-xs font-medium text-emerald-600 dark:text-emerald-400">Active Devices</div>
                        <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                            {stats.active_devices}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="text-xs font-medium text-amber-600 dark:text-amber-400">Expiring (&lt; 7 Days)</div>
                        <div className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {stats.expiring_soon}
                        </div>
                    </div>
                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-xs col-span-2 lg:col-span-1">
                        <div className="text-xs font-medium text-red-600 dark:text-red-400">Expired Licenses</div>
                        <div className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                            {stats.expired_devices}
                        </div>
                    </div>
                </div>

                {/* Filters & Search Toolbar */}
                <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 shadow-xs">
                    <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row items-center gap-3">
                        <div className="relative flex-1 w-full">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                            <Input
                                placeholder="Search by Device ID, Customer Name, Email, or Notes..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-9 text-xs h-9"
                            />
                        </div>

                        <div className="flex items-center gap-2 w-full sm:w-auto">
                            <select
                                value={selectedSoftware}
                                onChange={(e) => {
                                    setSelectedSoftware(e.target.value);
                                    handleFilter('software_id', e.target.value);
                                }}
                                className="text-xs h-9 rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3"
                            >
                                <option value="">All Softwares</option>
                                {allocatedSoftwares.map((sw) => (
                                    <option key={sw.serial_software_id} value={sw.serial_software_id}>
                                        {sw.software_name}
                                    </option>
                                ))}
                            </select>

                            <select
                                value={selectedStatus}
                                onChange={(e) => {
                                    setSelectedStatus(e.target.value);
                                    handleFilter('status', e.target.value);
                                }}
                                className="text-xs h-9 rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active Only</option>
                                <option value="expiring_soon">Expiring Soon (&lt; 7 Days)</option>
                                <option value="expired">Expired Only</option>
                                <option value="inactive">Inactive / Suspended</option>
                            </select>

                            <Button type="submit" size="sm" variant="outline" className="h-9 text-xs">
                                Filter
                            </Button>
                        </div>
                    </form>
                </div>

                {/* Device Table */}
                <div className="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 text-[11px] font-semibold uppercase text-zinc-500">
                                    <th className="px-5 py-3">Customer</th>
                                    <th className="px-5 py-3">Software &amp; Device</th>
                                    <th className="px-5 py-3">Expiration &amp; Term</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800 text-xs">
                                {devices.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-5 py-12 text-center text-zinc-500">
                                            <Laptop className="w-8 h-8 text-zinc-300 dark:text-zinc-700 mx-auto mb-2" />
                                            <p className="font-medium text-sm">No device assignments found</p>
                                            <p className="text-xs text-zinc-400 mt-0.5">
                                                Click "Assign Device" above to bind a customer license.
                                            </p>
                                        </td>
                                    </tr>
                                ) : (
                                    devices.data.map((device) => {
                                        const firstDevice = device.devices?.[0];
                                        const softwareName = firstDevice?.software?.name || 'Software License';
                                        const expiryInfo = getExpiryInfo(device.expires_at, device.status);
                                        const lastCheck = firstDevice?.last_check_date;

                                        return (
                                            <tr
                                                key={device.id}
                                                className="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors cursor-pointer"
                                                onClick={() => router.visit(`/portal/devices/${device.id}`)}
                                            >
                                                <td className="px-5 py-4">
                                                    <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                        {device.user?.name || 'Unassigned User'}
                                                    </div>
                                                    <div className="text-[11px] text-zinc-500">
                                                        {device.user?.email || 'No email registered'}
                                                    </div>
                                                    {device.notes && (
                                                        <div className="text-[11px] text-zinc-400 italic mt-0.5 line-clamp-1">
                                                            Note: {device.notes}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className="font-medium text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                                        <span>{softwareName}</span>
                                                        {device.package && (
                                                            <span className="text-[10px] px-1.5 py-0.2 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 font-normal">
                                                                {device.package.name}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="text-[11px] font-mono text-zinc-500 mt-0.5">
                                                        {device.device_id}
                                                    </div>
                                                    {firstDevice?.machine_name && (
                                                        <div className="text-[11px] text-zinc-400">
                                                            Machine: {firstDevice.machine_name}
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
                                                    {device.has_used_trial && (
                                                        <div className="text-[10px] text-zinc-400 mt-0.5">
                                                            Trial already used
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

                                                <td className="px-5 py-4 text-right" onClick={(e) => e.stopPropagation()}>
                                                    <div className="flex items-center justify-end gap-2">
                                                        {/* Quick +1 Month Renew button (Opens dialog with cost confirmation) */}
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
                                                                    onClick={() => router.visit(`/portal/devices/${device.id}`)}
                                                                    className="text-xs cursor-pointer font-medium"
                                                                >
                                                                    <Monitor className="w-3.5 h-3.5 mr-2 text-zinc-500" />
                                                                    View Details
                                                                </DropdownMenuItem>

                                                                <DropdownMenuItem
                                                                    onClick={() => {
                                                                        setRenewModalDevice(device);
                                                                        renewForm.setData({
                                                                            duration_preset: '1_month',
                                                                            package_id: device.package_id ? String(device.package_id) : '',
                                                                            custom_expires_at: '',
                                                                        });
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
                                                                            Suspend / Deactivate
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <CheckCircle2 className="w-3.5 h-3.5 mr-2 text-emerald-500" />
                                                                            Re-activate
                                                                        </>
                                                                    )}
                                                                </DropdownMenuItem>

                                                                <DropdownMenuItem
                                                                    onClick={() => handleUnassign(device)}
                                                                    className="text-xs cursor-pointer text-red-600 focus:text-red-600"
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
                    {devices.links && devices.links.length > 3 && (
                        <div className="p-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <span className="text-xs text-zinc-500">
                                Showing {devices.data.length} of {devices.total} device assignments
                            </span>
                            <div className="flex items-center gap-1">
                                {devices.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url || link.active}
                                        onClick={() => link.url && router.visit(link.url)}
                                        className={`px-3 py-1 text-xs rounded border transition-colors ${
                                            link.active
                                                ? 'bg-[#0071e3] text-white border-[#0071e3]'
                                                : link.url
                                                ? 'hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300'
                                                : 'text-zinc-400 border-transparent cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* ASSIGN DEVICE MODAL */}
            <Dialog open={isAssignModalOpen} onOpenChange={setIsAssignModalOpen}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">Assign Device to Customer</DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Bind a client hardware device, grant a 1-day free trial or deduct reseller cost from your wallet balance.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAssignSubmit} className="space-y-4 pt-2">
                        {/* Software Product Selection */}
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Software Product</Label>
                            <select
                                value={assignForm.data.serial_software_id}
                                onChange={(e) => assignForm.setData('serial_software_id', e.target.value)}
                                className="w-full h-9 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3"
                                required
                            >
                                {allocatedSoftwares.map((sw) => (
                                    <option key={sw.serial_software_id} value={sw.serial_software_id}>
                                        {sw.software_name} (Active Quota: {sw.active_devices_count} / {sw.is_unlimited ? 'Unlimited' : sw.max_devices})
                                    </option>
                                ))}
                            </select>
                            {assignForm.errors.serial_software_id && (
                                <p className="text-xs text-red-600">{assignForm.errors.serial_software_id}</p>
                            )}
                        </div>

                        {/* Customer Information */}
                        <div className="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <Label className="text-xs font-semibold">Customer Account</Label>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div className="space-y-1">
                                    <Input
                                        placeholder="Customer Full Name"
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

                        {/* Duration & Pricing Selection */}
                        <div className="space-y-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <div className="flex items-center justify-between">
                                <Label className="text-xs font-semibold">License Duration &amp; Pricing</Label>
                                <span className="text-[11px] text-zinc-500">
                                    Reseller Cost vs Customer Retail
                                </span>
                            </div>

                            {/* 1-Day Free Trial Option */}
                            <div
                                onClick={() => assignForm.setData((prev) => ({ ...prev, duration_preset: '1_day_trial', package_id: '' }))}
                                className={`p-3 rounded-xl border cursor-pointer transition-all ${
                                    assignForm.data.duration_preset === '1_day_trial'
                                        ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/30 ring-1 ring-emerald-500'
                                        : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/40'
                                }`}
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Sparkles className="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span className="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                            1-Day Free Trial (تجربة مجانية لمدة يوم واحد)
                                        </span>
                                    </div>
                                    <span className="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        0.00 {currentAssignSoftware?.currency || 'USD'}
                                    </span>
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                                    Strictly limited to 1 day per machine. Cannot be claimed again even if the device is unassigned or deleted.
                                </p>
                            </div>

                            {/* Packages / Paid Options */}
                            {currentAssignSoftware?.packages && currentAssignSoftware.packages.length > 0 ? (
                                <div className="space-y-2">
                                    <Label className="text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        Available Paid Packages
                                    </Label>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        {currentAssignSoftware.packages.map((pkg) => (
                                            <div
                                                key={pkg.id}
                                                onClick={() => assignForm.setData((prev) => ({ ...prev, duration_preset: 'package', package_id: String(pkg.id) }))}
                                                className={`p-3 rounded-xl border cursor-pointer transition-all ${
                                                    assignForm.data.duration_preset === 'package' && String(assignForm.data.package_id) === String(pkg.id)
                                                        ? 'border-[#0071e3] bg-[#0071e3]/5 ring-1 ring-[#0071e3]'
                                                        : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span className="text-xs font-bold text-zinc-900 dark:text-zinc-100">{pkg.name}</span>
                                                    <span className="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 capitalize text-zinc-600 dark:text-zinc-300">
                                                        {pkg.billing_cycle}
                                                    </span>
                                                </div>
                                                <div className="mt-2 flex items-baseline justify-between text-xs">
                                                    <span className="text-zinc-500">Reseller Cost:</span>
                                                    <span className="font-bold text-[#0071e3]">
                                                        {pkg.reseller_price !== null && pkg.reseller_price !== undefined ? pkg.reseller_price : pkg.price} {pkg.currency}
                                                    </span>
                                                </div>
                                                <div className="flex items-baseline justify-between text-[11px] text-zinc-400">
                                                    <span>Customer Retail:</span>
                                                    <span>{pkg.price} {pkg.currency}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    <Label className="text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                                        Paid License Durations
                                    </Label>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        {[
                                            { id: '1_month', label: '1 Month', mult: 1 },
                                            { id: '3_months', label: '3 Months', mult: 3 },
                                            { id: '6_months', label: '6 Months', mult: 6 },
                                            { id: '1_year', label: '1 Year', mult: 12 },
                                            { id: 'lifetime', label: 'Lifetime', mult: 1 },
                                        ].map((preset) => {
                                            const baseCost = currentAssignSoftware?.reseller_price !== null && currentAssignSoftware?.reseller_price !== undefined
                                                ? currentAssignSoftware.reseller_price
                                                : (currentAssignSoftware?.price || 0);
                                            const baseRetail = currentAssignSoftware?.price || 0;
                                            const cost = (baseCost * preset.mult).toFixed(2);
                                            const retail = (baseRetail * preset.mult).toFixed(2);

                                            return (
                                                <div
                                                    key={preset.id}
                                                    onClick={() => assignForm.setData((prev) => ({ ...prev, duration_preset: preset.id, package_id: '' }))}
                                                    className={`p-2.5 rounded-xl border cursor-pointer transition-all text-left ${
                                                        assignForm.data.duration_preset === preset.id
                                                            ? 'border-[#0071e3] bg-[#0071e3]/5 ring-1 ring-[#0071e3]'
                                                            : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'
                                                    }`}
                                                >
                                                    <div className="text-xs font-bold text-zinc-900 dark:text-zinc-100">{preset.label}</div>
                                                    <div className="text-[11px] font-semibold text-[#0071e3] mt-1">
                                                        Cost: {cost} {currentAssignSoftware?.currency || 'USD'}
                                                    </div>
                                                    <div className="text-[10px] text-zinc-400">
                                                        Retail: {retail} {currentAssignSoftware?.currency || 'USD'}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Wallet Summary Card */}
                            <div className="p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 space-y-2">
                                <div className="flex items-center justify-between text-xs">
                                    <span className="text-zinc-500">Your Wallet Balance:</span>
                                    <span className="font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                        {walletBalance.toFixed(2)} {walletCurrency}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-xs">
                                    <span className="text-zinc-500">Cost to Deduct:</span>
                                    <span className={`font-bold font-mono ${assignCostInfo.isTrial ? 'text-emerald-600' : 'text-[#0071e3]'}`}>
                                        {assignCostInfo.cost.toFixed(2)} {assignCostInfo.currency}
                                    </span>
                                </div>
                                {assignCostInfo.cost > 0 && walletBalance < assignCostInfo.cost && (
                                    <div className="text-xs text-red-600 dark:text-red-400 font-medium flex items-center gap-1.5 pt-1 border-t border-red-200 dark:border-red-900/50">
                                        <AlertTriangle className="w-3.5 h-3.5 shrink-0" />
                                        <span>Insufficient balance. Please top up your wallet before activating.</span>
                                    </div>
                                )}
                            </div>
                            {assignForm.errors.duration_preset && (
                                <p className="text-xs text-red-600">{assignForm.errors.duration_preset}</p>
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
                                disabled={assignForm.processing || (assignCostInfo.cost > 0 && walletBalance < assignCostInfo.cost)}
                                className="bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs"
                            >
                                {assignForm.processing ? 'Processing...' : assignCostInfo.isTrial ? 'Activate 1-Day Trial' : 'Confirm & Deduct from Wallet'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* RENEW LICENSE MODAL */}
            <Dialog open={renewModalDevice !== null} onOpenChange={(open) => !open && setRenewModalDevice(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">Renew Device License</DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Extend the active license term for {renewModalDevice?.user?.name || renewModalDevice?.device_id}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleRenewSubmit} className="space-y-4 pt-2">
                        {/* Notice: Free Trial not allowed on renewal */}
                        <div className="p-2.5 rounded-lg border border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-950/20 text-amber-900 dark:text-amber-200 text-xs flex items-center gap-2">
                            <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />
                            <span>Renewals require paid activation deducted from your wallet balance.</span>
                        </div>

                        <div className="space-y-2">
                            <Label className="text-xs font-semibold">Select Renewal Term</Label>
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                {[
                                    { id: '1_month', label: '+1 Month', mult: 1 },
                                    { id: '3_months', label: '+3 Months', mult: 3 },
                                    { id: '6_months', label: '+6 Months', mult: 6 },
                                    { id: '1_year', label: '+1 Year', mult: 12 },
                                    { id: 'lifetime', label: 'Lifetime', mult: 1 },
                                ].map((preset) => (
                                    <button
                                        type="button"
                                        key={preset.id}
                                        onClick={() => renewForm.setData('duration_preset', preset.id)}
                                        className={`p-2 rounded-lg text-xs font-medium border transition-colors ${
                                            renewForm.data.duration_preset === preset.id
                                                ? 'bg-[#0071e3] text-white border-[#0071e3]'
                                                : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100'
                                        }`}
                                    >
                                        <div className="font-bold">{preset.label}</div>
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Renewal Cost & Wallet Balance Summary */}
                        <div className="p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-zinc-500">Your Wallet Balance:</span>
                                <span className="font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {walletBalance.toFixed(2)} {walletCurrency}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-zinc-500">Renewal Cost:</span>
                                <span className="font-bold font-mono text-[#0071e3]">
                                    {renewCostInfo.cost.toFixed(2)} {renewCostInfo.currency}
                                </span>
                            </div>
                            {renewCostInfo.customerPrice > 0 && (
                                <div className="flex items-center justify-between text-[11px] text-zinc-400">
                                    <span>Suggested Retail Price:</span>
                                    <span>{renewCostInfo.customerPrice.toFixed(2)} {renewCostInfo.currency}</span>
                                </div>
                            )}
                            {renewCostInfo.cost > 0 && walletBalance < renewCostInfo.cost && (
                                <div className="text-xs text-red-600 dark:text-red-400 font-medium flex items-center gap-1.5 pt-1 border-t border-red-200 dark:border-red-900/50">
                                    <AlertTriangle className="w-3.5 h-3.5 shrink-0" />
                                    <span>Insufficient wallet balance. Please top up before renewing.</span>
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
                                disabled={renewForm.processing || (renewCostInfo.cost > 0 && walletBalance < renewCostInfo.cost)}
                                className="bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs"
                            >
                                {renewForm.processing ? 'Processing...' : 'Confirm & Deduct from Wallet'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
