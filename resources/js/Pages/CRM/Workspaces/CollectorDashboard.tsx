import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Upload, Plus, Users } from 'lucide-react';
import { __ } from '@/utils/translations';

export default function CollectorDashboard({ stats, recentImports }: { stats: any, recentImports: any[] }) {
    return (
        <AppLayout title={__('Collector Workspace')}>
            <div className="flex-1 space-y-4 p-8 pt-6">
                <div className="flex items-center justify-between space-y-2">
                    <h2 className="text-3xl font-bold tracking-tight">{__('Lead Acquisition Workspace')}</h2>
                    <div className="flex items-center space-x-2">
                        <Button variant="outline"><Upload className="w-4 h-4 mr-2" /> {__('Bulk CSV Import')}</Button>
                        <Button><Plus className="w-4 h-4 mr-2" /> {__('Quick Add Lead')}</Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Total Leads Added')}</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_added ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{__('Duplicates Prevented')}</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.duplicates_prevented ?? 0}</div>
                        </CardContent>
                    </Card>
                </div>
                
                {/* Space for the quick entry table */}
                <Card>
                    <CardHeader>
                        <CardTitle>{__('Recent Imports')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentImports && recentImports.length > 0 ? (
                            <div className="space-y-4">
                                {recentImports.map((importJob, index) => (
                                    <div key={index} className="flex items-center justify-between border-b pb-2 last:border-0 last:pb-0">
                                        <div>
                                            <p className="font-medium text-sm">{importJob.name}</p>
                                            <p className="text-xs text-muted-foreground">{importJob.phone}</p>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {new Date(importJob.created_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-sm text-muted-foreground">
                                {__('No recent imports available.')}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
