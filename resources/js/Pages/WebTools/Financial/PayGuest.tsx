import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { CreditCard, Wallet, User, Mail, Phone, ArrowRight, ShieldCheck } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { __ } from '@/lib/i18n';

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
        <WebToolsLayout title={__('general.pay_as_guest')} activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8 text-center">
                    <div className="inline-flex items-center justify-center p-3 bg-indigo-100 rounded-2xl text-indigo-600 mb-4">
                        <CreditCard className="w-8 h-8" />
                    </div>
                    <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
                        {__('general.pay_as_guest')}</h1>
                    <p className="text-lg text-slate-600 max-w-7xl mx-auto">
                        {__('general.create_an_account_instantly_and_receive')}</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-2">
                        <Card className="border-slate-200 shadow-sm border-t-4 border-t-indigo-500 overflow-hidden">
                            <div className="bg-indigo-50/50 p-6 border-b border-slate-100 flex items-start gap-4">
                                <ShieldCheck className="w-6 h-6 text-indigo-500 mt-1 flex-shrink-0" />
                                <div>
                                    <h3 className="font-bold text-slate-900">{__('general.secure_guest_checkout')}</h3>
                                    <p className="text-sm text-slate-600 mt-1">
                                        {__('general.your_account_will_be_created_automatical')}</p>
                                </div>
                            </div>
                            
                            <CardContent className="p-6">
                                {payGuestCurrencies.length === 0 ? (
                                    <Alert variant="destructive">
                                        <AlertTitle>{__('general.error')}</AlertTitle>
                                        <AlertDescription>{__('general.payment_signup_is_temporarily_unavailabl')}</AlertDescription>
                                    </Alert>
                                ) : (
                                    <form onSubmit={submit} className="space-y-6">
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
                                                    value={data.amount}
                                                    onChange={e => setData('amount', e.target.value)}
                                                    className={`ps-10 h-14 text-xl font-bold rounded-e-none focus-visible:z-10 ${errors.amount ? 'border-red-500' : ''}`}
                                                    placeholder="e.g. 1000"
                                                />
                                                <Select 
                                                    value={data.currency_id} 
                                                    onValueChange={val => setData('currency_id', val || '')}
                                                >
                                                    <SelectTrigger className={`w-[120px] h-14 rounded-s-none border-s-0 bg-slate-50 font-bold ${errors.currency_id ? 'border-red-500' : ''}`}>
                                                        <SelectValue placeholder={__('general.currency')} />
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
                                                <Label className="text-slate-700 font-bold">{__('general.full_name')}</Label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                                        <User className="w-5 h-5 text-slate-400" />
                                                    </div>
                                                    <Input 
                                                        type="text" 
                                                        required 
                                                        value={data.name}
                                                        onChange={e => setData('name', e.target.value)}
                                                        className={`ps-10 h-12 bg-slate-50 ${errors.name ? 'border-red-500' : ''}`}
                                                        placeholder={__('general.your_full_name')}
                                                    />
                                                </div>
                                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
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
                                                        value={data.email}
                                                        onChange={e => setData('email', e.target.value)}
                                                        className={`ps-10 h-12 bg-slate-50 ${errors.email ? 'border-red-500' : ''}`}
                                                        placeholder="name@example.com"
                                                    />
                                                </div>
                                                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
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
                                                        value={data.mobile}
                                                        onChange={e => setData('mobile', e.target.value)}
                                                        className={`ps-10 h-12 bg-slate-50 ${errors.mobile ? 'border-red-500' : ''}`}
                                                        placeholder="+1234567890"
                                                    />
                                                </div>
                                                <p className="text-xs text-slate-500">{__('general.we_will_send_your_login_credentials_here')}</p>
                                                {errors.mobile && <p className="text-sm text-red-500">{errors.mobile}</p>}
                                            </div>
                                        </div>

                                        <Button type="submit" size="lg" className="w-full h-14 text-lg mt-6" disabled={processing}>
                                            {processing ? 'Processing...' : 'Continue to Payment'} <ArrowRight className="w-5 h-5 ms-2" />
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
                                        <div className="absolute -start-[21px] top-1 w-3 h-3 rounded-full bg-indigo-500 border-2 border-white"></div>
                                        <strong className="text-slate-900 block mb-1">1. Fill Details</strong>
                                        {__('general.enter_your_amount_and_contact_info')}</li>
                                    <li className="relative">
                                        <div className="absolute -start-[21px] top-1 w-3 h-3 rounded-full bg-indigo-500 border-2 border-white"></div>
                                        <strong className="text-slate-900 block mb-1">2. Get Credentials</strong>
                                        {__('general.receive_an_instant_whatsapp_message_with')}</li>
                                    <li className="relative">
                                        <div className="absolute -start-[21px] top-1 w-3 h-3 rounded-full bg-indigo-500 border-2 border-white"></div>
                                        <strong className="text-slate-900 block mb-1">3. Pay Securely</strong>
                                        {__('general.youll_be_redirected_to_log_in_and_comple')}</li>
                                </ol>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </WebToolsLayout>
    );
}
