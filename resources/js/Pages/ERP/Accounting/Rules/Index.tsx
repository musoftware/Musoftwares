import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function RulesIndex({ rules, accounts }: { rules: any[], accounts: any[] }) {
    const { data, setData, post, processing, reset } = useForm({
        event_name: '',
        debit_account_id: '',
        credit_account_id: '',
    });

    const [isCreating, setIsCreating] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.accounting.rules.store'), {
            onSuccess: () => {
                reset();
                setIsCreating(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Accounting Rules" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-semibold">Automated Accounting Rules</h2>
                            <button
                                onClick={() => setIsCreating(!isCreating)}
                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            >
                                {isCreating ? 'Cancel' : 'New Rule'}
                            </button>
                        </div>

                        {isCreating && (
                            <form onSubmit={submit} className="mb-8 bg-gray-50 p-4 rounded border">
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Event Name</label>
                                        <input
                                            type="text"
                                            value={data.event_name}
                                            onChange={e => setData('event_name', e.target.value)}
                                            placeholder="e.g. InvoicePaid"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Debit Account</label>
                                        <select
                                            value={data.debit_account_id}
                                            onChange={e => setData('debit_account_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        >
                                            <option value="">Select Account</option>
                                            {accounts.map(acc => (
                                                <option key={acc.id} value={acc.id}>{acc.code} - {acc.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Credit Account</label>
                                        <select
                                            value={data.credit_account_id}
                                            onChange={e => setData('credit_account_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        >
                                            <option value="">Select Account</option>
                                            {accounts.map(acc => (
                                                <option key={acc.id} value={acc.id}>{acc.code} - {acc.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="flex items-end">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                                        >
                                            Save Rule
                                        </button>
                                    </div>
                                </div>
                            </form>
                        )}

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debit Account</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Account</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {rules.map(rule => (
                                        <tr key={rule.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{rule.event_name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">{rule.debit_account?.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">{rule.credit_account?.name}</td>
                                        </tr>
                                    ))}
                                    {rules.length === 0 && (
                                        <tr>
                                            <td colSpan={3} className="px-6 py-4 text-center text-sm text-gray-500">
                                                No rules found.
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
