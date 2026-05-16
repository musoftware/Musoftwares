import React, { useState } from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router } from '@inertiajs/react';

export default function PointsIndex({ auth, packages, transactions, wallet_balance = 125.00 }) {
    const { post, processing } = useForm();
    const [customPoints, setCustomPoints] = useState('');

    // Fallback packages if not provided by backend to match mockup
    const displayPackages = packages?.length ? packages : [
        { id: 1, name: 'Starter', points: 100, price: 9.99, currency_code: '$' },
        { id: 2, name: 'Pro', points: 300, price: 24.99, currency_code: '$', is_popular: true },
        { id: 3, name: 'Power', points: 700, price: 49.99, currency_code: '$' },
    ];

    const handlePurchase = (packageId) => {
        if (confirm('Are you sure you want to purchase this point package?')) {
            router.post(route('freelance.point-purchases.store'), { package_id: packageId });
        }
    };

    const handleWalletPurchase = () => {
        if (!customPoints || isNaN(customPoints) || Number(customPoints) <= 0) return;
        if (confirm(`Are you sure you want to purchase ${customPoints} points with your wallet balance?`)) {
            router.post(route('freelance.point-purchases.store-wallet'), { points: customPoints });
        }
    };

    const customCost = customPoints && !isNaN(customPoints) ? (Number(customPoints) * 0.10).toFixed(2) : '0.00';

    return (
        <FreelanceLayout auth={auth}>
            <div className="max-w-5xl mx-auto">
                <div className="text-center mb-10 bg-indigo-50 py-10 rounded-2xl shadow-sm border border-indigo-100">
                    <div className="text-6xl font-extrabold text-indigo-600 mb-2 flex items-center justify-center gap-3">
                        <span>💎</span>
                        <span>{auth.user.points_balance || 340} Points</span>
                    </div>
                    <p className="text-gray-600 text-lg">
                        ≈ {Math.floor((auth.user.points_balance || 340) / 10)} job applications or {Math.floor((auth.user.points_balance || 340) / 20)} job posts
                    </p>
                </div>

                <div className="mb-12">
                    <h3 className="text-2xl font-bold mb-6">Buy Points</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {displayPackages.map(pkg => (
                            <div key={pkg.id} className={`relative border rounded-xl p-6 text-center transition ${pkg.is_popular ? 'border-indigo-500 shadow-lg bg-indigo-50/30' : 'shadow-sm hover:shadow-md bg-white border-gray-200'}`}>
                                {pkg.is_popular && (
                                    <div className="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                                        Most Popular
                                    </div>
                                )}
                                <h4 className="text-xl font-semibold mb-2">{pkg.name}</h4>
                                <div className="text-3xl font-bold text-gray-900 mb-4">{pkg.points} <span className="text-lg text-gray-500 font-normal">pts</span></div>
                                <div className="text-xl text-gray-600 mb-6">{pkg.currency_code || '$'}{pkg.price}</div>
                                <button
                                    onClick={() => handlePurchase(pkg.id)}
                                    disabled={processing}
                                    className={`${pkg.is_popular ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-800 hover:bg-gray-900'} text-white font-bold py-2 px-6 rounded-full w-full disabled:opacity-50`}
                                >
                                    Buy Now
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Buy with Wallet Section */}
                <div className="mb-12 bg-white border border-gray-200 rounded-xl p-8 shadow-sm">
                    <h3 className="text-xl font-bold mb-4">Or use your wallet: ${wallet_balance.toFixed(2)} available</h3>
                    <div className="flex flex-col sm:flex-row items-end gap-4">
                        <div className="flex-grow">
                            <label className="block text-sm font-medium text-gray-700 mb-1">How many points?</label>
                            <input
                                type="number"
                                value={customPoints}
                                onChange={(e) => setCustomPoints(e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter amount..."
                                min="1"
                            />
                        </div>
                        <div className="flex items-center h-10 text-gray-600 font-medium px-4">
                            = ${customCost} from your balance
                        </div>
                        <button
                            onClick={handleWalletPurchase}
                            disabled={!customPoints || processing || customCost > wallet_balance}
                            className="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-6 rounded-lg disabled:opacity-50 whitespace-nowrap"
                        >
                            Buy with Wallet
                        </button>
                    </div>
                </div>

                <div>
                    <h3 className="text-2xl font-bold mb-6">Transaction History</h3>
                    <div className="bg-white border rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {transactions?.data?.length === 0 || !transactions ? (
                                    <tr>
                                        <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                            <div className="flex flex-col items-center">
                                                <span className="text-3xl mb-2">📊</span>
                                                <p>No transactions found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    transactions?.data?.map(tx => (
                                        <tr key={tx.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(tx.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-2">
                                                    {tx.type === 'purchased' && <span className="text-green-500">➕</span>}
                                                    {tx.type === 'spent' && <span className="text-red-500">➖</span>}
                                                    {tx.type === 'refunded' && <span className="text-blue-500">↩️</span>}
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                        tx.type === 'purchased' || tx.type === 'refunded' || tx.type === 'earned' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {tx.type || 'transaction'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {tx.description}
                                            </td>
                                            <td className={`px-6 py-4 whitespace-nowrap text-sm font-bold text-right ${
                                                tx.type === 'purchased' || tx.type === 'refunded' || tx.type === 'earned' ? 'text-green-600' : 'text-red-600'
                                            }`}>
                                                {tx.type === 'purchased' || tx.type === 'refunded' || tx.type === 'earned' ? '+' : '-'}{tx.points}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 font-medium">
                                                {tx.balance_after}
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
