import React, { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import { RuntimeStatusBanner } from '@/Components/Tools/RuntimeStatusBanner';
import { Download, CreditCard, LayoutGrid, LifeBuoy } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';

interface ToolsPublicLayoutProps extends PropsWithChildren {
    title: string;
    activeNav?: 'explore' | 'downloads' | 'billing' | 'licenses' | 'tickets';
    toolSlug?: string;
}

export default function ToolsPublicLayout({
    children,
    title,
    activeNav = 'explore',
    toolSlug,
}: ToolsPublicLayoutProps) {
    useInertiaNotifications();
    const { auth } = usePage().props as any;
    const isAuthed = !!auth?.user;

    const navItems = [
        { id: 'explore',   label: 'Browse Tools',      icon: LayoutGrid,   href: route('tools.explore'),   public: true  },
        { id: 'downloads', label: 'My Downloads',    icon: Download,     href: route('tools.billing'), public: false },
        { id: 'billing',   label: 'Licenses & Billing', icon: CreditCard,   href: route('tools.billing'),   public: false },
        { id: 'tickets',   label: 'Support Tickets',  icon: LifeBuoy,     href: route('tickets.index'),   public: false },
    ];

    const visibleItems = navItems.filter(item => item.public || isAuthed);

    return (
        <PublicLayout>
            <Head title={`${title} — Musoftwares Tools`} />

            <div className="w-full bg-[#fbfbfd] text-[#1d1d1f] min-h-screen">
                {/* Runtime status banner */}
                {isAuthed && (
                    <div className="border-b border-black/5 bg-white">
                        <RuntimeStatusBanner toolSlug={toolSlug} />
                    </div>
                )}

                {/* Sub-Navigation Bar */}
                <div className="border-b border-black/5 bg-white/80 backdrop-blur-md py-3 px-6 sm:px-10 sticky top-14 z-30">
                    <div className="max-w-[1400px] mx-auto flex items-center justify-between">
                        <div className="flex items-center space-x-2 sm:space-x-3 overflow-x-auto">
                            {visibleItems.map((item) => {
                                const Icon = item.icon;
                                const isActive = activeNav === item.id;
                                return (
                                    <Link
                                        key={item.id}
                                        href={item.href}
                                        className={`flex items-center gap-2 px-4 py-2 text-xs rounded-full font-medium transition-all ${
                                            isActive
                                                ? 'bg-[#0071e3] text-white shadow-sm font-semibold'
                                                : 'text-[#1d1d1f]/70 hover:text-[#1d1d1f] hover:bg-[#f5f5f7]'
                                        }`}
                                    >
                                        <Icon className="h-3.5 w-3.5" />
                                        <span>{item.label}</span>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Main content */}
                <main className="max-w-[1400px] mx-auto px-6 sm:px-10 py-10">
                    {children}
                </main>
            </div>
        </PublicLayout>
    );
}
