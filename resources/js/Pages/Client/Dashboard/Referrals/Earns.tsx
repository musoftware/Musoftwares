import { CheckCircle2, Clock, Wallet } from 'lucide-react';
import ClientPage, { Card, PrimaryAction } from '@/Components/Client/ClientPage';
import ReferralTabs from './Partials/ReferralTabs';
import { __ } from '@/lib/i18n';

interface EarnsProps {
    pending_balance_str: string;
    available_commission: number;
    available_commission_str: string;
    withdrawed_commission_str: string;
}

/** Referral earnings in three plain lines: on the way, ready to withdraw, already paid out. */
export default function Earns({ pending_balance_str, available_commission, available_commission_str, withdrawed_commission_str }: EarnsProps) {
    const rows = [
        { icon: Clock, tone: 'text-amber-600', label: __('client_rewards.referrals.pending'), hint: __('client_rewards.referrals.pending_hint'), value: pending_balance_str },
        { icon: Wallet, tone: 'text-[#0071e3]', label: __('client_rewards.referrals.available'), hint: __('client_rewards.referrals.available_hint'), value: available_commission_str },
        { icon: CheckCircle2, tone: 'text-emerald-600', label: __('client_rewards.referrals.paid_out'), hint: null, value: withdrawed_commission_str },
    ];

    return (
        <ClientPage
            title={__('client_rewards.referrals.title')}
            hint={__('client_rewards.referrals.earnings_hint')}
            action={available_commission > 0 ? <PrimaryAction href={route('financial.withdrawals')} icon={<Wallet className="h-4 w-4" />}>{__('client_rewards.referrals.withdraw')}</PrimaryAction> : undefined}
        >
            <ReferralTabs active="earnings" />
            <Card className="divide-y divide-black/5 !p-0 dark:divide-white/10">
                {rows.map(({ icon: Icon, tone, label, hint, value }) => (
                    <div key={label} className="flex items-center gap-3 px-4 py-4 sm:px-5">
                        <Icon className={`h-5 w-5 shrink-0 ${tone}`} />
                        <div className="min-w-0 flex-1">
                            <p className="font-semibold">{label}</p>
                            {hint && <p className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400">{hint}</p>}
                        </div>
                        <span className="shrink-0 text-lg font-bold" dir="ltr">{value}</span>
                    </div>
                ))}
            </Card>
        </ClientPage>
    );
}
