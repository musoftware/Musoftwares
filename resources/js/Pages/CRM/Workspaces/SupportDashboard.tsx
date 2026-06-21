import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { MessageSquare, Clock, CheckCircle2, AlertCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Link, router } from '@inertiajs/react';

export default function SupportDashboard({ stats, priorityMessages }: { stats: any, priorityMessages?: any[] }) {
    return (
        <CrmLayout title={__('general.support_workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('general.customer_support')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('general.monitor_tickets_and_response_slas')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KPICard 
                        title={__('general.open_tickets')} 
                        value={stats?.open_tickets ?? 0} 
                        icon={AlertCircle} 
                        colorClass="bg-red-100 text-red-600" 
                    />
                    <KPICard 
                        title={__('general.unread_messages')} 
                        value={stats?.unread_messages ?? 0} 
                        icon={MessageSquare} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('general.avg_response_time')} 
                        value={stats?.avg_response_time ?? '0m'} 
                        icon={Clock} 
                        colorClass="bg-amber-100 text-amber-600" 
                    />
                    <KPICard 
                        title={__('general.resolved_today')} 
                        value={stats?.resolved_today ?? 0} 
                        icon={CheckCircle2} 
                        colorClass="bg-emerald-100 text-emerald-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0 flex flex-row justify-between items-center">
                            <CardTitle className="font-semibold">{__('general.priority_inbox')}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            {priorityMessages && priorityMessages.length > 0 ? (
                                <div className="space-y-3">
                                    {priorityMessages.map((msg: any, index: number) => (
                                        <div key={index} className="flex items-start gap-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                            <div className="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 mt-0.5">
                                                <AlertCircle size={14} />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex justify-between items-center mb-1">
                                                    <p className="font-semibold text-sm text-foreground truncate">{msg.customer}</p>
                                                    <span className="text-xs text-muted-foreground whitespace-nowrap ms-2">{msg.timeAgo}</span>
                                                </div>
                                                <p className="text-sm text-slate-600 truncate">{msg.preview}</p>
                                            </div>
                                            <Button variant="ghost" size="sm" className="shrink-0 text-indigo-600">
                                                    {__('general.reply')}
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg">
                                    {__('general.no_priority_messages_at_the_moment')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
