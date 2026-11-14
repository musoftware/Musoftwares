import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopPager, { Paginated } from '@/Components/Admin/Shop/ShopPager';

export interface Asker {
    user: { id: number; name: string; email: string } | null;
    ip: string | null;
    page: string | null;
    question: string;
    at: string | null;
}

interface Visitor extends Asker {
    visitor: string;
    questions: number;
}

/** The account's name when the visitor was signed in; otherwise where the guest wrote from. */
export function askerName(asker: Asker, visitor: string): string {
    if (asker.user) return asker.user.name;
    return visitor.startsWith('telegram:') ? t('Telegram user', 'مستخدم تيليجرام') : t('Guest', 'زائر');
}

function VisitorRow({ row }: { row: Visitor }) {
    return (
        <li>
            <Link href={`/admin/assistant-chats/${encodeURIComponent(row.visitor)}`} className="block min-h-11 py-4 hover:bg-slate-50">
                <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <p className="font-semibold">
                        {askerName(row, row.visitor)}
                        <span className="ms-2 text-sm font-normal text-muted-foreground">{t(`${row.questions} questions`, `${row.questions} سؤال`)}</span>
                    </p>
                    <p className="text-sm text-muted-foreground" dir="ltr">{row.at}</p>
                </div>
                <p className="mt-1 break-words text-sm">{row.question}</p>
                <p className="mt-1 break-all text-xs text-muted-foreground" dir="ltr">{[row.user?.email, row.ip, row.page].filter(Boolean).join(' · ')}</p>
            </Link>
        </li>
    );
}

export default function AssistantChats({ visitors }: { visitors: Paginated<Visitor> }) {
    return (
        <AdminSidebarLayout>
            <Head title={t('AI assistant chats', 'محادثات المساعد الذكي')} />
            <div className="mx-auto max-w-4xl min-w-0 space-y-4 overflow-x-clip p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-bold">{t('AI assistant chats', 'محادثات المساعد الذكي')}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">{t('Everyone who wrote to the assistant on the site or in Telegram. The one who wrote last is first.', 'كل اللي كلموا المساعد في الموقع أو تيليجرام. آخر واحد كتب في الأول.')}</p>
                </div>
                {visitors.data.length === 0
                    ? <p className="py-10 text-center text-muted-foreground">{t('Nobody wrote to the assistant yet.', 'لسه محدش كلم المساعد.')}</p>
                    : <ul className="divide-y border-y">{visitors.data.map((row) => <VisitorRow key={row.visitor} row={row} />)}</ul>}
                <ShopPager page={visitors} />
            </div>
        </AdminSidebarLayout>
    );
}
