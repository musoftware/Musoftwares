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
            return new Intl.DateTimeFormat('ar-EG', {
                timeZone: 'Africa/Cairo',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            }).format(new Date(dateString));
        } catch (e) {
            return dateString;
        }
    };

    return (
        <AdminSidebarLayout header="تقرير الإيميلات الصادرة (Outgoing Emails)">
            <Head title="تقرير الإيميلات الصادرة" />

            <div className="space-y-6">
                {/* Summary Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">إجمالي الصادر</CardTitle>
                            <Mail className="h-5 w-5 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{stats.total_sent.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">إجمالي الإيميلات في النظام</p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">صادرة اليوم</CardTitle>
                            <Calendar className="h-5 w-5 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600">{stats.sent_today.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">توقيت القاهرة (Africa/Cairo)</p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">صادرة هذا الشهر</CardTitle>
                            <CheckCircle2 className="h-5 w-5 text-indigo-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-indigo-600">{stats.sent_this_month.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">خلال الشهر الحالي</p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-600">إيميلات فاشلة</CardTitle>
                            <AlertCircle className="h-5 w-5 text-rose-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-rose-600">{stats.failed_count.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 mt-1">تحتاج مراجعة أو تصحيح</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Filter & Table Card */}
                <Card className="bg-white border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <CardTitle className="text-lg font-semibold text-slate-800">سجل الرسائل الصادرة</CardTitle>
                                <p className="text-sm text-slate-500">عرض جميع الإيميلات التي تم إرسالها تلقائياً عبر النظام</p>
                            </div>
                            <Button variant="outline" size="sm" onClick={resetFilters} className="self-start md:self-auto gap-2">
                                <RefreshCw className="h-4 w-4" />
                                إعادة ضبط الفلترة
                            </Button>
                        </div>

                        {/* Filters Bar */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="بحث بالمستلم أو الموضوع..."
                                    className="pl-9"
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
                                    <SelectValue placeholder="الحالة" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">جميع الحالات</SelectItem>
                                    <SelectItem value="sent">ناجحة (Sent)</SelectItem>
                                    <SelectItem value="failed">فاشلة (Failed)</SelectItem>
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={fromDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                placeholder="من تاريخ"
                            />

                            <div className="flex gap-2">
                                <Input
                                    type="date"
                                    value={toDate}
                                    onChange={(e) => setToDate(e.target.value)}
                                    placeholder="إلى تاريخ"
                                />
                                <Button onClick={() => applyFilters()} className="bg-slate-900 text-white hover:bg-slate-800 shrink-0">
                                    تطبيق
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
                                        <TableHead>المستلم (To Email)</TableHead>
                                        <TableHead>الموضوع (Subject)</TableHead>
                                        <TableHead>نوع الرسالة / Class</TableHead>
                                        <TableHead className="text-center">الحالة</TableHead>
                                        <TableHead className="text-right">وقت الإرسال (القاهرة)</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {emails.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-8 text-slate-500">
                                                لا توجد إيميلات صادرة مطابقة لشروط البحث
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        emails.data.map((item) => (
                                            <TableRow key={item.id} className="hover:bg-slate-50/80">
                                                <TableCell className="font-mono text-xs text-slate-500">{item.id}</TableCell>
                                                <TableCell className="font-medium text-slate-900">{item.to_email}</TableCell>
                                                <TableCell className="text-slate-700 max-w-xs truncate" title={item.subject || ''}>
                                                    {item.subject || <span className="text-slate-400 font-italic">(بدون عنوان)</span>}
                                                </TableCell>
                                                <TableCell className="text-slate-600">
                                                    <Badge variant="outline" className="bg-slate-100 font-mono text-xs text-slate-700">
                                                        {item.mail_class || 'Mail'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {item.status === 'sent' ? (
                                                        <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100 border-none font-medium">
                                                            تم الإرسال
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-rose-100 text-rose-800 hover:bg-rose-100 border-none font-medium" title={item.error_message || ''}>
                                                            فشل الإرسال
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right text-xs text-slate-600 font-mono">
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
                                    عرض {emails.data.length} من إجمالي {emails.total} سجل
                                </div>
                                <div className="flex gap-1">
                                    {emails.links.map((link, idx) => (
                                        <Button
                                            key={idx}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true, replace: true })}
                                            className={`h-8 px-3 text-xs ${link.active ? 'bg-slate-900 text-white' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
