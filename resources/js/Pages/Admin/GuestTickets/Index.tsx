import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Eye } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Index({ tickets }: { tickets: any }) {
    return (
        <AdminSidebarLayout header="Guest Tickets">
            <Head title={__('general.guest_tickets')} />
            <div className="bg-white rounded-xl shadow-sm border border-slate-200">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{__('general.date')}</TableHead>
                            <TableHead>{__('general.name')}</TableHead>
                            <TableHead>{__('general.email')}</TableHead>
                            <TableHead>{__('general.status')}</TableHead>
                            <TableHead className="text-end">{__('general.actions')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tickets.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center py-8 text-slate-500">{__('general.no_guest_tickets_found')}</TableCell>
                            </TableRow>
                        ) : (
                            tickets.data.map((ticket: any) => (
                                <TableRow key={ticket.id}>
                                    <TableCell>{new Date(ticket.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell className="font-medium">{ticket.name}</TableCell>
                                    <TableCell>{ticket.email}</TableCell>
                                    <TableCell>
                                        <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                                            ticket.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'
                                        }`}>
                                            {ticket.status}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <Link href={route('admin.guest-tickets.show', ticket.id)}>
                                            <Button variant="ghost" size="sm"><Eye className="w-4 h-4" /></Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </AdminSidebarLayout>
    );
}
