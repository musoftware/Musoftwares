import { StatusBadge } from '@/Components/ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

import { useEffect } from 'react';
import { useMarketplaceMode } from '@/Components/Marketplace/MarketplaceModeContext';

export default function Index({ orders, tab }: any) {
    const { mode, setMode } = useMarketplaceMode();

    useEffect(() => {
        // Sync context mode with current tab
        if (tab === 'sales' && mode !== 'seller') {
            setMode('seller');
        } else if (tab === 'purchases' && mode !== 'client') {
            setMode('client');
        }
    }, [tab]);

    useEffect(() => {
        // If mode changes via header toggle, reload the page with correct tab
        if (mode === 'seller' && tab !== 'sales') {
            router.get(route('marketplace.orders.index'), { tab: 'sales' }, { preserveState: true });
        } else if (mode === 'client' && tab === 'sales') {
            router.get(route('marketplace.orders.index'), { tab: 'purchases' }, { preserveState: true });
        }
    }, [mode]);

    const handleTabChange = (newTab: string) => {
        setMode(newTab === 'sales' ? 'seller' : 'client');
        router.get(
            route('marketplace.orders.index'),
            { tab: newTab },
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-2xl leading-tight font-bold text-gray-800">
                    Orders
                </h2>
            }
        >
            <Head title="Orders" />
            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Tabs */}
                    <div className="mb-6 flex space-x-8 border-b border-gray-200">
                        <button
                            onClick={() => handleTabChange('purchases')}
                            className={`border-b-2 px-1 pb-4 text-base font-medium transition-colors ${
                                tab === 'purchases' || !tab
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            }`}
                        >{__('general.as_buyer')}</button>
                        <button
                            onClick={() => handleTabChange('sales')}
                            className={`border-b-2 px-1 pb-4 text-base font-medium transition-colors ${
                                tab === 'sales'
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            }`}
                        >{__('general.as_seller')}</button>
                    </div>

                    {/* Orders List */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        {orders.data && orders.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >
                                                Service
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >
                                                Package
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >{__('general.other_party')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >
                                                Amount
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >
                                                Status
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >
                                                Deadline
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-right text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                            >
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {orders.data.map((order: any) => {
                                            const isBuyer =
                                                tab === 'purchases' || !tab;
                                            const otherParty = isBuyer
                                                ? order.seller
                                                : order.buyer;

                                            // Calculate deadline mock if not present
                                            const deliveryDays =
                                                order.package?.delivery_days ||
                                                7;
                                            const orderDate = new Date(
                                                order.created_at,
                                            );
                                            const deadlineDate = new Date(
                                                orderDate,
                                            );
                                            deadlineDate.setDate(
                                                deadlineDate.getDate() +
                                                    deliveryDays,
                                            );

                                            return (
                                                <tr
                                                    key={order.id}
                                                    className="transition-colors hover:bg-gray-50"
                                                >
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center">
                                                            <div className="mr-3 h-10 w-16 flex-shrink-0 overflow-hidden rounded bg-gray-200">
                                                                {order.package
                                                                    ?.service
                                                                    ?.cover_image ? (
                                                                    <img
                                                                        src={
                                                                            order
                                                                                .package
                                                                                .service
                                                                                .cover_image
                                                                        }
                                                                        alt=""
                                                                        className="h-full w-full object-cover"
                                                                    />
                                                                ) : (
                                                                    <div className="flex h-full w-full items-center justify-center text-xs text-gray-400">
                                                                        Img
                                                                    </div>
                                                                )}
                                                            </div>
                                                            <div className="line-clamp-2 max-w-[200px] text-sm font-medium text-gray-900">
                                                                {order.package
                                                                    ?.service
                                                                    ?.title ||
                                                                    'Unknown Service'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {
                                                                order.package
                                                                    ?.name
                                                            }
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                                                {otherParty?.name?.charAt(
                                                                    0,
                                                                ) || '?'}
                                                            </div>
                                                            <div className="text-sm text-gray-900">
                                                                {otherParty?.name ||
                                                                    'Unknown'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-bold text-gray-900">
                                                            {order.formatted_amount}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <StatusBadge
                                                            status={
                                                                order.status
                                                            }
                                                        />
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900">
                                                            {deadlineDate.toLocaleDateString()}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {deliveryDays} days
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                                        <Link
                                                            href={route(
                                                                'marketplace.orders.show',
                                                                order.id,
                                                            )}
                                                            className="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                                        >
                                                            View Order{' '}
                                                            <span
                                                                aria-hidden="true"
                                                                className="ml-1"
                                                            >
                                                                &rarr;
                                                            </span>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="px-4 py-16 text-center sm:px-6 lg:px-8">
                                <svg
                                    className="mx-auto h-12 w-12 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    ></path>
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-gray-900">{__('general.no_orders_found_1')}</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    You don't have any{' '}
                                    {tab === 'sales' ? 'sales' : 'purchases'}{' '}
                                    yet.
                                </p>
                                {(!tab || tab === 'purchases') && (
                                    <div className="mt-6">
                                        <Link
                                            href={route(
                                                'marketplace.services.index',
                                            )}
                                            className="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                        >{__('general.browse_services')}</Link>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Pagination */}
                        {orders.links && orders.links.length > 3 && (
                            <div className="flex justify-center gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                {orders.links.map((link: any, idx: number) => (
                                    <button
                                        key={idx}
                                        onClick={() => {
                                            if (link.url)
                                                router.get(
                                                    link.url,
                                                    { tab },
                                                    { preserveState: true },
                                                );
                                        }}
                                        disabled={!link.url}
                                        className={`rounded-md border px-3 py-1 text-sm font-medium transition ${link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'} ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
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
