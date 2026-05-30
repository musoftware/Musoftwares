import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import KPICard from '../Components/Widgets/KPICard';
import { MessageSquare, Clock, CheckCircle2, AlertCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';

export default function SupportDashboard({ stats }: { stats: any }) {
    return (
        <CrmLayout title={__('Support Workspace')} activeMenu="workspaces">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Dashboard Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('Customer Support')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('Monitor tickets, WhatsApp messages, and response SLAs.')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white" asChild>
                            <Link href="/crm/whatsapp">
                                <MessageSquare size={16} />
                                {__('Open WhatsApp Inbox')}
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* KPI Bar */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KPICard 
                        title={__('Open Tickets')} 
                        value={stats?.open_tickets ?? 0} 
                        icon={AlertCircle} 
                        colorClass="bg-red-100 text-red-600" 
                    />
                    <KPICard 
                        title={__('Unread Messages')} 
                        value={stats?.unread_messages ?? 0} 
                        icon={MessageSquare} 
                        colorClass="bg-blue-100 text-blue-600" 
                    />
                    <KPICard 
                        title={__('Avg Response Time')} 
                        value={stats?.avg_response_time ?? '0m'} 
                        icon={Clock} 
                        colorClass="bg-amber-100 text-amber-600" 
                    />
                    <KPICard 
                        title={__('Resolved Today')} 
                        value={stats?.resolved_today ?? 0} 
                        icon={CheckCircle2} 
                        colorClass="bg-emerald-100 text-emerald-600" 
                    />
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex gap-6 min-h-0">
                    <Card className="flex-1 flex flex-col overflow-hidden shadow-sm">
                        <CardHeader className="p-4 border-b bg-muted/50 space-y-0 flex flex-row justify-between items-center">
                            <CardTitle className="font-semibold">{__('Priority Inbox')}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 p-4 overflow-y-auto">
                            <div className="flex items-center justify-center h-40 text-sm text-muted-foreground border border-dashed rounded-lg">
                                {__('No priority messages at the moment.')}
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </CrmLayout>
    );
}
