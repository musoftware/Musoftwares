import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate } from '@/lib/utils';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { __ } from '@/lib/i18n';

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
        <AuthenticatedLayout
            header={
                <h2 className="text-xl leading-tight font-semibold text-gray-800">
                    {__('freelance.jobs_portal')}
                </h2>
            }
        >
            <Head title={__('freelance.freelance_jobs')} />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="relative overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        {loading && (
                            <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/50 backdrop-blur-sm">
                                <div className="font-medium text-indigo-600">
                                    {__('Loading...')}
                                </div>
                            </div>
                        )}
                        <h3 className="mb-4 text-lg font-bold">{__('freelance.jobs')}</h3>

                        {(jobs.data as any).length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                {__('general.title')}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                {__('erp.budget')}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                {__('general.created_at')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {(jobs.data as any).map((job: any) => (
                                            <tr key={job.id}>
                                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                                    {job.title}
                                                </td>
                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                    <FinancialAmount amount={job.budget} currency={job.currency} />
                                                </td>
                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                    {formatDate(job.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="py-12 text-center">
                                <p className="text-gray-500">{__('freelance.no_jobs_found_2')}</p>
                            </div>
                        )}

                        <Pagination links={jobs.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
