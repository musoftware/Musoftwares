import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Radio, Search, Zap, Activity, Filter, BookmarkPlus, PlaySquare } from 'lucide-react';
import { formatDate } from '@/lib/utils';

export default function AdsFeed({ ads }: any) {
    const menuItems = [
        { id: 'intelligence-dashboard', label: 'Feed', icon: Zap, href: '/intelligence', isActive: false },
        { id: 'competitors', label: 'Competitors', icon: Radio, href: '/intelligence/competitors', isActive: false },
        { id: 'ads', label: 'Ad Tracker', icon: Search, href: '/intelligence/ads', isActive: true },
        { id: 'ugc', label: 'UGC Tracker', icon: Activity, href: '/intelligence/ugc', isActive: false },
    ];

    const adList = ads?.data || [];

    return (
        <WorkspaceLayout 
            title="Ad Tracker Feed"
            workspaceName="Market Intelligence"
            tenantId="INTEL-CORE"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title="Ad Intelligence Feed"
                    description="Real-time stream of detected competitor creatives and active campaigns."
                    actions={
                        <button className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                            <Filter className="w-4 h-4 mr-2" /> Filter
                        </button>
                    }
                />

                {adList.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                        <EmptyState 
                            icon={PlaySquare}
                            title="No ads in the feed"
                            description="Ads will appear here once the trackers detect new campaigns."
                        />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {adList.map((ad: any) => (
                            <div key={ad.id} className="group flex flex-col bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                                <div className="aspect-square bg-slate-100 flex items-center justify-center relative">
                                    {ad.creative_url ? (
                                        <img src={ad.creative_url} alt="Ad Creative" className="w-full h-full object-cover" />
                                    ) : (
                                        <PlaySquare className="w-8 h-8 text-slate-300" />
                                    )}
                                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-start justify-end p-2">
                                        <button className="w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm text-slate-700 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center hover:text-rose-600 hover:bg-white">
                                            <BookmarkPlus className="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div className="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-black/60 backdrop-blur-md text-white">
                                        {ad.platform || 'Unknown'}
                                    </div>
                                </div>
                                <div className="p-4 flex flex-col flex-1">
                                    <div className="text-xs font-semibold text-rose-600 mb-1">
                                        {ad.competitor?.name || 'Unknown Brand'}
                                    </div>
                                    <p className="text-sm text-slate-700 line-clamp-3 mb-4 flex-1 leading-relaxed">
                                        {ad.ad_copy || "No copy detected..."}
                                    </p>
                                    <div className="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                                        <span>First seen: {formatDate(ad.first_seen_at)}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </WorkspaceLayout>
    );
}
