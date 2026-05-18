import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Radio, Search, Plus, Zap, Activity, Globe } from 'lucide-react';

export default function CompetitorsIndex({ competitors = [] }: any) {
    const menuItems = [
        { id: 'intelligence-dashboard', label: 'Feed', icon: Zap, href: '/intelligence', isActive: false },
        { id: 'competitors', label: 'Competitors', icon: Radio, href: '/intelligence/competitors', isActive: true },
        { id: 'ads', label: 'Ad Tracker', icon: Search, href: '/intelligence/ads', isActive: false },
        { id: 'ugc', label: 'UGC Tracker', icon: Activity, href: '/intelligence/ugc', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title="Competitor Radar"
            workspaceName="Market Intelligence"
            tenantId="INTEL-CORE"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title="Competitor Radar"
                    description="Manage brands, domains, and social profiles you are actively tracking."
                    actions={
                        <Link 
                            href="#" 
                            className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                        >
                            <Plus className="w-4 h-4 mr-2 stroke-[1.5]" /> Add Competitor
                        </Link>
                    }
                />

                <OperationalCard noPadding>
                    <div className="divide-y divide-slate-100">
                        {competitors.length === 0 ? (
                            <EmptyState 
                                icon={Radio}
                                title="No competitors tracked yet"
                                description="Start adding brands to monitor their ads, landing pages, and UGC creators."
                            />
                        ) : competitors.map((comp: any) => (
                            <div key={comp.id} className="p-4 hover:bg-slate-50 transition flex items-center justify-between">
                                <div className="flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 text-slate-500 font-bold">
                                        {comp.name.charAt(0)}
                                    </div>
                                    <div>
                                        <Link href={`/intelligence/competitors/${comp.id}`} className="font-medium text-slate-900 text-sm hover:text-rose-600 transition">
                                            {comp.name}
                                        </Link>
                                        <div className="flex items-center gap-3 mt-1 text-xs text-slate-500">
                                            {comp.domain && <span className="flex items-center gap-1"><Globe className="w-3 h-3" /> {comp.domain}</span>}
                                            <span>{comp.tracked_assets_count || 0} Assets</span>
                                            <span>{comp.ads_count || 0} Ads Logged</span>
                                        </div>
                                    </div>
                                </div>
                                <Link 
                                    href={`/intelligence/competitors/${comp.id}`}
                                    className="px-3 py-1.5 text-xs font-medium bg-white border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition"
                                >
                                    View Radar
                                </Link>
                            </div>
                        ))}
                    </div>
                </OperationalCard>
            </div>
        </WorkspaceLayout>
    );
}
