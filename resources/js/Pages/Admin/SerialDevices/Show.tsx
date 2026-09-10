import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
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
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { __ } from '@/lib/i18n';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    Clock,
    Copy,
    ExternalLink,
    Key,
    Layers,
    Monitor,
    RefreshCw,
    Shield,
    Trash2,
    User,
    UserX,
    XCircle,
} from 'lucide-react';

interface CustomKeyItem {
    id: number;
    key: string;
    label: string;
    description: string | null;
    default_value: string | null;
    override_id: number | null;
    override_value: string | null;
    effective_value: string | null;
    is_overridden: boolean;
}

interface UserSummary {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    created_at?: string;
}

interface AssignmentData {
    id: number;
    status: string;
    expires_at: string | null;
    expires_at_formatted: string | null;
    is_expired: boolean;
    remaining_days: number | null;
    notes: string | null;
    user: UserSummary | null;
    reseller?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface DeviceData {
    id: number;
    device_id: string;
    machine_name: string | null;
    user_name: string | null;
    user_domain: string | null;
    serial_software_id: number;
    status: 'active' | 'inactive' | 'blocked';
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
    updated_at: string | null;
    software?: {
        id: number;
        name: string;
        pricing_type?: string;
    };
    userDeviceAssignment?: any;
    resolved_custom_keys?: Record<string, string>;
}

interface Props {
    device: DeviceData;
    customKeys: CustomKeyItem[];
    assignment: AssignmentData | null;
    users: Array<{ id: number; name: string; email: string }>;
    statuses: string[];
}

export default function SerialDeviceShow({
    device,
    customKeys,
    assignment,
    users,
    statuses,
}: Props) {
    const [copiedDeviceId, setCopiedDeviceId] = useState(false);
    const [copiedDirectory, setCopiedDirectory] = useState(false);

    // Key override modal state
    const [overrideKeyModal, setOverrideKeyModal] = useState<CustomKeyItem | null>(null);
    const [overrideValueInput, setOverrideValueInput] = useState('');

    // Expiration editing state
    const [isLifetime, setIsLifetime] = useState(assignment?.expires_at ? false : true);
    const [expiresAtInput, setExpiresAtInput] = useState(assignment?.expires_at ?? '');

    const copyToClipboard = (text: string, type: 'device' | 'dir') => {
        navigator.clipboard.writeText(text);
        if (type === 'device') {
            setCopiedDeviceId(true);
            setTimeout(() => setCopiedDeviceId(false), 2000);
        } else {
            setCopiedDirectory(true);
            setTimeout(() => setCopiedDirectory(false), 2000);
        }
    };

    const handleStatusChange = (newStatus: string) => {
        router.patch(route('admin.serial-devices.status', device.id), {
            status: newStatus,
        }, {
            preserveScroll: true,
        });
    };

    const handleDeleteDevice = () => {
        if (confirm(__('general.confirm_delete_device') ?? 'Are you sure you want to delete this device?')) {
            router.delete(route('admin.serial-devices.destroy', device.id), {
                onSuccess: () => {
                    router.visit(route('admin.serial-devices.index'));
                },
            });
        }
    };

    const handleAssignClient = (userId: string | number | null) => {
        router.post(route('admin.serial-devices.assign-user', device.id), {
            user_id: userId ? Number(userId) : null,
        }, {
            preserveScroll: true,
        });
    };

    const handleSaveExpiration = (e: React.FormEvent) => {
        e.preventDefault();
        router.patch(route('admin.serial-devices.expires-at', device.id), {
            is_lifetime: isLifetime,
            expires_at: isLifetime ? null : expiresAtInput,
        }, {
            preserveScroll: true,
        });
    };

    const handleSaveKeyOverride = (e: React.FormEvent) => {
        e.preventDefault();
        if (!overrideKeyModal) return;

        router.post(route('admin.serial-devices.keys.set', device.id), {
            serial_software_key_id: overrideKeyModal.id,
            value: overrideValueInput,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setOverrideKeyModal(null);
            },
        });
    };

    const handleRemoveKeyOverride = (keyItem: CustomKeyItem) => {
        if (!keyItem.override_id) return;
        if (confirm(__('general.revert_key_to_default_confirm', { key: keyItem.key }))) {
            router.delete(route('admin.serial-devices.keys.remove', [device.id, keyItem.override_id]), {
                preserveScroll: true,
            });
        }
    };

