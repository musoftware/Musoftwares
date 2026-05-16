import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Browse({ services, categories, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryId, setCategoryId] = useState(filters.category_id || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('marketplace.services.index'), { search, category_id: categoryId }, { preserveState: true });
    };

    return (
        <MarketplaceLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Marketplace Browse</h2>}>
            <Head title="Browse Services" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
                    {/* Sidebar Filters */}
                    <div className="w-full md:w-1/4">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-bold mb-4">Filters</h3>
                            <form onSubmit={handleSearch}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700">Search</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Search services..."
                                    />
                                </div>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700">Category</label>
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={categoryId}
                                        onChange={(e) => setCategoryId(e.target.value)}
                                    >
                                        <option value="">All Categories</option>
                                        {categories.map((cat: any) => (
                                            <option key={cat.id} value={cat.id}>{cat.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <button type="submit" className="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                                    Filter
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Services Grid */}
                    <div className="w-full md:w-3/4">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {services.data.map((service: any) => (
                                <Link href={route('marketplace.services.show', service.id)} key={service.id}>
                                    <div className={`bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition cursor-pointer border-2 ${service.is_featured ? 'border-indigo-500' : 'border-transparent'}`}>
                                        {/* Placeholder Image */}
                                        <div className="h-48 bg-gray-200 w-full flex items-center justify-center text-gray-500">
                                            [Service Image]
                                        </div>
                                        <div className="p-4">
                                            {service.is_featured && <span className="inline-block px-2 py-1 text-xs font-semibold bg-indigo-100 text-indigo-800 rounded mb-2">Featured</span>}
                                            <h3 className="text-lg font-bold truncate" title={service.title}>{service.title}</h3>
                                            <p className="text-sm text-gray-600 mt-1">by {service.seller?.name}</p>
                                            <p className="text-sm text-gray-500 mt-2 truncate" title={service.description}>{service.description}</p>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {services.data.length === 0 && (
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                                No services found.
                            </div>
                        )}

                        {/* Pagination placeholder if needed */}
                        {services.links && services.links.length > 3 && (
                            <div className="mt-6 flex justify-center gap-2">
                                {/* Basic pagination representation */}
                                {services.links.map((link: any, idx: number) => (
                                    <button
                                        key={idx}
                                        onClick={() => {
                                            if (link.url) router.get(link.url, { search, category_id: categoryId }, { preserveState: true });
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
        </MarketplaceLayout>
    );
}
