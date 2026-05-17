import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Send, CheckCircle, Copy, Download, MoreHorizontal, Clock, Wallet, Info, History, ArrowUpRight, ArrowDownLeft } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

const Separator = () => <div className="h-px bg-gray-200 w-full my-4" />;

export default function Show({ invoice, timeline, referral_earnings }) {
    const { auth } = usePage().props;
    const isClientView = !auth.user.roles?.includes('admin') && auth.user.id !== invoice.created_by;

    const [timerValue, setTimerValue] = useState(0);
    const activeTimerItem = invoice.items.find((i) => i.type === 'timer' && i.timer_sessions?.some((s) => !s.stopped_at));

    useEffect(() => {
        let interval;
        if (activeTimerItem) {
            const activeSession = activeTimerItem.timer_sessions.find((s) => !s.stopped_at);
            const startTime = new Date(activeSession.started_at).getTime();

            interval = setInterval(() => {
                const now = new Date().getTime();
                setTimerValue(Math.floor((now - startTime) / 1000));
            }, 1000);
        }
        return () => clearInterval(interval);
    }, [activeTimerItem]);

    const formatDuration = (seconds) => {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    };

    const handleWalletAction = (type) => {
        const amount = prompt(`Enter amount to ${type}:`);
        if (amount && !isNaN(amount)) {
            router.post(route(`erp.wallet.${type}`, invoice.client_id), { amount });
        }
    };

    if (isClientView) {
        return (
            <div className="min-h-screen bg-gray-50 p-4 md:p-8">
                <Head title={`Invoice ${invoice.invoice_number}`} />
                <div className="max-w-4xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden border">
                    {invoice.status === 'paid' ? (
                        <div className="bg-green-600 text-white p-4 text-center font-bold flex items-center justify-center gap-2">
                            <CheckCircle className="h-5 w-5" /> PAID ON {new Date(invoice.paid_at).toLocaleDateString()}
                        </div>
                    ) : (
                        <div className="bg-yellow-500 text-white p-4 text-center font-bold">
                            PAYMENT PENDING - DUE BY {new Date(invoice.due_date).toLocaleDateString()}
                        </div>
                    )}

                    <div className="p-8 md:p-12">
                        <div className="flex flex-col md:flex-row justify-between mb-12 gap-8">
                            <div>
                                <div className="h-16 w-16 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-2xl mb-4">LOGO</div>
                                <h2 className="text-xl font-bold">Business Name</h2>
                                <p className="text-gray-500">123 Business St, City, Country</p>
                            </div>
                            <div className="text-md-right">
                                <h1 className="text-3xl font-bold text-gray-900 mb-2">INVOICE</h1>
                                <p className="font-mono text-gray-600">{invoice.invoice_number}</p>
                                <p className="text-gray-500 mt-4">Issued: {new Date(invoice.issued_at).toLocaleDateString()}</p>
                                <p className="text-gray-500">Due: {new Date(invoice.due_date).toLocaleDateString()}</p>
                            </div>
                        </div>

                        <Separator />

                        <div className="mb-12">
                            <h3 className="text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest">Bill To</h3>
                            <p className="text-xl font-bold">{invoice.client?.name}</p>
                            <p className="text-gray-600">{invoice.client?.email}</p>
                        </div>

                        {activeTimerItem && (
                            <div className="mb-8 p-6 bg-red-50 border-2 border-red-100 rounded-xl">
                                <div className="flex flex-col md:flex-row items-center justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <div className="h-4 w-4 bg-red-600 rounded-full animate-ping"></div>
                                        <div>
                                            <p className="font-bold text-red-900 uppercase text-xs tracking-wider">Live Session Running</p>
                                            <p className="text-lg font-bold text-gray-900">{activeTimerItem.title}</p>
                                        </div>
                                    </div>
                                    <div className="text-4xl font-mono font-black text-red-600">
                                        {formatDuration(timerValue)}
                                    </div>
                                </div>
                                <p className="text-sm text-gray-600 mt-4 text-center md:text-left">
                                    This session + previous sessions: <span className="font-bold">{(timerValue/60 + parseFloat(activeTimerItem.quantity)).toFixed(0)} mins</span> total.
                                </p>
                            </div>
                        )}

                        <Table>
                            <TableHeader>
                                <TableRow className="bg-gray-50">
                                    <TableHead className="font-bold text-gray-900">Description</TableHead>
                                    <TableHead className="text-center font-bold text-gray-900">Qty</TableHead>
                                    <TableHead className="text-right font-bold text-gray-900">Rate</TableHead>
                                    <TableHead className="text-right font-bold text-gray-900">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoice.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="py-6">
                                            <div className="font-bold text-gray-900">{item.title}</div>
                                            {item.description && <div className="text-sm text-gray-500 mt-1">{item.description}</div>}
                                            {item.type === 'timer' && <Badge variant="outline" className="mt-2 text-purple-600 border-purple-200 bg-purple-50">Timer Item</Badge>}
                                        </TableCell>
                                        <TableCell className="text-center">{item.quantity}</TableCell>
                                        <TableCell className="text-right">
                                            <CurrencyDisplay amount={item.unit_price} currency={invoice.amount_currency} />
                                        </TableCell>
                                        <TableCell className="text-right font-bold text-gray-900">
                                            <CurrencyDisplay amount={item.total} currency={invoice.amount_currency} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <div className="mt-12 flex justify-end">
                            <div className="w-full md:w-1/3 space-y-4">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span><CurrencyDisplay amount={invoice.amount - invoice.tax_amount + invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                </div>
                                {invoice.discount_amount > 0 && (
                                    <div className="flex justify-between text-red-600">
                                        <span>Discount</span>
                                        <span>-<CurrencyDisplay amount={invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                    </div>
                                )}
                                {invoice.tax_amount > 0 && (
                                    <div className="flex justify-between text-gray-600">
                                        <span>Tax ({invoice.tax_rate}%)</span>
                                        <span><CurrencyDisplay amount={invoice.tax_amount} currency={invoice.amount_currency} /></span>
                                    </div>
                                )}
                                <div className="pt-4 border-t-2 border-gray-900 flex justify-between items-center">
                                    <span className="text-xl font-black uppercase">Total</span>
                                    <span className="text-3xl font-black text-indigo-600">
                                        <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                    </span>
                                </div>
                            </div>
                        </div>

                        {invoice.notes && (
                            <div className="mt-16 p-6 bg-gray-50 rounded-xl border-l-4 border-indigo-600">
                                <h4 className="text-xs font-bold uppercase text-gray-400 mb-2">Message from business</h4>
                                <p className="text-gray-700 italic">{invoice.notes}</p>
                            </div>
                        )}

                        <div className="mt-16 flex justify-center gap-4 no-print">
                            <Button size="lg" className="px-12" onClick={() => window.print()}>
                                <Download className="mr-2 h-5 w-5" /> Download PDF
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // ADMIN VIEW
    return (
        <AuthenticatedLayout>
            <Head title={`Invoice ${invoice.invoice_number}`} />
            <div className="p-6 max-w-7xl mx-auto">
                <div className="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                    <div className="flex items-center gap-4">
                        <h1 className="text-2xl font-bold font-mono">{invoice.invoice_number}</h1>
                        <StatusBadge status={invoice.status} />
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('erp.invoices.edit', invoice.id)}><Edit className="mr-2 h-4 w-4" /> Edit</Link>
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.send', invoice.id))}>
                            <Send className="mr-2 h-4 w-4" /> Send
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.mark-paid', invoice.id))}>
                            <CheckCircle className="mr-2 h-4 w-4" /> Mark Paid
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm"><MoreHorizontal className="h-4 w-4" /></Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={() => router.post(route('erp.invoices.duplicate', invoice.id))}>
                                    <Copy className="mr-2 h-4 w-4" /> Duplicate
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={route('erp.invoices.download', invoice.id)}><Download className="mr-2 h-4 w-4" /> Download PDF</Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="shadow-sm border-none bg-white">
                            <CardContent className="p-8">
                                <div className="flex justify-between mb-8">
                                    <div>
                                        <h2 className="text-xl font-bold text-indigo-600 mb-1">Business Workspace</h2>
                                        <p className="text-sm text-gray-500">Administrative Dashboard</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-gray-500 uppercase tracking-wider">Invoice To</p>
                                        <h3 className="text-lg font-bold">{invoice.client?.name}</h3>
                                        <p className="text-sm text-gray-500">{invoice.client?.email}</p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg mb-8">
                                    <div>
                                        <p className="text-xs text-gray-500 uppercase">Issued Date</p>
                                        <p className="font-medium">{new Date(invoice.issued_at).toLocaleDateString()}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 uppercase">Due Date</p>
                                        <p className="font-medium">{new Date(invoice.due_date).toLocaleDateString()}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs text-gray-500 uppercase">Amount Due</p>
                                        <p className="text-xl font-bold text-indigo-600">
                                            <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                        </p>
                                    </div>
                                </div>

                                {activeTimerItem && (
                                    <div className="mb-8 p-4 bg-purple-50 border border-purple-100 rounded-lg animate-pulse">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="h-3 w-3 bg-red-500 rounded-full"></div>
                                                <div>
                                                    <p className="text-sm font-bold text-purple-900">LIVE TIMER: {activeTimerItem.title}</p>
                                                    <p className="text-xs text-purple-700">This session is currently running...</p>
                                                </div>
                                            </div>
                                            <div className="text-2xl font-mono font-bold text-purple-900">
                                                {formatDuration(timerValue)}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Description</TableHead>
                                            <TableHead className="text-center w-[80px]">Qty</TableHead>
                                            <TableHead className="text-right w-[120px]">Rate</TableHead>
                                            <TableHead className="text-right w-[120px]">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.items.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <div className="font-medium">{item.title}</div>
                                                    {item.description && <div className="text-sm text-gray-500">{item.description}</div>}
                                                </TableCell>
                                                <TableCell className="text-center">{item.quantity}</TableCell>
                                                <TableCell className="text-right">
                                                    <CurrencyDisplay amount={item.unit_price} currency={invoice.amount_currency} />
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    <CurrencyDisplay amount={item.total} currency={invoice.amount_currency} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                <div className="mt-8 flex justify-end">
                                    <div className="w-full md:w-1/2 space-y-3">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Subtotal</span>
                                            <span><CurrencyDisplay amount={invoice.amount - invoice.tax_amount + invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                        </div>
                                        <Separator />
                                        <div className="flex justify-between items-center">
                                            <span className="text-lg font-bold">Total</span>
                                            <div className="text-right">
                                                <div className="text-2xl font-bold text-indigo-600">
                                                    <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                                </div>
                                                <div className="text-xs text-gray-500 font-medium">
                                                    ≈ <CurrencyDisplay amount={invoice.business_amount} currency={invoice.business_currency} />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {invoice.costs?.length > 0 && (
                            <Card className="border-red-100 bg-red-50/30">
                                <CardHeader>
                                    <CardTitle className="text-sm font-bold flex items-center gap-2">
                                        <Info className="h-4 w-4 text-red-500" />
                                        Internal Costs
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableBody>
                                            {invoice.costs.map((cost) => (
                                                <TableRow key={cost.id} className="border-red-100">
                                                    <TableCell className="font-medium">{cost.title}</TableCell>
                                                    <TableCell className="text-right">
                                                        <CurrencyDisplay amount={cost.amount} currency={cost.amount_currency} />
                                                    </TableCell>
                                                    <TableCell className="w-[100px]">
                                                        <Badge variant={cost.payment_status === 'paid' ? 'default' : 'outline'}>{cost.payment_status}</Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {cost.payment_status === 'unpaid' && <Button size="sm" variant="ghost">Pay</Button>}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <History className="h-5 w-5" />
                                    Payment History
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {invoice.status === 'paid' ? (
                                    <div className="flex items-center gap-4 p-4 border rounded-lg bg-green-50/50">
                                        <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <CheckCircle className="h-6 w-6" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="font-bold">Full Payment Received</p>
                                            <p className="text-sm text-gray-500">{new Date(invoice.paid_at).toLocaleString()}</p>
                                        </div>
                                        <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} className="text-lg font-black" />
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 text-center py-4">No payments recorded yet.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-bold flex items-center gap-2">
                                    <Wallet className="h-4 w-4 text-indigo-500" />
                                    Client Wallet
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="p-4 bg-indigo-50 rounded-lg">
                                    <p className="text-xs text-indigo-600 uppercase font-bold mb-1">Current Balance</p>
                                    <p className="text-2xl font-bold text-indigo-900">
                                        <CurrencyDisplay amount={invoice.client?.wallet?.balance || 0} currency={invoice.client?.wallet?.currency} />
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" className="flex-1 text-xs" onClick={() => handleWalletAction('credit')}>
                                        <ArrowDownLeft className="mr-1 h-3 w-3 text-green-600" /> Credit
                                    </Button>
                                    <Button variant="outline" size="sm" className="flex-1 text-xs" onClick={() => handleWalletAction('debit')}>
                                        <ArrowUpRight className="mr-1 h-3 w-3 text-red-600" /> Debit
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-bold">Exchange Rate Info</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Rate Used</span>
                                    <span className="font-mono">{invoice.exchange_rate}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Date Fixed</span>
                                    <span>{new Date(invoice.exchange_rate_date).toLocaleDateString()}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {referral_earnings?.length > 0 && (
                            <Card className="border-green-100 bg-green-50/30">
                                <CardHeader>
                                    <CardTitle className="text-sm font-bold text-green-800">Referral Earnings</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {referral_earnings.map((earning) => (
                                        <div key={earning.id} className="flex justify-between items-center text-sm p-2 bg-white rounded border border-green-100 shadow-sm">
                                            <div>
                                                <p className="font-bold">Level {earning.level}</p>
                                                <p className="text-xs text-gray-500">{earning.referrer?.name || `ID: ${earning.referrer_id}`}</p>
                                            </div>
                                            <p className="font-bold text-green-600">
                                                +<CurrencyDisplay amount={earning.amount} currency={earning.amount_currency} />
                                            </p>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-bold">Invoice Timeline</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {timeline.map((item, i) => (
                                        <div key={i} className="flex gap-3">
                                            <div className="relative flex flex-col items-center">
                                                <div className="h-2 w-2 rounded-full bg-indigo-600 z-10"></div>
                                                {i < timeline.length - 1 && <div className="w-px h-full bg-gray-200 absolute top-2"></div>}
                                            </div>
                                            <div className="pb-4">
                                                <p className="text-xs font-bold leading-none">{item.event}</p>
                                                <p className="text-[10px] text-gray-500 mt-1">{new Date(item.time).toLocaleString()}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
