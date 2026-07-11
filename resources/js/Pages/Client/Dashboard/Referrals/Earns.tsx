import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Clock, Wallet, CheckCircle2 } from 'lucide-react';

interface EarnsProps {
    pending_balance: number;
    pending_balance_str: string;
    available_commission: number;
    available_commission_str: string;
    withdrawed_commission: number;
    withdrawed_commission_str: string;
}

export default function Earns({
    pending_balance,
    pending_balance_str,
    available_commission,
    available_commission_str,
    withdrawed_commission,
    withdrawed_commission_str,
}: EarnsProps) {

    const handleTabChange = (val: string) => {
        router.visit(route(val));
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.referral_earnings')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="flex justify-between items-center">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {__('general.referrals')}
                        </h2>
                    </div>

                    <Tabs value="referrals.earns" onValueChange={handleTabChange} className="w-full">
                        <TabsList className="grid w-full grid-cols-3 max-w-md bg-slate-100 p-1 rounded-lg">
                            <TabsTrigger value="referrals.index" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md py-1.5 text-sm font-medium transition-all">
                                {__('general.referral_link')}
                            </TabsTrigger>
                            <TabsTrigger value="referrals.earns" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md py-1.5 text-sm font-medium transition-all">
                                {__('general.referral_earnings')}
                            </TabsTrigger>
                            <TabsTrigger value="referrals.registers" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-md py-1.5 text-sm font-medium transition-all">
                                {__('general.referred_users')}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Pending Commission */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div className="space-y-1">
                                    <CardTitle className="text-sm font-medium text-slate-500 uppercase tracking-wider">
                                        {__('general.pending_earns') || __('general.pending_commission') || 'Pending Earns'}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('general.earned_not_cleared_yet') || 'Earned but not yet cleared'}
                                    </CardDescription>
                                </div>
                                <Clock className="w-5 h-5 text-amber-500" />
                            </CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold text-slate-900">
                                    {pending_balance_str}
                                </span>
                            </CardContent>
                        </Card>

                        {/* Available Commission */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div className="space-y-1">
                                    <CardTitle className="text-sm font-medium text-slate-500 uppercase tracking-wider">
                                        {__('general.available_commission') || 'Available Commission'}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('general.ready_for_withdrawal') || 'Available to withdraw'}
                                    </CardDescription>
                                </div>
                                <Wallet className="w-5 h-5 text-indigo-500" />
                            </CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold text-slate-900">
                                    {available_commission_str}
                                </span>
                            </CardContent>
                        </Card>

                        {/* Withdrawn Commission */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div className="space-y-1">
                                    <CardTitle className="text-sm font-medium text-slate-500 uppercase tracking-wider">
                                        {__('general.withdrawn_commission') || 'Withdrawn Commission'}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('general.successfully_paid_out') || 'Successfully paid out'}
                                    </CardDescription>
                                </div>
                                <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                            </CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold text-slate-900">
                                    {withdrawed_commission_str}
                                </span>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
