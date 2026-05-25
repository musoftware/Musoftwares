import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { CheckCircle2, Clock, XCircle, Wallet } from 'lucide-react';

export default function AffiliatePayoutsIndex({ payouts }: any) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <Head title="My Payouts" />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">My Payouts</h1>
                    <p className="text-sm text-gray-500 mt-1">Track your requested withdrawals and current status.</p>
                </div>
                <Button className="bg-blue-600 hover:bg-blue-700">
                    <Wallet className="w-4 h-4 mr-2" /> Request Payout
                </Button>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardHeader className="bg-gray-50/50 border-b pb-4">
                    <CardTitle className="text-lg">Payout History</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">ID</TableHead>
                                <TableHead className="font-semibold text-gray-600">Method</TableHead>
                                <TableHead className="font-semibold text-gray-600">Account</TableHead>
                                <TableHead className="font-semibold text-gray-600">Amount (EGP)</TableHead>
                                <TableHead className="font-semibold text-gray-600">Status</TableHead>
                                <TableHead className="font-semibold text-gray-600">Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payouts.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-24 text-center text-gray-500">
                                        No payout requests found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                payouts.data.map((payout: any) => (
                                    <TableRow key={payout.id}>
                                        <TableCell className="font-mono text-sm">#{payout.id}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="bg-white">{payout.payment_method?.name}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm font-medium">{payout.account_name}</div>
                                            <div className="text-xs text-gray-500 font-mono">{payout.account_number}</div>
                                        </TableCell>
                                        <TableCell className="font-semibold">{payout.amount}</TableCell>
                                        <TableCell>
                                            {payout.status === 'pending' && <Badge className="bg-amber-100 text-amber-800 hover:bg-amber-100 border-none"><Clock className="w-3 h-3 mr-1" /> Pending</Badge>}
                                            {payout.status === 'approved' && <Badge className="bg-green-100 text-green-800 hover:bg-green-100 border-none"><CheckCircle2 className="w-3 h-3 mr-1" /> Approved</Badge>}
                                            {payout.status === 'declined' && <Badge className="bg-red-100 text-red-800 hover:bg-red-100 border-none"><XCircle className="w-3 h-3 mr-1" /> Declined</Badge>}
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-500">
                                            {new Date(payout.created_at).toLocaleDateString()}
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
