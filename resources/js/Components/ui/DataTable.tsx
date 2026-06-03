import React from 'react';
import { cn } from '@/lib/utils';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
} from 'lucide-react';
import { SearchInput } from './SearchInput';
import { SkeletonTable } from './SkeletonLoaders';
import { EmptyState } from './EmptyState';
import { Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

// ── Column format A: legacy {key, label, render?, sortable?}
export interface ColumnDefKey {
    key: string;
    label: string;
    sortable?: boolean;
    className?: string;
    render?: (row: any) => React.ReactNode;
}

// ── Column format B: TanStack-style {header, cell, accessorKey?}
export interface ColumnDefHeader {
    header: string;
    accessorKey?: string;
    sortable?: boolean;
    className?: string;
    cell?: (row: any) => React.ReactNode;
}

export type ColumnDef = ColumnDefKey | ColumnDefHeader;

/** Normalizes both column formats into a unified internal shape */
function normalizeColumn(col: ColumnDef): {
    key: string;
    label: string;
    sortable: boolean;
    className?: string;
    render: (row: any) => React.ReactNode;
} {
    if ('key' in col) {
        return {
            key: col.key,
            label: col.label,
            sortable: col.sortable ?? false,
            className: col.className,
            render: col.render ?? ((row) => row[col.key] ?? '—'),
        };
    }
    // ColumnDefHeader format
    const key = col.accessorKey ?? col.header.toLowerCase().replace(/\s+/g, '_');
    return {
        key,
        label: col.header,
        sortable: col.sortable ?? false,
        className: col.className,
        render: col.cell ?? ((row) => row[key] ?? '—'),
    };
}

export interface DataTableProps {
    columns?: ColumnDef[];
    data?: any[];
    pagination?: any;
    loading?: boolean;
    filters?: any;
    onSearch?: (search: string) => void;
    onSort?: (key: string, direction?: string) => void;
    onPageChange?: (page: string | number) => void;
    onPerPageChange?: (perPage: number) => void;
    emptyState?: React.ReactNode;
    emptyIcon?: React.ElementType;
    emptyTitle?: string;
    emptyDescription?: string;
    className?: string;
}

export function DataTable({
    columns = [],
    data = [],
    pagination,
    loading = false,
    filters,
    onSearch,
    onSort,
    onPageChange,
    onPerPageChange,
    emptyState,
    emptyIcon,
    emptyTitle = 'No results found',
    emptyDescription,
    className,
}: DataTableProps) {
    const normalized = columns.map(normalizeColumn);

    if (loading && !(data as any).length) {
        return <SkeletonTable cols={normalized.length || 4} rows={5} className="" />;
    }

    return (
        <div
            className={cn(
                'flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm',
                className,
            )}
        >
            {/* Top Bar: Search & Filters */}
            {(onSearch || filters?.extra) && (
                <div className="flex items-center justify-between gap-4 border-b border-slate-100 p-4">
                    {onSearch && (
                        <div className="w-full max-w-sm">
                            <SearchInput
                                value={filters?.search || ''}
                                onChange={onSearch}
                                placeholder={__('general.search')}
                                className=""
                            />
                        </div>
                    )}
                    {filters?.extra && (
                        <div className="flex items-center gap-2">
                            {filters.extra}
                        </div>
                    )}
                </div>
            )}

            {/* Table Area */}
            <div className="relative flex-1 overflow-x-auto">
                <table className="w-full border-collapse text-left font-sans text-[13px]">
                    <thead className="sticky top-0 z-10 border-b border-slate-200 bg-slate-50">
                        <tr>
                            {normalized.map((col, idx) => (
                                <th
                                    key={idx}
                                    className={cn(
                                        'px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap',
                                        col.sortable
                                            ? 'cursor-pointer select-none transition-colors hover:text-slate-900'
                                            : '',
                                        col.className,
                                    )}
                                    onClick={() =>
                                        col.sortable && onSort?.(col.key)
                                    }
                                >
                                    <div className="flex items-center gap-1">
                                        {col.label}
                                        {col.sortable &&
                                            filters?.sort === col.key &&
                                            (filters?.dir === 'asc' ? (
                                                <ChevronUp className="h-3.5 w-3.5 text-indigo-600" />
                                            ) : (
                                                <ChevronDown className="h-3.5 w-3.5 text-indigo-600" />
                                            ))}
                                    </div>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-slate-100">
                        {(data as any).length > 0 ? (
                            (data as any).map((row, rowIndex) => (
                                <tr
                                    key={row.id ?? rowIndex}
                                    className="transition-colors duration-100 hover:bg-slate-50/70"
                                >
                                    {normalized.map((col, colIndex) => (
                                        <td
                                            key={colIndex}
                                            className={cn(
                                                'px-4 py-3 text-slate-700',
                                                col.className,
                                            )}
                                        >
                                            {col.render(row)}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={normalized.length || 1}
                                    className="p-0"
                                >
                                    {emptyState || (
                                        <EmptyState
                                            icon={emptyIcon}
                                            title={emptyTitle}
                                            description={emptyDescription}
                                            className="rounded-none border-0 bg-white py-12"
                                        />
                                    )}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination Bar */}
            {pagination && pagination.total > 0 && (
                <div className="flex items-center justify-between gap-4 border-t border-slate-200 bg-white px-4 py-3 text-[13px] text-slate-600">
                    <div className="flex items-center gap-2">
                        <span className="text-slate-400 text-xs">Show</span>
                        <select
                            className="h-7 rounded-md border border-slate-200 bg-white pl-2 pr-6 text-[12px] focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            value={pagination.per_page}
                            onChange={(e) =>
                                onPerPageChange?.(Number(e.target.value))
                            }
                        >
                            {[10, 25, 50, 100].map((val) => (
                                <option key={val} value={val}>
                                    {val}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex-1 text-center text-slate-400 text-xs">
                        {pagination.from || 0}–{pagination.to || 0} of{' '}
                        <span className="font-medium text-slate-700">{pagination.total}</span>
                    </div>

                    <div className="flex items-center gap-1">
                        {pagination.links ? (
                            <div className="flex items-center flex-wrap gap-1">
                                {pagination.links.map((link: any, idx: number) => (
                                    link.url ? (
                                        <Link
                                            key={idx}
                                            href={link.url}
                                            className={cn(
                                                'rounded-md px-2.5 py-1 text-[12px] transition-colors min-w-[28px] text-center',
                                                link.active
                                                    ? 'bg-slate-900 font-medium text-white shadow-sm'
                                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                            )}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            key={idx}
                                            className="rounded-md px-2.5 py-1 text-[12px] text-slate-300 cursor-not-allowed min-w-[28px] text-center"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )
                                ))}
                            </div>
                        ) : (
                            <>
                                <button
                                    onClick={() =>
                                        onPageChange?.(pagination.current_page - 1)
                                    }
                                    disabled={pagination.current_page === 1}
                                    className="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </button>

                                <div className="flex items-center gap-1 px-2">
                                    <span className="flex h-7 w-7 items-center justify-center rounded-md bg-slate-900 font-medium text-white text-[12px]">
                                        {pagination.current_page}
                                    </span>
                                </div>

                                <button
                                    onClick={() =>
                                        onPageChange?.(pagination.current_page + 1)
                                    }
                                    disabled={
                                        pagination.current_page === pagination.last_page
                                    }
                                    className="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
