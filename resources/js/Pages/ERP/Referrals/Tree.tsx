import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link } from '@inertiajs/react';

function TreeNode({ client }: { client: any }) {
    return (
        <div className="mt-2 ml-6">
            <div className="flex items-center space-x-2">
                <span className="text-gray-500">↳</span>
                <span className="font-medium">{client.name}</span>
                <span className="text-xs text-gray-500">({client.email})</span>
            </div>
            {client.referrals && client.referrals.length > 0 && (
                <div className="ml-2 border-l-2 border-gray-200 pl-2">
                    {client.referrals.map((child: any) => (
                        <TreeNode key={child.id} client={child} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Tree({ client }: { client: any }) {
    const { menuItems, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={`Referral Tree — ${client.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

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

                        <div className="rounded-lg border bg-gray-50 p-4">
                            <div className="text-lg font-bold text-indigo-700">
                                {client.name}
                            </div>
                            {client.referrals && client.referrals.length > 0 ? (
                                client.referrals.map((child: any) => (
                                    <TreeNode key={child.id} client={child} />
                                ))
                            ) : (
                                <p className="mt-2 text-gray-500 italic">
                                    No referrals yet.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}
