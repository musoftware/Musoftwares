import React from 'react';
import { Head, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Download, ShoppingBag, Star, Activity, Smartphone, Wifi, Clock, ArrowLeft, AlertTriangle } from 'lucide-react';

interface Device {
    id: number; device_name: string; os: string; app_version: string | null;
    status: string; is_active: boolean; last_seen_at: string;
    ip_address: string | null; hardware_fingerprint: string;
}
interface Props {
    license: { id: number; license_key: string; max_devices: number; tool: { slug: string; title: string } };
    devices: Device[];
}

export default function Devices({ license, devices }: Props) {
    const activeCount = devices.filter(d => d.is_active).length;

    const handleRevoke = (deviceId: number) => {
        if (!confirm('Revoke this device? It will lose access immediately.')) return;
        router.delete(route('tools.devices.revoke', { licenseId: license.id, deviceId }), {
            preserveScroll: true
        });
    };

    const osIcons: Record<string, string> = { windows: '🪟', mac: '🍎', linux: '🐧' };

    return (
        <WorkspaceLayout title="Device Activations" workspaceName="Tools" tenantId="SYS-TOOLS"
            menuItems={[
                { id: 'explore',   label: 'Explore',     icon: ShoppingBag, href: route('tools.explore'),     isActive: false },
                { id: 'downloads', label: 'Downloads',   icon: Download,    href: route('tools.downloads'),   isActive: false },
                { id: 'licenses',  label: 'My Licenses', icon: Star,        href: route('tools.my-licenses'), isActive: true  },
                { id: 'billing',   label: 'Billing',     icon: Activity,    href: route('tools.billing'),     isActive: false },
            ]}>
            <Head title="Device Activations" />
            <div className="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
                <button onClick={() => router.visit(route('tools.my-licenses'))}
                    className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition-colors">
                    <ArrowLeft className="h-4 w-4" /> Back to Licenses
                </button>

                <ModulePageHeader
                    title={`Device Activations — ${license.tool.title}`}
                    description={`${activeCount} of ${license.max_devices} activation slots used.`}
                />

                {/* Capacity warning */}
                {activeCount >= license.max_devices && (
                    <div className="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                        <AlertTriangle className="h-4 w-4 flex-shrink-0 mt-0.5" />
                        <span>You've reached the maximum number of activations. Revoke a device below to activate a new one.</span>
                    </div>
                )}

                {devices.length === 0 ? (
                    <EmptyState icon={Smartphone} title="No devices activated"
                        description="Open the desktop app and log in with your license key to activate this device." />
                ) : (
                    <div className="space-y-3">
                        {devices.map(device => (
                            <div key={device.id} className={`bg-white border rounded-2xl p-5 flex items-center gap-4 shadow-sm transition-all ${device.is_active ? 'border-slate-200/80' : 'border-slate-100 opacity-60'}`}>
                                {/* OS Icon */}
                                <div className="text-2xl flex-shrink-0">{osIcons[device.os] ?? '💻'}</div>

                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-1">
                                        <p className="font-semibold text-slate-900 text-sm truncate">{device.device_name}</p>
                                        <Badge className={`text-xs flex-shrink-0 ${device.is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-100'}`}>
                                            {device.status}
                                        </Badge>
                                    </div>
                                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <span className="flex items-center gap-1 capitalize"><Smartphone className="h-3 w-3" />{device.os}</span>
                                        {device.app_version && <span className="font-mono">v{device.app_version}</span>}
                                        <span className="flex items-center gap-1"><Clock className="h-3 w-3" />{device.last_seen_at}</span>
                                        {device.ip_address && <span className="flex items-center gap-1"><Wifi className="h-3 w-3" />{device.ip_address}</span>}
                                        <span className="font-mono text-slate-300">ID:{device.hardware_fingerprint}</span>
                                    </div>
                                </div>

                                {device.is_active && (
                                    <Button variant="outline" size="sm" className="flex-shrink-0 text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300"
                                        onClick={() => handleRevoke(device.id)}>
                                        Revoke
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </WorkspaceLayout>
    );
}
