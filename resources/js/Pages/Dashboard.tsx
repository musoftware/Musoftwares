import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

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
                            <h3 className="mb-4 text-lg font-bold">
                                Welcome back, {user.name}!
                            </h3>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                {/* ERP Module Card */}
                                <Link
                                    href={route('erp.dashboard')}
                                    className="rounded-lg border p-6 shadow-sm transition hover:scale-105 hover:shadow-md"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <h4 className="text-xl font-bold text-blue-600">
                                            ERP Workspace
                                        </h4>
                                        <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                            Business
                                        </span>
                                    </div>
                                    <p className="text-sm text-gray-600">
                                        Manage your business operations, including invoices, clients, and financial tracking.
                                    </p>
                                    <div className="mt-4 text-blue-600 font-medium text-sm">Open Workspace →</div>
                                </Link>

                                {/* Freelance Module Card */}
                                <Link
                                    href={route('freelance.dashboard')}
                                    className="rounded-lg border p-6 shadow-sm transition hover:scale-105 hover:shadow-md"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <h4 className="text-xl font-bold text-green-600">
                                            Freelance Portal
                                        </h4>
                                        <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                            Jobs
                                        </span>
                                    </div>
                                    <p className="text-sm text-gray-600">
                                        Browse available jobs, submit professional proposals, and manage your active contracts.
                                    </p>
                                    <div className="mt-4 text-green-600 font-medium text-sm">Find Work →</div>
                                </Link>

                                {/* Marketplace Module Card */}
                                <Link
                                    href={route('marketplace.dashboard')}
                                    className="rounded-lg border p-6 shadow-sm transition hover:scale-105 hover:shadow-md"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <h4 className="text-xl font-bold text-purple-600">
                                            Marketplace
                                        </h4>
                                        <span className="rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-800">
                                            Store
                                        </span>
                                    </div>
                                    <p className="text-sm text-gray-600">
                                        Buy and sell high-quality services in our marketplace environment.
                                    </p>
                                    <div className="mt-4 text-purple-600 font-medium text-sm">Visit Store →</div>
                                </Link>

                                {user.role === 'admin' && (
                                    <Link
                                        href={route('admin.dashboard')}
                                        className="rounded-lg border border-red-200 bg-red-50 p-6 shadow-sm transition hover:scale-105 hover:shadow-md md:col-span-3"
                                    >
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-xl font-bold text-red-600">
                                                System Administration
                                            </h4>
                                            <span className="rounded-full bg-red-600 px-3 py-1 text-xs font-medium text-white">
                                                Admin Only
                                            </span>
                                        </div>
                                        <p className="mt-2 text-sm text-gray-600">
                                            Access global system settings, user management, and detailed analytical reports.
                                        </p>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
