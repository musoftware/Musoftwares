import React from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, Link } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Radio, Search, Zap, Activity, LayoutGrid, Folder, Plus } from 'lucide-react';

export default function SwipeVaultIndex({ collections = [] }: any) {
    const menuItems = [
        { id: 'intelligence-dashboard', label: 'Feed', icon: Zap, href: '/intelligence', isActive: false },
        { id: 'competitors', label: 'Competitors', icon: Radio, href: '/intelligence/competitors', isActive: false },
        { id: 'ads', label: 'Ad Tracker', icon: Search, href: '/intelligence/ads', isActive: false },
        { id: 'ugc', label: 'UGC Tracker', icon: Activity, href: '/intelligence/ugc', isActive: false },
        { id: 'swipe', label: 'Swipe Vault', icon: LayoutGrid, href: '/intelligence/swipe-vault', isActive: true },
    ];

    return (
        <WorkspaceLayout 
            title="Swipe Vault"
            workspaceName="Market Intelligence"
            tenantId="INTEL-CORE"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title="Creative Swipe Vault"
                    description="Organize your saved ads, hooks, and landing page snapshots into collections."
                    actions={
                        <button className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm">
                            <Plus className="w-4 h-4 mr-2" /> New Collection
                        </button>
                    }
                />

                <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    {collections.length === 0 ? (
                        <div className="col-span-full">
                            <OperationalCard>
                                <EmptyState 
                                    icon={Folder}
                                    title="Vault is empty"
                                    description="Create collections to start saving market intelligence."
                                />
                            </OperationalCard>
                        </div>
                    ) : collections.map((col: any) => (
                        <Link key={col.id} href={`/intelligence/swipe-vault/${col.id}`} className="group bg-white rounded-xl border border-slate-200 p-5 hover:border-indigo-300 hover:shadow-sm transition block">
                            <div className="w-12 h-12 rounded-lg bg-indigo-50 flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                                <Folder className="w-6 h-6 text-indigo-500" />
                            </div>
                            <h3 className="font-semibold text-slate-900 mb-1">{col.name}</h3>
                            <p className="text-xs text-slate-500">{col.items_count || 0} items saved</p>
                        </Link>
                    ))}
                </div>
            </div>
        </WorkspaceLayout>
    );
}
