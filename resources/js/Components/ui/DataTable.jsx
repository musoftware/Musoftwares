import { cn } from '@/lib/utils';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
} from 'lucide-react';
import { SearchInput } from './SearchInput';
import { SkeletonTable } from './SkeletonLoaders';

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
}) {
    if (loading && !data.length) {
        return <SkeletonTable cols={columns.length} rows={5} />;
    }

    return (
        <div
            className={cn(
                'bg-surface border-border flex flex-col overflow-hidden rounded-xl border shadow-sm',
                className,
            )}
        >
            {/* Top Bar: Search & Filters */}
            {(onSearch || filters) && (
                <div className="border-border flex items-center justify-between gap-4 border-b p-4">
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
                    <thead className="bg-surface-raised border-border sticky top-0 z-10 border-b">
                        <tr>
                            {columns.map((col, idx) => (
                                <th
                                    key={idx}
                                    className={cn(
                                        'text-text-muted px-4 py-3 text-[12px] font-medium whitespace-nowrap uppercase',
                                        col.sortable
                                            ? 'hover:text-text-primary cursor-pointer transition-colors select-none'
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
                                                <ChevronUp className="text-primary h-3.5 w-3.5" />
                                            ) : (
                                                <ChevronDown className="text-primary h-3.5 w-3.5" />
                                            ))}
                                    </div>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="bg-surface">
                        {data.length > 0 ? (
                            data.map((row, rowIndex) => (
                                <tr
                                    key={rowIndex}
                                    className="border-border hover:bg-surface-raised border-b transition-colors duration-150 last:border-0"
                                >
                                    {columns.map((col, colIndex) => (
                                        <td
                                            key={colIndex}
                                            className={cn(
                                                'px-4 py-3.5',
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
                                        <div className="text-text-muted">
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
                <div className="border-border bg-surface text-text-secondary flex items-center justify-between gap-4 border-t px-4 py-3 text-[13px]">
                    <div className="flex items-center gap-2">
                        <span>Show</span>
                        <select
                            className="border-border bg-surface focus:ring-primary focus:border-primary h-8 rounded border pr-6 pl-2 text-[13px] focus:ring-1"
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

                    <div className="flex-1 text-center">
                        Showing {pagination.from || 0}–{pagination.to || 0} of{' '}
                        {pagination.total} results
                    </div>

                    <div className="flex items-center gap-1">
                        <button
                            onClick={() =>
                                onPageChange?.(pagination.current_page - 1)
                            }
                            disabled={pagination.current_page === 1}
                            className="text-text-secondary hover:bg-surface-raised hover:text-text-primary rounded p-1.5 transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </button>

                        {/* Simple page numbers approach for brevity */}
                        <div className="flex items-center gap-1 px-2">
                            <span className="bg-primary flex h-7 w-7 items-center justify-center rounded font-medium text-white">
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
                            className="text-text-secondary hover:bg-surface-raised hover:text-text-primary rounded p-1.5 transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
