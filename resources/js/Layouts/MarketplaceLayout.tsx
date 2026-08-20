import React, { PropsWithChildren, ReactNode } from 'react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import PublicLayout from '@/Layouts/PublicLayout';
import MarketplaceCategoryNav from '@/Components/Marketplace/Layout/MarketplaceCategoryNav';

export default function MarketplaceLayout({
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    useInertiaNotifications();

    return (
        <PublicLayout>
            <div className="w-full bg-[#111111] text-[#E5E5E5] min-h-screen">
                <MarketplaceCategoryNav />
                {/* Main content area */}
                <main className="w-full">
                    {children}
                </main>
            </div>
        </PublicLayout>
    );
}
