import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { t } from '@/Components/Admin/SoftwareStore/t';

export interface ReviewItem {
    id: number;
    title: string;
    kind: 'fix' | 'build';
    status: 'unpaid' | 'ready' | 'building' | 'done';
    free: boolean;
    amount: number;
    paid: boolean;
    model: string | null;
    spec: string | null;
    customer_words: string | null;
    approved: boolean;
    can_rollback: boolean;
    rolling_back: boolean;
    rolled_back: boolean;
    open: boolean;
    at: string | null;
}

const STATUS: Record<ReviewItem['status'], [string, string]> = {
    unpaid: ['Waiting for payment', 'مستني الدفع'],
    ready: ['Paid, waiting', 'اتدفع، مستني'],
    building: ['Being built', 'بيتنفّذ'],
    done: ['Done', 'خلص'],
};

const done = (en: string, ar: string) => ({
    preserveScroll: true,
    onSuccess: () => toast.success(t(en, ar)),
    onError: () => toast.error(t('Not saved. Check the fields.', 'متحفظش. راجع الخانات.')),
});

/** What the request costs the customer, and whether they paid it. */
function priceLine(item: ReviewItem, currency: string): string {
    if (item.free) return t('Free fix', 'تصليح مجاني');
    return `${item.amount.toFixed(2)} ${currency} · ${t(...STATUS[item.status])}`;
}

/** A delivered change: undo it (the worker reverts its commits and releases), or see that it was undone. */
function DeliveredRequest({ uuid, item, currency }: { uuid: string; item: ReviewItem; currency: string }) {
    const rollback = () => {
        if (!window.confirm(t('Roll this change back? The worker reverts its commits and releases the project (GitHub, server, app).',
            'ترجّع التعديل ده؟ الـ worker هيلغي الـ commits بتاعته ويرفع المشروع تاني (GitHub والسيرفر والتطبيق).'))) return;
        router.post(`/admin/builder/${uuid}/requests/${item.id}/rollback`, {}, done('Rollback started', 'الرجوع بدأ'));
    };
    const state = item.rolled_back ? t('Rolled back', 'اترجّع') : item.rolling_back ? t('Rolling back...', 'بيترجّع...') : '';

    return (
        <li id={`request-${item.id}`} className="flex flex-wrap items-center justify-between gap-2 py-2">
            <span className="min-w-0 wrap-anywhere" dir="auto">{item.title}</span>
            <span className="flex flex-wrap items-center gap-2 text-slate-500">
                <span dir="ltr">{priceLine(item, currency)}</span>
                {state && <span className="font-semibold text-amber-700">{state}</span>}
                {item.can_rollback && (
                    <button type="button" onClick={rollback} className="min-h-11 rounded-lg border px-3 font-semibold text-red-700">{t('Roll back', 'رجّعه')}</button>
                )}
            </span>
        </li>
    );
}

/** Where an open request stands, so an approved one never reads as still waiting for the owner. */
function waitLine(item: ReviewItem): string {
    if (!item.approved) return t('Waiting for your approval: nothing starts before it', 'مستني موافقتك: مفيش حاجة هتبدأ قبلها');
    if (item.status === 'unpaid') return t('Approved, starts once paid', 'متوافق عليه، هيبدأ بعد الدفع');
    return t('In the queue: it starts when the work running now is done', 'في الطابور: هيبدأ لما الشغل اللي شغال دلوقتي يخلص');
}

/** An approved request is closed to one line; the owner opens it only to correct it or turn it down. */
function OpenRequest(props: { uuid: string; item: ReviewItem; currency: string }) {
    const { item } = props;
    if (!item.approved) {
        return (
            <article id={`request-${item.id}`} className="scroll-mt-4 space-y-3 rounded-xl border border-amber-400 bg-white p-4">
                <RequestForm {...props} />
            </article>
        );
    }

    return (
        <details id={`request-${item.id}`} className="scroll-mt-4 rounded-xl border bg-white p-4">
            <summary className="cursor-pointer">
                <span className="font-semibold" dir="auto">{item.title}</span>
                <span className="block text-sm text-emerald-700">{waitLine(item)}</span>
            </summary>
            <div className="mt-3 space-y-3"><RequestForm {...props} /></div>
        </details>
    );
}

