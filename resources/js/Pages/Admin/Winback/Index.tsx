import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { 
    Users, Send, Clock, CheckCircle2, AlertCircle, 
    MessageSquare, Eye, RefreshCw, Search, ExternalLink, 
    MailCheck, ShieldAlert, UserX, Tag
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { 
    Dialog, DialogContent, DialogHeader, DialogTitle, 
    DialogDescription, DialogFooter 
} from '@/Components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { __ } from '@/lib/i18n';

interface ClientItem {
    id: number;
    name: string;
    email: string;
    phone?: string;
    whatsapp_number?: string;
    avatar_url?: string;
    inactivity_days: number;
    last_activity_at?: string;
    status: string;
    status_label: string;
    next_reminder?: number;
    reminders_sent_count: number;
    last_sent_at?: string;
    can_send_now: boolean;
    winback_unsubscribed_at?: string;
    has_open_ticket: boolean;
    past_invoices_count: number;
    past_projects_count: number;
}

interface RecentLog {
    id: number;
    user_name: string;
    user_email: string;
    reminder_number: number;
    status?: string;
    coupon_code: string;
    sent_at: string;
    sent_at_exact: string;
    recovered_at?: string;
}

interface Props {
    clients: {
        data: ClientItem[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: {
        total_inactive_clients: number;
        eligible_now_count: number;
        waiting_cadence_count: number;
        completed_count: number;
        recovered_count: number;
        unsubscribed_count: number;
        total_sent_reminders: number;
        reminders_by_stage: Record<number, number>;
    };
    recentLogs: RecentLog[];
    coupon: {
        code: string;
        discount_percentage: number;
        is_active: boolean;
    };
    whatsappPhone: string;
    filters: {
        search: string;
        status: string;
    };
}

export default function WinbackIndex({ clients, stats, recentLogs, coupon, whatsappPhone, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [isRunning, setIsRunning] = useState(false);
    const [sendingUserId, setSendingUserId] = useState<number | null>(null);

    // Preview Modal state
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewReminderNumber, setPreviewReminderNumber] = useState(1);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/winback', { search, status: statusFilter }, { preserveState: true });
    };

    const handleStatusTab = (tabKey: string) => {
        setStatusFilter(tabKey);
        router.get('/admin/winback', { search, status: tabKey }, { preserveState: true });
    };

    const handleRunAll = () => {
        if (!confirm('هل أنت متأكد من رغبتك في تشغيل محرك الحملة الآن وإرسال الإيميلات لجميع المؤهلين؟')) {
            return;
        }

        setIsRunning(true);
        router.post('/admin/winback/run', {}, {
            onFinish: () => setIsRunning(false),
        });
    };

    const handleSendToUser = (user: ClientItem) => {
        if (!confirm(`هل تريد إرسال التذكير رقم (${user.next_reminder || 1}) للعميل ${user.name} الآن؟`)) {
            return;
        }

        setSendingUserId(user.id);
        router.post(`/admin/winback/users/${user.id}/send`, {}, {
            onFinish: () => setSendingUserId(null),
        });
    };

    const getStatusBadge = (client: ClientItem) => {
        switch (client.status) {
            case 'eligible_now':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        <Send className="w-3 h-3" />
                        {client.status_label}
                    </span>
                );
            case 'recovered':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                        <CheckCircle2 className="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                        {client.status_label}
                    </span>
                );
            case 'waiting_cadence':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                        <Clock className="w-3 h-3" />
                        {client.status_label}
                    </span>
                );
            case 'completed':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-300 dark:border-zinc-700">
                        <CheckCircle2 className="w-3 h-3" />
                        {client.status_label}
                    </span>
                );
            case 'unsubscribed':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                        <UserX className="w-3 h-3" />
                        {client.status_label}
                    </span>
                );
            case 'open_ticket':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                        <ShieldAlert className="w-3 h-3" />
                        {client.status_label}
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                        {client.status_label}
                    </span>
                );
        }
    };

    return (
        <AdminSidebarLayout
            title="حملات استعادة العملاء | Musoftwares"
            header="حملات استعادة العملاء (Software Winback)"
            actions={
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setPreviewOpen(true)}
                        className="gap-2 text-xs h-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                    >
                        <Eye className="w-4 h-4" />
                        معاينة الإيميل
                    </Button>
                    <Button
                        size="sm"
                        onClick={handleRunAll}
                        disabled={isRunning || stats.eligible_now_count === 0}
                        className="gap-2 text-xs h-9 bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                    >
                        <RefreshCw className={`w-3.5 h-3.5 ${isRunning ? 'animate-spin' : ''}`} />
                        تشغيل الحملة فوراً ({stats.eligible_now_count})
                    </Button>
                </div>
            }
        >
            <Head title="حملات استعادة العملاء — لوحة التحكم" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full">
                
                {/* Information Notice Bar */}
                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                            <Tag className="w-5 h-5 text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h3 className="text-sm font-semibold text-zinc-900 dark:text-white">
                                    كود الخصم النشط: <span className="font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded border border-zinc-300 dark:border-zinc-700">{coupon.code}</span>
                                </h3>
                                <span className="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-medium">
                                    خصم {coupon.discount_percentage}%
                                </span>
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                تذكير للعملاء المنقطعين لأكثر من 6 شهور، تذكير دوري كل 3 أشهر بحد أقصى 4 مرات مع رابط واتساب مباشر (+{whatsappPhone}).
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 self-end sm:self-center">
                        <span className="text-xs text-zinc-500 dark:text-zinc-400">
                            مجدولة يومياً 09:30 ص
                        </span>
                    </div>
                </div>

                {/* KPI Metrics Grid */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs text-zinc-500 dark:text-zinc-400 flex items-center justify-between">
                                إجمالي العملاء المنقطعين
                                <Users className="w-4 h-4 text-zinc-400" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-zinc-900 dark:text-white">
                                {stats.total_inactive_clients}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-zinc-500">
                                لديهم فواتير أو مشاريع سابقة (&gt;180 يوم)
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs text-zinc-500 dark:text-zinc-400 flex items-center justify-between">
                                مؤهل للإرسال الآن
                                <Send className="w-4 h-4 text-emerald-600" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {stats.eligible_now_count}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-zinc-500">
                                جاهز للإرسال الفوري أو عبر الجدولة
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs text-zinc-500 dark:text-zinc-400 flex items-center justify-between">
                                بانتظار دورة الـ 90 يوم
                                <Clock className="w-4 h-4 text-zinc-400" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-zinc-900 dark:text-white">
                                {stats.waiting_cadence_count}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-zinc-500">
                                استلموا تذكيرات سابقة وقيد الانتظار
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs text-zinc-500 dark:text-zinc-400 flex items-center justify-between">
                                إجمالي الرسائل المرسلة
                                <MailCheck className="w-4 h-4 text-zinc-400" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-zinc-900 dark:text-white">
                                {stats.total_sent_reminders}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2 text-xs text-zinc-500 font-mono">
                                <span>ت1: {stats.reminders_by_stage[1] || 0}</span>
                                <span>•</span>
                                <span>ت2: {stats.reminders_by_stage[2] || 0}</span>
                                <span>•</span>
                                <span>ت3: {stats.reminders_by_stage[3] || 0}</span>
                                <span>•</span>
                                <span>ت4: {stats.reminders_by_stage[4] || 0}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter Tabs & Search Controls */}
                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 space-y-4 shadow-sm">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        {/* Status Tabs */}
                        <div className="flex flex-wrap items-center gap-1.5">
                            {[
                                { key: 'all', label: 'الكل', count: stats.total_inactive_clients },
                                { key: 'eligible_now', label: 'مؤهل الآن', count: stats.eligible_now_count },
                                { key: 'waiting_cadence', label: 'بانتظار الـ 90 يوم', count: stats.waiting_cadence_count },
                                { key: 'recovered', label: 'تمت استعادتهم', count: stats.recovered_count || 0 },
                                { key: 'completed', label: 'مكتمل (4 تذكيرات)', count: stats.completed_count },
                                { key: 'unsubscribed', label: 'ملغي الاشتراك', count: stats.unsubscribed_count },
                            ].map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => handleStatusTab(tab.key)}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
                                        statusFilter === tab.key
                                            ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                                            : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700'
                                    }`}
                                >
                                    {tab.label}
                                    <span className="ms-1.5 opacity-75 font-mono">({tab.count})</span>
                                </button>
                            ))}
                        </div>

                        {/* Search Form */}
                        <form onSubmit={handleSearch} className="flex items-center gap-2 max-w-sm w-full">
                            <div className="relative flex-1">
                                <Search className="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="بحث بالاسم أو البريد أو الهاتف..."
                                    className="ps-9 h-9 text-xs bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700"
                                />
                            </div>
                            <Button type="submit" size="sm" variant="outline" className="h-9 text-xs">
                                بحث
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Inactive Clients Table */}
                <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <CardHeader className="border-b border-zinc-100 dark:border-zinc-800 pb-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-semibold text-zinc-900 dark:text-white">
                                    قائمة العملاء المنقطعين
                                </CardTitle>
                                <CardDescription className="text-xs text-zinc-500">
                                    إجمالي {clients.total} عميل يطابق معايير الفلترة الحالية
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="text-xs">العميل</TableHead>
                                    <TableHead className="text-xs">مدة الانقطاع</TableHead>
                                    <TableHead className="text-xs">السجل السابق</TableHead>
                                    <TableHead className="text-xs">حالة الحملة</TableHead>
                                    <TableHead className="text-xs">التذكيرات المرسلة</TableHead>
                                    <TableHead className="text-xs text-end">إجراءات</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {clients.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-12 text-sm text-zinc-500">
                                            لا يوجد عملاء منقطعين يطابقون شروط البحث الحالية.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    clients.data.map((client) => {
                                        const whatsappUrl = `https://wa.me/${(client.whatsapp_number || '').replace(/[^0-9]/g, '')}?text=${encodeURIComponent(
                                            `أهلاً يا ${client.name}، حابب أشارك معاك كود خصم الـ 50% (${coupon.code}) على أي مشروع برمجي جديد من Musoftwares.`
                                        )}`;

                                        return (
                                            <TableRow key={client.id} className="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40">
                                                <TableCell>
                                                    <div className="flex items-center gap-3">
                                                        <Avatar className="h-9 w-9 border border-zinc-200 dark:border-zinc-700">
                                                            <AvatarImage src={client.avatar_url} />
                                                            <AvatarFallback className="text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200">
                                                                {client.name.substring(0, 2).toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <div className="font-medium text-xs text-zinc-900 dark:text-white flex items-center gap-1.5">
                                                                {client.name}
                                                                <Link
                                                                    href={`/admin/users?search=${encodeURIComponent(client.email)}`}
                                                                    className="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                                                                    title="عرض ملف المستخدم"
                                                                >
                                                                    <ExternalLink className="w-3 h-3" />
                                                                </Link>
                                                            </div>
                                                            <div className="text-xs text-zinc-500 font-mono">
                                                                {client.email}
                                                            </div>
                                                            {client.phone && (
                                                                <div className="text-xs text-zinc-400 font-mono">
                                                                    {client.phone}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs font-medium text-zinc-900 dark:text-zinc-200">
                                                        {Math.floor(client.inactivity_days / 30)} شهر ({client.inactivity_days} يوم)
                                                    </div>
                                                    <div className="text-xs text-zinc-400">
                                                        {client.last_activity_at ? `آخر نشاط: ${client.last_activity_at}` : 'لا يوجد نشاط مسجل'}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs text-zinc-700 dark:text-zinc-300">
                                                        <span>{client.past_invoices_count} فواتير مدفوعة</span>
                                                        <span className="mx-1">•</span>
                                                        <span>{client.past_projects_count} مشاريع</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {getStatusBadge(client)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs font-mono text-zinc-800 dark:text-zinc-200">
                                                        {client.reminders_sent_count} / 4 تذكيرات
                                                    </div>
                                                    {client.last_sent_at && (
                                                        <div className="text-xs text-zinc-400">
                                                            آخر إرسال: {client.last_sent_at}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-end">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {client.whatsapp_number && (
                                                            <a
                                                                href={whatsappUrl}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-zinc-200 dark:border-zinc-700 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors"
                                                                title="مراسلة واتساب مباشرة"
                                                            >
                                                                <MessageSquare className="w-4 h-4" />
                                                            </a>
                                                        )}
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            disabled={!client.can_send_now || sendingUserId === client.id}
                                                            onClick={() => handleSendToUser(client)}
                                                            className="h-8 text-xs bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700"
                                                        >
                                                            {sendingUserId === client.id ? (
                                                                <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                                                            ) : (
                                                                <>
                                                                    <Send className="w-3 h-3 me-1.5" />
                                                                    إرسال تذكير ({client.next_reminder || 1})
                                                                </>
                                                            )}
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {clients.last_page > 1 && (
                        <div className="p-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                            <div>
                                صفحة {clients.current_page} من {clients.last_page}
                            </div>
                            <div className="flex items-center gap-1">
                                {clients.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 rounded text-xs transition-colors ${
                                            link.active
                                                ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                                                : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                        } ${!link.url ? 'opacity-40 cursor-not-allowed' : ''}`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>

                {/* Recent Activity Log Section */}
                <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <CardHeader className="pb-3 border-b border-zinc-100 dark:border-zinc-800">
                        <CardTitle className="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                            <Clock className="w-4 h-4 text-zinc-400" />
                            سجل الإرسال الأخير
                        </CardTitle>
                        <CardDescription className="text-xs text-zinc-500">
                            آخر 10 تذكيرات تم إرسالها بنجاح للعملاء
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-4">
                        {recentLogs.length === 0 ? (
                            <p className="text-xs text-zinc-500 py-4 text-center">
                                لم يتم إرسال أي تذكيرات بعد.
                            </p>
                        ) : (
                            <div className="divide-y divide-zinc-100 dark:divide-zinc-800">
                                {recentLogs.map((log) => (
                                    <div key={log.id} className="py-2.5 flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-3">
                                            <div className="w-7 h-7 rounded-md bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-mono font-bold text-zinc-700 dark:text-zinc-300">
                                                #{log.reminder_number}
                                            </div>
                                            <div>
                                                <div className="font-medium text-zinc-900 dark:text-white">
                                                    {log.user_name}
                                                </div>
                                                <div className="text-zinc-400 font-mono text-[11px]">
                                                    {log.user_email}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-end">
                                            <span className="font-mono bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-[11px] text-zinc-600 dark:text-zinc-300">
                                                {log.coupon_code}
                                            </span>
                                            <div className="text-zinc-400 text-[11px] mt-0.5" title={log.sent_at_exact}>
                                                {log.sent_at}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Email Preview Modal */}
            <Dialog open={previewOpen} onOpenChange={setPreviewOpen}>
                <DialogContent className="max-w-3xl w-full p-0 overflow-hidden bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800">
                    <DialogHeader className="p-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900">
                        <div className="flex items-center justify-between">
                            <div>
                                <DialogTitle className="text-base font-semibold text-zinc-900 dark:text-white">
                                    معاينة قالب البريد الإلكتروني
                                </DialogTitle>
                                <DialogDescription className="text-xs text-zinc-500">
                                    معاينة حية للقالب المتجاوب لكل مرحلة من مراحل التذكير
                                </DialogDescription>
                            </div>
                            {/* Stage Selector */}
                            <div className="flex items-center gap-1">
                                {[1, 2, 3, 4].map((num) => (
                                    <button
                                        key={num}
                                        type="button"
                                        onClick={() => setPreviewReminderNumber(num)}
                                        className={`px-2.5 py-1 rounded text-xs font-medium transition-colors ${
                                            previewReminderNumber === num
                                                ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                                                : 'bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-300'
                                        }`}
                                    >
                                        تذكير {num}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </DialogHeader>

                    {/* Preview Iframe Container */}
                    <div className="w-full h-[550px] bg-zinc-100 dark:bg-zinc-950 p-2">
                        <iframe
                            key={previewReminderNumber}
                            src={`/admin/winback/preview?reminder_number=${previewReminderNumber}`}
                            className="w-full h-full border-0 rounded-lg shadow-inner bg-white"
                            title="معاينة الإيميل"
                        />
                    </div>

                    <DialogFooter className="p-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 flex justify-between items-center">
                        <span className="text-xs text-zinc-500">
                            يتم إرفاق كود الخصم ({coupon.code}) وزر الواتساب تلقائياً.
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPreviewOpen(false)}
                            className="text-xs"
                        >
                            إغلاق المعاينة
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
