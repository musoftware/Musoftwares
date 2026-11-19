import { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Card, Section } from '@/Components/Client/ClientPage';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

type InvoiceLine = { id: number; title: string; quantity: number; unit_price: number; total: number };
type TimeSession = { id: number; start: string; end: string; duration: string; amount: number };
type TimeReport = { total_time: string; billed: string; full_value: string; savings: string | null; market_value: string | null; sessions: TimeSession[] };

/** The invoice as the server's InvoiceSummary sends it, for both the guest link and the signed-in pay page. */
export interface InvoiceSummary {
    id: number;
    uuid: string;
    number: string;
    status: string;
    issued_at: string | null;
    due_date: string | null;
    currency: string | null;
    total: number;
    paid: number;
    remaining: number;
    client_name: string | null;
    project_name: string | null;
    items: InvoiceLine[];
    time_report: TimeReport | null;
}

const muted = 'text-[#1d1d1f]/60 dark:text-zinc-400';

/** What the invoice is for: dates, lines, totals and, when time was tracked, the hours. */
export default function InvoiceStatement({ invoice }: { invoice: InvoiceSummary }) {
    const money = (amount: number) => formatMoney(amount, invoice.currency);

    return (
        <div className="space-y-8">
            <Card className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <dl className="flex flex-wrap gap-x-8 gap-y-2 text-sm">
                        <InfoItem label={__('general.issued_date')} value={invoice.issued_at} />
                        <InfoItem label={__('general.due_date')} value={invoice.due_date} />
                        <InfoItem label={__('general.client_details')} value={invoice.client_name} />
                        <InfoItem label={__('general.project')} value={invoice.project_name} />
                    </dl>
                    <StatusBadge status={invoice.status} />
                </div>

                <ul className="divide-y divide-black/5 border-y border-black/5 dark:divide-white/10 dark:border-white/10">
                    {invoice.items.map((item) => (
                        <li key={item.id} className="flex items-start justify-between gap-4 py-3">
                            <div className="min-w-0">
                                <p className="font-medium">{item.title}</p>
                                <p className={`text-xs ${muted}`} dir="ltr">{item.quantity} × {money(item.unit_price)}</p>
                            </div>
                            <span className="shrink-0 font-semibold" dir="ltr">{money(item.total)}</span>
                        </li>
                    ))}
                </ul>

                <dl className="ms-auto w-full max-w-xs space-y-2 text-sm">
                    <TotalRow label={__('general.total')} value={money(invoice.total)} />
                    {invoice.paid > 0 && <TotalRow label={__('general.paid_to_date')} value={money(invoice.paid)} />}
                    <div className="flex justify-between gap-4 border-t border-black/5 pt-2 text-base font-bold dark:border-white/10">
                        <dt>{__('general.total_outstanding')}</dt>
                        <dd dir="ltr">{money(invoice.remaining)}</dd>
                    </div>
                </dl>
            </Card>

            {invoice.time_report && <TimeReportSection report={invoice.time_report} money={money} />}
        </div>
    );
}

function InfoItem({ label, value }: { label: string; value: string | null }) {
    if (!value) return null;
    return (
        <div>
            <dt className={`text-xs ${muted}`}>{label}</dt>
            <dd className="font-semibold">{value}</dd>
        </div>
    );
}

function TotalRow({ label, value }: { label: string; value: string }) {
    return (
        <div className={`flex justify-between gap-4 ${muted}`}>
            <dt>{label}</dt>
            <dd className="font-semibold" dir="ltr">{value}</dd>
        </div>
    );
}

/** Tracked hours, what they would cost at full price, and every work session on request. */
function TimeReportSection({ report, money }: { report: TimeReport; money: (n: number) => string }) {
    const [open, setOpen] = useState(false);
    const rows = [
        { label: __('general.total_time'), value: report.total_time },
        { label: __('general.billed_amount'), value: report.billed },
        { label: __('general.full_real_value'), value: report.full_value },
        { label: __('general.discount_savings'), value: report.savings },
        { label: __('general.market_value'), value: report.market_value },
    ].filter((row): row is { label: string; value: string } => Boolean(row.value));

    return (
        <Section title={__('frontend.guest_invoice_time_tracking')}>
            <Card className="space-y-4">
                <dl className="grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                    {rows.map((row) => <TotalRow key={row.label} label={row.label} value={row.value} />)}
                </dl>
                {report.sessions.length > 0 && (
                    <button type="button" onClick={() => setOpen(!open)} aria-expanded={open}
                        className="flex min-h-11 items-center gap-2 text-sm font-semibold text-[#0071e3] hover:underline">
                        {__('general.view_detailed_time_sessions')} ({report.sessions.length})
                        {open ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                    </button>
                )}
                {open && <SessionList sessions={report.sessions} money={money} />}
            </Card>
        </Section>
    );
}

function SessionList({ sessions, money }: { sessions: TimeSession[]; money: (n: number) => string }) {
    return (
        <ul className="divide-y divide-black/5 text-sm dark:divide-white/10">
            {sessions.map((s) => (
                <li key={s.id} className="flex items-center justify-between gap-4 py-2">
                    <span className={`min-w-0 truncate font-mono text-xs ${muted}`} dir="ltr">{s.start} → {s.end}</span>
                    <span className="shrink-0 font-mono">{s.duration}</span>
                    <span className="shrink-0 font-semibold" dir="ltr">{money(s.amount)}</span>
                </li>
            ))}
        </ul>
    );
}
