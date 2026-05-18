import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { ActivityFeed } from '@/Components/ui/ActivityFeed';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Search, Plus, Radio, Zap, Activity } from 'lucide-react';

export default function Dashboard({ activities = [] }: any) {
    const menuItems = [
        { id: 'intelligence-dashboard', label: 'Feed', icon: Zap, href: '/intelligence', isActive: true },
        { id: 'competitors', label: 'Competitors', icon: Radio, href: '/intelligence/competitors', isActive: false },
        { id: 'ads', label: 'Ad Tracker', icon: Search, href: '/intelligence/ads', isActive: false },
        { id: 'ugc', label: 'UGC Tracker', icon: Activity, href: '/intelligence/ugc', isActive: false },
    ];

    const feedItems = activities.map((act: any) => ({
        id: act.id,
        user_id: null,
        subject_type: 'competitor',
        subject_id: act.competitor_id,
        event: act.event_type,
        description: `Detected ${act.event_type.replace('_', ' ')} for ${act.competitor?.name || 'Unknown Competitor'}`,
        properties: act.data_json,
        workspace: 'intelligence',
        created_at: act.created_at,
        icon: 'zap',
        color: 'rose',
        user: null
    }));

    return (
        <WorkspaceLayout 
            title="Intelligence Feed"
            workspaceName="Market Intelligence"
            tenantId="INTEL-CORE"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title="Market Intelligence"
                    description="Real-time operational feed of competitor updates, new ads, and market changes."
                    actions={
                        <Link 
                            href="/intelligence/competitors/create" 
                            className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                        >
                            <Plus className="w-4 h-4 mr-2 stroke-[1.5]" /> Track Competitor
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2">
                        <OperationalCard title="Live Market Feed" description="Recent activities detected across your tracked assets." noPadding>
                            {feedItems.length > 0 ? (
                                <ActivityFeed items={feedItems} />
                            ) : (
                                <EmptyState 
                                    icon={Zap}
                                    title="Quiet on the front"
                                    description="No recent competitor activity detected. Try adding more competitors to track."
                                    action="/intelligence/competitors"
                                    actionLabel="Manage Trackers"
                                />
                            )}
                        </OperationalCard>
                    </div>
                    
                    <div className="space-y-6">
                        <OperationalCard title="Quick Search">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input 
                                    type="text" 
                                    placeholder="Search brands, ads, keywords..." 
                                    className="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                />
                            </div>
                        </OperationalCard>

                        <div className="bg-rose-50/50 border border-rose-100 rounded-2xl p-5 text-sm leading-relaxed text-slate-600 space-y-2">
                            <div className="flex items-center gap-1.5 font-semibold text-rose-900">
                                <Activity className="h-4 w-4 text-rose-500" /> Auto-Tracking Active
                            </div>
                            <p>
                                The system is continuously monitoring your saved Facebook Pages, TikTok accounts, and Domains for new activity.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </WorkspaceLayout>
    );
}
