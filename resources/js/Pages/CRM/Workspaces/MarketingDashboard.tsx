import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { Target, Megaphone, DollarSign, TrendingUp, Filter } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

export default function MarketingDashboard({ stats, topCampaigns }: { stats: any, topCampaigns?: any[] }) {
    return (
        <CrmLayout title={__('general.marketing_workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('general.marketing_growth')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('general.campaign_performance_and_lead_acquisition')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" className="flex items-center gap-2">
                            <Filter size={16} className="text-muted-foreground" />
                            {__('general.filter_by_date')}
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
                        title={__('general.active_campaigns')} 
                        value={stats?.active_campaigns ?? 0} 
                        icon={Megaphone} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('general.leads_today')} 
                        value={stats?.leads_today ?? 0} 
                        icon={Target} 
                        colorClass="bg-emerald-100 text-emerald-600" 
                    />
                    <KPICard 
                        title={__('general.avg_cost_per_lead')} 
                        value={stats?.cost_per_lead ?? '$0.00'} 
                        icon={DollarSign} 
                        colorClass="bg-amber-100 text-amber-600" 
                    />
                    <KPICard 
                        title={__('general.campaign_roi')} 
                        value={stats?.roi ?? '0%'} 
                        icon={TrendingUp} 
                        colorClass="bg-purple-100 text-purple-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0">
                            <CardTitle className="font-semibold">{__('general.top_performing_campaigns')}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            {topCampaigns && topCampaigns.length > 0 ? (
                                <div className="space-y-3">
                                    {topCampaigns.map((campaign: any, index: number) => (
                                        <div key={index} className="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                                    <Megaphone size={14} />
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-sm text-foreground">{campaign.name}</p>
                                                    <p className="text-xs text-muted-foreground">{__('general.status')} <span className="font-medium text-emerald-600">{campaign.status}</span></p>
                                                </div>
                                            </div>
                                            <div className="flex flex-col items-end gap-1 w-32">
                                                <span className="text-xs font-medium text-muted-foreground">{campaign.progress}% {__('general.sent')}</span>
                                                <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                    <div className="bg-indigo-600 h-full rounded-full" style={{ width: `${campaign.progress}%` }} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg">
                                    {__('general.no_active_campaigns_data_available')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
