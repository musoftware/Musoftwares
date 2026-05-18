import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Download, ShoppingBag, Star, Activity, Key, Copy, Check, Monitor, Smartphone, Clock } from 'lucide-react';

interface License {
    id: number; license_key: string; status: string; is_valid: boolean;
    max_devices: number; active_devices: number; expires_at: string | null;
    last_validated: string | null;
    tool: { slug: string; title: string; icon_url: string | null; category: string };
}
interface Props { licenses: License[] }

export default function MyLicenses({ licenses }: Props) {
    const [copiedId, setCopiedId] = useState<number | null>(null);

    const copyKey = (id: number, key: string) => {
        navigator.clipboard.writeText(key);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    const statusColors: Record<string, string> = {
        active: 'bg-emerald-100 text-emerald-700',
        suspended: 'bg-amber-100 text-amber-700',
        revoked: 'bg-red-100 text-red-700',
    };

    return (
        <WorkspaceLayout title="My Licenses" workspaceName="Tools" tenantId="SYS-TOOLS"
            menuItems={[
                { id: 'explore',   label: 'Explore',     icon: ShoppingBag, href: route('tools.explore'),     isActive: false },
                { id: 'downloads', label: 'Downloads',   icon: Download,    href: route('tools.downloads'),   isActive: false },
                { id: 'licenses',  label: 'My Licenses', icon: Star,        href: route('tools.my-licenses'), isActive: true  },
                { id: 'billing',   label: 'Billing',     icon: Activity,    href: route('tools.billing'),     isActive: false },
            ]}>
            <Head title="My Licenses" />
            <div className="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
                <ModulePageHeader title="My Licenses" description="Your issued license keys. Copy the key and paste it into the desktop app on first launch." />

                {licenses.length === 0 ? (
                    <EmptyState icon={Key} title="No licenses yet"
                        description="Subscribe to a tool to receive your license key."
                        action={{ label: 'Browse Tools', href: route('tools.explore') }} />
                ) : (
                    <div className="space-y-4">
                        {licenses.map(lic => (
                            <div key={lic.id} className="bg-white border border-slate-200/80 rounded-2xl p-6 space-y-5 shadow-sm">
                                {/* Header */}
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            {lic.tool.icon_url
                                                ? <img src={lic.tool.icon_url} alt="" className="w-7 h-7 object-contain" />
                                                : <Monitor className="h-5 w-5 text-slate-400" />}
                                        </div>
                                        <div>
                                            <p className="font-semibold text-slate-900">{lic.tool.title}</p>
                                            <p className="text-xs text-slate-400 capitalize">{lic.tool.category}</p>
                                        </div>
                                    </div>
                                    <Badge className={`${statusColors[lic.status] ?? 'bg-slate-100 text-slate-600'} hover:${statusColors[lic.status]}`}>
                                        {lic.status}
                                    </Badge>
                                </div>

                                {/* License key */}
                                <div className="bg-slate-50 rounded-xl p-4 flex items-center gap-3 border border-slate-100">
                                    <Key className="h-4 w-4 text-slate-400 flex-shrink-0" />
                                    <code className="text-xs font-mono text-slate-700 flex-1 break-all select-all">{lic.license_key}</code>
                                    <button onClick={() => copyKey(lic.id, lic.license_key)}
                                        className="flex-shrink-0 p-1.5 rounded-lg hover:bg-slate-200 transition-colors text-slate-500">
                                        {copiedId === lic.id ? <Check className="h-4 w-4 text-emerald-500" /> : <Copy className="h-4 w-4" />}
                                    </button>
                                </div>

                                {/* Stats */}
                                <div className="grid grid-cols-3 gap-4 text-center">
                                    <div className="bg-slate-50 rounded-lg p-3">
                                        <p className="text-lg font-bold text-slate-900">{lic.active_devices}/{lic.max_devices}</p>
                                        <p className="text-xs text-slate-500 mt-0.5">Devices</p>
                                    </div>
                                    <div className="bg-slate-50 rounded-lg p-3">
                                        <p className="text-sm font-semibold text-slate-900">{lic.expires_at ?? 'Lifetime'}</p>
                                        <p className="text-xs text-slate-500 mt-0.5">Expires</p>
                                    </div>
                                    <div className="bg-slate-50 rounded-lg p-3">
                                        <p className="text-xs font-semibold text-slate-900 truncate">{lic.last_validated ?? 'Never'}</p>
                                        <p className="text-xs text-slate-500 mt-0.5">Last used</p>
                                    </div>
                                </div>

                                {/* Actions */}
                                <div className="flex gap-3 pt-1">
                                    <Button variant="outline" size="sm" className="gap-2 flex-1"
                                        onClick={() => router.visit(route('tools.devices', lic.id))}>
                                        <Smartphone className="h-4 w-4" /> Manage Devices ({lic.active_devices})
                                    </Button>
                                    <Button variant="outline" size="sm" className="gap-2 flex-1"
                                        onClick={() => router.visit(route('tools.download.generate', lic.tool.slug))}>
                                        <Download className="h-4 w-4" /> Download Tool
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </WorkspaceLayout>
    );
}
