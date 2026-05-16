import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import React from 'react';

export interface ColumnDef<T> {
    header: string;
    accessorKey?: keyof T;
    cell?: (item: T) => React.ReactNode;
}

interface PaginationProps {
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface DataTableProps<T> {
    columns: ColumnDef<T>[];
    data: T[];
    pagination?: PaginationProps;
}

export default function DataTable<T>({
    columns,
    data,
    pagination,
}: DataTableProps<T>) {
    return (
        <div className="space-y-4">
            <div className="rounded-md border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((col, index) => (
                                <TableHead key={index}>{col.header}</TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center"
                                >
                                    No results.
                                </TableCell>
                            </TableRow>
                        ) : (
                            data.map((item, rowIndex) => (
                                <TableRow key={rowIndex}>
                                    {columns.map((col, colIndex) => (
                                        <TableCell key={colIndex}>
                                            {col.cell
                                                ? col.cell(item)
                                                : col.accessorKey
                                                  ? String(
                                                        item[col.accessorKey],
                                                    )
                                                  : null}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Basic Pagination Stub */}
            {pagination && pagination.last_page > 1 && (
                <div className="flex items-center justify-end space-x-2 py-4">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!pagination.prev_page_url}
                        onClick={() =>
                            (window.location.href =
                                pagination.prev_page_url || '#')
                        }
                    >
                        Previous
                    </Button>
                    <div className="text-sm text-gray-500">
                        Page {pagination.current_page} of {pagination.last_page}
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!pagination.next_page_url}
                        onClick={() =>
                            (window.location.href =
                                pagination.next_page_url || '#')
                        }
                    >
                        Next
                    </Button>
                </div>
            )}
        </div>
    );
}
