import React from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Heart, Star, Trash2, ArrowRight, ShoppingBag } from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface FavoriteItem {
    id: number;
    favoritable: {
        id: number;
        title: string;
        tagline?: string;
        cover_image?: string;
        avg_rating?: number;
        review_count?: number;
        seller?: {
            id: number;
            name: string;
        };
    };
}

interface FavoritesIndexProps {
    favorites: {
        data: FavoriteItem[];
        links: any[];
        total: number;
    };
}

export default function Index({ favorites }: FavoritesIndexProps) {
    const handleRemove = (serviceId: number) => {
        router.post(`/marketplace/services/${serviceId}/favorite`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <MarketplaceLayout>
            <Head title={__('general.saved_favorites') || 'My Saved Wishlist'} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <ModulePageHeader
                    title={__('general.saved_favorites') || 'Saved Services & Wishlist'}
                    description={__('general.saved_favorites_sub') || 'Quick access to your bookmarked marketplace service listings.'}
                />

                <OperationalCard
                    title={__('general.bookmarked_gigs') || 'Wishlist Items'}
                    description={`${favorites.total || favorites.data.length} services saved.`}
                >
                    {favorites.data.length === 0 ? (
                        <div className="p-12 text-center text-slate-400 space-y-3">
                            <Heart className="w-10 h-10 mx-auto text-slate-300 stroke-[1.5]" />
                            <p className="text-sm font-medium text-slate-700">
                                {__('general.no_saved_favorites_yet') || 'Your wishlist is currently empty.'}
                            </p>
                            <p className="text-xs text-slate-400 max-w-md mx-auto">
                                Click the heart icon on any service listing to bookmark it for quick access later.
                            </p>
                            <div className="pt-2">
                                <Link
                                    href="/marketplace/services"
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition"
                                >
                                    <ShoppingBag className="w-4 h-4" />
                                    Browse Catalog Services
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            {favorites.data.map((fav) => {
                                const srv = fav.favoritable;
                                if (!srv) return null;

                                return (
                                    <div
                                        key={fav.id}
                                        className="group border border-slate-200 rounded-2xl overflow-hidden bg-white hover:shadow-lg transition flex flex-col justify-between"
                                    >
                                        <div className="relative aspect-video bg-slate-100 overflow-hidden">
                                            <img
                                                src={srv.cover_image || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=800&q=80'}
                                                alt={srv.title}
                                                className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                            />
                                            <button
                                                onClick={() => handleRemove(srv.id)}
                                                className="absolute top-3 right-3 p-2 rounded-full bg-white/90 backdrop-blur-sm text-red-500 hover:bg-red-500 hover:text-white transition shadow-sm"
                                                title="Remove from favorites"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>

                                        <div className="p-5 space-y-3 flex-1 flex flex-col justify-between">
                                            <div className="space-y-1.5">
                                                <div className="text-xs text-slate-400 font-medium">
                                                    Seller: {srv.seller?.name || 'Verified Creator'}
                                                </div>
                                                <h4 className="font-bold text-slate-900 text-base line-clamp-2 group-hover:text-indigo-600 transition">
                                                    {srv.title}
                                                </h4>
                                            </div>

                                            <div className="flex items-center justify-between pt-3 border-t border-slate-100">
                                                <div className="flex items-center gap-1 text-xs text-amber-500 font-semibold">
                                                    <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                                                    <span>{srv.avg_rating || '5.0'}</span>
                                                    <span className="text-slate-400 font-normal">({srv.review_count || 0})</span>
                                                </div>

                                                <Link
                                                    href={`/marketplace/services/${srv.id}`}
                                                    className="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                                >
                                                    View Details <ArrowRight className="w-3.5 h-3.5" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </OperationalCard>
            </div>
        </MarketplaceLayout>
    );
}
