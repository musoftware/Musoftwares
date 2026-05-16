import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function MarketplaceDashboard() {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Marketplace</h2>}>
            <Head title="Marketplace Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-bold mb-4">Marketplace</h3>
                        <p>Buy and sell services, manage packages and orders.</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
