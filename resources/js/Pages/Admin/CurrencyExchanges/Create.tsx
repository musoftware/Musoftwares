import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface CurrencyMini {
    id: number;
    currency: string;
    symbol: string;
}

interface Props {
    currencies: CurrencyMini[];
}

const NONE = '__none__';

export default function Create({ currencies }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        date_string: new Date().toISOString().slice(0, 10),
        currency1: '',
        currency2: '',
        rate: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.currency-exchanges.store'));
    };

    return (
        <AdminSidebarLayout
            title={__('admin.create_currency_exchange')}
            header={__('admin.create_currency_exchange')}
        >
            <Head title={__('admin.create_currency_exchange')} />

            <div className="w-full max-w-3xl space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" asChild>
                        <Link href={route('admin.currency-exchanges.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900">
                            {__('admin.create_currency_exchange')}
                        </h1>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="date_string">{__('admin.date')}</Label>
                                <Input
                                    id="date_string"
                                    type="date"
                                    value={data.date_string}
                                    onChange={(e) => setData('date_string', e.target.value)}
                                    className={errors.date_string ? 'border-red-500' : ''}
                                />
                                {errors.date_string && (
                                    <p className="text-xs text-red-500">{errors.date_string}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="rate">{__('admin.rate')}</Label>
                                <Input
                                    id="rate"
                                    type="number"
                                    step="0.000001"
                                    min="0"
                                    value={data.rate}
                                    onChange={(e) => setData('rate', e.target.value)}
                                    className={errors.rate ? 'border-red-500' : ''}
                                />
                                {errors.rate && <p className="text-xs text-red-500">{errors.rate}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="currency1">{__('admin.from_currency')}</Label>
                                <Select
                                    value={data.currency1 || NONE}
                                    onValueChange={(v) =>
                                        setData('currency1', v === NONE ? '' : (v as string))
                                    }
                                >
                                    <SelectTrigger className={errors.currency1 ? 'border-red-500' : ''}>
                                        <SelectValue placeholder={__('admin.select_currency')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE} disabled>
                                            {__('admin.select_currency')}
                                        </SelectItem>
                                        {currencies.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.currency} ({c.symbol})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.currency1 && <p className="text-xs text-red-500">{errors.currency1}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="currency2">{__('admin.to_currency')}</Label>
                                <Select
                                    value={data.currency2 || NONE}
                                    onValueChange={(v) =>
                                        setData('currency2', v === NONE ? '' : (v as string))
                                    }
                                >
                                    <SelectTrigger className={errors.currency2 ? 'border-red-500' : ''}>
                                        <SelectValue placeholder={__('admin.select_currency')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE} disabled>
                                            {__('admin.select_currency')}
                                        </SelectItem>
                                        {currencies.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.currency} ({c.symbol})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.currency2 && <p className="text-xs text-red-500">{errors.currency2}</p>}
                            </div>
                        </div>

                        <p className="text-xs text-slate-500">{__('admin.exchange_rate_hint')}</p>

                        <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route('admin.currency-exchanges.index')}>
                                    {__('general.cancel')}
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? __('general.saving') : __('general.save')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}