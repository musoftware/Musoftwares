import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Wallet, Smartphone, ShieldCheck, Mail, User, Phone, ArrowRight, DollarSign, Calculator, Settings, Send } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { __ } from '@/lib/i18n';

interface Currency {
    id: number;
    currency: string;
}

interface WithdrawInstapayProps {
    input?: number | null;
    result?: number | null;
    amount_to_pay?: number | null;
    calculation_type: string;
    ninety_percent?: number | null;
    withdrawInstapayCurrencies: Currency[];
}

export default function WithdrawInstapay({
    input,
    result,
    amount_to_pay,
    calculation_type,
    ninety_percent,
    withdrawInstapayCurrencies
}: WithdrawInstapayProps) {
    const { auth, flash } = usePage<any>().props;
    const isGuest = !auth?.user;

    const signupForm = useForm({
        amount: '',
        currency_id: withdrawInstapayCurrencies.length > 0 ? withdrawInstapayCurrencies.find(c => c.currency === 'EGP')?.id?.toString() || withdrawInstapayCurrencies[0].id.toString() : '',
        name: '',
        email: '',
        mobile: '',
        referral: ''
    });

    const calcForm = useForm({
        balance_egp: input || '',
        calculation_type: calculation_type || 'visa_master'
    });

    const formatMoney = (val: number) => val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const handleSignup = (e: React.FormEvent) => {
        e.preventDefault();
        signupForm.post(route('tools.withdraw-instapay.signup'));
    };

    const handleCalc = (e: React.FormEvent) => {
        e.preventDefault();
        calcForm.post(route('tools.withdraw-instapay'));
    };

    return (
        <WebToolsLayout title={__('general.instapay_vodafone_cash')} activeNav="explore">
            <div className="max-w-5xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8 text-center">
                    <div className="inline-flex items-center justify-center p-3 bg-purple-100 rounded-2xl text-purple-600 mb-4">
                        <Send className="w-8 h-8" />
                    </div>
                    <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
                        {__('general.instapay_vodafone_cash')}</h1>
                    <p className="text-lg text-slate-600 max-w-2xl mx-auto">
                        {isGuest 
                            ? "Enter your details to create an account and receive login credentials on WhatsApp. Then log in to complete payment."
                            : "Calculate transfer fees and process payments between InstaPay and mobile wallets."
                        }
                    </p>
                </div>

                {isGuest ? (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                        <div className="md:col-span-2">
                            <Card className="border-slate-200 shadow-sm border-t-4 border-t-purple-500 overflow-hidden">
                                <div className="bg-purple-50/50 p-6 border-b border-slate-100 flex items-start gap-4">
                                    <ShieldCheck className="w-6 h-6 text-purple-500 mt-1 flex-shrink-0" />
                                    <div>
                                        <h3 className="font-bold text-slate-900">{__('general.pay_with_instapay')}</h3>
                                        <p className="text-sm text-slate-600 mt-1">
                                            {__('general.account_will_be_created_and_login_creden')}</p>
                                    </div>
                                </div>
                                
                                <CardContent className="p-6">
                                    {withdrawInstapayCurrencies.length === 0 ? (
                                        <Alert variant="destructive">
                                            <AlertTitle>{__('general.error')}</AlertTitle>
                                            <AlertDescription>{__('general.payment_signup_is_temporarily_unavailabl')}</AlertDescription>
                                        </Alert>
                                    ) : (
                                        <form onSubmit={handleSignup} className="space-y-6">
                                            <div className="space-y-2">
                                                <Label className="text-slate-700 font-bold flex items-center gap-2 text-base">
                                                    <Wallet className="w-4 h-4 text-slate-500" /> {__('general.amount_to_pay')}</Label>
                                                <div className="flex relative">
                                                    <div className="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none">
                                                        <span className="text-slate-500 font-bold">$</span>
                                                    </div>
                                                    <Input 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0.01"
                                                        required
                                                        value={signupForm.data.amount}
                                                        onChange={e => signupForm.setData('amount', e.target.value)}
                                                        className={`ps-10 h-14 text-xl font-bold rounded-e-none focus-visible:z-10 ${signupForm.errors.amount ? 'border-red-500' : ''}`}
                                                        placeholder="e.g. 1000"
                                                    />
                                                    <Select 
                                                        value={signupForm.data.currency_id} 
                                                        onValueChange={val => signupForm.setData('currency_id', val || '')}
                                                    >
                                                        <SelectTrigger className={`w-[120px] h-14 rounded-s-none border-s-0 bg-slate-50 font-bold ${signupForm.errors.currency_id ? 'border-red-500' : ''}`}>
                                                            <SelectValue placeholder={__('general.currency')} />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {withdrawInstapayCurrencies.map(cur => (
                                                                <SelectItem key={cur.id} value={cur.id.toString()}>{cur.currency}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                {signupForm.errors.amount && <p className="text-sm text-red-500">{signupForm.errors.amount}</p>}
                                                {signupForm.errors.currency_id && <p className="text-sm text-red-500">{signupForm.errors.currency_id}</p>}
                                            </div>

                                            <div className="space-y-4 pt-4 border-t border-slate-100">
                                                <div className="space-y-2">
                                                    <Label className="text-slate-700 font-bold">{__('general.full_name')}</Label>
                                                    <div className="relative">
                                                        <div className="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                                            <User className="w-5 h-5 text-slate-400" />
                                                        </div>
                                                        <Input 
                                                            type="text" 
                                                            required 
                                                            value={signupForm.data.name}
                                                            onChange={e => signupForm.setData('name', e.target.value)}
                                                            className={`ps-10 h-12 bg-slate-50 ${signupForm.errors.name ? 'border-red-500' : ''}`}
                                                            placeholder={__('general.your_full_name')}
                                                        />
                                                    </div>
                                                    {signupForm.errors.name && <p className="text-sm text-red-500">{signupForm.errors.name}</p>}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label className="text-slate-700 font-bold">{__('general.email_address')}</Label>
                                                    <div className="relative">
                                                        <div className="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                                            <Mail className="w-5 h-5 text-slate-400" />
                                                        </div>
                                                        <Input 
                                                            type="email" 
                                                            required 
                                                            value={signupForm.data.email}
                                                            onChange={e => signupForm.setData('email', e.target.value)}
                                                            className={`ps-10 h-12 bg-slate-50 ${signupForm.errors.email ? 'border-red-500' : ''}`}
                                                            placeholder="name@example.com"
                                                        />
                                                    </div>
                                                    {signupForm.errors.email && <p className="text-sm text-red-500">{signupForm.errors.email}</p>}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label className="text-slate-700 font-bold">{__('general.whatsapp_number')}</Label>
                                                    <div className="relative">
                                                        <div className="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                                            <Phone className="w-5 h-5 text-slate-400" />
                                                        </div>
                                                        <Input 
                                                            type="text" 
                                                            required 
                                                            value={signupForm.data.mobile}
                                                            onChange={e => signupForm.setData('mobile', e.target.value)}
                                                            className={`ps-10 h-12 bg-slate-50 ${signupForm.errors.mobile ? 'border-red-500' : ''}`}
                                                            placeholder="+1234567890"
                                                        />
                                                    </div>
                                                    <p className="text-xs text-slate-500">{__('general.we_will_send_your_login_credentials_here')}</p>
                                                    {signupForm.errors.mobile && <p className="text-sm text-red-500">{signupForm.errors.mobile}</p>}
                                                </div>
                                            </div>

                                            <Button type="submit" size="lg" className="w-full h-14 text-lg mt-6" disabled={signupForm.processing}>
                                                {signupForm.processing ? 'Processing...' : 'Continue to Payment'} <ArrowRight className="w-5 h-5 ms-2" />
                                            </Button>
                                        </form>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                        <div className="md:col-span-1 space-y-6">
                            <Card className="border-none shadow-sm bg-slate-50">
                                <CardContent className="p-6">
                                    <h3 className="font-bold text-slate-900 mb-4">{__('general.how_it_works')}</h3>
                                    <ol className="space-y-4 text-sm text-slate-600 relative border-s border-slate-200 ms-3 ps-4">
                                        <li className="relative">
                                            <div className="absolute -start-[21px] top-1 w-3 h-3 rounded-full bg-purple-500 border-2 border-white"></div>
                                            <strong className="text-slate-900 block mb-1">1. Fill Details</strong>
                                            {__('general.enter_your_amount_and_contact_info')}</li>
                                        <li className="relative">
                                            <div className="absolute -start-[21px] top-1 w-3 h-3 rounded-full bg-purple-500 border-2 border-white"></div>
                                            <strong className="text-slate-900 block mb-1">2. Get Credentials</strong>
                                            {__('general.receive_an_instant_whatsapp_message_with')}</li>
                                        <li className="relative">
                                            <div className="absolute -start-[21px] top-1 w-3 h-3 rounded-full bg-purple-500 border-2 border-white"></div>
                                            <strong className="text-slate-900 block mb-1">3. Pay Securely</strong>
                                            {__('general.youll_be_redirected_to_log_in_and_comple')}</li>
                                    </ol>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader className="bg-slate-50 border-b border-slate-100 pb-4">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Calculator className="w-5 h-5 text-slate-500" />
                                    {__('general.fee_calculator')}</CardTitle>
                                <CardDescription>{__('general.estimate_your_transfer_and_receiving_amo')}</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <form onSubmit={handleCalc} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 font-bold">{__('general.calculation_type')}</Label>
                                        <Select 
                                            value={calcForm.data.calculation_type} 
                                            onValueChange={val => calcForm.setData('calculation_type', val || '')}
                                        >
                                            <SelectTrigger className="h-12">
                                                <SelectValue placeholder={__('general.select_type')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="visa_master">{__('general.credit_card_to_instapay')}</SelectItem>
                                                <SelectItem value="vodafone_cash">{__('general.vodafone_cash_to_instapay')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-slate-700 font-bold">Transfer Amount (EGP)</Label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                                <span className="text-slate-500 font-bold">EGP</span>
                                            </div>
                                            <Input 
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                required
                                                value={calcForm.data.balance_egp}
                                                onChange={e => calcForm.setData('balance_egp', e.target.value)}
                                                className="ps-14 h-12 text-lg font-bold"
                                                placeholder="0.00"
                                            />
                                        </div>
                                    </div>
                                    <Button type="submit" size="lg" className="w-full h-12" disabled={calcForm.processing}>
                                        {calcForm.processing ? 'Calculating...' : 'Calculate Fees'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        {result !== null && result !== undefined ? (
                            <div className="space-y-6">
                                <Card className="bg-slate-900 text-white border-none shadow-lg">
                                    <CardContent className="p-8">
                                        <p className="text-slate-400 font-medium mb-1">{__('general.you_will_receive')}</p>
                                        <h2 className="text-4xl font-bold text-emerald-400">${formatMoney(result)} <span className="text-xl text-emerald-600">EGP</span></h2>
                                        
                                        <div className="mt-6 pt-6 border-t border-slate-800 grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-slate-500 text-sm mb-1">To withdraw this amount, user must pay:</p>
                                                <p className="text-xl font-bold text-white">{formatMoney(amount_to_pay || 0)} <span className="text-sm text-slate-400">EGP</span></p>
                                            </div>
                                            <div>
                                                <p className="text-slate-500 text-sm mb-1">90% Advance Option:</p>
                                                <p className="text-xl font-bold text-amber-400">{formatMoney(ninety_percent || 0)} <span className="text-sm text-amber-600/50">EGP</span></p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="border-slate-200 shadow-sm border-s-4 border-s-blue-500">
                                    <CardContent className="p-6 flex items-center justify-between">
                                        <div>
                                            <h4 className="font-bold text-slate-900">{__('general.total_fees_deducted')}</h4>
                                            <p className="text-sm text-slate-500">{__('general.includes_processing_gateway_and_service')}</p>
                                        </div>
                                        <div className="text-end">
                                            <p className="text-2xl font-bold text-slate-900">{formatMoney((input || 0) - result)} <span className="text-sm text-slate-500">EGP</span></p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        ) : (
                            <Card className="border-dashed border-2 border-slate-200 bg-slate-50/50 flex flex-col items-center justify-center p-12 text-center text-slate-500">
                                <Calculator className="w-16 h-16 text-slate-300 mb-4" />
                                <h3 className="text-xl font-medium text-slate-700">{__('general.ready_to_calculate')}</h3>
                                <p className="mt-2 text-sm">{__('general.enter_an_amount_and_select_a_type_to_see')}</p>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </WebToolsLayout>
    );
}
