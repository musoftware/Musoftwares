import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Activity, AlertTriangle, Users, Trophy } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import LeaderboardTable from '../Components/Widgets/LeaderboardTable';
import SlaAlertsList from '../Components/Widgets/SlaAlertsList';

export default function ManagerDashboard({ branchKpis, slaAlerts, leaderboard }: { branchKpis: any, slaAlerts: any, leaderboard: any }) {
    return (
        <CrmLayout title={__('general.manager_workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('general.operations_oversight')}</h1>
                    <p className="text-sm text-muted-foreground mt-1">{__('general.branch_performance_and_agent_tracking')}</p>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <KPICard 
                        title={__('general.branch_conversion_rate')} 
                        value={branchKpis?.conversion_rate ?? '0%'} 
                        icon={Activity} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('general.sla_breaches')} 
                        value={slaAlerts?.total ?? 0} 
                        icon={AlertTriangle} 
                        colorClass="bg-red-100 text-red-600" 
                    />
                    <KPICard 
                        title={__('general.active_telesales_agents')} 
                        value={branchKpis?.active_agents ?? 0} 
                        icon={Users} 
                        colorClass="bg-purple-100 text-purple-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-[2] flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2">
                                <Trophy size={18} className="text-amber-500" />
                                {__('general.agent_leaderboard')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-0 overflow-y-auto">
                            <LeaderboardTable leaderboard={leaderboard} />
                        </CardContent>
                    </Card>

                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm border-red-200">
                        <CardHeader className="p-4 border-b bg-red-50/50 border-red-100 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2 text-red-700">
                                <AlertTriangle size={18} className="text-red-500" />
                                {__('general.sla_breaches_stale_leads')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto bg-slate-50/30">
                            <SlaAlertsList leads={slaAlerts?.leads || []} />
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
