import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function All({ services }: any) {
    const handleFeatureToggle = (id: number) => {
        router.post(route('admin.marketplace.services.feature', id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">All Services</h2>}>
            <Head title="All Services" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        {services.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {services.data.map((service: any) => (
                                            <tr key={service.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{service.title}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{service.seller?.name}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        ${service.status === 'active' ? 'bg-green-100 text-green-800' :
                                                          service.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                                          'bg-red-100 text-red-800'}`}>
                                                        {service.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {service.is_featured ? 'Yes' : 'No'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button onClick={() => handleFeatureToggle(service.id)} className="text-indigo-600 hover:text-indigo-900">
                                                        {service.is_featured ? 'Unfeature' : 'Feature'}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                No services found.
                            </div>
                        )}

                        {services.links && services.links.length > 3 && (
                            <div className="mt-6 flex justify-center gap-2">
                                {services.links.map((link: any, idx: number) => (
                                    <button
                                        key={idx}
                                        onClick={() => {
                                            if (link.url) router.get(link.url);
                                        }}
                                        disabled={!link.url}
                                        className={`px-3 py-1 border rounded ${link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
