import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router } from '@inertiajs/react';
import { MouseEvent as ReactMouseEvent, useRef, useState } from 'react';

export default function Browse({ services, categories, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryId, setCategoryId] = useState(filters.category_id || '');
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const handleSearch = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        router.get(
            route('marketplace.services.index'),
            { search, category_id: categoryId },
            { preserveState: true },
        );
    };

    const selectCategory = (id: string) => {
        setCategoryId(id);
        router.get(
            route('marketplace.services.index'),
            { search, category_id: id },
            { preserveState: true },
        );
    };

    // Horizontal drag to scroll
    let isDown = false;
    let startX: number;
    let scrollLeft: number;

    const handleMouseDown = (e: ReactMouseEvent) => {
        isDown = true;
        if (scrollContainerRef.current) {
            scrollContainerRef.current.classList.add('active');
            startX = e.pageX - scrollContainerRef.current.offsetLeft;
            scrollLeft = scrollContainerRef.current.scrollLeft;
        }
    };
    const handleMouseLeave = () => {
        isDown = false;
        if (scrollContainerRef.current) {
            scrollContainerRef.current.classList.remove('active');
        }
    };
    const handleMouseUp = () => {
        isDown = false;
        if (scrollContainerRef.current) {
            scrollContainerRef.current.classList.remove('active');
        }
    };
    const handleMouseMove = (e: ReactMouseEvent) => {
        if (!isDown) return;
        e.preventDefault();
        if (scrollContainerRef.current) {
            const x = e.pageX - scrollContainerRef.current.offsetLeft;
            const walk = (x - startX) * 2; // scroll-fast
            scrollContainerRef.current.scrollLeft = scrollLeft - walk;
        }
    };

    return (
        <MarketplaceLayout>
            <Head title="Find the perfect service" />

            {/* Hero Banner */}
            <div className="bg-indigo-900 px-6 py-20 text-center text-white sm:px-12">
                <h1 className="mb-6 text-4xl font-bold md:text-5xl">
                    Find the perfect service
                </h1>
                <div className="relative mx-auto max-w-3xl">
                    <form
                        onSubmit={handleSearch}
                        className="relative flex w-full items-center"
                    >
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="What service are you looking for today?"
                            className="w-full rounded-full py-4 pr-32 pl-6 text-lg text-gray-900 shadow-lg focus:ring-4 focus:ring-indigo-300 focus:outline-none"
                        />
                        <button
                            type="submit"
                            className="absolute top-2 right-2 bottom-2 rounded-full bg-indigo-600 px-6 font-semibold text-white transition hover:bg-indigo-700"
                        >
                            Search
                        </button>
                    </form>
                </div>
            </div>

            {/* Category Filter (Horizontal Scroll) */}
            <div className="border-b border-gray-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        ref={scrollContainerRef}
                        className="no-scrollbar flex cursor-grab gap-4 overflow-x-auto py-4 active:cursor-grabbing"
                        onMouseDown={handleMouseDown}
                        onMouseLeave={handleMouseLeave}
                        onMouseUp={handleMouseUp}
                        onMouseMove={handleMouseMove}
                        style={{
                            scrollbarWidth: 'none',
                            msOverflowStyle: 'none',
                        }}
                    >
                        <button
                            onClick={() => selectCategory('')}
                            className={`rounded-full border px-4 py-2 whitespace-nowrap transition ${categoryId === '' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-900'}`}
                        >
                            All Categories
                        </button>
                        {categories.map((cat: any) => (
                            <button
                                key={cat.id}
                                onClick={() =>
                                    selectCategory(cat.id.toString())
                                }
                                className={`rounded-full border px-4 py-2 whitespace-nowrap transition ${categoryId === cat.id.toString() ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-900'}`}
                            >
                                {cat.name}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Services Grid */}
            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {services.data.map((service: any) => {
                            // Determine starting price from packages
                            const startingPrice =
                                service.packages && service.packages.length > 0
                                    ? Math.min(
                                          ...service.packages.map((p: any) =>
                                              Number(p.price),
                                          ),
                                      )
                                    : null;

                            // Mock rating
                            const rating = '4.9';
                            const reviewsCount = '128';

                            return (
                                <Link
                                    href={route(
                                        'marketplace.services.show',
                                        service.id,
                                    )}
                                    key={service.id}
                                    className="group block h-full"
                                >
                                    <div
                                        className={`flex h-full flex-col overflow-hidden rounded-xl border bg-white transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl ${service.is_featured ? 'border-2 border-amber-400' : 'border-gray-200'}`}
                                    >
                                        {/* Cover Image */}
                                        <div className="relative h-52 overflow-hidden bg-gray-200">
                                            {service.cover_image ? (
                                                <img
                                                    src={service.cover_image}
                                                    alt={service.title}
                                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center bg-gray-100 text-gray-400 transition-transform duration-500 group-hover:scale-105">
                                                    [Cover Image]
                                                </div>
                                            )}
                                            {service.is_featured && (
                                                <div className="absolute top-3 left-3 rounded-full bg-amber-400 px-3 py-1 text-xs font-bold text-amber-900 shadow">
                                                    FEATURED
                                                </div>
                                            )}
                                            <button className="absolute top-3 right-3 rounded-full bg-white/80 p-2 text-gray-400 transition-colors hover:text-red-500">
                                                <svg
                                                    className="h-5 w-5"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </div>

                                        <div className="flex flex-1 flex-col p-5">
                                            {/* Seller Info */}
                                            <div className="mb-3 flex items-center gap-2">
                                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                                    {service.seller?.name?.charAt(
                                                        0,
                                                    ) || '?'}
                                                </div>
                                                <span className="truncate text-sm font-medium text-gray-900">
                                                    {service.seller?.name}
                                                </span>
                                            </div>

                                            {/* Title */}
                                            <h3 className="line-clamp-2 flex-1 text-base leading-tight text-gray-800 transition-colors group-hover:text-indigo-600">
                                                {service.title}
                                            </h3>

                                            {/* Rating */}
                                            <div className="mt-3 flex items-center gap-1 text-amber-500">
                                                <svg
                                                    className="h-4 w-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                <span className="text-sm font-bold">
                                                    {rating}
                                                </span>
                                                <span className="text-sm text-gray-500">
                                                    ({reviewsCount})
                                                </span>
                                            </div>
                                        </div>

                                        {/* Footer / Price */}
                                        <div className="flex items-center justify-between border-t border-gray-100 px-5 py-4">
                                            <div className="cursor-pointer text-gray-500 hover:text-indigo-600">
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
                                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <div className="text-right">
                                                <span className="text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Starting at
                                                </span>
                                                <span className="block text-lg font-bold text-gray-900">
                                                    ${startingPrice || 25}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>

                    {services.data.length === 0 && (
                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white p-12 text-center text-gray-500 shadow-sm">
                            <svg
                                className="mx-auto mb-4 h-12 w-12 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <h3 className="text-lg font-medium text-gray-900">
                                No services found
                            </h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Try adjusting your search or category filters.
                            </p>
                            <button
                                onClick={() => {
                                    setSearch('');
                                    setCategoryId('');
                                    handleSearch();
                                }}
                                className="mt-4 font-medium text-indigo-600 hover:text-indigo-500"
                            >
                                Clear all filters
                            </button>
                        </div>
                    )}

                    {/* Pagination */}
                    {services.links && services.links.length > 3 && (
                        <div className="mt-10 flex justify-center gap-2">
                            {services.links.map((link: any, idx: number) => (
                                <button
                                    key={idx}
                                    onClick={() => {
                                        if (link.url)
                                            router.get(
                                                link.url,
                                                {
                                                    search,
                                                    category_id: categoryId,
                                                },
                                                { preserveState: true },
                                            );
                                    }}
                                    disabled={!link.url}
                                    className={`rounded-md border px-4 py-2 text-sm font-medium transition ${link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'} ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Styles for hiding scrollbar */}
            <style
                dangerouslySetInnerHTML={{
                    __html: `
                .no-scrollbar::-webkit-scrollbar {
                    display: none;
                }
            `,
                }}
            />
        </MarketplaceLayout>
    );
}
