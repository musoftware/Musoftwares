import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate, formatMoney } from '@/lib/utils';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Index({ services }: any) {
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
                    Marketplace Services
                </h2>
            }
        >
            <Head title="Services" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="relative overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        {loading && (
                            <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/50 backdrop-blur-sm">
                                <div className="font-medium text-indigo-600">
                                    Loading...
                                </div>
                            </div>
                        )}
                        <h3 className="mb-4 text-lg font-bold">Services</h3>

                        {services.data.length > 0 ? (
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {services.data.map((service: any) => (
                                    <div
                                        key={service.id}
                                        className="rounded-lg border p-4 shadow-sm"
                                    >
                                        <h4 className="text-lg font-semibold">
                                            {service.title}
                                        </h4>
                                        <p className="mt-1 text-sm text-gray-500">
                                            by{' '}
                                            {service.seller?.name || 'Unknown'}
                                        </p>
                                        <div className="mt-4 flex items-center justify-between">
                                            <span className="font-medium text-indigo-600">
                                                {formatMoney(
                                                    service.price,
                                                    service.currency_code ||
                                                        'USD',
                                                )}
                                            </span>
                                            <span className="text-xs text-gray-400">
                                                {formatDate(service.created_at)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-12 text-center">
                                <p className="text-gray-500">
                                    No services found.
                                </p>
                            </div>
                        )}

                        <div className="mt-6">
                            <Pagination links={services.links} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
