import React, { useState, useEffect, useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import {
    Zap,
    Monitor,
    Search,
    CheckCircle2,
    Clock,
    User,
    Package,
    AlertCircle,
    Loader2,
} from 'lucide-react';
import axios from 'axios';

/* ─── Types ─────────────────────────────────────────────────────── */

interface SoftwarePackage {
    id: number;
    name: string;
    price: number;
    currency: string;
    billing_cycle: string;
    billing_days: number | null;
    description: string | null;
}

interface Software {
    id: number;
    name: string;
    logo_url: string | null;
    pricing_type: 'free' | 'single' | 'packages';
    requires_payment: boolean;
    price: number | null;
    currency: string;
    billing_cycle: string;
    billing_days: number | null;
    packages: SoftwarePackage[];
}

interface RecentActivation {
    id: number;
    device_id: string;
    software_name: string;
    package_name: string | null;
    machine_name: string | null;
    user_name: string | null;
    status: string;
    expires_at: string | null;
    is_lifetime: boolean;
    updated_at: string;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
}

interface DeviceLookupResult {
    found: boolean;
    device_id: string;
    machine_name?: string;
    user_name?: string;
    status?: string;
    current_software?: string;
    current_package?: string;
}

interface Props {
    softwares: Software[];
    recentActivations: RecentActivation[];
    users: UserItem[];
    initialDeviceId?: string;
    initialSoftwareId?: number | null;
}

type DurationType = 'lifetime' | 'software_default' | 'package_default' | 'days' | 'date';

/* ─── Component ─────────────────────────────────────────────────── */

export default function QuickActivate({ softwares, recentActivations, users, initialDeviceId = '', initialSoftwareId = null }: Props) {
    const { props } = usePage<any>();
    const flash = props.flash as { success?: string; error?: string } | undefined;
    const activatedInfo = props.activated_info as Record<string, string> | undefined;

    const [deviceId, setDeviceId]           = useState(initialDeviceId);
    const [softwareId, setSoftwareId]       = useState<string>(initialSoftwareId ? String(initialSoftwareId) : '');
    const [packageId, setPackageId]         = useState<string>('');
    const [durationType, setDurationType]   = useState<DurationType>('lifetime');
    const [durationDays, setDurationDays]   = useState<string>('30');
    const [expiresDate, setExpiresDate]     = useState<string>('');
    const [userId, setUserId]               = useState<string>('');
    const [notes, setNotes]                 = useState<string>('');
    const [submitting, setSubmitting]       = useState(false);
    const [lookupResult, setLookupResult]   = useState<DeviceLookupResult | null>(null);
    const [lookupLoading, setLookupLoading] = useState(false);
    const lookupTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const selectedSoftware = softwares.find(s => String(s.id) === softwareId) ?? null;
    const hasPackages = (selectedSoftware?.packages?.length ?? 0) > 0;
    const isPackagesPricing = selectedSoftware?.pricing_type === 'packages';

    useEffect(() => {
        setPackageId('');
        setDurationType('lifetime');
    }, [softwareId]);

    useEffect(() => {
        if (lookupTimer.current) clearTimeout(lookupTimer.current);
        if (deviceId.trim().length < 4) { setLookupResult(null); return; }
        lookupTimer.current = setTimeout(() => triggerLookup(deviceId.trim()), 600);
        return () => { if (lookupTimer.current) clearTimeout(lookupTimer.current); };
    }, [deviceId, softwareId]);

    function triggerLookup(id: string) {
        setLookupLoading(true);
        const params: Record<string, string> = { device_id: id };
        if (softwareId) params.software_id = softwareId;
        axios.get(route('serial-devices.lookup'), { params })
            .then(res => setLookupResult(res.data))
            .catch(() => setLookupResult(null))
            .finally(() => setLookupLoading(false));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!deviceId.trim() || !softwareId) return;
        setSubmitting(true);
        router.post(
            route('serial-devices.execute-quick-activate'),
            {
                device_id:     deviceId.trim(),
                software_id:   softwareId,
                package_id:    packageId || null,
                duration_type: durationType,
                duration_days: durationType === 'days' ? durationDays : null,
                expires_date:  durationType === 'date' ? expiresDate : null,
                user_id:       userId || null,
                notes:         notes || null,
            },
            { onFinish: () => setSubmitting(false), preserveScroll: true }
        );
    }

    function durationOptions(sw: Software | null): { value: DurationType; label: string }[] {
        const base: { value: DurationType; label: string }[] = [
            { value: 'lifetime',         label: 'Lifetime (no expiry)' },
            { value: 'software_default', label: sw ? `Software default (${sw.billing_cycle})` : 'Software default' },
            { value: 'days',             label: 'Custom days' },
            { value: 'date',             label: 'Specific date' },
        ];
        if (hasPackages && packageId) base.splice(1, 0, { value: 'package_default', label: 'Package default' });
        return base;
    }

    return (
        <AdminSidebarLayout>
            <Head title="Quick Activate" />

            <div className="p-6 max-w-5xl mx-auto space-y-6">

                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-lg bg-primary/10">
                        <Zap className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold">Quick Activate</h1>
                        <p className="text-sm text-muted-foreground">Activate a device immediately without going through the device list.</p>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-500/30 bg-green-500/10 p-4 text-sm text-green-700 dark:text-green-400 flex gap-2 items-start">
                        <CheckCircle2 className="w-4 h-4 mt-0.5 shrink-0" />
                        <div>
                            <p>{flash.success}</p>
                            {activatedInfo?.whatsapp_confirmation && (
                                <pre className="mt-2 text-xs whitespace-pre-wrap opacity-70">{activatedInfo.whatsapp_confirmation}</pre>
                            )}
                        </div>
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive flex gap-2 items-start">
                        <AlertCircle className="w-4 h-4 mt-0.5 shrink-0" />
                        <p>{flash.error}</p>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <Card className="lg:col-span-2">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-medium">Device Activation</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-5">

                                <div className="space-y-1.5">
                                    <Label htmlFor="device_id">Device ID</Label>
                                    <div className="relative">
                                        <Monitor className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                        <Input
                                            id="device_id"
                                            className="pl-9 font-mono text-sm"
                                            placeholder="Paste device ID here…"
                                            value={deviceId}
                                            onChange={e => setDeviceId(e.target.value)}
                                            required
                                        />
                                        {lookupLoading && (
                                            <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-muted-foreground" />
                                        )}
                                    </div>

                                    {lookupResult && (
                                        <div className={`rounded-md border px-3 py-2 text-xs space-y-0.5 ${
                                            lookupResult.found
                                                ? 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-400'
                                                : 'border-muted bg-muted/40 text-muted-foreground'
                                        }`}>
                                            {lookupResult.found ? (
                                                <>
                                                    <p className="font-medium flex items-center gap-1">
                                                        <Search className="w-3 h-3" /> Device found in system
                                                    </p>
                                                    {lookupResult.machine_name && <p>Machine: <span className="font-mono">{lookupResult.machine_name}</span></p>}
                                                    {lookupResult.user_name && <p>Windows user: <span className="font-mono">{lookupResult.user_name}</span></p>}
                                                    {lookupResult.current_software && <p>Registered software: {lookupResult.current_software}</p>}
                                                    {lookupResult.status && (
                                                        <p>Current status: <Badge variant={lookupResult.status === 'active' ? 'default' : 'secondary'} className="text-[10px] py-0">{lookupResult.status}</Badge></p>
                                                    )}
                                                </>
                                            ) : (
                                                <p className="flex items-center gap-1">
                                                    <Search className="w-3 h-3" /> Device not found — will be created on activation.
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="software_id">Software</Label>
                                    <Select value={softwareId} onValueChange={v => setSoftwareId(v ?? "")} required>
                                        <SelectTrigger id="software_id">
                                            <SelectValue placeholder="Select a software…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {softwares.map(sw => (
                                                <SelectItem key={sw.id} value={String(sw.id)}>
                                                    {sw.name}
                                                    {sw.pricing_type !== 'free' && (
                                                        <span className="text-xs text-muted-foreground ml-1">({sw.pricing_type})</span>
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {isPackagesPricing && hasPackages && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="package_id">
                                            Package <span className="text-muted-foreground text-xs">(optional)</span>
                                        </Label>
                                        <Select value={packageId} onValueChange={v => setPackageId(v ?? "")}>
                                            <SelectTrigger id="package_id">
                                                <SelectValue placeholder="No specific package" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="">No specific package</SelectItem>
                                                {selectedSoftware?.packages.map(pkg => (
                                                    <SelectItem key={pkg.id} value={String(pkg.id)}>
                                                        {pkg.name} — {pkg.price} {pkg.currency} / {pkg.billing_cycle}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}

                                <Separator />

                                <div className="space-y-1.5">
                                    <Label htmlFor="duration_type">Duration</Label>
                                    <Select value={durationType} onValueChange={v => setDurationType(v as DurationType)}>
                                        <SelectTrigger id="duration_type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {durationOptions(selectedSoftware).map(opt => (
                                                <SelectItem key={opt.value} value={opt.value}>{opt.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {durationType === 'days' && (
                                        <Input
                                            type="number"
                                            min={1}
                                            max={3650}
                                            value={durationDays}
                                            onChange={e => setDurationDays(e.target.value)}
                                            placeholder="Number of days"
                                            className="mt-1.5"
                                        />
                                    )}
                                    {durationType === 'date' && (
                                        <Input
                                            type="date"
                                            value={expiresDate}
                                            onChange={e => setExpiresDate(e.target.value)}
                                            className="mt-1.5"
                                        />
                                    )}
                                </div>

                                <Separator />

                                <div className="space-y-1.5">
                                    <Label htmlFor="user_id">
                                        Assign to user <span className="text-muted-foreground text-xs">(optional)</span>
                                    </Label>
                                    <Select value={userId} onValueChange={v => setUserId(v ?? "")}>
                                        <SelectTrigger id="user_id">
                                            <SelectValue placeholder="No user assignment" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">No assignment</SelectItem>
                                            {users.map(u => (
                                                <SelectItem key={u.id} value={String(u.id)}>
                                                    {u.name} ({u.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="notes">
                                        Notes <span className="text-muted-foreground text-xs">(optional)</span>
                                    </Label>
                                    <Textarea
                                        id="notes"
                                        value={notes}
                                        onChange={e => setNotes(e.target.value)}
                                        placeholder="Customer name, order reference…"
                                        rows={2}
                                        className="resize-none text-sm"
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full gap-2"
                                    disabled={submitting || !deviceId.trim() || !softwareId}
                                >
                                    {submitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Zap className="w-4 h-4" />}
                                    {submitting ? 'Activating…' : 'Activate Device'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        {selectedSoftware && (
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Package className="w-4 h-4" /> Software Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-sm space-y-2 text-muted-foreground">
                                    <div className="flex justify-between">
                                        <span>Pricing</span>
                                        <Badge variant="outline" className="text-xs">{selectedSoftware.pricing_type}</Badge>
                                    </div>
                                    {selectedSoftware.price !== null && (
                                        <div className="flex justify-between">
                                            <span>Price</span>
                                            <span className="font-mono">{selectedSoftware.price} {selectedSoftware.currency}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span>Billing</span>
                                        <span>{selectedSoftware.billing_cycle}</span>
                                    </div>
                                    {selectedSoftware.packages.length > 0 && (
                                        <div className="flex justify-between">
                                            <span>Packages</span>
                                            <span>{selectedSoftware.packages.length} available</span>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <Clock className="w-4 h-4" /> Recent Activations
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {recentActivations.length === 0 ? (
                                    <p className="text-xs text-muted-foreground p-4">No recent activations.</p>
                                ) : (
                                    <ul className="divide-y divide-border">
                                        {recentActivations.map(act => (
                                            <li key={act.id} className="px-4 py-2.5 space-y-0.5 hover:bg-muted/40 transition-colors">
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="font-mono text-xs truncate max-w-[120px]" title={act.device_id}>
                                                        {act.device_id.length > 12 ? `${act.device_id.slice(0, 8)}…` : act.device_id}
                                                    </span>
                                                    <Badge variant={act.status === 'active' ? 'default' : 'secondary'} className="text-[10px] py-0 shrink-0">
                                                        {act.status}
                                                    </Badge>
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {act.software_name}{act.package_name ? ` · ${act.package_name}` : ''}
                                                </p>
                                                <p className="text-xs text-muted-foreground flex items-center gap-1">
                                                    {act.user_name && <><User className="w-3 h-3" />{act.user_name} · </>}
                                                    {act.is_lifetime ? 'Lifetime' : (act.expires_at ?? '—')}
                                                </p>
                                                <p className="text-[10px] text-muted-foreground/60">{act.updated_at}</p>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}