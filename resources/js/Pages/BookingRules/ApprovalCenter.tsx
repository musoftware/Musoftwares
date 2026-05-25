import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { CheckCircle2, XCircle, Clock } from 'lucide-react';

export default function ApprovalCenter({ pendingApprovals }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            <div>
                <h1 className="text-3xl font-bold tracking-tight">Approval Center</h1>
                <p className="text-muted-foreground mt-2">Manage bookings halted by advanced rules requiring manual intervention.</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Clock className="h-5 w-5 text-orange-500" /> Pending Approvals
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date / Time</TableHead>
                                <TableHead>Rule Triggered</TableHead>
                                <TableHead>Booking Ref</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
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
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="outline" size="sm" className="text-red-600 border-red-200 hover:bg-red-50">
                                            <XCircle className="h-4 w-4 mr-1" /> Reject
                                        </Button>
                                        <Button size="sm" className="bg-emerald-600 hover:bg-emerald-700">
                                            <CheckCircle2 className="h-4 w-4 mr-1" /> Approve
                                        </Button>
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
