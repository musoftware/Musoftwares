import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card } from '@/Components/ui/card';
import { ExternalLink, Key, Copy, Check, Monitor, Smartphone, ShoppingBag } from 'lucide-react';

interface License {
    id: number; license_key: string; status: string; is_valid: boolean;
    expires_at: string | null;
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
                    <Card className="text-center py-14">
                        <div className="w-12 h-12 rounded-2xl bg-secondary flex items-center justify-center mx-auto mb-3">
                            <Key className="h-6 w-6 text-muted-foreground" />
                        </div>
                        <p className="text-sm font-semibold">No licenses yet</p>
                        <p className="text-xs text-muted-foreground mt-1 mb-4">Subscribe to a tool to receive your license key.</p>
                        
                        <Link href={route('tools.marketplace.index')}>
                            <Button className="gap-2 h-9 text-xs">
                                <ShoppingBag className="h-4 w-4" /> Browse Tools
                            </Button>
                        </Link>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {licenses.map(lic => {
                            return (
                                <Card key={lic.id} className="overflow-hidden hover:shadow-md transition-shadow relative group">
                                    <div className="p-5">
                                        <div className="flex justify-between items-start mb-4">
                                            <div className="w-10 h-10 rounded-xl bg-secondary flex items-center justify-center shrink-0">
                                                {lic.tool.icon_url ? <img src={lic.tool.icon_url} className="w-6 h-6 object-contain" alt="" /> : <Key className="h-5 w-5 text-muted-foreground" />}
                                            </div>
                                            <Badge variant="secondary" className={STATUS_STYLE[lic.status] || ''}>
                                                {lic.status}
                                            </Badge>
                                        </div>
                                        <h3 className="font-bold mb-1">{lic.tool.title}</h3>
                                        <p className="text-xs text-muted-foreground mb-4 font-mono truncate">{lic.license_key}</p>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => copyKey(lic.id, lic.license_key)}
                                                className="flex-1 gap-2 text-xs h-8"
                                            >
                                                {copiedId === lic.id ? <Check className="h-3.5 w-3.5 text-emerald-500" /> : <Copy className="h-3.5 w-3.5" />}
                                                {copiedId === lic.id ? 'Copied' : 'Copy Key'}
                                            </Button>
                                            <Link href={route('tools.run', lic.tool.slug)} className="flex-1">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="w-full gap-2 text-xs border-indigo-200 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950 h-8"
                                                >
                                                    <ExternalLink className="h-3.5 w-3.5" />
                                                    Open Tool
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </ToolsPublicLayout>
    );
}
