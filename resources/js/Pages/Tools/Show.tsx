import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Download, ShoppingBag, Star, Activity,
    Monitor, CheckCircle2, ChevronDown, ChevronUp,
    Shield, Cpu, ArrowLeft, LogIn
} from 'lucide-react';

interface PricingPlan {
    id: number; name: string; price_monthly: number; price_yearly: number;
    max_devices: number; features: string[]; is_popular: boolean; yearly_savings: number;
}
interface ToolVersion {
    version: string; changelog: string; is_latest: boolean;
    is_beta: boolean; file_size: string; released_at: string;
}
interface Props {
    tool: {
        id: number; slug: string; title: string; description: string;
        short_description: string; icon_url: string | null; category: string;
        category_label: string; supported_os: string[]; current_version: string;
        download_count: number; is_featured: boolean; features: string[];
        requirements: string[];
        screenshots: { id: number; url: string; caption: string | null }[];
        pricing_plans: PricingPlan[];
        versions: ToolVersion[];
    };
    userSubscription: { id: number; plan_name: string; billing_cycle: string; status: string; expires_at: string; } | null;
    userLicense: { id: number; license_key: string; max_devices: number; active_devices: number; } | null;
}

export default function Show({ tool, userSubscription, userLicense }: Props) {
    const { auth } = usePage().props as any;
    const isAuthed = !!auth?.user;
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>('monthly');
    const [activeScreenshot, setActiveScreenshot] = useState(0);
    const [expandedVersion, setExpandedVersion] = useState<string | null>(tool.versions[0]?.version ?? null);
    const isSubscribed = !!userSubscription && userSubscription.status === 'active';

    const handleSubscribeClick = (planId: number) => {
        if (!isAuthed) {
            router.visit(route('login'));
            return;
        }
        router.visit(route('tools.checkout', { slug: tool.slug, planId }));
    };

    return (
        <ToolsPublicLayout title={tool.title} activeNav="explore">
            <Head title={tool.title} />
            <div className="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-10">
                <button onClick={() => router.visit(route('tools.explore'))}
                    className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition-colors">
                    <ArrowLeft className="h-4 w-4" /> Back to Marketplace
                </button>

                {/* Hero */}
                <div className="flex flex-col md:flex-row gap-6 items-start">
                    <div className="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center flex-shrink-0 border border-slate-200">
                        {tool.icon_url ? <img src={tool.icon_url} alt={tool.title} className="w-14 h-14 object-contain" />
                            : <Monitor className="h-10 w-10 text-slate-400" />}
                    </div>
                    <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                            <Badge variant="outline" className="text-slate-500 capitalize">{tool.category_label}</Badge>
                            <span className="text-xs text-slate-400">v{tool.current_version}</span>
                            {tool.is_featured && <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">Featured</Badge>}
                        </div>
                        <h1 className="text-2xl font-bold text-slate-900 mb-2">{tool.title}</h1>
                        <p className="text-slate-600 max-w-2xl leading-relaxed">{tool.short_description}</p>
                        <div className="flex items-center gap-4 mt-4">
                            {isSubscribed ? (
                                <>
                                    <Button onClick={() => router.visit(route('tools.download.generate', tool.slug))} className="gap-2">
                                        <Download className="h-4 w-4" /> Download Latest
                                    </Button>
                                    <span className="text-sm text-emerald-600 font-medium flex items-center gap-1">
                                        <CheckCircle2 className="h-4 w-4" /> Active — {userSubscription!.plan_name}
                                    </span>
                                </>
                            ) : (
                                <p className="text-sm text-slate-500">Choose a plan below to get started.</p>
                            )}
                        <div className="flex gap-1 ml-auto">
                                {(Array.isArray(tool.supported_os)
                                    ? tool.supported_os
                                    : typeof tool.supported_os === 'string'
                                        ? JSON.parse(tool.supported_os)
                                        : []
                                ).map((os: string) => (
                                    <span key={os} className="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded capitalize font-medium">{os}</span>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Screenshots */}
                {(tool.screenshots ?? []).length > 0 && (
                    <div className="space-y-3">
                        <div className="relative bg-slate-900 rounded-2xl overflow-hidden aspect-video">
                            <img src={tool.screenshots[activeScreenshot]?.url} alt="" className="w-full h-full object-cover" />
                        </div>
                        {tool.screenshots.length > 1 && (
                            <div className="flex gap-2 overflow-x-auto pb-2">
                                {tool.screenshots.map((s, i) => (
                                    <button key={s.id} onClick={() => setActiveScreenshot(i)}
                                        className={`flex-shrink-0 w-24 h-16 rounded-lg overflow-hidden border-2 transition-all ${activeScreenshot === i ? 'border-slate-900' : 'border-transparent opacity-60 hover:opacity-100'}`}>
                                        <img src={s.url} alt="" className="w-full h-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        {tool.description && (
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900 mb-3">About this tool</h2>
                                <div className="text-slate-600 leading-relaxed whitespace-pre-wrap text-sm">{tool.description}</div>
                            </div>
                        )}
                        {(tool.features ?? []).length > 0 && (
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900 mb-4">Features</h2>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {(tool.features ?? []).map((f: string, i: number) => (
                                        <div key={i} className="flex items-start gap-2.5">
                                            <CheckCircle2 className="h-4 w-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                                            <span className="text-sm text-slate-700">{f}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                        {(tool.versions ?? []).length > 0 && (
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900 mb-4">Release Notes</h2>
                                <div className="space-y-3">
                                    {(tool.versions ?? []).map(v => (
                                        <div key={v.version} className="border border-slate-200 rounded-xl overflow-hidden">
                                            <button className="w-full flex items-center justify-between p-4 text-left hover:bg-slate-50 transition-colors"
                                                onClick={() => setExpandedVersion(expandedVersion === v.version ? null : v.version)}>
                                                <div className="flex items-center gap-3">
                                                    <span className="font-mono text-sm font-semibold text-slate-800">v{v.version}</span>
                                                    {v.is_latest && <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100 text-xs">Latest</Badge>}
                                                    {v.is_beta && <Badge className="bg-orange-100 text-orange-700 hover:bg-orange-100 text-xs">Beta</Badge>}
                                                    <span className="text-xs text-slate-400">{v.released_at}</span>
                                                </div>
                                                {expandedVersion === v.version ? <ChevronUp className="h-4 w-4 text-slate-400" /> : <ChevronDown className="h-4 w-4 text-slate-400" />}
                                            </button>
                                            {expandedVersion === v.version && v.changelog && (
                                                <div className="px-4 pb-4 text-sm text-slate-600 leading-relaxed whitespace-pre-wrap border-t border-slate-100 pt-3">{v.changelog}</div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="space-y-6">
                        {userLicense && (
                            <Card className="border-emerald-200 bg-emerald-50/50">
                                <CardContent className="p-4 space-y-2">
                                    <p className="text-sm font-semibold text-emerald-800 flex items-center gap-2"><Shield className="h-4 w-4" /> Active License</p>
                                    <p className="text-xs text-emerald-700 font-mono break-all">{userLicense.license_key}</p>
                                    <p className="text-xs text-emerald-600">{userLicense.active_devices}/{userLicense.max_devices} devices activated</p>
                                    <Button variant="outline" size="sm" className="w-full mt-2 border-emerald-300 text-emerald-700"
                                        onClick={() => router.visit(route('tools.devices', userLicense.id))}>
                                        Manage Devices
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        {!isSubscribed && (tool.pricing_plans ?? []).length > 0 && (
                            <div>
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-base font-semibold text-slate-900">Plans</h2>
                                    <div className="flex items-center bg-slate-100 rounded-lg p-0.5 text-xs font-medium">
                                        {(['monthly', 'yearly'] as const).map(cycle => (
                                            <button key={cycle} onClick={() => setBillingCycle(cycle)}
                                                className={`px-2.5 py-1 rounded-md transition-all capitalize ${billingCycle === cycle ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'}`}>
                                                {cycle}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                                <div className="space-y-3">
                                    {tool.pricing_plans.map(plan => (
                                        <div key={plan.id} className={`rounded-xl border p-4 space-y-3 ${plan.is_popular ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white'}`}>
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <p className={`font-semibold text-sm ${plan.is_popular ? 'text-white' : 'text-slate-900'}`}>{plan.name}</p>
                                                    {plan.is_popular && <Badge className="bg-amber-400 text-amber-900 hover:bg-amber-400 text-xs mt-1">Most Popular</Badge>}
                                                </div>
                                                <div className="text-right">
                                                    <span className={`text-xl font-bold ${plan.is_popular ? 'text-white' : 'text-slate-900'}`}>
                                                        ${billingCycle === 'monthly' ? plan.price_monthly : (plan.price_yearly / 12).toFixed(0)}
                                                    </span>
                                                    <span className={`text-xs ${plan.is_popular ? 'text-slate-300' : 'text-slate-400'}`}>/mo</span>
                                                    {billingCycle === 'yearly' && plan.yearly_savings > 0 && (
                                                        <p className={`text-xs ${plan.is_popular ? 'text-emerald-300' : 'text-emerald-600'}`}>Save {plan.yearly_savings}%</p>
                                                    )}
                                                </div>
                                            </div>
                                            <p className={`text-xs ${plan.is_popular ? 'text-slate-300' : 'text-slate-500'}`}>Up to {plan.max_devices} device{plan.max_devices > 1 ? 's' : ''}</p>
                                            <ul className="space-y-1">
                                                {(plan.features ?? []).slice(0, 4).map((f: string, i: number) => (
                                                    <li key={i} className={`flex items-start gap-1.5 text-xs ${plan.is_popular ? 'text-slate-200' : 'text-slate-600'}`}>
                                                        <CheckCircle2 className={`h-3 w-3 flex-shrink-0 mt-0.5 ${plan.is_popular ? 'text-emerald-300' : 'text-emerald-500'}`} />{f}
                                                    </li>
                                                ))}
                                            </ul>
                                            <Button onClick={() => handleSubscribeClick(plan.id)}
                                                className={`w-full text-sm ${plan.is_popular ? 'bg-white text-slate-900 hover:bg-slate-100' : ''}`}
                                                variant={plan.is_popular ? 'secondary' : 'default'}>
                                                {isAuthed ? `Get ${plan.name}` : <><LogIn className="h-3.5 w-3.5 mr-1.5" />Sign in to Subscribe</>}
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {(tool.requirements ?? []).length > 0 && (
                            <div>
                                <h3 className="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
                                    <Cpu className="h-4 w-4 text-slate-400" /> System Requirements
                                </h3>
                                <ul className="space-y-1.5">
                                    {(tool.requirements ?? []).map((r: string, i: number) => (
                                        <li key={i} className="text-xs text-slate-600 flex items-start gap-2">
                                            <span className="text-slate-300 mt-0.5">—</span>{r}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
