import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Currency {
    id: number;
    currency: string;
    symbol: string;
    string_format: string;
}

interface Props {
    currency: Currency;
}

export default function Edit({ currency }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        currency: currency.currency,
        symbol: currency.symbol,
        string_format: currency.string_format,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.currencies.update', currency.id));
    };

    return (
        <AdminSidebarLayout title={__('admin.edit_currency')} header={__('admin.edit_currency')}>
            <Head title={__('admin.edit_currency')} />

            <div className="w-full max-w-3xl space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" asChild>
                        <Link href={route('admin.currencies.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900">{__('admin.edit_currency')}</h1>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="currency">{__('admin.currency_code')}</Label>
                                <Input
                                    id="currency"
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value.toUpperCase())}
                                    maxLength={10}
                                    className={errors.currency ? 'border-red-500' : ''}
                                />
                                {errors.currency && <p className="text-xs text-red-500">{errors.currency}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="symbol">{__('admin.symbol')}</Label>
                                <Input
                                    id="symbol"
                                    value={data.symbol}
                                    onChange={(e) => setData('symbol', e.target.value)}
                                    maxLength={10}
                                    className={errors.symbol ? 'border-red-500' : ''}
                                />
                                {errors.symbol && <p className="text-xs text-red-500">{errors.symbol}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="string_format">{__('admin.string_format')}</Label>
                                <Input
                                    id="string_format"
                                    value={data.string_format}
                                    onChange={(e) => setData('string_format', e.target.value)}
                                    maxLength={20}
                                    className={errors.string_format ? 'border-red-500' : ''}
                                />
                                {errors.string_format && (
                                    <p className="text-xs text-red-500">{errors.string_format}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route('admin.currencies.index')}>{__('general.cancel')}</Link>
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