import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Eye, Trash2, MessageCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Props {
    tickets: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: { search: string; status: string };
    statuses: string[];
}

const STATUS_STYLES: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    replied: 'bg-emerald-100 text-emerald-700',
    closed: 'bg-slate-200 text-slate-700',
};

export default function Index({ tickets, filters, statuses }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const applyFilters = (next?: { search?: string; status?: string }) => {
        const q = {
            search: next?.search ?? search,
            status: next?.status ?? status,
        };
        router.get(route('admin.guest-tickets.index'), q, { preserveState: true, replace: true });
    };

    const onDelete = (id: number) => {
        if (! confirm(__('general.are_you_sure') || 'Are you sure?')) return;
        router.delete(route('admin.guest-tickets.destroy', id));
    };

    return (
        <AdminSidebarLayout header={__('general.guest_tickets')}>
            <Head title={__('general.guest_tickets')} />
            <div className="bg-white rounded-xl shadow-sm border border-slate-200">
                <div className="flex flex-col md:flex-row md:items-center gap-3 p-4 border-b border-slate-100">
                    <form
                        onSubmit={(e) => { e.preventDefault(); applyFilters(); }}
                        className="flex-1 flex gap-2"
                    >
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={__('general.search_by_name_email_subject') || 'Search...'}
                            className="max-w-sm"
                        />
                        <Select value={status || 'all'} onValueChange={(v) => { const nv = v === 'all' ? '' : v; setStatus(nv); applyFilters({ status: nv }); }}>
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder={__('general.status_filter_all')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{__('general.status_filter_all')}</SelectItem>
                                {statuses.map((s) => (
                                    <SelectItem key={s} value={s}>{__(`general.status_${s}`)}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button type="submit" variant="outline">{__('general.search') || 'Search'}</Button>
                    </form>
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{__('general.date')}</TableHead>
                            <TableHead>{__('general.name')}</TableHead>
                            <TableHead>{__('general.email')}</TableHead>
                            <TableHead>{__('general.subject')}</TableHead>
                            <TableHead>{__('general.status')}</TableHead>
                            <TableHead>{__('general.last_message')}</TableHead>
                            <TableHead className="text-end">{__('general.actions')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tickets.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={7} className="text-center py-8 text-slate-500">
                                    {__('general.no_guest_tickets_found')}
                                </TableCell>
                            </TableRow>
                        ) : (
                            tickets.data.map((ticket: any) => (
                                <TableRow key={ticket.id}>
                                    <TableCell>{new Date(ticket.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell className="font-medium">{ticket.name}</TableCell>
                                    <TableCell>{ticket.email}</TableCell>
                                    <TableCell className="max-w-xs truncate">{ticket.subject ?? '—'}</TableCell>
                                    <TableCell>
                                        <span className={`px-2 py-1 rounded-full text-xs font-semibold ${STATUS_STYLES[ticket.status] ?? 'bg-slate-100 text-slate-700'}`}>
                                            {__(`general.status_${ticket.status}`)}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-xs text-slate-500">
                                        {ticket.last_message_at ? new Date(ticket.last_message_at).toLocaleString() : '—'}
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <div className="flex items-center justify-end gap-1">
                                            {ticket.mobile && (
                                                <a href={`https://wa.me/${String(ticket.mobile).replace(/\D/g, '')}`} target="_blank" rel="noreferrer">
                                                    <Button variant="ghost" size="sm" aria-label="WhatsApp">
                                                        <MessageCircle className="w-4 h-4 text-[#25D366]" />
                                                    </Button>
                                                </a>
                                            )}
                                            <Link href={route('admin.guest-tickets.show', ticket.id)}>
                                                <Button variant="ghost" size="sm" aria-label={__('general.view') || 'View'}>
                                                    <Eye className="w-4 h-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => onDelete(ticket.id)}
                                                aria-label={__('general.delete') || 'Delete'}
                                            >
                                                <Trash2 className="w-4 h-4 text-rose-600" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
                {tickets.last_page > 1 && (
                    <nav className="flex items-center justify-center gap-2 p-4 border-t border-slate-100" aria-label="Pagination">
                        {tickets.links?.map((l: any, i: number) => (
                            <Link
                                key={i}
                                href={l.url ?? '#'}
                                preserveState
                                className={`px-3 py-1 rounded text-sm ${l.active ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }}
                            />
                        ))}
                    </nav>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
