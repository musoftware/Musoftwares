import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import ActivityFeed from '../Components/Widgets/ActivityFeed';
import PipelineBoard from '../Components/Kanban/PipelineBoard';
import { PhoneCall, Calendar, Target, Zap } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';

export default function TelesalesDashboard({ pipeline, kpis, activityFeed }: { pipeline: any, kpis: any, activityFeed?: any }) {
    return (
        <CrmLayout title={__('general.telesales_workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('general.telesales_workspace')}</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        {__('general.good_morning_you_have')} <span className="font-semibold text-slate-700">{kpis?.pending_followups ?? 0}</span> {__('general.follow_ups_due_today')}
                    </p>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KPICard 
                        title={__('general.calls_made_today')} 
                        value={kpis?.calls_today ?? 0} 
                        icon={PhoneCall} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('general.pending_follow_ups')} 
                        value={kpis?.pending_followups ?? 0} 
                        icon={Calendar} 
                        colorClass="bg-red-100 text-red-600" 
                    />
                    <KPICard 
                        title={__('general.conversion_rate')} 
                        value={kpis?.conversion_rate ?? '0%'} 
                        icon={Target} 
                        colorClass="bg-green-100 text-green-600" 
                    />
                    <KPICard 
                        title={__('general.hot_leads')} 
                        value={kpis?.hot_leads ?? 0} 
                        icon={Zap} 
                        colorClass="bg-amber-100 text-amber-600" 
                    />
                </div>

                {/* Main Content Area (Pipeline & Activity Feed) */}
                <div className="flex-1 flex gap-6 min-h-0">
                    {/* Kanban Pipeline */}
                    <Card className="flex-1 flex flex-col overflow-hidden border-slate-200 shadow-sm">
                        <CardHeader className="p-4 border-b border-slate-100 flex flex-row justify-between items-center bg-slate-50/50 space-y-0">
                            <CardTitle className="font-semibold text-slate-800">{__('general.active_pipeline')}</CardTitle>
                            <button className="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                                {__('general.view_all_leads')}
                            </button>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-hidden">
                            <PipelineBoard />
                        </CardContent>
                    </Card>

                    {/* Right Sidebar (Live Feed) */}
                    <div className="w-80 hidden xl:block flex-shrink-0">
                        <ActivityFeed feed={activityFeed} />
                    </div>
                </div>

            </div>
        </CrmLayout>
    );
}
