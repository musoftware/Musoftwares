import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';

export default function Index({ auth, paymentMethods }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        bank_name: '',
        account_number: '',
        account_name: '',
        swift_code: '',
        is_default: false,
    });

    const [showForm, setShowForm] = useState(false);

    const submitForm = (e) => {
        e.preventDefault();
        post(route('erp.payment-methods.store'), {
            onSuccess: () => {
                setShowForm(false);
                reset();
            },
        });
    };

    const handleSetDefault = (id, currentDefault) => {
        router.patch(route('erp.payment-methods.update', id), {
            is_default: !currentDefault,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this payment method?')) {
            router.delete(route('erp.payment-methods.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Payment Methods</h2>}
        >
            <Head title="Payment Methods" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    <div className="flex justify-between items-center">
                        <h3 className="text-lg font-medium text-gray-900">Bank Accounts</h3>
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            {showForm ? 'Cancel' : 'Add New Account'}
                        </button>
                    </div>

                    {showForm && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <form onSubmit={submitForm} className="space-y-4 max-w-lg">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Bank Name</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={data.bank_name}
                                        onChange={e => setData('bank_name', e.target.value)}
                                    />
                                    {errors.bank_name && <div className="text-red-600 mt-1 text-sm">{errors.bank_name}</div>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Account Name</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={data.account_name}
                                        onChange={e => setData('account_name', e.target.value)}
                                    />
                                    {errors.account_name && <div className="text-red-600 mt-1 text-sm">{errors.account_name}</div>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Account Number</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={data.account_number}
                                        onChange={e => setData('account_number', e.target.value)}
                                    />
                                    {errors.account_number && <div className="text-red-600 mt-1 text-sm">{errors.account_number}</div>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">SWIFT/BIC Code (Optional)</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={data.swift_code}
                                        onChange={e => setData('swift_code', e.target.value)}
                                    />
                                    {errors.swift_code && <div className="text-red-600 mt-1 text-sm">{errors.swift_code}</div>}
                                </div>
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        checked={data.is_default}
                                        onChange={e => setData('is_default', e.target.checked)}
                                    />
                                    <label className="ml-2 block text-sm text-gray-900">Set as default payment method</label>
                                </div>
                                <button type="submit" disabled={processing} className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Save Account
                                </button>
                            </form>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {paymentMethods.map((method) => (
                            <div key={method.id} className={`bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 ${method.is_default ? 'border-green-500' : 'border-gray-200'}`}>
                                <div className="flex justify-between items-start">
                                    <div>
                                        <h4 className="text-lg font-bold text-gray-900">{method.bank_name}</h4>
                                        <p className="text-sm text-gray-600 mt-1">{method.account_name}</p>
                                        <p className="text-sm font-mono text-gray-800 mt-2">{method.account_number}</p>
                                        {method.swift_code && <p className="text-xs text-gray-500 mt-1">SWIFT: {method.swift_code}</p>}

                                        <div className="mt-4">
                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                ${method.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''}
                                                ${method.status === 'approved' ? 'bg-green-100 text-green-800' : ''}
                                                ${method.status === 'rejected' ? 'bg-red-100 text-red-800' : ''}
                                            `}>
                                                {method.status.charAt(0).toUpperCase() + method.status.slice(1)}
                                            </span>
                                            {method.is_default && (
                                                <span className="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                    Default
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex flex-col space-y-2">
                                        <button
                                            onClick={() => handleSetDefault(method.id, method.is_default)}
                                            className="text-xs text-indigo-600 hover:text-indigo-900 text-right"
                                        >
                                            {method.is_default ? 'Remove Default' : 'Set Default'}
                                        </button>
                                        <button
                                            onClick={() => handleDelete(method.id)}
                                            className="text-xs text-red-600 hover:text-red-900 text-right"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}

                        {paymentMethods.length === 0 && !showForm && (
                            <div className="col-span-full bg-white p-6 rounded-lg shadow-sm text-center text-gray-500">
                                No payment methods added yet. Add one to receive withdrawals.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
