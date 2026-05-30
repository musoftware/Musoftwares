import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Target, Megaphone, DollarSign, TrendingUp, Filter } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

export default function MarketingDashboard({ stats }: { stats: any }) {
    return (
        <CrmLayout title={__('Marketing Workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('Marketing & Growth')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('Campaign performance and lead acquisition.')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" className="flex items-center gap-2">
                            <Filter size={16} className="text-muted-foreground" />
                            {__('Filter by Date')}
                        </Button>
                        <Button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                            <Megaphone size={16} />
                            {__('New Campaign')}
                        </Button>
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KPICard 
                        title={__('Active Campaigns')} 
                        value={stats?.active_campaigns ?? 0} 
                        icon={Megaphone} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('Leads Today')} 
                        value={stats?.leads_today ?? 0} 
                        icon={Target} 
                        colorClass="bg-emerald-100 text-emerald-600" 
                    />
                    <KPICard 
                        title={__('Avg Cost Per Lead')} 
                        value={stats?.cost_per_lead ?? '$0.00'} 
                        icon={DollarSign} 
                        colorClass="bg-amber-100 text-amber-600" 
                    />
                    <KPICard 
                        title={__('Campaign ROI')} 
                        value={stats?.roi ?? '0%'} 
                        icon={TrendingUp} 
                        colorClass="bg-purple-100 text-purple-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold">{__('Top Performing Campaigns')}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            <div className="flex items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg">
                                {__('No active campaigns data available.')}
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
