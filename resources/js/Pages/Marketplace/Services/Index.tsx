import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { formatMoney, formatDate } from '@/lib/utils';
import Pagination from '@/Components/Pagination';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

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
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Marketplace Services
                </h2>
            }
        >
            <Head title="Services" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 relative">
                        {loading && (
                            <div className="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center">
                                <div className="text-indigo-600 font-medium">Loading...</div>
                            </div>
                        )}
                        <h3 className="text-lg font-bold mb-4">Services</h3>

                        {services.data.length > 0 ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {services.data.map((service: any) => (
                                    <div key={service.id} className="border rounded-lg p-4 shadow-sm">
                                        <h4 className="font-semibold text-lg">{service.title}</h4>
                                        <p className="text-gray-500 text-sm mt-1">by {service.seller?.name || 'Unknown'}</p>
                                        <div className="mt-4 flex justify-between items-center">
                                            <span className="font-medium text-indigo-600">
                                                {formatMoney(service.price, service.currency_code || 'USD')}
                                            </span>
                                            <span className="text-xs text-gray-400">
                                                {formatDate(service.created_at)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <p className="text-gray-500">No services found.</p>
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
