import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Separator } from '@/Components/ui/separator';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    Copy,
    HardDrive,
    Laptop,
    Layers,
    Monitor,
    Phone,
    RefreshCw,
    Shield,
    Trash2,
    User,
    XCircle,
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface UserInfo {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    created_at?: string;
}

interface TelemetryInfo {
    id: number;
    machine_name: string | null;
    user_name: string | null;
    user_domain: string | null;
    os_version: string | null;
    framework_version: string | null;
    is_64bit_os: boolean | null;
    is_64bit_process: boolean | null;
    current_directory: string | null;
    current_culture: string | null;
    last_check_date: string | null;
    last_check_date_full: string | null;
        software?: {
            id: number;
            name: string;
            pricing_type?: string;
            price?: number | null;
            reseller_price?: number | null;
            currency?: string;
            packages?: Array<{
                id: number;
                name: string;
                price: number;
                reseller_price: number;
                currency: string;
                billing_cycle: string;
            }>;
        } | null;
}

interface DeviceAssignmentDetail {
    id: number;
    device_id: string;
    status: 'active' | 'inactive';
    expires_at: string | null;
    notes: string | null;
    package?: {
        id: number;
        name: string;
        price: number;
        reseller_price: number;
        currency: string;
        billing_cycle: string;
    } | null;
    created_at: string;
    updated_at: string;
    user: UserInfo | null;
    reseller?: {
        id: number;
        name: string;
        email: string;
    } | null;
    telemetry: TelemetryInfo | null;
}

interface ExpiryInfo {
    expires_at: string | null;
    expires_at_formatted: string | null;
    is_expired: boolean;
    is_expiring_soon: boolean;
    remaining_days: number | null;
    is_lifetime: boolean;
}

interface Props {
    device: DeviceAssignmentDetail;
    expiryInfo: ExpiryInfo;
    walletBalance?: number;
    walletCurrency?: string;
}

