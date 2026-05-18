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
            <div className="min-h-screen bg-background p-4 md:p-8">
                <Head title={`Invoice ${invoice.invoice_number}`} />
                <div className="max-w-4xl mx-auto bg-card text-card-foreground shadow-sm rounded-xl overflow-hidden border">
                    {invoice.status === 'paid' ? (
                        <div className="bg-primary/10 text-primary p-4 text-center font-bold flex items-center justify-center gap-2 border-b">
                            <CheckCircle className="h-5 w-5" /> PAID ON {new Date(invoice.paid_at).toLocaleDateString()}
                        </div>
                    ) : (
                        <div className="bg-amber-500/10 text-amber-600 p-4 text-center font-bold border-b">
                            PAYMENT PENDING - DUE BY {new Date(invoice.due_date).toLocaleDateString()}
                        </div>
                    )}

                    <div className="p-8 md:p-12">
                        <div className="flex flex-col md:flex-row justify-between mb-12 gap-8">
                            <div>
                                <div className="h-16 w-16 bg-primary rounded-lg flex items-center justify-center text-primary-foreground font-bold text-2xl mb-4">LOGO</div>
                                <h2 className="text-xl font-bold">Business Name</h2>
                                <p className="text-muted-foreground">123 Business St, City, Country</p>
                            </div>
                            <div className="text-md-right">
                                <h1 className="text-3xl font-bold tracking-tight mb-2">INVOICE</h1>
                                <p className="font-mono text-muted-foreground">{invoice.invoice_number}</p>
                                <p className="text-muted-foreground mt-4">Issued: {new Date(invoice.issued_at).toLocaleDateString()}</p>
                                <p className="text-muted-foreground">Due: {new Date(invoice.due_date).toLocaleDateString()}</p>
                            </div>
                        </div>

                        <Separator />

                        <div className="mb-12">
                            <h3 className="text-xs font-bold uppercase text-muted-foreground mb-4 tracking-widest">Bill To</h3>
                            <p className="text-xl font-bold">{invoice.client?.name}</p>
                            <p className="text-muted-foreground">{invoice.client?.email}</p>
                        </div>

                        {activeTimerItem && (
                            <div className="mb-8 p-6 bg-destructive/5 border border-destructive/20 rounded-xl">
                                <div className="flex flex-col md:flex-row items-center justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <div className="h-4 w-4 bg-destructive rounded-full animate-ping"></div>
                                        <div>
                                            <p className="font-bold text-destructive uppercase text-xs tracking-wider">Live Session Running</p>
                                            <p className="text-lg font-bold">{activeTimerItem.title}</p>
                                        </div>
                                    </div>
                                    <div className="text-4xl font-mono font-black text-destructive">
                                        {formatDuration(timerValue)}
                                    </div>
                                </div>
                                <p className="text-sm text-muted-foreground mt-4 text-center md:text-left">
                                    This session + previous sessions: <span className="font-bold">{(timerValue/60 + parseFloat(activeTimerItem.quantity)).toFixed(0)} mins</span> total.
                                </p>
                            </div>
                        )}

                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50">
                                    <TableHead className="font-bold">Description</TableHead>
                                    <TableHead className="text-center font-bold">Qty</TableHead>
                                    <TableHead className="text-right font-bold">Rate</TableHead>
                                    <TableHead className="text-right font-bold">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoice.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="py-6">
                                            <div className="font-bold">{item.title}</div>
                                            {item.description && <div className="text-sm text-muted-foreground mt-1">{item.description}</div>}
                                            {item.type === 'timer' && <Badge variant="outline" className="mt-2 text-primary border-primary/20 bg-primary/5">Timer Item</Badge>}
                                        </TableCell>
                                        <TableCell className="text-center">{item.quantity}</TableCell>
                                        <TableCell className="text-right">
                                            <CurrencyDisplay amount={item.unit_price} currency={invoice.amount_currency} />
                                        </TableCell>
                                        <TableCell className="text-right font-bold">
                                            <CurrencyDisplay amount={item.total} currency={invoice.amount_currency} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <div className="mt-12 flex justify-end">
                            <div className="w-full md:w-1/3 space-y-4">
                                <div className="flex justify-between text-muted-foreground">
                                    <span>Subtotal</span>
                                    <span><CurrencyDisplay amount={invoice.amount - invoice.tax_amount + invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                </div>
                                {invoice.discount_amount > 0 && (
                                    <div className="flex justify-between text-destructive">
                                        <span>Discount</span>
                                        <span>-<CurrencyDisplay amount={invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                    </div>
                                )}
                                {invoice.tax_amount > 0 && (
                                    <div className="flex justify-between text-muted-foreground">
                                        <span>Tax ({invoice.tax_rate}%)</span>
                                        <span><CurrencyDisplay amount={invoice.tax_amount} currency={invoice.amount_currency} /></span>
                                    </div>
                                )}
                                <div className="pt-4 border-t-2 border-border flex justify-between items-center">
                                    <span className="text-xl font-black uppercase text-foreground">Total</span>
                                    <span className="text-3xl font-black text-primary">
                                        <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                    </span>
                                </div>
                            </div>
                        </div>

                        {invoice.notes && (
                            <div className="mt-16 p-6 bg-muted/30 rounded-xl border-l-4 border-primary">
                                <h4 className="text-xs font-bold uppercase text-muted-foreground mb-2">Message from business</h4>
                                <p className="italic text-foreground">{invoice.notes}</p>
                            </div>
                        )}

                        <div className="mt-16 flex justify-center gap-4 no-print">
                            <Button size="lg" className="px-12 shadow-none">
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
        <AuthenticatedLayout header={`Invoice ${invoice.invoice_number}`}>
            <Head title={`Invoice ${invoice.invoice_number}`} />
            <div className="p-4 sm:p-6 lg:p-8 max-w-[1100px] mx-auto font-sans">
                
                {/* ──────────────────────────────────────────────────────── */}
                {/* PAGE HEADER */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 border-b border-slate-100 pb-6">
                    <div className="flex items-center gap-4">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{invoice.invoice_number}</h1>
                        <StatusBadge status={invoice.status} />
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild className="shadow-sm border-slate-200">
                            <Link href={route('erp.invoices.edit', invoice.id)}><Edit className="mr-2 h-4 w-4" /> Edit</Link>
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.send', invoice.id))} className="shadow-sm border-slate-200">
                            <Send className="mr-2 h-4 w-4" /> Send
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.mark-paid', invoice.id))} className="shadow-sm border-slate-200">
                            <CheckCircle className="mr-2 h-4 w-4 text-emerald-600" /> Mark Paid
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="shadow-sm border-slate-200"><MoreHorizontal className="h-4 w-4" /></Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="font-sans">
                                <DropdownMenuItem onClick={() => router.post(route('erp.invoices.duplicate', invoice.id))} className="text-sm">
                                    <Copy className="mr-2 h-4 w-4 text-slate-400" /> Duplicate
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild className="text-sm">
                                    <Link href={route('erp.invoices.download', invoice.id)}><Download className="mr-2 h-4 w-4 text-slate-400" /> Download PDF</Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-12">
                    
                    {/* ──────────────────────────────────────────────────────── */}
                    {/* MAIN CONTENT COLUMN */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <div className="lg:col-span-8 space-y-12">
                        
                        {/* INVOICE DETAILS SECTION */}
                        <section>
                            <div className="flex justify-between items-start mb-8">
                                <div>
                                    <h2 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-1">Business Workspace</h2>
                                    <p className="text-sm text-slate-500">Administrative Dashboard</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Invoice To</p>
                                    <h3 className="text-base font-semibold text-slate-900">{invoice.client?.name}</h3>
                                    <p className="text-sm text-slate-500">{invoice.client?.email}</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-4 p-5 bg-slate-50 rounded-2xl mb-8 border border-slate-100 shadow-sm">
                                <div>
                                    <p className="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Issued</p>
                                    <p className="font-medium text-slate-900 text-sm">{new Date(invoice.issued_at).toLocaleDateString()}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Due</p>
                                    <p className="font-medium text-slate-900 text-sm">{new Date(invoice.due_date).toLocaleDateString()}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Amount Due</p>
                                    <p className="text-lg font-bold text-indigo-600 tracking-tight">
                                        <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                    </p>
                                </div>
                            </div>

                            {activeTimerItem && (
                                <div className="mb-8 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="h-2.5 w-2.5 bg-rose-500 rounded-full animate-pulse"></div>
                                        <div>
                                            <p className="text-xs font-bold text-rose-700 uppercase tracking-wider">Live Timer Running</p>
                                            <p className="text-sm font-medium text-rose-900">{activeTimerItem.title}</p>
                                        </div>
                                    </div>
                                    <div className="text-2xl font-bold font-mono text-rose-600 tracking-tight">
                                        {formatDuration(timerValue)}
                                    </div>
                                </div>
                            )}

                            <div className="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden mb-8">
                                <Table>
                                    <TableHeader className="bg-slate-50/50">
                                        <TableRow className="border-slate-100 hover:bg-transparent">
                                            <TableHead className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</TableHead>
                                            <TableHead className="text-center w-[80px] text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</TableHead>
                                            <TableHead className="text-right w-[120px] text-xs font-semibold text-slate-500 uppercase tracking-wider">Rate</TableHead>
                                            <TableHead className="text-right w-[120px] text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.items.map((item) => (
                                            <TableRow key={item.id} className="border-slate-100 hover:bg-slate-50/50">
                                                <TableCell className="py-4">
                                                    <div className="font-medium text-slate-900 text-sm">{item.title}</div>
                                                    {item.description && <div className="text-sm text-slate-500 mt-1">{item.description}</div>}
                                                </TableCell>
                                                <TableCell className="text-center font-medium text-slate-700 text-sm">{item.quantity}</TableCell>
                                                <TableCell className="text-right font-medium text-slate-700 text-sm">
                                                    <CurrencyDisplay amount={item.unit_price} currency={invoice.amount_currency} />
                                                </TableCell>
                                                <TableCell className="text-right font-semibold text-slate-900 text-sm">
                                                    <CurrencyDisplay amount={item.total} currency={invoice.amount_currency} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            <div className="flex justify-end">
                                <div className="w-full md:w-1/2 space-y-3 bg-slate-50 p-6 rounded-2xl border border-slate-100 shadow-sm">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500 font-medium">Subtotal</span>
                                        <span className="font-medium text-slate-900"><CurrencyDisplay amount={invoice.amount - invoice.tax_amount + invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                    </div>
                                    <div className="h-px bg-slate-200 w-full my-3" />
                                    <div className="flex justify-between items-end">
                                        <span className="text-base font-semibold text-slate-900">Total</span>
                                        <div className="text-right">
                                            <div className="text-2xl font-bold text-indigo-600 tracking-tight">
                                                <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                            </div>
                                            <div className="text-xs text-slate-400 font-medium mt-1">
                                                ≈ <CurrencyDisplay amount={invoice.business_amount} currency={invoice.business_currency} />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* INTERNAL COSTS SECTION */}
                        {invoice.costs?.length > 0 && (
                            <section className="pt-10 border-t border-slate-100">
                                <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <Info className="h-4 w-4 text-slate-400" />
                                    Internal Costs
                                </h3>
                                <div className="border border-slate-100 rounded-2xl overflow-hidden bg-white shadow-sm">
                                    <Table>
                                        <TableBody>
                                            {invoice.costs.map((cost) => (
                                                <TableRow key={cost.id} className="border-slate-100 hover:bg-slate-50/50">
                                                    <TableCell className="font-medium text-slate-900 text-sm py-3">{cost.title}</TableCell>
                                                    <TableCell className="text-right font-medium text-slate-900 text-sm">
                                                        <CurrencyDisplay amount={cost.amount} currency={cost.amount_currency} />
                                                    </TableCell>
                                                    <TableCell className="w-[100px]">
                                                        <Badge variant="secondary" className={cost.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}>
                                                            {cost.payment_status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right w-[80px]">
                                                        {cost.payment_status === 'unpaid' && (
                                                            <Button size="sm" variant="ghost" className="text-rose-600 hover:bg-rose-50 hover:text-rose-700 h-8 text-xs font-medium">Pay</Button>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </section>
                        )}

                        {/* PAYMENT HISTORY SECTION */}
                        <section className="pt-10 border-t border-slate-100">
                            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <History className="h-4 w-4 text-slate-400" />
                                Payment History
                            </h3>
                            {invoice.status === 'paid' ? (
                                <div className="flex items-center gap-4 p-5 border border-emerald-100 rounded-2xl bg-emerald-50/50">
                                    <div className="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                        <CheckCircle className="h-5 w-5" />
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-semibold text-emerald-900 text-sm">Full Payment Received</p>
                                        <p className="text-xs text-emerald-600/80 font-medium mt-0.5">{new Date(invoice.paid_at).toLocaleString()}</p>
                                    </div>
                                    <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} className="text-lg font-bold text-emerald-700 tracking-tight" />
                                </div>
                            ) : (
                                <div className="p-8 text-center border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <p className="text-sm text-slate-500 font-medium">No payments recorded yet.</p>
                                </div>
                            )}
                        </section>
                    </div>

                    {/* ──────────────────────────────────────────────────────── */}
                    {/* SIDEBAR COLUMN */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <div className="lg:col-span-4 space-y-6">
                        
                        <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                            <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                                <Wallet className="h-4 w-4 text-slate-400" />
                                Client Wallet
                            </h3>
                            <div className="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <p className="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Available Balance</p>
                                <p className="text-xl font-bold tracking-tight text-slate-900">
                                    <CurrencyDisplay amount={invoice.client?.wallet?.balance || 0} currency={invoice.client?.wallet?.currency} />
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" className="flex-1 text-xs shadow-sm border-slate-200" onClick={() => handleWalletAction('credit')}>
                                    <ArrowDownLeft className="mr-1.5 h-3.5 w-3.5 text-emerald-500" /> Credit
                                </Button>
                                <Button variant="outline" size="sm" className="flex-1 text-xs shadow-sm border-slate-200" onClick={() => handleWalletAction('debit')}>
                                    <ArrowUpRight className="mr-1.5 h-3.5 w-3.5 text-rose-500" /> Debit
                                </Button>
                            </div>
                        </div>

                        <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                            <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Exchange Rate</h3>
                            <div className="space-y-3">
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Rate Used</span>
                                    <span className="font-mono bg-slate-100 px-2 py-0.5 rounded text-xs font-semibold text-slate-700">{invoice.exchange_rate}</span>
                                </div>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Date Fixed</span>
                                    <span className="font-medium text-slate-900">{new Date(invoice.exchange_rate_date).toLocaleDateString()}</span>
                                </div>
                            </div>
                        </div>

                        {referral_earnings?.length > 0 && (
                            <div className="bg-emerald-50/50 p-5 rounded-2xl border border-emerald-100 space-y-4">
                                <h3 className="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Referral Earnings</h3>
                                <div className="space-y-2">
                                    {referral_earnings.map((earning) => (
                                        <div key={earning.id} className="flex justify-between items-center text-sm p-3 bg-white rounded-xl border border-emerald-100 shadow-sm">
                                            <div>
                                                <p className="font-semibold text-slate-900 text-xs">Level {earning.level}</p>
                                                <p className="text-[11px] text-slate-500 font-medium mt-0.5">{earning.referrer?.name || `ID: ${earning.referrer_id}`}</p>
                                            </div>
                                            <p className="font-bold text-emerald-600">
                                                +<CurrencyDisplay amount={earning.amount} currency={earning.amount_currency} />
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                            <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Audit Timeline</h3>
                            <div className="space-y-4 pt-2">
                                {timeline.map((item, i) => (
                                    <div key={i} className="flex gap-4">
                                        <div className="relative flex flex-col items-center">
                                            <div className="h-2.5 w-2.5 rounded-full bg-slate-300 ring-4 ring-slate-100 z-10"></div>
                                            {i < timeline.length - 1 && <div className="w-px h-full bg-slate-100 absolute top-2.5"></div>}
                                        </div>
                                        <div className="pb-4 pt-0.5">
                                            <p className="text-sm font-medium leading-none text-slate-900">{item.event}</p>
                                            <p className="text-xs font-medium text-slate-400 mt-1.5">{new Date(item.time).toLocaleString()}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
