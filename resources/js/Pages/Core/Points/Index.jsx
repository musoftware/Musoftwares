import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router, usePage } from '@inertiajs/react';
import { formatMoney, formatNumber, formatDate } from '../../../lib/utils';
import { CreditCard, Wallet, ArrowRight, CheckCircle2, History, Zap, TrendingUp, RefreshCcw, Sparkles, BadgePercent, ChevronRight, Info } from 'lucide-react';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { AppPage } from '@/Components/ui/AppPage';
import { PageHeader } from '@/Components/ui/PageHeader';
import { __ } from '@/lib/i18n';

export default function PointsIndex({ auth, tiers = [], quickPackages = [], transactions, egpToPreferredRate = 0.10, currency }) {
    const { wallet, flash } = usePage().props;
    const wallet_balance = wallet ? Number(wallet.balance) : 0;
    const Layout = AuthenticatedLayout;
    const [customPoints, setCustomPoints] = useState('');
    const globalCurrency = currency || wallet?.currency || auth?.user?.currency || auth?.user?.preferred_currency;

    // Calculate dynamic price for custom amount using tiers
    const customPricing = useMemo(() => {
        const pts = parseInt(customPoints, 10);
        if (!pts || pts <= 0 || !tiers.length) return null;

        // Find applicable tier
        const tier = tiers.find(t => pts >= t.min && (t.max === null || pts <= t.max));
        if (!tier) return null;

        const pricePerPoint = tier.price_per_point;
        const totalCost = pts * pricePerPoint;
        const fullPrice = pts * tiers[0].price_per_point; // base rate
        const savings = fullPrice - totalCost;
        const discountPercent = tier.discount_percent;

        return { pricePerPoint, totalCost, fullPrice, savings, discountPercent };
    }, [customPoints, tiers]);

    const handleQuickBuy = (pkg) => {
        const canUseWallet = wallet_balance >= pkg.total_cost;
        const msg = canUseWallet
            ? `Pay ${formatMoney(pkg.total_cost, globalCurrency)} from your wallet to buy ${formatNumber(pkg.points)} points?`
            : `You'll be redirected to Kashier to securely pay ${formatMoney(pkg.total_cost, globalCurrency)}. Continue?`;

        if (confirm(msg)) {
            router.post(route('point-purchases.store'), { package_id: pkg.id });
        }
    };

    const handleCustomPurchase = () => {
        const pts = parseInt(customPoints, 10);
        if (!pts || pts <= 0 || !customPricing) return;

        const canUseWallet = wallet_balance >= customPricing.totalCost;
        const msg = canUseWallet
            ? `Pay ${formatMoney(customPricing.totalCost, globalCurrency)} from your wallet to buy ${formatNumber(pts)} points?`
            : `You'll be redirected to Kashier to securely pay ${formatMoney(customPricing.totalCost, globalCurrency)}. Continue?`;

        if (confirm(msg)) {
            router.post(route('point-purchases.store-wallet'), { points: pts });
        }
    };

    return (
        <Layout header="Buy Points">
            <AppPage>
                <PageHeader 
                    title={__('general.buy_points')} 
                    subtitle={__('general.use_points_for_job_applications_proposal_boosts_isaas_lookups_and_premium_marketplace_tools')}
                    icon={Zap}
                />

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <Card className="lg:col-span-2 relative overflow-hidden bg-primary text-primary-foreground border-none">
                        <div className="absolute top-0 end-0 p-8 opacity-10">
                            <Zap className="w-48 h-48" />
                        </div>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-primary-foreground/80 text-sm font-medium">
                                <TrendingUp className="w-4 h-4" />{__('general.active_points_balance')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-5xl lg:text-6xl font-bold tracking-tight mb-2">
                                {formatNumber(auth.user.points_balance || 0)} <span className="text-2xl text-primary-foreground/70 font-medium">pts</span>
                            </div>
                        </CardContent>
                        <CardFooter className="bg-black/10 border-t-0 mt-4">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-full bg-white/20">
                                    <Wallet className="w-4 h-4" />
                                </div>
                                <div>
                                    <p className="text-xs text-primary-foreground/70 uppercase tracking-wider">{__('general.wallet_balance')}</p>
                                    <div className="font-semibold"><FinancialAmount amount={wallet_balance} currency={globalCurrency} /></div>
                                </div>
                            </div>
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BadgePercent className="w-5 h-5 text-primary" />{__('general.volume_pricing')}</CardTitle>
                            <CardDescription>{__('general.buy_more_save_more_on_every_point')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {tiers.map((tier, i) => {
                                const pts = parseInt(customPoints, 10) || 0;
                                const isActive = pts >= tier.min && (tier.max === null || pts <= tier.max);

                                return (
                                    <div
                                        key={i}
                                        className={`flex items-center justify-between rounded-lg px-3 py-2 border transition-all ${isActive
                                            ? 'bg-primary/5 border-primary/20 ring-1 ring-primary/20'
                                            : 'bg-muted/50 border-transparent'
                                            }`}
                                    >
                                        <div className="flex items-center gap-2">
                                            {isActive && <Sparkles className="w-3 h-3 text-primary animate-pulse" />}
                                            <span className={`text-sm font-medium ${isActive ? 'text-primary' : 'text-foreground'}`}>
                                                {formatNumber(tier.min)}{tier.max ? `–${formatNumber(tier.max)}` : '+'} pts
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className={`text-sm font-semibold ${isActive ? 'text-primary' : 'text-foreground'}`}>
                                                <FinancialAmount amount={tier.price_per_point} currency={globalCurrency} />/pt
                                            </span>
                                            {tier.discount_percent > 0 && (
                                                <Badge variant={isActive ? "default" : "secondary"} className="h-5 px-1.5 text-[10px]">
                                                    -{tier.discount_percent}%
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="packages" className="mb-8">
                    <TabsList className="grid w-full grid-cols-2 lg:w-[400px]">
                        <TabsTrigger value="packages">{__('general.quick_packages')}</TabsTrigger>
                        <TabsTrigger value="custom">{__('general.custom_amount')}</TabsTrigger>
                    </TabsList>
                    
                    <TabsContent value="packages" className="mt-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            {quickPackages.map((pkg, i) => {
                                const canAfford = wallet_balance >= pkg.total_cost;
                                const isBest = pkg.discount_percent >= 40;

                                return (
                                    <Card key={i} className={`relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-md ${isBest ? 'border-primary shadow-sm ring-1 ring-primary/20' : ''}`}>
                                        {isBest && (
                                            <div className="absolute top-0 inset-x-0 h-1 bg-primary" />
                                        )}
                                        {isBest && (
                                            <div className="absolute top-3 end-3">
                                                <Badge variant="default" className="shadow-sm">{__('general.best_value')}</Badge>
                                            </div>
                                        )}
                                        {!isBest && pkg.discount_percent > 0 && (
                                            <div className="absolute top-3 end-3">
                                                <Badge variant="secondary">-{pkg.discount_percent}%</Badge>
                                            </div>
                                        )}
                                        <CardHeader className="text-center pt-8">
                                            <CardDescription className="uppercase tracking-widest text-xs font-semibold">{pkg.label}</CardDescription>
                                            <CardTitle className="text-3xl font-bold flex items-baseline justify-center gap-1 mt-2">
                                                {formatNumber(pkg.points)}
                                                <span className="text-sm text-muted-foreground font-normal">pts</span>
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="text-center pb-6">
                                            {pkg.discount_percent > 0 && (
                                                <div className="text-xs text-muted-foreground line-through mb-1">
                                                    <FinancialAmount amount={pkg.full_price} currency={globalCurrency} />
                                                </div>
                                            )}
                                            <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{__('general.you_pay')}</div>
                                            <div className="text-3xl font-bold mb-1 text-slate-900">
                                                <FinancialAmount amount={pkg.total_cost} currency={globalCurrency} />
                                            </div>
                                            <div className="text-xs text-muted-foreground mb-4">
                                                <FinancialAmount amount={pkg.price_per_point} currency={globalCurrency} />{__('general.per_point')}</div>
                                            {pkg.savings > 0 && (
                                                <div className="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md mb-2">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    {__('general.save')}<FinancialAmount amount={pkg.savings} currency={globalCurrency} />
                                                </div>
                                            )}
                                        </CardContent>
                                        <CardFooter>
                                            <Button 
                                                className="w-full" 
                                                variant={isBest ? "default" : "outline"}
                                                onClick={() => handleQuickBuy(pkg)}
                                            >
                                                {canAfford ? (
                                                    <span className="flex items-center gap-2">{__('general.pay')}<FinancialAmount amount={pkg.total_cost} currency={globalCurrency} />{__('general.via_wallet')}<Wallet className="w-4 h-4" /></span>
                                                ) : (
                                                    <span className="flex items-center gap-2">{__('general.pay')}<FinancialAmount amount={pkg.total_cost} currency={globalCurrency} />{__('general.via_kashier')}<CreditCard className="w-4 h-4" /></span>
                                                )}
                                            </Button>
                                        </CardFooter>
                                    </Card>
                                );
                            })}
                        </div>
                    </TabsContent>

                    <TabsContent value="custom" className="mt-6">
                        <Card className="max-w-xl mx-auto">
                            <CardHeader className="text-center pb-8">
                                <div className="mx-auto w-12 h-12 bg-muted rounded-full flex items-center justify-center mb-4 text-foreground">
                                    <RefreshCcw className="w-5 h-5" />
                                </div>
                                <CardTitle>{__('general.choose_your_amount')}</CardTitle>
                                <CardDescription>{__('general.enter_any_number_of_points_buy_more_to_unlock_better_rates')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div>
                                    <label className="block text-sm font-medium mb-2">{__('general.number_of_points')}</label>
                                    <div className="relative">
                                        <Input
                                            type="number"
                                            value={customPoints}
                                            onChange={(e) => setCustomPoints(e.target.value)}
                                            className="text-lg py-6 px-4"
                                            placeholder={__('general.e_g_500')}
                                            min="1"
                                        />
                                        <div className="absolute end-4 top-1/2 -translate-y-1/2 text-muted-foreground font-medium">
                                            pts
                                        </div>
                                    </div>
                                </div>

                                {customPricing && (
                                    <div className="bg-muted/50 rounded-lg p-5 space-y-3">
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-muted-foreground">{__('general.price_per_point')}</span>
                                            <span className="text-sm font-semibold">
                                                <FinancialAmount amount={customPricing.pricePerPoint} currency={globalCurrency} />
                                            </span>
                                        </div>

                                        {customPricing.discountPercent > 0 && (
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-muted-foreground">{__('general.full_price')}</span>
                                                <span className="text-sm text-muted-foreground line-through">
                                                    <FinancialAmount amount={customPricing.fullPrice} currency={globalCurrency} />
                                                </span>
                                            </div>
                                        )}

                                        <div className="border-t border-border pt-3 mt-3 flex justify-between items-end">
                                            <div>
                                                <p className="text-xs text-muted-foreground font-medium uppercase tracking-wider mb-1">{__('general.you_pay')}</p>
                                                <div className="text-3xl font-bold text-slate-900">
                                                    <FinancialAmount amount={customPricing.totalCost} currency={globalCurrency} />
                                                </div>
                                            </div>
                                            <div className="text-end">
                                                <p className="text-xs text-muted-foreground font-medium uppercase tracking-wider">{__('general.wallet')}</p>
                                                <div className={`text-sm font-semibold ${wallet_balance >= customPricing.totalCost ? 'text-emerald-600' : 'text-foreground'}`}>
                                                    <FinancialAmount amount={wallet_balance} currency={globalCurrency} />
                                                </div>
                                            </div>
                                        </div>

                                        {customPricing.savings > 0 && (
                                            <div className="flex items-center justify-center gap-2 py-1.5 px-3 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-md mt-2">
                                                <Sparkles className="w-3 h-3" />{__('general.you_save')}<FinancialAmount amount={customPricing.savings} currency={globalCurrency} /> ({customPricing.discountPercent}% off)
                                            </div>
                                        )}
                                    </div>
                                )}

                                {!customPricing && customPoints && (
                                    <div className="text-center py-4 text-sm text-muted-foreground">{__('general.enter_a_valid_number_of_points_to_see_pricing')}</div>
                                )}
                            </CardContent>
                            <CardFooter>
                                <Button 
                                    className="w-full" 
                                    size="lg"
                                    disabled={!customPricing}
                                    onClick={handleCustomPurchase}
                                >
                                    {customPricing && wallet_balance >= customPricing.totalCost ? (
                                        <span className="flex items-center gap-2">{__('general.pay')}<FinancialAmount amount={customPricing.totalCost} currency={globalCurrency} />{__('general.via_wallet')}<Wallet className="w-4 h-4" /></span>
                                    ) : (
                                        <span className="flex items-center gap-2">{__('general.pay')}<FinancialAmount amount={customPricing?.totalCost || 0} currency={globalCurrency} />{__('general.via_kashier')}<CreditCard className="w-4 h-4" /></span>
                                    )}
                                </Button>
                            </CardFooter>
                        </Card>
                    </TabsContent>
                </Tabs>

                <div>
                    <div className="flex items-center gap-2 mb-4">
                        <History className="w-5 h-5 text-muted-foreground" />
                        <h3 className="text-lg font-semibold">{__('general.history')}</h3>
                    </div>

                    <Card>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{__('general.date')}</TableHead>
                                    <TableHead>{__('general.description')}</TableHead>
                                    <TableHead className="text-end">{__('general.points')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(!transactions || !transactions.data || transactions.data.length === 0) ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="h-32 text-center">
                                            <div className="flex flex-col items-center justify-center text-muted-foreground">
                                                <History className="w-8 h-8 mb-2 opacity-50" />
                                                <p>{__('general.no_transactions_found')}</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    transactions.data.map(tx => (
                                        <TableRow key={tx.id}>
                                            <TableCell className="font-medium text-muted-foreground">
                                                {formatDate(tx.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className={`p-1.5 rounded-full ${['purchased', 'earned'].includes(tx.type) ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'}`}>
                                                        {['purchased', 'earned'].includes(tx.type) ? <TrendingUp className="w-3.5 h-3.5" /> : <TrendingUp className="w-3.5 h-3.5 rotate-180" />}
                                                    </div>
                                                    <div>
                                                        <p className="font-medium">{tx.description}</p>
                                                        <p className="text-xs text-muted-foreground capitalize">{tx.type}</p>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-end">
                                                <Badge variant={['purchased', 'earned'].includes(tx.type) ? "secondary" : "destructive"} className="font-semibold">
                                                    {['purchased', 'earned'].includes(tx.type) ? '+' : '-'}{formatNumber(tx.points)}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </Card>
                </div>
            </AppPage>
        </Layout>
    );
}

