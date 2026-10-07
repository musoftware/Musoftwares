import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Mail, CheckCircle2, AlertCircle, Calendar, Search, RefreshCw } from 'lucide-react';
import Pagination from '@/Components/Pagination';
import { __, getLoadedLocale } from '@/lib/i18n';

interface EmailRecord {
    id: number;
    to_email: string;
    subject: string | null;
    mail_class: string | null;
    status: 'sent' | 'failed';
    error_message: string | null;
    sent_at: string;
}

interface Props {
    emails: {
        data: EmailRecord[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: {
        total_sent: number;
        sent_today: number;
        sent_this_month: number;
        failed_count: number;
    };
    filters: {
        search: string;
        status: string;
        from_date: string;
        to_date: string;
    };
}

export default function Index({ emails, stats, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [fromDate, setFromDate] = useState(filters.from_date || '');
    const [toDate, setToDate] = useState(filters.to_date || '');

    const applyFilters = (overrideFilters?: Partial<typeof filters>) => {
        const queryParams = {
            search: overrideFilters?.search !== undefined ? overrideFilters.search : search,
            status: overrideFilters?.status !== undefined ? (overrideFilters.status === 'all' ? '' : overrideFilters.status) : (status === 'all' ? '' : status),
            from_date: overrideFilters?.from_date !== undefined ? overrideFilters.from_date : fromDate,
            to_date: overrideFilters?.to_date !== undefined ? overrideFilters.to_date : toDate,
        };

        router.get('/admin/outgoing-emails', queryParams, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('all');
        setFromDate('');
        setToDate('');
        router.get('/admin/outgoing-emails', {}, { preserveState: true, replace: true });
    };

    const formatDateCairo = (dateString: string) => {
        if (!dateString) return '-';
        try {
            return new Intl.DateTimeFormat(getLoadedLocale() ?? 'en', {
                timeZone: 'Africa/Cairo',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            }).format(new Date(dateString));
        } catch {
            return dateString;
        }
    };

    return (
        <AdminSidebarLayout header={__('admin.outgoing_emails_header')}>
            <Head title={__('admin.outgoing_emails_header')} />

            <div className="space-y-6">
                {/* Summary Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">{__('admin.outgoing_emails_total_sent')}</CardTitle>
                            <Mail className="h-5 w-5 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{stats.total_sent.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">{__('admin.outgoing_emails_total_sent_hint')}</p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">{__('admin.outgoing_emails_sent_today')}</CardTitle>
                            <Calendar className="h-5 w-5 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600">{stats.sent_today.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">{__('admin.outgoing_emails_sent_today_hint')}</p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">{__('admin.outgoing_emails_sent_this_month')}</CardTitle>
                            <CheckCircle2 className="h-5 w-5 text-indigo-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-indigo-600">{stats.sent_this_month.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">{__('admin.outgoing_emails_sent_this_month_hint')}</p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">{__('admin.outgoing_emails_failed_count')}</CardTitle>
                            <AlertCircle className="h-5 w-5 text-rose-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-rose-600">{stats.failed_count.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">{__('admin.outgoing_emails_failed_count_hint')}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Filter & Table Card */}
                <Card className="bg-white border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <CardTitle className="text-lg font-semibold text-slate-800">{__('admin.outgoing_emails_log_title')}</CardTitle>
                                <p className="text-sm text-slate-500">{__('admin.outgoing_emails_log_description')}</p>
                            </div>
                            <Button variant="outline" size="sm" onClick={resetFilters} className="self-start md:self-auto gap-2">
                                <RefreshCw className="h-4 w-4" />
                                {__('general.reset_filters')}
                            </Button>
                        </div>

                        {/* Filters Bar */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
                            <div className="relative">
                                <Search className="absolute start-3 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder={__('admin.outgoing_emails_search_placeholder')}
                                    className="ps-9"
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                />
                            </div>

                            <Select
                                value={status}
                                onValueChange={(val) => {
                                    const nextStatus = val || 'all';
                                    setStatus(nextStatus);
                                    applyFilters({ status: nextStatus });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={__('general.status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{__('admin.outgoing_emails_all_statuses')}</SelectItem>
                                    <SelectItem value="sent">{__('general.status_sent')}</SelectItem>
                                    <SelectItem value="failed">{__('general.status_failed')}</SelectItem>
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={fromDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                placeholder={__('admin.outgoing_emails_from_date')}
                                aria-label={__('admin.outgoing_emails_from_date')}
                            />

                            <div className="flex gap-2">
                                <Input
                                    type="date"
                                    value={toDate}
                                    onChange={(e) => setToDate(e.target.value)}
                                    placeholder={__('admin.outgoing_emails_to_date')}
                                    aria-label={__('admin.outgoing_emails_to_date')}
                                />
                                <Button onClick={() => applyFilters()} className="bg-slate-900 text-white hover:bg-slate-800 shrink-0">
                                    {__('general.apply')}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="w-16">#</TableHead>
                                        <TableHead>{__('general.recipient')}</TableHead>
                                        <TableHead>{__('admin.outgoing_emails_subject')}</TableHead>
                                        <TableHead>{__('admin.outgoing_emails_mail_class')}</TableHead>
                                        <TableHead className="text-center">{__('general.status')}</TableHead>
                                        <TableHead className="text-end">{__('admin.outgoing_emails_sent_at')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {emails.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-8 text-slate-500">
                                                {__('admin.outgoing_emails_no_results')}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        emails.data.map((item) => (
                                            <TableRow key={item.id} className="hover:bg-slate-50/80">
                                                <TableCell className="font-mono text-xs text-slate-500">{item.id}</TableCell>
                                                <TableCell className="font-medium text-slate-900">{item.to_email}</TableCell>
                                                <TableCell className="text-slate-700 max-w-xs truncate" title={item.subject || ''}>
                                                    {item.subject || <span className="text-slate-400 italic">{__('admin.outgoing_emails_no_subject')}</span>}
                                                </TableCell>
                                                <TableCell className="text-slate-600">
                                                    <Badge variant="outline" className="bg-slate-100 font-mono text-xs text-slate-700">
                                                        {item.mail_class || 'Mail'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {item.status === 'sent' ? (
                                                        <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100 border-none font-medium">
                                                            {__('admin.outgoing_emails_status_sent')}
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-rose-100 text-rose-800 hover:bg-rose-100 border-none font-medium" title={item.error_message || ''}>
                                                            {__('admin.outgoing_emails_status_failed')}
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-end text-xs text-slate-600 font-mono">
                                                    {formatDateCairo(item.sent_at)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination Links */}
                        {emails.links && emails.links.length > 3 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                                <div className="text-xs text-slate-500">
                                    {__('admin.outgoing_emails_showing', { shown: emails.data.length, total: emails.total })}
                                </div>
                                <Pagination links={emails.links} />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
