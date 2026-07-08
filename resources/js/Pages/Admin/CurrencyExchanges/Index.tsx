import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogTrigger,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { MoreHorizontal, Search, Plus, Trash2, Edit } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface CurrencyMini {
    id: number;
    currency: string;
    symbol: string;
}

interface Exchange {
    id: number;
    date_string: string;
    currency1: number;
    currency2: number;
    rate: number;
    currency_from?: CurrencyMini | null;
    currency_to?: CurrencyMini | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
}

interface Props {
    exchanges: Pagination<Exchange>;
    currencies: CurrencyMini[];
    filters: { search: string; currency_id: string | null };
}

const ALL_CURRENCIES = '__all__';

export default function Index({ exchanges, currencies, filters }: Props) {
    const [searchInput, setSearchInput] = React.useState(filters.search ?? '');
    const [currencyFilter, setCurrencyFilter] = React.useState<string>(
        filters.currency_id ? String(filters.currency_id) : ALL_CURRENCIES,
    );
    const handleCurrencyFilterChange = (val: string | null) =>
        setCurrencyFilter(val ?? ALL_CURRENCIES);
    const [deleteId, setDeleteId] = React.useState<number | null>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = { search: searchInput };
        if (currencyFilter && currencyFilter !== ALL_CURRENCIES) params.currency_id = currencyFilter;
        router.get(route('admin.currency-exchanges.index'), params, { preserveState: true });
    };

    const handleDelete = () => {
        if (!deleteId) return;
        router.delete(route('admin.currency-exchanges.destroy', deleteId), {
            onSuccess: () => setDeleteId(null),
        });
    };

    return (
        <AdminSidebarLayout
            title={__('admin.currency_exchanges')}
            header={__('admin.currency_exchanges')}
        >
            <Head title={__('admin.currency_exchanges')} />

            <div className="space-y-6">
                <div className="flex items-center justify-end gap-4">
                    <div className="me-auto">
                        <h1 className="text-2xl font-semibold text-slate-900">
                            {__('admin.currency_exchanges')}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('admin.manage_currency_exchanges')}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route('admin.currency-exchanges.create')}>
                            <Plus className="me-2 h-4 w-4" />
                            {__('admin.create_currency_exchange')}
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch} className="flex flex-wrap items-center gap-2">
                    <div className="relative w-full max-w-xs">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input
                            placeholder={__('admin.search_currency_code')}
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            className="ps-9"
                        />
                    </div>
                    <Select value={currencyFilter} onValueChange={handleCurrencyFilterChange}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder={__('admin.all_currencies')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_CURRENCIES}>{__('admin.all_currencies')}</SelectItem>
                            {currencies.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.currency} ({c.symbol})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="outline">
                        {__('general.search')}
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-start text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.date')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.from')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.to')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.rate')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500 text-end">{__('general.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {(exchanges.data as any).length > 0 ? (
                                    (exchanges.data as any).map((ex: Exchange) => (
                                        <tr key={ex.id} className="hover:bg-slate-50 transition-colors">
                                            <td className="px-4 py-3 text-slate-700">{ex.date_string}</td>
                                            <td className="px-4 py-3 font-medium text-slate-900">
                                                {ex.currency_from?.currency ?? `#${ex.currency1}`}{' '}
                                                <span className="text-slate-400 text-xs">
                                                    {ex.currency_from?.symbol}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-medium text-slate-900">
                                                {ex.currency_to?.currency ?? `#${ex.currency2}`}{' '}
                                                <span className="text-slate-400 text-xs">
                                                    {ex.currency_to?.symbol}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {Number(ex.rate).toFixed(6)}
                                            </td>
                                            <td className="px-4 py-3 text-end">
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent className="sm:max-w-xs">
                                                        <DialogHeader>
                                                            <DialogTitle>{__('general.actions')}</DialogTitle>
                                                        </DialogHeader>
                                                        <div className="flex flex-col gap-2 py-2">
                                                            <Button
                                                                variant="outline"
                                                                className="justify-start"
                                                                onClick={() =>
                                                                    router.visit(
                                                                        route(
                                                                            'admin.currency-exchanges.edit',
                                                                            ex.id,
                                                                        ),
                                                                    )
                                                                }
                                                            >
                                                                <Edit className="me-2 h-4 w-4" />
                                                                {__('general.edit')}
                                                            </Button>
                                                            <Button
                                                                variant="destructive"
                                                                className="justify-start"
                                                                onClick={() => setDeleteId(ex.id)}
                                                            >
                                                                <Trash2 className="me-2 h-4 w-4" />
                                                                {__('general.delete')}
                                                            </Button>
                                                        </div>
                                                    </DialogContent>
                                                </Dialog>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                                            {__('general.no_records_found')}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {exchanges.total > 0 && exchanges.links.length > 3 && (
                        <div className="flex items-center justify-end gap-4 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <div className="me-auto flex items-center gap-1">
                                {exchanges.links.map((link, idx) =>
                                    link.url ? (
                                        <button
                                            key={idx}
                                            onClick={() => router.visit(link.url!)}
                                            className={`rounded-md px-3 py-1 text-sm transition-colors ${
                                                link.active
                                                    ? 'bg-slate-900 font-medium text-white shadow-sm'
                                                    : 'text-slate-500 hover:bg-slate-100'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            key={idx}
                                            className="rounded-md px-3 py-1 text-sm text-slate-300 cursor-not-allowed"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ),
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={deleteId !== null} onOpenChange={(open) => !open && setDeleteId(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.confirm_delete')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-slate-500">
                        {__('general.are_you_sure_you_want_to_delete_this_item')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteId(null)}>
                            {__('general.cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            {__('general.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}