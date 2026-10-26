import { CheckCircle2, XCircle } from 'lucide-react';
import ClientPage, { Card, PrimaryAction, TextAction } from '@/Components/Client/ClientPage';
import { __ } from '@/lib/i18n';

/** After the card payment of an invoice: did it work, and the way back. */
export default function PaymentResult({ status, message }: { status: 'success' | 'error'; message: string }) {
    const ok = status === 'success';

    return (
        <ClientPage width="sm" title={ok ? __('client_money.result.success') : __('client_money.result.failed')}>
            <Card className="flex flex-col items-center gap-4 py-10 text-center">
                {ok ? <CheckCircle2 className="h-16 w-16 text-emerald-600" /> : <XCircle className="h-16 w-16 text-rose-600" />}
                <p className="max-w-sm text-[15px] text-[#1d1d1f]/70 dark:text-zinc-300">{message}</p>
                <div className="flex flex-wrap items-center justify-center gap-4">
                    <PrimaryAction href={route('billing.invoices.index')}>{__('client_money.result.invoices')}</PrimaryAction>
                    <TextAction href="/dashboard">{__('client_money.result.home')}</TextAction>
                </div>
            </Card>
        </ClientPage>
    );
}
