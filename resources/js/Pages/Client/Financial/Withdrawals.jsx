import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Wallet, ArrowUpRight, ShieldAlert, CreditCard } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import Modal from '@/Components/Modal';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Withdrawals({ auth, withdrawals, payoutMethods, wallet }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        payout_method_id: payoutMethods?.[0]?.id || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('financial.withdrawals.store'), {
            onSuccess: () => {
                setIsModalOpen(false);
                reset();
            },
        });
    };

    const maxAvailable = Number(wallet?.earned_balance || 0);

    return (
        <AuthenticatedLayout header="Request Withdrawal">
            <Head title={__('general.withdrawals')} />

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8">
                
                {/* Header Summary Card */}
                <Card className="shadow-none border-primary/20 bg-muted/10">
                    <CardContent className="p-8 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div className="space-y-2 text-center md:text-start">
                            <span className="text-sm font-semibold text-primary uppercase tracking-wider">{__('general.available_earned_funds')}</span>
                            <div className="text-4xl sm:text-5xl font-bold tracking-tight">
                                {formatMoney(maxAvailable, wallet?.currency)}
                             </div>
                            <p className="text-sm text-muted-foreground">{__('general.you_can_only_withdraw_funds_that_have_been_earned_on_the_platform')}</p>
                        </div>

                        <div className="flex flex-col sm:flex-row items-center gap-3">
                            {!auth.user.kyc_verified ? (
                                <Button asChild variant="secondary" className="h-12 px-6 shadow-none">
                                    <Link href={route('kyc.index')}>
                                        <ShieldAlert className="me-2 h-5 w-5 text-amber-600" /> Verify Identity (KYC)
                                    </Link>
                                </Button>
                            ) : (!payoutMethods || payoutMethods.length === 0) ? (
                                <Button asChild variant="secondary" className="h-12 px-6 shadow-none">
                                    <Link href={route('financial.payout-methods.index')}>
                                        <CreditCard className="me-2 h-5 w-5" />{__('general.setup_payout_method')}</Link>
                                </Button>
                            ) : (
                                <Button
                                    onClick={() => setIsModalOpen(true)}
                                    disabled={maxAvailable <= 0}
                                    className="h-12 px-6 shadow-none"
                                >
                                    <ArrowUpRight className="me-2 h-5 w-5" />{__('general.request_payout')}</Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Withdrawals Table */}
                <Card className="shadow-none">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-base font-semibold">{__('general.withdrawal_history')}</CardTitle>
                            <CardDescription>{__('general.view_your_past_withdrawal_requests_and_their_statuses')}</CardDescription>
                        </div>
                        <Badge variant="secondary" className="font-normal">{withdrawals?.total || 0} Requests</Badge>
                    </CardHeader>

                    <CardContent className="px-0 pt-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="ps-6">ID #</TableHead>
                                    <TableHead>{__('general.payout_method')}</TableHead>
                                    <TableHead>{__('general.amount')}</TableHead>
                                    <TableHead>{__('general.status')}</TableHead>
                                    <TableHead className="pe-6 text-end">{__('general.date')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(!withdrawals?.data || withdrawals.data.length === 0) ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center text-muted-foreground">{__('general.no_withdrawal_requests_found')}</TableCell>
                                    </TableRow>
                                ) : (
                                    withdrawals.data.map((w) => (
                                        <TableRow key={w.id}>
                                            <TableCell className="ps-6 font-medium">#{w.id}</TableCell>
                                            <TableCell className="capitalize">
                                                {w.payout_method ? w.payout_method.type.replace('_', ' ') : 'Standard Method'}
                                            </TableCell>
                                            <TableCell className="font-semibold">
                                                {formatMoney(w.amount, w.currency)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={
                                                    w.status === 'paid' ? 'default' :
                                                    w.status === 'approved' ? 'secondary' :
                                                    w.status === 'rejected' ? 'destructive' : 'outline'
                                                } className="capitalize font-normal tracking-wide">
                                                    {w.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="pe-6 text-end text-muted-foreground text-xs">
                                                {new Date(w.created_at).toLocaleDateString()}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Request Payout Modal */}
                <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)} maxWidth="md">
                    <div className="p-6 space-y-6">
                        <div className="flex items-center gap-2">
                            <Wallet className="h-5 w-5 text-primary" />
                            <h2 className="text-xl font-semibold tracking-tight">{__('general.request_withdrawal')}</h2>
                        </div>
                        
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="amount">Amount to Withdraw (Max: {formatMoney(maxAvailable, wallet?.currency)})</Label>
                                <div className="relative">
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max={maxAvailable}
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className="pe-16 shadow-none font-medium"
                                        placeholder="0.00"
                                        required
                                    />
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => setData('amount', maxAvailable.toString())}
                                        className="absolute end-1 top-1/2 -translate-y-1/2 h-7 px-2 text-xs shadow-none"
                                    >
                                        {__('general.max')}</Button>
                                </div>
                                {errors.amount && <p className="text-sm text-destructive">{errors.amount}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="payout_method">{__('general.destination_payout_method')}</Label>
                                <select
                                    id="payout_method"
                                    value={data.payout_method_id}
                                    onChange={(e) => setData('payout_method_id', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    required
                                >
                                    {payoutMethods?.map((pm) => (
                                        <option key={pm.id} value={pm.id}>
                                            {pm.type.replace('_', ' ').toUpperCase()} - {pm.details?.bank_name || pm.details?.paypal_email || pm.details?.wallet_address || 'Account'}
                                        </option>
                                    ))}
                                </select>
                                {errors.payout_method_id && <p className="text-sm text-destructive">{errors.payout_method_id}</p>}
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t">
                                <Button type="button" variant="ghost" onClick={() => setIsModalOpen(false)}>{__('general.cancel')}</Button>
                                <Button type="submit" disabled={processing} className="shadow-none">{__('general.confirm_withdrawal')}</Button>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}

