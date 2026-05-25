import React, { useState } from 'react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Plus, Settings, PlayCircle, AlertCircle } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function RulesDashboard({ rules, stats }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-8">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Booking Advanced Rules</h1>
                    <p className="text-muted-foreground mt-2">Automate scheduling policies, approvals, and workflows.</p>
                </div>
                <Link href="/booking-rules/builder">
                    <Button>
                        <Plus className="mr-2 h-4 w-4" /> Create New Rule
                    </Button>
                </Link>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Active Rules</CardTitle>
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
                        <CardTitle className="text-sm font-medium">Failed/Conflicts</CardTitle>
                        <AlertCircle className="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-red-500">{stats.failed_count}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Configured Rules</CardTitle>
                    <CardDescription>Manage priority and active status of your booking rules.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Priority</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Trigger</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
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
                                    <TableCell className="text-right">
                                        <Link href={`/booking-rules/${rule.id}/edit`}>
                                            <Button variant="ghost" size="sm">Edit</Button>
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
