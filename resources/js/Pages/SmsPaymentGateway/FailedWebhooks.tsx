import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { format, parseISO } from 'date-fns';
import { ArrowLeft, ChevronLeft, ChevronRight, Activity, MoreHorizontal, Eye } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface FailedWebhooksProps {
    failedWebhooks: {
        data: any[];
        links: PaginationLinks[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
}

export default function FailedWebhooks({ failedWebhooks }: FailedWebhooksProps) {
    const [selectedWebhook, setSelectedWebhook] = useState<any>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const formatDate = (dateString: string) => {
        if (!dateString) return 'N/A';
        try {
            return format(parseISO(dateString), 'PP p');
        } catch (e) {
            return dateString;
        }
    };

    const handleViewDetails = (webhook: any) => {
        setSelectedWebhook(webhook);
        setIsDialogOpen(true);
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Failed Webhooks</h2>}>
            <Head title="Failed Webhooks" />

            <div className="py-8 md:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <Activity className="w-6 h-6 text-rose-600" />
                                Failed Webhooks
                            </h1>
                            <p className="text-slate-500 mt-1">Review the details and payloads of failed webhook deliveries.</p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.webhooks'))}>
                                <ArrowLeft className="w-4 h-4 me-2" />
                                {__('general.back')}
                            </Button>
                        </div>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Delivery Failures Log</CardTitle>
                            <CardDescription>
                                {__('general.showing')} {failedWebhooks.from || 0} {__('general.to')} {failedWebhooks.to || 0} {__('general.of')} {failedWebhooks.total || 0} {__('general.results')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border overflow-hidden">
                                <Table>
                                    <TableHeader className="bg-slate-50">
                                        <TableRow>
                                            <TableHead>{__('general.date')}</TableHead>
                                            <TableHead>Target URL</TableHead>
                                            <TableHead>Error Message</TableHead>
                                            <TableHead className="w-[80px]"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(failedWebhooks.data as any).length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={4} className="text-center py-12 text-slate-500">
                                                    No failed webhooks found.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            (failedWebhooks.data as any).map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell className="whitespace-nowrap text-sm text-slate-600">
                                                        {formatDate(item.failed_at || item.created_at)}
                                                    </TableCell>
                                                    <TableCell className="text-sm font-mono text-slate-600 truncate max-w-[200px]">
                                                        {item.webhook?.webhook_url || '-'}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-rose-600 truncate max-w-[300px]" title={item.error_message}>
                                                        {item.error_message}
                                                    </TableCell>
                                                    <TableCell>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                                    <span className="sr-only">Open menu</span>
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end" className="w-[160px]">
                                                                <DropdownMenuItem onClick={() => handleViewDetails(item)}>
                                                                    <Eye className="me-2 h-4 w-4" />
                                                                    <span>View Details</span>
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {failedWebhooks.links && failedWebhooks.links.length > 3 && (
                                <div className="flex items-center justify-between mt-4">
                                    <div className="flex flex-wrap gap-1">
                                        {failedWebhooks.links.map((link, i) => {
                                            const isPrevious = link.label.includes('Previous');
                                            const isNext = link.label.includes('Next');
                                            return (
                                                <Button
                                                    key={i}
                                                    variant={link.active ? "default" : "outline"}
                                                    size="sm"
                                                    disabled={!link.url}
                                                    onClick={() => link.url && router.visit(link.url)}
                                                    className={`
                                                        ${link.active ? 'pointer-events-none' : ''}
                                                        ${(!isPrevious && !isNext) ? 'w-9' : ''}
                                                    `}
                                                >
                                                    {isPrevious ? <ChevronLeft className="w-4 h-4" /> : isNext ? <ChevronRight className="w-4 h-4" /> : link.label}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* View Details Dialog */}
                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogContent className="sm:max-w-[700px] max-h-[80vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Webhook Failure Details</DialogTitle>
                            </DialogHeader>
                            {selectedWebhook && (
                                <div className="space-y-6 mt-4">
                                    <div className="space-y-2">
                                        <h4 className="font-semibold text-sm text-slate-900">Failed At</h4>
                                        <div className="text-sm text-slate-600">
                                            {formatDate(selectedWebhook.failed_at || selectedWebhook.created_at)}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <h4 className="font-semibold text-sm text-slate-900">Target URL</h4>
                                        <div className="text-sm font-mono text-slate-600 bg-slate-50 p-2 rounded border break-all">
                                            {selectedWebhook.webhook?.webhook_url || '-'}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <h4 className="font-semibold text-sm text-rose-600">Error Message</h4>
                                        <div className="text-sm font-mono text-rose-600 bg-rose-50 p-3 rounded border border-rose-100 whitespace-pre-wrap">
                                            {selectedWebhook.error_message}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <h4 className="font-semibold text-sm text-slate-900">Payload Sent</h4>
                                        <div className="text-sm font-mono text-emerald-400 bg-slate-900 p-4 rounded overflow-x-auto">
                                            <pre>{JSON.stringify(selectedWebhook.payload, null, 2)}</pre>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
