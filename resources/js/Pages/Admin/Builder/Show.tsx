import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { toast } from 'sonner';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { t } from '@/Components/Admin/SoftwareStore/t';
import { STATUS_LABELS } from './Index';

interface Message {
    id: number;
    role: 'user' | 'assistant' | 'team';
    text: string;
    question: boolean;
    files: string[];
    at: string;
}

interface Secret {
    id: number;
    name: string;
    source: string;
    updated_at: string | null;
}

interface Project {
    uuid: string;
    design_fee: string;
    design_url: string | null;
    title: string | null;
    status: string;
    awaiting_answer: boolean;
    amount: string;
    leave_discount_percent: number;
    leave_discount_usd: string;
    paid_total: string;
    currency: string;
    build_cost_usd: string;
    chat_cost_usd: number;
    budget_usd: string;
    build_model: string | null;
    client_name: string | null;
    client_email: string | null;
    client_whatsapp: string | null;
    spec: string | null;
    error: string | null;
    version: number;
}

/** Names the worker knows how to use for a deploy; any other name is passed to the build as it is. */
const COMMON_SECRETS = ['ssh_host', 'ssh_port', 'ssh_user', 'ssh_password', 'ssh_private_key', 'deploy_path', 'site_url', 'ftp_host', 'ftp_user', 'ftp_password', 'ftp_path'];

const WHO: Record<Message['role'], [string, string]> = {
    user: ['Customer', 'العميل'],
    assistant: ['AI', 'الـ AI'],
    team: ['Team', 'الفريق'],
};

const onDone = (en: string, ar: string) => ({
    preserveScroll: true,
    onSuccess: () => toast.success(t(en, ar)),
    onError: () => toast.error(t('Not saved. Check the fields.', 'متحفظش. راجع الخانات.')),
});

function MessageList({ messages }: { messages: Message[] }) {
    return (
        <ol className="space-y-3">
            {messages.map((message) => (
                <li key={message.id} className={`rounded-xl border p-3 ${message.role === 'user' ? 'bg-slate-50' : 'bg-white'} ${message.question ? 'border-amber-400' : ''}`}>
                    <p className="mb-1 text-xs font-semibold text-slate-500">
                        {t(...WHO[message.role])}{message.question && ` · ${t('question from the build', 'سؤال من البناء')}`} · <span dir="ltr">{message.at}</span>
                    </p>
                    <p className="whitespace-pre-wrap break-words" dir="auto">{message.text}</p>
                    {message.files.length > 0 && <p className="mt-1 text-sm text-slate-500" dir="auto">{message.files.join(' · ')}</p>}
                </li>
            ))}
        </ol>
    );
}

function TeamReply({ project }: { project: Project }) {
    const [text, setText] = useState('');
    const send = (event: FormEvent) => {
        event.preventDefault();
        router.post(`/admin/builder/${project.uuid}/messages`, { text }, { ...onDone('Sent', 'اتبعت'), onSuccess: () => { setText(''); toast.success(t('Sent', 'اتبعت')); } });
    };

    return (
        <form onSubmit={send} className="space-y-2 rounded-xl border bg-white p-4">
            <label htmlFor="team-text" className="font-semibold">
                {project.awaiting_answer ? t('Answer the build', 'رد على البناء') : t('Write as the team', 'اكتب باسم الفريق')}
            </label>
            <p className="text-sm text-slate-500">
                {project.awaiting_answer
                    ? t('The build is waiting. Your answer goes straight to the engineer.', 'البناء مستني. ردك بيروح للمهندس على طول.')
                    : t('The customer sees it, and the AI reads it on the next message.', 'العميل بيشوفها، والـ AI بيقراها في الرسالة الجاية.')}
            </p>
            <textarea id="team-text" value={text} onChange={(event) => setText(event.target.value)} rows={3} required dir="auto" className="w-full rounded-lg border p-2" />
            <button type="submit" className="min-h-11 rounded-lg bg-slate-900 px-5 font-semibold text-white">{t('Send', 'ابعت')}</button>
        </form>
    );
}

