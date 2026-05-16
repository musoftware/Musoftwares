import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

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
            header={
                <h2 className="text-xl leading-tight font-semibold text-gray-800">
                    Payment Methods
                </h2>
            }
        >
            <Head title="Payment Methods" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-medium text-gray-900">
                            Bank Accounts
                        </h3>
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase ring-indigo-300 transition duration-150 ease-in-out hover:bg-indigo-700 focus:border-indigo-900 focus:ring focus:outline-none active:bg-indigo-900 disabled:opacity-25"
                        >
                            {showForm ? 'Cancel' : 'Add New Account'}
                        </button>
                    </div>

                    {showForm && (
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <form
                                onSubmit={submitForm}
                                className="max-w-lg space-y-4"
                            >
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Bank Name
                                    </label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value={data.bank_name}
                                        onChange={(e) =>
                                            setData('bank_name', e.target.value)
                                        }
                                    />
                                    {errors.bank_name && (
                                        <div className="mt-1 text-sm text-red-600">
                                            {errors.bank_name}
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Account Name
                                    </label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value={data.account_name}
                                        onChange={(e) =>
                                            setData(
                                                'account_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.account_name && (
                                        <div className="mt-1 text-sm text-red-600">
                                            {errors.account_name}
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Account Number
                                    </label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value={data.account_number}
                                        onChange={(e) =>
                                            setData(
                                                'account_number',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.account_number && (
                                        <div className="mt-1 text-sm text-red-600">
                                            {errors.account_number}
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        SWIFT/BIC Code (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value={data.swift_code}
                                        onChange={(e) =>
                                            setData(
                                                'swift_code',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.swift_code && (
                                        <div className="mt-1 text-sm text-red-600">
                                            {errors.swift_code}
                                        </div>
                                    )}
                                </div>
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        checked={data.is_default}
                                        onChange={(e) =>
                                            setData(
                                                'is_default',
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    <label className="ml-2 block text-sm text-gray-900">
                                        Set as default payment method
                                    </label>
                                </div>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                >
                                    Save Account
                                </button>
                            </form>
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {paymentMethods.map((method) => (
                            <div
                                key={method.id}
                                className={`overflow-hidden border-l-4 bg-white p-6 shadow-sm sm:rounded-lg ${method.is_default ? 'border-green-500' : 'border-gray-200'}`}
                            >
                                <div className="flex items-start justify-between">
                                    <div>
                                        <h4 className="text-lg font-bold text-gray-900">
                                            {method.bank_name}
                                        </h4>
                                        <p className="mt-1 text-sm text-gray-600">
                                            {method.account_name}
                                        </p>
                                        <p className="mt-2 font-mono text-sm text-gray-800">
                                            {method.account_number}
                                        </p>
                                        {method.swift_code && (
                                            <p className="mt-1 text-xs text-gray-500">
                                                SWIFT: {method.swift_code}
                                            </p>
                                        )}

                                        <div className="mt-4">
                                            <span
                                                className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${method.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''} ${method.status === 'approved' ? 'bg-green-100 text-green-800' : ''} ${method.status === 'rejected' ? 'bg-red-100 text-red-800' : ''} `}
                                            >
                                                {method.status
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                    method.status.slice(1)}
                                            </span>
                                            {method.is_default && (
                                                <span className="ml-2 inline-flex rounded-full bg-indigo-100 px-2 text-xs leading-5 font-semibold text-indigo-800">
                                                    Default
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex flex-col space-y-2">
                                        <button
                                            onClick={() =>
                                                handleSetDefault(
                                                    method.id,
                                                    method.is_default,
                                                )
                                            }
                                            className="text-right text-xs text-indigo-600 hover:text-indigo-900"
                                        >
                                            {method.is_default
                                                ? 'Remove Default'
                                                : 'Set Default'}
                                        </button>
                                        <button
                                            onClick={() =>
                                                handleDelete(method.id)
                                            }
                                            className="text-right text-xs text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}

                        {paymentMethods.length === 0 && !showForm && (
                            <div className="col-span-full rounded-lg bg-white p-6 text-center text-gray-500 shadow-sm">
                                No payment methods added yet. Add one to receive
                                withdrawals.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
