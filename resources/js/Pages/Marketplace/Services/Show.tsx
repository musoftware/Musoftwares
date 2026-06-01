import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ service }: any) {
    const { auth } = usePage().props as any;
    const userBalance = auth?.user?.balance || 0; // Mock balance if not properly passed from backend or real

    // Default mock balance for display purposes in case it's not set
    const displayBalance = userBalance > 0 ? userBalance : 125.0;

    const [activeTab, setActiveTab] = useState<'overview' | 'reviews'>(
        'overview',
    );

    // Sort packages Basic, Standard, Premium if names match, otherwise just take what's given.
    const packages = service.packages || [];
    const sortedPackages = packages.sort((a: any, b: any) => a.price - b.price);
    const [selectedPackageId, setSelectedPackageId] = useState<number | null>(
        sortedPackages.length > 0 ? sortedPackages[0].id : null,
    );

    const handleBuyNow = (packageId: number) => {
        router.post(route('marketplace.orders.store'), {
            package_id: packageId,
        });
    };

    const selectedPackage = sortedPackages.find(
        (p: any) => p.id === selectedPackageId,
    );

    return (
        <MarketplaceLayout>
            <Head title={service.title} />

            {/* Breadcrumb */}
            <div className="border-b border-gray-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-3 text-sm text-gray-500 sm:px-6 lg:px-8">
                    <Link
                        href={route('marketplace.services.index')}
                        className="transition-colors hover:text-indigo-600"
                    >
                        Marketplace
                    </Link>
                    <span className="mx-2">/</span>
                    <Link
                        href={route('marketplace.services.index', {
                            category_id: service.category_id,
                        })}
                        className="transition-colors hover:text-indigo-600"
                    >
                        {service.category?.name || 'Category'}
                    </Link>
                    <span className="mx-2">/</span>
                    <span className="font-medium text-gray-900">
                        {service.title}
                    </span>
                </div>
            </div>

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="mx-auto flex max-w-7xl flex-col gap-8 sm:px-6 lg:flex-row lg:px-8">
                    {/* Left Column: 60% */}
                    <div className="w-full lg:w-3/5">
                        {/* Title & basic info */}
                        <h1 className="mb-4 text-3xl font-bold text-gray-900">
                            {service.title}
                        </h1>
                        <div className="mb-6 flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                                    {service.seller?.name?.charAt(0) || '?'}
                                </div>
                                <span className="font-medium text-gray-900">
                                    {service.seller?.name}
                                </span>
                            </div>
                            <div className="flex items-center text-sm text-amber-500">
                                <span className="flex items-center">
                                    <svg
                                        className="mr-1 h-4 w-4"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span className="font-bold text-gray-900">
                                        4.9
                                    </span>
                                </span>
                                <span className="ml-1 text-gray-500">
                                    (128 reviews)
                                </span>
                            </div>
                        </div>

                        {/* Image Gallery */}
                        <div className="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div className="flex aspect-video w-full items-center justify-center bg-gray-100 text-gray-400">
                                {service.cover_image ? (
                                    <img
                                        src={service.cover_image}
                                        alt={service.title}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <span className="text-lg">
                                        [Main Image]
                                    </span>
                                )}
                            </div>
                            <div className="flex gap-2 overflow-x-auto p-2">
                                {[1, 2, 3, 4].map((i) => (
                                    <div
                                        key={i}
                                        className={`h-16 w-24 cursor-pointer rounded border-2 bg-gray-200 ${i === 1 ? 'border-indigo-600' : 'border-transparent hover:border-gray-400'}`}
                                    ></div>
                                ))}
                            </div>
                        </div>

                        {/* Tabs */}
                        <div className="mb-6 border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    onClick={() => setActiveTab('overview')}
                                    className={`border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap transition-colors ${activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'}`}
                                >
                                    Overview
                                </button>
                                <button
                                    onClick={() => setActiveTab('reviews')}
                                    className={`border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap transition-colors ${activeTab === 'reviews' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'}`}
                                >
                                    Reviews (128)
                                </button>
                            </nav>
                        </div>

                        {/* Tab Content */}
                        {activeTab === 'overview' && (
                            <div className="space-y-8">
                                {/* Description */}
                                <div>
                                    <h3 className="mb-4 text-xl font-bold text-gray-900">{__('general.about_this_service')}</h3>
                                    <div className="prose max-w-none text-gray-700">
                                        <p className="whitespace-pre-wrap">
                                            {service.description}
                                        </p>
                                    </div>
                                </div>

                                {/* Seller Card */}
                                <div>
                                    <h3 className="mb-4 text-xl font-bold text-gray-900">{__('general.about_the_seller')}</h3>
                                    <div className="flex flex-col items-center gap-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:flex-row md:items-start">
                                        <div className="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-3xl font-bold text-indigo-700">
                                            {service.seller?.name?.charAt(0) ||
                                                '?'}
                                        </div>
                                        <div className="flex-1 text-center md:text-left">
                                            <h4 className="mb-1 text-xl font-bold text-gray-900">
                                                {service.seller?.name}
                                            </h4>
                                            <div className="mb-2 flex items-center justify-center gap-2 text-sm text-amber-500 md:justify-start">
                                                <svg
                                                    className="h-4 w-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                <span className="font-bold text-gray-900">
                                                    4.9
                                                </span>
                                                <span className="text-gray-500">
                                                    (128 reviews)
                                                </span>
                                            </div>
                                            <p className="mb-4 text-sm text-gray-500">
                                                Member since{' '}
                                                {new Date(
                                                    service.seller?.created_at,
                                                ).getFullYear()}
                                            </p>
                                            <button className="rounded-md border border-indigo-600 px-6 py-2 font-medium text-indigo-600 transition hover:bg-indigo-50">{__('general.contact_me')}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'reviews' && (
                            <div className="py-8 text-center text-gray-500">{__('general.reviews_will_be_displayed_here')}</div>
                        )}
                    </div>

                    {/* Right Column: 40% (Sticky Package Selector) */}
                    <div className="w-full lg:w-2/5">
                        <div className="sticky top-6">
                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                                {sortedPackages.length > 0 ? (
                                    <>
                                        {/* Package Tabs */}
                                        <div className="flex border-b border-gray-200 bg-gray-50">
                                            {sortedPackages.map((pkg: any) => (
                                                <button
                                                    key={pkg.id}
                                                    onClick={() =>
                                                        setSelectedPackageId(
                                                            pkg.id,
                                                        )
                                                    }
                                                    className={`flex-1 border-b-2 px-2 py-4 text-center text-sm font-bold transition-colors ${selectedPackageId === pkg.id ? 'border-indigo-600 bg-white text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
                                                >
                                                    {pkg.name}
                                                </button>
                                            ))}
                                        </div>

                                        {/* Package Details */}
                                        {selectedPackage && (
                                            <div className="p-6">
                                                <div className="mb-4 flex items-start justify-between">
                                                    <h3 className="text-xl font-bold text-gray-900">
                                                        {selectedPackage.name}{' '}
                                                        Package
                                                    </h3>
                                                    <span className="text-2xl font-bold text-gray-900">
                                                        ${Number(selectedPackage.price).toFixed(2)}
                                                    </span>
                                                </div>
                                                <p className="mb-6 text-sm text-gray-600">
                                                    {
                                                        selectedPackage.description
                                                    }
                                                </p>

                                                <div className="mb-6 flex items-center gap-4 text-sm font-bold text-gray-700">
                                                    <div className="flex items-center gap-1">
                                                        <svg
                                                            className="h-5 w-5 text-gray-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                            ></path>
                                                        </svg>
                                                        <span>
                                                            {
                                                                selectedPackage.delivery_days
                                                            }{' '}
                                                            Days Delivery
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <svg
                                                            className="h-5 w-5 text-gray-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                            ></path>
                                                        </svg>
                                                        <span>{__('general.unlimited_revisions')}</span>
                                                    </div>
                                                </div>

                                                <ul className="mb-8 space-y-3">
                                                    <li className="flex items-start text-sm text-gray-600">
                                                        <svg
                                                            className="mr-2 h-5 w-5 shrink-0 text-green-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M5 13l4 4L19 7"
                                                            ></path>
                                                        </svg>
                                                        Feature inclusion 1
                                                    </li>
                                                    <li className="flex items-start text-sm text-gray-600">
                                                        <svg
                                                            className="mr-2 h-5 w-5 shrink-0 text-green-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M5 13l4 4L19 7"
                                                            ></path>
                                                        </svg>
                                                        Feature inclusion 2
                                                    </li>
                                                    <li className="flex items-start text-sm text-gray-600">
                                                        <svg
                                                            className="mr-2 h-5 w-5 shrink-0 text-green-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M5 13l4 4L19 7"
                                                            ></path>
                                                        </svg>
                                                        Feature inclusion 3
                                                    </li>
                                                </ul>

                                                <div className="mb-6 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-4">
                                                    <span className="text-sm font-medium text-gray-700">
                                                        Your balance:
                                                    </span>
                                                    <span className="text-sm font-bold text-gray-900">
                                                        $
                                                        {displayBalance.toFixed(
                                                            2,
                                                        )}
                                                    </span>
                                                </div>

                                                {displayBalance >=
                                                selectedPackage.price ? (
                                                    <button
                                                        onClick={() =>
                                                            handleBuyNow(
                                                                selectedPackage.id,
                                                            )
                                                        }
                                                        className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition hover:bg-indigo-700"
                                                    >
                                                        Continue — $
                                                        {Number(selectedPackage.price).toFixed(2)}
                                                        <svg
                                                            className="h-5 w-5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth="2"
                                                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                                                            ></path>
                                                        </svg>
                                                    </button>
                                                ) : (
                                                    <div className="space-y-3">
                                                        <div className="text-center text-sm font-medium text-red-600">
                                                            Need $
                                                            {(
                                                                selectedPackage.price -
                                                                displayBalance
                                                            ).toFixed(2)}{' '}
                                                            more to purchase
                                                        </div>
                                                        <button className="w-full rounded-lg bg-amber-500 px-4 py-3 font-bold text-white transition hover:bg-amber-600">{__('general.top_up_wallet')}</button>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="p-8 text-center text-gray-500">
                                        No packages available for this service
                                        yet.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </MarketplaceLayout>
    );
}
