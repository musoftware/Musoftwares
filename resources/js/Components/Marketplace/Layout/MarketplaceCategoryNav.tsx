import React, { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import { ChevronRight } from 'lucide-react';
import { Skeleton } from '@/Components/ui/skeleton';

interface Category {
    id: number;
    name: string;
    slug: string;
}

export default function MarketplaceCategoryNav() {
    // Try to get from Inertia props first, fallback to empty array
    const { categories: inertiaCategories } = usePage().props as any;
    
    const [categories, setCategories] = useState<Category[]>(
        Array.isArray(inertiaCategories) ? inertiaCategories : []
    );
    const [loading, setLoading] = useState(!Array.isArray(inertiaCategories) || inertiaCategories.length === 0);

    useEffect(() => {
        // If we didn't get categories from Inertia props, fetch them client-side
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
        return null; // Don't render empty nav bar
    }

    return (
        <div className="hidden md:block w-full border-b border-gray-200 bg-white">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav className="flex items-center h-12 overflow-x-auto no-scrollbar gap-6">
                    {loading ? (
                        // Skeleton loader
                        Array.from({ length: 8 }).map((_, i) => (
                            <Skeleton key={i} className="h-4 w-24 shrink-0" />
                        ))
                    ) : (
                        categories.map((category) => (
                            <Link
                                key={category.id}
                                href={`/marketplace/services?category=${category.slug || category.id}`}
                                className="whitespace-nowrap text-sm font-medium text-gray-500 hover:text-gray-900 hover:underline hover:underline-offset-8 transition-all shrink-0"
                            >
                                {category.name}
                            </Link>
                        ))
                    )}
                </nav>
            </div>
        </div>
    );
}
