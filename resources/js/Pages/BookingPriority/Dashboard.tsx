import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Crown, AlertTriangle, TrendingUp } from 'lucide-react';

export default function PriorityDashboard({ stats, recentEscalations }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            <div>
                <h1 className="text-3xl font-bold tracking-tight">Priority & VIP Support</h1>
                <p className="text-muted-foreground mt-2">Manage premium availability and fast-track queues.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium">Total VIP Customers</CardTitle>
                        <Crown className="h-4 w-4 text-amber-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats.vipCount}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium">Emergency Escalations (30d)</CardTitle>
                        <AlertTriangle className="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats.emergencyCount}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium">Priority Utilization</CardTitle>
                        <TrendingUp className="h-4 w-4 text-emerald-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats.utilizationPercentage}%</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Recent Escalations</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Booking Ref</TableHead>
                                <TableHead>Level</TableHead>
                                <TableHead>Reason</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {recentEscalations.map((esc) => (
                                <TableRow key={esc.id}>
                                    <TableCell className="font-medium">{new Date(esc.created_at).toLocaleString()}</TableCell>
                                    <TableCell>#{esc.model_id}</TableCell>
                                    <TableCell><Badge variant="destructive">{esc.level.name}</Badge></TableCell>
                                    <TableCell className="text-muted-foreground">{esc.reason}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
