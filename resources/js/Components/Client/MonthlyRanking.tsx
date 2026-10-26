import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export type MonthlyRankingData = {
    month: string;
    participants: number;
    me: { position: number | null; points: number; top_percent: number | null };
    top: { position: number; points: number; tier: string; is_me: boolean }[];
};

/**
 * This month's ranking. Other clients appear only as "Client #n" with their badge and points;
 * the server never sends their names (MonthlyLeaderboardService).
 */
export default function MonthlyRanking({ ranking, compact = false }: { ranking: MonthlyRankingData; compact?: boolean }) {
    const { me, top } = ranking;
    const meInTop = top.some((row) => row.is_me);

    if (top.length === 0) {
        return <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.rewards.empty')}</p>;
    }

    return (
        <ol className={cn('divide-y divide-black/5 dark:divide-white/10', compact ? 'text-sm' : 'text-[15px]')}>
            {top.map((row) => (
                <Row key={row.position} position={row.position} tier={row.tier} points={row.points} isMe={row.is_me} />
            ))}
            {!meInTop && me.position !== null && (
                <>
                    <li className="py-1 text-center text-[#1d1d1f]/30 dark:text-zinc-600" aria-hidden="true">⋯</li>
                    <Row position={me.position} tier={null} points={me.points} isMe />
                </>
            )}
            {!compact && me.position === null && (
                <li className="pt-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.rewards.not_ranked')}</li>
            )}
        </ol>
    );
}

function Row({ position, tier, points, isMe }: { position: number; tier: string | null; points: number; isMe: boolean }) {
    return (
        <li className={cn('flex items-center gap-3 py-2.5', isMe && 'rounded-xl bg-[#0071e3]/[0.07] px-2 -mx-2')}>
            <span className={cn('w-7 shrink-0 text-center font-bold tabular-nums', position <= 3 ? 'text-amber-600' : 'text-[#1d1d1f]/50 dark:text-zinc-500')}>
                {position}
            </span>
            {tier ? (
                <img src={`/images/tiers/${tier}.svg`} alt="" className="h-6 w-6 shrink-0" loading="lazy" />
            ) : (
                <span className="h-6 w-6 shrink-0" />
            )}
            <span className={cn('flex-1 truncate', isMe ? 'font-semibold text-[#0071e3]' : 'text-[#1d1d1f] dark:text-zinc-200')}>
                {isMe ? __('client_home.rewards.you') : __('client_home.rewards.client', { position })}
            </span>
            <span className="shrink-0 font-semibold tabular-nums" dir="ltr">{points.toLocaleString('en-US')}</span>
        </li>
    );
}
