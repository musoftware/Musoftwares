import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { CheckCircle2, XCircle, Clock } from 'lucide-react';

export default function PayoutsIndex({ payouts, filters }: any) {
    const { post, processing } = useForm({
        status: ''
    });

    const handleProcess = (id: number, status: 'approved' | 'declined') => {
        if (confirm(`Are you sure you want to ${status} this payout request?`)) {
            post(route('affiliate_pos.admin.payouts.process', { paymentRequest: id }), {
                data: { status },
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <Head title={__('general.payout_requests')} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">{__('general.payout_requests')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_withdrawal_requests_from_affiliates_and_vendors')}</p>
                </div>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardHeader className="bg-gray-50/50 border-b pb-4">
                    <CardTitle className="text-lg">{__('general.recent_requests')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80 hover:bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">User</TableHead>
                                <TableHead className="font-semibold text-gray-600">Method</TableHead>
                                <TableHead className="font-semibold text-gray-600">{__('general.account_info')}</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Amount</TableHead>
                                <TableHead className="font-semibold text-gray-600">Status</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payouts.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-24 text-center text-gray-500">{__('general.no_payout_requests_found')}</TableCell>
                                </TableRow>
                            ) : (
                                payouts.data.map((payout: any) => (
                                    <TableRow key={payout.id} className="group">
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{payout.user.name}</div>
                                            <div className="text-sm text-gray-500">{payout.user.email}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="font-medium bg-white">
                                                {payout.payment_method.name}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-gray-900">{payout.account_name}</div>
                                            <div className="text-sm font-mono text-gray-500">{payout.account_number}</div>
                                        </TableCell>
                                        <TableCell className="text-right font-semibold text-gray-900">
                                            EGP {payout.amount.toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            {payout.status === 'pending' && (
                                                <Badge className="bg-amber-100 text-amber-800 hover:bg-amber-100 border-none flex w-fit items-center gap-1">
                                                    <Clock className="w-3 h-3" /> Pending
                                                </Badge>
                                            )}
                                            {payout.status === 'approved' && (
                                                <Badge className="bg-green-100 text-green-800 hover:bg-green-100 border-none flex w-fit items-center gap-1">
                                                    <CheckCircle2 className="w-3 h-3" /> Approved
                                                </Badge>
                                            )}
                                            {payout.status === 'declined' && (
                                                <Badge className="bg-red-100 text-red-800 hover:bg-red-100 border-none flex w-fit items-center gap-1">
                                                    <XCircle className="w-3 h-3" /> Declined
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {payout.status === 'pending' ? (
                                                <div className="flex justify-end gap-2">
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline" 
                                                        className="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                                                        onClick={() => handleProcess(payout.id, 'declined')}
                                                        disabled={processing}
                                                    >
                                                        Decline
                                                    </Button>
                                                    <Button 
                                                        size="sm" 
                                                        className="bg-green-600 hover:bg-green-700 text-white shadow-sm"
                                                        onClick={() => handleProcess(payout.id, 'approved')}
                                                        disabled={processing}
                                                    >
                                                        Approve
                                                    </Button>
                                                </div>
                                            ) : (
                                                <span className="text-sm text-gray-400">Processed</span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
