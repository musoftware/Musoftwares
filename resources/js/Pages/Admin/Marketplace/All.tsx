import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router } from '@inertiajs/react';

export default function All({ auth, services }: any) {
    const handleFeatureToggle = (id: number) => {
        router.post(route('admin.marketplace.services.feature', id));
    };

    return (
        <AdminSidebarLayout user={auth?.user} title="All Services" header="All Services">
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        {services.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Title
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Seller
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Featured
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {services.data.map((service: any) => (
                                            <tr key={service.id}>
                                                <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                                    {service.title}
                                                </td>
                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                    {service.seller?.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                                                            service.status ===
                                                            'active'
                                                                ? 'bg-green-100 text-green-800'
                                                                : service.status ===
                                                                    'draft'
                                                                  ? 'bg-yellow-100 text-yellow-800'
                                                                  : 'bg-red-100 text-red-800'
                                                        }`}
                                                    >
                                                        {service.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                    {service.is_featured
                                                        ? 'Yes'
                                                        : 'No'}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                                    <button
                                                        onClick={() =>
                                                            handleFeatureToggle(
                                                                service.id,
                                                            )
                                                        }
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        {service.is_featured
                                                            ? 'Unfeature'
                                                            : 'Feature'}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="py-12 text-center text-gray-500">
                                No services found.
                            </div>
                        )}

                        {services.links && services.links.length > 3 && (
                            <div className="mt-6 flex justify-center gap-2">
                                {services.links.map(
                                    (link: any, idx: number) => (
                                        <button
                                            key={idx}
                                            onClick={() => {
                                                if (link.url)
                                                    router.get(link.url);
                                            }}
                                            disabled={!link.url}
                                            className={`rounded border px-3 py-1 ${link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'}`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
