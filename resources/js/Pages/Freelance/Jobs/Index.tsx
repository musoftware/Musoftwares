import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index({ jobs }: any) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Jobs Portal
                </h2>
            }
        >
            <Head title="Freelance Jobs" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-lg font-bold">Jobs</h3>
                        <ul>
                            {jobs.data.map((job: any) => (
                                <li key={job.id} className="border-b py-2">
                                    {job.title} - {job.budget}{' '}
                                    {job.currency_code}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
