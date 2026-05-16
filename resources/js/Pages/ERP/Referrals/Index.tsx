import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ clients }: { clients: any[] }) {
    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Referrals</h2>}
        >
            <Head title="Referrals" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Clients & Referral Links</h3>
                            <Link href={route('erp.referrals.earnings')} className="text-indigo-600 hover:text-indigo-900">View Earnings</Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referred By</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referral Link</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {clients.map((client) => (
                                        <tr key={client.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{client.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{client.referrer?.name || '-'}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {client.referral_code ? (
                                                    <div className="flex items-center space-x-2">
                                                        <input
                                                            type="text"
                                                            readOnly
                                                            value={`${window.location.origin}/register?ref=${client.referral_code}`}
                                                            className="text-xs border-gray-300 rounded"
                                                        />
                                                    </div>
                                                ) : '-'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <Link href={route('erp.referrals.tree', client.id)} className="text-indigo-600 hover:text-indigo-900">View Tree</Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
