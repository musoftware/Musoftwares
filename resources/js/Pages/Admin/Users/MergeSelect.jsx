import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ShieldCheck,
    Search,
    GitMerge,
    AlertTriangle,
    CheckCircle2,
    User as UserIcon,
    Mail,
    CalendarClock,
    Info,
} from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { __ } from '@/lib/i18n';

function useDebouncedValue(value, delay = 350) {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const t = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(t);
    }, [value, delay]);
    return debounced;
}

export default function MergeSelect({ survivor, search: initialSearch = '', suggestions = [], recently_merged = [] }) {
    const [search, setSearch] = useState(initialSearch || '');
    const [selected, setSelected] = useState([]);
    const debouncedSearch = useDebouncedValue(search, 350);
    const lastIssuedRef = useRef(initialSearch || '');

    useEffect(() => {
        const next = debouncedSearch.trim();
        if (next === lastIssuedRef.current) return;
        lastIssuedRef.current = next;
        router.get(
            route('admin.users.merge.select', survivor.id),
            next === '' ? {} : { search: next },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, [debouncedSearch, survivor.id]);

    const toggle = (id) => {
        setSelected((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
        );
    };

    const selectAll = () => {
        setSelected(suggestions.map((s) => s.id));
    };

    const clearSelection = () => setSelected([]);

    const sortedSuggestions = useMemo(() => {
        return [...suggestions].sort((a, b) => {
            const aSel = selected.includes(a.id) ? 0 : 1;
            const bSel = selected.includes(b.id) ? 0 : 1;
            if (aSel !== bSel) return aSel - bSel;
            return a.id - b.id;
        });
    }, [suggestions, selected]);

    const continueToReview = () => {
        if (selected.length === 0) return;
        const url =
            route('admin.users.merge.preview', survivor.id) +
            '?' +
            selected.map((id) => `duplicate_ids[]=${id}`).join('&');
        router.get(url);
    };

    const survivorInitial = (survivor.name || survivor.email || '?').trim().charAt(0).toUpperCase();

    return (
        <AdminSidebarLayout auth={{ user: survivor }}>
            <Head title={`Merge into #${survivor.id}`} />

            <div className="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold tracking-wider uppercase text-slate-500">
                            {__('general.users') || 'Users'} · {__('general.merge') || 'Merge'}
                        </p>
                        <h1 className="mt-1 text-2xl sm:text-3xl font-bold text-slate-900 flex items-center gap-3">
                            <GitMerge className="h-7 w-7 text-indigo-600" />
                            {__('general.merge_accounts_into') || 'Merge accounts into'} #{survivor.id}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {__('general.merge_select_intro') ||
                                'Search for the duplicate accounts you want to absorb into this survivor. You can pick one or many. The merge review screen will show every conflict before anything is committed.'}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('admin.users.show', survivor.id)}>
                            <ArrowLeft className="me-2 h-4 w-4" />
                            {__('general.back_to_user') || 'Back to user'}
                        </Link>
                    </Button>
                </div>

                <section className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 sm:p-6 shadow-sm">
                    <div className="flex items-start gap-4">
                        <div className="h-12 w-12 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shrink-0">
                            {survivorInitial}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="text-[11px] font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-2">
                                <ShieldCheck className="h-3.5 w-3.5" />
                                {__('general.survivor_primary_account') || 'Survivor (primary account)'}
                            </div>
                            <div className="mt-1 text-lg font-semibold text-slate-900 truncate">
                                {survivor.name || '—'}
                            </div>
                            <div className="text-sm text-slate-600 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span className="inline-flex items-center gap-1.5">
                                    <Mail className="h-3.5 w-3.5 text-slate-400" />
                                    <span className="font-mono truncate">{survivor.email}</span>
                                </span>
                                {survivor.role && (
                                    <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white border border-emerald-200 text-emerald-700">
                                        {survivor.role}
                                    </span>
                                )}
                                {survivor.email_verified_at && (
                                    <span className="inline-flex items-center gap-1 text-emerald-700 text-xs font-semibold">
                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                        {__('general.email_verified') || 'Email verified'}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="p-5 sm:p-6 border-b border-slate-100">
                        <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <Search className="h-4 w-4 text-slate-500" />
                            {__('general.find_duplicate_accounts') || 'Find duplicate accounts'}
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            {__('general.merge_search_help') ||
                                'Search by user ID, name, or email. Each result can be reviewed before merging. The survivor is always excluded automatically.'}
                        </p>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                const next = search.trim();
                                if (next === lastIssuedRef.current) return;
                                lastIssuedRef.current = next;
                                router.get(
                                    route('admin.users.merge.select', survivor.id),
                                    next === '' ? {} : { search: next },
                                    { preserveState: true, replace: true, preserveScroll: true }
                                );
                            }}
                            className="mt-4 flex flex-col sm:flex-row gap-2"
                        >
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <Input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder={__('general.merge_search_placeholder') || 'Search by ID, name, or email…'}
                                    className="ps-9 h-11"
                                    autoFocus
                                />
                            </div>
                            <Button type="submit" variant="outline" className="h-11">
                                {__('general.search') || 'Search'}
                            </Button>
                            {search && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="h-11"
                                    onClick={() => {
                                        setSearch('');
                                        lastIssuedRef.current = '';
                                        router.get(
                                            route('admin.users.merge.select', survivor.id),
                                            {},
                                            { preserveState: true, replace: true, preserveScroll: true }
                                        );
                                    }}
                                >
                                    {__('general.clear') || 'Clear'}
                                </Button>
                            )}
                        </form>
                    </div>

                    <div className="p-5 sm:p-6">
                        {search.trim() === '' ? (
                            <EmptySearchState />
                        ) : suggestions.length === 0 ? (
                            <NoResultsState query={search} />
                        ) : (
                            <>
                                <div className="flex items-center justify-between mb-3">
                                <div className="text-xs uppercase tracking-wider font-bold text-slate-500">
                                    {suggestions.length === 1
                                        ? __('general.one_result') || '1 result'
                                        : __('general.n_results', { count: suggestions.length }) ||
                                          `${suggestions.length} results`}
                                </div>
                                    <div className="flex items-center gap-2">
                                        {selected.length > 0 && (
                                            <button
                                                type="button"
                                                onClick={clearSelection}
                                                className="text-xs font-semibold text-slate-500 hover:text-slate-900 underline-offset-2 hover:underline"
                                            >
                                                {__('general.clear_selection') || 'Clear selection'}
                                            </button>
                                        )}
                                        <button
                                            type="button"
                                            onClick={selectAll}
                                            className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline-offset-2 hover:underline"
                                        >
                                            {__('general.select_all_results') || 'Select all'}
                                        </button>
                                    </div>
                                </div>
                                <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden">
                                    {sortedSuggestions.map((s) => {
                                        const checked = selected.includes(s.id);
                                        return (
                                            <li
                                                key={s.id}
                                                className={`flex items-center gap-3 px-4 py-3 transition-colors ${
                                                    checked ? 'bg-indigo-50/60' : 'bg-white hover:bg-slate-50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                    checked={checked}
                                                    onChange={() => toggle(s.id)}
                                                />
                                                <div className="h-9 w-9 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm shrink-0">
                                                    {(s.name || s.email || '?').charAt(0).toUpperCase()}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs text-slate-400">#{s.id}</span>
                                                        <span className="font-semibold text-slate-900 truncate">
                                                            {s.name || '—'}
                                                        </span>
                                                        {s.role && (
                                                            <span className="px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                                                {s.role}
                                                            </span>
                                                        )}
                                                        {s.email_verified ? (
                                                            <span className="inline-flex items-center gap-1 text-emerald-700 text-[10px] font-semibold">
                                                                <CheckCircle2 className="h-3 w-3" />
                                                                {__('general.verified') || 'Verified'}
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1 text-amber-700 text-[10px] font-semibold">
                                                                <AlertTriangle className="h-3 w-3" />
                                                                {__('general.unverified') || 'Unverified'}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-slate-500 font-mono truncate">{s.email}</div>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </>
                        )}
                    </div>
                </section>

                {recently_merged.length > 0 && (
                    <section className="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                        <h2 className="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <Info className="h-4 w-4 text-slate-500" />
                            {__('general.recently_merged_into_this_account') || 'Recently merged into this account'}
                        </h2>
                        <ul className="mt-3 divide-y divide-slate-200/70 rounded-lg border border-slate-200 bg-white">
                            {recently_merged.map((m) => (
                                <li key={m.id} className="flex items-center gap-3 px-4 py-2 text-sm">
                                    <UserIcon className="h-4 w-4 text-slate-400" />
                                    <span className="font-mono text-xs text-slate-400">#{m.id}</span>
                                    <span className="font-semibold text-slate-800 truncate">{m.name || '—'}</span>
                                    <span className="font-mono text-xs text-slate-500 truncate">{m.email}</span>
                                    <span className="ms-auto inline-flex items-center gap-1 text-[11px] text-slate-500">
                                        <CalendarClock className="h-3 w-3" />
                                        {m.merged_at ? new Date(m.merged_at).toLocaleDateString() : '—'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <div className="sticky bottom-0 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 bg-white/90 backdrop-blur border-t border-slate-200">
                    <div className="max-w-5xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div className="text-sm text-slate-600">
                            {selected.length === 0 ? (
                                <span>{__('general.no_duplicates_selected') || 'No duplicates selected yet.'}</span>
                            ) : (
                                <span className="font-semibold text-slate-900">
                                    {selected.length === 1
                                        ? __('general.one_duplicate_selected') || '1 duplicate selected'
                                        : __('general.n_duplicates_selected_plain', { count: selected.length }) ||
                                          `${selected.length} duplicates selected`}
                                </span>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline">
                                <Link href={route('admin.users.show', survivor.id)}>
                                    {__('general.cancel') || 'Cancel'}
                                </Link>
                            </Button>
                            <Button
                                onClick={continueToReview}
                                disabled={selected.length === 0}
                                className="bg-indigo-600 hover:bg-indigo-700"
                            >
                                <GitMerge className="me-2 h-4 w-4" />
                                {__('general.continue_to_review') || 'Continue to merge review'}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}

function EmptySearchState() {
    return (
        <div className="flex flex-col items-center justify-center text-center py-10 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
            <div className="h-12 w-12 rounded-full bg-white border border-slate-200 flex items-center justify-center mb-3">
                <Search className="h-5 w-5 text-slate-400" />
            </div>
            <p className="text-sm font-semibold text-slate-800">
                {__('general.start_typing_to_search') || 'Start typing to search'}
            </p>
            <p className="mt-1 text-xs text-slate-500 max-w-sm">
                {__('general.merge_empty_state_help') ||
                    'Enter a user ID (e.g. 1234), part of a name, or an email address. Matching accounts will appear here for you to select.'}
            </p>
        </div>
    );
}

function NoResultsState({ query }) {
    return (
        <div className="flex flex-col items-center justify-center text-center py-10 px-4 rounded-xl border border-dashed border-amber-200 bg-amber-50/50">
            <div className="h-12 w-12 rounded-full bg-white border border-amber-200 flex items-center justify-center mb-3">
                <AlertTriangle className="h-5 w-5 text-amber-500" />
            </div>
            <p className="text-sm font-semibold text-amber-900">
                {__('general.no_matching_users') || 'No matching accounts'}
            </p>
            <p className="mt-1 text-xs text-amber-700 max-w-sm">
                {__('general.no_matches_for_query', { query }) ||
                    `We couldn't find any active accounts for "${query}". Try a different name, email, or numeric ID.`}
            </p>
        </div>
    );
}
