import React, { useEffect, useRef, useState } from 'react';
import { ArrowDownUp, ArrowDown, ArrowUp } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import { cn } from '@/lib/utils';
import { PROJECT_STATUS_OPTIONS } from './ProjectFormFields';
import type {
    ProjectFiltersState,
    ProjectOwnerOption,
    ProjectSort,
    ProjectStatus,
} from '@/types/project';

export type FilterPartial = Record<string, string | number | string[] | null | undefined>;

const SORT_OPTIONS: { value: ProjectSort; label: string }[] = [
    { value: 'created_at', label: __('general.created_at') },
    { value: 'project_name', label: __('general.name') },
    { value: 'status', label: __('general.status') },
    { value: 'budget', label: __('general.budget') },
    { value: 'percentage', label: __('general.percentage') },
    { value: 'date_start', label: __('general.start_date') },
    { value: 'date_end', label: __('general.end_date') },
];

const STATUS_PILL_STYLES: Record<string, string> = {
    open: 'data-[on=true]:bg-emerald-100 data-[on=true]:text-emerald-800 data-[on=true]:border-emerald-300',
    hold_on: 'data-[on=true]:bg-amber-100 data-[on=true]:text-amber-800 data-[on=true]:border-amber-300',
    closed: 'data-[on=true]:bg-slate-200 data-[on=true]:text-slate-800 data-[on=true]:border-slate-300',
};

const inputClass =
    'mt-1 flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
const selectClass = inputClass;

export interface ProjectFiltersPanelProps {
    filters: ProjectFiltersState;
    owners: ProjectOwnerOption[];
    perPageOptions: number[];
    sort: ProjectSort;
    dir: 'asc' | 'desc';
    perPage: number;
    onChange: (partial: FilterPartial) => void;
    onClear: () => void;
}

