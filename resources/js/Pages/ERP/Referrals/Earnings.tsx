import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';

export default function Earnings({ earnings }: { earnings: any }) {
    const { menuItems, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title="Referral Earnings" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="mb-4">
                            <Link
                                href={route('erp.referrals.index')}
                                className="text-indigo-600 hover:text-indigo-900"
                            >
                                &larr; Back to Referrals
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Client (Earner)
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Referred Client
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Amount
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {earnings.data.map((earning: any) => (
                                        <tr key={earning.id}>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                {new Date(
                                                    earning.created_at,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                                {earning.client?.name}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                {earning.referred_client?.name}
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-green-600">
                                                {earning.amount}{' '}
                                                {earning.currency}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap">
                                                <span
                                                    className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${earning.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}
                                                >
                                                    {earning.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                    {earnings.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-6 py-4 text-center text-sm text-gray-500"
                                            >
                                                No earnings recorded.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
