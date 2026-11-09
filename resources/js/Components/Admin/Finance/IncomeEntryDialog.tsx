import React, { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { UserSearchCombobox } from '@/Components/Admin/UserSearchCombobox';
import { __ } from '@/lib/i18n';

declare const route: any;

interface Currency {
    id: number;
    code: string;
    symbol: string;
}

interface Props {
    entry: any | null;
    currencies: Currency[];
    defaultCurrencyCode: string;
    onClose: () => void;
}

const SELECT_CLASS = 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10';

const dayOf = (value?: string | null) => (value ? value.slice(0, 10) : '');

/** Adds a new income record, or changes one, from the income page. */
export default function IncomeEntryDialog({ entry, currencies, defaultCurrencyCode, onClose }: Props) {
    const defaultCurrency = currencies.find((currency) => currency.code === defaultCurrencyCode) ?? currencies[0];
    const { data, setData, post, put, processing, errors } = useForm({
        type: 'income',
        title: entry?.reason ?? '',
        amount: entry ? String(Math.abs(Number(entry.amount))) : '',
        currency_id: String(entry?.currency_id ?? defaultCurrency?.id ?? ''),
        user_id: entry?.user?.id ? String(entry.user.id) : '',
        status: entry?.status ?? 'completed',
        due_date: dayOf(entry?.next_due_date),
        transaction_date: dayOf(entry?.created_at) || new Date().toISOString().slice(0, 10),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        if (entry) {
            put(route('admin.finance.update', entry.id), options);
            return;
        }
        post(route('admin.finance.store'), options);
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-[425px]">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{entry ? __('general.edit') : __('general.add_income')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="income-title">{__('general.description_title')}</Label>
                            <Input id="income-title" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                            {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="income-amount">{__('general.amount')}</Label>
                                <Input id="income-amount" type="number" min="0.01" step="0.01" dir="ltr" value={data.amount} onChange={(e) => setData('amount', e.target.value)} required />
                                {errors.amount && <p className="text-sm text-destructive">{errors.amount}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="income-currency">{__('general.currency')}</Label>
                                <select id="income-currency" className={SELECT_CLASS} value={data.currency_id} onChange={(e) => setData('currency_id', e.target.value)}>
                                    {currencies.map((currency) => <option key={currency.id} value={currency.id}>{currency.code}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="income-user">{__('general.client_user_optional')}</Label>
                            <UserSearchCombobox
                                value={data.user_id}
                                onChange={(value) => setData('user_id', value ? String(value) : '')}
                                placeholder={__('general.select_user')}
                                searchPlaceholder={__('general.search_users')}
                            />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="income-status">{__('general.status')}</Label>
                                <select id="income-status" className={SELECT_CLASS} value={data.status} onChange={(e) => setData('status', e.target.value)}>
                                    <option value="completed">{__('general.completed_paid')}</option>
                                    <option value="pending">{__('general.pending')}</option>
                                    <option value="overdue">{__('general.overdue')}</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="income-due">{__('general.due_date')}</Label>
                                <Input id="income-due" type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="income-date">{__('general.transaction_date')}</Label>
                            <Input id="income-date" type="date" value={data.transaction_date} onChange={(e) => setData('transaction_date', e.target.value)} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>{__('general.cancel')}</Button>
                        <Button type="submit" disabled={processing}>{__('general.save')}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
