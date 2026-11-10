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

/** Every AI builder chat, newest first, with the ones waiting for an answer easy to find. */
export default function BuilderIndex({ projects, waitingOnly }: { projects: Paginated<Project>; waitingOnly: boolean }) {
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

                {projects.data.length === 0 && <p className="text-slate-500">{t('No chats yet.', 'لسه مفيش محادثات.')}</p>}

                <ul className="divide-y rounded-xl border bg-white">
                    {projects.data.map((project) => (
                        <li key={project.id}>
                            <Link href={`/admin/builder/${project.uuid}`} className="flex flex-col gap-1 p-4 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <p className="truncate font-semibold" dir="auto">{project.title || t('(no title yet)', '(لسه من غير اسم)')}</p>
                                    <p className="text-sm text-slate-500" dir="auto">{project.client_name || '—'} · {project.updated_at}</p>
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
