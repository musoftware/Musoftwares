import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { SeoHead } from '@/Components/ui/SeoHead';
import { useState } from 'react';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { HelpCircle, Zap, ClipboardList, CheckCircle2, Tag, ZoomIn, X, ChevronLeft, ChevronRight } from 'lucide-react';

export default function Show({ service }: any) {
    const { auth, wallet } = usePage().props as any;
    const displayBalance = wallet?.balance || auth?.user?.user_balance || 0;

    const [activeTab, setActiveTab] = useState<'overview' | 'reviews'>(
        'overview',
    );
    const [isZoomOpen, setIsZoomOpen] = useState(false);

    // Sort packages Basic, Standard, Premium if names match, otherwise just take what's given.
    const packages = service.packages || [];
    const sortedPackages = packages.length > 0
        ? [...packages].sort((a: any, b: any) => a.price - b.price)
        : [
            {
                id: 0,
                name: __('general.standard') || 'Standard',
                description: service.description || __('general.no_description_available') || 'No description available.',
                price: service.is_free ? 0 : 5,
                currency: 'USD',
                delivery_days: 3,
                revisions: 2,
                is_mock: true
            }
        ];
    const [selectedPackageId, setSelectedPackageId] = useState<number | null>(
        sortedPackages.length > 0 ? sortedPackages[0].id : null,
    );
    const [processing, setProcessing] = useState(false);

    const handleBuyNow = (packageId: number) => {
        if (processing) return;
        setProcessing(true);
        router.post(route('marketplace.orders.store'), {
            package_id: packageId,
        }, {
            onFinish: () => setProcessing(false)
        });
    };

    const selectedPackage = sortedPackages.find(
        (p: any) => p.id === selectedPackageId,
    );

    const isOwner = auth?.user && (auth.user.id === service.seller_id || auth.user.role === 'admin' || auth.user.roles?.includes('admin') || auth.user.is_admin);

    const [activeMediaIndex, setActiveMediaIndex] = useState(0);

    const mediaItems = (() => {
        const items: Array<{ type: 'image' | 'video'; url: string; thumbnail: string }> = [];

        if (service.video_url) {
            const videoUrl = service.video_url.trim();
            const shortsMatch = videoUrl.match(/(?:youtube\.com|youtu\.be)\/shorts\/([a-zA-Z0-9_-]+)/);
            const youtubeMatch = videoUrl.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/);
            const vimeoMatch = videoUrl.match(/vimeo\.com\/(?:video\/)?([0-9]+)/);

            if (shortsMatch && shortsMatch[1]) {
                const id = shortsMatch[1];
                items.push({
                    type: 'video',
                    url: `https://www.youtube.com/embed/${id}`,
                    thumbnail: `https://img.youtube.com/vi/${id}/hqdefault.jpg`,
                });
            } else if (youtubeMatch && youtubeMatch[1]) {
                const id = youtubeMatch[1];
                items.push({
                    type: 'video',
                    url: `https://www.youtube.com/embed/${id}`,
                    thumbnail: `https://img.youtube.com/vi/${id}/hqdefault.jpg`,
                });
            } else if (vimeoMatch && vimeoMatch[1]) {
                const id = vimeoMatch[1];
                items.push({
                    type: 'video',
                    url: `https://player.vimeo.com/video/${id}`,
                    thumbnail: `https://vumbnail.com/${id}.jpg`,
                });
            }
        }

        if (service.gallery && Array.isArray(service.gallery) && service.gallery.length > 0) {
            service.gallery.forEach((path: string) => {
                if (path) {
                    let imgUrl = path;
                    if (!path.startsWith('http') && !path.startsWith('/')) {
                        const clean = path.replace(/^storage\//, '').replace(/^uploads\//, '');
                        imgUrl = `/uploads/${clean}`;
                    }
                    items.push({ type: 'image', url: imgUrl, thumbnail: imgUrl });
                }
            });
        }

        if (items.length === 0 && service.cover_image) {
            items.push({ type: 'image', url: service.cover_image, thumbnail: service.cover_image });
        }

        return items;
    })();

    const activeMedia = mediaItems[activeMediaIndex] || mediaItems[0];

    const serviceCanonicalUrl = typeof window !== 'undefined'
        ? `${window.location.origin}/marketplace/services/${service.id}/${service.slug || ''}`
        : `https://www.musoftwares.com/marketplace/services/${service.id}`;

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Service',
        'name': service.title,
        'description': service.tagline || service.description?.substring(0, 300) || service.title,
        'image': service.cover_image || service.thumbnail,
        'url': serviceCanonicalUrl,
        'category': service.category?.name || 'Software Services',
        'provider': {
            '@type': 'Organization',
            'name': service.seller?.name || 'MuSoftwares Marketplace',
            'url': 'https://www.musoftwares.com'
        },
        'offers': {
            '@type': 'Offer',
            'price': selectedPackage?.price || service.starting_price || 0,
            'priceCurrency': selectedPackage?.currency?.code || 'USD',
            'availability': 'https://schema.org/InStock',
            'url': serviceCanonicalUrl
        },
        ...(service.avg_rating && Number(service.avg_rating) > 0 ? {
            'aggregateRating': {
                '@type': 'AggregateRating',
                'ratingValue': service.avg_rating,
                'reviewCount': service.review_count || 1,
                'bestRating': 5,
                'worstRating': 1
            }
        } : {})
    };

    return (
        <MarketplaceLayout>
            <SeoHead
                title={`${service.title} | MuSoftwares Marketplace`}
                description={service.tagline || service.description?.substring(0, 160)}
                image={service.cover_image || service.thumbnail}
                url={serviceCanonicalUrl}
                canonicalUrl={serviceCanonicalUrl}
                type="product"
                jsonLd={jsonLd}
            />

            {/* Breadcrumb */}
            <div className="border-b border-gray-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-3 text-sm text-gray-500 sm:px-6 lg:px-8 flex items-center justify-between">
                    <div>
                        <Link
                            href={route('marketplace.services.index')}
                            className="transition-colors hover:text-indigo-600"
                        >
                            {__('general.marketplace')}</Link>
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

                    {isOwner && (
                        <div className="flex items-center gap-2">
                            <Link
                                href={route('marketplace.services.edit', service.id)}
                                className="px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-bold transition-colors"
                            >
                                {__('general.edit')}
                            </Link>
                            <button
                                type="button"
                                onClick={() => {
                                    if (confirm(__('general.are_you_sure_you_want_to_delete_this_service') || 'Are you sure you want to delete this service?')) {
                                        router.delete(route('marketplace.services.destroy', service.id));
                                    }
                                }}
                                className="px-3 py-1.5 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold transition-colors"
                            >
                                {__('general.delete') || 'Delete'}
                            </button>
                        </div>
                    )}
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
                        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-4">
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
                                            className="me-1 h-4 w-4"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        <span className="font-bold text-gray-900">
                                            {service.avg_rating || '5.0'}
                                        </span>
                                    </span>
                                    <span className="ms-1 text-gray-500">
                                        ({service.review_count || 0} reviews)
                                    </span>
                                </div>
                            </div>

                            <button
                                onClick={() => router.post(`/marketplace/services/${service.id}/favorite`, {}, { preserveScroll: true })}
                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition ${service.is_favorited ? 'bg-red-50 text-red-600 border-red-200' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'}`}
                            >
                                <svg className={`w-4 h-4 ${service.is_favorited ? 'fill-red-500 text-red-500' : 'text-slate-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                {service.is_favorited ? 'Saved to Wishlist' : 'Save to Wishlist'}
                            </button>
                        </div>

                        {/* Interactive Media Gallery (Video & Images) */}
                        <div className="mb-8 space-y-3">
                            <div
                                className={`relative aspect-video w-full overflow-hidden rounded-2xl border border-gray-200 bg-slate-900 shadow-sm flex items-center justify-center ${activeMedia?.type === 'image' ? 'cursor-zoom-in group' : ''}`}
                                onClick={() => activeMedia?.type === 'image' && setIsZoomOpen(true)}
                            >
                                {activeMedia?.type === 'video' ? (
                                    <iframe
                                        src={activeMedia.url}
                                        title={service.title}
                                        className="h-full w-full border-0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowFullScreen
                                    />
                                ) : activeMedia?.type === 'image' ? (
                                    <>
                                        <img
                                            src={activeMedia.url}
                                            alt={service.title}
                                            className="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                        <div className="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                            <div className="bg-white/95 backdrop-blur-md text-slate-900 px-4 py-2 rounded-full text-xs font-bold shadow-xl flex items-center gap-2 border border-white/40">
                                                <ZoomIn className="w-4 h-4 text-indigo-600" />
                                                <span>Click to Zoom</span>
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <div className="text-gray-400 text-sm">
                                        [No Media Preview]
                                    </div>
                                )}
                            </div>

                            {/* Media Thumbnails Slider */}
                            {mediaItems.length > 1 && (
                                <div className="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-thin">
                                    {mediaItems.map((item, idx) => (
                                        <button
                                            key={idx}
                                            type="button"
                                            onClick={() => setActiveMediaIndex(idx)}
                                            className={`relative h-16 w-24 shrink-0 overflow-hidden rounded-xl border-2 transition-all ${activeMediaIndex === idx
                                                    ? 'border-indigo-600 ring-2 ring-indigo-200 scale-105'
                                                    : 'border-gray-200 opacity-70 hover:opacity-100'
                                                }`}
                                        >
                                            <img src={item.thumbnail} alt={`Media ${idx}`} className="h-full w-full object-cover" />
                                            {item.type === 'video' && (
                                                <div className="absolute inset-0 bg-black/40 flex items-center justify-center">
                                                    <div className="w-6 h-6 rounded-full bg-white/90 text-indigo-600 flex items-center justify-center shadow">
                                                        <svg className="w-3.5 h-3.5 fill-current ms-0.5" viewBox="0 0 24 24">
                                                            <path d="M8 5v14l11-7z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            )}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Tabs */}
                        <div className="mb-6 border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    onClick={() => setActiveTab('overview')}
                                    className={`border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap transition-colors ${activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'}`}
                                >
                                    {__('general.overview')}
                                </button>
                                <button
                                    onClick={() => setActiveTab('reviews')}
                                    className={`border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap transition-colors ${activeTab === 'reviews' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'}`}
                                >
                                    {__('general.reviews')} ({service.review_count || 0})
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

                                {/* Available Extras / Add-ons */}
                                {service.extras && service.extras.length > 0 && (
                                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                            <Zap className="w-5 h-5 text-amber-500" />
                                            {__('general.service_extras') || 'Available Extras & Upgrades'}
                                        </h3>
                                        <div className="space-y-3">
                                            {service.extras.map((extra: any) => (
                                                <div key={extra.id} className="flex items-center justify-between p-3.5 rounded-lg border border-slate-100 bg-slate-50 hover:bg-slate-100/80 transition-colors">
                                                    <div>
                                                        <h4 className="font-bold text-slate-900 text-sm">{extra.title}</h4>
                                                        {extra.duration_days > 0 && (
                                                            <span className="text-xs text-slate-500 font-medium">+ {extra.duration_days} {extra.duration_days === 1 ? 'day' : 'days'} delivery</span>
                                                        )}
                                                    </div>
                                                    <span className="font-bold text-indigo-600 text-sm bg-indigo-50 px-3 py-1 rounded-md border border-indigo-100">
                                                        + {formatCurrency(extra.price, selectedPackage?.currency || 'USD')}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Frequently Asked Questions (FAQ) */}
                                {service.faq && service.faq.length > 0 && (
                                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                            <HelpCircle className="w-5 h-5 text-indigo-600" />
                                            {__('general.frequently_asked_questions') || 'Frequently Asked Questions'}
                                        </h3>
                                        <div className="divide-y divide-gray-100">
                                            {service.faq.map((item: any, idx: number) => (
                                                <div key={idx} className="py-3.5 first:pt-0 last:pb-0">
                                                    <h4 className="font-semibold text-gray-900 text-sm mb-1 flex items-start gap-2">
                                                        <span className="text-indigo-600 font-bold shrink-0">Q:</span>
                                                        <span>{item.question}</span>
                                                    </h4>
                                                    <p className="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap ps-6">
                                                        {item.answer}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Requirements Needed From Buyer */}
                                {service.requirements && service.requirements.length > 0 && (
                                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                            <ClipboardList className="w-5 h-5 text-indigo-600" />
                                            {__('general.requirements_from_buyer') || 'Requirements Needed From Buyer'}
                                        </h3>
                                        <ul className="space-y-2">
                                            {service.requirements.map((req: string, idx: number) => (
                                                <li key={idx} className="flex items-start gap-2.5 text-sm text-gray-700">
                                                    <CheckCircle2 className="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" />
                                                    <span>{req}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                {/* Tags */}
                                {service.tags && service.tags.length > 0 && (
                                    <div className="pt-4 border-t border-gray-200">
                                        <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">{__('general.tags') || 'Tags'}</h4>
                                        <div className="flex flex-wrap gap-2">
                                            {service.tags.map((tag: string, idx: number) => (
                                                <span key={idx} className="inline-flex items-center gap-1 text-xs font-medium bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md">
                                                    <Tag className="w-3 h-3 text-slate-400" />
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Seller Card */}
                                <div>
                                    <h3 className="mb-4 text-xl font-bold text-gray-900">{__('general.about_the_seller')}</h3>
                                    <div className="flex flex-col items-center gap-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:flex-row md:items-start">
                                        <div className="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-3xl font-bold text-indigo-700">
                                            {service.seller?.name?.charAt(0) ||
                                                '?'}
                                        </div>
                                        <div className="flex-1 text-center md:text-start">
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
                                                    {service.avg_rating || '5.0'}
                                                </span>
                                                <span className="text-gray-500">
                                                    ({service.review_count || 0} reviews)
                                                </span>
                                            </div>
                                            <p className="mb-4 text-sm text-gray-500">
                                                Member since{' '}
                                                {service.seller?.created_at
                                                    ? new Date(service.seller.created_at).getFullYear()
                                                    : '2026'}
                                            </p>
                                            <Link href={service.seller?.id ? route('messages.index', { recipient_id: service.seller.id }) : route('messages.index')} className="inline-block rounded-md border border-indigo-600 px-6 py-2 font-medium text-indigo-600 transition hover:bg-indigo-50">{__('general.contact_me')}</Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'reviews' && (
                            <div className="space-y-4 py-4">
                                {(!service.reviews || service.reviews.length === 0) ? (
                                    <div className="py-8 text-center text-gray-500">
                                        No customer reviews for this service yet.
                                    </div>
                                ) : (
                                    service.reviews.map((rev: any) => (
                                        <div key={rev.id} className="p-4 rounded-xl border border-gray-200 bg-white space-y-2">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-8 h-8 rounded-full bg-slate-100 font-bold text-xs flex items-center justify-center text-slate-700">
                                                        {rev.reviewer?.name?.charAt(0) || 'U'}
                                                    </div>
                                                    <span className="font-semibold text-slate-900 text-sm">{rev.reviewer?.name || 'Verified Buyer'}</span>
                                                </div>
                                                <span className="text-amber-500 font-bold text-xs flex items-center gap-1">
                                                    ★ {rev.rating}/5
                                                </span>
                                            </div>
                                            <p className="text-slate-600 text-sm">{rev.review || 'Great seller and high quality deliverable!'}</p>
                                        </div>
                                    ))
                                )}
                            </div>
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
                                                    <div className="text-end">
                                                        <div className="flex items-center gap-2 justify-end">
                                                            <span className="text-2xl font-bold text-gray-900">
                                                                {formatCurrency(selectedPackage.price, selectedPackage.currency)}
                                                            </span>
                                                            {Number(selectedPackage.old_price) > Number(selectedPackage.price) && (
                                                                <span className="text-sm text-gray-400 line-through">
                                                                    {formatCurrency(selectedPackage.old_price, selectedPackage.currency)}
                                                                </span>
                                                            )}
                                                        </div>
                                                        {Number(selectedPackage.old_price) > Number(selectedPackage.price) && (
                                                            <span className="inline-block bg-red-100 text-red-700 font-bold text-[11px] px-2 py-0.5 rounded mt-0.5">
                                                                -{Math.round(((Number(selectedPackage.old_price) - Number(selectedPackage.price)) / Number(selectedPackage.old_price)) * 100)}% OFF
                                                            </span>
                                                        )}
                                                    </div>
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
                                                    {/* Feature inclusions would be rendered here from selectedPackage */}
                                                </ul>

                                                <div className="mb-6 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-4">
                                                    <span className="text-sm font-medium text-gray-700">
                                                        {__('general.your_balance')}:
                                                    </span>
                                                    <span className="text-sm font-bold text-gray-900">
                                                        {formatCurrency(displayBalance, auth?.user?.currency)}
                                                    </span>
                                                </div>

                                                {displayBalance >= selectedPackage.price ? (
                                                    <div className="space-y-3">
                                                        <button
                                                            onClick={() => handleBuyNow(selectedPackage.id)}
                                                            disabled={processing}
                                                            className={`flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 font-bold text-white transition ${processing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-700'}`}
                                                        >
                                                            {__('general.continue')} — {formatCurrency(selectedPackage.price, selectedPackage.currency)}
                                                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                                            </svg>
                                                        </button>
                                                        <Link
                                                            href={service.seller?.id ? route('messages.index', { recipient_id: service.seller.id }) : route('messages.index')}
                                                            className="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 font-semibold text-slate-700 transition shadow-none text-sm"
                                                        >
                                                            {__('general.contact_seller') || 'Contact Seller'}
                                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                            </svg>
                                                        </Link>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-3">
                                                        <div className="text-center text-sm font-medium text-red-600">
                                                            {__('general.need_more_balance', { amount: formatCurrency(selectedPackage.price - displayBalance, selectedPackage.currency) })}
                                                        </div>
                                                        <Link
                                                            href={route('billing.invoices.index')}
                                                            className="block w-full text-center rounded-lg bg-amber-500 px-4 py-3 font-bold text-white transition hover:bg-amber-600 shadow-sm"
                                                        >
                                                            {__('general.top_up_wallet')}
                                                        </Link>
                                                        <Link
                                                            href={service.seller?.id ? route('messages.index', { recipient_id: service.seller.id }) : route('messages.index')}
                                                            className="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-4 py-2.5 font-semibold text-slate-700 transition shadow-none text-sm"
                                                        >
                                                            {__('general.contact_seller') || 'Contact Seller'}
                                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                            </svg>
                                                        </Link>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="p-8 text-center text-gray-500">
                                        {__('general.no_packages_available_for_this_service_yet')}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Fullscreen Lightbox Zoom Modal */}
            {isZoomOpen && activeMedia?.type === 'image' && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-md animate-in fade-in duration-200"
                    onClick={() => setIsZoomOpen(false)}
                >
                    <div
                        className="relative max-h-[90vh] max-w-[90vw] overflow-hidden rounded-2xl shadow-2xl flex items-center justify-center bg-slate-950/80 p-2"
                        onClick={(e) => e.stopPropagation()}
                    >
                        {/* Close Button */}
                        <button
                            type="button"
                            onClick={() => setIsZoomOpen(false)}
                            className="absolute top-4 end-4 z-20 p-2.5 rounded-full bg-black/60 text-white hover:bg-black/80 transition-colors shadow-lg border border-white/10"
                        >
                            <X className="w-6 h-6" />
                        </button>

                        {/* Previous Image Button */}
                        {mediaItems.length > 1 && (
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setActiveMediaIndex((prev) => (prev > 0 ? prev - 1 : mediaItems.length - 1));
                                }}
                                className="absolute start-4 top-1/2 -translate-y-1/2 z-20 p-3 rounded-full bg-black/60 text-white hover:bg-black/80 transition-colors shadow-lg border border-white/10"
                            >
                                <ChevronLeft className="w-6 h-6" />
                            </button>
                        )}

                        {/* Next Image Button */}
                        {mediaItems.length > 1 && (
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setActiveMediaIndex((prev) => (prev < mediaItems.length - 1 ? prev + 1 : 0));
                                }}
                                className="absolute end-4 top-1/2 -translate-y-1/2 z-20 p-3 rounded-full bg-black/60 text-white hover:bg-black/80 transition-colors shadow-lg border border-white/10"
                            >
                                <ChevronRight className="w-6 h-6" />
                            </button>
                        )}

                        {/* Zoomed Image */}
                        <img
                            src={activeMedia.url}
                            alt={service.title}
                            className="max-h-[85vh] max-w-[85vw] object-contain rounded-xl select-none"
                        />
                    </div>
                </div>
            )}
        </MarketplaceLayout>
    );
}

