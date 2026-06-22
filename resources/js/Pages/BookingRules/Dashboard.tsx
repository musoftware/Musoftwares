import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Plus, Settings, PlayCircle, AlertCircle } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function RulesDashboard({ rules, stats }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.booking_advanced_rules')}</h1>
                    <p className="text-muted-foreground mt-2">{__('general.automate_scheduling_policies_approvals_and_workflows')}</p>
                </div>
                <Link href="/booking-rules/builder">
                    <Button>
                        <Plus className="me-2 h-4 w-4" />{__('general.create_new_rule')}</Button>
                </Link>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">{__('general.active_rules')}</CardTitle>
                        <Settings className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats.active_count}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Executions (30d)</CardTitle>
                        <PlayCircle className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats.execution_count}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">{__('general.failed_conflicts')}</CardTitle>
                        <AlertCircle className="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-red-500">{stats.failed_count}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{__('general.configured_rules')}</CardTitle>
                    <CardDescription>{__('general.manage_priority_and_active_status_of_your_booking_rules')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{__('general.priority')}</TableHead>
                                <TableHead>{__('general.name')}</TableHead>
                                <TableHead>{__('general.trigger')}</TableHead>
                                <TableHead>{__('general.status')}</TableHead>
                                <TableHead className="text-end">{__('general.actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rules.map((rule) => (
                                <TableRow key={rule.id}>
                                    <TableCell>{rule.priority}</TableCell>
                                    <TableCell className="font-medium">{rule.name}</TableCell>
                                    <TableCell><Badge variant="outline">{rule.event_trigger}</Badge></TableCell>
                                    <TableCell>
                                        <Badge variant={rule.is_active ? 'default' : 'secondary'}>
                                            {rule.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <Link href={`/booking-rules/${rule.id}/edit`}>
                                            <Button variant="ghost" size="sm">{__('general.edit')}</Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
