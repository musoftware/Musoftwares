import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle, XCircle } from 'lucide-react';

export default function ApprovalRequestShow({ approvalRequest }: { approvalRequest: any }) {
    return (
        <ERPLayout title="Request Details">
            <Head title={`Approval Request: ${approvalRequest.id}`} />
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Review Request</h2>
                        <p className="text-muted-foreground">Approve or reject this request.</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{approvalRequest.workflow_definition?.name}</CardTitle>
                        <CardDescription>Requested by {approvalRequest.requester?.user?.name}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h3 className="font-semibold mb-2">Request Details</h3>
                            <pre className="bg-muted p-4 rounded-md text-sm overflow-auto">
                                {JSON.stringify(approvalRequest.request_data, null, 2)}
                            </pre>
                        </div>
                        <div className="flex space-x-4 pt-4">
                            <Button className="bg-green-600 hover:bg-green-700 text-white">
                                <CheckCircle className="mr-2 h-4 w-4" /> Approve
                            </Button>
                            <Button variant="destructive">
                                <XCircle className="mr-2 h-4 w-4" /> Reject
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
