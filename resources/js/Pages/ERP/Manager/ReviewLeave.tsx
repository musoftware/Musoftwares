import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';
import { CheckCircle, XCircle, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export default function ReviewLeave({ leaveRequest }: any) {
    const { data, setData, post, processing, errors } = useForm({
        response: '',
    });

    const handleApprove = () => {
        post(route('erp.manager.approvals.leave.approve', leaveRequest.id), {
            onSuccess: () => toast.success(__('erp.leave_approved')),
        });
    };

    const handleReject = () => {
        if (!data.response) {
            toast.error(__('erp.response_required_for_rejection'));
            return;
        }
        post(route('erp.manager.approvals.leave.reject', leaveRequest.id), {
            onSuccess: () => toast.success(__('erp.leave_rejected')),
        });
    };

    return (
        <ERPLayout>
            <Head title={__('erp.review_leave_request')} />
            
            <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-4">
                        <Link href={route('erp.manager.approvals.index')}>
                            <Button variant="outline" size="icon" className="h-9 w-9">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight text-foreground">{__('erp.review_leave_request')}</h1>
                            <p className="text-muted-foreground mt-1">{__('erp.review_leave_request_description')}</p>
                        </div>
                    </div>
                </div>

                <Card className="shadow-sm border-border/40">
                    <CardHeader className="bg-muted/30 border-b">
                        <div className="flex justify-between items-start">
                            <div>
                                <CardTitle className="text-xl">{leaveRequest.member?.user?.name || __('general.unknown')}</CardTitle>
                                <CardDescription className="mt-1">{__('erp.team_member_leave_application')}</CardDescription>
                            </div>
                            <Badge variant="outline" className="capitalize text-sm px-3 py-1 bg-background">
                                {leaveRequest.type}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="p-6 grid gap-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">{__('general.start_date')}</p>
                                <p className="font-semibold">{format(new Date(leaveRequest.start_date), 'MMMM d, yyyy')}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">{__('general.end_date')}</p>
                                <p className="font-semibold">{format(new Date(leaveRequest.end_date), 'MMMM d, yyyy')}</p>
                            </div>
                        </div>

                        <div className="space-y-2 border rounded-md p-4 bg-muted/20">
                            <p className="text-sm font-medium text-muted-foreground">{__('general.reason')}</p>
                            <p className="whitespace-pre-wrap">{leaveRequest.reason || __('erp.no_reason_provided')}</p>
                        </div>

                        <div className="space-y-3 mt-4 pt-4 border-t">
                            <Label htmlFor="response" className="text-base">{__('erp.manager_response')} ({__('general.optional_for_approval')})</Label>
                            <Textarea 
                                id="response"
                                placeholder={__('erp.manager_response_placeholder')}
                                value={data.response}
                                onChange={e => setData('response', e.target.value)}
                                className="min-h-[100px] resize-none"
                            />
                            {errors.response && <p className="text-sm text-destructive">{errors.response}</p>}
                        </div>
                    </CardContent>
                    <CardFooter className="p-6 pt-0 flex justify-end gap-3 border-t bg-muted/10 mt-6">
                        <Button 
                            variant="outline" 
                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                            onClick={handleReject}
                            disabled={processing}
                        >
                            <XCircle className="w-4 h-4 mr-2" />
                            {__('general.reject')}
                        </Button>
                        <Button 
                            variant="default"
                            className="bg-green-600 hover:bg-green-700 text-white"
                            onClick={handleApprove}
                            disabled={processing}
                        >
                            <CheckCircle className="w-4 h-4 mr-2" />
                            {__('general.approve')}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </ERPLayout>
    );
}
