import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Radio, Search, Zap, Activity, Plus, Users, LayoutGrid } from 'lucide-react';

export default function UgcIndex({ creators = [] }: any) {
    const menuItems = [
        { id: 'intelligence-dashboard', label: 'Feed', icon: Zap, href: '/intelligence', isActive: false },
        { id: 'competitors', label: 'Competitors', icon: Radio, href: '/intelligence/competitors', isActive: false },
        { id: 'ads', label: 'Ad Tracker', icon: Search, href: '/intelligence/ads', isActive: false },
        { id: 'ugc', label: 'UGC Tracker', icon: Activity, href: '/intelligence/ugc', isActive: true },
        { id: 'swipe', label: 'Swipe Vault', icon: LayoutGrid, href: '/intelligence/swipe-vault', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title="UGC Tracker"
            workspaceName="Market Intelligence"
            tenantId="INTEL-CORE"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title="UGC Creator Roster"
                    description="Keep track of high-performing creators, viral hooks, and niche influencers."
                    actions={
                        <button className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm">
                            <Plus className="w-4 h-4 mr-2" /> Add Creator
                        </button>
                    }
                />

                <OperationalCard noPadding>
                    {creators.length === 0 ? (
                        <div className="p-8">
                            <EmptyState 
                                icon={Users}
                                title="No creators tracked"
                                description="Start adding creators to build your UGC roster."
                            />
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {creators.map((creator: any) => (
                                <div key={creator.id} className="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                                            {creator.name.charAt(0)}
                                        </div>
                                        <div>
                                            <div className="font-medium text-slate-900 text-sm">
                                                {creator.name}
                                            </div>
                                            <div className="text-xs text-slate-500 mt-0.5">
                                                {creator.platform} • @{creator.handle}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-sm font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                        {creator.niche || 'General'}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </OperationalCard>
            </div>
        </WorkspaceLayout>
    );
}
