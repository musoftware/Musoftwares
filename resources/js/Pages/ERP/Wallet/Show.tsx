import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Wallet, Lock, Unlock, ArrowUpRight, ArrowDownLeft, TrendingUp,
    History, Plus, Minus, Building2, ShieldCheck, User, FileText, ArrowLeft,
    Check, Clock, FileSpreadsheet, AlertCircle, X
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { useToast } from '@/Components/ui/use-toast';
import { ResponsiveContainer, AreaChart, Area, XAxis, YAxis, Tooltip } from 'recharts';

interface WalletShowProps {
    auth: any;
    wallet?: { id: number; balance: number; currency: string; locked_balance?: number };
    transactions?: { data: any[]; current_page: number; last_page: number; total: number };
    client?: { id: number; name: string; email: string; phone?: string; address?: string };
    errors?: any;
}

export default function Show({ auth, wallet, transactions, client, errors }: WalletShowProps) {
    const { toast } = useToast();
    
    // Safety Fallbacks
    const activeClient = client || { id: 0, name: 'Unknown Client', email: 'N/A', phone: '', address: '' };
    const activeWallet = wallet || { id: 0, balance: 0, currency: 'USD', locked_balance: 0 };
    const activeTransactions = transactions?.data || [];

    const safeRoute = (name: string, params?: any) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined' && route().has(name)) {
                // @ts-ignore
                return route(name, params);
            }
        } catch (e) {}
        return '#';
    };

    const [actionType, setActionType] = useState<'credit' | 'debit' | 'lock' | 'unlock' | null>(null);

    const creditForm = useForm({ amount: '', note: '' });
    const debitForm = useForm({ amount: '', note: '' });
    const lockForm = useForm({ amount: '', note: '' });
    const unlockForm = useForm({ amount: '', note: '' });

    // Super Admin check
    const isSuperAdmin = auth?.user?.role === 'admin';
    const [confirmEmergency, setConfirmEmergency] = useState(false);

    const handleActionSubmit = (e: React.FormEvent, type: 'credit' | 'debit' | 'lock' | 'unlock') => {
        e.preventDefault();
        
        if (!isSuperAdmin) {
            toast({ title: "Unauthorized", description: "Only super-admins can perform manual adjustments.", variant: "destructive" });
            return;
        }

        if (!confirmEmergency) {
            toast({ title: "Confirmation Required", description: "You must confirm this is an emergency audit adjustment.", variant: "destructive" });
            return;
        }

        let form: any;
        let routeName: string = '';

        if (type === 'credit') {
            form = creditForm;
            routeName = 'erp.wallet.credit';
        } else if (type === 'debit') {
            form = debitForm;
            routeName = 'erp.wallet.debit';
        } else if (type === 'lock') {
            form = lockForm;
            routeName = 'erp.wallet.lock';
        } else {
            form = unlockForm;
            routeName = 'erp.wallet.unlock';
        }

        const amt = parseFloat(form.data.amount);
        if (isNaN(amt) || amt <= 0) {
            toast({
                title: "Invalid Amount",
                description: "Please enter a valid amount greater than 0.",
                variant: "destructive"
            });
            return;
        }

        form.post(safeRoute(routeName, activeClient.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast({
                    title: `Success!`,
                    description: `Successfully executed audit ${type} transaction.`,
                });
                form.reset();
                setActionType(null);
                setConfirmEmergency(false);
            },
            onError: (err: any) => {
                toast({
                    title: "Transaction Error",
                    description: Object.values(err)[0] as string || "An unexpected ledger error occurred.",
                    variant: "destructive"
                });
            }
        });
    };

    const chartData = activeTransactions.length > 0
        ? [...activeTransactions].reverse().map((tx: any) => ({
            date: new Date(tx.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            balance: tx.balance_after ?? 0,
        }))
        : [{ date: 'Today', balance: activeWallet.balance }];

    const clientInvoices: { id: string; amount: number; status: string; date: string }[] = [];

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title={`Wallet Ledger - ${activeClient.name}`} />

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8">
                {/* Header */}
                <div className="space-y-2">
                    <Link href={safeRoute('erp.clients.index')} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                        <ArrowLeft className="mr-2 h-4 w-4" /> Client List
                    </Link>
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">{activeClient.name}'s Financial Ledger</h1>
                            <p className="text-sm text-muted-foreground">Immutable record of all client platform transactions.</p>
                        </div>
                    </div>
                </div>

                {/* Financial Summary */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card className="shadow-none">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Available Balance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                ${activeWallet.balance.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Ready for purchases</p>
                        </CardContent>
                    </Card>
                    <Card className="shadow-none">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                System Escrow <Lock className="h-3 w-3" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                ${(activeWallet.locked_balance ?? 0.00).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Held securely in active contracts</p>
                        </CardContent>
                    </Card>
                    <Card className="shadow-none bg-muted/30">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Ledger</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                ${(activeWallet.balance + (activeWallet.locked_balance ?? 0)).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Calculated from immutable events</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Super Admin Actions - Hidden from regular users */}
                {isSuperAdmin && (
                    <div className="border border-destructive/20 bg-destructive/5 rounded-xl p-4 space-y-4">
                        <div className="flex items-center gap-2 text-destructive font-semibold text-sm">
                            <AlertCircle className="w-4 h-4" /> SUPER ADMIN INTERNAL RECOVERY TOOLS
                        </div>
                        <p className="text-xs text-muted-foreground mb-4">
                            WARNING: Do not use these tools for normal operations. Balance should only be updated via system events (deposits, invoices, payouts).
                        </p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Button variant={actionType === 'credit' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'credit' ? null : 'credit')} className="h-10 shadow-none text-xs border-destructive/20">
                                <Plus className="mr-2 h-3 w-3" /> Force Credit
                            </Button>
                            <Button variant={actionType === 'debit' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'debit' ? null : 'debit')} className="h-10 shadow-none text-xs border-destructive/20">
                                <Minus className="mr-2 h-3 w-3" /> Force Debit
                            </Button>
                            <Button variant={actionType === 'lock' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'lock' ? null : 'lock')} className="h-10 shadow-none text-xs border-destructive/20">
                                <Lock className="mr-2 h-3 w-3" /> Force Lock
                            </Button>
                            <Button variant={actionType === 'unlock' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'unlock' ? null : 'unlock')} className="h-10 shadow-none text-xs border-destructive/20">
                                <Unlock className="mr-2 h-3 w-3" /> Force Unlock
                            </Button>
                        </div>

                        {/* Inline Action Panel */}
                        <AnimatePresence mode="wait">
                            {actionType && (
                                <motion.div
                                    initial={{ opacity: 0, height: 0 }}
                                    animate={{ opacity: 1, height: 'auto' }}
                                    exit={{ opacity: 0, height: 0 }}
                                    transition={{ duration: 0.2 }}
                                    className="overflow-hidden"
                                >
                                    <Card className="shadow-none border-destructive/20 bg-background mt-4">
                                        <CardHeader className="flex flex-row items-center justify-between pb-2 border-b border-border/50">
                                            <CardTitle className="text-sm font-semibold text-destructive capitalize flex items-center gap-2">
                                                Audit Emergency: {actionType} Funds
                                            </CardTitle>
                                            <Button variant="ghost" size="icon" onClick={() => setActionType(null)} className="h-6 w-6 text-muted-foreground hover:text-foreground">
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </CardHeader>
                                        <CardContent className="pt-4">
                                            <form onSubmit={(e) => handleActionSubmit(e, actionType)} className="space-y-4">
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                                                    <div className="space-y-2">
                                                        <Label className="text-xs font-semibold">Amount (USD)</Label>
                                                        <div className="relative">
                                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                                <span className="text-muted-foreground font-medium text-xs">$</span>
                                                            </div>
                                                            <Input
                                                                type="number"
                                                                step="0.01"
                                                                placeholder="0.00"
                                                                value={
                                                                    actionType === 'credit' ? creditForm.data.amount :
                                                                    actionType === 'debit' ? debitForm.data.amount :
                                                                    actionType === 'lock' ? lockForm.data.amount : unlockForm.data.amount
                                                                }
                                                                onChange={(e) => {
                                                                    const val = e.target.value;
                                                                    if (actionType === 'credit') creditForm.setData('amount', val);
                                                                    else if (actionType === 'debit') debitForm.setData('amount', val);
                                                                    else if (actionType === 'lock') lockForm.setData('amount', val);
                                                                    else unlockForm.setData('amount', val);
                                                                }}
                                                                className="pl-8 shadow-none h-9 text-sm"
                                                                required
                                                            />
                                                        </div>
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label className="text-xs font-semibold">Audit Admin Note (Required)</Label>
                                                        <Input
                                                            type="text"
                                                            placeholder="Detailed reason for manual adjustment..."
                                                            value={
                                                                actionType === 'credit' ? creditForm.data.note :
                                                                actionType === 'debit' ? debitForm.data.note :
                                                                actionType === 'lock' ? lockForm.data.note : unlockForm.data.note
                                                            }
                                                            onChange={(e) => {
                                                                const val = e.target.value;
                                                                if (actionType === 'credit') creditForm.setData('note', val);
                                                                else if (actionType === 'debit') debitForm.setData('note', val);
                                                                else if (actionType === 'lock') lockForm.setData('note', val);
                                                                else unlockForm.setData('note', val);
                                                            }}
                                                            className="shadow-none h-9 text-sm"
                                                            required
                                                        />
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2 pt-2 pb-2">
                                                    <input 
                                                        type="checkbox" 
                                                        id="confirmEmergency" 
                                                        checked={confirmEmergency} 
                                                        onChange={(e) => setConfirmEmergency(e.target.checked)}
                                                        className="rounded border-destructive/50 text-destructive focus:ring-destructive"
                                                    />
                                                    <label htmlFor="confirmEmergency" className="text-xs font-medium text-destructive">
                                                        I confirm this is an emergency internal accounting adjustment and will be permanently logged.
                                                    </label>
                                                </div>
                                                <div>
                                                    <Button
                                                        type="submit"
                                                        disabled={
                                                            !confirmEmergency || (
                                                            actionType === 'credit' ? creditForm.processing :
                                                            actionType === 'debit' ? debitForm.processing :
                                                            actionType === 'lock' ? lockForm.processing : unlockForm.processing
                                                        )}
                                                        variant="destructive"
                                                        className="w-full shadow-none"
                                                    >
                                                        Execute Permanent Ledger Adjustment
                                                    </Button>
                                                </div>
                                            </form>
                                        </CardContent>
                                    </Card>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left Column */}
                    <div className="lg:col-span-2 space-y-8">
                        
                        {/* Chart */}
                        <Card className="shadow-none">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div className="space-y-1">
                                    <CardTitle className="text-base font-semibold">Ledger Progression</CardTitle>
                                    <CardDescription>Historical balance progression</CardDescription>
                                </div>
                                <Badge variant="secondary" className="font-normal"><TrendingUp className="mr-1 h-3 w-3" /> Growth</Badge>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[200px] w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <AreaChart data={chartData} margin={{ top: 5, right: 0, left: -25, bottom: 0 }}>
                                            <XAxis dataKey="date" stroke="hsl(var(--muted-foreground))" fontSize={12} tickLine={false} axisLine={false} />
                                            <YAxis stroke="hsl(var(--muted-foreground))" fontSize={12} tickLine={false} axisLine={false} tickFormatter={(val) => `$${val}`} />
                                            <Tooltip formatter={(value: any) => [`$${value}`, 'Balance']} labelStyle={{ color: 'hsl(var(--foreground))' }} contentStyle={{ borderRadius: '8px', border: '1px solid hsl(var(--border))' }} />
                                            <Area type="monotone" dataKey="balance" stroke="hsl(var(--primary))" strokeWidth={2} fill="hsl(var(--primary))" fillOpacity={0.1} />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Transactions Table */}
                        <Card className="shadow-none">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <div className="space-y-1">
                                    <CardTitle className="text-base font-semibold">Transaction Ledger</CardTitle>
                                    <CardDescription>Immutable record of all wallet activity</CardDescription>
                                </div>
                                <Button variant="outline" size="sm" className="shadow-none"><FileSpreadsheet className="mr-2 w-4 h-4" /> Export</Button>
                            </CardHeader>
                            <CardContent className="px-0 pt-0">
                                {activeTransactions.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="pl-6">Type</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead>Before / After</TableHead>
                                                <TableHead>Description</TableHead>
                                                <TableHead className="pr-6 text-right">Date</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {activeTransactions.map((tx) => (
                                                <TableRow key={tx.id}>
                                                    <TableCell className="pl-6">
                                                        <Badge variant={tx.type === 'credit' ? 'default' : 'secondary'} className="font-normal uppercase tracking-wider text-[10px]">
                                                            {tx.type}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {tx.type === 'credit' ? '+' : '-'}${tx.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        ${tx.balance_before.toLocaleString()} &rarr; ${tx.balance_after.toLocaleString()}
                                                    </TableCell>
                                                    <TableCell className="max-w-[200px] truncate" title={tx.description}>
                                                        {tx.description}
                                                    </TableCell>
                                                    <TableCell className="pr-6 text-right text-muted-foreground text-xs">
                                                        {tx.created_at}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="py-12 text-center text-muted-foreground">
                                        <AlertCircle className="w-8 h-8 mx-auto text-muted mb-2" />
                                        No ledger activity found.
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column */}
                    <div className="space-y-6">
                        
                        {/* Client Overview */}
                        <Card className="shadow-none">
                            <CardHeader>
                                <CardTitle className="text-base font-semibold flex items-center gap-2"><User className="h-4 w-4" /> Client Overview</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                <div className="flex justify-between items-start border-b border-border pb-3">
                                    <span className="text-muted-foreground">Entity</span>
                                    <span className="font-medium text-right">{activeClient.name}</span>
                                </div>
                                <div className="flex justify-between items-start border-b border-border pb-3">
                                    <span className="text-muted-foreground">Contact</span>
                                    <span className="font-medium text-right">{activeClient.phone || 'N/A'}</span>
                                </div>
                                <div className="flex justify-between items-start border-b border-border pb-3">
                                    <span className="text-muted-foreground">Address</span>
                                    <span className="font-medium text-right max-w-[150px] truncate" title={activeClient.address}>{activeClient.address || 'N/A'}</span>
                                </div>
                                <div className="flex justify-between items-center pt-1">
                                    <span className="text-muted-foreground">Status</span>
                                    <Badge variant="outline" className="font-normal border-primary/20 bg-primary/5 text-primary flex items-center gap-1">
                                        <ShieldCheck className="w-3 h-3" /> Verified
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Recent Invoices */}
                        <Card className="shadow-none">
                            <CardHeader>
                                <CardTitle className="text-base font-semibold flex items-center gap-2"><FileText className="h-4 w-4" /> Invoices</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {clientInvoices.length > 0 ? (
                                    <div className="space-y-4 text-sm">
                                        {clientInvoices.map((inv) => (
                                            <div key={inv.id} className="flex justify-between items-center">
                                                <div>
                                                    <span className="font-medium block">{inv.id}</span>
                                                    <span className="text-muted-foreground text-xs">{inv.date}</span>
                                                </div>
                                                <div className="text-right">
                                                    <span className="font-medium block">${inv.amount.toLocaleString()}</span>
                                                    <span className="text-muted-foreground text-xs capitalize">{inv.status}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-sm text-muted-foreground">No recent invoices.</div>
                                )}
                            </CardContent>
                        </Card>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
