import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Activity, AlertTriangle, Users, Trophy, CheckSquare, Briefcase, Megaphone } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import LeaderboardTable from '../Components/Widgets/LeaderboardTable';
import SlaAlertsList from '../Components/Widgets/SlaAlertsList';
import ManagerProjectsList from '../Components/Widgets/ManagerProjectsList';
import ManagerCampaignsList from '../Components/Widgets/ManagerCampaignsList';

export default function ManagerDashboard({ branchKpis, slaAlerts, leaderboard, projects = [], campaigns = [] }: { branchKpis: any, slaAlerts: any, leaderboard: any, projects?: any[], campaigns?: any[] }) {
    return (
        <CrmLayout title={__('general.manager_workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('general.operations_oversight')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('general.branch_performance_and_agent_tracking')}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <a 
                            href={route('crm.reports.index')} 
                            className="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
                        >
                            {__('general.export_reports')}</a>
                        <a 
                            href={route('erp.manager.approvals.index')} 
                            className="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2"
                        >
                            {__('general.review_approvals')}</a>
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                    <KPICard 
                        title={__('general.tasks_completed')} 
                        value={branchKpis?.tasks_completed ?? 0} 
                        icon={CheckSquare} 
                        colorClass="bg-emerald-100 text-emerald-600" 
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

                {/* Secondary Content Area - Projects & Campaigns */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2">
                                <Briefcase size={18} className="text-blue-500" />
                                {__('general.active_projects')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            <ManagerProjectsList projects={projects} />
                        </CardContent>
                    </Card>

                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2">
                                <Megaphone size={18} className="text-purple-500" />
                                {__('general.active_campaigns')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            <ManagerCampaignsList campaigns={campaigns} />
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
