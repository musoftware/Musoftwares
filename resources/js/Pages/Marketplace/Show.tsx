import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ service }: any) {
    const handleBuyNow = (packageId: number) => {
        router.post(route('marketplace.orders.store'), { package_id: packageId });
    };

    return (
        <MarketplaceLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">{service.title}</h2>}>
            <Head title={service.title} />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
                    {/* Main Content */}
                    <div className="w-full md:w-2/3">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div className="h-64 bg-gray-200 w-full flex items-center justify-center text-gray-500 text-2xl font-bold">
                                [Cover Image Gallery]
                            </div>
                            <div className="p-6">
                                <h3 className="text-2xl font-bold mb-2">About This Service</h3>
                                <div className="prose max-w-none text-gray-700">
                                    {service.description}
                                </div>
                            </div>
                        </div>

                        {/* Seller Info */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-xl font-bold mb-4">About The Seller</h3>
                            <div className="flex items-center gap-4">
                                <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 text-xl font-bold">
                                    {service.seller?.name?.charAt(0)}
                                </div>
                                <div>
                                    <p className="font-semibold text-lg">{service.seller?.name}</p>
                                    <p className="text-gray-500 text-sm">Member since {new Date(service.seller?.created_at).getFullYear()}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Packages Sidebar */}
                    <div className="w-full md:w-1/3">
                        <div className="bg-white shadow-sm sm:rounded-lg p-6 sticky top-6 border border-gray-200">
                            <h3 className="text-xl font-bold mb-4">Packages</h3>
                            {service.packages && service.packages.length > 0 ? (
                                <div className="flex flex-col gap-4">
                                    {service.packages.map((pkg: any) => (
                                        <div key={pkg.id} className="border border-gray-200 rounded-md p-4 hover:border-indigo-500 transition">
                                            <div className="flex justify-between items-center mb-2">
                                                <h4 className="font-semibold text-lg">{pkg.name}</h4>
                                                <span className="font-bold text-xl">${pkg.price}</span>
                                            </div>
                                            <p className="text-sm text-gray-600 mb-4">{pkg.description}</p>
                                            <div className="flex justify-between text-sm text-gray-500 mb-4">
                                                <span>Delivery: {pkg.delivery_days} days</span>
                                            </div>
                                            <button
                                                onClick={() => handleBuyNow(pkg.id)}
                                                className="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition"
                                            >
                                                Buy Now
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500">No packages available for this service yet.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </MarketplaceLayout>
    );
}
