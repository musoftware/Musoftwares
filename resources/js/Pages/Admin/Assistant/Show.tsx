import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { t } from '@/Components/Admin/SoftwareStore/t';
import { Asker, askerName } from './Index';

interface Exchange extends Asker {
    id: number;
    answer: string;
}

function ExchangeRow({ exchange }: { exchange: Exchange }) {
    return (
        <li className="space-y-2 py-4">
            <p className="text-xs text-muted-foreground" dir="ltr">{[exchange.at, exchange.page].filter(Boolean).join(' · ')}</p>
            <p className="whitespace-pre-wrap break-words font-semibold">{exchange.question}</p>
            <p className="whitespace-pre-wrap break-words border-s-2 border-slate-300 ps-3 text-sm">{exchange.answer}</p>
        </li>
    );
}

export default function AssistantChat({ visitor, exchanges }: { visitor: string; exchanges: Exchange[] }) {
    const last = exchanges[exchanges.length - 1];

    return (
        <AdminSidebarLayout>
            <Head title={t('AI assistant chat', 'محادثة مع المساعد الذكي')} />
            <div className="mx-auto max-w-3xl min-w-0 space-y-4 overflow-x-clip p-4 sm:p-6">
                <Link href="/admin/assistant-chats" className="inline-flex min-h-11 items-center text-sm text-blue-600 underline">{t('All chats', 'كل المحادثات')}</Link>
                <div>
                    <h1 className="text-2xl font-bold">{askerName(last, visitor)}</h1>
                    <p className="mt-1 break-all text-sm text-muted-foreground" dir="ltr">{[last.user?.email, last.ip].filter(Boolean).join(' · ')}</p>
                </div>
                <ul className="divide-y border-y">{exchanges.map((exchange) => <ExchangeRow key={exchange.id} exchange={exchange} />)}</ul>
            </div>
        </AdminSidebarLayout>
    );
}
