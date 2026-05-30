import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Activity, AlertTriangle, Users, Trophy } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ManagerDashboard({ branchKpis, slaAlerts }: { branchKpis: any, slaAlerts: any }) {
    return (
        <CrmLayout title={__('Manager Workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('Operations Oversight')}</h1>
                    <p className="text-sm text-slate-500 mt-1">{__('Branch performance and agent tracking.')}</p>
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
                    <div className="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div className="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                                <Trophy size={18} className="text-amber-500" />
                                {__('Agent Leaderboard')}
                            </h2>
                        </div>
                        <div className="flex-1 p-4 overflow-y-auto">
                            <div className="flex items-center justify-center h-40 text-sm text-slate-400 border border-dashed border-slate-200 rounded-lg">
                                {__('No agent data available for this branch.')}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </CrmLayout>
    );
}
