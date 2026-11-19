import { usePage } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import GuestFrame from '@/Components/GuestFrame';
import ClientPage, { Card, PrimaryAction, TextAction } from '@/Components/Client/ClientPage';
import { __ } from '@/lib/i18n';

type ResultAction = { label: string; href: string; external?: boolean };
type DetailRow = { label: string; value: string };

interface ResultProps {
    status: 'success' | 'error';
    message: string;
    actions: ResultAction[];
    details?: DetailRow[];
    note?: string | null;
}

/**
 * The one page after any payment. A signed-in user sees it inside the client area;
 * a guest sees it on a plain page with the logo.
 */
export default function PaymentResult({ status, message, actions, details = [], note = null }: ResultProps) {
    const signedIn = Boolean((usePage().props as { auth?: { user?: unknown } }).auth?.user);
    const ok = status === 'success';
    const title = ok ? __('client_money.result.success') : __('client_money.result.failed');
    const body = <ResultCard ok={ok} message={message} actions={actions} details={details} note={note} />;

    if (signedIn) {
        return <ClientPage width="sm" title={title}>{body}</ClientPage>;
    }

    return <GuestFrame title={title}>{body}</GuestFrame>;
}

function ResultCard({ ok, message, actions, details, note }: { ok: boolean; message: string; actions: ResultAction[]; details: DetailRow[]; note: string | null }) {
    const [main, ...others] = actions;

    return (
        <Card className="flex flex-col items-center gap-5 py-10 text-center">
            {ok ? <CheckCircle2 className="h-16 w-16 text-emerald-600" aria-hidden /> : <XCircle className="h-16 w-16 text-rose-600" aria-hidden />}
            <p className="max-w-sm text-[15px] text-[#1d1d1f]/70 dark:text-zinc-300">{message}</p>
            {details.length > 0 && <DetailList rows={details} />}
            {note && <p className="max-w-sm text-sm leading-relaxed text-[#1d1d1f]/60 dark:text-zinc-400">{note}</p>}
            <div className="flex flex-wrap items-center justify-center gap-4">
                {main && <ActionLink action={main} primary />}
                {others.map((action) => <ActionLink key={action.href} action={action} />)}
            </div>
        </Card>
    );
}

function DetailList({ rows }: { rows: DetailRow[] }) {
    return (
        <dl className="w-full max-w-sm divide-y divide-black/5 rounded-2xl bg-[#F8F8F8] px-4 text-start text-sm dark:divide-white/10 dark:bg-zinc-800/60">
            {rows.map((row) => (
                <div key={row.label} className="flex items-center justify-between gap-4 py-3">
                    <dt className="text-[#1d1d1f]/60 dark:text-zinc-400">{row.label}</dt>
                    <dd className="min-w-0 truncate font-semibold">{row.value}</dd>
                </div>
            ))}
        </dl>
    );
}

/** Links that leave the site (a return url) need a full page load, not an Inertia visit. */
function ActionLink({ action, primary = false }: { action: ResultAction; primary?: boolean }) {
    if (action.external) {
        const cls = primary
            ? 'inline-flex h-11 items-center justify-center rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white shadow-sm hover:bg-[#0077ed]'
            : 'text-sm font-semibold text-[#0071e3] hover:underline';
        return <a href={action.href} className={cls}>{action.label}</a>;
    }

    return primary ? <PrimaryAction href={action.href}>{action.label}</PrimaryAction> : <TextAction href={action.href}>{action.label}</TextAction>;
}
