import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { toast } from 'sonner';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopPager, { Paginated } from '@/Components/Admin/Shop/ShopPager';
import ImportProject from '@/Components/Admin/Builder/ImportProject';

interface Project {
    id: number;
    uuid: string;
    title: string | null;
    status: string;
    awaiting_answer: boolean;
    amount: string;
    paid_total: string;
    currency: string;
    build_cost_usd: string;
    chat_cost_usd: number;
    client_name: string | null;
    updated_at: string;
    visitor: Visitor;
}

export interface Person {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    url: string;
}

/** Who started a chat: the signed-in account if any, the address and device, and whose link they came from. */
export interface Visitor {
    ip: string | null;
    country: string | null;
    user_agent: string | null;
    started_at: string | null;
    user: Person | null;
    referrer: Person | null;
}

const SYSTEMS: [RegExp, string][] = [[/android/i, 'Android'], [/iphone|ipad|ios/i, 'iPhone'], [/windows/i, 'Windows'], [/mac os/i, 'Mac'], [/linux/i, 'Linux']];
const BROWSERS: [RegExp, string][] = [[/edg\//i, 'Edge'], [/opr\/|opera/i, 'Opera'], [/samsungbrowser/i, 'Samsung'], [/chrome|crios/i, 'Chrome'], [/firefox|fxios/i, 'Firefox'], [/safari/i, 'Safari']];

/** "Android · Chrome" from the browser's long user agent text. */
export function deviceOf(userAgent: string | null): string {
    if (!userAgent) return '';
    const pick = (list: [RegExp, string][]) => list.find(([pattern]) => pattern.test(userAgent))?.[1];
    return [pick(SYSTEMS), pick(BROWSERS)].filter(Boolean).join(' · ');
}

export const STATUS_LABELS: Record<string, [string, string]> = {
    chatting: ['Talking', 'بيتكلم'],
    quoted: ['Price shown', 'السعر ظاهر'],
    queued: ['Paid, in the queue', 'مدفوع، في الدور'],
    designing: ['Waiting for the design', 'مستني التصميم'],
    building: ['Building', 'بيتبني'],
    ready: ['Delivered', 'اتسلّم'],
    failed: ['Failed', 'فشل'],
};

interface Learned {
    type: string;
    builds: number;
    factor: number;
    learned: boolean;
}

/** How prices follow real cost: each kind of project uses the median work factor of its latest delivered builds. */
function PriceLearning({ learning, defaultFactor }: { learning: Learned[]; defaultFactor: number }) {
    return (
        <details className="rounded-xl border bg-white p-4">
            <summary className="cursor-pointer font-semibold">{t('How prices learn from real builds', 'الأسعار بتتعلم إزاي من الشغل الحقيقي')}</summary>
            <p className="mt-2 text-sm text-slate-500">
                {t(`Each kind of project uses the real cost of its last delivered builds once it has 5. Until then the factor is ${defaultFactor}.`,
                    `كل نوع مشروع بيستخدم التكلفة الحقيقية لآخر مشاريعه اللي اتسلّمت لما يبقى عنده 5. لحد كده المعامل ${defaultFactor}.`)}
            </p>
            {learning.length === 0 && <p className="mt-2 text-sm">{t('No delivered builds yet.', 'لسه مفيش مشاريع اتسلّمت.')}</p>}
            {learning.length > 0 && (
                <ul className="mt-2 divide-y text-sm">
                    {learning.map((row) => (
                        <li key={row.type} className="flex flex-wrap justify-between gap-2 py-2">
                            <span dir="ltr">{row.type}</span>
                            <span>{t(`${row.builds} builds`, `${row.builds} مشروع`)} · <span dir="ltr">x{row.factor}</span> · {row.learned ? t('learned', 'متعلّم') : t('still the default', 'لسه الافتراضي')}</span>
                        </li>
                    ))}
                </ul>
            )}
        </details>
    );
}

interface DeliveryStats {
    count: number;
    hands_off: number | null;
    accepted: number | null;
    cost_share: number | null;
    cost_usd: number;
    price_usd: number;
}

/** Does the system work without a programmer? Three numbers over the last 30 deliveries. */
function WithoutAProgrammer({ stats }: { stats: DeliveryStats }) {
    const percent = (value: number | null) => (value === null ? '-' : `${value}%`);
    const rows = [
        { label: t('Delivered without you stepping in', 'اتسلّم من غير ما تتدخل'), value: percent(stats.hands_off), good: (stats.hands_off ?? 0) >= 80 },
        { label: t('Kept by the customer without a fix', 'العميل خده من غير ما يطلب تصليح'), value: percent(stats.accepted), good: (stats.accepted ?? 0) >= 80 },
        { label: t('Real cost out of the price paid', 'التكلفة الحقيقية من السعر المدفوع'), value: percent(stats.cost_share), good: stats.cost_share !== null && stats.cost_share <= 50 },
    ];

    return (
        <section className="rounded-xl border bg-white p-4">
            <h2 className="font-semibold">{t('Does it work without a programmer?', 'هل بيشتغل من غير مبرمج؟')}</h2>
            <p className="mt-1 text-sm text-slate-500">
                {stats.count === 0
                    ? t('Counted from the next delivery.', 'العدّ بيبدأ من أول تسليم جاي.')
                    : t(`The last ${stats.count} deliveries. Aim: above 80% for the first two.`, `آخر ${stats.count} تسليم. الهدف: أكتر من 80% في أول اتنين.`)}
            </p>
            <dl className="mt-3 grid gap-3 sm:grid-cols-3">
                {rows.map((row) => (
                    <div key={row.label} className="rounded-lg border p-3">
                        <dt className="text-sm text-slate-500">{row.label}</dt>
                        <dd className={`mt-1 text-2xl font-bold ${stats.count > 0 && row.good ? 'text-emerald-700' : ''}`} dir="ltr">{row.value}</dd>
                    </div>
                ))}
            </dl>
            {stats.count > 0 && (
                <p className="mt-2 text-sm text-slate-500" dir="ltr">
                    {t(`Cost $${stats.cost_usd} · paid $${stats.price_usd}`, `التكلفة $${stats.cost_usd} · المدفوع $${stats.price_usd}`)}
                </p>
            )}
        </section>
    );
}

/** Every AI builder chat, newest first, with the ones waiting for an answer easy to find. */
/** Sends a push to every admin's phone and browsers, to see that the builder's alerts really arrive. */
function TestPush() {
    const send = () => router.post('/admin/builder/push-test', {}, {
        preserveScroll: true,
        onSuccess: () => toast.success(t('Sent. It should reach your phone within a minute.', 'اتبعت. المفروض يوصل موبايلك خلال دقيقة.')),
        onError: () => toast.error(t('Not sent.', 'متبعتش.')),
    });

    return <button type="button" onClick={send} className="rounded-lg border px-4 py-2 text-sm font-semibold">{t('Test notification', 'جرّب الإشعار')}</button>;
}

type ChatTab = 'customers' | 'team';

const TABS: { key: ChatTab; label: [string, string] }[] = [
    { key: 'customers', label: ['Customers and guests', 'العملاء والزوار'] },
    { key: 'team', label: ['Admin chats', 'محادثات الأدمن'] },
];

/** Customers' chats apart from the team's own tests, each tab with how many chats it holds. */
function ChatTabs({ tab, counts, waitingOnly }: { tab: ChatTab; counts: Record<ChatTab, number>; waitingOnly: boolean }) {
    return (
        <div role="tablist" className="flex flex-wrap gap-2 border-b">
            {TABS.map(({ key, label }) => (
                <Link
                    key={key}
                    role="tab"
                    aria-selected={tab === key}
                    href={`/admin/builder?${new URLSearchParams({ ...(key === 'team' ? { tab: 'team' } : {}), ...(waitingOnly ? { waiting: '1' } : {}) })}`}
                    preserveScroll
                    className={`-mb-px min-h-11 border-b-2 px-4 py-2 text-sm font-semibold ${tab === key ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                >
                    {t(...label)} <span className="font-normal text-slate-500" dir="ltr">({counts[key]})</span>
                </Link>
            ))}
        </div>
    );
}

interface IndexProps {
    projects: Paginated<Project>;
    waitingOnly: boolean;
    learning: Learned[];
    defaultFactor: number;
    deliveryStats: DeliveryStats;
    tab: ChatTab;
    tabCounts: Record<ChatTab, number>;
}

export default function BuilderIndex({ projects, waitingOnly, learning, defaultFactor, deliveryStats, tab, tabCounts }: IndexProps) {
    const tabQuery = tab === 'team' ? 'tab=team&' : '';
    return (
        <AdminSidebarLayout>
            <Head title={t('AI builder', 'منشئ البرامج')} />
            <div className="mx-auto max-w-5xl space-y-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">{t('AI builder chats', 'محادثات منشئ البرامج')}</h1>
                    <div className="flex flex-wrap gap-2">
                        <TestPush />
                        <Link href={waitingOnly ? `/admin/builder?${tabQuery}` : `/admin/builder?${tabQuery}waiting=1`} className="rounded-lg border px-4 py-2 text-sm font-semibold">
                            {waitingOnly ? t('Show all', 'اعرض الكل') : t('Waiting for an answer', 'مستني رد')}
                        </Link>
                    </div>
                </div>

                <WithoutAProgrammer stats={deliveryStats} />
                <ImportProject />
                <PriceLearning learning={learning} defaultFactor={defaultFactor} />

                <ChatTabs tab={tab} counts={tabCounts} waitingOnly={waitingOnly} />

                {projects.data.length === 0 && <p className="text-slate-500">{t('No chats yet.', 'لسه مفيش محادثات.')}</p>}

                <ul className="divide-y rounded-xl border bg-white">
                    {projects.data.map((project) => (
                        <li key={project.id}>
                            <Link href={`/admin/builder/${project.uuid}`} className="flex flex-col gap-1 p-4 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <p className="truncate font-semibold" dir="auto">{project.title || t('(no title yet)', '(لسه من غير اسم)')}</p>
                                    <p className="text-sm text-slate-500" dir="auto">
                                        {project.visitor.user
                                            ? <span className="font-semibold text-slate-700">{project.visitor.user.name} <span dir="ltr">{project.visitor.user.email}</span></span>
                                            : (project.client_name || t('Guest', 'زائر'))}
                                        {' · '}{project.updated_at}
                                    </p>
                                    <p className="text-xs text-slate-500" dir="ltr">
                                        {[project.visitor.country, project.visitor.ip, deviceOf(project.visitor.user_agent), project.visitor.referrer && `ref: ${project.visitor.referrer.name}`].filter(Boolean).join(' · ')}
                                    </p>
                                </div>
                                <div className="flex shrink-0 flex-wrap items-center gap-3 text-sm">
                                    {project.awaiting_answer && <span className="font-semibold text-amber-700">{t('Needs an answer', 'محتاج رد')}</span>}
                                    <span>{t(...(STATUS_LABELS[project.status] || [project.status, project.status]))}</span>
                                    <span dir="ltr">{t('paid', 'المدفوع')} {project.paid_total} / {project.amount} {project.currency}</span>
                                    <span className="text-slate-500" dir="ltr">{t('chat', 'الشات')} ${project.chat_cost_usd.toFixed(4)} · {t('build', 'التنفيذ')} ${Number(project.build_cost_usd).toFixed(2)}</span>
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>

                <ShopPager page={projects} />
            </div>
        </AdminSidebarLayout>
    );
}
