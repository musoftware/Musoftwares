import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ orders, tab }: any) {
    const handleTabChange = (newTab: string) => {
        router.get(route('marketplace.orders.index'), { tab: newTab }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">My Orders</h2>}>
            <Head title="Orders" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">

                    {/* Tabs */}
                    <div className="mb-6 flex space-x-4 border-b border-gray-200">
                        <button
                            onClick={() => handleTabChange('purchases')}
                            className={`pb-2 px-1 border-b-2 font-medium text-sm transition ${
                                tab === 'purchases'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            My Purchases
                        </button>
                        <button
                            onClick={() => handleTabChange('sales')}
                            className={`pb-2 px-1 border-b-2 font-medium text-sm transition ${
                                tab === 'sales'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            My Sales
                        </button>
                    </div>

                    {/* Orders List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        {orders.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {orders.data.map((order: any) => (
                                            <tr key={order.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900">{order.package?.service?.title || 'Unknown Service'}</div>
                                                    <div className="text-sm text-gray-500">Pkg: {order.package?.name}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(order.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    ${order.amount}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        ${order.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                          order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                          order.status === 'delivered' ? 'bg-blue-100 text-blue-800' :
                                                          'bg-gray-100 text-gray-800'}`}>
                                                        {order.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <Link href={route('marketplace.orders.show', order.id)} className="text-indigo-600 hover:text-indigo-900">
                                                        View Details
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                No orders found in this category.
                            </div>
                        )}

                        {/* Pagination placeholder */}
                        {orders.links && orders.links.length > 3 && (
                            <div className="mt-6 flex justify-center gap-2">
                                {orders.links.map((link: any, idx: number) => (
                                    <button
                                        key={idx}
                                        onClick={() => {
                                            if (link.url) router.get(link.url, { tab }, { preserveState: true });
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
