import { router, useForm } from '@inertiajs/react';
import FreelanceLayout from '../Layout';

export default function PointsIndex({ auth, packages, transactions }) {
    const { post, processing } = useForm();

    const handlePurchase = (packageId) => {
        if (confirm('Are you sure you want to purchase this point package?')) {
            router.post(route('freelance.point-purchases.store'), {
                package_id: packageId,
            });
        }
    };

    return (
        <FreelanceLayout auth={auth}>
            <div className="mx-auto max-w-5xl">
                <div className="mb-10 text-center">
                    <h2 className="mb-2 text-3xl font-bold text-gray-800">
                        Your Points Balance
                    </h2>
                    <div className="text-5xl font-extrabold text-blue-600">
                        {auth.user.points_balance || 0}
                    </div>
                </div>

                <div className="mb-12">
                    <h3 className="mb-6 text-2xl font-bold">Buy Points</h3>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        {packages?.map((pkg) => (
                            <div
                                key={pkg.id}
                                className="rounded-xl border p-6 text-center shadow-sm transition hover:shadow-md"
                            >
                                <h4 className="mb-2 text-xl font-semibold">
                                    {pkg.name}
                                </h4>
                                <div className="mb-4 text-3xl font-bold text-gray-900">
                                    {pkg.points}{' '}
                                    <span className="text-lg font-normal text-gray-500">
                                        pts
                                    </span>
                                </div>
                                <div className="mb-6 text-xl text-gray-600">
                                    {pkg.currency_code} {pkg.price}
                                </div>
                                <button
                                    onClick={() => handlePurchase(pkg.id)}
                                    disabled={processing}
                                    className="w-full rounded-full bg-blue-600 px-6 py-2 font-bold text-white hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Purchase
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <div>
                    <h3 className="mb-6 text-2xl font-bold">
                        Transaction History
                    </h3>
                    <div className="overflow-hidden rounded-lg border bg-white">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        Description
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        Type
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                                        Points
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {transactions?.data?.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan="4"
                                            className="px-6 py-4 text-center text-gray-500"
                                        >
                                            No transactions found.
                                        </td>
                                    </tr>
                                ) : (
                                    transactions?.data?.map((tx) => (
                                        <tr key={tx.id}>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                {new Date(
                                                    tx.created_at,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {tx.description}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                                                        tx.type === 'earned'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    }`}
                                                >
                                                    {tx.type}
                                                </span>
                                            </td>
                                            <td
                                                className={`px-6 py-4 text-right text-sm font-bold whitespace-nowrap ${
                                                    tx.type === 'earned'
                                                        ? 'text-green-600'
                                                        : 'text-red-600'
                                                }`}
                                            >
                                                {tx.type === 'earned'
                                                    ? '+'
                                                    : '-'}
                                                {tx.points}
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
