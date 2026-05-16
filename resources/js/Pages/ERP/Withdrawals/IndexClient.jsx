import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';

export default function IndexClient({ auth, withdrawals, wallet, lockedAmount }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        payment_method_id: '',
    });

    const [showRequestForm, setShowRequestForm] = useState(false);

    const availableBalance = wallet ? wallet.balance - lockedAmount : 0;

    const submitRequest = (e) => {
        e.preventDefault();
        post(route('erp.withdrawals.store'), {
            onSuccess: () => {
                setShowRequestForm(false);
                reset();
            },
        });
    };

    const handleCancel = (id) => {
        if (confirm('Are you sure you want to cancel this request?')) {
            router.post(route('erp.withdrawals.cancel', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">My Withdrawals</h2>}
        >
            <Head title="Withdrawals" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Balances */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-gray-500 text-sm font-medium">Total Balance</h3>
                            <p className="mt-2 text-3xl font-semibold text-gray-900">
                                {Number(wallet?.balance || 0).toFixed(2)} {wallet?.currency || 'USD'}
                            </p>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-gray-500 text-sm font-medium">Locked (Pending)</h3>
                            <p className="mt-2 text-3xl font-semibold text-yellow-600">
                                {Number(lockedAmount).toFixed(2)} {wallet?.currency || 'USD'}
                            </p>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-gray-500 text-sm font-medium">Available for Withdrawal</h3>
                            <p className="mt-2 text-3xl font-semibold text-green-600">
                                {Number(availableBalance).toFixed(2)} {wallet?.currency || 'USD'}
                            </p>
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <button
                            onClick={() => setShowRequestForm(!showRequestForm)}
                            className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Request Withdrawal
                        </button>
                    </div>

                    {showRequestForm && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Request New Withdrawal</h3>
                            <form onSubmit={submitRequest} className="space-y-4 max-w-md">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Amount</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={data.amount}
                                        onChange={e => setData('amount', e.target.value)}
                                    />
                                    {errors.amount && <div className="text-red-600 mt-1 text-sm">{errors.amount}</div>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Payment Method ID</label>
                                    {/* In a real app, this would be a select dropdown of user's approved payment methods */}
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Enter Payment Method ID"
                                        value={data.payment_method_id}
                                        onChange={e => setData('payment_method_id', e.target.value)}
                                    />
                                    {errors.payment_method_id && <div className="text-red-600 mt-1 text-sm">{errors.payment_method_id}</div>}
                                </div>
                                <button type="submit" disabled={processing} className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Submit Request
                                </button>
                            </form>
                        </div>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Withdrawal History</h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {withdrawals.data.map((withdrawal) => (
                                            <tr key={withdrawal.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(withdrawal.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                    {Number(withdrawal.amount).toFixed(2)} {withdrawal.currency_code}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        ${withdrawal.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''}
                                                        ${withdrawal.status === 'approved' ? 'bg-blue-100 text-blue-800' : ''}
                                                        ${withdrawal.status === 'paid' ? 'bg-green-100 text-green-800' : ''}
                                                        ${withdrawal.status === 'rejected' || withdrawal.status === 'canceled' ? 'bg-red-100 text-red-800' : ''}
                                                    `}>
                                                        {withdrawal.status}
                                                    </span>
                                                    {withdrawal.admin_notes && (
                                                        <div className="text-xs text-gray-500 mt-1">Note: {withdrawal.admin_notes}</div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    {withdrawal.status === 'pending' && (
                                                        <button onClick={() => handleCancel(withdrawal.id)} className="text-red-600 hover:text-red-900">Cancel</button>
                                                    )}
                                                    {withdrawal.status === 'paid' && withdrawal.proof_path && (
                                                        <a href={`/storage/${withdrawal.proof_path}`} target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:text-indigo-900">View Proof</a>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {withdrawals.data.length === 0 && (
                                            <tr>
                                                <td colSpan="4" className="px-6 py-4 text-center text-sm text-gray-500">
                                                    No withdrawals found.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
