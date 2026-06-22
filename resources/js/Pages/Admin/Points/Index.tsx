import React, { useState } from 'react';
import { Head, router, useForm, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Coins, History, Search, X, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { __ } from '@/lib/i18n';

// ─── Types ───────────────────────────────────────────────────────────────────

interface UserRow {
    id: number;
    name: string;
    email: string;
    coins_balance: number;
    avatar: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination {
    data: UserRow[];
    links: PaginationLink[];
    meta: any;
    from: number;
    to: number;
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

interface Props {
    users: Pagination;
    search: string;
}

interface HistoryEntry {
    id: number;
    action_name: string;
    coins_reward: number;
    created_at: string;
}

// ─── Adjust Points Dialog ────────────────────────────────────────────────────

function AdjustDialog({ user, onClose }: { user: UserRow; onClose: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '' as string | number,
        reason: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.points.adjust', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    const amount = Number(data.amount) || 0;
    const isAdd = amount > 0;
    const isDeduct = amount < 0;

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Coins className="h-4 w-4 text-amber-500" />
                        Adjust Points — {user.name}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Current balance */}
                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-center">
                        <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">{__('general.current_balance')}</p>
                        <p className="text-2xl font-bold text-slate-900">
                            {user.coins_balance.toLocaleString()}
                            <span className="ms-1 text-sm font-normal text-slate-400">pts</span>
                        </p>
                    </div>

                    {/* Amount */}
                    <div className="space-y-1">
                        <Label htmlFor="adj-amount">
                            Amount
                            {amount !== 0 && (
                                <span className={`ms-2 text-xs font-semibold ${isAdd ? 'text-green-600' : 'text-red-600'}`}>
                                    {isAdd ? '+ Adding' : '− Deducting'} {Math.abs(amount).toLocaleString()} pts
                                </span>
                            )}
                        </Label>
                        <Input
                            id="adj-amount"
                            type="number"
                            placeholder={__('general.e_g_500_to_add_200_to_deduct')}
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            autoFocus
                            className={errors.amount ? 'border-red-500' : ''}
                        />
                        {errors.amount && <p className="text-xs text-red-500">{errors.amount}</p>}
                        <p className="text-xs text-slate-400">{__('general.positive_add_negative_deduct')}</p>
                    </div>

                    {/* Reason */}
                    <div className="space-y-1">
                        <Label htmlFor="adj-reason">{__('general.reason')}</Label>
                        <Input
                            id="adj-reason"
                            placeholder={__('general.e_g_bonus_for_referral_campaign')}
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            className={errors.reason ? 'border-red-500' : ''}
                        />
                        {errors.reason && <p className="text-xs text-red-500">{errors.reason}</p>}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {__('general.cancel')}</Button>
                        <Button
                            type="submit"
                            disabled={processing || !data.amount || !data.reason}
                            variant={isDeduct ? 'destructive' : 'default'}
                        >
                            {processing ? 'Saving…' : isDeduct ? '− Deduct Points' : '+ Add Points'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// ─── History Dialog ───────────────────────────────────────────────────────────

function HistoryDialog({ user, onClose }: { user: UserRow; onClose: () => void }) {
    const [entries, setEntries] = useState<HistoryEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    React.useEffect(() => {
        fetch(route('admin.points.history', user.id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => r.json())
            .then((data) => {
                setEntries(Array.isArray(data) ? data : []);
                setLoading(false);
            })
            .catch(() => {
                setError(true);
                setLoading(false);
            });
    }, [user.id]);

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <History className="h-4 w-4 text-slate-500" />
                        Points History — {user.name}
                    </DialogTitle>
                </DialogHeader>

                <div className="max-h-[55vh] overflow-y-auto -mx-4 px-4">
                    {loading ? (
                        <div className="flex items-center justify-center py-12 text-slate-400 text-sm">
                            Loading history…
                        </div>
                    ) : error ? (
                        <div className="flex items-center justify-center py-12 text-red-500 text-sm">{__('general.failed_to_load_history')}</div>
                    ) : entries.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-slate-400 text-sm gap-2">
                            <History className="h-8 w-8 opacity-30" />{__('general.no_points_history_found')}</div>
                    ) : (
                        <table className="w-full text-start text-[13px]">
                            <thead className="sticky top-0 bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{__('general.action_label')}</th>
                                    <th className="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 text-center">
                                        {__('general.points')}</th>
                                    <th className="px-3 py-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 text-end">
                                        {__('general.date')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {entries.map((entry) => (
                                    <tr key={entry.id} className="hover:bg-slate-50/70 transition-colors">
                                        <td className="px-3 py-2.5 text-slate-700 max-w-[280px] truncate">
                                            {entry.action_name}
                                        </td>
                                        <td className="px-3 py-2.5 text-center">
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                    entry.coins_reward > 0
                                                        ? 'bg-green-100 text-green-700'
                                                        : entry.coins_reward < 0
                                                        ? 'bg-red-100 text-red-700'
                                                        : 'bg-slate-100 text-slate-500'
                                                }`}
                                            >
                                                {entry.coins_reward > 0 ? (
                                                    <TrendingUp className="h-3 w-3" />
                                                ) : entry.coins_reward < 0 ? (
                                                    <TrendingDown className="h-3 w-3" />
                                                ) : (
                                                    <Minus className="h-3 w-3" />
                                                )}
                                                {entry.coins_reward > 0 ? '+' : ''}
                                                {entry.coins_reward.toLocaleString()}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2.5 text-end text-slate-400 text-xs whitespace-nowrap">
                                            {new Date(entry.created_at).toLocaleString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                <DialogFooter showCloseButton />
            </DialogContent>
        </Dialog>
    );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function Index({ users, search }: Props) {
    const [adjustTarget, setAdjustTarget] = useState<UserRow | null>(null);
    const [historyTarget, setHistoryTarget] = useState<UserRow | null>(null);
    const [searchInput, setSearchInput] = useState(search ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.points.index'), { search: searchInput }, { preserveState: true });
    };

    const clearSearch = () => {
        setSearchInput('');
        router.get(route('admin.points.index'));
    };

    const rows = users?.data ?? [];

    return (
        <AdminSidebarLayout title={__('general.points_control')} header="Points Control">
            <Head title={__('general.points_control')} />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">{__('general.points_control')}</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{__('general.view_and_adjust_user_points_balances_across_the_platform')}</p>
                    </div>
                    <div className="flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5">
                        <Coins className="h-4 w-4 text-amber-500" />
                        <span className="text-sm font-medium text-amber-700">
                            {users?.total ?? 0} users
                        </span>
                    </div>
                </div>

                {/* Search */}
                <form onSubmit={handleSearch} className="flex items-center gap-2">
                    <div className="relative max-w-sm w-full">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                        <Input
                            type="text"
                            placeholder={__('general.search_by_name_or_email')}
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            className="ps-9 pe-8"
                        />
                        {searchInput && (
                            <button
                                type="button"
                                onClick={clearSearch}
                                className="absolute end-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>
                    <Button type="submit" variant="default" size="sm">
                        {__('general.search')}</Button>
                </form>

                {/* Table */}
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-start text-[13px]">
                        <thead className="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    {__('general.user')}</th>
                                <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{__('general.points_balance')}</th>
                                <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 text-end">
                                    {__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.length > 0 ? (
                                rows.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="transition-colors duration-100 hover:bg-slate-50/70"
                                    >
                                        {/* User */}
                                        <td className="px-4 py-3">
                                            <Link href={route('admin.users.show', user.id)} className="flex items-center gap-3 group">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-white text-xs font-bold flex-shrink-0 group-hover:bg-slate-900 transition-colors">
                                                    {user.name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="font-medium text-slate-900 group-hover:text-slate-900 transition-colors">{user.name}</div>
                                                    <div className="text-[11px] text-slate-400">{user.email}</div>
                                                </div>
                                            </Link>
                                        </td>

                                        {/* Balance */}
                                        <td className="px-4 py-3">
                                            <span
                                                className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${
                                                    user.coins_balance > 0
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : user.coins_balance < 0
                                                        ? 'bg-red-100 text-red-700'
                                                        : 'bg-slate-100 text-slate-500'
                                                }`}
                                            >
                                                <Coins className="h-3 w-3" />
                                                {user.coins_balance.toLocaleString()} pts
                                            </span>
                                        </td>

                                        {/* Actions */}
                                        <td className="px-4 py-3 text-end">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => setAdjustTarget(user)}
                                                >
                                                    <Coins className="me-1.5 h-3.5 w-3.5" />
                                                    {__('general.adjust')}</Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => setHistoryTarget(user)}
                                                >
                                                    <History className="me-1.5 h-3.5 w-3.5" />
                                                    {__('general.history')}</Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={3} className="px-4 py-12 text-center text-slate-400">
                                        <Coins className="mx-auto mb-2 h-8 w-8 opacity-20" />
                                        <p>No users found{search ? ` for "${search}"` : ''}.</p>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {users?.links && users.total > 0 && (
                        <div className="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 text-[13px] text-slate-600">
                            <span className="text-xs text-slate-400">
                                {users.from}–{users.to} of{' '}
                                <span className="font-medium text-slate-700">{users.total}</span>
                            </span>
                            <div className="flex items-center gap-1 flex-wrap">
                                {users.links.map((link, idx) =>
                                    link.url ? (
                                        <button
                                            key={idx}
                                            onClick={() => router.visit(link.url!)}
                                            className={`min-w-[28px] rounded-md px-2.5 py-1 text-[12px] transition-colors text-center ${
                                                link.active
                                                    ? 'bg-slate-900 font-medium text-white shadow-sm'
                                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            key={idx}
                                            className="min-w-[28px] rounded-md px-2.5 py-1 text-[12px] text-slate-300 cursor-not-allowed text-center"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Dialogs */}
            {adjustTarget && (
                <AdjustDialog user={adjustTarget} onClose={() => setAdjustTarget(null)} />
            )}
            {historyTarget && (
                <HistoryDialog user={historyTarget} onClose={() => setHistoryTarget(null)} />
            )}
        </AdminSidebarLayout>
    );
}
