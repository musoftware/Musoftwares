import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index({ services }: any) {
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
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-lg font-bold">Services</h3>
                        <ul>
                            {services.data.map((service: any) => (
                                <li key={service.id} className="border-b py-2">
                                    {service.title} by {service.seller?.name}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