export default function ResellerDeviceShow({
    device,
    expiryInfo,
    walletBalance = 0,
    walletCurrency = 'USD',
}: Props) {
    const [copiedDeviceId, setCopiedDeviceId] = useState(false);
    const [copiedDirectory, setCopiedDirectory] = useState(false);

    // Renewal form
    const renewForm = useForm({
        duration_preset: '1_month',
        custom_expires_at: '',
    });

    // Notes form
    const notesForm = useForm({
        notes: device.notes ?? '',
    });

    const software = device.telemetry?.software;

    const getCostInfo = () => {
        if (!software) return { cost: 0, customerPrice: 0, currency: 'USD' };

        const baseReseller = software.reseller_price !== null && software.reseller_price !== undefined
            ? software.reseller_price
            : (software.price || 0);
        const baseCustomer = software.price || 0;

        const mult = renewForm.data.duration_preset === '3_months' ? 3
            : renewForm.data.duration_preset === '6_months' ? 6
            : renewForm.data.duration_preset === '1_year' ? 12 : 1;

        return {
            cost: baseReseller * mult,
            customerPrice: baseCustomer * mult,
            currency: software.currency || 'USD',
        };
    };

    const costInfo = getCostInfo();

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

    const handleQuickRenew = () => {
        const costStr = costInfo.cost > 0 ? ` for ${costInfo.cost.toFixed(2)} ${costInfo.currency}` : '';
        if (confirm(`Extend license by 1 Month for customer ${device.user?.name || device.device_id}${costStr}?`)) {
            router.post(`/portal/devices/${device.id}/renew`, {
                duration_preset: '1_month',
            }, {
                preserveScroll: true,
            });
        }
    };

    const handleRenewSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        renewForm.post(`/portal/devices/${device.id}/renew`, {
            preserveScroll: true,
        });
    };

    const handleToggleStatus = () => {
        const nextStatus = device.status === 'active' ? 'inactive' : 'active';
        router.patch(`/portal/devices/${device.id}/status`, {
            status: nextStatus,
        }, {
            preserveScroll: true,
        });
    };

    const handleSaveNotes = (e: React.FormEvent) => {
        e.preventDefault();
        notesForm.patch(`/portal/devices/${device.id}/notes`, {
            preserveScroll: true,
        });
    };

    const handleUnassign = () => {
        if (confirm(`Are you sure you want to unassign device ${device.device_id}? The customer will immediately lose software access.`)) {
            router.delete(`/portal/devices/${device.id}`, {
                onSuccess: () => {
                    router.visit('/portal/devices');
                },
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Customer License: ${device.user?.name || device.device_id}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Header Navigation */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-6">
                    <div className="space-y-1">
                        <Link
                            href="/portal/devices"
                            className="inline-flex items-center text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors mb-1"
                        >
                            <ArrowLeft className="w-3.5 h-3.5 mr-1" />
                            <span>Back to My Allocated Devices</span>
                        </Link>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                {device.user?.name || 'Customer License'}
                            </h1>
                            {device.status === 'active' && !expiryInfo.is_expired ? (
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 px-2.5 py-0.5 rounded-full">
                                    <CheckCircle2 className="w-3.5 h-3.5" /> Active
                                </span>
                            ) : expiryInfo.is_expired ? (
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 px-2.5 py-0.5 rounded-full">
                                    <XCircle className="w-3.5 h-3.5" /> Expired
                                </span>
                            ) : (
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-2.5 py-0.5 rounded-full">
                                    Inactive
                                </span>
                            )}
                            {device.telemetry?.software && (
                                <Badge variant="outline" className="text-xs font-normal">
                                    <Layers className="w-3 h-3 mr-1" />
                                    {device.telemetry.software.name}
                                </Badge>
                            )}
                        </div>
                        <div className="flex items-center gap-2 text-xs text-zinc-500 pt-1">
                            <span className="font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-700 select-all">
                                {device.device_id}
                            </span>
                            <button
                                type="button"
                                onClick={() => copyToClipboard(device.device_id, 'device')}
                                className="inline-flex items-center gap-1 text-xs text-zinc-700 dark:text-zinc-300 hover:underline"
                            >
                                {copiedDeviceId ? (
                                    <>
                                        <Check className="w-3.5 h-3.5 text-emerald-600" />
                                        <span className="text-emerald-600 font-medium">Copied!</span>
                                    </>
                                ) : (
                                    <>
                                        <Copy className="w-3.5 h-3.5" />
                                        <span>Copy Device ID</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="flex items-center gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={handleQuickRenew}
                            className="text-xs font-medium border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50"
                        >
                            <RefreshCw className="w-3.5 h-3.5 mr-1" />
                            <span>Quick Renew (+1 Month)</span>
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={handleToggleStatus}
                            className="text-xs font-medium"
                        >
                            {device.status === 'active' ? (
                                <>
                                    <XCircle className="w-3.5 h-3.5 mr-1 text-amber-500" />
                                    <span>Deactivate</span>
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 className="w-3.5 h-3.5 mr-1 text-emerald-500" />
                                    <span>Activate</span>
                                </>
                            )}
                        </Button>
                    </div>
                </div>

                {/* 2-Column Content Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left 2 Columns: License Renewal & Machine Environment */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* Section 1: License Renewal Terminal */}
                        <Card className="border-zinc-200 dark:border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <Calendar className="w-4 h-4 text-[#0071e3]" />
                                    <span>License Expiration & Renewal Terminal</span>
                                </CardTitle>
                                <CardDescription>
                                    Manage duration terms, grant extensions, or set custom expiration dates.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Expiry Status Banner */}
                                <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wider text-zinc-500">License Status</p>
                                        <p className="text-base font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">
                                            {expiryInfo.is_lifetime
                                                ? 'Lifetime License'
                                                : expiryInfo.is_expired
                                                ? `Expired on ${expiryInfo.expires_at}`
                                                : `${expiryInfo.remaining_days} Days Remaining`}
                                        </p>
                                        {!expiryInfo.is_lifetime && expiryInfo.expires_at_formatted && (
                                            <p className="text-xs text-zinc-500 mt-0.5">
                                                Exact Expiry: {expiryInfo.expires_at_formatted} (Africa/Cairo)
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {expiryInfo.is_expiring_soon && (
                                            <span className="text-xs font-semibold px-2 py-1 rounded bg-amber-50 text-amber-800 border border-amber-300 dark:bg-amber-950/40 dark:text-amber-400">
                                                Expiring Soon (&lt; 7 Days)
                                            </span>
                                        )}
                                    </div>
                                </div>

                                {/* Renewal Form */}
                                <form onSubmit={handleRenewSubmit} className="space-y-4">
                                    <Label className="text-xs font-semibold uppercase text-zinc-500">
                                        Choose Renewal Term
                                    </Label>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        {[
                                            { id: '1_month', label: '+1 Month' },
                                            { id: '3_months', label: '+3 Months' },
                                            { id: '6_months', label: '+6 Months' },
                                            { id: '1_year', label: '+1 Year' },
                                            { id: 'lifetime', label: 'Lifetime' },
                                            { id: 'custom', label: 'Custom Date' },
                                        ].map((preset) => (
                                            <label
                                                key={preset.id}
                                                className={`flex items-center justify-center p-3 rounded-xl border text-xs font-medium cursor-pointer transition-all ${
                                                    renewForm.data.duration_preset === preset.id
                                                        ? 'border-[#0071e3] bg-[#0071e3]/5 text-[#0071e3] font-bold shadow-xs'
                                                        : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="duration_preset"
                                                    value={preset.id}
                                                    checked={renewForm.data.duration_preset === preset.id}
                                                    onChange={(e) => renewForm.setData('duration_preset', e.target.value)}
                                                    className="sr-only"
                                                />
                                                <span>{preset.label}</span>
                                            </label>
                                        ))}
                                    </div>

                                    {renewForm.data.duration_preset === 'custom' && (
                                        <div className="space-y-1">
                                            <Label htmlFor="customDate" className="text-xs text-zinc-500">
                                                Select Custom Expiration Date
                                            </Label>
                                            <Input
                                                id="customDate"
                                                type="date"
                                                value={renewForm.data.custom_expires_at}
                                                onChange={(e) => renewForm.setData('custom_expires_at', e.target.value)}
                                                className="text-xs h-9"
                                                required={renewForm.data.duration_preset === 'custom'}
                                            />
                                        </div>
                                    )}

                                    {/* Cost & Wallet Balance Summary */}
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
                                                {costInfo.cost.toFixed(2)} {costInfo.currency}
                                            </span>
                                        </div>
                                        {costInfo.customerPrice > 0 && (
                                            <div className="flex items-center justify-between text-[11px] text-zinc-400">
                                                <span>Suggested Retail:</span>
                                                <span>{costInfo.customerPrice.toFixed(2)} {costInfo.currency}</span>
                                            </div>
                                        )}
                                        {costInfo.cost > 0 && walletBalance < costInfo.cost && (
                                            <div className="text-xs text-red-600 dark:text-red-400 font-medium flex items-center gap-1.5 pt-1 border-t border-red-200 dark:border-red-900/50">
                                                <AlertTriangle className="w-3.5 h-3.5 shrink-0" />
                                                <span>Insufficient wallet balance. Please top up before renewing.</span>
                                            </div>
                                        )}
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={renewForm.processing || (costInfo.cost > 0 && walletBalance < costInfo.cost)}
                                        className="w-full sm:w-auto bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs px-6"
                                    >
                                        {renewForm.processing ? 'Processing Renewal...' : 'Apply License Renewal'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Section 2: Machine & Hardware Environment */}
                        <Card className="border-zinc-200 dark:border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <Monitor className="w-4 h-4 text-zinc-500" />
                                    <span>Hardware Environment & Operating Telemetry</span>
                                </CardTitle>
                                <CardDescription>
                                    Technical data reported by the client desktop application.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                {device.telemetry ? (
                                    <>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20">
                                                <p className="text-xs text-zinc-500 uppercase font-medium">Machine Name</p>
                                                <p className="font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                                                    {device.telemetry.machine_name || '—'}
                                                </p>
                                            </div>
                                            <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20">
                                                <p className="text-xs text-zinc-500 uppercase font-medium">OS User & Domain</p>
                                                <p className="font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                                                    {device.telemetry.user_name || '—'} {device.telemetry.user_domain ? `(${device.telemetry.user_domain})` : ''}
                                                </p>
                                            </div>
                                            <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20">
                                                <p className="text-xs text-zinc-500 uppercase font-medium">Operating System</p>
                                                <p className="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                                                    {device.telemetry.os_version || '—'}
                                                </p>
                                            </div>
                                            <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20">
                                                <p className="text-xs text-zinc-500 uppercase font-medium">Runtime Framework</p>
                                                <p className="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                                                    {device.telemetry.framework_version || '—'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20 space-y-1">
                                            <p className="text-xs text-zinc-500 uppercase font-medium">Executable Path</p>
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="font-mono text-xs text-zinc-900 dark:text-zinc-100 break-all">
                                                    {device.telemetry.current_directory || '—'}
                                                </p>
                                                {device.telemetry.current_directory && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => copyToClipboard(device.telemetry!.current_directory!, 'dir')}
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
                                    </>
                                ) : (
                                    <div className="p-6 text-center text-sm text-zinc-500">
                                        <Laptop className="w-8 h-8 mx-auto text-zinc-400 mb-2" />
                                        <p className="font-medium text-zinc-700 dark:text-zinc-300">No hardware check-in telemetry recorded yet</p>
                                        <p className="text-xs mt-1">Hardware specs will populate automatically once the software is launched on the client machine.</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Customer Info, Reseller Notes, Danger Zone */}
                    <div className="space-y-8">
                        {/* Section 3: Customer Profile */}
                        <Card className="border-zinc-200 dark:border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <User className="w-4 h-4 text-zinc-500" />
                                    <span>Customer Profile</span>
                                </CardTitle>
                                <CardDescription>
                                    Client assigned to this license.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                {device.user ? (
                                    <div className="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20 space-y-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-[#0071e3] text-white flex items-center justify-center font-bold text-sm">
                                                {device.user.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-zinc-900 dark:text-zinc-100">{device.user.name}</p>
                                                <p className="text-xs text-zinc-500">{device.user.email}</p>
                                            </div>
                                        </div>

                                        {device.user.phone && (
                                            <div className="text-xs text-zinc-600 dark:text-zinc-400 flex items-center gap-1.5 pt-1 border-t border-zinc-200 dark:border-zinc-800">
                                                <Phone className="w-3.5 h-3.5 text-zinc-400" />
                                                <span>{device.user.phone}</span>
                                            </div>
                                        )}

                                        <div className="text-xs text-zinc-500 pt-1">
                                            Assigned on: <span className="font-medium text-zinc-900 dark:text-zinc-100">{device.created_at}</span>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-xs text-zinc-500 italic text-center py-2">
                                        No customer profile linked to this record.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Section 4: Internal Reseller Notes */}
                        <Card className="border-zinc-200 dark:border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold">Reseller Notes</CardTitle>
                                <CardDescription>
                                    Private notes for your business records regarding this customer's license.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSaveNotes} className="space-y-3">
                                    <Textarea
                                        value={notesForm.data.notes}
                                        onChange={(e) => notesForm.setData('notes', e.target.value)}
                                        placeholder="Add payment notes, invoice references, or customer comments..."
                                        rows={4}
                                        className="text-xs resize-none"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={notesForm.processing}
                                        className="w-full text-xs font-medium"
                                    >
                                        {notesForm.processing ? 'Saving Notes...' : 'Save Notes'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Section 5: Telemetry Timestamps */}
                        <Card className="border-zinc-200 dark:border-zinc-800">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <Clock className="w-4 h-4 text-zinc-500" />
                                    <span>Activity Timestamps</span>
                                </CardTitle>
                                <CardDescription>
                                    Timezone: Africa/Cairo
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-xs">
                                <div className="flex items-center justify-between py-1.5 border-b border-zinc-200 dark:border-zinc-800">
                                    <span className="text-zinc-500">First Assigned</span>
                                    <span className="font-mono text-zinc-900 dark:text-zinc-100 font-medium">{device.created_at}</span>
                                </div>
                                <div className="flex items-center justify-between py-1.5 border-b border-zinc-200 dark:border-zinc-800">
                                    <span className="text-zinc-500">Last Check-In</span>
                                    <div className="text-end">
                                        <p className="font-mono text-zinc-900 dark:text-zinc-100 font-semibold">
                                            {device.telemetry?.last_check_date_full || 'Never'}
                                        </p>
                                        {device.telemetry?.last_check_date && (
                                            <p className="text-[10px] text-zinc-500">{device.telemetry.last_check_date}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center justify-between py-1.5">
                                    <span className="text-zinc-500">Last Modified</span>
                                    <span className="font-mono text-zinc-900 dark:text-zinc-100">{device.updated_at}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Danger Zone */}
                        <Card className="border-red-200 dark:border-red-900/50 bg-red-50/20 dark:bg-red-950/10">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-semibold text-red-600 dark:text-red-400">
                                    Revoke License Access
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Unassigning removes the device from the customer's account and halts application execution.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleUnassign}
                                    className="w-full text-xs font-medium text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-950/40"
                                >
                                    <Trash2 className="w-3.5 h-3.5 mr-1" />
                                    <span>Unassign Customer Device</span>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
