import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Upload, Plus, Users, ShieldCheck, Database } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function CollectorDashboard({ stats, recentImports }: { stats: any, recentImports: any[] }) {
    return (
        <CrmLayout title={__('Collector Workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('Lead Acquisition Workspace')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('Manage incoming pipelines and import bulk lists.')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <button className="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm transition-colors">
                            <Upload size={16} className="text-slate-500" />
                            {__('Bulk CSV Import')}
                        </button>
                        <button className="flex items-center gap-2 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 shadow-sm transition-colors">
                            <Plus size={16} />
                            {__('Quick Add')}
                        </button>
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <KPICard 
                        title={__('Total Leads Added Today')} 
                        value={stats?.total_added ?? 0} 
                        icon={Database} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('Duplicates Prevented')} 
                        value={stats?.duplicates_prevented ?? 0} 
                        icon={ShieldCheck} 
                        colorClass="bg-emerald-100 text-emerald-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <div className="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div className="p-4 border-b border-slate-100 bg-slate-50/50">
                            <h2 className="font-semibold text-slate-800">{__('Recent Imports')}</h2>
                        </div>
                        <div className="flex-1 p-4 overflow-y-auto">
                            {recentImports && recentImports.length > 0 ? (
                                <div className="space-y-3">
                                    {recentImports.map((importJob, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 rounded-lg border border-slate-100 hover:bg-slate-50 transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs shrink-0">
                                                    {importJob.name.charAt(0)}
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-sm text-slate-800">{importJob.name}</p>
                                                    <p className="text-xs text-slate-500">{importJob.phone}</p>
                                                </div>
                                            </div>
                                            <div className="text-xs font-medium text-slate-400 bg-slate-100 px-2 py-1 rounded">
                                                {new Date(importJob.created_at).toLocaleDateString()}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-40 text-sm text-slate-400 border border-dashed border-slate-200 rounded-lg">
                                    {__('No recent imports available.')}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

            </div>
        </CrmLayout>
    );
}
