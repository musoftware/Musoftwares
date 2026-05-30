import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Upload, Plus, Users, ShieldCheck, Database } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

export default function CollectorDashboard({ stats, recentImports }: { stats: any, recentImports: any[] }) {
    return (
        <CrmLayout title={__('Collector Workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('Lead Acquisition Workspace')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('Manage incoming pipelines and import bulk lists.')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" className="flex items-center gap-2">
                            <Upload size={16} className="text-muted-foreground" />
                            {__('Bulk CSV Import')}
                        </Button>
                        <Button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                            <Plus size={16} />
                            {__('Quick Add')}
                        </Button>
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
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold">{__('Recent Imports')}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            {recentImports && recentImports.length > 0 ? (
                                <div className="space-y-3">
                                    {recentImports.map((importJob, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-full bg-muted text-muted-foreground flex items-center justify-center font-bold text-xs shrink-0">
                                                    {importJob.name.charAt(0)}
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-sm text-foreground">{importJob.name}</p>
                                                    <p className="text-xs text-muted-foreground">{importJob.phone}</p>
                                                </div>
                                            </div>
                                            <div className="text-xs font-medium text-muted-foreground bg-muted px-2 py-1 rounded">
                                                {new Date(importJob.created_at).toLocaleDateString()}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg">
                                    {__('No recent imports available.')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
