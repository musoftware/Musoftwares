import React from 'react';
import { Head, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Download, ShoppingBag, Activity, Receipt, XCircle, CheckCircle2 } from 'lucide-react';

interface Subscription {
    id: number; plan_name: string; billing_cycle: string; amount_paid: number;
    currency: string; status: string; is_active: boolean;
    starts_at: string; expires_at: string | null;
    tool: { slug: string; title: string; icon_url: string | null; category: string };
}
interface Props { subscriptions: Subscription[] }

export default function Billing({ subscriptions }: Props) {
    const handleCancel = (id: number) => {
        if (!confirm('Cancel this subscription? Access continues until the end of your current period.')) return;
        router.post(route('tools.subscriptions.cancel', id), {}, { preserveScroll: true });
    };

    const statusColors: Record<string, string> = {
        active:    'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-slate-100 text-slate-500',
        expired:   'bg-red-100 text-red-600',
        suspended: 'bg-amber-100 text-amber-700',
    };

    return (
        <WorkspaceLayout title="Tool Billing" workspaceName="Tools" tenantId="SYS-TOOLS"
            menuItems={[
                { id: 'explore',   label: 'Explore',   icon: ShoppingBag, href: route('tools.explore'),   isActive: false },
                { id: 'downloads', label: 'Downloads', icon: Download,    href: route('tools.downloads'), isActive: false },
                { id: 'billing',   label: 'Billing',   icon: Activity,    href: route('tools.billing'),   isActive: true  },
            ]}>
            <Head title="Tool Billing" />
            <div className="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
                <div className="flex items-start justify-between">
                    <ModulePageHeader title="Billing & Subscriptions" description="Manage your tool subscriptions and billing history." />
                    <Button variant="outline" size="sm" className="gap-2" onClick={() => router.visit(route('tools.explore'))}>
                        <ShoppingBag className="h-4 w-4" /> Browse More Tools
                    </Button>
                </div>

                {subscriptions.length === 0 ? (
                    <EmptyState icon={Receipt} title="No subscriptions yet"
                        description="Subscribe to a tool to see your billing history here."
                        action={{ label: 'Browse Tools', href: route('tools.explore') }} />
                ) : (
                    <div className="space-y-4">
                        {subscriptions.map(sub => (
                            <div key={sub.id} className={`bg-white border rounded-2xl p-6 shadow-sm ${sub.is_active ? 'border-slate-200/80' : 'border-slate-100'}`}>
                                <div className="flex items-start gap-4">
                                    <div className="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        {sub.tool.icon_url
                                            ? <img src={sub.tool.icon_url} alt="" className="w-8 h-8 object-contain" />
                                            : <Download className="h-5 w-5 text-slate-400" />}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="font-semibold text-slate-900">{sub.tool.title}</p>
                                                <p className="text-sm text-slate-500">{sub.plan_name} · {sub.billing_cycle}</p>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <Badge className={`${statusColors[sub.status] ?? ''} hover:${statusColors[sub.status]}`}>{sub.status}</Badge>
                                                <p className="text-lg font-bold text-slate-900 mt-1">${sub.amount_paid.toFixed(2)}<span className="text-xs text-slate-400 font-normal ml-1">{sub.currency}</span></p>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-x-6 gap-y-1 mt-3 text-xs text-slate-500">
                                            <span>Started: {sub.starts_at}</span>
                                            {sub.expires_at && <span>Expires: {sub.expires_at}</span>}
                                        </div>
                                    </div>
                                </div>

                                {sub.is_active && (
                                    <div className="flex gap-3 mt-5 pt-4 border-t border-slate-100">
                                        <Button variant="outline" size="sm" className="gap-2"
                                            onClick={() => router.visit(route('tools.download.generate', sub.tool.slug))}>
                                            <Download className="h-3.5 w-3.5" /> Download
                                        </Button>
                                        <Button variant="outline" size="sm" className="gap-2 text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300"
                                            onClick={() => handleCancel(sub.id)}>
                                            <XCircle className="h-3.5 w-3.5" /> Cancel
                                        </Button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </WorkspaceLayout>
    );
}
