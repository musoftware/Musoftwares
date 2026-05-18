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
import { Link } from '@inertiajs/react';

export interface ColumnDef {
    key: string;
    label: string;
    sortable?: boolean;
    render?: (row: any) => React.ReactNode;
}

export interface DataTableProps {
    columns?: ColumnDef[];
    data?: any[];
    pagination?: any;
    loading?: boolean;
    filters?: any;
    onSearch?: (search: string) => void;
    onSort?: (key: string, direction: string) => void;
    onPageChange?: (url: string) => void;
    onPerPageChange?: (perPage: number) => void;
    emptyState?: React.ReactNode;
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
    className,
}: DataTableProps) {
    if (loading && !data.length) {
        return <SkeletonTable cols={columns.length} rows={5} />;
    }

    return (
        <div
            className={cn(
                'flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm',
                className,
            )}
        >
            {/* Top Bar: Search & Filters */}
            {(onSearch || filters) && (
                <div className="flex items-center justify-between gap-4 border-b border-slate-100 p-4">
                    {onSearch && (
                        <div className="w-full max-w-sm">
                            <SearchInput
                                value={filters?.search || ''}
                                onChange={onSearch}
                                placeholder="Search..."
                            />
                        </div>
                    )}
                    {filters && (
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
                            {columns.map((col, idx) => (
                                <th
                                    key={idx}
                                    className={cn(
                                        'px-4 py-3 text-[12px] font-medium uppercase text-slate-500 whitespace-nowrap',
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
                    <tbody className="bg-white">
                        {data.length > 0 ? (
                            data.map((row, rowIndex) => (
                                <tr
                                    key={rowIndex}
                                    className="border-b border-slate-100 transition-colors duration-150 hover:bg-slate-50/50 last:border-0"
                                >
                                    {columns.map((col, colIndex) => (
                                        <td
                                            key={colIndex}
                                            className={cn(
                                                'px-4 py-3.5 text-slate-700',
                                                col.className,
                                            )}
                                        >
                                            {col.render
                                                ? col.render(row)
                                                : row[col.key]}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-4 py-16 text-center"
                                >
                                    {emptyState || (
                                        <div className="text-slate-500">
                                            No data available
                                        </div>
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
                        <span>Show</span>
                        <select
                            className="h-8 rounded-lg border border-slate-200 bg-white pl-2 pr-6 text-[13px] focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
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

                    <div className="flex-1 text-center text-slate-500">
                        Showing {pagination.from || 0}–{pagination.to || 0} of{' '}
                        <span className="font-medium text-slate-700">{pagination.total}</span> results
                    </div>

                    <div className="flex items-center gap-1">
                        {pagination.links ? (
                            <div className="flex items-center flex-wrap gap-1">
                                {pagination.links.map((link, idx) => (
                                    link.url ? (
                                        <Link
                                            key={idx}
                                            href={link.url}
                                            className={cn(
                                                "rounded-lg px-3 py-1.5 text-[13px] transition-colors",
                                                link.active 
                                                    ? "bg-indigo-600 font-medium text-white shadow-sm" 
                                                    : "text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                                            )}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span 
                                            key={idx} 
                                            className="rounded-lg px-3 py-1.5 text-[13px] text-slate-400 opacity-50 cursor-not-allowed"
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
                                    className="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </button>

                                <div className="flex items-center gap-1 px-2">
                                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 font-medium text-white">
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
                                    className="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
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

