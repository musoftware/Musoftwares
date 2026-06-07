import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function IndexAdmin({ auth, withdrawals }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        admin_notes: '',
        reference: '',
        proof: null,
    });

    const [actionModal, setActionModal] = useState({
        show: false,
        type: '',
        id: null,
    });

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
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('transactions');

    return (
        <ERPLayout title="Withdrawals" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 bg-white p-6">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Date
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Client
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Amount
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">{__('general.bank_info')}</th>
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
                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                    {withdrawal.client?.name}
                                                </td>
                                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                                    {formatCurrency(withdrawal.amount, withdrawal.currency)}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500">
                                                    <div>
                                                        {
                                                            withdrawal
                                                                .payment_method
                                                                ?.bank_name
                                                        }
                                                    </div>
                                                    <div>
                                                        {
                                                            withdrawal
                                                                .payment_method
                                                                ?.account_number
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${withdrawal.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''} ${withdrawal.status === 'approved' ? 'bg-blue-100 text-blue-800' : ''} ${withdrawal.status === 'paid' ? 'bg-green-100 text-green-800' : ''} ${withdrawal.status === 'rejected' || withdrawal.status === 'canceled' ? 'bg-red-100 text-red-800' : ''} `}
                                                    >
                                                        {withdrawal.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                                    {withdrawal.status ===
                                                        'pending' && (
                                                        <>
                                                            <button
                                                                onClick={() =>
                                                                    handleApprove(
                                                                        withdrawal.id,
                                                                    )
                                                                }
                                                                className="mr-3 text-indigo-600 hover:text-indigo-900"
                                                            >
                                                                Approve
                                                            </button>
                                                            <button
                                                                onClick={() =>
                                                                    setActionModal(
                                                                        {
                                                                            show: true,
                                                                            type: 'reject',
                                                                            id: withdrawal.id,
                                                                        },
                                                                    )
                                                                }
                                                                className="text-red-600 hover:text-red-900"
                                                            >
                                                                Reject
                                                            </button>
                                                        </>
                                                    )}
                                                    {withdrawal.status ===
                                                        'approved' && (
                                                        <button
                                                            onClick={() =>
                                                                setActionModal({
                                                                    show: true,
                                                                    type: 'markPaid',
                                                                    id: withdrawal.id,
                                                                })
                                                            }
                                                            className="text-green-600 hover:text-green-900"
                                                        >{__('general.mark_paid')}</button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {withdrawals.data.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan="6"
                                                    className="px-6 py-4 text-center text-sm text-gray-500"
                                                >{__('general.no_withdrawals_found')}</td>
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
                <div className="fixed inset-0 z-10 overflow-y-auto">
                    <div className="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div
                            className="fixed inset-0 transition-opacity"
                            aria-hidden="true"
                        >
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>
                        <span
                            className="hidden sm:inline-block sm:h-screen sm:align-middle"
                            aria-hidden="true"
                        >
                            &#8203;
                        </span>
                        <div className="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                            {actionModal.type === 'reject' ? (
                                <form onSubmit={handleReject}>
                                    <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">{__('general.reject_withdrawal')}</h3>
                                        <div className="mt-2">
                                            <textarea
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                                placeholder={__('general.reason_for_rejection')}
                                                value={data.admin_notes}
                                                onChange={(e) =>
                                                    setData(
                                                        'admin_notes',
                                                        e.target.value,
                                                    )
                                                }
                                            ></textarea>
                                            {errors.admin_notes && (
                                                <div className="mt-1 text-sm text-red-600">
                                                    {errors.admin_notes}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm"
                                        >
                                            Reject
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setActionModal({
                                                    show: false,
                                                    type: '',
                                                    id: null,
                                                })
                                            }
                                            className="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            ) : (
                                <form onSubmit={handleMarkPaid}>
                                    <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">{__('general.mark_as_paid')}</h3>
                                        <div className="mt-2 space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">{__('general.reference_number')}</label>
                                                <input
                                                    type="text"
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    value={data.reference}
                                                    onChange={(e) =>
                                                        setData(
                                                            'reference',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                {errors.reference && (
                                                    <div className="mt-1 text-sm text-red-600">
                                                        {errors.reference}
                                                    </div>
                                                )}
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">
                                                    Proof of Payment (optional)
                                                </label>
                                                <input
                                                    type="file"
                                                    className="mt-1 block w-full"
                                                    onChange={(e) =>
                                                        setData(
                                                            'proof',
                                                            e.target.files[0],
                                                        )
                                                    }
                                                />
                                                {errors.proof && (
                                                    <div className="mt-1 text-sm text-red-600">
                                                        {errors.proof}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm"
                                        >{__('general.mark_paid')}</button>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setActionModal({
                                                    show: false,
                                                    type: '',
                                                    id: null,
                                                })
                                            }
                                            className="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </ERPLayout>
    );
}
