import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

function TreeNode({ client }: { client: any }) {
    return (
        <div className="ml-6 mt-2">
            <div className="flex items-center space-x-2">
                <span className="text-gray-500">↳</span>
                <span className="font-medium">{client.name}</span>
                <span className="text-xs text-gray-500">({client.email})</span>
            </div>
            {client.referrals && client.referrals.length > 0 && (
                <div className="border-l-2 border-gray-200 ml-2 pl-2">
                    {client.referrals.map((child: any) => (
                        <TreeNode key={child.id} client={child} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Tree({ client }: { client: any }) {
    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Referral Tree: {client.name}</h2>}
        >
            <Head title={`Referral Tree - ${client.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="mb-4">
                            <Link href={route('erp.referrals.index')} className="text-indigo-600 hover:text-indigo-900">&larr; Back to Referrals</Link>
                        </div>

                        <div className="p-4 bg-gray-50 rounded-lg border">
                            <div className="font-bold text-lg text-indigo-700">{client.name}</div>
                            {client.referrals && client.referrals.length > 0 ? (
                                client.referrals.map((child: any) => (
                                    <TreeNode key={child.id} client={child} />
                                ))
                            ) : (
                                <p className="text-gray-500 mt-2 italic">No referrals yet.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
