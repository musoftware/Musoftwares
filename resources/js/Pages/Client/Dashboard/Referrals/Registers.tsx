import { usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';
import ClientPage, { Card, EmptyNote, TextAction } from '@/Components/Client/ClientPage';
import ReferralTabs from './Partials/ReferralTabs';
import { __ } from '@/lib/i18n';
import { formatMoney } from '@/lib/utils';

interface ReferredUser {
    id: number;
    name: string;
    email: string; // masked by the server, e.g. a***@gmail.com
    created_at: string | null;
    email_verified_at: string | null;
}

interface RegistersProps {
    referred_users: { data: ReferredUser[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
    commissionByUserId: Record<number, number>;
    global_commission_total: number;
}

/** People who signed up with the client's link, with what each one earned them. */
export default function Registers({ referred_users, commissionByUserId, global_commission_total }: RegistersProps) {
    const { currencies, auth, locale } = usePage().props as any;
    const currency = currencies?.find((c: any) => c.id === auth?.user?.currency_id);
    const formatDate = (date: string | null) =>
        date ? new Date(date + 'T00:00:00').toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

    return (
        <ClientPage title={__('client_rewards.referrals.title')} hint={__('client_rewards.referrals.people_hint')}>
            <ReferralTabs active="people" />

            {referred_users.data.length === 0 ? (
                <EmptyNote icon={<Users className="h-5 w-5" />} action={<TextAction href="/referrals">{__('client_rewards.referrals.copy')}</TextAction>}>
                    {__('client_rewards.referrals.no_people')}
                </EmptyNote>
            ) : (
                <>
                    <p className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                        {__('client_rewards.referrals.people_count', { count: referred_users.total, amount: formatMoney(global_commission_total, currency) })}
                    </p>
                    <Card className="divide-y divide-black/5 !p-0 dark:divide-white/10">
                        {referred_users.data.map((person) => (
                            <div key={person.id} className="flex items-center gap-3 px-4 py-3 sm:px-5">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0071e3]/10 text-sm font-bold text-[#0071e3]">
                                    {String(person.name || '?').substring(0, 1).toUpperCase()}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-semibold">{person.name}</p>
                                    <p className="truncate text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                        <span dir="ltr">{person.email}</span> · {__('client_rewards.referrals.joined', { date: formatDate(person.created_at) })} ·{' '}
                                        <span className={person.email_verified_at ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'}>
                                            {person.email_verified_at ? __('client_rewards.referrals.verified') : __('client_rewards.referrals.not_verified')}
                                        </span>
                                    </p>
                                </div>
                                <span className="shrink-0 font-semibold" dir="ltr">{formatMoney(commissionByUserId[person.id] || 0, currency)}</span>
                            </div>
                        ))}
                    </Card>
                    {referred_users.last_page > 1 && (
                        <div className="flex items-center justify-between text-sm font-semibold">
                            {referred_users.prev_page_url ? <TextAction href={referred_users.prev_page_url}>{__('client_rewards.referrals.previous')}</TextAction> : <span />}
                            <span className="text-[#1d1d1f]/50" dir="ltr">{referred_users.current_page} / {referred_users.last_page}</span>
                            {referred_users.next_page_url ? <TextAction href={referred_users.next_page_url}>{__('client_rewards.referrals.next')}</TextAction> : <span />}
                        </div>
                    )}
                </>
            )}
        </ClientPage>
    );
}
