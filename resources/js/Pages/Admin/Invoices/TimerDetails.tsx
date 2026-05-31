import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Clock, DollarSign, Calendar } from 'lucide-react';

interface Timer {
    id: number;
    start_date: string | null;
    end_date: string | null;
    amount: number;
    duration_seconds: number;
}

interface Props {
    item: {
        id: number;
        item_title: string;
        invoice_id: number;
        invoice_number: string | null;
        client_name: string | null;
    };
    timers: Timer[];
    total_seconds: number;
    total_billable: number;
}

function formatDuration(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleString();
}

export default function TimerDetails({ item, timers, total_seconds, total_billable }: Props) {
    return (
        <AdminSidebarLayout>
            <Head title={`Timer Details — ${item.item_title}`} />

            {/* Header */}
            <div className="flex items-center gap-4 mb-6">
                <Link href={route('admin.invoices.show', item.invoice_id)}>
                    <Button variant="outline" size="sm">
                        <ArrowLeft className="w-4 h-4 mr-2" /> Back to Invoice
                    </Button>
                </Link>
                <div>
                    <h1 className="text-2xl font-black text-gray-900">{item.item_title}</h1>
                    <p className="text-sm text-gray-500">
                        Invoice #{item.invoice_number} • {item.client_name}
                    </p>
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <Clock className="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tracked Time</p>
                            <p className="text-2xl font-black text-gray-900 tabular-nums">{formatDuration(total_seconds)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <DollarSign className="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Billable</p>
                            <p className="text-2xl font-black text-gray-900">{total_billable.toFixed(2)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <Calendar className="w-6 h-6 text-purple-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Sessions</p>
                            <p className="text-2xl font-black text-gray-900">{timers.length}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Sessions Table */}
            <Card className="shadow-sm overflow-hidden">
                <CardHeader className="bg-gray-50 border-b py-3">
                    <CardTitle className="text-base font-bold text-gray-900">Timer Sessions</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Start</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">End</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Earnings</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {timers.map((timer, index) => (
                                    <tr key={timer.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-4 py-3 text-gray-500 font-mono text-xs">{index + 1}</td>
                                        <td className="px-4 py-3 text-gray-900 font-medium">{formatDate(timer.start_date)}</td>
                                        <td className="px-4 py-3 text-gray-900 font-medium">{formatDate(timer.end_date)}</td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 tabular-nums">
                                                {formatDuration(timer.duration_seconds)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right font-bold text-gray-900 tabular-nums">{timer.amount.toFixed(2)}</td>
                                    </tr>
                                ))}
                                {timers.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                                            No timer sessions recorded.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                            {timers.length > 0 && (
                                <tfoot className="bg-gray-50 border-t">
                                    <tr>
                                        <td colSpan={3} className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Totals</td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center rounded bg-gray-200 px-2 py-1 text-xs font-bold text-gray-800 tabular-nums">
                                                {formatDuration(total_seconds)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right font-black text-gray-900 tabular-nums">{total_billable.toFixed(2)}</td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminSidebarLayout>
    );
}
