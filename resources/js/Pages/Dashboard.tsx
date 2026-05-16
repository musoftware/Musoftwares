import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const user = usePage().props.auth.user;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-bold mb-4">Welcome back, {user.name}!</h3>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* ERP Module Card */}
                                <div className="border rounded-lg p-4 shadow-sm hover:shadow-md transition">
                                    <h4 className="font-semibold text-blue-600">ERP Workspace</h4>
                                    <p className="text-sm text-gray-600 mt-2">Manage your business, invoices, and clients.</p>
                                </div>

                                {/* Freelance Module Card */}
                                <div className="border rounded-lg p-4 shadow-sm hover:shadow-md transition">
                                    <h4 className="font-semibold text-green-600">Freelance Portal</h4>
                                    <p className="text-sm text-gray-600 mt-2">Find jobs, submit proposals, and manage contracts.</p>
                                </div>

                                {/* Marketplace Module Card */}
                                <div className="border rounded-lg p-4 shadow-sm hover:shadow-md transition">
                                    <h4 className="font-semibold text-purple-600">Marketplace</h4>
                                    <p className="text-sm text-gray-600 mt-2">Buy and sell services Fiverr-style.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
