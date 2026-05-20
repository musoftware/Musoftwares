import React, { useState } from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router, usePage } from '@inertiajs/react';
import { formatMoney, formatNumber, formatDate } from '../../../lib/utils';
import { CreditCard, Wallet, ArrowRight, CheckCircle2, History, AlertCircle, Zap, TrendingUp, RefreshCcw } from 'lucide-react';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';

export default function PointsIndex({ auth, packages, transactions }) {
    const { wallet, flash } = usePage().props;
    const wallet_balance = wallet ? Number(wallet.balance) : 0;
    const { processing } = useForm();
    const [customPoints, setCustomPoints] = useState('');
    const [activeTab, setActiveTab] = useState('packages'); // packages, custom
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    const displayPackages = packages?.length ? packages : [
        { id: 1, name: 'Starter', points: 100, price: 9.99, currency_code: '$' },
        { id: 2, name: 'Pro', points: 300, price: 24.99, currency_code: '$', is_popular: true },
        { id: 3, name: 'Power', points: 700, price: 49.99, currency_code: '$' },
    ];

    const handlePurchase = (pkg) => {
        const canUseWallet = wallet_balance >= pkg.price;
        const msg = canUseWallet 
            ? `Pay ${formatMoney(pkg.price)} from your wallet balance to buy ${pkg.points} points?`
            : `You'll be redirected to Kashier to securely pay ${formatMoney(pkg.price)}. Continue?`;
            
        if (confirm(msg)) {
            router.post(route('freelance.point-purchases.store'), { package_id: pkg.id });
        }
    };

    const handleWalletPurchase = () => {
        if (!customPoints || isNaN(customPoints) || Number(customPoints) <= 0) return;
        const cost = Number(customPoints) * 0.10;
        const canUseWallet = wallet_balance >= cost;
        const msg = canUseWallet 
            ? `Pay ${formatMoney(cost)} from your wallet balance to buy ${customPoints} points?`
            : `You'll be redirected to Kashier to securely pay ${formatMoney(cost)}. Continue?`;

        if (confirm(msg)) {
            router.post(route('freelance.point-purchases.store-wallet'), { points: customPoints });
        }
    };

    const customCost = customPoints && !isNaN(customPoints) ? (Number(customPoints) * 0.10) : 0;

    return (
        <FreelanceLayout>
            <div className="w-full pb-20 space-y-8">
                
                {/* Header & Balance Card */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 bg-slate-900 text-white rounded-3xl p-8 relative overflow-hidden shadow-2xl">
                        <div className="absolute top-0 right-0 p-8 opacity-10">
                            <Zap className="w-48 h-48" />
                        </div>
                        <div className="relative z-10 flex flex-col h-full justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-sm font-medium mb-6 border border-slate-700">
                                    <TrendingUp className="w-4 h-4" />
                                    Active Points Balance
                                </div>
                                <h1 className="text-5xl lg:text-7xl font-extrabold tracking-tight mb-2">
                                    {formatNumber(auth.user.points_balance || 0)} <span className="text-2xl text-slate-400 font-semibold tracking-normal">pts</span>
                                </h1>
                                <p className="text-slate-400 text-lg max-w-md mt-4 leading-relaxed">
                                    Use points to apply for premium jobs, feature your proposals, and unlock exclusive marketplace tools.
                                </p>
                            </div>
                            
                            <div className="mt-8 flex flex-wrap gap-4 items-center">
                                <div className="flex items-center gap-2 bg-slate-800/50 backdrop-blur rounded-xl px-4 py-3 border border-slate-700/50">
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
                    
                    {/* Estimations */}
                    <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm flex flex-col justify-center">
                        <h3 className="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                            <CheckCircle2 className="w-5 h-5 text-indigo-600" />
                            What can you do?
                        </h3>
                        <div className="space-y-6">
                            <div className="group relative">
                                <div className="flex justify-between items-end mb-2">
                                    <span className="text-slate-600 font-medium">Job Applications</span>
                                    <span className="text-2xl font-bold text-slate-900">≈ {Math.floor((auth.user.points_balance || 0) / 10)}</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-indigo-500 h-2 rounded-full" style={{ width: '100%' }}></div>
                                </div>
                                <p className="text-xs text-slate-500 mt-2">10 pts per application</p>
                            </div>
                            <div className="group relative">
                                <div className="flex justify-between items-end mb-2">
                                    <span className="text-slate-600 font-medium">Proposal Boosts</span>
                                    <span className="text-2xl font-bold text-slate-900">≈ {Math.floor((auth.user.points_balance || 0) / 25)}</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-sky-500 h-2 rounded-full" style={{ width: '100%' }}></div>
                                </div>
                                <p className="text-xs text-slate-500 mt-2">25 pts per boost</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    {/* Tabs */}
                    <div className="flex border-b border-slate-100">
                        <button 
                            onClick={() => setActiveTab('packages')}
                            className={`flex-1 py-5 text-center font-semibold text-lg transition-colors ${activeTab === 'packages' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50/30' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Select Package
                        </button>
                        <button 
                            onClick={() => setActiveTab('custom')}
                            className={`flex-1 py-5 text-center font-semibold text-lg transition-colors ${activeTab === 'custom' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50/30' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Custom Amount
                        </button>
                    </div>

                    <div className="p-8 lg:p-12 bg-slate-50/50">
                        {activeTab === 'packages' && (
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                                {displayPackages.map(pkg => {
                                    const canAffordWithWallet = wallet_balance >= pkg.price;
                                    
                                    return (
                                        <div key={pkg.id} className={`relative bg-white rounded-2xl border-2 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl ${pkg.is_popular ? 'border-indigo-500 shadow-lg' : 'border-slate-100 shadow-sm'}`}>
                                            {pkg.is_popular && (
                                                <div className="absolute -top-4 left-0 right-0 flex justify-center">
                                                    <span className="bg-indigo-500 text-white text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-sm">
                                                        Most Popular
                                                    </span>
                                                </div>
                                            )}
                                            <div className="p-8 text-center">
                                                <h4 className="text-slate-500 font-semibold uppercase tracking-widest text-sm mb-4">{pkg.name}</h4>
                                                <div className="text-4xl font-extrabold text-slate-900 mb-2 flex items-baseline justify-center gap-1">
                                                    {formatNumber(pkg.points)}
                                                    <span className="text-lg text-slate-400 font-medium">pts</span>
                                                </div>
                                                <div className="text-xl text-slate-600 font-medium mb-8">
                                                    <FinancialAmount amount={pkg.price} currency={globalCurrency} />
                                                </div>
                                                
                                                <button
                                                    onClick={() => handlePurchase(pkg)}
                                                    disabled={processing}
                                                    className={`w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl font-semibold transition-all ${
                                                        pkg.is_popular 
                                                            ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md hover:shadow-lg' 
                                                            : 'bg-slate-900 text-white hover:bg-slate-800'
                                                    } disabled:opacity-70 disabled:cursor-not-allowed`}
                                                >
                                                    {canAffordWithWallet ? (
                                                        <>Pay with Wallet <Wallet className="w-4 h-4" /></>
                                                    ) : (
                                                        <>Checkout securely <CreditCard className="w-4 h-4" /></>
                                                    )}
                                                </button>
                                                
                                                {!canAffordWithWallet && (
                                                    <p className="text-xs text-slate-400 mt-4 flex items-center justify-center gap-1">
                                                        via Kashier Checkout
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {activeTab === 'custom' && (
                            <div className="max-w-xl mx-auto bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                                <div className="text-center mb-8">
                                    <div className="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-600">
                                        <RefreshCcw className="w-8 h-8" />
                                    </div>
                                    <h3 className="text-2xl font-bold text-slate-900">Need a specific amount?</h3>
                                    <p className="text-slate-500 mt-2">Enter the exact number of points you want to purchase.</p>
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
                                                placeholder="e.g. 150"
                                                min="1"
                                            />
                                            <div className="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 font-medium">
                                                pts
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="bg-slate-50 rounded-xl p-6 border border-slate-100 flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-slate-500 font-medium">Total Cost</p>
                                            <div className="text-3xl font-extrabold text-slate-900"><FinancialAmount amount={customCost} currency={globalCurrency} /></div>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm text-slate-500 font-medium">Wallet Balance</p>
                                            <div className={`text-lg font-bold ${wallet_balance >= customCost ? 'text-emerald-600' : 'text-slate-700'}`}>
                                                <FinancialAmount amount={wallet_balance} currency={globalCurrency} />
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        onClick={handleWalletPurchase}
                                        disabled={!customPoints || customPoints <= 0 || processing}
                                        className="w-full flex items-center justify-center gap-2 py-4 px-6 rounded-xl font-bold text-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {wallet_balance >= customCost ? (
                                            <>Confirm Purchase <Wallet className="w-5 h-5" /></>
                                        ) : (
                                            <>Proceed to Checkout <ArrowRight className="w-5 h-5" /></>
                                        )}
                                    </button>
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
                                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                                                        ['purchased', 'earned'].includes(tx.type) ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'
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
                                                <span className={`inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold ${
                                                    ['purchased', 'earned'].includes(tx.type) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'
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
        </FreelanceLayout>
    );
}
