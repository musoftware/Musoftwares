import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ clients }) {
    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
    };

    return (
        <AuthenticatedLayout>
            <div className="p-6">
                <Head title="Clients" />
                <h1 className="text-3xl font-bold mb-6">Clients</h1>

                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 border-b">
                            <tr>
                                <th className="p-4 font-medium text-gray-600">Name</th>
                                <th className="p-4 font-medium text-gray-600">Email</th>
                                <th className="p-4 font-medium text-gray-600">Phone</th>
                                <th className="p-4 font-medium text-gray-600 text-right">Wallet Balance</th>
                                <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {clients.data.map(client => (
                                <tr key={client.id} className="border-b hover:bg-gray-50">
                                    <td className="p-4">{client.name}</td>
                                    <td className="p-4">{client.email}</td>
                                    <td className="p-4">{client.phone}</td>
                                    <td className="p-4 text-right">
                                        {client.wallet ? formatCurrency(client.wallet.balance, client.wallet.currency) : '$0.00'}
                                    </td>
                                    <td className="p-4 text-right">
                                        <Link href={`/admin/clients/${client.id}`} className="text-blue-600 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {clients.data.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="p-4 text-center text-gray-500">No clients found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination placeholder */}
                <div className="mt-4 flex justify-between items-center text-sm text-gray-600">
                    <span>Showing {clients.from || 0} to {clients.to || 0} of {clients.total} entries</span>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
