import React from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';
import { AlertCircle, Wallet, CreditCard, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PayAllModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    totalOwed: number;
    walletBalance: number;
    currency: string;
}

export function PayAllModal({ open, onOpenChange, totalOwed, walletBalance, currency }: PayAllModalProps) {
    const { post, processing } = useForm({});

    const diff = totalOwed - walletBalance;
    const requiresKashier = diff > 0;
    const amountToPayViaKashier = requiresKashier ? diff : 0;
    const amountFromWallet = requiresKashier ? walletBalance : totalOwed;

    const handleConfirm = () => {
        post(route('billing.invoices.pay-all'), {
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{__('general.settle_account_balance')}</DialogTitle>
                    <DialogDescription>
                        {__('general.review_your_payment_details_before_confirming')}
                    </DialogDescription>
                </DialogHeader>

                <div className="py-4 space-y-6">
                    {/* Summary Card */}
                    <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <div className="flex justify-between items-center mb-3">
                            <span className="text-sm font-medium text-slate-500">{__('general.total_outstanding')}</span>
                            <CurrencyDisplay amount={totalOwed} currency={currency} className="font-bold text-slate-900" />
                        </div>
                        <div className="flex justify-between items-center mb-3 pb-3 border-b border-slate-200">
                            <span className="text-sm font-medium text-slate-500">{__('general.wallet_balance')}</span>
                            <CurrencyDisplay amount={walletBalance} currency={currency} className="font-medium text-slate-700" />
                        </div>
                        
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-bold text-slate-900">
                                {requiresKashier ? __('general.remaining_due') : __('general.new_wallet_balance')}
                            </span>
                            <CurrencyDisplay 
                                amount={Math.abs(diff)} 
                                currency={currency} 
                                className={cn("font-bold text-lg", requiresKashier ? "text-rose-600" : "text-emerald-600")} 
                            />
                        </div>
                    </div>

                    {/* Payment Method Explanation */}
                    <div className="space-y-3">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">{__('general.payment_method')}</h4>
                        
                        {amountFromWallet > 0 && (
                            <div className="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-white">
                                <div className="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Wallet className="w-4 h-4 text-emerald-700" />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-slate-900">{__('general.deduct_from_wallet')}</p>
                                </div>
                                <CurrencyDisplay amount={amountFromWallet} currency={currency} className="font-bold text-sm text-slate-900" />
                            </div>
                        )}

                        {requiresKashier && (
                            <div className="flex items-center gap-3 p-3 rounded-lg border border-indigo-100 bg-indigo-50">
                                <div className="w-8 h-8 rounded-full bg-indigo-200 flex items-center justify-center shrink-0">
                                    <CreditCard className="w-4 h-4 text-indigo-700" />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-indigo-900">{__('general.pay_via_kashier')}</p>
                                    <p className="text-xs text-indigo-700">{__('general.redirecting_to_secure_checkout')}</p>
                                </div>
                                <CurrencyDisplay amount={amountToPayViaKashier} currency={currency} className="font-bold text-sm text-indigo-900" />
                            </div>
                        )}
                    </div>

                    {requiresKashier && (
                        <div className="flex gap-2 items-start text-xs text-amber-700 bg-amber-50 p-3 rounded-lg border border-amber-200">
                            <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                            <p>{__('general.insufficient_wallet_balance_kashier_warning')}</p>
                        </div>
                    )}
                </div>

                <DialogFooter className="gap-2 sm:gap-0">
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>
                        {__('general.cancel')}
                    </Button>
                    <Button 
                        onClick={handleConfirm} 
                        disabled={processing}
                        className={cn(requiresKashier ? "bg-indigo-600 hover:bg-indigo-700" : "bg-slate-900 hover:bg-slate-800")}
                    >
                        {processing && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
                        {requiresKashier ? __('general.proceed_to_payment') : __('general.confirm_payment')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
