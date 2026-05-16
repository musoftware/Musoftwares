import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function MarketplaceDashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Marketplace
                </h2>
            }
        >
            <Head title="Marketplace Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-lg font-bold">Marketplace</h3>
                        <p>
                            Buy and sell services, manage packages and orders.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
