import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Smartphone, MessagesSquare, BarChart3, LayoutTemplate, Activity } from 'lucide-react';

import AccountsTab from './WhatsAppOS/AccountsTab';
import CampaignsTab from './WhatsAppOS/CampaignsTab';
import ReportsTab from './WhatsAppOS/ReportsTab';
import TemplatesTab from './WhatsAppOS/TemplatesTab';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function WhatsAppSenderRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const [activeTab, setActiveTab] = useState<'accounts' | 'campaigns' | 'reports' | 'templates'>('campaigns');

    const tabs = [
        { id: 'accounts', label: 'Accounts', icon: Smartphone },
        { id: 'campaigns', label: 'Campaigns', icon: MessagesSquare },
        { id: 'reports', label: 'Reports', icon: BarChart3 },
        { id: 'templates', label: 'Templates', icon: LayoutTemplate },
    ] as const;

    return (
        <ToolsPublicLayout title="WhatsApp Campaign OS" toolSlug={pluginSlug}>
            <Head title="WhatsApp Campaign OS" />

            <div className="min-h-screen bg-slate-50 pb-20">
                {/* Header & Tabs Navigation */}
                <div className="bg-white border-b border-slate-200 sticky top-0 z-30 pt-4">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col md:flex-row md:items-center justify-between mb-6">
                            <div>
                                <h1 className="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                                    WhatsApp Campaign OS
                                    <span className="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider">Operational</span>
                                </h1>
                            </div>
                            <div className="mt-4 md:mt-0 flex items-center gap-2">
                                <div className="flex items-center gap-2 bg-slate-100 px-4 py-2 rounded-xl text-xs font-bold text-slate-600">
                                    <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                                    Runtime Connected
                                </div>
                            </div>
                        </div>

                        <div className="flex overflow-x-auto hide-scrollbar gap-6">
                            {tabs.map(tab => {
                                const Icon = tab.icon;
                                const isActive = activeTab === tab.id;
                                return (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`pb-4 text-sm font-black transition-colors flex items-center gap-2 border-b-2 whitespace-nowrap ${
                                            isActive 
                                                ? 'border-emerald-500 text-emerald-600' 
                                                : 'border-transparent text-slate-500 hover:text-slate-800'
                                        }`}
                                    >
                                        <Icon className={`w-4 h-4 ${isActive ? 'text-emerald-500' : 'text-slate-400'}`} />
                                        {tab.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Main Workspace Area */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                    {activeTab === 'accounts' && <AccountsTab runtimePort={runtimePort} />}
                    {activeTab === 'campaigns' && <CampaignsTab runtimePort={runtimePort} />}
                    {activeTab === 'reports' && <ReportsTab />}
                    {activeTab === 'templates' && <TemplatesTab />}
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
