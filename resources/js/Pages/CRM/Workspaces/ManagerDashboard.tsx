import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Activity, AlertTriangle, Users, Trophy } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';

export default function ManagerDashboard({ branchKpis, slaAlerts }: { branchKpis: any, slaAlerts: any }) {
    return (
        <CrmLayout title={__('Manager Workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('Operations Oversight')}</h1>
                    <p className="text-sm text-muted-foreground mt-1">{__('Branch performance and agent tracking.')}</p>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <KPICard 
                        title={__('Branch Conversion Rate')} 
                        value={branchKpis?.conversion_rate ?? '0%'} 
                        icon={Activity} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('SLA Breaches')} 
                        value={slaAlerts?.total ?? 0} 
                        icon={AlertTriangle} 
                        colorClass="bg-red-100 text-red-600" 
                    />
                    <KPICard 
                        title={__('Active Telesales Agents')} 
                        value={branchKpis?.active_agents ?? 0} 
                        icon={Users} 
                        colorClass="bg-purple-100 text-purple-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold flex items-center gap-2">
                                <Trophy size={18} className="text-amber-500" />
                                {__('Agent Leaderboard')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            <div className="flex items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg">
                                {__('No agent data available for this branch.')}
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
