import { Award, Bell, FileText, Home, Menu } from 'lucide-react';
import SafeLink from '@/Components/SafeLink';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * Phone tab bar for signed-in clients, like the bottom bar of the Facebook app: the four places a
 * client goes most, plus "Menu" for everything else (opens the existing drawer).
 */
export default function ClientBottomNav({ unread, onOpenMenu }: { unread: number; onOpenMenu: () => void }) {
    const path = typeof window !== 'undefined' ? window.location.pathname : '';
    const tabs = [
        { href: '/dashboard', icon: Home, label: __('client_home.nav.home'), active: path === '/dashboard' },
        { href: '/billing/invoices', icon: FileText, label: __('client_home.nav.billing'), active: path.startsWith('/billing') || path.startsWith('/financial') },
        { href: '/loyalty', icon: Award, label: __('client_home.nav.rewards'), active: path.startsWith('/loyalty') },
        { href: '/notifications', icon: Bell, label: __('client_home.nav.alerts'), active: path.startsWith('/notifications'), dot: unread > 0 },
    ];
    const item = 'relative flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium';

    return (
        <nav
            className="fixed inset-x-0 bottom-0 z-40 border-t border-black/5 bg-white/95 backdrop-blur-md dark:border-white/10 dark:bg-[#0f172a]/95 md:hidden"
            style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}
            aria-label={__('client_home.nav.menu')}
        >
            <div className="mx-auto flex max-w-md">
                {tabs.map(({ href, icon: Icon, label, active, dot }) => (
                    <SafeLink key={href} href={href} aria-current={active ? 'page' : undefined}
                        className={cn(item, active ? 'text-[#0071e3]' : 'text-[#1d1d1f]/60 dark:text-zinc-400')}>
                        <span className="relative">
                            <Icon className="h-6 w-6" strokeWidth={active ? 2.4 : 1.8} />
                            {dot && <span className="absolute -end-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-rose-500 dark:border-[#0f172a]" />}
                        </span>
                        {label}
                    </SafeLink>
                ))}
                <button type="button" onClick={onOpenMenu} className={cn(item, 'text-[#1d1d1f]/60 dark:text-zinc-400')}>
                    <Menu className="h-6 w-6" strokeWidth={1.8} />
                    {__('client_home.nav.menu')}
                </button>
            </div>
        </nav>
    );
}
