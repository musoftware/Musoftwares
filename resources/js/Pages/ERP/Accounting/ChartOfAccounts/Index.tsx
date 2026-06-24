import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function ChartOfAccountsIndex({ accounts }: { accounts: any[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        name: '',
        type: 'asset',
        is_active: true,
    });

    const [isCreating, setIsCreating] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.accounting.chart-of-accounts.store'), {
            onSuccess: () => {
                reset();
                setIsCreating(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Chart of Accounts" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-semibold">Chart of Accounts</h2>
                            <button
                                onClick={() => setIsCreating(!isCreating)}
                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            >
                                {isCreating ? 'Cancel' : 'New Account'}
                            </button>
                        </div>

                        {isCreating && (
                            <form onSubmit={submit} className="mb-8 bg-gray-50 p-4 rounded border">
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Code</label>
                                        <input
                                            type="text"
                                            value={data.code}
                                            onChange={e => setData('code', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Name</label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Type</label>
                                        <select
                                            value={data.type}
                                            onChange={e => setData('type', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        >
                                            <option value="asset">Asset</option>
                                            <option value="liability">Liability</option>
                                            <option value="equity">Equity</option>
                                            <option value="revenue">Revenue</option>
                                            <option value="expense">Expense</option>
                                        </select>
                                    </div>
                                    <div className="flex items-end">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                                        >
                                            Save Account
                                        </button>
                                    </div>
                                </div>
                            </form>
                        )}

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {accounts.map(account => (
                                        <tr key={account.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{account.code}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{account.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm capitalize text-gray-900">{account.type}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${account.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                    {account.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                    {accounts.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-6 py-4 text-center text-sm text-gray-500">
                                                No accounts found. Create one to get started.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
