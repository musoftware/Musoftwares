import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { MessageCircle, LayoutGrid, User } from 'lucide-react';
import SafeLink from '@/Components/SafeLink';
import type { SiteFooterData } from '@/Components/Public/SiteFooter';

interface MobileBottomActionBarProps {
    /** Kept for compatibility; the bar now opens WhatsApp like the Blade version. */
    onOpenTicket?: () => void;
}

/**
 * Phone action bar. Items come from App\Support\SiteNav::footer()['mobile_bar'], the same data the
 * Blade partial resources/views/layouts/site-mobile-actions.blade.php renders, so both look alike.
 */
export default function MobileBottomActionBar(_props: MobileBottomActionBarProps) {
    const { siteFooter } = usePage().props as unknown as { siteFooter: SiteFooterData };
    const bar = siteFooter.mobile_bar;
    const [isVisible, setIsVisible] = useState(true);
    const [lastScrollY, setLastScrollY] = useState(0);

    useEffect(() => {
        const handleScroll = () => {
            const currentScrollY = window.scrollY;
            if (currentScrollY > 100 && currentScrollY > lastScrollY + 15) {
                setIsVisible(false);
            } else if (currentScrollY < lastScrollY - 10) {
                setIsVisible(true);
            }
            setLastScrollY(currentScrollY);
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, [lastScrollY]);

    const sideLink = 'flex-1 flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-zinc-600 hover:text-zinc-900 active:bg-zinc-100 transition text-[11px] font-bold';

    return (
        <div
            className={`lg:hidden fixed bottom-0 inset-x-0 z-40 px-4 pb-4 pt-2 bg-white/90 backdrop-blur-xl border-t border-zinc-200/80 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] transition-transform duration-300 ${
                isVisible ? 'translate-y-0' : 'translate-y-full'
            }`}
        >
            <div className="max-w-md mx-auto flex items-center justify-between gap-2">
                <SafeLink href={bar.pricing.url} className={sideLink}>
                    <LayoutGrid className="w-5 h-5 mb-0.5 text-zinc-700" />
                    <span>{bar.pricing.label}</span>
                </SafeLink>

                <a
                    href={bar.whatsapp.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex-[2] flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-zinc-950 text-white hover:bg-zinc-800 active:scale-95 transition shadow-sm text-xs font-bold uppercase tracking-wider"
                >
                    <MessageCircle className="w-4 h-4 text-emerald-400 fill-emerald-400/20" />
                    <span>{bar.whatsapp.label}</span>
                </a>

                <SafeLink href={bar.account.url} className={sideLink}>
                    <User className="w-5 h-5 mb-0.5 text-zinc-700" />
                    <span>{bar.account.label}</span>
                </SafeLink>
            </div>
        </div>
    );
}
