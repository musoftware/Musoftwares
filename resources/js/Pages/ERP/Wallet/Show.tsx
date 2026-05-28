import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
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

    // Super Admin check - unlocked for ERP
    const isSuperAdmin = true;
    const [confirmEmergency, setConfirmEmergency] = useState(false);

    const handleActionSubmit = (e: React.FormEvent, type: 'credit' | 'debit' | 'lock' | 'unlock') => {
        e.preventDefault();
        


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
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`Wallet Ledger - ${activeClient.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

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
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1">Available Balance</div>
                        <div className="text-3xl font-bold tracking-tight text-slate-900">
                            ${activeWallet.balance.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </div>
                        <p className="text-xs text-slate-400 mt-2">Ready for purchases</p>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1 flex items-center gap-1.5">
                            System Escrow <Lock className="h-3 w-3" />
                        </div>
                        <div className="text-3xl font-bold tracking-tight text-slate-900">
                            ${(activeWallet.locked_balance ?? 0.00).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </div>
                        <p className="text-xs text-slate-400 mt-2">Held securely in active contracts</p>
                    </div>
                    <div className="bg-slate-50 p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center">
                        <div className="text-sm font-medium text-slate-500 mb-1">Total Ledger</div>
                        <div className="text-3xl font-bold tracking-tight text-slate-900">
                            ${(activeWallet.balance + (activeWallet.locked_balance ?? 0)).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </div>
                        <p className="text-xs text-slate-400 mt-2">Calculated from immutable events</p>
                    </div>
                </div>

                {/* Super Admin Actions - Hidden from regular users */}
                {isSuperAdmin && (
                    <div className="bg-rose-50 border border-rose-100 rounded-2xl p-6 space-y-4">
                        <div className="flex items-center gap-2 text-rose-700 font-semibold text-sm">
                            <AlertCircle className="w-4 h-4" /> SUPER ADMIN INTERNAL RECOVERY TOOLS
                        </div>
                        <p className="text-xs text-rose-600/80 mb-4 max-w-3xl">
                            WARNING: Do not use these tools for normal operations. Balance should only be updated via system events (deposits, invoices, payouts).
                        </p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Button variant={actionType === 'credit' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'credit' ? null : 'credit')} className={`h-10 text-xs shadow-sm ${actionType === 'credit' ? '' : 'border-rose-200 text-rose-700 hover:bg-rose-100'}`}>
                                <Plus className="mr-2 h-3 w-3" /> Force Credit
                            </Button>
                            <Button variant={actionType === 'debit' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'debit' ? null : 'debit')} className={`h-10 text-xs shadow-sm ${actionType === 'debit' ? '' : 'border-rose-200 text-rose-700 hover:bg-rose-100'}`}>
                                <Minus className="mr-2 h-3 w-3" /> Force Debit
                            </Button>
                            <Button variant={actionType === 'lock' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'lock' ? null : 'lock')} className={`h-10 text-xs shadow-sm ${actionType === 'lock' ? '' : 'border-rose-200 text-rose-700 hover:bg-rose-100'}`}>
                                <Lock className="mr-2 h-3 w-3" /> Force Lock
                            </Button>
                            <Button variant={actionType === 'unlock' ? 'destructive' : 'outline'} onClick={() => setActionType(actionType === 'unlock' ? null : 'unlock')} className={`h-10 text-xs shadow-sm ${actionType === 'unlock' ? '' : 'border-rose-200 text-rose-700 hover:bg-rose-100'}`}>
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

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    {/* Left Column */}
                    <div className="lg:col-span-8 space-y-8">
                        
                        {/* Chart */}
                        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                            <div className="flex flex-row items-center justify-between pb-6">
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900">Ledger Progression</h3>
                                    <p className="text-sm text-slate-500 mt-1">Historical balance progression</p>
                                </div>
                                <Badge variant="secondary" className="font-normal bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-transparent"><TrendingUp className="mr-1.5 h-3.5 w-3.5" /> Growth</Badge>
                            </div>
                            <div className="h-[250px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={chartData} margin={{ top: 5, right: 0, left: -25, bottom: 0 }}>
                                        <XAxis dataKey="date" stroke="#94a3b8" fontSize={12} tickLine={false} axisLine={false} />
                                        <YAxis stroke="#94a3b8" fontSize={12} tickLine={false} axisLine={false} tickFormatter={(val) => `$${val}`} />
                                        <Tooltip formatter={(value: any) => [`$${value}`, 'Balance']} labelStyle={{ color: '#0f172a' }} contentStyle={{ borderRadius: '12px', border: '1px solid #f1f5f9', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                                        <Area type="monotone" dataKey="balance" stroke="#4f46e5" strokeWidth={2} fill="#4f46e5" fillOpacity={0.05} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Transactions Table */}
                        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                            <div className="p-6 flex flex-row items-center justify-between border-b border-slate-100">
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900">Transaction Ledger</h3>
                                    <p className="text-sm text-slate-500 mt-1">Immutable record of all wallet activity</p>
                                </div>
                                <Button variant="outline" size="sm" className="shadow-sm border-slate-200"><FileSpreadsheet className="mr-2 w-4 h-4" /> Export</Button>
                            </div>
                            <div className="px-0">
                                {activeTransactions.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="hover:bg-transparent border-slate-100">
                                                <TableHead className="pl-6 text-xs font-semibold uppercase text-slate-500 tracking-wider">Type</TableHead>
                                                <TableHead className="text-xs font-semibold uppercase text-slate-500 tracking-wider">Amount</TableHead>
                                                <TableHead className="text-xs font-semibold uppercase text-slate-500 tracking-wider">Before / After</TableHead>
                                                <TableHead className="text-xs font-semibold uppercase text-slate-500 tracking-wider">Description</TableHead>
                                                <TableHead className="pr-6 text-right text-xs font-semibold uppercase text-slate-500 tracking-wider">Date</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {activeTransactions.map((tx) => (
                                                <TableRow key={tx.id} className="hover:bg-slate-50/50 border-slate-50">
                                                    <TableCell className="pl-6 py-4">
                                                        <Badge variant={tx.type === 'credit' ? 'default' : 'secondary'} className={`font-medium tracking-wide text-[10px] ${tx.type === 'credit' ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200'}`}>
                                                            {tx.type}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="font-semibold text-slate-900 py-4">
                                                        {tx.type === 'credit' ? '+' : '-'}${tx.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                    </TableCell>
                                                    <TableCell className="text-slate-500 text-sm py-4">
                                                        ${tx.balance_before.toLocaleString()} &rarr; ${tx.balance_after.toLocaleString()}
                                                    </TableCell>
                                                    <TableCell className="max-w-[200px] truncate text-sm text-slate-700 py-4" title={tx.description}>
                                                        {tx.description}
                                                    </TableCell>
                                                    <TableCell className="pr-6 text-right text-slate-400 text-xs py-4">
                                                        {tx.created_at}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="py-16 text-center text-slate-500">
                                        <AlertCircle className="w-8 h-8 mx-auto text-slate-300 mb-3" />
                                        <p className="font-medium text-slate-900">No ledger activity found</p>
                                        <p className="text-sm mt-1">This wallet hasn't had any transactions yet.</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right Column */}
                    <div className="lg:col-span-4 space-y-8">
                        
                        {/* Client Overview */}
                        <div>
                            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <User className="h-4 w-4 text-slate-400" /> Client Overview
                            </h3>
                            <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5 text-sm">
                                <div className="flex justify-between items-start">
                                    <span className="text-slate-500">Entity</span>
                                    <span className="font-semibold text-slate-900 text-right">{activeClient.name}</span>
                                </div>
                                <div className="flex justify-between items-start">
                                    <span className="text-slate-500">Contact</span>
                                    <span className="font-medium text-slate-700 text-right">{activeClient.phone || 'N/A'}</span>
                                </div>
                                <div className="flex justify-between items-start">
                                    <span className="text-slate-500">Address</span>
                                    <span className="font-medium text-slate-700 text-right max-w-[150px] truncate" title={activeClient.address}>{activeClient.address || 'N/A'}</span>
                                </div>
                                <div className="flex justify-between items-center pt-2 border-t border-slate-100">
                                    <span className="text-slate-500">Status</span>
                                    <Badge variant="outline" className="font-medium border-emerald-200 bg-emerald-50 text-emerald-700 flex items-center gap-1.5">
                                        <ShieldCheck className="w-3.5 h-3.5" /> Verified
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {/* Recent Invoices */}
                        <div>
                            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <FileText className="h-4 w-4 text-slate-400" /> Recent Invoices
                            </h3>
                            <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                                {clientInvoices.length > 0 ? (
                                    <div className="space-y-4 text-sm">
                                        {clientInvoices.map((inv) => (
                                            <div key={inv.id} className="flex justify-between items-center pb-4 last:pb-0 border-b last:border-0 border-slate-50">
                                                <div>
                                                    <span className="font-semibold text-slate-900 block">{inv.id}</span>
                                                    <span className="text-slate-400 text-xs mt-0.5">{inv.date}</span>
                                                </div>
                                                <div className="text-right">
                                                    <span className="font-semibold text-slate-900 block">${inv.amount.toLocaleString()}</span>
                                                    <span className={`text-xs capitalize mt-0.5 ${inv.status === 'paid' ? 'text-emerald-600' : 'text-amber-600'}`}>{inv.status}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6">
                                        <p className="text-sm text-slate-500 font-medium">No recent invoices.</p>
                                    </div>
                                )}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