function Secrets({ project, secrets }: { project: Project; secrets: Secret[] }) {
    const [name, setName] = useState('');
    const [value, setValue] = useState('');
    const save = (event: FormEvent) => {
        event.preventDefault();
        router.post(`/admin/builder/${project.uuid}/secrets`, { name, value }, { ...onDone('Saved', 'اتحفظ'), onSuccess: () => { setName(''); setValue(''); toast.success(t('Saved', 'اتحفظ')); } });
    };
    const remove = (secret: Secret) => router.delete(`/admin/builder/${project.uuid}/secrets/${secret.id}`, onDone('Removed', 'اتشال'));

    return (
        <section className="space-y-3 rounded-xl border bg-white p-4">
            <h2 className="font-semibold">{t('Access details', 'بيانات الدخول')}</h2>
            <p className="text-sm text-slate-500">{t('Saved encrypted and given only to the build worker. With ssh_host or ftp_host, the build deploys by itself when it is done.', 'بتتحفظ مشفّرة وبتروح للـ worker بس. لو فيه ssh_host أو ftp_host البناء بيعمل deploy لوحده لما يخلص.')}</p>
            {secrets.length > 0 && (
                <ul className="divide-y text-sm">
                    {secrets.map((secret) => (
                        <li key={secret.id} className="flex items-center justify-between gap-2 py-2">
                            <span dir="ltr" className="font-mono">{secret.name} = ••••</span>
                            <span className="text-slate-500">{secret.source === 'team' ? t('team', 'الفريق') : t('customer', 'العميل')}</span>
                            <button type="button" onClick={() => remove(secret)} className="min-h-11 rounded-lg px-3 text-red-700">{t('Remove', 'شيل')}</button>
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={save} className="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                <input list="secret-names" value={name} onChange={(event) => setName(event.target.value)} required placeholder="ssh_host" aria-label={t('Name', 'الاسم')} dir="ltr" className="min-h-11 rounded-lg border px-3" />
                <datalist id="secret-names">{COMMON_SECRETS.map((item) => <option key={item} value={item} />)}</datalist>
                <textarea value={value} onChange={(event) => setValue(event.target.value)} required rows={1} aria-label={t('Value', 'القيمة')} dir="ltr" className="min-h-11 rounded-lg border px-3 py-2 font-mono" />
                <button type="submit" className="min-h-11 rounded-lg bg-slate-900 px-5 font-semibold text-white">{t('Save', 'احفظ')}</button>
            </form>
        </section>
    );
}

/** A paid build that failed: send it back to the worker, which continues from the files saved on the drive. */
function RetryBuild({ project }: { project: Project }) {
    if (project.status !== 'failed') return null;
    const retry = () => router.post(`/admin/builder/${project.uuid}/retry`, {}, onDone('Queued again', 'رجع للتنفيذ'));

    return (
        <div className="space-y-2 rounded-xl border bg-white p-4">
            <p className="font-semibold">{t('The build stopped', 'التنفيذ وقف')}</p>
            <p className="text-sm text-slate-500">
                {t('Fix the cause first (an access detail, a reply, the worker), then retry. The work already done is kept.',
                    'صلّح السبب الأول (بيانات دخول، رد، الـ worker)، وبعدين جرّب تاني. الشغل اللي اتعمل محفوظ.')}
            </p>
            <button type="button" onClick={retry} className="min-h-11 rounded-lg bg-slate-900 px-5 font-semibold text-white">{t('Retry the build', 'كمّل التنفيذ')}</button>
        </div>
    );
}

/** The discount a customer took when they started to leave the price, if any. */
function leaveDiscountNote(project: Project): string {
    if (Number(project.leave_discount_usd) <= 0) return '';
    const amount = `${project.leave_discount_usd} ${project.currency}`;
    return t(`after a ${project.leave_discount_percent}% leave discount (-${amount})`, `بعد خصم ${project.leave_discount_percent}% عند المغادرة (-${amount})`);
}

/** The four numbers the owner checks first: what the customer was quoted and paid, and what the chat and the build cost. */
function Money({ project }: { project: Project }) {
    const items: [string, string, string][] = [
        [t('Price quoted', 'السعر المعروض'), `${project.amount} ${project.currency}`, leaveDiscountNote(project)],
        [t('Paid so far', 'المدفوع لحد دلوقتي'), `${project.paid_total} ${project.currency}`, ''],
        [t('Chat cost', 'تكلفة الشات'), `$${project.chat_cost_usd.toFixed(4)}`, ''],
        [t('Build cost', 'تكلفة التنفيذ'), `$${Number(project.build_cost_usd).toFixed(2)}`, t(`budget $${project.budget_usd}`, `الميزانية $${project.budget_usd}`)],
    ];

    return (
        <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {items.map(([label, value, note]) => (
                <div key={label} className="rounded-xl border bg-white p-3">
                    <dt className="text-sm text-slate-500">{label}</dt>
                    <dd className="text-xl font-bold" dir="ltr">{value}</dd>
                    {note && <dd className="text-xs text-slate-500" dir="ltr">{note}</dd>}
                </div>
            ))}
        </dl>
    );
}

/** The designer's Figma link. Saving it starts the build of a project that waits for its design. */
function DesignLink({ project }: { project: Project }) {
    const [url, setUrl] = useState(project.design_url || '');
    if (Number(project.design_fee) <= 0 && !project.design_url) return null;
    const save = (event: FormEvent) => {
        event.preventDefault();
        router.post(`/admin/builder/${project.uuid}/design`, { design_url: url }, onDone('Saved, the build has started', 'اتحفظ، والتنفيذ بدأ'));
    };

    return (
        <form onSubmit={save} className="space-y-2 rounded-xl border bg-white p-4">
            <label htmlFor="design-url" className="font-semibold">{t('Design (Figma link)', 'التصميم (لينك Figma)')}</label>
            <p className="text-sm text-slate-500">
                {t(`The customer paid ${project.design_fee} for a professional design. Share the Figma file "anyone with the link can view", paste the link, and the build starts.`,
                    `العميل دفع ${project.design_fee} للتصميم. خلّي ملف Figma متاح لأي حد معاه اللينك، والزق اللينك هنا، والتنفيذ هيبدأ.`)}
            </p>
            <input id="design-url" type="url" required value={url} onChange={(event) => setUrl(event.target.value)} dir="ltr" placeholder="https://www.figma.com/design/..." className="min-h-11 w-full rounded-lg border px-3" />
            <button type="submit" className="min-h-11 rounded-lg bg-slate-900 px-5 font-semibold text-white">{t('Save and start the build', 'احفظ وابدأ التنفيذ')}</button>
        </form>
    );
}

/** One AI builder chat: the whole conversation, the brief, the costs, and the owner's ways to help. */
export default function BuilderShow({ project, messages, secrets, chatUrl }: { project: Project; messages: Message[]; secrets: Secret[]; chatUrl: string }) {
    return (
        <AdminSidebarLayout>
            <Head title={project.title || t('AI builder chat', 'محادثة منشئ البرامج')} />
            <div className="mx-auto max-w-5xl space-y-4 p-4">
                <Link href="/admin/builder" className="text-sm font-semibold text-slate-600">{t('All chats', 'كل المحادثات')}</Link>
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h1 className="text-2xl font-bold" dir="auto">{project.title || t('(no title yet)', '(لسه من غير اسم)')}</h1>
                    <a href={chatUrl} target="_blank" rel="noopener" className="text-sm font-semibold text-blue-700">{t('Open the customer page', 'افتح صفحة العميل')}</a>
                </div>
                <p className="text-sm text-slate-600">
                    {t(...(STATUS_LABELS[project.status] || [project.status, project.status]))}
                    {project.awaiting_answer && <span className="font-semibold text-amber-700"> · {t('waiting for an answer', 'مستني رد')}</span>}
                    {project.build_model && <> · <span dir="ltr">{project.build_model}</span></>}
                    {project.client_name && <> · <span dir="auto">{project.client_name}</span> <span dir="ltr">{project.client_whatsapp} {project.client_email}</span></>}
                </p>
                <Money project={project} />
                {project.error && <p className="rounded-lg bg-red-50 p-3 text-sm text-red-800 whitespace-pre-wrap" dir="ltr">{project.error}</p>}

                <div className="grid gap-4 lg:grid-cols-[3fr_2fr]">
                    <MessageList messages={messages} />
                    <div className="space-y-4">
                        <RetryBuild project={project} />
                        <DesignLink project={project} />
                        <TeamReply project={project} />
                        <Secrets project={project} secrets={secrets} />
                        {project.spec && (
                            <details className="rounded-xl border bg-white p-4">
                                <summary className="cursor-pointer font-semibold">{t('Build brief', 'الـ brief')}</summary>
                                <pre className="mt-2 whitespace-pre-wrap break-words text-sm" dir="ltr">{project.spec}</pre>
                            </details>
                        )}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
