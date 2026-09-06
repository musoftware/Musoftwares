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
            <div className="w-full bg-[#fbfbfd] dark:bg-[#090d16] text-[#1d1d1f] dark:text-[#f8fafc] min-h-screen transition-colors duration-200">
                <MarketplaceCategoryNav />
                {/* Main content area */}
                <main className="w-full">
                    {children}
                </main>
            </div>
        </PublicLayout>
    );
}
