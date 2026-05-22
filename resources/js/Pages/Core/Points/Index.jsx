import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router, usePage } from '@inertiajs/react';
import { formatMoney, formatNumber, formatDate } from '../../../lib/utils';
import { CreditCard, Wallet, ArrowRight, CheckCircle2, History, Zap, TrendingUp, RefreshCcw, Sparkles, BadgePercent, ChevronRight, Info } from 'lucide-react';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';

export default function PointsIndex({ auth, tiers = [], quickPackages = [], transactions, egpToPreferredRate = 0.10, currency = 'USD' }) {
    const { wallet, flash } = usePage().props;
    const wallet_balance = wallet ? Number(wallet.balance) : 0;
    const [customPoints, setCustomPoints] = useState('');
    const [activeTab, setActiveTab] = useState('packages');
    const globalCurrency = currency || wallet?.currency || auth?.user?.preferred_currency || 'USD';

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
            router.post(route('point-purchases.store-wallet'), { points: pkg.points });
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
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Buy Points</h2>}>
            <div className="w-full pb-20 space-y-8">

                {/* Hero Balance Card */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-8 relative overflow-hidden shadow-2xl">
                        <div className="absolute top-0 right-0 p-8 opacity-[0.07]">
                            <Zap className="w-56 h-56" />
                        </div>
                        <div className="absolute -bottom-20 -left-20 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl" />
                        <div className="relative z-10 flex flex-col h-full justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-slate-300 text-sm font-medium mb-6 border border-white/10 backdrop-blur-sm">
                                    <TrendingUp className="w-4 h-4" />
                                    Active Points Balance
                                </div>
                                <h1 className="text-5xl lg:text-7xl font-extrabold tracking-tight mb-2">
                                    {formatNumber(auth.user.points_balance || 0)} <span className="text-2xl text-slate-400 font-semibold tracking-normal">pts</span>
                                </h1>
                                <p className="text-slate-400 text-lg max-w-md mt-4 leading-relaxed">
                                    Use points for job applications, proposal boosts, iSAAS lookups, and premium marketplace tools.
                                </p>
                            </div>

                            <div className="mt-8 flex flex-wrap gap-4 items-center">
                                <div className="flex items-center gap-2 bg-white/5 backdrop-blur rounded-xl px-4 py-3 border border-white/10">
                                    <div className="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                                        <Wallet className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p className="text-xs text-slate-400 uppercase tracking-wider">Wallet Balance</p>
                                        <div className="font-semibold text-white"><FinancialAmount amount={wallet_balance} currency={globalCurrency} /></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Volume Discount Tiers */}
                    <div className="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col">
                        <h3 className="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <BadgePercent className="w-5 h-5 text-indigo-600" />
                            Volume Pricing
                        </h3>
                        <p className="text-xs text-slate-500 mb-4">Buy more, save more on every point.</p>
                        <div className="space-y-2 flex-1">
                            {tiers.map((tier, i) => {
                                const pts = parseInt(customPoints, 10) || 0;
                                const isActive = pts >= tier.min && (tier.max === null || pts <= tier.max);

                                return (
                                    <div
                                        key={i}
                                        className={`flex items-center justify-between rounded-xl px-3.5 py-2.5 border transition-all duration-200 ${isActive
                                                ? 'bg-indigo-50 border-indigo-200 ring-1 ring-indigo-200 shadow-sm'
                                                : 'bg-slate-50/50 border-slate-100 hover:bg-slate-50'
                                            }`}
                                    >
                                        <div className="flex items-center gap-2 min-w-0">
                                            {isActive && <Sparkles className="w-3.5 h-3.5 text-indigo-500 shrink-0 animate-pulse" />}
                                            <span className={`text-sm font-medium truncate ${isActive ? 'text-indigo-900' : 'text-slate-700'}`}>
                                                {formatNumber(tier.min)}{tier.max ? `–${formatNumber(tier.max)}` : '+'} pts
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 shrink-0">
                                            <span className={`text-sm font-bold ${isActive ? 'text-indigo-700' : 'text-slate-800'}`}>
                                                <FinancialAmount amount={tier.price_per_point} currency={globalCurrency} />/pt
                                            </span>
                                            {tier.discount_percent > 0 && (
                                                <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${isActive
                                                        ? 'bg-indigo-200 text-indigo-800'
                                                        : 'bg-emerald-100 text-emerald-700'
                                                    }`}>
                                                    -{tier.discount_percent}%
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Main Content — Tabs */}
                <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="flex border-b border-slate-100">
                        <button
                            onClick={() => setActiveTab('packages')}
                            className={`flex-1 py-5 text-center font-semibold text-lg transition-colors ${activeTab === 'packages' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50/30' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Quick Packages
                        </button>
                        <button
                            onClick={() => setActiveTab('custom')}
                            className={`flex-1 py-5 text-center font-semibold text-lg transition-colors ${activeTab === 'custom' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50/30' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Custom Amount
                        </button>
                    </div>

                    <div className="p-8 lg:p-12 bg-slate-50/50">
                        {/* Quick Packages Tab */}
                        {activeTab === 'packages' && (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                {quickPackages.map((pkg, i) => {
                                    const canAfford = wallet_balance >= pkg.total_cost;
                                    const isBest = pkg.discount_percent >= 40;

                                    return (
                                        <div
                                            key={i}
                                            className={`relative bg-white rounded-2xl border-2 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl group ${isBest ? 'border-indigo-500 shadow-lg' : 'border-slate-100 shadow-sm'}`}
                                        >
                                            {isBest && (
                                                <div className="absolute -top-4 left-0 right-0 flex justify-center">
                                                    <span className="bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-md">
                                                        Best Value
                                                    </span>
                                                </div>
                                            )}
                                            {pkg.discount_percent > 0 && !isBest && (
                                                <div className="absolute -top-3 right-4">
                                                    <span className="bg-emerald-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-sm">
                                                        -{pkg.discount_percent}%
                                                    </span>
                                                </div>
                                            )}
                                            <div className="p-6 text-center">
                                                <h4 className="text-slate-500 font-semibold uppercase tracking-widest text-xs mb-3">{pkg.label}</h4>
                                                <div className="text-4xl font-extrabold text-slate-900 mb-1 flex items-baseline justify-center gap-1">
                                                    {formatNumber(pkg.points)}
                                                    <span className="text-lg text-slate-400 font-medium">pts</span>
                                                </div>

                                                {pkg.discount_percent > 0 && (
                                                    <div className="text-xs text-slate-400 line-through mb-1">
                                                        <FinancialAmount amount={pkg.full_price} currency={globalCurrency} />
                                                    </div>
                                                )}

                                                <div className="text-xl text-slate-700 font-bold mb-1">
                                                    <FinancialAmount amount={pkg.total_cost} currency={globalCurrency} />
                                                </div>

                                                <div className="text-xs text-slate-500 mb-6">
                                                    <FinancialAmount amount={pkg.price_per_point} currency={globalCurrency} /> per point
                                                </div>

                                                {pkg.savings > 0 && (
                                                    <div className="text-xs text-emerald-600 font-medium bg-emerald-50 rounded-lg px-3 py-1.5 mb-4 inline-flex items-center gap-1">
                                                        <CheckCircle2 className="w-3 h-3" />
                                                        Save <FinancialAmount amount={pkg.savings} currency={globalCurrency} />
                                                    </div>
                                                )}

                                                <button
                                                    onClick={() => handleQuickBuy(pkg)}
                                                    className={`w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-semibold text-sm transition-all ${isBest
                                                            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white hover:shadow-lg hover:shadow-indigo-200'
                                                            : 'bg-slate-900 text-white hover:bg-slate-800'
                                                        } disabled:opacity-70 disabled:cursor-not-allowed`}
                                                >
                                                    {canAfford ? (
                                                        <>Pay with Wallet <Wallet className="w-4 h-4" /></>
                                                    ) : (
                                                        <>Checkout via Kashier <CreditCard className="w-4 h-4" /></>
                                                    )}
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {/* Custom Amount Tab */}
                        {activeTab === 'custom' && (
                            <div className="max-w-xl mx-auto bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                                <div className="text-center mb-8">
                                    <div className="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-600">
                                        <RefreshCcw className="w-8 h-8" />
                                    </div>
                                    <h3 className="text-2xl font-bold text-slate-900">Choose Your Amount</h3>
                                    <p className="text-slate-500 mt-2">Enter any number of points. Buy more to unlock better rates!</p>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-2">Number of Points</label>
                                        <div className="relative">
                                            <input
                                                type="number"
                                                value={customPoints}
                                                onChange={(e) => setCustomPoints(e.target.value)}
                                                className="w-full text-2xl font-bold border-2 border-slate-200 rounded-xl px-6 py-4 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors"
                                                placeholder="e.g. 500"
                                                min="1"
                                            />
                                            <div className="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 font-medium">
                                                pts
                                            </div>
                                        </div>
                                    </div>

                                    {customPricing && (
                                        <>
                                            {/* Pricing Breakdown */}
                                            <div className="bg-gradient-to-r from-slate-50 to-indigo-50/50 rounded-xl p-6 border border-slate-100 space-y-4">
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-slate-600">Price per point</span>
                                                    <span className="text-sm font-bold text-slate-800">
                                                        <FinancialAmount amount={customPricing.pricePerPoint} currency={globalCurrency} />
                                                    </span>
                                                </div>

                                                {customPricing.discountPercent > 0 && (
                                                    <div className="flex justify-between items-center">
                                                        <span className="text-sm text-slate-600">Full price</span>
                                                        <span className="text-sm text-slate-400 line-through">
                                                            <FinancialAmount amount={customPricing.fullPrice} currency={globalCurrency} />
                                                        </span>
                                                    </div>
                                                )}

                                                <div className="border-t border-slate-200 pt-4 flex justify-between items-end">
                                                    <div>
                                                        <p className="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Cost</p>
                                                        <div className="text-3xl font-extrabold text-slate-900">
                                                            <FinancialAmount amount={customPricing.totalCost} currency={globalCurrency} />
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-xs text-slate-500 font-medium uppercase tracking-wider">Wallet</p>
                                                        <div className={`text-lg font-bold ${wallet_balance >= customPricing.totalCost ? 'text-emerald-600' : 'text-slate-700'}`}>
                                                            <FinancialAmount amount={wallet_balance} currency={globalCurrency} />
                                                        </div>
                                                    </div>
                                                </div>

                                                {customPricing.savings > 0 && (
                                                    <div className="flex items-center justify-center gap-2 py-2 px-4 bg-emerald-50 border border-emerald-100 rounded-lg text-emerald-700 text-sm font-semibold">
                                                        <Sparkles className="w-4 h-4" />
                                                        You save <FinancialAmount amount={customPricing.savings} currency={globalCurrency} /> ({customPricing.discountPercent}% off)
                                                    </div>
                                                )}
                                            </div>

                                            <button
                                                onClick={handleCustomPurchase}
                                                className="w-full flex items-center justify-center gap-2 py-4 px-6 rounded-xl font-bold text-lg bg-gradient-to-r from-indigo-600 to-violet-600 text-white hover:shadow-lg hover:shadow-indigo-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {wallet_balance >= customPricing.totalCost ? (
                                                    <>Confirm Purchase <Wallet className="w-5 h-5" /></>
                                                ) : (
                                                    <>Proceed to Kashier <ArrowRight className="w-5 h-5" /></>
                                                )}
                                            </button>
                                        </>
                                    )}

                                    {!customPricing && customPoints && (
                                        <div className="text-center py-4 text-sm text-slate-400">
                                            Enter a valid number of points to see pricing.
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Transaction History */}
                <div>
                    <div className="flex items-center gap-2 mb-6 px-2">
                        <History className="w-6 h-6 text-slate-400" />
                        <h3 className="text-2xl font-bold text-slate-800">History</h3>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50">
                                    <th className="px-8 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th className="px-8 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Description</th>
                                    <th className="px-8 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Points</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {(!transactions || !transactions.data || transactions.data.length === 0) ? (
                                    <tr>
                                        <td colSpan="3" className="px-8 py-16 text-center">
                                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4 text-slate-300">
                                                <History className="w-8 h-8" />
                                            </div>
                                            <p className="text-slate-500 font-medium">No transactions found.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    transactions.data.map(tx => (
                                        <tr key={tx.id} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="px-8 py-6 whitespace-nowrap text-sm text-slate-500 font-medium">
                                                {formatDate(tx.created_at)}
                                            </td>
                                            <td className="px-8 py-6">
                                                <div className="flex items-center gap-3">
                                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${['purchased', 'earned'].includes(tx.type) ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'
                                                        }`}>
                                                        {['purchased', 'earned'].includes(tx.type) ? <TrendingUp className="w-4 h-4" /> : <TrendingUp className="w-4 h-4 rotate-180" />}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-semibold text-slate-900">{tx.description}</p>
                                                        <span className="text-xs text-slate-500 capitalize">{tx.type}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-8 py-6 text-right">
                                                <span className={`inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold ${['purchased', 'earned'].includes(tx.type) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'
                                                    }`}>
                                                    {['purchased', 'earned'].includes(tx.type) ? '+' : '-'}{formatNumber(tx.points)}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
