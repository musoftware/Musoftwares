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

interface Currency {
    id: number;
    currency: string;
    symbol: string;
    string_format: string;
    country_codes?: string[];
    is_default?: boolean;
    exchanges_from_count?: number;
    exchanges_to_count?: number;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
}

interface Props {
    currencies: Pagination<Currency>;
    search: string;
}

export default function Index({ currencies, search }: Props) {
    const [searchInput, setSearchInput] = React.useState(search ?? '');
    const [deleteId, setDeleteId] = React.useState<number | null>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.currencies.index'), { search: searchInput }, { preserveState: true });
    };

    const handleDelete = () => {
        if (!deleteId) return;
        router.delete(route('admin.currencies.destroy', deleteId), {
            onSuccess: () => setDeleteId(null),
        });
    };

    return (
        <AdminSidebarLayout title={__('admin.currencies')} header={__('admin.currencies')}>
            <Head title={__('admin.currencies')} />

            <div className="space-y-6">
                <div className="flex items-center justify-end gap-4">
                    <div className="me-auto">
                        <h1 className="text-2xl font-semibold text-slate-900">{__('admin.currencies')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('admin.manage_currencies')}</p>
                    </div>
                    <Button asChild>
                        <Link href={route('admin.currencies.create')}>
                            <Plus className="me-2 h-4 w-4" />
                            {__('admin.create_currency')}
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch} className="flex max-w-sm items-center gap-2">
                    <div className="relative w-full">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input
                            placeholder={__('admin.search_currencies')}
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            className="ps-9"
                        />
                    </div>
                    <Button type="submit" variant="outline">
                        {__('general.search')}
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-start text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.currency_code')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.symbol')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.countries')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.string_format')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500">{__('admin.default')}</th>
                                    <th className="px-4 py-3 font-semibold text-slate-500 text-end">{__('general.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {(currencies.data as any).length > 0 ? (
                                    (currencies.data as any).map((c: Currency) => (
                                        <tr key={c.id} className="hover:bg-slate-50 transition-colors">
                                            <td className="px-4 py-3 font-medium text-slate-900">{c.currency}</td>
                                            <td className="px-4 py-3 text-slate-600">{c.symbol}</td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {c.country_codes && c.country_codes.length > 0 ? (
                                                    <div className="flex flex-wrap gap-1">
                                                        {c.country_codes.map((code) => (
                                                            <span key={code} className="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                                                {code}
                                                            </span>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-slate-400">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                <code className="rounded bg-slate-100 px-2 py-1 text-xs">{c.string_format}</code>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {c.is_default ? (
                                                    <span className="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                                        {__('general.default') || 'Default'}
                                                    </span>
                                                ) : null}
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
                                                                onClick={() => router.visit(route('admin.currencies.edit', c.id))}
                                                            >
                                                                <Edit className="me-2 h-4 w-4" />
                                                                {__('general.edit')}
                                                            </Button>
                                                            <Button
                                                                variant="destructive"
                                                                className="justify-start"
                                                                onClick={() => setDeleteId(c.id)}
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

                    {currencies.total > 0 && currencies.links.length > 3 && (
                        <div className="flex items-center justify-end gap-4 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <div className="me-auto flex items-center gap-1">
                                {currencies.links.map((link, idx) =>
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
                    <p className="text-sm text-slate-500">{__('general.are_you_sure_you_want_to_delete_this_item')}</p>
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