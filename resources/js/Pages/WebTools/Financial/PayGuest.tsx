import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { CreditCard, Wallet, User, Mail, Phone, ArrowRight, ShieldCheck } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';

interface Currency {
    id: number;
    currency: string;
}

interface PayGuestProps {
    payGuestCurrencies: Currency[];
}

export default function PayGuest({ payGuestCurrencies = [] }: PayGuestProps) {
    const { data, setData, post, processing, errors } = useForm({
        amount: '',
        currency_id: payGuestCurrencies.length > 0 ? payGuestCurrencies[0].id.toString() : '',
        name: '',
        email: '',
        mobile: '',
        referral: ''
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tools.pay-guest.signup'));
    };

    return (
        <ToolsPublicLayout title="Pay as Guest" activeNav="explore">
            <div className="max-w-4xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8 text-center">
                    <div className="inline-flex items-center justify-center p-3 bg-indigo-100 rounded-2xl text-indigo-600 mb-4">
                        <CreditCard className="w-8 h-8" />
                    </div>
                    <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
                        Pay as Guest
                    </h1>
                    <p className="text-lg text-slate-600 max-w-2xl mx-auto">
                        Create an account instantly and receive your login credentials on WhatsApp. Log in to complete your secure payment.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-2">
                        <Card className="border-slate-200 shadow-sm border-t-4 border-t-indigo-500 overflow-hidden">
                            <div className="bg-indigo-50/50 p-6 border-b border-slate-100 flex items-start gap-4">
                                <ShieldCheck className="w-6 h-6 text-indigo-500 mt-1 flex-shrink-0" />
                                <div>
                                    <h3 className="font-bold text-slate-900">Secure Guest Checkout</h3>
                                    <p className="text-sm text-slate-600 mt-1">
                                        Your account will be created automatically. We'll send your password directly to your WhatsApp for security. No need to memorize a password right now.
                                    </p>
                                </div>
                            </div>
                            
                            <CardContent className="p-6">
                                {payGuestCurrencies.length === 0 ? (
                                    <Alert variant="destructive">
                                        <AlertTitle>Error</AlertTitle>
                                        <AlertDescription>Payment signup is temporarily unavailable. Please contact support.</AlertDescription>
                                    </Alert>
                                ) : (
                                    <form onSubmit={submit} className="space-y-6">
                                        <div className="space-y-2">
                                            <Label className="text-slate-700 font-bold flex items-center gap-2 text-base">
                                                <Wallet className="w-4 h-4 text-slate-500" /> Amount to Pay
                                            </Label>
                                            <div className="flex relative">
                                                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <span className="text-slate-500 font-bold">$</span>
                                                </div>
                                                <Input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0.01"
                                                    required
                                                    value={data.amount}
                                                    onChange={e => setData('amount', e.target.value)}
                                                    className={`pl-10 h-14 text-xl font-bold rounded-r-none focus-visible:z-10 ${errors.amount ? 'border-red-500' : ''}`}
                                                    placeholder="e.g. 1000"
                                                />
                                                <Select 
                                                    value={data.currency_id} 
                                                    onValueChange={val => setData('currency_id', val || '')}
                                                >
                                                    <SelectTrigger className={`w-[120px] h-14 rounded-l-none border-l-0 bg-slate-50 font-bold ${errors.currency_id ? 'border-red-500' : ''}`}>
                                                        <SelectValue placeholder="Currency" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {payGuestCurrencies.map(cur => (
                                                            <SelectItem key={cur.id} value={cur.id.toString()}>{cur.currency}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            {errors.amount && <p className="text-sm text-red-500">{errors.amount}</p>}
                                            {errors.currency_id && <p className="text-sm text-red-500">{errors.currency_id}</p>}
                                        </div>

                                        <div className="space-y-4 pt-4 border-t border-slate-100">
                                            <div className="space-y-2">
                                                <Label className="text-slate-700 font-bold">Full Name</Label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <User className="w-5 h-5 text-slate-400" />
                                                    </div>
                                                    <Input 
                                                        type="text" 
                                                        required 
                                                        value={data.name}
                                                        onChange={e => setData('name', e.target.value)}
                                                        className={`pl-10 h-12 bg-slate-50 ${errors.name ? 'border-red-500' : ''}`}
                                                        placeholder="Your full name"
                                                    />
                                                </div>
                                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label className="text-slate-700 font-bold">Email Address</Label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <Mail className="w-5 h-5 text-slate-400" />
                                                    </div>
                                                    <Input 
                                                        type="email" 
                                                        required 
                                                        value={data.email}
                                                        onChange={e => setData('email', e.target.value)}
                                                        className={`pl-10 h-12 bg-slate-50 ${errors.email ? 'border-red-500' : ''}`}
                                                        placeholder="name@example.com"
                                                    />
                                                </div>
                                                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label className="text-slate-700 font-bold">WhatsApp Number</Label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <Phone className="w-5 h-5 text-slate-400" />
                                                    </div>
                                                    <Input 
                                                        type="text" 
                                                        required 
                                                        value={data.mobile}
                                                        onChange={e => setData('mobile', e.target.value)}
                                                        className={`pl-10 h-12 bg-slate-50 ${errors.mobile ? 'border-red-500' : ''}`}
                                                        placeholder="+1234567890"
                                                    />
                                                </div>
                                                <p className="text-xs text-slate-500">We will send your login credentials here.</p>
                                                {errors.mobile && <p className="text-sm text-red-500">{errors.mobile}</p>}
                                            </div>
                                        </div>

                                        <Button type="submit" size="lg" className="w-full h-14 text-lg mt-6" disabled={processing}>
                                            {processing ? 'Processing...' : 'Continue to Payment'} <ArrowRight className="w-5 h-5 ml-2" />
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="md:col-span-1 space-y-6">
                        <Card className="border-none shadow-sm bg-slate-50">
                            <CardContent className="p-6">
                                <h3 className="font-bold text-slate-900 mb-4">How it works</h3>
                                <ol className="space-y-4 text-sm text-slate-600 relative border-l border-slate-200 ml-3 pl-4">
                                    <li className="relative">
                                        <div className="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-indigo-500 border-2 border-white"></div>
                                        <strong className="text-slate-900 block mb-1">1. Fill Details</strong>
                                        Enter your amount and contact info.
                                    </li>
                                    <li className="relative">
                                        <div className="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-indigo-500 border-2 border-white"></div>
                                        <strong className="text-slate-900 block mb-1">2. Get Credentials</strong>
                                        Receive an instant WhatsApp message with your secure password.
                                    </li>
                                    <li className="relative">
                                        <div className="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-indigo-500 border-2 border-white"></div>
                                        <strong className="text-slate-900 block mb-1">3. Pay Securely</strong>
                                        You'll be redirected to log in and complete your payment via Instapay or Card.
                                    </li>
                                </ol>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
