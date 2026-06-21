import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Activity, LayoutGrid, BarChart3, Zap } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Log {
    id: number;
    description: string;
    created_at: string;
    action: string;
}

interface Metrics {
    avgFragmentationScore: number;
    avgUtilization: number;
    optimizationCount: number;
    recentLogs: Log[];
}

export default function OptimizationDashboard({ metrics }: { metrics: Metrics }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.smart_slots_optimization')}</h1>
                    <p className="text-muted-foreground mt-2">{__('general.ai_driven_gap_reduction_and_dynamic_load_balancing')}</p>
                </div>
                <Button>
                    <Zap className="me-2 h-4 w-4" />{__('general.run_manual_optimization')}</Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium">{__('general.avg_fragmentation_score')}</CardTitle>
                        <LayoutGrid className="h-4 w-4 text-amber-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{metrics.avgFragmentationScore}/100</div>
                        <p className="text-xs text-muted-foreground mt-1">{__('general.lower_is_better_represents_scattered_gaps')}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium">{__('general.resource_utilization')}</CardTitle>
                        <BarChart3 className="h-4 w-4 text-emerald-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{metrics.avgUtilization}%</div>
                        <p className="text-xs text-muted-foreground mt-1">{__('general.total_booked_vs_available_hours')}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium">Optimizations Ran (30d)</CardTitle>
                        <Activity className="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{metrics.optimizationCount}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{__('general.recent_optimizations')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {metrics.recentLogs.map((log) => (
                            <div key={log.id} className="flex items-center justify-between p-4 border rounded-lg">
                                <div>
                                    <div className="font-medium">{log.description}</div>
                                    <div className="text-sm text-muted-foreground">{new Date(log.created_at).toLocaleString()}</div>
                                </div>
                                <Badge variant="outline">{log.action}</Badge>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
