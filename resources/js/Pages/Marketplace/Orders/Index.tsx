import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import StatusBadge from '@/Components/StatusBadge';

export default function Index({ orders, tab }: any) {
    const handleTabChange = (newTab: string) => {
        router.get(route('marketplace.orders.index'), { tab: newTab }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-2xl font-bold leading-tight text-gray-800">Orders</h2>}>
            <Head title="Orders" />
            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">

                    {/* Tabs */}
                    <div className="mb-6 flex space-x-8 border-b border-gray-200">
                        <button
                            onClick={() => handleTabChange('purchases')}
                            className={`pb-4 px-1 border-b-2 font-medium text-base transition-colors ${
                                tab === 'purchases' || !tab
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            As Buyer
                        </button>
                        <button
                            onClick={() => handleTabChange('sales')}
                            className={`pb-4 px-1 border-b-2 font-medium text-base transition-colors ${
                                tab === 'sales'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            As Seller
                        </button>
                    </div>

                    {/* Orders List */}
                    <div className="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                        {orders.data && orders.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Service</th>
                                            <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Package</th>
                                            <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Other Party</th>
                                            <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deadline</th>
                                            <th scope="col" className="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {orders.data.map((order: any) => {
                                            const isBuyer = tab === 'purchases' || !tab;
                                            const otherParty = isBuyer ? order.seller : order.buyer;

                                            // Calculate deadline mock if not present
                                            const deliveryDays = order.package?.delivery_days || 7;
                                            const orderDate = new Date(order.created_at);
                                            const deadlineDate = new Date(orderDate);
                                            deadlineDate.setDate(deadlineDate.getDate() + deliveryDays);

                                            return (
                                                <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center">
                                                            <div className="h-10 w-16 bg-gray-200 rounded overflow-hidden mr-3 flex-shrink-0">
                                                                {order.package?.service?.cover_image ? (
                                                                    <img src={order.package.service.cover_image} alt="" className="h-full w-full object-cover" />
                                                                ) : (
                                                                    <div className="h-full w-full flex items-center justify-center text-gray-400 text-xs">Img</div>
                                                                )}
                                                            </div>
                                                            <div className="text-sm font-medium text-gray-900 line-clamp-2 max-w-[200px]">
                                                                {order.package?.service?.title || 'Unknown Service'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900 font-medium">{order.package?.name}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="h-6 w-6 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700 mr-2">
                                                                {otherParty?.name?.charAt(0) || '?'}
                                                            </div>
                                                            <div className="text-sm text-gray-900">{otherParty?.name || 'Unknown'}</div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-bold text-gray-900">${order.amount}</div>
                                                        <div className="text-xs text-gray-500">{order.currency_code || 'USD'}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <StatusBadge status={order.status} />
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900">
                                                            {deadlineDate.toLocaleDateString()}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {deliveryDays} days
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('marketplace.orders.show', order.id)}
                                                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                        >
                                                            View Order <span aria-hidden="true" className="ml-1">&rarr;</span>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-16 px-4 sm:px-6 lg:px-8">
                                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No orders found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    You don't have any {tab === 'sales' ? 'sales' : 'purchases'} yet.
                                </p>
                                {(!tab || tab === 'purchases') && (
                                    <div className="mt-6">
                                        <Link href={route('marketplace.services.index')} className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Browse Services
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Pagination */}
                        {orders.links && orders.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-200 flex justify-center gap-2 bg-gray-50">
                                {orders.links.map((link: any, idx: number) => (
                                    <button
                                        key={idx}
                                        onClick={() => {
                                            if (link.url) router.get(link.url, { tab }, { preserveState: true });
                                        }}
                                        disabled={!link.url}
                                        className={`px-3 py-1 border rounded-md text-sm font-medium transition ${link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'} ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
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
