import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ service }: any) {
    const { auth } = usePage().props as any;
    const userBalance = auth?.user?.balance || 0; // Mock balance if not properly passed from backend or real

    // Default mock balance for display purposes in case it's not set
    const displayBalance = userBalance > 0 ? userBalance : 125.00;

    const [activeTab, setActiveTab] = useState<'overview' | 'reviews'>('overview');

    // Sort packages Basic, Standard, Premium if names match, otherwise just take what's given.
    const packages = service.packages || [];
    const sortedPackages = packages.sort((a: any, b: any) => a.price - b.price);
    const [selectedPackageId, setSelectedPackageId] = useState<number | null>(
        sortedPackages.length > 0 ? sortedPackages[0].id : null
    );

    const handleBuyNow = (packageId: number) => {
        router.post(route('marketplace.orders.store'), { package_id: packageId });
    };

    const selectedPackage = sortedPackages.find((p: any) => p.id === selectedPackageId);

    return (
        <MarketplaceLayout>
            <Head title={service.title} />

            {/* Breadcrumb */}
            <div className="bg-white border-b border-gray-200">
                <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8 text-sm text-gray-500">
                    <Link href={route('marketplace.services.index')} className="hover:text-indigo-600 transition-colors">Marketplace</Link>
                    <span className="mx-2">/</span>
                    <Link href={route('marketplace.services.index', { category_id: service.category_id })} className="hover:text-indigo-600 transition-colors">{service.category?.name || 'Category'}</Link>
                    <span className="mx-2">/</span>
                    <span className="text-gray-900 font-medium">{service.title}</span>
                </div>
            </div>

            <div className="py-8 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-8">

                    {/* Left Column: 60% */}
                    <div className="w-full lg:w-3/5">
                        {/* Title & basic info */}
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">{service.title}</h1>
                        <div className="flex items-center gap-4 mb-6">
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-700">
                                    {service.seller?.name?.charAt(0) || '?'}
                                </div>
                                <span className="font-medium text-gray-900">{service.seller?.name}</span>
                            </div>
                            <div className="flex items-center text-amber-500 text-sm">
                                <span className="flex items-center">
                                    <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                    <span className="font-bold text-gray-900">4.9</span>
                                </span>
                                <span className="text-gray-500 ml-1">(128 reviews)</span>
                            </div>
                        </div>

                        {/* Image Gallery */}
                        <div className="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 mb-8">
                            <div className="aspect-video bg-gray-100 w-full flex items-center justify-center text-gray-400">
                                {service.cover_image ? (
                                    <img src={service.cover_image} alt={service.title} className="w-full h-full object-cover" />
                                ) : (
                                    <span className="text-lg">[Main Image]</span>
                                )}
                            </div>
                            <div className="flex p-2 gap-2 overflow-x-auto">
                                {[1, 2, 3, 4].map(i => (
                                    <div key={i} className={`w-24 h-16 bg-gray-200 rounded cursor-pointer border-2 ${i === 1 ? 'border-indigo-600' : 'border-transparent hover:border-gray-400'}`}></div>
                                ))}
                            </div>
                        </div>

                        {/* Tabs */}
                        <div className="border-b border-gray-200 mb-6">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    onClick={() => setActiveTab('overview')}
                                    className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors ${activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`}
                                >
                                    Overview
                                </button>
                                <button
                                    onClick={() => setActiveTab('reviews')}
                                    className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors ${activeTab === 'reviews' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`}
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
                                    <h3 className="text-xl font-bold text-gray-900 mb-4">About This Service</h3>
                                    <div className="prose max-w-none text-gray-700">
                                        <p className="whitespace-pre-wrap">{service.description}</p>
                                    </div>
                                </div>

                                {/* Seller Card */}
                                <div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-4">About The Seller</h3>
                                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row items-center md:items-start gap-6">
                                        <div className="w-24 h-24 rounded-full bg-indigo-100 flex-shrink-0 flex items-center justify-center text-3xl font-bold text-indigo-700">
                                            {service.seller?.name?.charAt(0) || '?'}
                                        </div>
                                        <div className="flex-1 text-center md:text-left">
                                            <h4 className="text-xl font-bold text-gray-900 mb-1">{service.seller?.name}</h4>
                                            <div className="flex items-center justify-center md:justify-start gap-2 text-amber-500 text-sm mb-2">
                                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                <span className="font-bold text-gray-900">4.9</span>
                                                <span className="text-gray-500">(128 reviews)</span>
                                            </div>
                                            <p className="text-sm text-gray-500 mb-4">Member since {new Date(service.seller?.created_at).getFullYear()}</p>
                                            <button className="border border-indigo-600 text-indigo-600 px-6 py-2 rounded-md hover:bg-indigo-50 transition font-medium">
                                                Contact Me
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'reviews' && (
                            <div className="text-gray-500 py-8 text-center">
                                Reviews will be displayed here.
                            </div>
                        )}
                    </div>

                    {/* Right Column: 40% (Sticky Package Selector) */}
                    <div className="w-full lg:w-2/5">
                        <div className="sticky top-6">
                            <div className="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                                {sortedPackages.length > 0 ? (
                                    <>
                                        {/* Package Tabs */}
                                        <div className="flex border-b border-gray-200 bg-gray-50">
                                            {sortedPackages.map((pkg: any) => (
                                                <button
                                                    key={pkg.id}
                                                    onClick={() => setSelectedPackageId(pkg.id)}
                                                    className={`flex-1 py-4 px-2 text-sm font-bold text-center border-b-2 transition-colors ${selectedPackageId === pkg.id ? 'border-indigo-600 text-indigo-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
                                                >
                                                    {pkg.name}
                                                </button>
                                            ))}
                                        </div>

                                        {/* Package Details */}
                                        {selectedPackage && (
                                            <div className="p-6">
                                                <div className="flex justify-between items-start mb-4">
                                                    <h3 className="text-xl font-bold text-gray-900">{selectedPackage.name} Package</h3>
                                                    <span className="text-2xl font-bold text-gray-900">${selectedPackage.price}</span>
                                                </div>
                                                <p className="text-gray-600 mb-6 text-sm">{selectedPackage.description}</p>

                                                <div className="flex items-center text-sm font-bold text-gray-700 mb-6 gap-4">
                                                    <div className="flex items-center gap-1">
                                                        <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        <span>{selectedPackage.delivery_days} Days Delivery</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                        <span>Unlimited Revisions</span>
                                                    </div>
                                                </div>

                                                <ul className="space-y-3 mb-8">
                                                    <li className="flex items-start text-sm text-gray-600">
                                                        <svg className="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Feature inclusion 1
                                                    </li>
                                                    <li className="flex items-start text-sm text-gray-600">
                                                        <svg className="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Feature inclusion 2
                                                    </li>
                                                    <li className="flex items-start text-sm text-gray-600">
                                                        <svg className="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Feature inclusion 3
                                                    </li>
                                                </ul>

                                                <div className="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-100 flex justify-between items-center">
                                                    <span className="text-sm font-medium text-gray-700">Your balance:</span>
                                                    <span className="text-sm font-bold text-gray-900">${displayBalance.toFixed(2)}</span>
                                                </div>

                                                {displayBalance >= selectedPackage.price ? (
                                                    <button
                                                        onClick={() => handleBuyNow(selectedPackage.id)}
                                                        className="w-full bg-indigo-600 text-white font-bold px-4 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center justify-center gap-2"
                                                    >
                                                        Continue — ${selectedPackage.price}
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                    </button>
                                                ) : (
                                                    <div className="space-y-3">
                                                        <div className="text-red-600 text-sm font-medium text-center">
                                                            Need ${(selectedPackage.price - displayBalance).toFixed(2)} more to purchase
                                                        </div>
                                                        <button
                                                            className="w-full bg-amber-500 text-white font-bold px-4 py-3 rounded-lg hover:bg-amber-600 transition"
                                                        >
                                                            Top up wallet
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="p-8 text-center text-gray-500">
                                        No packages available for this service yet.
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
