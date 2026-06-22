import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { CheckCircle2, XCircle, Clock } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ApprovalCenter({ pendingApprovals }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            <div>
                <h1 className="text-3xl font-bold tracking-tight">{__('general.approval_center')}</h1>
                <p className="text-muted-foreground mt-2">{__('general.manage_bookings_halted_by_advanced_rules_requiring_manual_intervention')}</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Clock className="h-5 w-5 text-orange-500" />{__('general.pending_approvals')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{__('general.date_time')}</TableHead>
                                <TableHead>{__('general.rule_triggered')}</TableHead>
                                <TableHead>{__('general.booking_ref')}</TableHead>
                                <TableHead>{__('general.reason')}</TableHead>
                                <TableHead className="text-end">{__('general.actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pendingApprovals.map((exec) => (
                                <TableRow key={exec.id}>
                                    <TableCell className="font-medium">{new Date(exec.created_at).toLocaleString()}</TableCell>
                                    <TableCell><Badge variant="outline">{exec.rule.name}</Badge></TableCell>
                                    <TableCell>#{exec.booking_id}</TableCell>
                                    <TableCell className="text-muted-foreground text-sm">
                                        {/* Grab reason from last log context */}
                                        {exec.logs[0]?.message || 'Manual review required.'}
                                    </TableCell>
                                    <TableCell className="text-end space-x-2">
                                        <Button variant="outline" size="sm" className="text-red-600 border-red-200 hover:bg-red-50">
                                            <XCircle className="h-4 w-4 me-1" /> {__('general.reject')}</Button>
                                        <Button size="sm" className="bg-emerald-600 hover:bg-emerald-700">
                                            <CheckCircle2 className="h-4 w-4 me-1" /> {__('general.approve')}</Button>
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
