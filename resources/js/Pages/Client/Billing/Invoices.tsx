import { Head, usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, CreditCard, Download, FileText, Plus, Wallet } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SafeLink from '@/Components/SafeLink';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface Invoice {
    id: number;
    uuid: string;
    invoice_number: number;
    title: string | null;
    more_items: number;
    amount: number;
    paid_amount: number;
    remaining: number;
    wallet_amount?: number | null;
    wallet_remaining?: number | null;
    currency: any;
    status: string;
    due_date: string | null;
    is_overdue: boolean;
    issued_at: string | null;
    paid_at: string | null;
}

interface IndexProps {
    invoices?: { data: Invoice[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
    unpaid_invoices?: Invoice[];
    total_outstanding?: number;
    client_balance?: number;
    wallet_currency?: any;
}

/**
 * Client invoices in two parts: what is still to pay (cards with one Pay button each) and the
 * history of settled invoices. Every invoice says what it is for.
 */
export default function Invoices({ invoices, unpaid_invoices = [], total_outstanding = 0, client_balance = 0, wallet_currency }: IndexProps) {
    const { locale } = usePage().props as any;
    const formatDate = (date: string | null) =>
        date ? new Date(date + 'T00:00:00').toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';
    const history = invoices?.data ?? [];
    const oldest = unpaid_invoices[0];

    return (
        <AuthenticatedLayout>
            <Head title={__('client_home.invoices.title')} />
            <div className="mx-auto w-full max-w-[880px] px-4 py-6 sm:px-6 sm:py-10">
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{__('client_home.invoices.title')}</h1>
                <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.invoices.hint')}</p>

                {/* Summary: total to pay, one button, and the wallet */}
                <section className="mt-6 rounded-[24px] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-6">
                    {unpaid_invoices.length > 0 ? (
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.invoices.to_pay_total')}</p>
                                <CurrencyDisplay amount={total_outstanding} currency={wallet_currency} className="mt-1 block text-3xl font-bold tracking-tight sm:text-4xl" />
                            </div>
                            {oldest && (
                                <SafeLink href={route('billing.invoices.pay', oldest.uuid)} className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-7 text-[15px] font-semibold text-white shadow-sm hover:bg-[#0077ed]">
                                    <CreditCard className="h-4 w-4" />{__('client_home.invoices.pay_oldest')}
                                </SafeLink>
                            )}
                        </div>
                    ) : (
                        <div className="flex items-center gap-3">
                            <CheckCircle2 className="h-9 w-9 shrink-0 text-emerald-600" />
                            <p className="text-lg font-bold">{__('client_home.invoices.nothing_to_pay')}</p>
                        </div>
                    )}
                    <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-black/5 pt-4 text-sm dark:border-white/10">
                        <span className="flex items-center gap-2 text-[#1d1d1f]/70 dark:text-zinc-300">
                            <Wallet className="h-4 w-4 text-emerald-600" />{__('client_home.invoices.wallet')}:
                            <CurrencyDisplay amount={client_balance} currency={wallet_currency} className="font-bold" />
                        </span>
                        <SafeLink href={route('financial.add-balance')} className="inline-flex items-center gap-1 font-semibold text-[#0071e3]">
                            <Plus className="h-4 w-4" />{__('client_home.invoices.add_balance')}
                        </SafeLink>
                        <p className="w-full text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_home.invoices.wallet_hint')}</p>
                    </div>
                </section>

                {/* To pay */}
                {unpaid_invoices.length > 0 && (
                    <section className="mt-8">
                        <h2 className="mb-3 text-lg font-bold">{__('client_home.invoices.to_pay')} ({unpaid_invoices.length})</h2>
                        <ul className="space-y-3">
                            {unpaid_invoices.map((inv) => (
                                <li key={inv.id} className={cn('rounded-[20px] border bg-white p-4 shadow-sm dark:bg-zinc-900 sm:p-5', inv.is_overdue ? 'border-rose-200 dark:border-rose-900/50' : 'border-black/5 dark:border-white/10')}>
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <p className="font-semibold">#{inv.invoice_number}{inv.title && <span className="font-normal text-[#1d1d1f]/70 dark:text-zinc-300"> · {inv.title}</span>}</p>
                                            {inv.more_items > 0 && <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_home.invoices.and_more', { count: inv.more_items })}</p>}
                                            <p className={cn('mt-1 flex items-center gap-1.5 text-sm', inv.is_overdue ? 'font-semibold text-rose-600' : 'text-[#1d1d1f]/60 dark:text-zinc-400')}>
                                                {inv.is_overdue && <AlertCircle className="h-4 w-4" />}
                                                {inv.due_date
                                                    ? __(inv.is_overdue ? 'client_home.money.was_due' : 'client_home.money.due_on', { date: formatDate(inv.due_date) })
                                                    : __('client_home.invoices.issued_on', { date: formatDate(inv.issued_at) })}
                                            </p>
                                            {inv.paid_amount > 0 && (
                                                <p className="mt-1 inline-flex items-center gap-1 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                                    {__('client_home.invoices.status.partially_paid')}: <CurrencyDisplay amount={inv.paid_amount} currency={inv.currency} /> / <CurrencyDisplay amount={inv.amount} currency={inv.currency} />
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <SafeLink href={route('billing.invoices.pay', inv.uuid)} className="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-5 text-sm font-semibold text-white hover:bg-[#0077ed] sm:flex-none">
                                                <CreditCard className="h-4 w-4" />
                                                <CurrencyDisplay amount={inv.remaining} currency={inv.currency} />
                                            </SafeLink>
                                            <PdfLink uuid={inv.uuid} />
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {/* History */}
                <section className="mt-8">
                    <h2 className="mb-3 text-lg font-bold">{__('client_home.invoices.history')}</h2>
                    {history.length === 0 ? (
                        <div className="flex items-center gap-3 rounded-[20px] bg-white p-5 text-sm text-[#1d1d1f]/60 shadow-sm dark:bg-zinc-900 dark:text-zinc-400">
                            <FileText className="h-5 w-5" />{__('client_home.invoices.history_empty')}
                        </div>
                    ) : (
                        <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                            {history.map((inv) => (
                                <li key={inv.id} className="flex items-center gap-3 px-4 py-3">
                                    <SafeLink href={route('billing.invoices.pay', inv.uuid)} className="min-w-0 flex-1">
                                        <p className="truncate text-[15px] font-medium">#{inv.invoice_number}{inv.title && <span className="font-normal text-[#1d1d1f]/70 dark:text-zinc-300"> · {inv.title}</span>}</p>
                                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                                            <StatusText status={inv.status} /> ·{' '}
                                            {inv.status === 'paid' && inv.paid_at
                                                ? __('client_home.invoices.paid_on', { date: formatDate(inv.paid_at) })
                                                : __('client_home.invoices.issued_on', { date: formatDate(inv.issued_at) })}
                                        </p>
                                    </SafeLink>
                                    <CurrencyDisplay amount={inv.amount} currency={inv.currency} className="shrink-0 text-sm font-semibold" />
                                    <PdfLink uuid={inv.uuid} />
                                </li>
                            ))}
                        </ul>
                    )}
                    {invoices && invoices.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm font-semibold">
                            {invoices.prev_page_url ? <SafeLink href={invoices.prev_page_url} className="text-[#0071e3]">{__('client_home.invoices.previous')}</SafeLink> : <span />}
                            <span className="text-[#1d1d1f]/50 dark:text-zinc-500" dir="ltr">{invoices.current_page} / {invoices.last_page}</span>
                            {invoices.next_page_url ? <SafeLink href={invoices.next_page_url} className="text-[#0071e3]">{__('client_home.invoices.next')}</SafeLink> : <span />}
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function StatusText({ status }: { status: string }) {
    const key = `client_home.invoices.status.${status}`;
    const label = __(key);
    return <span className={cn(status === 'paid' && 'text-emerald-700 dark:text-emerald-400')}>{label === key ? status : label}</span>;
}

function PdfLink({ uuid }: { uuid: string }) {
    return (
        <a href={route('billing.invoices.pdf', uuid)} target="_blank" rel="noreferrer" title={__('client_home.invoices.pdf')}
            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f5f5f7] text-[#1d1d1f]/70 hover:bg-black/5 dark:bg-zinc-800 dark:text-zinc-300">
            <Download className="h-4 w-4" />
            <span className="sr-only">{__('client_home.invoices.pdf')}</span>
        </a>
    );
}
