import React from 'react';
import FreelanceLayout from '../Layout';
import { useForm, router } from '@inertiajs/react';

export default function PointsIndex({ auth, packages, transactions }) {
    const { post, processing } = useForm();

    const handlePurchase = (packageId) => {
        if (confirm('Are you sure you want to purchase this point package?')) {
            router.post(route('freelance.point-purchases.store'), { package_id: packageId });
        }
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="max-w-5xl mx-auto">
                <div className="text-center mb-10">
                    <h2 className="text-3xl font-bold text-gray-800 mb-2">Your Points Balance</h2>
                    <div className="text-5xl font-extrabold text-blue-600">
                        {auth.user.points_balance || 0}
                    </div>
                </div>

                <div className="mb-12">
                    <h3 className="text-2xl font-bold mb-6">Buy Points</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {packages?.map(pkg => (
                            <div key={pkg.id} className="border rounded-xl p-6 text-center shadow-sm hover:shadow-md transition">
                                <h4 className="text-xl font-semibold mb-2">{pkg.name}</h4>
                                <div className="text-3xl font-bold text-gray-900 mb-4">{pkg.points} <span className="text-lg text-gray-500 font-normal">pts</span></div>
                                <div className="text-xl text-gray-600 mb-6">{pkg.currency_code} {pkg.price}</div>
                                <button
                                    onClick={() => handlePurchase(pkg.id)}
                                    disabled={processing}
                                    className="bg-blue-600 text-white font-bold py-2 px-6 rounded-full w-full hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Purchase
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <div>
                    <h3 className="text-2xl font-bold mb-6">Transaction History</h3>
                    <div className="bg-white border rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {transactions?.data?.length === 0 ? (
                                    <tr>
                                        <td colSpan="4" className="px-6 py-4 text-center text-gray-500">No transactions found.</td>
                                    </tr>
                                ) : (
                                    transactions?.data?.map(tx => (
                                        <tr key={tx.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(tx.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {tx.description}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                    tx.type === 'earned' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {tx.type}
                                                </span>
                                            </td>
                                            <td className={`px-6 py-4 whitespace-nowrap text-sm font-bold text-right ${
                                                tx.type === 'earned' ? 'text-green-600' : 'text-red-600'
                                            }`}>
                                                {tx.type === 'earned' ? '+' : '-'}{tx.points}
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
