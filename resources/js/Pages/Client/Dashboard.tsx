import { Head, usePage } from '@inertiajs/react';
import {
    AlertCircle, Bell, CheckCircle2, ChevronRight, CreditCard, FolderKanban, KeyRound, LifeBuoy,
    MessageSquare, Plus, RefreshCw, Settings, ShoppingBag, UserRound, Wallet,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SafeLink from '@/Components/SafeLink';
import MonthlyRanking, { MonthlyRankingData } from '@/Components/Client/MonthlyRanking';
import { DataLockinVault, VaultAssetItem } from './Components/DataLockinVault';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type InvoiceRow = { number: string; amount_formatted: string; due_date: string | null; is_overdue: boolean; pay_url: string };
type AttentionItem =
    | { type: 'invoice_due' | 'invoice_overdue'; number: string; amount: string; date: string | null; url: string }
    | { type: 'renewal'; name: string; days: number; url: string }
    | { type: 'notifications'; count: number; url: string }
    | { type: 'profile'; percent: number; url: string };

type HomeData = {
    money: {
        total_due: number;
        total_due_formatted: string;
        unpaid_count: number;
        overdue_count: number;
        next_invoice: InvoiceRow | null;
        wallet_formatted: string;
        next_renewal: { name: string; date: string; days_left: number; auto_renew: boolean } | null;
        links: { invoices: string; add_balance: string; subscriptions: string };
    };
    attention: AttentionItem[];
    services: {
        projects: { id: number; name: string; progress: number; url: string }[];
        projects_count: number;
        subscriptions: { name: string; expires_at: string | null }[];
    };
    rewards: {
        tier: string;
        tier_name: string;
        balance: number;
        lifetime_points: number;
        progress: number;
        next_tier_name: string | null;
        points_to_next: number;
        monthly: MonthlyRankingData;
    };
};

/**
 * Client home. Answers, in order: what do I owe and where do I pay, what needs me,
 * what do I have, and how am I doing (badge and this month's rank).
 */
export default function Dashboard({ home, vaultAssets = [], vaultStats }: { home: HomeData; vaultAssets?: VaultAssetItem[]; vaultStats?: any }) {
    const { auth, locale } = usePage().props as any;
    const firstName = String(auth?.user?.name ?? '').trim().split(/\s+/)[0] || '';
    const formatDate = (date: string | null) =>
        date ? new Date(date + 'T00:00:00').toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

    return (
        <AuthenticatedLayout>
            <Head title={__('client_home.nav.home')} />
            <div className="mx-auto w-full max-w-[1100px] px-4 py-6 sm:px-6 sm:py-10">
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{__('client_home.greeting', { name: firstName })}</h1>

                <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <MoneyCard money={home.money} formatDate={formatDate} />
                        {home.attention.length > 0 && <AttentionList items={home.attention} formatDate={formatDate} />}
                        <div className="lg:hidden"><RewardsCard rewards={home.rewards} /></div>
                        <ServicesCard services={home.services} formatDate={formatDate} />
                        <Shortcuts />
                        {vaultAssets.length > 0 && <DataLockinVault assets={vaultAssets} vaultStats={vaultStats} />}
                    </div>
                    <aside className="hidden lg:block">
                        <div className="sticky top-24"><RewardsCard rewards={home.rewards} /></div>
                    </aside>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Card({ title, action, children, className }: { title?: string; action?: React.ReactNode; children: React.ReactNode; className?: string }) {
    return (
        <section className={cn('rounded-[24px] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-6', className)}>
            {(title || action) && (
                <div className="mb-4 flex items-center justify-between gap-3">
                    {title && <h2 className="text-base font-bold">{title}</h2>}
                    {action}
                </div>
            )}
            {children}
        </section>
    );
}

function MoneyCard({ money, formatDate }: { money: HomeData['money']; formatDate: (d: string | null) => string }) {
    const next = money.next_invoice;
    const owes = money.unpaid_count > 0;

    return (
        <Card title={__('client_home.money.title')} action={<TextLink href={money.links.invoices}>{__('client_home.money.all_invoices')}</TextLink>}>
            {owes ? (
                <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.money.you_owe')}</p>
                        <p className="mt-1 text-3xl font-bold tracking-tight sm:text-4xl" dir="ltr">{money.total_due_formatted}</p>
                        <p className="mt-2 text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                            {money.unpaid_count === 1 ? __('client_home.money.invoices_one') : __('client_home.money.invoices_many', { count: money.unpaid_count })}
                            {money.overdue_count > 0 && (
                                <span className="ms-2 font-semibold text-rose-600">· {__('client_home.money.overdue', { count: money.overdue_count })}</span>
                            )}
                        </p>
                        {next && (
                            <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                                {__('client_home.money.next_invoice', { number: next.number })} · <span dir="ltr">{next.amount_formatted}</span>
                                {next.due_date && ` · ${__(next.is_overdue ? 'client_home.money.was_due' : 'client_home.money.due_on', { date: formatDate(next.due_date) })}`}
                            </p>
                        )}
                    </div>
                    {next && (
                        <SafeLink href={next.pay_url} className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white shadow-sm transition-colors hover:bg-[#0077ed]">
                            <CreditCard className="h-4 w-4" />{__('client_home.money.pay_now')}
                        </SafeLink>
                    )}
                </div>
            ) : (
                <div className="flex items-center gap-3">
                    <CheckCircle2 className="h-9 w-9 shrink-0 text-emerald-600" />
                    <div>
                        <p className="text-lg font-bold">{__('client_home.money.all_set')}</p>
                        <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.money.all_set_hint')}</p>
                    </div>
                </div>
            )}

            <div className="mt-5 grid grid-cols-1 gap-3 border-t border-black/5 pt-4 text-sm dark:border-white/10 sm:grid-cols-2">
                <div className="flex items-center justify-between gap-3">
                    <span className="flex items-center gap-2 text-[#1d1d1f]/70 dark:text-zinc-300">
                        <Wallet className="h-4 w-4 text-emerald-600" />{__('client_home.money.wallet')}: <b dir="ltr">{money.wallet_formatted}</b>
                    </span>
                    <TextLink href={money.links.add_balance}>{__('client_home.money.add_balance')}</TextLink>
                </div>
                {money.next_renewal && (
                    <div className="flex items-center justify-between gap-3">
                        <span className="flex items-center gap-2 text-[#1d1d1f]/70 dark:text-zinc-300">
                            <RefreshCw className="h-4 w-4 text-[#0071e3]" />
                            {__(money.next_renewal.auto_renew ? 'client_home.money.renewal' : 'client_home.money.renewal_ends', { name: money.next_renewal.name, date: formatDate(money.next_renewal.date) })}
                        </span>
                        <TextLink href={money.links.subscriptions}>{__('client_home.money.manage')}</TextLink>
                    </div>
                )}
            </div>
        </Card>
    );
}

function AttentionList({ items, formatDate }: { items: AttentionItem[]; formatDate: (d: string | null) => string }) {
    const row = (item: AttentionItem) => {
        switch (item.type) {
            case 'invoice_due':
                return { icon: CreditCard, tone: 'text-[#0071e3]', text: __('client_home.attention.invoice_due', { number: item.number, amount: item.amount }) + (item.date ? ` · ${formatDate(item.date)}` : '') };
            case 'invoice_overdue':
                return { icon: AlertCircle, tone: 'text-rose-600', text: __('client_home.attention.invoice_overdue', { number: item.number, amount: item.amount }) };
            case 'renewal':
                return { icon: RefreshCw, tone: 'text-amber-600', text: item.days === 0 ? __('client_home.attention.renewal_today', { name: item.name }) : __('client_home.attention.renewal', { name: item.name, days: item.days }) };
            case 'notifications':
                return { icon: Bell, tone: 'text-[#0071e3]', text: __('client_home.attention.notifications', { count: item.count }) };
            case 'profile':
                return { icon: UserRound, tone: 'text-emerald-600', text: __('client_home.attention.profile', { percent: item.percent }) };
        }
    };

    return (
        <Card title={__('client_home.attention.title')}>
            <ul className="-my-1 divide-y divide-black/5 dark:divide-white/10">
                {items.map((item, i) => {
                    const { icon: Icon, tone, text } = row(item);
                    return (
                        <li key={i}>
                            <SafeLink href={item.url} className="flex items-center gap-3 py-3 text-[15px] hover:text-[#0071e3]">
                                <Icon className={cn('h-5 w-5 shrink-0', tone)} />
                                <span className="flex-1">{text}</span>
                                <ChevronRight className="h-4 w-4 shrink-0 text-[#1d1d1f]/30 rtl:rotate-180" />
                            </SafeLink>
                        </li>
                    );
                })}
            </ul>
        </Card>
    );
}

function RewardsCard({ rewards }: { rewards: HomeData['rewards'] }) {
    const { me, participants } = rewards.monthly;

    return (
        <Card title={__('client_home.rewards.title')} action={<TextLink href="/loyalty#ranking">{__('client_home.rewards.see_ranking')}</TextLink>}>
            <div className="flex items-center gap-4">
                <img src={`/images/tiers/${rewards.tier}.svg`} alt="" className="h-16 w-16 shrink-0" />
                <div className="min-w-0">
                    <p className="text-xl font-bold">{rewards.tier_name}</p>
                    <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.rewards.points', { count: rewards.balance.toLocaleString('en-US') })}</p>
                </div>
            </div>
            <div className="mt-4">
                <div className="h-2 overflow-hidden rounded-full bg-[#f5f5f7] dark:bg-zinc-800" role="progressbar" aria-valuenow={rewards.progress} aria-valuemin={0} aria-valuemax={100}>
                    <div className="h-full rounded-full bg-[#0071e3]" style={{ width: `${rewards.progress}%` }} />
                </div>
                <p className="mt-2 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                    {rewards.next_tier_name
                        ? __('client_home.rewards.to_next', { points: rewards.points_to_next.toLocaleString('en-US'), tier: rewards.next_tier_name })
                        : __('client_home.rewards.top_tier')}
                </p>
            </div>

            <div className="mt-5 border-t border-black/5 pt-4 dark:border-white/10">
                <div className="mb-2 flex items-baseline justify-between gap-2">
                    <h3 className="text-sm font-bold">{__('client_home.rewards.this_month')}</h3>
                    {me.position !== null && (
                        <span className="text-sm font-semibold text-[#0071e3]">
                            {__('client_home.rewards.rank', { position: me.position, total: participants })} · {__('client_home.rewards.top_percent', { percent: me.top_percent ?? 100 })}
                        </span>
                    )}
                </div>
                {me.position === null && rewards.monthly.top.length > 0 && (
                    <p className="mb-2 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.rewards.not_ranked')}</p>
                )}
                <MonthlyRanking ranking={rewards.monthly} compact />
            </div>
        </Card>
    );
}

function ServicesCard({ services, formatDate }: { services: HomeData['services']; formatDate: (d: string | null) => string }) {
    return (
        <Card title={__('client_home.services.title')}>
            <div className="mb-2 flex items-center justify-between">
                <h3 className="text-sm font-bold text-[#1d1d1f]/70 dark:text-zinc-300">{__('client_home.services.projects')}</h3>
                {services.projects_count > 0 && <TextLink href="/projects">{__('client_home.services.all_projects')}</TextLink>}
            </div>
            {services.projects.length > 0 ? (
                <ul className="space-y-3">
                    {services.projects.map((project) => (
                        <li key={project.id}>
                            <SafeLink href={project.url} className="block rounded-2xl bg-[#f5f5f7] p-3 hover:bg-[#ebebed] dark:bg-zinc-800 dark:hover:bg-zinc-700">
                                <div className="flex items-center justify-between gap-3 text-sm font-semibold">
                                    <span className="truncate">{project.name}</span>
                                    <span className="shrink-0 tabular-nums" dir="ltr">{project.progress}%</span>
                                </div>
                                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
                                    <div className="h-full rounded-full bg-emerald-500" style={{ width: `${project.progress}%` }} />
                                </div>
                            </SafeLink>
                        </li>
                    ))}
                </ul>
            ) : (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#f5f5f7] p-4 text-sm dark:bg-zinc-800">
                    <span className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.services.no_projects')}</span>
                    <SafeLink href="/projects/create-new" className="inline-flex items-center gap-1.5 font-semibold text-[#0071e3]">
                        <Plus className="h-4 w-4" />{__('client_home.services.start_project')}
                    </SafeLink>
                </div>
            )}

            {services.subscriptions.length > 0 && (
                <>
                    <h3 className="mb-2 mt-5 text-sm font-bold text-[#1d1d1f]/70 dark:text-zinc-300">{__('client_home.services.subscriptions')}</h3>
                    <ul className="divide-y divide-black/5 text-sm dark:divide-white/10">
                        {services.subscriptions.map((s, i) => (
                            <li key={i} className="flex items-center justify-between gap-3 py-2">
                                <span className="font-medium">{s.name}</span>
                                {s.expires_at && <span className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.services.until', { date: formatDate(s.expires_at) })}</span>}
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </Card>
    );
}

function Shortcuts() {
    const items = [
        { href: '/services', icon: ShoppingBag, label: __('client_home.services.store') },
        { href: '/tickets', icon: LifeBuoy, label: __('client_home.services.support') },
        { href: '/messages', icon: MessageSquare, label: __('client_home.services.messages') },
        { href: '/projects', icon: FolderKanban, label: __('client_home.services.projects') },
        { href: '/my-licenses', icon: KeyRound, label: __('client_home.services.licenses') },
        { href: '/settings', icon: Settings, label: __('client_home.nav.settings') },
    ];

    return (
        <Card title={__('client_home.services.shortcuts')}>
            <div className="grid grid-cols-3 gap-3 sm:grid-cols-6">
                {items.map(({ href, icon: Icon, label }) => (
                    <SafeLink key={href} href={href} className="flex flex-col items-center gap-2 rounded-2xl p-3 text-center text-xs font-medium hover:bg-[#f5f5f7] dark:hover:bg-zinc-800">
                        <span className="flex h-11 w-11 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3]"><Icon className="h-5 w-5" /></span>
                        {label}
                    </SafeLink>
                ))}
            </div>
        </Card>
    );
}

function TextLink({ href, children }: { href: string; children: React.ReactNode }) {
    return (
        <SafeLink href={href} className="shrink-0 text-sm font-semibold text-[#0071e3] hover:underline">
            {children}
        </SafeLink>
    );
}
