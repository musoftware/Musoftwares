import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { CheckCircle2, Wallet, CreditCard, ShieldCheck, ArrowRight, Clock } from 'lucide-react';
import { format } from 'date-fns';
import { __ } from '@/lib/i18n';
import ThemeToggle from '@/Components/ThemeToggle';

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
        <div className="min-h-screen bg-background text-foreground flex flex-col items-center justify-center p-4 py-12 selection:bg-primary selection:text-white transition-colors duration-200">
            <Head title={__('general.complete_your_booking')} />
            
            <div className="max-w-3xl w-full">
                <div className="flex justify-end mb-4">
                    <ThemeToggle className="h-9 w-9" />
                </div>
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-semibold text-foreground tracking-tight mb-2">{__('general.complete_your_booking')}</h1>
                    <p className="text-muted-foreground">{__('general.you_re_almost_there_secure_your_session_by_completing_payment')}</p>
                </div>

                <div className="grid md:grid-cols-5 gap-6">
                    {/* Left Col - Payment Form */}
                    <div className="md:col-span-3 space-y-6">
                        <Card className="border-border shadow-sm rounded-2xl overflow-hidden bg-card text-card-foreground">
                            <CardHeader className="bg-muted/20 border-b border-border">
                                <CardTitle className="text-lg text-foreground">{__('general.payment_method')}</CardTitle>
                                <CardDescription className="text-muted-foreground">{__('general.select_how_you_d_like_to_pay_for_this_session')}</CardDescription>
                            </CardHeader>
                            <CardContent className="p-6 space-y-4">
                                {/* Credit Card Option */}
                                <div 
                                    className={`relative flex cursor-pointer rounded-xl border p-4 transition-all ${
                                        paymentMethod === 'card' 
                                            ? 'border-primary bg-primary/5 shadow-sm' 
                                            : 'border-border hover:border-primary/50 bg-card'
                                    }`}
                                    onClick={() => setPaymentMethod('card')}
                                >
                                    <div className="flex w-full items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded-full ${paymentMethod === 'card' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                                                <CreditCard className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-foreground">{__('general.credit_or_debit_card')}</p>
                                                <p className="text-sm text-muted-foreground">{__('general.powered_securely_by_kashier')}</p>
                                            </div>
                                        </div>
                                        <div className={`h-5 w-5 rounded-full border flex items-center justify-center ${paymentMethod === 'card' ? 'border-primary' : 'border-border'}`}>
                                            {paymentMethod === 'card' && <div className="h-2.5 w-2.5 rounded-full bg-primary" />}
                                        </div>
                                    </div>
                                </div>

                                {/* Wallet Option */}
                                <div 
                                    className={`relative flex rounded-xl border p-4 transition-all ${
                                        paymentMethod === 'wallet' 
                                            ? 'border-primary bg-primary/5 shadow-sm' 
                                            : 'border-border bg-card'
                                    } ${!hasEnoughBalance ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:border-primary/50'}`}
                                    onClick={() => hasEnoughBalance && setPaymentMethod('wallet')}
                                >
                                    <div className="flex w-full items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded-full ${paymentMethod === 'wallet' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                                                <Wallet className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium text-foreground">{__('general.pay_with_wallet_balance')}</p>
                                                    {!hasEnoughBalance && (
                                                        <span className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400">{__('general.insufficient')}</span>
                                                    )}
                                                </div>
                                                <p className="text-sm text-muted-foreground">Available: {walletBalance.toFixed(2)} {booking.currency}</p>
                                            </div>
                                        </div>
                                        <div className={`h-5 w-5 rounded-full border flex items-center justify-center ${paymentMethod === 'wallet' ? 'border-primary' : 'border-border'}`}>
                                            {paymentMethod === 'wallet' && <div className="h-2.5 w-2.5 rounded-full bg-primary" />}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <form onSubmit={handlePayment}>
                            <Button 
                                type="submit" 
                                className="w-full rounded-xl h-14 text-lg font-medium shadow-sm hover:shadow-md transition-all bg-primary hover:bg-primary/90 text-primary-foreground"
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
                        
                        <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                            <ShieldCheck className="h-4 w-4" />
                            <span>{__('general.payments_are_secure_and_encrypted')}</span>
                        </div>
                    </div>

                    {/* Right Col - Order Summary */}
                    <div className="md:col-span-2 space-y-6">
                        <Card className="border-border shadow-sm rounded-2xl bg-card text-card-foreground">
                            <CardHeader className="pb-4 border-b border-border bg-muted/20">
                                <CardTitle className="text-lg text-foreground">{__('general.booking_summary')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-6 space-y-6">
                                <div>
                                    <h3 className="font-medium text-foreground mb-1">{eventType.title}</h3>
                                    <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                                        <Clock className="h-3.5 w-3.5" />
                                        {eventType.duration_minutes} minutes
                                    </p>
                                </div>
                                
                                <div className="space-y-3 pt-4 border-t border-border">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{__('general.date')}</span>
                                        <span className="font-medium text-foreground">{format(new Date(booking.starts_at), 'MMM d, yyyy')}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{__('general.time')}</span>
                                        <span className="font-medium text-foreground">{format(new Date(booking.starts_at), 'h:mm a')}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{__('general.guest')}</span>
                                        <span className="font-medium text-foreground">{booking.guest_name}</span>
                                    </div>
                                </div>
                                
                                <div className="pt-4 border-t border-border flex justify-between items-center">
                                    <span className="font-medium text-foreground">{__('general.total')}</span>
                                    <span className="text-xl font-semibold text-foreground">{booking.price} {booking.currency}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    );
}
