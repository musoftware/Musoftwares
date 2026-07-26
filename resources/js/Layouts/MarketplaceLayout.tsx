import React, { PropsWithChildren, ReactNode } from 'react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import MarketplaceHeader from '@/Components/Marketplace/Layout/MarketplaceHeader';
import MarketplaceCategoryNav from '@/Components/Marketplace/Layout/MarketplaceCategoryNav';
import MarketplaceFooter from '@/Components/Marketplace/Layout/MarketplaceFooter';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

export default function MarketplaceLayout({
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    // Keep notification hook from original layout
    useInertiaNotifications();

    return (
        <div className="flex min-h-screen flex-col bg-white">
            <MarketplaceHeader />
            <MarketplaceCategoryNav />

            {/* Main content area */}
            <main className="flex-1 w-full bg-white">
                {children}
            </main>

            <MarketplaceFooter />

            <FloatingWhatsAppButton
                phoneNumber="201015218548"
                defaultMessage="Hello Mahmoud 👋 I have an inquiry about Musoftware Marketplace"
            />
        </div>
    );
}

