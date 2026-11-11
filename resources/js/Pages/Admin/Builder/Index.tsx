import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopPager, { Paginated } from '@/Components/Admin/Shop/ShopPager';

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

/** Every AI builder chat, newest first, with the ones waiting for an answer easy to find. */
export default function BuilderIndex({ projects, waitingOnly, learning, defaultFactor }: { projects: Paginated<Project>; waitingOnly: boolean; learning: Learned[]; defaultFactor: number }) {
    return (
        <AdminSidebarLayout>
            <Head title={t('AI builder', 'منشئ البرامج')} />
            <div className="mx-auto max-w-5xl space-y-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">{t('AI builder chats', 'محادثات منشئ البرامج')}</h1>
                    <Link href={waitingOnly ? '/admin/builder' : '/admin/builder?waiting=1'} className="rounded-lg border px-4 py-2 text-sm font-semibold">
                        {waitingOnly ? t('Show all', 'اعرض الكل') : t('Waiting for an answer', 'مستني رد')}
                    </Link>
                </div>

                <PriceLearning learning={learning} defaultFactor={defaultFactor} />

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
