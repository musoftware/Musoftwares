import React, { FormEvent, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { Plus, ServerCog } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { LastCheckIn, PriceText, StateText, SystemFields, SystemFieldsData, SystemSummary } from './shared';

interface Currency {
    id: number;
    currency: string;
}

interface Props {
    clientId: number;
    clientCurrencyId: number | null;
    systems: SystemSummary[];
    currencies: Currency[];
}

function NewSystemForm({ clientId, clientCurrencyId, currencies, onCancel }: Omit<Props, 'systems'> & { onCancel: () => void }) {
    const form = useForm<SystemFieldsData & { currency_id: string }>({
        name: '', site_url: '', monthly_price: '0', sales_percent: '0', grace_days: '7',
        currency_id: String(clientCurrencyId ?? currencies[0]?.id ?? ''),
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/admin/users/${clientId}/client-systems`, { preserveScroll: true });
    };
    const currency = (
        <div className="space-y-1">
            <Label htmlFor="cs-new-currency">{__('client_systems.field_currency')}</Label>
            <select id="cs-new-currency" className="h-11 w-full rounded-md border border-slate-300 bg-white px-3 text-sm" value={form.data.currency_id} onChange={(e) => form.setData('currency_id', e.target.value)}>
                {currencies.map((item) => <option key={item.id} value={item.id}>{item.currency}</option>)}
            </select>
            {form.errors.currency_id && <p className="text-sm text-red-700">{form.errors.currency_id}</p>}
        </div>
    );

    return (
        <form onSubmit={submit} className="space-y-4 rounded-md border border-slate-200 p-4">
            <SystemFields idPrefix="cs-new" data={form.data} errors={form.errors} setData={(key, value) => form.setData(key, value)} currency={currency} />
            <p className="text-sm text-muted-foreground">{__('client_systems.first_bill_note')}</p>
            <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={form.processing} className="min-h-11">{__('client_systems.create')}</Button>
                <Button type="button" variant="outline" onClick={onCancel} className="min-h-11">{__('general.cancel')}</Button>
            </div>
        </form>
    );
}

/** On the client's page: their licensed systems, and a form to add one. */
export default function ClientSystemsSection({ clientId, clientCurrencyId, systems, currencies }: Props) {
    const [adding, setAdding] = useState(false);

    return (
        <section id="client-systems" className="mb-6 scroll-mt-24 rounded-[12px] border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b pb-2">
                <h2 className="flex items-center gap-2 text-lg font-bold text-slate-900">
                    <ServerCog size={18} className="text-slate-400" aria-hidden="true" />
                    {__('client_systems.section_title')}
                </h2>
                {!adding && (
                    <Button type="button" variant="outline" onClick={() => setAdding(true)} className="min-h-11">
                        <Plus size={16} aria-hidden="true" /> {__('client_systems.new')}
                    </Button>
                )}
            </div>
            <p className="mb-4 text-sm text-muted-foreground">{__('client_systems.section_hint')}</p>
            {adding && <NewSystemForm clientId={clientId} clientCurrencyId={clientCurrencyId} currencies={currencies} onCancel={() => setAdding(false)} />}
            {systems.length > 0 && (
                <ul className="divide-y">
                    {systems.map((system) => (
                        <li key={system.id} className="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                            <div className="min-w-0">
                                <Link href={`/admin/client-systems/${system.id}`} className="font-semibold text-blue-700 underline">{system.name}</Link>
                                <div className="text-muted-foreground"><PriceText system={system} /> · <LastCheckIn at={system.last_check_in_at} /></div>
                            </div>
                            <StateText state={system.state} />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
