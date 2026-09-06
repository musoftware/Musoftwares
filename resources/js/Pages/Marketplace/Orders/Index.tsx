import { StatusBadge } from '@/Components/ui/StatusBadge';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router } from '@inertiajs/react';

import { useEffect } from 'react';
import { useMarketplaceMode } from '@/Components/Marketplace/MarketplaceModeContext';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency, formatDate } from '@/lib/utils';

export default function Index({ orders, tab, auth }: any) {
    const { mode, setMode } = useMarketplaceMode();

    useEffect(() => {
        // Sync context mode with current tab
        if (tab === 'sales' && mode !== 'seller') {
            setMode('seller');
        } else if (tab === 'purchases' && mode !== 'client') {
            setMode('client');
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [tab]);

    useEffect(() => {
        // If mode changes via header toggle, reload the page with correct tab
        if (mode === 'seller' && tab !== 'sales') {
            router.get(route('marketplace.orders.index'), { tab: 'sales' }, { preserveState: true });
        } else if (mode === 'client' && tab === 'sales') {
            router.get(route('marketplace.orders.index'), { tab: 'purchases' }, { preserveState: true });
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
        <MarketplaceLayout>
            <Head title={__('general.orders')} />
            <div className="min-h-screen bg-gray-50 dark:bg-[#090d16] py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Page Heading */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{__('general.orders')}</h1>
                    </div>
                    {/* Tabs */}
                    <div className="mb-6 flex space-x-8 border-b border-gray-200 dark:border-white/10">
                        <button
                            onClick={() => handleTabChange('purchases')}
                            className={`border-b-2 px-1 pb-4 text-base font-medium transition-colors ${
                                tab === 'purchases' || !tab
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-slate-400 hover:border-gray-300 dark:hover:border-white/20 hover:text-gray-700 dark:hover:text-slate-200'
                            }`}
                        >{__('general.as_buyer')}</button>
                        <button
                            onClick={() => handleTabChange('sales')}
                            className={`border-b-2 px-1 pb-4 text-base font-medium transition-colors ${
                                tab === 'sales'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-slate-400 hover:border-gray-300 dark:hover:border-white/20 hover:text-gray-700 dark:hover:text-slate-200'
                            }`}
                        >{__('general.as_seller')}</button>
                    </div>

                    {/* Orders List */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-[#0f172a] shadow-sm">
                        {orders.data && (orders.data as any).length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                                    <thead className="bg-gray-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-start text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >
                                                {__('general.service')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-start text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >
                                                {__('general.package')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-start text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >{__('general.other_party')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-start text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >
                                                {__('general.amount')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-start text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >
                                                {__('general.status')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-start text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >
                                                {__('general.deadline')}</th>
                                            <th
                                                scope="col"
                                                className="px-6 py-4 text-end text-xs font-semibold tracking-wider text-gray-500 dark:text-slate-400 uppercase"
                                            >
                                                {__('general.action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-white/10 bg-white dark:bg-[#0f172a]">
                                        {(orders.data as any).map((order: any) => {
                                            const isBuyer =
                                                tab === 'purchases' || !tab;
                                            const otherParty = isBuyer
                                                ? order.seller
                                                : order.buyer;

                                            // Use actual deadline if available, else format as TBD
                                            const deliveryDays = order.package?.delivery_days;
                                            const orderDate = new Date(order.created_at);
                                            let deadlineDate: Date | null = null;
                                            if (deliveryDays) {
                                                deadlineDate = new Date(orderDate);
                                                deadlineDate.setDate(deadlineDate.getDate() + deliveryDays);
                                            }

                                            return (
                                                <tr
                                                    key={order.id}
                                                    className="transition-colors hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                                                >
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center">
                                                            <div className="me-3 h-10 w-16 flex-shrink-0 overflow-hidden rounded bg-gray-200 dark:bg-slate-800">
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
                                                                    <div className="flex h-full w-full items-center justify-center text-xs text-gray-400 dark:text-slate-500">
                                                                        {__('general.no_image')}
                                                                    </div>
                                                                )}
                                                            </div>
                                                            <div className="line-clamp-2 max-w-[200px] text-sm font-medium text-gray-900 dark:text-white">
                                                                {order.package
                                                                    ?.service
                                                                    ?.title ||
                                                                    'Unknown Service'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {
                                                                order.package
                                                                    ?.name
                                                            }
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="me-2 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                                                {otherParty?.name?.charAt(
                                                                    0,
                                                                ) || '?'}
                                                            </div>
                                                            <div className="text-sm text-gray-900 dark:text-white">
                                                                {otherParty?.name ||
                                                                    'Unknown'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-bold text-gray-900 dark:text-white">
                                                            {formatCurrency(order.amount, order.currency || order.package?.service?.currency)}
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
                                                        <div className="text-sm text-gray-900 dark:text-white">
                                                            {deadlineDate ? formatDate(deadlineDate) : __('general.tbd')}
                                                        </div>
                                                        {deliveryDays && (
                                                            <div className="text-xs text-gray-500 dark:text-slate-400">
                                                                {deliveryDays} {__('general.days')}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-end text-sm font-medium whitespace-nowrap">
                                                        <Link
                                                            href={route(
                                                                'marketplace.orders.show',
                                                                order.id,
                                                             )}
                                                            className="inline-flex items-center rounded border border-gray-300 dark:border-white/10 bg-white dark:bg-[#1e293b] px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-slate-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                                        >
                                                            {__('general.view_order')}{' '}
                                                            <span
                                                                aria-hidden="true"
                                                                className="ms-1"
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
                                    className="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500"
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
                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">{__('general.no_orders_found_1')}</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-slate-400">
                                     {tab === 'sales'
                                         ? __('general.no_sales_yet')
                                         : __('general.no_purchases_yet')}
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
                            <div className="flex justify-center gap-2 border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-slate-900/50 px-6 py-4">
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
                                        className={`rounded-md border px-3 py-1 text-sm font-medium transition ${link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 dark:border-white/10 bg-white dark:bg-[#1e293b] text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700'} ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
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
        </MarketplaceLayout>
    );
}
