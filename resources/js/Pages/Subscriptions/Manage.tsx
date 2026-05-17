import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Ban, Calendar, Clock, CreditCard, Receipt, Wallet, Layers, ShieldAlert } from 'lucide-react';

interface Subscription {
    id: number;
    module: string;
    plan_name: string;
    price: number;
    billing: string;
    status: string;
    started_at: string;
    expires_at: string;
    auto_renew: boolean;
}

interface Invoice {
    id: number;
    invoice_number: string;
    plan_name: string;
    module: string;
    amount: number;
    currency: string;
    status: string;
    payment_method: string;
    paid_at: string;
}

interface ManageProps {
    subscriptions: Subscription[];
    invoices: Invoice[];
    walletBalance: number;
    currency: string;
}

export default function Manage({ subscriptions, invoices, walletBalance, currency }: ManageProps) {
    
    const formatMoney = (amount: number, customCurr?: string) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: customCurr || currency
        }).format(amount);
    };

    const handleCancel = (subId: number) => {
        if (confirm("Are you sure you want to cancel the auto-renewal for this subscription? You will retain access until the end of the billing cycle.")) {
            router.post(route('subscriptions.cancel'), { id: subId });
        }
    };

    const handleRenew = (subId: number, price: number) => {
        if (walletBalance < price) {
            alert(`Insufficient wallet balance to renew. Price is ${formatMoney(price)}. Please add funds first.`);
            return;
        }
        if (confirm(`Renew subscription for ${formatMoney(price)} using your wallet balance?`)) {
            router.post(route('subscriptions.renew'), { id: subId });
        }
    };

    return (
        <AuthenticatedLayout header="Manage Subscriptions">
            <Head title="My Subscriptions" />

            <div className="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                {/* Header Row */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 className="text-3xl font-extrabold tracking-tight text-foreground">My Subscriptions</h2>
                        <p className="text-sm text-muted-foreground mt-1">Manage renewals, cycles, billing, and platform module access.</p>
                    </div>
                    <Link href={route('subscriptions.plans')}>
                        <Button className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                            Explore Pricing Plans
                        </Button>
                    </Link>
                </div>

                {/* Grid Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Active subs count */}
                    <Card className="shadow-none border bg-card">
                        <CardContent className="p-6 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Active Modules</span>
                                <h3 className="text-3xl font-extrabold tracking-tight">
                                    {subscriptions.filter(s => s.status === 'active').length}
                                </h3>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl">
                                <Layers className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Wallet balance */}
                    <Card className="shadow-none border bg-card">
                        <CardContent className="p-6 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Wallet Balance</span>
                                <h3 className="text-3xl font-extrabold tracking-tight text-indigo-600 dark:text-indigo-400">
                                    {formatMoney(walletBalance)}
                                </h3>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl">
                                <Wallet className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice spent */}
                    <Card className="shadow-none border bg-card">
                        <CardContent className="p-6 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Invoices paid</span>
                                <h3 className="text-3xl font-extrabold tracking-tight">
                                    {invoices.filter(i => i.status === 'paid').length} Invoices
                                </h3>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl">
                                <Receipt className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Subscriptions Section */}
                <div className="space-y-6">
                    <h3 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        <Layers className="h-5 w-5 text-indigo-500" /> Subscription Access Layer
                    </h3>
                    
                    {subscriptions.length === 0 ? (
                        <Card className="shadow-none border bg-card text-center p-12">
                            <div className="max-w-md mx-auto space-y-4">
                                <div className="mx-auto w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center">
                                    <Clock className="h-6 w-6 text-muted-foreground" />
                                </div>
                                <h4 className="text-lg font-bold text-foreground">No active subscriptions found</h4>
                                <p className="text-sm text-muted-foreground">You do not currently have any paid SaaS module subscriptions enabled. Unlock features in a single click.</p>
                                <Link href={route('subscriptions.plans')}>
                                    <Button className="shadow-none mt-2">Explore pricing plans</Button>
                                </Link>
                            </div>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {subscriptions.map((sub) => {
                                const isActive = sub.status === 'active';
                                const isCancelled = sub.status === 'cancelled';
                                const isExpired = sub.status === 'expired';

                                return (
                                    <Card key={sub.id} className={`shadow-none border bg-card relative ${isActive ? 'border-l-4 border-l-emerald-500' : isCancelled ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-destructive'}`}>
                                        <div className="p-6 space-y-5">
                                            <div className="flex justify-between items-start">
                                                <div className="space-y-1">
                                                    <span className="text-[10px] uppercase font-bold text-muted-foreground bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded">
                                                        {sub.module} MODULE
                                                    </span>
                                                    <h4 className="text-lg font-extrabold tracking-tight mt-1">{sub.plan_name}</h4>
                                                    <p className="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                                        {formatMoney(sub.price)} /{sub.billing === 'yearly' ? 'year' : 'month'}
                                                    </p>
                                                </div>

                                                <div>
                                                    {isActive && (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/50">
                                                            Active
                                                        </span>
                                                    )}
                                                    {isCancelled && (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/50">
                                                            Pending Expiry
                                                        </span>
                                                    )}
                                                    {isExpired && (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200/50">
                                                            Expired
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 text-xs font-medium text-muted-foreground border-t border-b py-3">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4 text-slate-400" />
                                                    <span>Started: {sub.started_at}</span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Clock className="h-4 w-4 text-slate-400" />
                                                    <span>Expires: {sub.expires_at}</span>
                                                </div>
                                            </div>

                                            <div className="flex justify-end gap-3 pt-1">
                                                {isActive && (
                                                    <Button
                                                        onClick={() => handleCancel(sub.id)}
                                                        variant="outline"
                                                        size="sm"
                                                        className="shadow-none text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/20 gap-1.5 border-slate-200 dark:border-slate-800"
                                                    >
                                                        <Ban className="h-3.5 w-3.5" /> Cancel Auto-Renew
                                                    </Button>
                                                )}

                                                {(isCancelled || isExpired) && (
                                                    <Button
                                                        onClick={() => handleRenew(sub.id, sub.price)}
                                                        size="sm"
                                                        className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold gap-1.5"
                                                    >
                                                        <RefreshCw className="h-3.5 w-3.5" /> Renew via Wallet
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Platform Invoices History */}
                <div className="space-y-6">
                    <h3 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        <Receipt className="h-5 w-5 text-indigo-500" /> Platform Billing History
                    </h3>

                    <Card className="shadow-none border bg-card overflow-hidden">
                        {invoices.length === 0 ? (
                            <div className="p-12 text-center text-muted-foreground text-sm font-medium">
                                No invoice history found.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                            <th className="px-6 py-4">Invoice #</th>
                                            <th className="px-6 py-4">Module</th>
                                            <th className="px-6 py-4">Plan Description</th>
                                            <th className="px-6 py-4">Amount Paid</th>
                                            <th className="px-6 py-4">Payment Method</th>
                                            <th className="px-6 py-4">Date</th>
                                            <th className="px-6 py-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y text-sm">
                                        {invoices.map((inv) => (
                                            <tr key={inv.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-all font-medium text-foreground">
                                                <td className="px-6 py-4 font-bold text-slate-800 dark:text-slate-200">
                                                    {inv.invoice_number}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-[10px] bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded font-semibold text-muted-foreground">
                                                        {inv.module}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-xs font-semibold">
                                                    {inv.plan_name}
                                                </td>
                                                <td className="px-6 py-4 font-bold">
                                                    {formatMoney(inv.amount, inv.currency)}
                                                </td>
                                                <td className="px-6 py-4 text-xs">
                                                    {inv.payment_method}
                                                </td>
                                                <td className="px-6 py-4 text-xs text-muted-foreground">
                                                    {inv.paid_at}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                                                        {inv.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
