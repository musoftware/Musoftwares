import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Link, router } from '@inertiajs/react';
import { SeoHead } from '@/Components/ui/SeoHead';
import { MouseEvent as ReactMouseEvent, useRef, useState } from 'react';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { Plus } from 'lucide-react';

export default function Browse({ services, categories, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');
    const activeCategoryParam = filters.category || filters.category_id || '';
    const [categoryId, setCategoryId] = useState(activeCategoryParam);
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        'name': __('general.software_services_marketplace') || 'Software Development & IT Services Marketplace',
        'description': __('general.marketplace_seo_description') || 'Browse top software development, IT services, custom scripts, and digital solutions on MuSoftwares Marketplace.',
        'itemListElement': services?.data?.map((service: any, index: number) => ({
            '@type': 'ListItem',
            'position': index + 1,
            'item': {
                '@type': 'Service',
                'name': service.title,
                'description': service.description ? service.description.substring(0, 150) : service.title,
                'url': service.url || (typeof window !== 'undefined' ? `${window.location.origin}/marketplace/services/${service.id}/${service.slug || ''}` : `https://www.musoftwares.com/marketplace/services/${service.id}`),
                'image': service.thumbnail || service.cover_image,
                'provider': {
                    '@type': 'Organization',
                    'name': service.seller?.name || 'MuSoftwares Marketplace'
                }
            }
        })) || []
    };

    const handleSearch = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        router.get(
            route('marketplace.services.index'),
            { search, category: categoryId },
            { preserveState: true },
        );
    };

    const selectCategory = (id: string) => {
        setCategoryId(id);
        router.get(
            route('marketplace.services.index'),
            { search, category: id },
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
            <SeoHead
                title={`${__('general.software_services_marketplace') || 'Software Development & IT Services Marketplace'} | MuSoftwares`}
                description={__('general.marketplace_seo_description') || 'Browse top software development, IT services, custom scripts, and digital solutions on MuSoftwares Marketplace.'}
                canonicalUrl="https://www.musoftwares.com/marketplace/services"
                type="website"
                jsonLd={jsonLd}
            />

            {/* Hero Banner */}
            <div className="bg-indigo-900 px-6 py-20 text-center text-white sm:px-12">
                <h1 className="mb-6 text-4xl font-bold md:text-5xl">{__('general.find_the_perfect_service')}</h1>
                <div className="relative mx-auto max-w-7xl">
                    <form
                        onSubmit={handleSearch}
                        className="relative flex w-full items-center"
                    >
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={__('general.what_service_are_you_looking_for_today')}
                            className="w-full rounded-full py-4 pe-32 ps-6 text-lg text-gray-900 shadow-lg focus:ring-4 focus:ring-indigo-300 focus:outline-none"
                        />
                        <button
                            type="submit"
                            className="absolute top-2 end-2 bottom-2 rounded-full bg-indigo-600 px-6 font-semibold text-white transition hover:bg-indigo-700"
                        >
                            {__('general.search')}</button>
                    </form>
                    <div className="mt-4 flex items-center justify-center gap-3 text-sm">
                        <span className="text-indigo-200">{__('general.are_you_a_freelancer_or_vendor') || 'Are you a freelancer or software seller?'}</span>
                        <Link
                            href="/marketplace/services/create"
                            className="inline-flex items-center gap-1.5 rounded-full bg-white/10 hover:bg-white/20 px-4 py-1.5 font-semibold text-white backdrop-blur-sm transition border border-white/20"
                        >
                            <Plus className="h-4 w-4 text-indigo-300" />
                            <span>{__('general.add_marketplace_item') || __('general.publish_service') || 'Add Marketplace Item'}</span>
                        </Link>
                    </div>
                </div>
            </div>

            {/* Active Filter Indicators */}
            {(filters.category || filters.search) && (
                <div className="border-b border-gray-200 bg-gray-50 py-3">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2 text-sm text-gray-700">
                            <span className="font-medium text-gray-500">{__('general.filtering_by') || 'Filtering by'}:</span>
                            {filters.category && (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">
                                    {filters.category_name || categories.find((c: any) =>
                                        (c?.id && filters.category_id && c.id.toString() === filters.category_id.toString()) ||
                                        (c?.slug && c.slug.toLowerCase() === filters.category?.toString().toLowerCase()) ||
                                        (c?.id && c.id.toString() === filters.category?.toString()) ||
                                        (c?.slug && c.slug.toLowerCase().startsWith(filters.category?.toString().toLowerCase()))
                                    )?.name || filters.category}
                                    <button
                                        type="button"
                                        onClick={() => selectCategory('')}
                                        className="ms-1 font-bold hover:text-indigo-950"
                                    >
                                        ×
                                    </button>
                                </span>
                            )}
                            {filters.search && (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-800">
                                    "{filters.search}"
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('');
                                            router.get(
                                                route('marketplace.services.index'),
                                                { category: categoryId },
                                                { preserveState: true }
                                            );
                                        }}
                                        className="ms-1 font-bold hover:text-gray-950"
                                    >
                                        ×
                                    </button>
                                </span>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                setSearch('');
                                setCategoryId('');
                                router.get(
                                    route('marketplace.services.index'),
                                    {},
                                    { preserveState: true }
                                );
                            }}
                            className="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                        >
                            {__('general.clear_all_filters') || 'Clear all'}
                        </button>
                    </div>
                </div>
            )}


            {/* Services Grid */}
            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {(services.data as any).map((service: any) => {
                            // Determine starting price package
                            const startingPackage =
                                service.packages && service.packages.length > 0
                                    ? service.packages.reduce((min: any, p: any) => Number(p.price) < Number(min.price) ? p : min, service.packages[0])
                                    : null;

                            const startingPrice = startingPackage ? startingPackage.price : (service.is_free ? 0 : 5);
                            const startingCurrency = startingPackage ? startingPackage.currency : 'USD';

                            // Use actual rating
                            const rating = service.avg_rating ? Number(service.avg_rating).toFixed(1) : '0.0';
                            const reviewsCount = service.review_count || '0';

                            return (
                                <Link
                                    href={service.url || route(
                                        'marketplace.services.show',
                                        { id: service.id, slug: service.slug }
                                    )}
                                    key={service.id}
                                    className="group block h-full"
                                >
                                    <div
                                        className={`flex h-full flex-col overflow-hidden rounded-xl border bg-white transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl ${service.is_featured ? 'border-2 border-amber-400' : 'border-gray-200'}`}
                                    >
                                        {/* Cover Image */}
                                        <div className="relative h-52 overflow-hidden bg-gray-200">
                                            {(service.thumbnail || service.cover_image) ? (
                                                <img
                                                    src={service.thumbnail || service.cover_image}
                                                    alt={service.title}
                                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center bg-gray-100 text-gray-400 transition-transform duration-500 group-hover:scale-105">
                                                    <svg className="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            )}
                                            {service.is_featured && (
                                                <div className="absolute top-3 start-3 rounded-full bg-amber-400 px-3 py-1 text-xs font-bold text-amber-900 shadow">
                                                    {__('general.featured')}</div>
                                            )}
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    router.post(
                                                        route('marketplace.favorites.toggle', service.id),
                                                        {},
                                                        { preserveScroll: true }
                                                    );
                                                }}
                                                className={`absolute top-3 end-3 rounded-full bg-white/90 p-2 shadow-sm transition-colors ${service.is_favorited ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-red-500'}`}
                                                title={service.is_favorited ? __('general.remove_from_favorites') || 'Remove from favorites' : __('general.add_to_favorites') || 'Add to favorites'}
                                            >
                                                <svg
                                                    className="h-5 w-5"
                                                    fill={service.is_favorited ? 'currentColor' : 'none'}
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
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
                                            {service.avg_rating > 0 && (
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
                                            )}
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
                                            <div className="text-end">
                                                <span className="text-xs font-medium tracking-wider text-gray-500 uppercase">{__('general.starting_at')}</span>
                                                <span className="block text-lg font-bold text-gray-900">
                                                    {formatCurrency(startingPrice, startingCurrency)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>

                    {(services.data as any).length === 0 && (
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
                            <h3 className="text-lg font-medium text-gray-900">{__('general.no_services_found')}</h3>
                            <p className="mt-1 text-sm text-gray-500">{__('general.try_adjusting_your_search_or_category_filters')}</p>
                            <button
                                onClick={() => {
                                    setSearch('');
                                    setCategoryId('');
                                    handleSearch();
                                }}
                                className="mt-4 font-medium text-indigo-600 hover:text-indigo-500"
                            >{__('general.clear_all_filters')}</button>
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
