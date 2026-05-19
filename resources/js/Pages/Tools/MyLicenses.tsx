import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ExternalLink, Key, Copy, Check, Monitor, Smartphone, ShoppingBag } from 'lucide-react';

interface License {
    id: number; license_key: string; status: string; is_valid: boolean;
    max_devices: number; active_devices: number; expires_at: string | null;
    last_validated: string | null;
    tool: { slug: string; title: string; icon_url: string | null; category: string };
}
interface Props { licenses: License[] }

const STATUS_STYLE: Record<string, string> = {
    active:    'bg-emerald-50 text-emerald-700 border-emerald-200',
    suspended: 'bg-amber-50 text-amber-700 border-amber-200',
    revoked:   'bg-red-50 text-red-700 border-red-200',
};

export default function MyLicenses({ licenses }: Props) {
    const [copiedId, setCopiedId] = useState<number | null>(null);

    const copyKey = (id: number, key: string) => {
        navigator.clipboard.writeText(key);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    return (
        <ToolsPublicLayout title="My Licenses" activeNav="licenses">
            <Head title="My Licenses" />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

                {/* Page header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">My Licenses</h1>
                    <p className="text-sm text-slate-500 mt-1">Your license keys — used by the runtime to verify your subscriptions locally.</p>
                </div>

                {licenses.length === 0 ? (
                    <div className="text-center py-14 bg-white border border-slate-200 rounded-xl">
                        <div className="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3">
                            <Key className="h-6 w-6 text-slate-300" />
                        </div>
                        <p className="text-sm font-semibold text-slate-700">No licenses yet</p>
                        <p className="text-xs text-slate-400 mt-1 mb-4">Subscribe to a tool to receive your license key.</p>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('tools.explore'))}
                        >
                            <ShoppingBag className="h-3.5 w-3.5 mr-1.5" />
                            Browse Tools
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {licenses.map(lic => {
                            const devicePct = Math.min((lic.active_devices / lic.max_devices) * 100, 100);
                            return (
                                <div key={lic.id} className="bg-white border border-slate-200 rounded-xl overflow-hidden hover:border-slate-300 transition-colors">
                                    {/* Card header */}
                                    <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                                {lic.tool.icon_url
                                                    ? <img src={lic.tool.icon_url} alt="" className="w-6 h-6 object-contain" />
                                                    : <Monitor className="h-4 w-4 text-slate-300" />
                                                }
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold text-slate-900">{lic.tool.title}</p>
                                                <p className="text-xs text-slate-400 capitalize">{lic.tool.category}</p>
                                            </div>
                                        </div>
                                        <Badge className={`${STATUS_STYLE[lic.status] ?? 'bg-slate-50 text-slate-600 border-slate-200'} border hover:bg-transparent text-[11px] px-2 py-0.5`}>
                                            {lic.status}
                                        </Badge>
                                    </div>

                                    <div className="px-5 py-4 space-y-4">
                                        {/* License key */}
                                        <div className="flex items-center gap-2 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5">
                                            <Key className="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                                            <code className="text-xs font-mono text-slate-700 flex-1 break-all select-all">{lic.license_key}</code>
                                            <button
                                                onClick={() => copyKey(lic.id, lic.license_key)}
                                                className="flex-shrink-0 p-1.5 rounded-md hover:bg-slate-200 transition-colors text-slate-400 hover:text-slate-600"
                                            >
                                                {copiedId === lic.id
                                                    ? <Check className="h-3.5 w-3.5 text-emerald-500" />
                                                    : <Copy className="h-3.5 w-3.5" />
                                                }
                                            </button>
                                        </div>

                                        {/* Device usage */}
                                        <div className="space-y-1.5">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-slate-500">Devices</span>
                                                <span className="font-semibold text-slate-800">{lic.active_devices} / {lic.max_devices}</span>
                                            </div>
                                            <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div
                                                    className={`h-full rounded-full transition-all ${devicePct >= 100 ? 'bg-red-400' : devicePct >= 80 ? 'bg-amber-400' : 'bg-emerald-400'}`}
                                                    style={{ width: `${devicePct}%` }}
                                                />
                                            </div>
                                        </div>

                                        {/* Meta grid */}
                                        <div className="grid grid-cols-2 gap-3">
                                            <div className="bg-slate-50 rounded-lg p-3 text-center">
                                                <p className="text-xs font-semibold text-slate-800">{lic.expires_at ?? 'Lifetime'}</p>
                                                <p className="text-[10px] text-slate-400 mt-0.5">Expires</p>
                                            </div>
                                            <div className="bg-slate-50 rounded-lg p-3 text-center">
                                                <p className="text-xs font-semibold text-slate-800 truncate">{lic.last_validated ?? 'Never'}</p>
                                                <p className="text-[10px] text-slate-400 mt-0.5">Last used</p>
                                            </div>
                                        </div>

                                        {/* Actions */}
                                        <div className="flex gap-2 pt-1">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="flex-1 gap-2 text-xs border-slate-200 text-slate-600 hover:bg-slate-50 h-8"
                                                onClick={() => router.visit(route('tools.devices', lic.id))}
                                            >
                                                <Smartphone className="h-3.5 w-3.5" />
                                                Devices ({lic.active_devices})
                                            </Button>
                                            <Link href={route('tools.show', lic.tool.slug)} className="flex-1">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="w-full gap-2 text-xs border-indigo-200 text-indigo-600 hover:bg-indigo-50 h-8"
                                                >
                                                    <ExternalLink className="h-3.5 w-3.5" />
                                                    Open Tool
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </ToolsPublicLayout>
    );
}
