import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';

export default function IndexAdmin({ auth, withdrawals }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        admin_notes: '',
        reference: '',
        proof: null,
    });

    const [actionModal, setActionModal] = useState({ show: false, type: '', id: null });

    const handleApprove = (id) => {
        router.post(route('erp.withdrawals.approve', id));
    };

    const handleReject = (e) => {
        e.preventDefault();
        post(route('erp.withdrawals.reject', actionModal.id), {
            onSuccess: () => {
                setActionModal({ show: false, type: '', id: null });
                reset();
            },
        });
    };

    const handleMarkPaid = (e) => {
        e.preventDefault();
        post(route('erp.withdrawals.markPaid', actionModal.id), {
            onSuccess: () => {
                setActionModal({ show: false, type: '', id: null });
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Manage Withdrawals</h2>}
        >
            <Head title="Withdrawals" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Info</th>
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
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {withdrawal.client?.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                    {Number(withdrawal.amount).toFixed(2)} {withdrawal.currency_code}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500">
                                                    <div>{withdrawal.payment_method?.bank_name}</div>
                                                    <div>{withdrawal.payment_method?.account_number}</div>
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
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    {withdrawal.status === 'pending' && (
                                                        <>
                                                            <button onClick={() => handleApprove(withdrawal.id)} className="text-indigo-600 hover:text-indigo-900 mr-3">Approve</button>
                                                            <button onClick={() => setActionModal({show: true, type: 'reject', id: withdrawal.id})} className="text-red-600 hover:text-red-900">Reject</button>
                                                        </>
                                                    )}
                                                    {withdrawal.status === 'approved' && (
                                                        <button onClick={() => setActionModal({show: true, type: 'markPaid', id: withdrawal.id})} className="text-green-600 hover:text-green-900">Mark Paid</button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {withdrawals.data.length === 0 && (
                                            <tr>
                                                <td colSpan="6" className="px-6 py-4 text-center text-sm text-gray-500">
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

            {/* Modals for actions */}
            {actionModal.show && (
                <div className="fixed z-10 inset-0 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 transition-opacity" aria-hidden="true">
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            {actionModal.type === 'reject' ? (
                                <form onSubmit={handleReject}>
                                    <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">Reject Withdrawal</h3>
                                        <div className="mt-2">
                                            <textarea
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                                placeholder="Reason for rejection"
                                                value={data.admin_notes}
                                                onChange={e => setData('admin_notes', e.target.value)}
                                            ></textarea>
                                            {errors.admin_notes && <div className="text-red-600 mt-1 text-sm">{errors.admin_notes}</div>}
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" disabled={processing} className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                                            Reject
                                        </button>
                                        <button type="button" onClick={() => setActionModal({show: false, type: '', id: null})} className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            ) : (
                                <form onSubmit={handleMarkPaid}>
                                    <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">Mark as Paid</h3>
                                        <div className="mt-2 space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Reference Number</label>
                                                <input
                                                    type="text"
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    value={data.reference}
                                                    onChange={e => setData('reference', e.target.value)}
                                                    required
                                                />
                                                {errors.reference && <div className="text-red-600 mt-1 text-sm">{errors.reference}</div>}
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Proof of Payment (optional)</label>
                                                <input
                                                    type="file"
                                                    className="mt-1 block w-full"
                                                    onChange={e => setData('proof', e.target.files[0])}
                                                />
                                                {errors.proof && <div className="text-red-600 mt-1 text-sm">{errors.proof}</div>}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" disabled={processing} className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                                            Mark Paid
                                        </button>
                                        <button type="button" onClick={() => setActionModal({show: false, type: '', id: null})} className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
