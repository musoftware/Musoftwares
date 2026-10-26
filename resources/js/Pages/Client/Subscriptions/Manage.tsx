import { router, usePage } from '@inertiajs/react';
import { RefreshCw, Sparkles, Wallet } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import { useConfirm } from '@/hooks/useConfirm';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface Subscription {
    id: number;
    plan_name: string;
    amount: number;
    currency: string;
    status: string;
    expires_on: string | null;
    auto_renew: boolean;
}

/** Each subscription as one card: what it is, when it ends or renews, and the one thing to do. */
export default function Manage({ subscriptions = [], walletBalance = 0, currency }: { subscriptions: Subscription[]; walletBalance: number; currency: string }) {
    const { locale } = usePage().props as any;
    const { confirm, confirmDialog } = useConfirm();
    const money = (amount: number) => {
        try {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);
        } catch {
            return `${amount} ${currency}`;
        }
    };
    const formatDate = (date: string | null) =>
        date ? new Date(date + 'T00:00:00').toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';
    const daysLeft = (date: string | null) => (date ? Math.ceil((new Date(date + 'T00:00:00').getTime() - Date.now()) / 86400000) : null);

    const renew = async (sub: Subscription) => {
        if (walletBalance < sub.amount) {
            const add = await confirm({ title: __('client_home.subs.renew'), description: __('client_home.subs.low_balance', { balance: money(walletBalance), price: money(sub.amount) }) });
            if (add) router.visit('/financial/add-balance');
            return;
        }
        if (await confirm({ title: __('client_home.subs.renew'), description: __('client_home.subs.renew_confirm', { name: sub.plan_name, price: money(sub.amount) }) })) {
            router.post(route('subscriptions.renew'), { id: sub.id });
        }
    };

    const stopAutoRenew = async (sub: Subscription) => {
        if (await confirm({ title: __('client_home.subs.auto_off'), description: __('client_home.subs.auto_off_confirm', { name: sub.plan_name, date: formatDate(sub.expires_on) }), variant: 'danger' })) {
            router.post(route('subscriptions.cancel'), { id: sub.id });
        }
    };

    return (
        <ClientPage
            title={__('client_home.subs.title')}
            hint={__('client_home.subs.hint')}
            action={<PrimaryAction href={route('subscriptions.plans')} icon={<Sparkles className="h-4 w-4" />}>{__('client_home.subs.browse')}</PrimaryAction>}
        >
            <Card className="flex flex-wrap items-center justify-between gap-3 text-sm">
                <span className="flex items-center gap-2 text-[#1d1d1f]/70 dark:text-zinc-300">
                    <Wallet className="h-4 w-4 text-emerald-600" />
                    {__('client_home.subs.wallet', { balance: money(walletBalance) })}
                </span>
                <TextAction href="/financial/add-balance">{__('client_home.money.add_balance')}</TextAction>
            </Card>

            <Section>
                {subscriptions.length === 0 ? (
                    <EmptyNote action={<TextAction href={route('subscriptions.plans')}>{__('client_home.subs.browse')}</TextAction>}>
                        {__('client_home.subs.empty')}
                    </EmptyNote>
                ) : (
                    <ul className="space-y-3">
                        {subscriptions.map((sub) => {
                            const left = daysLeft(sub.expires_on);
                            const ended = sub.status !== 'active' || (left !== null && left < 0);
                            const endingSoon = !ended && !sub.auto_renew && left !== null && left <= 14;
                            const state = ended ? 'ended' : endingSoon ? 'ending' : 'active';

                            return (
                                <li key={sub.id}>
                                    <Card>
                                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="text-lg font-bold">{sub.plan_name}</h3>
                                                    <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                        state === 'active' && 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
                                                        state === 'ending' && 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
                                                        state === 'ended' && 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400')}>
                                                        {__(`client_home.subs.${state}`)}
                                                    </span>
                                                </div>
                                                {sub.amount > 0 && <p className="mt-1 text-sm font-semibold text-[#0071e3]" dir="auto">{__('client_home.subs.per_month', { amount: money(sub.amount) })}</p>}
                                                <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                                                    {__(ended ? 'client_home.subs.ended_on' : sub.auto_renew ? 'client_home.subs.renews_on' : 'client_home.subs.ends_on', { date: formatDate(sub.expires_on) })}
                                                    {!ended && left !== null && ` ${__('client_home.subs.days_left', { days: left })}`}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-4">
                                                {(ended || !sub.auto_renew) && (
                                                    <PrimaryAction onClick={() => renew(sub)} icon={<RefreshCw className="h-4 w-4" />}>{__('client_home.subs.renew')}</PrimaryAction>
                                                )}
                                                {!ended && sub.auto_renew && (
                                                    <TextAction danger onClick={() => stopAutoRenew(sub)}>{__('client_home.subs.auto_off')}</TextAction>
                                                )}
                                            </div>
                                        </div>
                                    </Card>
                                </li>
                            );
                        })}
                    </ul>
                )}
                <p className="mt-4 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                    {__('client_home.subs.invoices')} <TextAction href="/billing/invoices">{__('client_home.nav.billing')}</TextAction>
                </p>
            </Section>
            {confirmDialog}
        </ClientPage>
    );
}
