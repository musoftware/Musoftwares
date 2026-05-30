import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Phone, Clock, CheckCircle } from 'lucide-react';
import { __ } from '@/utils/translations';

export default function TelesalesDashboard({ pipeline, kpis }: { pipeline: any, kpis: any }) {
    return (
        <AppLayout title={__('Telesales Workspace')}>
            <div className="flex-1 space-y-4 p-8 pt-6">
                <div className="flex items-center justify-between space-y-2">
                    <h2 className="text-3xl font-bold tracking-tight">{__('Telesales Pipeline')}</h2>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Calls Made Today')}</CardTitle>
                            <Phone className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{kpis?.calls_today ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Pending Follow-ups')}</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{kpis?.pending_followups ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Conversion Rate')}</CardTitle>
                            <CheckCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{kpis?.conversion_rate ?? '0%'}</div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 grid-cols-4 mt-6">
                    {['NEW', 'FOLLOW_UP', 'INTERESTED', 'NEGOTIATION'].map(stage => (
                        <div key={stage} className="bg-gray-50/50 p-4 rounded-lg border h-[600px] overflow-y-auto">
                            <h3 className="font-semibold mb-4 pb-2 border-b">{__(stage)}</h3>
                            <div className="space-y-3">
                                {pipeline[stage] ? pipeline[stage].map((lead: any) => (
                                    <div key={lead.id} className="bg-white p-3 rounded shadow-sm border text-sm">
                                        <p className="font-bold">{lead.name}</p>
                                        <p className="text-muted-foreground">{lead.phone}</p>
                                        {lead.last_contacted_at && (
                                            <p className="text-xs text-gray-400 mt-2">
                                                {__('Last Contact:')} {new Date(lead.last_contacted_at).toLocaleDateString()}
                                            </p>
                                        )}
                                    </div>
                                )) : (
                                    <p className="text-xs text-muted-foreground italic">{__('No leads in this stage.')}</p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
