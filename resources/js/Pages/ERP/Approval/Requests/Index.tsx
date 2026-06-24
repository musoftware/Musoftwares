import React from 'react';
import { ERPLayout } from '@/Layouts/ERPLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { ArrowRight } from 'lucide-react';

export default function ApprovalRequestIndex({ requests }: { requests: any[] }) {
    return (
        <ERPLayout>
            <Head title="Pending Approvals" />
            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Pending Approvals</h2>
                    <p className="text-muted-foreground">Review and act on approval requests.</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Approval Requests</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Workflow</TableHead>
                                    <TableHead>Requester</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date Requested</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {requests.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                                            No pending approvals.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    requests.map((req) => (
                                        <TableRow key={req.id}>
                                            <TableCell className="font-medium">{req.workflow_definition?.name}</TableCell>
                                            <TableCell>{req.requester?.user?.name}</TableCell>
                                            <TableCell>
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                    req.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    req.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {req.status}
                                                </span>
                                            </TableCell>
                                            <TableCell>{new Date(req.created_at).toLocaleDateString()}</TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={route('erp.approvals.requests.show', req.id)}>
                                                        Review <ArrowRight className="ml-2 h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
