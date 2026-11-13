import React from 'react';
import { usePoll } from '@inertiajs/react';
import { t } from '@/Components/Admin/SoftwareStore/t';

export interface WorkerEvent {
    id: number;
    kind: string;
    detail: string | null;
    worker: string | null;
    repeats: number;
    at: string | null;
    last_at: string | null;
}

const KINDS: Record<string, [string, string]> = {
    claim: ['Took the job', 'استلم الشغل'],
    source: ['Downloaded the last version', 'نزّل آخر نسخة'],
    chat: ['Read the chat', 'قرا المحادثة'],
    attachment: ['Downloaded a customer file', 'نزّل ملف من العميل'],
    progress: ['Step', 'خطوة'],
    commit: ['Commit', 'commit'],
    snapshot: ['Progress', 'تقدّم'],
    question: ['Asked a question', 'سأل سؤال'],
    answers: ['Waiting for an answer', 'مستني رد'],
    complete: ['Delivered', 'سلّم'],
    fail: ['Failed', 'فشل'],
    abandoned: ['Worker restarted; the job went back to the queue', 'الـ worker اتعمله restart والشغل رجع للطابور'],
    imported: ['Imported the project', 'استورد المشروع'],
    released: ['Released', 'نزّل التعديلات'],
    'trials.started': ['Trial started', 'التجربة اشتغلت'],
    'trials.failed': ['Trial failed', 'التجربة فشلت'],
    refused: ['Refused by the site', 'الموقع رفض الطلب'],
};

const BAD = new Set(['fail', 'refused', 'trials.failed']);

/** The steps the worker names, in the owner's words. */
const STEPS: Record<string, [string, string]> = {
    importing: ['copying the project into the build box', 'بينسخ المشروع لصندوق البناء'],
    started: ['project loaded', 'المشروع اتحمّل'],
    packaging: ['packing the delivery', 'بيجهّز التسليم'],
};

const detailOf = (event: WorkerEvent) => (event.kind === 'progress' && event.detail && STEPS[event.detail] ? t(...STEPS[event.detail]) : event.detail);

/** The time only, when the line is from today; the date too otherwise. */
const when = (at: string | null) => (at ?? '').replace(new Intl.DateTimeFormat('en-CA', { timeZone: 'Africa/Cairo' }).format(new Date()) + ' ', '');

function EventLine({ event }: { event: WorkerEvent }) {
    return (
        <li className="grid grid-cols-[auto_1fr] gap-x-3 border-b py-2 last:border-0">
            <span className="text-xs text-slate-500 tabular-nums" dir="ltr">{when(event.at)}</span>
            <div className="min-w-0 text-sm">
                <span className={BAD.has(event.kind) ? 'font-semibold text-red-700' : 'font-semibold'}>{t(...(KINDS[event.kind] || [event.kind, event.kind]))}</span>
                {event.worker === 'backup' && <span className="text-xs text-slate-500"> · {t('backup PC', 'الجهاز الاحتياطي')}</span>}
                {event.repeats > 1 && <span className="text-xs text-slate-500" dir="ltr"> · ×{event.repeats} {t('until', 'لحد')} {when(event.last_at)}</span>}
                {event.detail && <p className="text-slate-700 whitespace-pre-wrap wrap-anywhere" dir="auto">{detailOf(event)}</p>}
            </div>
        </li>
    );
}

/** What the worker did on this project, newest first; it refreshes by itself while the project is being built. */
export default function WorkerLog({ events, live }: { events: WorkerEvent[]; live: boolean }) {
    usePoll(5000, { only: ['workerEvents', 'project'] }, { autoStart: live });

    return (
        <details className="rounded-xl border bg-white p-4" open={live}>
            <summary className="cursor-pointer font-semibold">
                {t('What the worker did', 'حركات الـ worker')}
                {live && <span className="text-sm font-normal text-emerald-700"> · {t('updates every 5 seconds', 'بيتحدّث كل 5 ثواني')}</span>}
            </summary>
            {events.length === 0
                ? <p className="mt-2 text-sm text-slate-600">{t('The worker has not touched this project yet.', 'الـ worker لسه ملمسش المشروع ده.')}</p>
                : <ol className="mt-2">{events.map((event) => <EventLine key={event.id} event={event} />)}</ol>}
        </details>
    );
}
