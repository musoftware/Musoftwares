import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { ActivityFeed } from '@/Components/ui/ActivityFeed';
import { EmptyState } from '@/Components/ui/EmptyState';
import { MetricCard } from '@/Components/ui/MetricCard';
import { Radio, Search, Zap, Activity, Globe, MonitorPlay, Layers } from 'lucide-react';
import { formatDate } from '@/lib/utils';

export default function CompetitorsShow({ competitor }: any) {
    const menuItems = [
        { id: 'intelligence-dashboard', label: 'Feed', icon: Zap, href: '/intelligence', isActive: false },
        { id: 'competitors', label: 'Competitors', icon: Radio, href: '/intelligence/competitors', isActive: true },
        { id: 'ads', label: 'Ad Tracker', icon: Search, href: '/intelligence/ads', isActive: false },
        { id: 'ugc', label: 'UGC Tracker', icon: Activity, href: '/intelligence/ugc', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title={`${competitor.name} Radar`}
            workspaceName="Market Intelligence"
            tenantId="INTEL-CORE"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title={competitor.name}
                    description={competitor.domain || "Tracking competitor activity..."}
                    actions={
                        <Link 
                            href={`/intelligence/competitors/${competitor.id}/edit`}
                            className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm"
                        >
                            Edit Configuration
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <MetricCard 
                        label="Tracked Assets"
                        value={competitor.tracked_assets?.length || 0}
                        icon={Globe}
                    />
                    <MetricCard 
                        label="Ads Captured"
                        value={competitor.ads?.length || 0}
                        icon={MonitorPlay}
                    />
                    <MetricCard 
                        label="Total Events"
                        value={competitor.activities?.length || 0}
                        icon={Activity}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        <OperationalCard title="Activity Radar" description="Recent intelligence logged for this competitor." noPadding>
                            {competitor.activities?.length > 0 ? (
                                <ActivityFeed items={competitor.activities.map((act: any) => ({
                                    id: act.id,
                                    event: act.event_type,
                                    description: `Event: ${act.event_type}`,
                                    created_at: act.created_at,
                                    icon: 'zap',
                                    color: 'rose'
                                }))} />
                            ) : (
                                <EmptyState 
                                    icon={Activity}
                                    title="Radar is clear"
                                    description="No recent events detected."
                                />
                            )}
                        </OperationalCard>

                        <OperationalCard title="Known Ads" action={<Link href="/intelligence/ads" className="text-xs font-bold text-rose-600 hover:underline">View All</Link>} noPadding>
                             {competitor.ads?.length > 0 ? (
                                <div className="divide-y divide-slate-100">
                                    {competitor.ads.map((ad: any) => (
                                        <div key={ad.id} className="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                                            <div className="text-sm font-medium text-slate-900 truncate pr-4">
                                                {ad.ad_copy || `Ad ID: ${ad.ad_id}`}
                                            </div>
                                            <div className="text-xs text-slate-500 whitespace-nowrap">
                                                {formatDate(ad.first_seen_at)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                             ) : (
                                <EmptyState 
                                    icon={MonitorPlay}
                                    title="No ads captured"
                                    description="We haven't detected any active ads for this competitor yet."
                                />
                             )}
                        </OperationalCard>
                    </div>
                    
                    <div className="space-y-6">
                        <OperationalCard title="Tracked Assets">
                            {competitor.tracked_assets?.length > 0 ? (
                                <div className="space-y-3">
                                    {competitor.tracked_assets.map((asset: any) => (
                                        <div key={asset.id} className="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50">
                                            <div className="flex items-center gap-2">
                                                <div className="w-6 h-6 rounded bg-white flex items-center justify-center border border-slate-200">
                                                    <Globe className="w-3 h-3 text-slate-500" />
                                                </div>
                                                <div className="text-xs font-medium text-slate-900 truncate max-w-[120px]">
                                                    {asset.url}
                                                </div>
                                            </div>
                                            <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Active</span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-xs text-slate-500">No assets configured for tracking.</p>
                            )}
                        </OperationalCard>
                    </div>
                </div>
            </div>
        </WorkspaceLayout>
    );
}