export function ProjectFiltersPanel({
    filters,
    owners,
    perPageOptions,
    sort,
    dir,
    perPage,
    onChange,
    onClear,
}: ProjectFiltersPanelProps) {
    // Local draft state mirrors server state and lets text inputs feel instant.
    const [search, setSearch] = useState(filters.search ?? '');
    const [budgetMin, setBudgetMin] = useState(filters.budget_min ?? '');
    const [budgetMax, setBudgetMax] = useState(filters.budget_max ?? '');
    const [percentMin, setPercentMin] = useState(filters.percent_min ?? '');
    const [percentMax, setPercentMax] = useState(filters.percent_max ?? '');

    useEffect(() => setSearch(filters.search ?? ''), [filters.search]);
    useEffect(() => setBudgetMin(filters.budget_min ?? ''), [filters.budget_min]);
    useEffect(() => setBudgetMax(filters.budget_max ?? ''), [filters.budget_max]);
    useEffect(() => setPercentMin(filters.percent_min ?? ''), [filters.percent_min]);
    useEffect(() => setPercentMax(filters.percent_max ?? ''), [filters.percent_max]);

    // Debounce text/numeric inputs (300ms).
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const debounce = (partial: FilterPartial) => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => onChange({ ...partial, page: 1 }), 300);
    };
    useEffect(() => () => { if (debounceRef.current) clearTimeout(debounceRef.current); }, []);

    const immediate = (partial: FilterPartial) => onChange({ ...partial, page: 1 });

    const toggleStatus = (s: ProjectStatus) => {
        const current = filters.statuses ?? [];
        const next = current.includes(s) ? current.filter((x) => x !== s) : [...current, s];
        immediate({ statuses: next.length ? next : null });
    };

    const selectedClientId = filters.client_id ?? '';

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {/* Search */}
                <div className="sm:col-span-2 lg:col-span-3 xl:col-span-2">
                    <Label htmlFor="filter_search">{__('general.search')}</Label>
                    <Input
                        id="filter_search"
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            debounce({ search: e.target.value });
                        }}
                        placeholder={__('general.search_projects')}
                        className="mt-1 h-9"
                    />
                </div>

                {/* Client */}
                <div className="xl:col-span-2">
                    <Label>{__('general.client')}</Label>
                    <ClientAutocomplete
                        value={selectedClientId}
                        onChange={(val) => immediate({ client_id: val || null })}
                        searchEndpoint={route('admin.projects.search-clients')}
                        initialClient={null}
                        placeholder={__('general.select_client')}
                        className="mt-1"
                    />
                </div>

                {/* Owner */}
                <div>
                    <Label htmlFor="filter_owner">{__('general.owner')}</Label>
                    <select
                        id="filter_owner"
                        className={selectClass}
                        value={filters.owner_id ?? ''}
                        onChange={(e) => immediate({ owner_id: e.target.value || null })}
                        disabled={owners.length === 0}
                    >
                        <option value="">{owners.length === 0 ? __('general.no_owners') : __('general.all')}</option>
                        {owners.map((o) => (
                            <option key={o.id} value={o.id}>{o.name}</option>
                        ))}
                    </select>
                </div>

                {/* Status pills */}
                <div className="sm:col-span-2 lg:col-span-3 xl:col-span-2">
                    <Label>{__('general.status')}</Label>
                    <div className="mt-1 flex flex-wrap gap-2">
                        {PROJECT_STATUS_OPTIONS.map((s) => {
                            const on = (filters.statuses ?? []).includes(s);
                            return (
                                <button
                                    key={s}
                                    type="button"
                                    data-on={on}
                                    onClick={() => toggleStatus(s)}
                                    className={cn(
                                        'inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-medium capitalize text-slate-600 transition-colors hover:bg-slate-50',
                                        STATUS_PILL_STYLES[s] ?? '',
                                    )}
                                >
                                    {s.replace('_', ' ')}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Budget range */}
                <div className="sm:col-span-2 xl:col-span-2">
                    <Label>{__('general.budget_range')}</Label>
                    <div className="mt-1 grid grid-cols-2 gap-2">
                        <Input type="number" step="0.01" min="0" value={budgetMin} placeholder={__('general.min')}
                            onChange={(e) => { setBudgetMin(e.target.value); debounce({ budget_min: e.target.value }); }} className="h-9" />
                        <Input type="number" step="0.01" min="0" value={budgetMax} placeholder={__('general.max')}
                            onChange={(e) => { setBudgetMax(e.target.value); debounce({ budget_max: e.target.value }); }} className="h-9" />
                    </div>
                </div>

                {/* Percentage range */}
                <div>
                    <Label>{__('general.percentage_range')}</Label>
                    <div className="mt-1 grid grid-cols-2 gap-2">
                        <Input type="number" step="1" min="0" max="100" value={percentMin} placeholder={__('general.min')}
                            onChange={(e) => { setPercentMin(e.target.value); debounce({ percent_min: e.target.value }); }} className="h-9" />
                        <Input type="number" step="1" min="0" max="100" value={percentMax} placeholder={__('general.max')}
                            onChange={(e) => { setPercentMax(e.target.value); debounce({ percent_max: e.target.value }); }} className="h-9" />
                    </div>
                </div>

                {/* Start date range */}
                <div>
                    <Label>{__('general.date_range')} ({__('general.start')})</Label>
                    <div className="mt-1 grid grid-cols-2 gap-2">
                        <Input type="date" value={filters.start_from ?? ''}
                            onChange={(e) => immediate({ start_from: e.target.value || null })} className="h-9" />
                        <Input type="date" value={filters.start_to ?? ''}
                            onChange={(e) => immediate({ start_to: e.target.value || null })} className="h-9" />
                    </div>
                </div>

                {/* Created date range */}
                <div>
                    <Label>{__('general.date_range')} ({__('general.created')})</Label>
                    <div className="mt-1 grid grid-cols-2 gap-2">
                        <Input type="date" value={filters.created_from ?? ''}
                            onChange={(e) => immediate({ created_from: e.target.value || null })} className="h-9" />
                        <Input type="date" value={filters.created_to ?? ''}
                            onChange={(e) => immediate({ created_to: e.target.value || null })} className="h-9" />
                    </div>
                </div>

                {/* Has unpaid invoices */}
                <div className="flex items-end">
                    <label className="flex cursor-pointer items-center gap-2 text-sm">
                        <Checkbox
                            checked={filters.has_unpaid === '1'}
                            onCheckedChange={(c) => immediate({ has_unpaid: c ? '1' : null })}
                        />
                        {__('general.has_unpaid_invoices')}
                    </label>
                </div>

                {/* Sort + dir */}
                <div>
                    <Label htmlFor="filter_sort">{__('general.sort_by')}</Label>
                    <div className="mt-1 flex gap-2">
                        <select
                            id="filter_sort"
                            className={cn(selectClass, 'flex-1')}
                            value={sort}
                            onChange={(e) => immediate({ sort: e.target.value })}
                        >
                            {SORT_OPTIONS.map((o) => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </select>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="h-9 w-9 shrink-0"
                            onClick={() => immediate({ dir: dir === 'asc' ? 'desc' : 'asc' })}
                            aria-label={dir === 'asc' ? __('general.ascending') : __('general.descending')}
                            title={dir === 'asc' ? __('general.ascending') : __('general.descending')}
                        >
                            {dir === 'asc' ? <ArrowUp className="h-4 w-4" /> : <ArrowDown className="h-4 w-4" />}
                        </Button>
                    </div>
                </div>

                {/* Per page */}
                <div>
                    <Label htmlFor="filter_per_page">{__('general.per_page')}</Label>
                    <select
                        id="filter_per_page"
                        className={selectClass}
                        value={perPage}
                        onChange={(e) => immediate({ per_page: Number(e.target.value) })}
                    >
                        {perPageOptions.map((n) => (
                            <option key={n} value={n}>{n}</option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="mt-4 flex items-center justify-end">
                <Button type="button" variant="ghost" size="sm" onClick={onClear}>
                    <ArrowDownUp className="h-3.5 w-3.5" /> {__('general.clear_all')}
                </Button>
            </div>
        </div>
    );
}

export default ProjectFiltersPanel;
