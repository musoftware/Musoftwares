import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { CheckCircle2, Wallet, CreditCard, ShieldCheck, ArrowRight, Clock } from 'lucide-react';
import { format } from 'date-fns';
import { __ } from '@/lib/i18n';

export default function Checkout({ booking, walletBalance }: any) {
    const { eventType } = booking;
    const [paymentMethod, setPaymentMethod] = useState<'wallet' | 'card'>('card');
    
    const { post: payWithWallet, processing: processingWallet } = useForm();
    const { post: payWithCard, processing: processingCard } = useForm();

    const hasEnoughBalance = walletBalance >= booking.price;

    const handlePayment = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (paymentMethod === 'wallet' && hasEnoughBalance) {
            payWithWallet(route('booking.pay.wallet', booking.id));
        } else {
            payWithCard(route('booking.pay.kashier', booking.id));
        }
    };

    const isProcessing = processingWallet || processingCard;

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4 py-12 selection:bg-slate-200">
            <Head title={__('general.complete_your_booking')} />
            
            <div className="max-w-3xl w-full">
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-semibold text-slate-900 tracking-tight mb-2">{__('general.complete_your_booking')}</h1>
                    <p className="text-slate-500">{__('general.you_re_almost_there_secure_your_session_by_completing_payment')}</p>
                </div>

                <div className="grid md:grid-cols-5 gap-6">
                    {/* Left Col - Payment Form */}
                    <div className="md:col-span-3 space-y-6">
                        <Card className="border-slate-100 shadow-sm rounded-2xl overflow-hidden">
                            <CardHeader className="bg-white border-b border-slate-50">
                                <CardTitle className="text-lg">{__('general.payment_method')}</CardTitle>
                                <CardDescription>{__('general.select_how_you_d_like_to_pay_for_this_session')}</CardDescription>
                            </CardHeader>
                            <CardContent className="p-6 space-y-4">
                                {/* Credit Card Option */}
                                <div 
                                    className={`relative flex cursor-pointer rounded-xl border p-4 transition-all ${
                                        paymentMethod === 'card' 
                                            ? 'border-slate-900 bg-slate-50/50 shadow-sm' 
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    }`}
                                    onClick={() => setPaymentMethod('card')}
                                >
                                    <div className="flex w-full items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded-full ${paymentMethod === 'card' ? 'bg-white shadow-sm' : 'bg-slate-50'}`}>
                                                <CreditCard className="h-5 w-5 text-slate-700" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-slate-900">{__('general.credit_or_debit_card')}</p>
                                                <p className="text-sm text-slate-500">{__('general.powered_securely_by_kashier')}</p>
                                            </div>
                                        </div>
                                        <div className={`h-5 w-5 rounded-full border flex items-center justify-center ${paymentMethod === 'card' ? 'border-slate-900' : 'border-slate-300'}`}>
                                            {paymentMethod === 'card' && <div className="h-2.5 w-2.5 rounded-full bg-slate-900" />}
                                        </div>
                                    </div>
                                </div>

                                {/* Wallet Option */}
                                <div 
                                    className={`relative flex rounded-xl border p-4 transition-all ${
                                        paymentMethod === 'wallet' 
                                            ? 'border-slate-900 bg-slate-50/50 shadow-sm' 
                                            : 'border-slate-200 bg-white'
                                    } ${!hasEnoughBalance ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:border-slate-300'}`}
                                    onClick={() => hasEnoughBalance && setPaymentMethod('wallet')}
                                >
                                    <div className="flex w-full items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded-full ${paymentMethod === 'wallet' ? 'bg-white shadow-sm' : 'bg-slate-50'}`}>
                                                <Wallet className="h-5 w-5 text-slate-700" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium text-slate-900">{__('general.pay_with_wallet_balance')}</p>
                                                    {!hasEnoughBalance && (
                                                        <span className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{__('general.insufficient')}</span>
                                                    )}
                                                </div>
                                                <p className="text-sm text-slate-500">Available: {walletBalance.toFixed(2)} {booking.currency}</p>
                                            </div>
                                        </div>
                                        <div className={`h-5 w-5 rounded-full border flex items-center justify-center ${paymentMethod === 'wallet' ? 'border-slate-900' : 'border-slate-300'}`}>
                                            {paymentMethod === 'wallet' && <div className="h-2.5 w-2.5 rounded-full bg-slate-900" />}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <form onSubmit={handlePayment}>
                            <Button 
                                type="submit" 
                                className="w-full rounded-xl h-14 text-lg font-medium shadow-sm hover:shadow-md transition-all"
                                disabled={isProcessing}
                            >
                                {isProcessing ? (
                                    <span className="flex items-center gap-2">{__('general.processing_securely')}</span>
                                ) : (
                                    <span className="flex items-center gap-2">
                                        Pay {booking.price} {booking.currency} <ArrowRight className="h-5 w-5" />
                                    </span>
                                )}
                            </Button>
                        </form>
                        
                        <div className="flex items-center justify-center gap-2 text-sm text-slate-500">
                            <ShieldCheck className="h-4 w-4" />
                            <span>{__('general.payments_are_secure_and_encrypted')}</span>
                        </div>
                    </div>

                    {/* Right Col - Order Summary */}
                    <div className="md:col-span-2 space-y-6">
                        <Card className="border-slate-100 shadow-sm rounded-2xl bg-white">
                            <CardHeader className="pb-4 border-b border-slate-50">
                                <CardTitle className="text-lg">{__('general.booking_summary')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-6 space-y-6">
                                <div>
                                    <h3 className="font-medium text-slate-900 mb-1">{eventType.title}</h3>
                                    <p className="text-sm text-slate-500 flex items-center gap-1.5">
                                        <Clock className="h-3.5 w-3.5" />
                                        {eventType.duration_minutes} minutes
                                    </p>
                                </div>
                                
                                <div className="space-y-3 pt-4 border-t border-slate-100">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">{__('general.date')}</span>
                                        <span className="font-medium text-slate-900">{format(new Date(booking.starts_at), 'MMM d, yyyy')}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">{__('general.time')}</span>
                                        <span className="font-medium text-slate-900">{format(new Date(booking.starts_at), 'h:mm a')}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">{__('general.guest')}</span>
                                        <span className="font-medium text-slate-900">{booking.guest_name}</span>
                                    </div>
                                </div>
                                
                                <div className="pt-4 border-t border-slate-100 flex justify-between items-center">
                                    <span className="font-medium text-slate-900">{__('general.total')}</span>
                                    <span className="text-xl font-semibold text-slate-900">{booking.price} {booking.currency}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    );
}
