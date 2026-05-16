import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function IndexClient({
    auth,
    withdrawals,
    wallet,
    lockedAmount,
}) {
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
            header={
                <h2 className="text-xl leading-tight font-semibold text-gray-800">
                    My Withdrawals
                </h2>
            }
        >
            <Head title="Withdrawals" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Balances */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-sm font-medium text-gray-500">
                                Total Balance
                            </h3>
                            <p className="mt-2 text-3xl font-semibold text-gray-900">
                                {Number(wallet?.balance || 0).toFixed(2)}{' '}
                                {wallet?.currency || 'USD'}
                            </p>
                        </div>
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-sm font-medium text-gray-500">
                                Locked (Pending)
                            </h3>
                            <p className="mt-2 text-3xl font-semibold text-yellow-600">
                                {Number(lockedAmount).toFixed(2)}{' '}
                                {wallet?.currency || 'USD'}
                            </p>
                        </div>
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-sm font-medium text-gray-500">
                                Available for Withdrawal
                            </h3>
                            <p className="mt-2 text-3xl font-semibold text-green-600">
                                {Number(availableBalance).toFixed(2)}{' '}
                                {wallet?.currency || 'USD'}
                            </p>
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <button
                            onClick={() => setShowRequestForm(!showRequestForm)}
                            className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase ring-gray-300 transition duration-150 ease-in-out hover:bg-gray-700 focus:border-gray-900 focus:ring focus:outline-none active:bg-gray-900 disabled:opacity-25"
                        >
                            Request Withdrawal
                        </button>
                    </div>

                    {showRequestForm && (
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">
                                Request New Withdrawal
                            </h3>
                            <form
                                onSubmit={submitRequest}
                                className="max-w-md space-y-4"
                            >
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Amount
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value={data.amount}
                                        onChange={(e) =>
                                            setData('amount', e.target.value)
                                        }
                                    />
                                    {errors.amount && (
                                        <div className="mt-1 text-sm text-red-600">
                                            {errors.amount}
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Payment Method ID
                                    </label>
                                    {/* In a real app, this would be a select dropdown of user's approved payment methods */}
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Enter Payment Method ID"
                                        value={data.payment_method_id}
                                        onChange={(e) =>
                                            setData(
                                                'payment_method_id',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.payment_method_id && (
                                        <div className="mt-1 text-sm text-red-600">
                                            {errors.payment_method_id}
                                        </div>
                                    )}
                                </div>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                >
                                    Submit Request
                                </button>
                            </form>
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 bg-white p-6">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">
                                Withdrawal History
                            </h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Date
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Amount
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {withdrawals.data.map((withdrawal) => (
                                            <tr key={withdrawal.id}>
                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                    {new Date(
                                                        withdrawal.created_at,
                                                    ).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                                    {Number(
                                                        withdrawal.amount,
                                                    ).toFixed(2)}{' '}
                                                    {withdrawal.currency_code}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${withdrawal.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''} ${withdrawal.status === 'approved' ? 'bg-blue-100 text-blue-800' : ''} ${withdrawal.status === 'paid' ? 'bg-green-100 text-green-800' : ''} ${withdrawal.status === 'rejected' || withdrawal.status === 'canceled' ? 'bg-red-100 text-red-800' : ''} `}
                                                    >
                                                        {withdrawal.status}
                                                    </span>
                                                    {withdrawal.admin_notes && (
                                                        <div className="mt-1 text-xs text-gray-500">
                                                            Note:{' '}
                                                            {
                                                                withdrawal.admin_notes
                                                            }
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                                    {withdrawal.status ===
                                                        'pending' && (
                                                        <button
                                                            onClick={() =>
                                                                handleCancel(
                                                                    withdrawal.id,
                                                                )
                                                            }
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            Cancel
                                                        </button>
                                                    )}
                                                    {withdrawal.status ===
                                                        'paid' &&
                                                        withdrawal.proof_path && (
                                                            <a
                                                                href={`/storage/${withdrawal.proof_path}`}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="text-indigo-600 hover:text-indigo-900"
                                                            >
                                                                View Proof
                                                            </a>
                                                        )}
                                                </td>
                                            </tr>
                                        ))}
                                        {withdrawals.data.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan="4"
                                                    className="px-6 py-4 text-center text-sm text-gray-500"
                                                >
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
