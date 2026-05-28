import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';

export default function Index({ clients }: { clients: any[] }) {
    const { menuItems, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title="Referrals" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="mb-6 flex items-center justify-between">
                            <h3 className="text-lg font-medium text-gray-900">
                                Clients & Referral Links
                            </h3>
                            <Link
                                href={route('erp.referrals.earnings')}
                                className="text-indigo-600 hover:text-indigo-900"
                            >
                                View Earnings
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Client
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Referred By
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Referral Link
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {clients.map((client) => (
                                        <tr key={client.id}>
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                                {client.name}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                {client.referrer?.name || '-'}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                {client.referral_code ? (
                                                    <div className="flex items-center space-x-2">
                                                        <input
                                                            type="text"
                                                            readOnly
                                                            value={`${window.location.origin}/register?ref=${client.referral_code}`}
                                                            className="rounded border-gray-300 text-xs"
                                                        />
                                                    </div>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                                <Link
                                                    href={route(
                                                        'erp.referrals.tree',
                                                        client.id,
                                                    )}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View Tree
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
