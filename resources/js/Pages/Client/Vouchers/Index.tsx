import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Info, Ticket, Wallet, Calendar, UserCheck, Users, ArrowRight, History } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';

interface Voucher {
    id: number;
    name: string;
    description: string;
    type: 'fixed' | 'percentage';
    reward_percentage: number | null;
    spend_amount_user_currency: number;
    reward_amount_user_currency: number;
    expires_at: string | null;
    max_uses_per_user: number | null;
    current_uses: number;
    max_total_uses: number | null;
}

interface Redemption {
    id: number;
    spent_amount: number;
    reward_amount: number;
    created_at: string;
    voucher: {
        name: string;
    };
}

interface Props {
    auth: {
        user: any;
    };
    vouchers: Voucher[];
    redemptions: {
        data: Redemption[];
        links: any[];
    };
}

export default function Index({ auth, vouchers, redemptions }: Props) {
    const userCurrency = auth.user?.currency;

    return (
        <AuthenticatedLayout>
            <Head title={__('vouchers.title')} />

            <div className="container mx-auto px-4 py-8 max-w-7xl">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h2 className="text-3xl font-bold flex items-center gap-2">
                            <Ticket className="h-8 w-8 text-primary" />
                            {__('vouchers.title')}
                        </h2>
                        <p className="text-muted-foreground mt-1">
                            {__('vouchers.subtitle')}
                        </p>
                    </div>
                    <Button asChild size="lg" className="shrink-0">
                        <Link href={route('financial.add-balance')}>
                            <Wallet className="me-2 h-5 w-5" />
                            {__('vouchers.add_balance')}
                        </Link>
                    </Button>
                </div>

                {/* How it works info */}
                {vouchers.length > 0 && (
                    <Alert className="mb-8 bg-blue-50/50 border-blue-200 text-blue-900 dark:bg-blue-950/20 dark:border-blue-900 dark:text-blue-200">
                        <Info className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <AlertTitle className="text-blue-800 dark:text-blue-300 font-semibold">
                            {__('vouchers.how_vouchers_work.title')}
                        </AlertTitle>
                        <AlertDescription className="text-blue-700 dark:text-blue-400/80">
                            {__('vouchers.how_vouchers_work.description')}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Vouchers Grid */}
                {vouchers.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-12">
                        {vouchers.map((voucher) => (
                            <Card key={voucher.id} className="flex flex-col overflow-hidden border-0 shadow-md">
                                <CardHeader className="bg-gradient-to-br from-violet-600 to-purple-700 text-white p-6">
                                    <div className="flex justify-between items-start mb-2">
                                        <h3 className="font-bold text-xl">{voucher.name}</h3>
                                        <Badge variant="secondary" className="bg-white/20 hover:bg-white/30 text-white border-0">
                                            {voucher.type === 'percentage' ? `${voucher.reward_percentage}%` : __('vouchers.fixed')}
                                        </Badge>
                                    </div>
                                    {voucher.description && (
                                        <p className="text-white/80 text-sm line-clamp-2">
                                            {voucher.description}
                                        </p>
                                    )}
                                </CardHeader>
                                <CardContent className="p-6 flex-1">
                                    <div className="bg-slate-50 dark:bg-slate-900 rounded-lg p-4 mb-5 flex items-center justify-between border border-slate-100 dark:border-slate-800">
                                        <div>
                                            <span className="text-xs text-muted-foreground uppercase tracking-wider font-semibold block mb-1">
                                                {__('vouchers.spend')}
                                            </span>
                                            <strong className="text-lg">
                                                {formatCurrency(voucher.spend_amount_user_currency, userCurrency)}
                                            </strong>
                                        </div>
                                        <ArrowRight className="h-6 w-6 text-primary/60 mx-2" />
                                        <div className="text-end">
                                            <span className="text-xs text-muted-foreground uppercase tracking-wider font-semibold block mb-1">
                                                {__('vouchers.get')}
                                            </span>
                                            <strong className="text-lg text-emerald-600 dark:text-emerald-400">
                                                {formatCurrency(voucher.reward_amount_user_currency, userCurrency)}
                                            </strong>
                                        </div>
                                    </div>

                                    <div className="space-y-3 mb-6">
                                        <div className="flex flex-col">
                                            <span className="text-sm text-muted-foreground flex items-center gap-1.5 mb-1">
                                                <Calendar className="h-4 w-4" />
                                                {__('vouchers.valid_until')}
                                            </span>
                                            <div>
                                                {voucher.expires_at ? (
                                                    <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900">
                                                        {voucher.expires_at}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900">
                                                        {__('vouchers.no_expiry')}
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>

                                        {voucher.max_uses_per_user && (
                                            <div className="text-sm text-muted-foreground flex items-center gap-1.5">
                                                <UserCheck className="h-4 w-4" />
                                                {__('vouchers.max')} {voucher.max_uses_per_user} {__('vouchers.per_user')}
                                            </div>
                                        )}
                                    </div>

                                    <Button asChild className="w-full mt-auto" size="lg">
                                        <Link href={route('financial.add-balance')}>
                                            <Wallet className="me-2 h-4 w-4" />
                                            {__('vouchers.add_balance_and_pay')}
                                        </Link>
                                    </Button>
                                </CardContent>
                                <CardFooter className="bg-slate-50 dark:bg-slate-950 p-4 border-t flex justify-between items-center text-xs text-muted-foreground">
                                    <div className="flex items-center gap-1">
                                        <Users className="h-4 w-4" />
                                        {voucher.current_uses} {__('vouchers.uses')}
                                    </div>
                                    {voucher.max_total_uses && (
                                        <div>
                                            {__('vouchers.limit')}: {voucher.max_total_uses}
                                        </div>
                                    )}
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-20 px-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed mb-12">
                        <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 mb-6">
                            <Ticket className="h-10 w-10 text-slate-400" />
                        </div>
                        <h3 className="text-2xl font-bold mb-2">{__('vouchers.empty.title')}</h3>
                        <p className="text-muted-foreground max-w-7xl mx-auto">
                            {__('vouchers.empty.description')}
                        </p>
                    </div>
                )}

                {/* Redemption History Table */}
                {redemptions.data.length > 0 && (
                    <Card className="border-0 shadow-sm">
                        <CardHeader className="bg-slate-50/50 dark:bg-slate-900/50 border-b pb-4">
                            <h3 className="font-semibold text-lg flex items-center gap-2">
                                <History className="h-5 w-5 text-muted-foreground" />
                                {__('vouchers.redemptions.title')}
                            </h3>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead>{__('vouchers.voucher')}</TableHead>
                                        <TableHead>{__('vouchers.redemptions.spent')}</TableHead>
                                        <TableHead>{__('vouchers.redemptions.reward_received')}</TableHead>
                                        <TableHead>{__('vouchers.redemptions.date')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {redemptions.data.map((redemption) => (
                                        <TableRow key={redemption.id}>
                                            <TableCell className="font-medium">
                                                {redemption.voucher.name}
                                            </TableCell>
                                            <TableCell>
                                                {formatCurrency(redemption.spent_amount, userCurrency)}
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-emerald-600 dark:text-emerald-400 font-bold">
                                                    +{formatCurrency(redemption.reward_amount, userCurrency)}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {new Date(redemption.created_at).toLocaleDateString()} {new Date(redemption.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
