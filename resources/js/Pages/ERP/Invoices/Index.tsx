import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { formatMoney, formatDate } from '@/lib/utils';
import Pagination from '@/Components/Pagination';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function Index({ invoices }: any) {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setLoading(true));
        const removeFinish = router.on('finish', () => setLoading(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Invoices</h2>}>
            <Head title="Invoices" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 relative">
                        {loading && (
                            <div className="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center">
                                <div className="text-indigo-600 font-medium">Loading...</div>
                            </div>
                        )}
                        <h3 className="text-lg font-bold mb-4">Invoices List</h3>

                        {invoices.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {invoices.data.map((invoice: any) => (
                                            <tr key={invoice.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{invoice.number}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{invoice.client?.name || 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {formatMoney(invoice.amount, invoice.currency_code || 'USD')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {formatDate(invoice.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <p className="text-gray-500">No invoices found.</p>
                            </div>
                        )}

                        <Pagination links={invoices.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
