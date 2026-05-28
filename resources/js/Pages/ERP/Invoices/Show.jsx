import React, { useState, useEffect } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Edit, Send, CheckCircle, Copy, Download, MoreHorizontal, Clock, Wallet, Info, History, ArrowUpRight, ArrowDownLeft } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { PromptModal } from '@/Components/ui/ConfirmModal';

const Separator = () => <div className="h-px bg-gray-200 w-full my-4" />;

export default function Show({ invoice, timeline, referral_earnings }) {
    const { auth } = usePage().props;
    const isClientView = !auth.user.roles?.includes('admin') && auth.user.id !== invoice.created_by;

    const [timerValue, setTimerValue] = useState(0);
    const [walletModal, setWalletModal] = useState({ open: false, type: '' });
    const activeTimerItem = invoice.items.find((i) => i.type === 'timer' && i.timer_sessions?.some((s) => !s.stopped_at));

    const clientViewWorkspaceName = auth.user?.workspace_name || auth.user?.name || 'Your Business';

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
        setWalletModal({ open: true, type });
    };

    const handleWalletConfirm = (amount) => {
        if (amount && !isNaN(amount)) {
            router.post(route(`erp.clients.wallet.${walletModal.type}`, invoice.client_id), { amount });
        }
        setWalletModal({ open: false, type: '' });
    };

    if (isClientView) {

    return (
            <div className="min-h-screen bg-background p-4 md:p-8">
                <Head title={`Invoice ${invoice.invoice_number}`} />
                <div className="max-w-4xl mx-auto bg-card text-card-foreground shadow-sm rounded-xl overflow-hidden border">
                    {invoice.status === 'paid' ? (
                        <div className="bg-primary/10 text-primary p-4 text-center font-bold flex items-center justify-center gap-2 border-b">
                            <CheckCircle className="h-5 w-5" /> PAID ON <DateDisplay date={invoice.paid_at} />
                        </div>
                    ) : (
                        <div className="bg-amber-500/10 text-amber-600 p-4 text-center font-bold border-b">
                            PAYMENT PENDING — DUE BY <DateDisplay date={invoice.due_date} />
                        </div>
                    )}

                    <div className="p-8 md:p-12">
                        <div className="flex flex-col md:flex-row justify-between mb-12 gap-8">
                            <div>
                                <div className="h-12 w-12 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold text-lg mb-4">
                                    {clientViewWorkspaceName.charAt(0).toUpperCase()}
                                </div>
                                <h2 className="text-xl font-bold">{clientViewWorkspaceName}</h2>
                                <p className="text-muted-foreground">{auth.user?.email}</p>
                            </div>
                            <div className="text-md-right">
                                <h1 className="text-3xl font-bold tracking-tight mb-2">INVOICE</h1>
                                <p className="font-mono text-muted-foreground">{invoice.invoice_number}</p>
                                <p className="text-muted-foreground mt-4">Issued: <DateDisplay date={invoice.issued_at} /></p>
                                <p className="text-muted-foreground">Due: <DateDisplay date={invoice.due_date} /></p>
                            </div>
                        </div>

                        <Separator />

                        <div className="mb-12">
                            <h3 className="text-xs font-bold uppercase text-muted-foreground mb-4 tracking-widest">Bill To</h3>
                            <p className="text-xl font-bold">{invoice.client?.name}</p>
                            <p className="text-muted-foreground">{invoice.client?.email}</p>
                            {invoice.project && (
                                <div className="mt-4">
                                    <h4 className="text-xs font-bold uppercase text-muted-foreground mb-1 tracking-widest">Project</h4>
                                    <p className="text-md font-semibold text-primary">{invoice.project.name}</p>
                                </div>
                            )}
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
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={`Invoice ${invoice.invoice_number}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans space-y-12">
                
                {/* ──────────────────────────────────────────────────────── */}
                {/* HEADER & ACTIONS */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-6">
                    <div className="flex items-center gap-4">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{invoice.invoice_number}</h1>
                        <StatusBadge status={invoice.status} />
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {invoice.status === 'draft' && (
                            <>
                                <Button variant="outline" size="sm" asChild className="shadow-sm border-slate-200 hover:bg-slate-50 transition-colors text-slate-700">
                                    <Link href={route('erp.invoices.edit', invoice.id)}><Edit className="mr-2 h-4 w-4" /> Edit Draft</Link>
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.send', invoice.id))} className="shadow-sm border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                                    <Send className="mr-2 h-4 w-4" /> Issue Invoice
                                </Button>
                            </>
                        )}
                        {(invoice.status === 'sent' || invoice.status === 'partial') && (
                            <>
                                <Button variant="outline" size="sm" asChild className="shadow-sm border-slate-200 hover:bg-slate-50 transition-colors text-slate-700">
                                    <Link href={route('erp.invoices.edit', invoice.id)}><Edit className="mr-2 h-4 w-4" /> Edit Draft</Link>
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.mark-paid', invoice.id))} className="shadow-sm border-emerald-200 text-emerald-700 hover:bg-emerald-50 transition-colors">
                                    <CheckCircle className="mr-2 h-4 w-4" /> Mark as Paid
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => router.post(route('erp.invoices.pay-wallet', invoice.id))} className="shadow-sm border-indigo-200 text-indigo-700 hover:bg-indigo-50 transition-colors">
                                    <Wallet className="mr-2 h-4 w-4" /> Pay with Wallet
                                </Button>
                            </>
                        )}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="shadow-sm border-slate-200"><MoreHorizontal className="h-4 w-4 text-slate-500" /></Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48">
                                <DropdownMenuItem onClick={() => router.post(route('erp.invoices.duplicate', invoice.id))} className="cursor-pointer">
                                    <Copy className="mr-2 h-4 w-4 text-slate-400" /> Duplicate
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild className="cursor-pointer">
                                    <Link href={route('erp.invoices.download', invoice.id)}><Download className="mr-2 h-4 w-4 text-slate-400" /> Download PDF</Link>
                                </DropdownMenuItem>
                                {(invoice.status === 'sent' || invoice.status === 'partial' || invoice.status === 'paid') && (
                                    <DropdownMenuItem onClick={() => router.post(route('erp.invoices.cancel', invoice.id))} className="cursor-pointer text-rose-600 hover:text-rose-700 focus:text-rose-700">
                                        <CheckCircle className="mr-2 h-4 w-4 text-rose-500" /> Cancel Invoice
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    {/* ──────────────────────────────────────────────────────── */}
                    {/* MAIN INVOICE DOCUMENT */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <div className="lg:col-span-8 space-y-10">
                        
                        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 md:p-12">
                            <div className="flex justify-between items-start mb-10">
                                <div>
                                    <div className="h-12 w-12 bg-slate-900 rounded-xl flex items-center justify-center text-white font-bold text-lg mb-4">M</div>
                                    <h2 className="text-lg font-semibold text-slate-900">Musoftware</h2>
                                    <p className="text-sm text-slate-500">Business Workspace</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Billed To</p>
                                    <h3 className="text-base font-semibold text-slate-900">{invoice.client?.name}</h3>
                                    <p className="text-sm text-slate-500">{invoice.client?.email}</p>
                                    {invoice.project && (
                                        <div className="mt-3">
                                            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Project</p>
                                            <p className="text-sm font-medium text-indigo-600">{invoice.project.name}</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-6 py-5 border-y border-slate-100 mb-10">
                                <div>
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Issued Date</p>
                                <p className="font-medium text-sm text-slate-900"><DateDisplay date={invoice.issued_at} /></p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Due Date</p>
                                <p className="font-medium text-sm text-slate-900"><DateDisplay date={invoice.due_date} /></p>
                                </div>
                                <div className="text-right">
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Amount Due</p>
                                    <p className="text-lg font-bold text-indigo-600">
                                        <CurrencyDisplay amount={invoice.amount} currency={invoice.amount_currency} />
                                    </p>
                                </div>
                            </div>

                            {activeTimerItem && (
                                <div className="mb-10 p-5 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="h-3 w-3 bg-rose-500 rounded-full animate-pulse"></div>
                                        <div>
                                            <p className="text-sm font-semibold text-indigo-900">LIVE TRACKING: {activeTimerItem.title}</p>
                                            <p className="text-xs text-indigo-700/70">Session is currently active.</p>
                                        </div>
                                    </div>
                                    <div className="text-2xl font-mono font-bold text-indigo-600">
                                        {formatDuration(timerValue)}
                                    </div>
                                </div>
                            )}

                            <div className="mb-10">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="border-b border-slate-100 hover:bg-transparent">
                                            <TableHead className="text-xs font-semibold text-slate-500 uppercase tracking-wider h-10">Description</TableHead>
                                            <TableHead className="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider h-10 w-[80px]">Qty</TableHead>
                                            <TableHead className="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider h-10 w-[120px]">Rate</TableHead>
                                            <TableHead className="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider h-10 w-[120px]">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.items.map((item) => (
                                            <TableRow key={item.id} className="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                                <TableCell className="py-4">
                                                    <div className="font-medium text-slate-900">{item.title}</div>
                                                    {item.description && <div className="text-sm text-slate-500 mt-0.5">{item.description}</div>}
                                                </TableCell>
                                                <TableCell className="text-center font-medium text-slate-700 py-4">{item.quantity}</TableCell>
                                                <TableCell className="text-right font-medium text-slate-700 py-4">
                                                    <CurrencyDisplay amount={item.unit_price} currency={invoice.amount_currency} />
                                                </TableCell>
                                                <TableCell className="text-right font-semibold text-slate-900 py-4">
                                                    <CurrencyDisplay amount={item.total} currency={invoice.amount_currency} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            <div className="flex justify-end">
                                <div className="w-full md:w-1/2 space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500 font-medium">Subtotal</span>
                                        <span className="font-medium text-slate-900"><CurrencyDisplay amount={invoice.amount - invoice.tax_amount + invoice.discount_amount} currency={invoice.amount_currency} /></span>
                                    </div>
                                    <div className="pt-3 border-t border-slate-100 flex justify-between items-center">
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
                        </div>

                        {/* INTERNAL COSTS (ADMIN ONLY) */}
                        {invoice.costs?.length > 0 && (
                            <div>
                                <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <Info className="h-4 w-4 text-rose-500" /> Internal Costs
                                </h3>
                                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                                    <Table>
                                        <TableBody>
                                            {invoice.costs.map((cost) => (
                                                <TableRow key={cost.id} className="border-b border-slate-50 hover:bg-slate-50/50">
                                                    <TableCell className="font-medium text-slate-900 py-4">{cost.title}</TableCell>
                                                    <TableCell className="text-right font-medium text-slate-900 py-4">
                                                        <CurrencyDisplay amount={cost.amount} currency={cost.amount_currency} />
                                                    </TableCell>
                                                    <TableCell className="w-[100px] py-4">
                                                        <Badge variant={cost.payment_status === 'paid' ? 'default' : 'outline'} className={cost.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'text-slate-500'}>
                                                            {cost.payment_status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right py-4">
                                                        {cost.payment_status === 'unpaid' && <Button size="sm" variant="ghost" className="text-rose-600 hover:bg-rose-50 hover:text-rose-700">Pay Now</Button>}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        )}
                        
                    </div>

                    {/* ──────────────────────────────────────────────────────── */}
                    {/* SIDEBAR: TIMELINE, WALLET, DETAILS */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <div className="lg:col-span-4 space-y-8">
                        
                        {/* PAYMENT HISTORY */}
                        <div>
                            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <History className="h-4 w-4 text-slate-400" /> Payment Status
                            </h3>
                            {invoice.status === 'paid' ? (
                                <div className="flex items-center gap-4 p-5 rounded-2xl bg-emerald-50 border border-emerald-100">
                                    <div className="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                                        <CheckCircle className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="font-semibold text-emerald-900">Paid in Full</p>
                                        <p className="text-xs text-emerald-700/80 mt-0.5"><DateDisplay date={invoice.paid_at} format="datetime" /></p>
                                    </div>
                                </div>
                            ) : (
                                <div className="p-6 text-center rounded-2xl bg-slate-50 border border-slate-100">
                                    <p className="text-sm text-slate-500 font-medium">Awaiting payment.</p>
                                </div>
                            )}
                        </div>

                        {/* CLIENT WALLET QUICK ACTIONS */}
                        <div>
                            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <Wallet className="h-4 w-4 text-slate-400" /> Client Wallet
                            </h3>
                            <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                                <div className="flex justify-between items-center">
                                    <p className="text-sm font-medium text-slate-500">Available Balance</p>
                                    <p className="text-lg font-bold tracking-tight text-slate-900">
                                        <CurrencyDisplay amount={invoice.client?.wallet?.balance || 0} currency={invoice.client?.currency?.currency || invoice.amount_currency} />
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" className="flex-1 shadow-sm border-slate-200 text-slate-700 hover:bg-slate-50" onClick={() => handleWalletAction('credit')}>
                                        <ArrowDownLeft className="mr-1.5 h-3.5 w-3.5 text-emerald-600" /> Credit
                                    </Button>
                                    <Button variant="outline" size="sm" className="flex-1 shadow-sm border-slate-200 text-slate-700 hover:bg-slate-50" onClick={() => handleWalletAction('debit')}>
                                        <ArrowUpRight className="mr-1.5 h-3.5 w-3.5 text-rose-600" /> Debit
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* EXCHANGE RATE */}
                        <div className="bg-slate-50 rounded-2xl p-5 border border-slate-100 space-y-3">
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 font-medium">Exchange Rate</span>
                                <span className="font-mono font-semibold text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200">{invoice.exchange_rate}</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 font-medium">Rate Date</span>
                                <span className="font-medium text-slate-700"><DateDisplay date={invoice.exchange_rate_date} /></span>
                            </div>
                        </div>

                        {/* REFERRALS */}
                        {referral_earnings?.length > 0 && (
                            <div>
                                <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <CheckCircle className="h-4 w-4 text-emerald-500" /> Referral Distribution
                                </h3>
                                <div className="space-y-3">
                                    {referral_earnings.map((earning) => (
                                        <div key={earning.id} className="flex justify-between items-center text-sm p-4 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                            <div>
                                                <p className="font-semibold text-slate-900">Level {earning.level}</p>
                                                <p className="text-xs text-slate-500 font-medium mt-0.5">{earning.referrer?.name || `ID: ${earning.referrer_id}`}</p>
                                            </div>
                                            <p className="font-bold text-emerald-600">
                                                +<CurrencyDisplay amount={earning.amount} currency={earning.amount_currency} />
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* TIMELINE */}
                        <div>
                            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">Activity Timeline</h3>
                            <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                                <div className="space-y-5">
                                    {timeline.map((item, i) => (
                                        <div key={i} className="flex gap-4">
                                            <div className="relative flex flex-col items-center mt-1">
                                                <div className="h-2 w-2 rounded-full bg-slate-400 ring-4 ring-slate-100 z-10"></div>
                                                {i < timeline.length - 1 && <div className="w-px h-full bg-slate-100 absolute top-2"></div>}
                                            </div>
                                            <div className="pb-1">
                                                <p className="text-sm font-medium text-slate-900">{item.event}</p>
                                                <p className="text-xs text-slate-400 mt-1"><DateDisplay date={item.time} format="datetime" /></p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <PromptModal
                isOpen={walletModal.open}
                title={walletModal.type === 'credit' ? 'Credit Client Wallet' : 'Debit Client Wallet'}
                description={`Enter the amount to ${walletModal.type} for ${invoice.client?.name}.`}
                label="Amount"
                placeholder="0.00"
                inputType="number"
                confirmLabel={walletModal.type === 'credit' ? 'Credit' : 'Debit'}
                onConfirm={handleWalletConfirm}
                onCancel={() => setWalletModal({ open: false, type: '' })}
            />
        </ERPLayout>
    );
}
