import React from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { __ } from '@/lib/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import Pagination from '@/Components/Pagination';
import { formatMoney } from '@/lib/utils';
import { Users, DollarSign, Calendar, Mail, ShieldAlert, ShieldCheck } from 'lucide-react';

interface ReferredUser {
    id: number;
    name: string;
    email: string;
    created_at: string;
    email_verified_at: string | null;
}

interface RegistersProps {
    referred_users: {
        data: ReferredUser[];
        links: any[];
        total: number;
        last_page: number;
    };
    commissionByUserId: Record<number, number>;
    global_commission_total: number;
}

export default function Registers({
    referred_users,
    commissionByUserId,
    global_commission_total,
}: RegistersProps) {
    const { currencies, auth } = usePage().props as any;
    const userCurrency = currencies?.find((c: any) => c.id === auth?.user?.currency_id) || { symbol: '$', currency: 'USD' };

    const handleTabChange = (val: string) => {
        router.visit(route(val));
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.referred_users')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="flex justify-between items-center">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {__('general.referrals')}
                        </h2>
                    </div>

                    <Tabs value="referrals.registers" onValueChange={handleTabChange} className="w-full">
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

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Total Referred Users */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div className="space-y-1">
                                    <CardTitle className="text-sm font-medium text-slate-500 uppercase tracking-wider">
                                        {__('general.total_referrals') || 'Total Referrals'}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('general.users_registered_via_your_link') || 'Users registered via your referral link'}
                                    </CardDescription>
                                </div>
                                <Users className="w-5 h-5 text-indigo-500" />
                            </CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold text-slate-900">
                                    {referred_users.total}
                                </span>
                            </CardContent>
                        </Card>

                        {/* Global Commissions Total */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div className="space-y-1">
                                    <CardTitle className="text-sm font-medium text-slate-500 uppercase tracking-wider">
                                        {__('general.total_commissions_earned') || 'Total Commissions'}
                                    </CardTitle>
                                    <CardDescription>
                                        {__('general.commissions_earned_from_all_payments') || 'Aggregate commission earned from all referrals'}
                                    </CardDescription>
                                </div>
                                <DollarSign className="w-5 h-5 text-emerald-500" />
                            </CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold text-slate-900">
                                    {formatMoney(global_commission_total, userCurrency)}
                                </span>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.referred_users')}</CardTitle>
                            <CardDescription>
                                {__('general.list_of_your_referred_users') || 'List of users registered under your referral code'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            {referred_users.data.length > 0 ? (
                                <>
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-slate-50 hover:bg-slate-50">
                                                <TableHead className="w-16">ID</TableHead>
                                                <TableHead>{__('general.name')}</TableHead>
                                                <TableHead>{__('general.email')}</TableHead>
                                                <TableHead>{__('general.joined_date')}</TableHead>
                                                <TableHead>{__('general.status')}</TableHead>
                                                <TableHead className="text-end">{__('general.commissions')}</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {referred_users.data.map((user) => (
                                                <TableRow key={user.id}>
                                                    <TableCell className="font-mono text-slate-500">#{user.id}</TableCell>
                                                    <TableCell className="font-medium text-slate-900">{user.name}</TableCell>
                                                    <TableCell className="text-slate-500">
                                                        <div className="flex items-center gap-1.5">
                                                            <Mail className="w-3.5 h-3.5" />
                                                            {user.email}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-slate-500">
                                                        <div className="flex items-center gap-1.5">
                                                            <Calendar className="w-3.5 h-3.5" />
                                                            {new Date(user.created_at).toLocaleDateString()}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {user.email_verified_at ? (
                                                            <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200/50 hover:bg-emerald-50 flex items-center gap-1 w-fit shadow-none">
                                                                <ShieldCheck className="w-3 h-3" />
                                                                {__('general.verified')}
                                                            </Badge>
                                                        ) : (
                                                            <Badge className="bg-amber-50 text-amber-700 border-amber-200/50 hover:bg-amber-50 flex items-center gap-1 w-fit shadow-none">
                                                                <ShieldAlert className="w-3 h-3" />
                                                                {__('general.unverified')}
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-end font-bold text-slate-900">
                                                        {formatMoney(commissionByUserId[user.id] || 0, userCurrency)}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                    {referred_users.last_page > 1 && (
                                        <div className="p-4 border-t">
                                            <Pagination links={referred_users.links} />
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="py-12 text-center text-slate-500">
                                    <Users className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                                    <p className="font-medium text-slate-700">{__('general.no_referrals_yet')}</p>
                                    <p className="text-sm text-slate-400 mt-1">{__('general.share_link_to_start_earning') || 'Share your link to start earning commissions.'}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
