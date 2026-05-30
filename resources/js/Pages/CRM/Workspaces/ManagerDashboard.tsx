import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Activity, AlertTriangle, Users } from 'lucide-react';
import { __ } from '@/utils/translations';

export default function ManagerDashboard({ branchKpis, slaAlerts }: { branchKpis: any, slaAlerts: any }) {
    return (
        <AppLayout title={__('Manager Workspace')}>
            <div className="flex-1 space-y-4 p-8 pt-6">
                <div className="flex items-center justify-between space-y-2">
                    <h2 className="text-3xl font-bold tracking-tight">{__('Operations Oversight')}</h2>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Branch Conversion Rate')}</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{branchKpis?.conversion_rate ?? '0%'}</div>
                        </CardContent>
                    </Card>
                    <Card className="border-red-200 bg-red-50/50">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-red-600">{__('SLA Breaches')}</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{slaAlerts?.total ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Active Telesales Agents')}</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{branchKpis?.active_agents ?? 0}</div>
                        </CardContent>
                    </Card>
                </div>

                <div className="mt-8">
                    <h3 className="text-xl font-bold tracking-tight mb-4">{__('Agent Leaderboard')}</h3>
                    <Card>
                        <CardContent className="p-0">
                            <div className="p-4 text-sm text-muted-foreground text-center">
                                {__('No agent data available for this branch.')}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