/** One open request: the owner corrects what the engineer will read, then approves or turns it down. */
function RequestForm({ uuid, item, currency }: { uuid: string; item: ReviewItem; currency: string }) {
    const [words, setWords] = useState(item.customer_words || '');
    const [spec, setSpec] = useState(item.spec || '');
    const [amount, setAmount] = useState(String(item.amount));
    const [note, setNote] = useState('');
    const [rejecting, setRejecting] = useState(false);
    const base = `/admin/builder/${uuid}/requests/${item.id}`;
    const changes = { customer_words: words, spec, amount: item.status === 'unpaid' ? amount : '' };

    const approve = () => router.post(`${base}/approve`, changes, done('Approved', 'اتوافق عليه'));
    const save = () => router.post(`${base}/save`, changes, done('Saved', 'اتحفظ'));
    const reject = () => {
        if (!window.confirm(t('Turn this request down? The customer reads your note.', 'ترفض الطلب ده؟ العميل هيقرا ملاحظتك.'))) return;
        router.post(`${base}/reject`, { note }, done('Turned down', 'اترفض'));
    };

    return (
        <>
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <h3 className="font-semibold" dir="auto">{item.title}</h3>
                <span className="text-sm text-slate-600" dir="ltr">{priceLine(item, currency)}</span>
            </div>
            <p className="text-sm text-slate-500">
                {item.kind === 'fix' ? t('Fix', 'تصليح') : t('New work', 'تطوير')}
                {item.model && <> · <span dir="ltr">{item.model}</span></>}
                {' · '}{waitLine(item)}
            </p>

            <label className="block space-y-1">
                <span className="text-sm font-semibold">{t("The customer's words (the engineer reads them as they are)", 'كلام العميل (المهندس بيقراه زي ما هو)')}</span>
                <textarea value={words} onChange={(event) => setWords(event.target.value)} rows={4} dir="auto" className="w-full rounded-lg border p-2" />
            </label>
            <label className="block space-y-1">
                <span className="text-sm font-semibold">{t("The chat's note for the engineer", 'ملاحظة الشات للمهندس')}</span>
                <textarea value={spec} onChange={(event) => setSpec(event.target.value)} rows={6} dir="auto" className="w-full rounded-lg border p-2 text-sm" />
            </label>
            {item.status === 'unpaid' && !item.free && (
                <label className="block space-y-1">
                    <span className="text-sm font-semibold">{t(`Price (${currency})`, `السعر (${currency})`)}</span>
                    <input type="number" min={0} step="0.01" value={amount} onChange={(event) => setAmount(event.target.value)} dir="ltr" className="min-h-11 w-full rounded-lg border px-3 sm:w-40" />
                </label>
            )}

            <div className="flex flex-wrap gap-2">
                {!item.approved && (
                    <button type="button" onClick={approve} className="min-h-11 flex-1 rounded-lg bg-slate-900 px-5 font-semibold text-white sm:flex-none">
                        {item.status === 'unpaid' ? t('Approve', 'وافق') : t('Approve and start', 'وافق وابدأ')}
                    </button>
                )}
                <button type="button" onClick={save} className="min-h-11 rounded-lg border px-5 font-semibold">{item.approved ? t('Save the changes', 'احفظ التعديل') : t('Save only', 'احفظ بس')}</button>
                <button type="button" onClick={() => setRejecting(!rejecting)} className="min-h-11 rounded-lg px-4 font-semibold text-red-700">{t('Turn down', 'ارفض')}</button>
            </div>
            {rejecting && (
                <div className="space-y-2">
                    <textarea value={note} onChange={(event) => setNote(event.target.value)} rows={2} dir="auto"
                        placeholder={t('Why (the customer reads it). Empty sends a polite default.', 'السبب (العميل هيقراه). لو فاضي هيتبعت رد مهذب جاهز.')}
                        className="w-full rounded-lg border p-2" />
                    <button type="button" onClick={reject} className="min-h-11 rounded-lg bg-red-700 px-5 font-semibold text-white">{t('Turn it down', 'ارفضه')}</button>
                </div>
            )}
        </>
    );
}

const isQueued = (item: ReviewItem) => item.open && item.approved && item.status === 'ready';

const titles = (items: ReviewItem[]) => items.map((item) => item.title).join(' · ');

/** The order of work: what the worker does now, then what waits. Everything that waits goes out as one build. */
function Queue({ items }: { items: ReviewItem[] }) {
    const queued = items.filter(isQueued);
    if (!queued.length) return null;
    const running = items.filter((item) => item.status === 'building');

    return (
        <div className="rounded-xl border bg-white p-4">
            <h3 className="font-semibold">{t('Queue', 'الطابور')}</h3>
            <ol className="mt-2 list-decimal space-y-2 ps-5 text-sm">
                <li>
                    <span className="font-semibold">{t('Running now', 'شغال دلوقتي')}</span>
                    <span className="block text-slate-700 wrap-anywhere" dir="auto">{running.length ? titles(running) : t('Other work on this project', 'شغل تاني على المشروع')}</span>
                </li>
                <li>
                    <span className="font-semibold">{t(`Next, as one build (${queued.length})`, `بعده، في build واحد (${queued.length})`)}</span>
                    <span className="block text-slate-700 wrap-anywhere" dir="auto">{titles(queued)}</span>
                </li>
            </ol>
        </div>
    );
}

/** Every request of this project; the open ones first, each opened straight from the owner's push. */
export default function RequestReview({ uuid, items, currency }: { uuid: string; items: ReviewItem[]; currency: string }) {
    // the push opens this page at #request-N; Inertia draws after the browser tried to scroll, so scroll again
    useEffect(() => {
        const target = window.location.hash ? document.querySelector(window.location.hash) : null;
        target?.scrollIntoView({ block: 'start' });
    }, []);
    if (!items.length) return null;
    // what still needs the owner comes first; approved ones wait below, closed
    const open = items.filter((item) => item.open).sort((a, b) => Number(a.approved) - Number(b.approved));
    const closed = items.filter((item) => !item.open);

    return (
        <section className="space-y-3">
            <h2 className="text-lg font-bold">{t('Requests', 'الطلبات')}</h2>
            <Queue items={items} />
            {open.map((item) => <OpenRequest key={item.id} uuid={uuid} item={item} currency={currency} />)}
            {closed.length > 0 && (
                <details className="rounded-xl border bg-white p-4">
                    <summary className="cursor-pointer font-semibold">{t(`Started or done (${closed.length})`, `بدأت أو خلصت (${closed.length})`)}</summary>
                    <ul className="mt-2 divide-y text-sm">
                        {closed.map((item) => <DeliveredRequest key={item.id} uuid={uuid} item={item} currency={currency} />)}
                    </ul>
                </details>
            )}
        </section>
    );
}
