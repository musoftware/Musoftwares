import SafeLink from '@/Components/SafeLink';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** The three referral pages read as one: link, earnings, people. Plain links, one row on phones. */
export default function ReferralTabs({ active }: { active: 'link' | 'earnings' | 'people' }) {
    const tabs = [
        { id: 'link', href: '/referrals', label: __('client_rewards.referrals.tab_link') },
        { id: 'earnings', href: '/referrals/earnings', label: __('client_rewards.referrals.tab_earnings') },
        { id: 'people', href: '/referrals/registers', label: __('client_rewards.referrals.tab_people') },
    ];

    return (
        <nav className="flex gap-1 overflow-x-auto rounded-full bg-black/[0.04] p-1 dark:bg-white/[0.06]" aria-label={__('client_rewards.referrals.title')}>
            {tabs.map((tab) => (
                <SafeLink
                    key={tab.id}
                    href={tab.href}
                    aria-current={active === tab.id ? 'page' : undefined}
                    className={cn(
                        'flex-1 whitespace-nowrap rounded-full px-4 py-2 text-center text-sm font-semibold transition-colors',
                        active === tab.id ? 'bg-white text-[#1d1d1f] shadow-sm dark:bg-zinc-800 dark:text-white' : 'text-[#1d1d1f]/60 hover:text-[#1d1d1f] dark:text-zinc-400',
                    )}
                >
                    {tab.label}
                </SafeLink>
            ))}
        </nav>
    );
}