    const statusBadgeVariant = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-300 dark:border-emerald-800';
            case 'blocked':
                return 'bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-400 border-red-300 dark:border-red-800';
            default:
                return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-300 dark:border-zinc-700';
        }
    };

    return (
        <AdminSidebarLayout
            title={device.machine_name || device.device_id}
            header={__('general.device_details') ?? 'Device Details'}
        >
            <Head title={`${device.machine_name || device.device_id} - ${__('general.device_details') ?? 'Device Details'}`} />

            <div className="p-4 md:p-8 max-w-7xl mx-auto space-y-8">
                {/* Top Navigation & Breadcrumbs */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-6">
                    <div className="space-y-1">
                        <Link
                            href={route('admin.serial-devices.index')}
                            className="inline-flex items-center text-xs font-medium text-muted-foreground hover:text-foreground transition-colors mb-1"
                        >
                            <ArrowLeft className="w-3.5 h-3.5 mr-1" />
                            {__('general.back_to_devices') ?? 'Back to Registered Devices'}
                        </Link>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight font-mono">
                                {device.machine_name || device.device_id}
                            </h1>
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border ${statusBadgeVariant(device.status)}`}>
                                {device.status}
                            </span>
                            {device.software && (
                                <Badge variant="outline" className="text-xs font-normal">
                                    <Layers className="w-3 h-3 mr-1" />
                                    {device.software.name}
                                </Badge>
                            )}
                        </div>
                        <div className="flex items-center gap-2 text-xs text-muted-foreground pt-1">
                            <span className="font-mono bg-muted px-2 py-0.5 rounded border select-all">
                                {device.device_id}
                            </span>
                            <button
                                type="button"
                                onClick={() => copyToClipboard(device.device_id, 'device')}
                                className="inline-flex items-center gap-1 text-xs text-foreground hover:underline"
                            >
                                {copiedDeviceId ? (
                                    <>
                                        <Check className="w-3.5 h-3.5 text-emerald-600" />
                                        <span className="text-emerald-600 font-medium">{__('general.copied') ?? 'Copied!'}</span>
                                    </>
                                ) : (
                                    <>
                                        <Copy className="w-3.5 h-3.5" />
                                        <span>{__('general.copy_id') ?? 'Copy ID'}</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Actions Toolbar */}
                    <div className="flex items-center gap-2">
                        {/* Status Switcher Dropdown */}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="font-medium">
                                    <RefreshCw className="w-3.5 h-3.5 mr-1.5" />
                                    <span>{__('general.change_status') ?? 'Change Status'}</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-44">
                                {statuses.map((st) => (
                                    <DropdownMenuItem
                                        key={st}
                                        onClick={() => handleStatusChange(st)}
                                        className={`capitalize cursor-pointer ${st === device.status ? 'font-semibold bg-muted' : ''}`}
                                    >
                                        {st}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        {/* Delete Device */}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleDeleteDevice}
                            className="text-red-600 border-red-200 hover:bg-red-50 dark:hover:bg-red-950/40"
                        >
                            <Trash2 className="w-3.5 h-3.5 mr-1" />
                            <span>{__('general.delete') ?? 'Delete'}</span>
                        </Button>
                    </div>
                </div>

                {/* 2-Column Responsive Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left 2 Columns: Telemetry, Licensing, Keys */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* Card 1: System & Hardware Telemetry */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <Monitor className="w-4 h-4 text-muted-foreground" />
                                    <span>{__('general.hardware_telemetry') ?? 'System & Hardware Telemetry'}</span>
                                </CardTitle>
                                <CardDescription>
                                    {__('general.hardware_telemetry_desc') ?? 'Detailed operating environment collected during application check-in.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="p-3 rounded-lg border bg-muted/20">
                                        <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.machine_name') ?? 'Machine Name'}</p>
                                        <p className="font-semibold text-foreground mt-0.5">{device.machine_name || '—'}</p>
                                    </div>
                                    <div className="p-3 rounded-lg border bg-muted/20">
                                        <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.os_user_domain') ?? 'OS User / Domain'}</p>
                                        <p className="font-semibold text-foreground mt-0.5">
                                            {device.user_name || '—'} {device.user_domain ? `(${device.user_domain})` : ''}
                                        </p>
                                    </div>
                                    <div className="p-3 rounded-lg border bg-muted/20">
                                        <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.os_version') ?? 'Operating System'}</p>
                                        <p className="font-medium text-foreground mt-0.5">{device.os_version || '—'}</p>
                                    </div>
                                    <div className="p-3 rounded-lg border bg-muted/20">
                                        <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.runtime_framework') ?? 'Runtime Framework'}</p>
                                        <p className="font-medium text-foreground mt-0.5">{device.framework_version || '—'}</p>
                                    </div>
                                    <div className="p-3 rounded-lg border bg-muted/20">
                                        <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.architecture') ?? 'Architecture'}</p>
                                        <div className="flex items-center gap-2 mt-1">
                                            <Badge variant="outline" className="text-xs font-mono">
                                                OS: {device.is_64bit_os === null ? '—' : device.is_64bit_os ? '64-bit' : '32-bit'}
                                            </Badge>
                                            <Badge variant="outline" className="text-xs font-mono">
                                                Process: {device.is_64bit_process === null ? '—' : device.is_64bit_process ? '64-bit' : '32-bit'}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div className="p-3 rounded-lg border bg-muted/20">
                                        <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.culture_locale') ?? 'Culture & Locale'}</p>
                                        <p className="font-mono text-xs text-foreground mt-1">
                                            {device.current_culture || '—'} / {device.current_ui_culture || '—'}
                                        </p>
                                    </div>
                                </div>

                                <div className="p-3 rounded-lg border bg-muted/20 space-y-1">
                                    <p className="text-xs text-muted-foreground uppercase font-medium">{__('general.executable_directory') ?? 'Application Directory'}</p>
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="font-mono text-xs text-foreground break-all">
                                            {device.current_directory || '—'}
                                        </p>
                                        {device.current_directory && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => copyToClipboard(device.current_directory!, 'dir')}
                                                className="h-7 text-xs flex-shrink-0"
                                            >
                                                {copiedDirectory ? (
                                                    <Check className="w-3 h-3 text-emerald-600" />
                                                ) : (
                                                    <Copy className="w-3 h-3" />
                                                )}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Card 2: Configuration Keys & Overrides */}
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                                            <Key className="w-4 h-4 text-muted-foreground" />
                                            <span>{__('general.custom_configuration_keys') ?? 'Active Configuration Keys & Dynamic Overrides'}</span>
                                        </CardTitle>
                                        <CardDescription>
                                            {__('general.custom_keys_description') ?? 'These configuration key-value pairs are returned dynamically to this machine during startup/check-in.'}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                {customKeys.length === 0 ? (
                                    <div className="p-6 text-center text-sm text-muted-foreground">
                                        <Key className="w-8 h-8 mx-auto text-muted-foreground/50 mb-2" />
                                        <p className="font-medium">{__('general.no_keys_configured') ?? 'No custom keys configured for this software'}</p>
                                        <p className="text-xs mt-1">{__('general.configure_keys_under_software_management')}</p>
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>{__('general.key_name') ?? 'Key Name'}</TableHead>
                                                    <TableHead>{__('general.default_value') ?? 'Software Default'}</TableHead>
                                                    <TableHead>{__('general.effective_value') ?? 'Active Device Value'}</TableHead>
                                                    <TableHead>{__('general.status') ?? 'Status'}</TableHead>
                                                    <TableHead className="text-end">{__('general.actions') ?? 'Actions'}</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {customKeys.map((item) => (
                                                    <TableRow key={item.id}>
                                                        <TableCell>
                                                            <div className="font-mono text-xs font-semibold text-foreground">{item.key}</div>
                                                            {item.description && (
                                                                <div className="text-[11px] text-muted-foreground">{item.description}</div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="font-mono text-xs text-muted-foreground">
                                                            {item.default_value || <span className="italic text-muted-foreground/60">(empty)</span>}
                                                        </TableCell>
                                                        <TableCell className="font-mono text-xs font-medium text-foreground">
                                                            {item.effective_value || <span className="italic text-muted-foreground/60">(empty)</span>}
                                                        </TableCell>
                                                        <TableCell>
                                                            {item.is_overridden ? (
                                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                                    {__('general.custom_override')}
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                                                    {__('general.default')}
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-end">
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => {
                                                                        setOverrideKeyModal(item);
                                                                        setOverrideValueInput(item.override_value ?? item.default_value ?? '');
                                                                    }}
                                                                    className="h-7 text-xs"
                                                                >
                                                                    {item.is_overridden ? __('general.edit') : __('general.override')}
                                                                </Button>
                                                                {item.is_overridden && (
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        onClick={() => handleRemoveKeyOverride(item)}
                                                                        className="h-7 text-xs text-red-600 hover:text-red-700"
                                                                    >
                                                                        {__('general.reset')}
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Ownership, Licensing, Activity Timestamps */}
                    <div className="space-y-8">
                        {/* Card 3: Client Assignment & Ownership */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <User className="w-4 h-4 text-muted-foreground" />
                                    <span>{__('general.client_assignment') ?? 'Client Ownership'}</span>
                                </CardTitle>
                                <CardDescription>
                                    {__('general.client_assignment_desc') ?? 'Customer assigned to this machine license.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                {assignment?.user ? (
                                    <div className="p-4 rounded-xl border bg-muted/20 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <div className="w-8 h-8 rounded-full bg-foreground text-background flex items-center justify-center font-semibold text-xs">
                                                    {assignment.user.name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-foreground text-sm">{assignment.user.name}</p>
                                                    <p className="text-xs text-muted-foreground">{assignment.user.email}</p>
                                                </div>
                                            </div>
                                            <Link
                                                href={route('admin.users.show', assignment.user.id)}
                                                className="text-xs text-foreground font-medium hover:underline inline-flex items-center gap-1"
                                            >
                                                <span>{__('general.profile')}</span>
                                                <ExternalLink className="w-3 h-3" />
                                            </Link>
                                        </div>

                                        {assignment.user.phone && (
                                            <div className="text-xs text-muted-foreground">
                                                {__('general.phone')}: <span className="text-foreground font-medium">{assignment.user.phone}</span>
                                            </div>
                                        )}

                                        {assignment.reseller && (
                                            <div className="text-xs text-muted-foreground border-t pt-2">
                                                {__('general.assigned_via_reseller')}: <span className="font-semibold text-foreground">{assignment.reseller.name}</span>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="p-4 rounded-xl border border-dashed text-center text-muted-foreground space-y-1">
                                        <UserX className="w-6 h-6 mx-auto text-muted-foreground/60 mb-1" />
                                        <p className="font-medium text-xs">{__('general.no_client_assigned') ?? 'Unassigned Device'}</p>
                                        <p className="text-[11px]">{__('general.device_unassigned_help') ?? 'Select a customer below to bind software access to their account.'}</p>
                                    </div>
                                )}

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold uppercase text-muted-foreground">
                                        {assignment?.user ? __('general.change_or_transfer_client') : __('general.assign_to_client')}
                                    </Label>
                                    <PremiumCombobox
                                        value={assignment?.user?.id ? String(assignment.user.id) : ''}
                                        onChange={handleAssignClient}
                                        options={[
                                            { value: '', label: __('general.unassigned_remove_client') },
                                            ...users.map(u => ({ value: String(u.id), label: `${u.name} (${u.email})` }))
                                        ]}
                                        placeholder={__('general.select_client') ?? 'Select Client...'}
                                        searchPlaceholder={__('general.search_users') ?? 'Search users...'}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Card 4: Software License Expiration */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <Shield className="w-4 h-4 text-muted-foreground" />
                                    <span>{__('general.license_expiration') ?? 'License Expiration'}</span>
                                </CardTitle>
                                <CardDescription>
                                    {__('general.manage_license_validity') ?? 'Manage lifetime access or set custom expiration date.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                {/* Current Status Display */}
                                <div className="p-3 rounded-lg border bg-muted/20 flex items-center justify-between">
                                    <span className="text-xs text-muted-foreground font-medium">{__('general.current_status') ?? 'Current Status'}</span>
                                    {assignment?.expires_at ? (
                                        assignment.is_expired ? (
                                             <span className="inline-flex items-center gap-1 text-xs font-semibold text-red-600 bg-red-50 dark:bg-red-950/40 px-2 py-0.5 rounded border border-red-200 dark:border-red-800">
                                                <XCircle className="w-3.5 h-3.5" /> {__('general.expired')} ({assignment.expires_at})
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                                <CheckCircle2 className="w-3.5 h-3.5" /> {assignment.remaining_days} {__('general.remaining_days')} ({assignment.expires_at})
                                            </span>
                                        )
                                    ) : (
                                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-zinc-800 dark:text-zinc-200 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-0.5 rounded border">
                                            {__('general.lifetime_license')}
                                        </span>
                                    )}
                                </div>

                                {/* Form to update expiration */}
                                {assignment ? (
                                    <form onSubmit={handleSaveExpiration} className="space-y-3 border-t pt-3">
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="isLifetimeCheck"
                                                checked={isLifetime}
                                                onChange={(e) => setIsLifetime(e.target.checked)}
                                                className="rounded border-border text-foreground focus:ring-foreground"
                                            />
                                            <label htmlFor="isLifetimeCheck" className="text-xs font-medium cursor-pointer">
                                                {__('general.lifetime_access') ?? 'Grant Lifetime License (No Expiration)'}
                                            </label>
                                        </div>

                                        {!isLifetime && (
                                            <div className="space-y-1">
                                                <Label htmlFor="expiresAtInput" className="text-xs text-muted-foreground">
                                                    {__('general.custom_expiration_date') ?? 'Expiration Date'}
                                                </Label>
                                                <Input
                                                    id="expiresAtInput"
                                                    type="date"
                                                    value={expiresAtInput}
                                                    onChange={(e) => setExpiresAtInput(e.target.value)}
                                                    className="h-9 text-xs"
                                                    required={!isLifetime}
                                                />
                                            </div>
                                        )}

                                        <Button type="submit" size="sm" className="w-full h-8 text-xs font-medium">
                                            {__('general.save_license_term') ?? 'Save License Term'}
                                        </Button>
                                    </form>
                                ) : (
                                    <p className="text-xs text-muted-foreground italic text-center py-2">
                                        {__('general.assign_client_to_enable_license_term_controls')}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Card 5: Audit & Activity Timestamps (Cairo Time) */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <Clock className="w-4 h-4 text-muted-foreground" />
                                    <span>{__('general.activity_timestamps') ?? 'Activity & Telemetry Timestamps'}</span>
                                </CardTitle>
                                <CardDescription>
                                    {__('general.all_timestamps_cairo_timezone')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-xs">
                                <div className="flex items-center justify-between py-1.5 border-b">
                                    <span className="text-muted-foreground">{__('general.registered_first_seen') ?? 'First Seen / Registered'}</span>
                                    <span className="font-mono text-foreground font-medium">{device.created_at || '—'}</span>
                                </div>
                                <div className="flex items-center justify-between py-1.5 border-b">
                                    <span className="text-muted-foreground">{__('general.last_check_in') ?? 'Last Check-In'}</span>
                                    <div className="text-end">
                                        <p className="font-mono text-foreground font-semibold">{device.last_check_date_full || 'Never'}</p>
                                        {device.last_check_date && (
                                            <p className="text-[10px] text-muted-foreground">{device.last_check_date}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center justify-between py-1.5">
                                    <span className="text-muted-foreground">{__('general.last_updated') ?? 'Last Record Update'}</span>
                                    <span className="font-mono text-foreground">{device.updated_at || '—'}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Override Key Dialog */}
            <Dialog open={!!overrideKeyModal} onOpenChange={(open) => !open && setOverrideKeyModal(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base">
                            <Key className="w-4 h-4" />
                            <span>{__('general.override_configuration_key')}</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            {__('general.override_key_description', { key: overrideKeyModal?.key || '', machine: device.machine_name || device.device_id })}
                        </DialogDescription>
                    </DialogHeader>
                    {overrideKeyModal && (
                        <form onSubmit={handleSaveKeyOverride} className="space-y-4 pt-2">
                            <div className="space-y-1">
                                <Label className="text-xs text-muted-foreground">{__('general.software_default_value')}</Label>
                                <Input
                                    value={overrideKeyModal.default_value || ''}
                                    readOnly
                                    disabled
                                    className="bg-muted text-muted-foreground text-xs font-mono h-8"
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="overrideInput" className="text-xs font-semibold">
                                    {__('general.device_override_value')}
                                </Label>
                                <Input
                                    id="overrideInput"
                                    value={overrideValueInput}
                                    onChange={(e) => setOverrideValueInput(e.target.value)}
                                    placeholder={__('general.enter_device_specific_value')}
                                    className="text-xs font-mono h-9"
                                    autoFocus
                                    required
                                />
                            </div>
                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setOverrideKeyModal(null)}
                                >
                                    {__('general.cancel')}
                                </Button>
                                <Button type="submit" size="sm">
                                    {__('general.save_override')}
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
