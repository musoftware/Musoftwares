import React from 'react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';

export type SystemState = 'active' | 'due' | 'locked';

export interface SystemSummary {
    id: number;
    name: string;
    site_url: string | null;
    user: { id: number; name: string; email: string } | null;
    currency: string | null;
    monthly_price: number;
    sales_percent: number;
    state: SystemState;
    last_check_in_at: string | null;
    has_alert: boolean;
}

export interface SystemFieldsData {
    name: string;
    site_url: string;
    monthly_price: string | number;
    sales_percent: string | number;
    grace_days: string | number;
}

const STATE_STYLE: Record<SystemState, string> = {
    active: 'text-green-700',
    due: 'text-amber-700',
    locked: 'text-red-700',
};

export function StateText({ state }: { state: SystemState }) {
    return <span className={`font-semibold ${STATE_STYLE[state]}`}>{__(`client_systems.state_${state}`)}</span>;
}

/** "500 EGP a month + 5% of sales", or only the part that applies. */
export function PriceText({ system }: { system: Pick<SystemSummary, 'monthly_price' | 'sales_percent' | 'currency'> }) {
    const parts = [
        system.monthly_price > 0 ? __('client_systems.price_monthly', { amount: system.monthly_price.toLocaleString(), currency: system.currency ?? '' }) : null,
        system.sales_percent > 0 ? __('client_systems.price_percent', { percent: system.sales_percent }) : null,
    ].filter(Boolean);
    return <span>{parts.length ? parts.join(' + ') : __('client_systems.price_free')}</span>;
}

export function LastCheckIn({ at }: { at: string | null }) {
    return <span>{at ? new Date(at).toLocaleString() : __('client_systems.never_checked_in')}</span>;
}

interface FieldsProps {
    data: SystemFieldsData;
    errors: Partial<Record<keyof SystemFieldsData, string>>;
    setData: (key: keyof SystemFieldsData, value: string) => void;
    idPrefix: string;
    currency?: React.ReactNode;
}

function Field({ id, label, hint, error, children }: { id: string; label: string; hint?: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="space-y-1">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            {error && <p className="text-sm text-red-700">{error}</p>}
        </div>
    );
}

/** The fields shared by "new system" and "edit system". */
export function SystemFields({ data, errors, setData, idPrefix, currency }: FieldsProps) {
    const id = (name: string) => `${idPrefix}-${name}`;
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <Field id={id('name')} label={__('client_systems.field_name')} error={errors.name}>
                <Input id={id('name')} value={data.name} onChange={(e) => setData('name', e.target.value)} required maxLength={120} />
            </Field>
            <Field id={id('url')} label={__('client_systems.field_site_url')} error={errors.site_url}>
                <Input id={id('url')} type="url" dir="ltr" value={data.site_url} onChange={(e) => setData('site_url', e.target.value)} placeholder="https://" />
            </Field>
            {currency}
            <Field id={id('monthly')} label={__('client_systems.field_monthly_price')} hint={__('client_systems.field_monthly_price_hint')} error={errors.monthly_price}>
                <Input id={id('monthly')} type="number" min={0} step="0.01" dir="ltr" inputMode="decimal" value={data.monthly_price} onChange={(e) => setData('monthly_price', e.target.value)} required />
            </Field>
            <Field id={id('percent')} label={__('client_systems.field_sales_percent')} hint={__('client_systems.field_sales_percent_hint')} error={errors.sales_percent}>
                <Input id={id('percent')} type="number" min={0} max={100} step="0.01" dir="ltr" inputMode="decimal" value={data.sales_percent} onChange={(e) => setData('sales_percent', e.target.value)} required />
            </Field>
            <Field id={id('grace')} label={__('client_systems.field_grace_days')} hint={__('client_systems.field_grace_days_hint')} error={errors.grace_days}>
                <Input id={id('grace')} type="number" min={0} max={60} dir="ltr" inputMode="numeric" value={data.grace_days} onChange={(e) => setData('grace_days', e.target.value)} required />
            </Field>
        </div>
    );
}
