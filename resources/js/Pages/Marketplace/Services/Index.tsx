import Pagination from '@/Components/Pagination';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { formatDate, formatMoney } from '@/lib/utils';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { __ } from '@/lib/i18n';
import { SeoHead } from '@/Components/ui/SeoHead';
import { MarketplaceGeoSection } from '@/Components/Public/MarketplaceGeoSection';

export default function Index({ services }: any) {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setLoading(true));
        const removeFinish = router.on('finish', () => setLoading(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    const pageTitle = __('general.services') ? `${__('general.services')} | Musoftware Marketplace` : 'Software & Professional Services | Musoftware Marketplace';
    const pageDesc = 'Browse verified software services, custom ERP extensions, desktop runtime tools, and developer solutions with escrow payment protection.';

    const jsonLd = {
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'WebPage',
                'name': pageTitle,
                'description': pageDesc,
            },
            {
                '@type': 'ItemList',
                'itemListElement': (services?.data || []).map((service: any, index: number) => ({
                    '@type': 'ListItem',
                    'position': index + 1,
                    'item': {
                        '@type': 'Product',
                        'name': service.title,
                        'url': typeof window !== 'undefined' ? `${window.location.origin}/marketplace/services/${service.id}/${service.slug || ''}` : '',
                        'offers': {
                            '@type': 'Offer',
                            'price': service.price || 0,
                            'priceCurrency': service.currency_code || 'USD'
                        }
                    }
                }))
            }
        ]
    };

    return (
        <MarketplaceLayout>
            <SeoHead
                title={pageTitle}
                description={pageDesc}
                jsonLd={jsonLd}
            />
            <div className="py-12 bg-zinc-950 text-zinc-100 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                    <div className="relative overflow-hidden bg-zinc-900 border border-zinc-800 p-6 shadow-xl sm:rounded-2xl">
                        {loading && (
                            <div className="absolute inset-0 z-10 flex items-center justify-center bg-zinc-950/70 backdrop-blur-sm">
                                <div className="font-medium text-emerald-400">{__('general.loading') || 'Loading...'}</div>
                            </div>
                        )}
                        <h3 className="mb-6 text-2xl font-bold text-white tracking-tight">{__('general.services') || 'Explore Software Services'}</h3>

                        {(services.data as any).length > 0 ? (
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {(services.data as any).map((service: any) => (
                                    <div
                                        key={service.id}
                                        className="rounded-xl border border-zinc-800 bg-zinc-950/50 p-5 shadow-sm hover:border-emerald-500/50 transition-all flex flex-col justify-between"
                                    >
                                        <div>
                                            <h4 className="text-lg font-bold text-white mb-2 line-clamp-2">
                                                {service.title}
                                            </h4>
                                            <p className="text-xs text-zinc-400 mb-4">
                                                {__('general.by') || 'By'}{' '}
                                                <span className="text-zinc-200 font-medium">{service.seller?.name || __('general.unknown') || 'Verified Partner'}</span>
                                            </p>
                                        </div>
                                        <div className="pt-4 border-t border-zinc-800/80 flex items-center justify-between">
                                            <span className="font-bold text-emerald-400 text-lg">
                                                {formatMoney(
                                                    service.price,
                                                    service.currency_code,
                                                )}
                                            </span>
                                            <span className="text-xs text-zinc-500">
                                                {formatDate(service.created_at)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-12 text-center">
                                <p className="text-zinc-400">{__('general.no_services_found_1') || 'No services available at the moment.'}</p>
                            </div>
                        )}

                        <div className="mt-6">
                            <Pagination links={services.links} />
                        </div>
                    </div>

                    {/* GEO Answer-First Component */}
                    <MarketplaceGeoSection />
                </div>
            </div>
        </MarketplaceLayout>
    );
}
