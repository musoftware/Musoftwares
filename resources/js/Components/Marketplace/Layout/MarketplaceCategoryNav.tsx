import React, { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Sparkles } from 'lucide-react';
import { Skeleton } from '@/Components/ui/skeleton';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
    slug: string;
}

export default function MarketplaceCategoryNav() {
    const { categories: inertiaCategories, filters } = usePage().props as any;
    
    const [categories, setCategories] = useState<Category[]>(
        Array.isArray(inertiaCategories) ? inertiaCategories : []
    );
    const [loading, setLoading] = useState(!Array.isArray(inertiaCategories) || inertiaCategories.length === 0);

    const activeCategory = filters?.category || filters?.category_id || '';

    useEffect(() => {
        if (categories.length === 0) {
            axios.get('/marketplace/api/categories')
                .then(response => {
                    setCategories(response.data.data || response.data);
                    setLoading(false);
                })
                .catch(error => {
                    console.error("Failed to fetch marketplace categories:", error);
                    setLoading(false);
                });
        } else {
            setLoading(false);
        }
    }, [categories.length]);

    if (!loading && categories.length === 0) {
        return null;
    }

    return (
        <div className="hidden md:block w-full border-b border-slate-200/80 bg-slate-50/50">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav className="flex items-center h-11 overflow-x-auto no-scrollbar gap-1.5 sm:gap-2 py-1">
                    <Link
                        href="/marketplace/services"
                        className={`inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-semibold px-3 py-1.5 rounded-full transition-all shrink-0 ${
                            !activeCategory
                                ? 'bg-indigo-600 text-white shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'
                        }`}
                    >
                        <Sparkles className="w-3 h-3" />
                        <span>{__('general.all_services') || 'All Services'}</span>
                    </Link>

                    <div className="h-4 w-px bg-slate-200 shrink-0 mx-1"></div>

                    {loading ? (
                        Array.from({ length: 8 }).map((_, i) => (
                            <Skeleton key={i} className="h-6 w-24 rounded-full shrink-0" />
                        ))
                    ) : (
                        categories.map((category) => {
                            if (!category) return null;
                            const catSlug = (category.slug || '').toLowerCase();
                            const catName = (category.name || '').toLowerCase();
                            const catId = (category.id ?? '').toString();
                            const activeStr = (activeCategory ?? '').toString().toLowerCase();

                            const isActive =
                                Boolean(activeStr) &&
                                (activeStr === catSlug || activeStr === catId || activeStr === catName);

                            return (
                                <Link
                                    key={category.id || category.slug || category.name}
                                    href={`/marketplace/services?category=${encodeURIComponent(category.slug || category.id || '')}`}
                                    className={`whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-full transition-all shrink-0 ${
                                        isActive
                                            ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-200/80'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'
                                    }`}
                                >
                                    {category.name || ''}
                                </Link>
                            );
                        })
                    )}
                </nav>
            </div>
        </div>
    );
}
