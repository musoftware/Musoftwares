import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { formatMoney, formatDate } from '@/lib/utils';
import Pagination from '@/Components/Pagination';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function Index({ jobs }: any) {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setLoading(true));
        const removeFinish = router.on('finish', () => setLoading(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Jobs Portal</h2>}>
            <Head title="Freelance Jobs" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 relative">
                        {loading && (
                            <div className="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center">
                                <div className="text-indigo-600 font-medium">Loading...</div>
                            </div>
                        )}
                        <h3 className="text-lg font-bold mb-4">Jobs</h3>

                        {jobs.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {jobs.data.map((job: any) => (
                                            <tr key={job.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{job.title}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {formatMoney(job.budget, job.currency_code || 'USD')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {formatDate(job.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <p className="text-gray-500">No jobs found.</p>
                            </div>
                        )}

                        <Pagination links={jobs.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
