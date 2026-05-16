import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function FreelanceDashboard() {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Freelance Portal</h2>}>
            <Head title="Freelance Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-bold mb-4">Freelance Portal</h3>
                        <p>Find jobs, submit proposals, manage contracts.</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
