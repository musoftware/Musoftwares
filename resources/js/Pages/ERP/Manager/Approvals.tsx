import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { CheckCircle, XCircle, ArrowRight } from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';
import { Link } from '@inertiajs/react';

export default function Approvals({ leaveRequests, withdrawals }: any) {
    const leaveForm = useForm({ response: '' });

    return (
        <ERPLayout>
            <Head title={__('erp.manager_approvals')} />
            
            <div className="flex flex-col gap-8 p-8 max-w-7xl mx-auto">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">{__('erp.pending_approvals')}</h1>
                        <p className="text-muted-foreground mt-1">{__('erp.review_and_approve_team_requests')}</p>
                    </div>
                </div>

                <div className="grid gap-6">
                    {/* Leave Requests */}
                    <Card className="shadow-sm border-border/40">
                        <CardHeader>
                            <CardTitle>{__('erp.leave_requests')}</CardTitle>
                            <CardDescription>{__('erp.review_time_off_requests')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {leaveRequests?.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{__('general.member')}</TableHead>
                                            <TableHead>{__('general.type')}</TableHead>
                                            <TableHead>{__('general.duration')}</TableHead>
                                            <TableHead>{__('general.reason')}</TableHead>
                                            <TableHead className="text-right">{__('general.action')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {leaveRequests.map((request: any) => (
                                            <TableRow key={request.id}>
                                                <TableCell className="font-medium">{request.member?.user?.name || __('general.unknown')}</TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="capitalize">{request.type}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {format(new Date(request.start_date), 'MMM d, yyyy')} - {format(new Date(request.end_date), 'MMM d, yyyy')}
                                                </TableCell>
                                                <TableCell className="max-w-xs truncate" title={request.reason}>
                                                    {request.reason || __('erp.no_reason_provided')}
                                                </TableCell>
                                                <TableCell className="text-right space-x-2">
                                                    <Link href={route('erp.manager.approvals.leave.show', request.id)}>
                                                        <Button 
                                                            variant="default" 
                                                            size="sm" 
                                                            className="shadow-sm"
                                                        >
                                                            {__('general.review')}
                                                            <ArrowRight className="w-4 h-4 ml-1" />
                                                        </Button>
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground border rounded-md border-dashed">
                                    {__('erp.no_pending_leave_requests')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Expense / Withdrawal Requests (Placeholder) */}
                    <Card className="shadow-sm border-border/40">
                        <CardHeader>
                            <CardTitle>{__('erp.expense_and_withdrawal_requests')}</CardTitle>
                            <CardDescription>{__('erp.review_financial_requests')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {withdrawals?.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{__('erp.client_member')}</TableHead>
                                            <TableHead>{__('general.amount')}</TableHead>
                                            <TableHead>{__('general.date')}</TableHead>
                                            <TableHead className="text-right">{__('general.action')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {withdrawals.map((withdrawal: any) => (
                                            <TableRow key={withdrawal.id}>
                                                <TableCell className="font-medium">{withdrawal.client?.name || __('general.unknown')}</TableCell>
                                                <TableCell>{withdrawal.amount} {withdrawal.amount_currency}</TableCell>
                                                <TableCell>{format(new Date(withdrawal.created_at), 'MMM d, yyyy')}</TableCell>
                                                <TableCell className="text-right">
                                                    <span className="text-xs text-muted-foreground italic">{__('erp.handled_by_finance')}</span>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground border rounded-md border-dashed">
                                    {__('erp.no_pending_financial_requests')}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </ERPLayout>
    );
}
