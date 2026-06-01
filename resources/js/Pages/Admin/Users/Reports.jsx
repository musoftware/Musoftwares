import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { ArrowLeft } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { formatMoney } from '@/lib/utils';
import { format } from 'date-fns';

function secondsToTime(seconds) {
    if (!seconds) return '00:00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

export default function Reports({ client, dates = [], unpaid = 0 }) {
    const userBalance = Number(client.user_balance) || 0;
    const totalPaid = Number(client.total_paid) || 0;
    const hourRate = Number(client.hour_rate) || 1; // prevent div by zero
    const currency = client.currency || 'USD';

    return (
        <AdminSidebarLayout title="User Reports" header="User Reports">
            <Head title={`Reports - ${client.name}`} />

            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={`/admin/users/${client.id}`} className="text-gray-500 hover:text-gray-700">
                            <ArrowLeft className="h-6 w-6" />
                        </Link>
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">Timer Reports</h2>
                            <p className="text-sm text-gray-500">View time tracking reports for {client.name}</p>
                        </div>
                    </div>
                </div>

                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Financial Summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <p className="text-sm text-gray-500 mb-1 font-medium">Account Balance</p>
                                <p className="text-xl font-bold">{formatMoney(userBalance, currency)}</p>
                            </div>
                            {userBalance < unpaid && (
                                <div className="p-4 bg-red-50 rounded-lg border border-red-100">
                                    <p className="text-sm text-red-600 mb-1 font-medium">Due Balance</p>
                                    <p className="text-xl font-bold text-red-700">{formatMoney(unpaid - userBalance, currency)}</p>
                                </div>
                            )}
                            <div className="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <p className="text-sm text-gray-500 mb-1 font-medium">Total Paid</p>
                                <p className="text-xl font-bold">{formatMoney(totalPaid, currency)}</p>
                            </div>
                            <div className="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <p className="text-sm text-gray-500 mb-1 font-medium">Total Spend</p>
                                <p className="text-xl font-bold">{formatMoney(totalPaid - userBalance, currency)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Work Dates</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {dates.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-gray-50">
                                        <TableHead>Date</TableHead>
                                        <TableHead>Time</TableHead>
                                        <TableHead className="text-right">Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {dates.map((dateObj, i) => {
                                        // Calculate cost based on time vs hour_rate_client, but wait! The legacy blade did:
                                        // $seconds = $work_date['sum_amount'] / $userVar->hour_rate_client() * 60 * 60;
                                        // Wait, the new blade might just use sum_amount as cost, and sum_seconds for time if it exists.
                                        // Let's use sum_seconds if available, otherwise calculate it.
                                        const cost = Number(dateObj.sum_amount) || 0;
                                        const seconds = dateObj.sum_seconds ? Number(dateObj.sum_seconds) : ((cost / hourRate) * 3600);
                                        
                                        return (
                                            <TableRow key={i}>
                                                <TableCell className="font-medium">
                                                    {dateObj.ds ? format(new Date(dateObj.ds), 'MM/dd/yyyy') : '--'}
                                                </TableCell>
                                                <TableCell>{secondsToTime(seconds)}</TableCell>
                                                <TableCell className="text-right">{formatMoney(cost, currency)}</TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="p-8 text-center text-gray-500">
                                No timer reports found for this user.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
