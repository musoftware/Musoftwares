import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import Pagination from '@/Components/Pagination';
import { 
    Briefcase, 
    Search, 
    Calendar, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    Filter, 
    X, 
    ArrowUpDown, 
    ExternalLink,
    Zap,
    UserCheck,
    Receipt
} from 'lucide-react';

interface CommissionItem {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
        avatar_url?: string;
    } | null;
    referred_user: {
        id: number;
        name: string;
        email: string;
        avatar_url?: string;
    } | null;
    referred_invoice_id: number | null;
    invoice_number: string | null;
    amount: number;
    currency: string;
    convert_to_balance_on: string | null;
    days_remaining: number | null;
    status: 'cleared' | 'due' | 'pending';
    transaction_id: number | null;
    created_at: string | null;
}

interface IndexProps {
    commissions: {
        data: CommissionItem[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
        per_page: number;
    };
    filters: {
        search: string;
        status: string;
        date_from: string;
        date_to: string;
        sort_by: string;
        direction: 'asc' | 'desc';
        per_page: number;
    };
    stats: {
        pending_count: number;
        due_count: number;
        cleared_count: number;
        total_count: number;
        next_upcoming_date: string | null;
    };
}

export default function CommissionsIndex({ commissions, filters, stats }: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [sortBy, setSortBy] = useState(filters.sort_by || 'convert_to_balance_on');
    const [direction, setDirection] = useState<'asc' | 'desc'>(filters.direction || 'desc');
    
    const [clearingItem, setClearingItem] = useState<CommissionItem | null>(null);
    const [isClearingOpen, setIsClearingOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const applyFilters = (overrides = {}) => {
        const queryParams = {
            search,
            status,
            date_from: dateFrom,
            date_to: dateTo,
            sort_by: sortBy,
            direction,
            per_page: filters.per_page,
            ...overrides,
        };

        // Remove empty strings
        Object.keys(queryParams).forEach((key) => {
            if ((queryParams as any)[key] === '' || (queryParams as any)[key] === null) {
                delete (queryParams as any)[key];
            }
        });

        router.get('/admin/commissions', queryParams, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search });
    };

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus);
        applyFilters({ status: newStatus });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('all');
        setDateFrom('');
        setDateTo('');
        setSortBy('convert_to_balance_on');
        setDirection('desc');
        router.get('/admin/commissions', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const openClearModal = (item: CommissionItem) => {
        setClearingItem(item);
        setIsClearingOpen(true);
    };

    const handleConfirmClear = () => {
        if (!clearingItem) return;
        setIsSubmitting(true);
        router.post(`/admin/commissions/${clearingItem.id}/clear`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setIsClearingOpen(false);
                setClearingItem(null);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const getStatusBadge = (itemStatus: string) => {
        switch (itemStatus) {
            case 'cleared':
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                        <CheckCircle2 size={12} className="me-1" />
                        {__('admin.cleared_commissions') || 'تم الصرف'}
                    </span>
                );
            case 'due':
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                        <AlertCircle size={12} className="me-1" />
                        {__('admin.due_commissions') || 'مستحقة الصرف الآن'}
                    </span>
                );
            case 'pending':
            default:
                return (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        <Clock size={12} className="me-1" />
                        {__('admin.pending_commissions') || 'قيد الانتظار'}
                    </span>
                );
        }
    };

    const getTimeRemainingDisplay = (item: CommissionItem) => {
        if (item.status === 'cleared') {
            return <span className="text-xs text-slate-500">—</span>;
        }

        if (item.days_remaining === null) {
            return <span className="text-xs text-slate-400">—</span>;
        }

        if (item.days_remaining > 0) {
            return (
                <span className="text-xs font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                    {item.days_remaining === 1 ? 'غداً' : `بعد ${item.days_remaining} يوم`}
                </span>
            );
        } else if (item.days_remaining === 0) {
            return (
                <span className="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                    اليوم
                </span>
            );
        } else {
            const absDays = Math.abs(item.days_remaining);
            return (
                <span className="text-xs font-semibold text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                    متأخرة بـ {absDays} يوم
                </span>
            );
        }
    };

    return (
        <AdminSidebarLayout
            title={__('admin.commissions_schedule') || 'جدول استحقاق العمولات'}
            header={
                <div className="flex items-center gap-2">
                    <Briefcase className="h-5 w-5 text-slate-800" />
                    <span className="font-bold text-slate-900 font-sora">
                        {__('admin.commissions_schedule') || 'جدول استحقاق العمولات'}
                    </span>
                </div>
            }
        >
            <Head title={__('admin.commissions_schedule') || 'جدول استحقاق العمولات'} />

            <div className="space-y-6">
                {/* Page Description */}
                <div>
                    <p className="text-sm text-slate-500">
                        {__('admin.commissions_schedule_desc') || 'متابعة مواعيد استحقاق عمولات التسويق والإحالة وحالة صرفها للمحفظة والتحكم بها.'}
                    </p>
                </div>

                {/* Stats Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-[12px] border border-slate-200 shadow-sm">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    {__('admin.pending_commissions') || 'قيد الانتظار'}
                                </p>
                                <h3 className="text-2xl font-bold font-sora text-slate-900 mt-1">
                                    {stats.pending_count}
                                </h3>
                                <p className="text-xs text-slate-400 mt-1">
                                    داخل فترة الحجز المحددة
                                </p>
                            </div>
                            <div className="p-2.5 bg-slate-100 rounded-lg text-slate-700">
                                <Clock size={20} />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-[12px] border border-slate-200 shadow-sm">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    {__('admin.due_commissions') || 'مستحقة الصرف الآن'}
                                </p>
                                <h3 className="text-2xl font-bold font-sora text-amber-600 mt-1">
                                    {stats.due_count}
                                </h3>
                                <p className="text-xs text-slate-400 mt-1">
                                    حان موعد نزولها للمحفظة
                                </p>
                            </div>
                            <div className="p-2.5 bg-amber-50 rounded-lg text-amber-600">
                                <AlertCircle size={20} />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-[12px] border border-slate-200 shadow-sm">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    {__('admin.cleared_commissions') || 'تم الصرف للمحفظة'}
                                </p>
                                <h3 className="text-2xl font-bold font-sora text-green-600 mt-1">
                                    {stats.cleared_count}
                                </h3>
                                <p className="text-xs text-slate-400 mt-1">
                                    أودعت بالفعل في رصيد المستخدمين
                                </p>
                            </div>
                            <div className="p-2.5 bg-green-50 rounded-lg text-green-600">
                                <CheckCircle2 size={20} />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-[12px] border border-slate-200 shadow-sm">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    {__('admin.next_upcoming_release') || 'أقرب موعد نزول قادم'}
                                </p>
                                <h3 className="text-lg font-bold font-sora text-slate-900 mt-2 truncate">
                                    {stats.next_upcoming_date || '—'}
                                </h3>
                                <p className="text-xs text-slate-400 mt-1">
                                    تاريخ نزول الدفعة القادمة
                                </p>
                            </div>
                            <div className="p-2.5 bg-slate-100 rounded-lg text-slate-700">
                                <Calendar size={20} />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="bg-white p-5 rounded-[12px] border border-slate-200 shadow-sm space-y-4">
                    {/* Status Tabs */}
                    <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-4">
                        <button
                            type="button"
                            onClick={() => handleStatusChange('all')}
                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                status === 'all'
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {__('general.all') || 'الكل'} ({stats.total_count})
                        </button>
                        <button
                            type="button"
                            onClick={() => handleStatusChange('pending')}
                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                status === 'pending'
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {__('admin.pending_commissions') || 'قيد الانتظار'} ({stats.pending_count})
                        </button>
                        <button
                            type="button"
                            onClick={() => handleStatusChange('due')}
                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                status === 'due'
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {__('admin.due_commissions') || 'مستحقة الصرف الآن'} ({stats.due_count})
                        </button>
                        <button
                            type="button"
                            onClick={() => handleStatusChange('cleared')}
                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${
                                status === 'cleared'
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {__('admin.cleared_commissions') || 'تم الصرف'} ({stats.cleared_count})
                        </button>
                    </div>

                    {/* Search & Date Controls */}
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                        <div className="lg:col-span-2">
                            <Label className="text-xs text-slate-600 mb-1 block">
                                {__('general.search') || 'بحث'}
                            </Label>
                            <div className="relative">
                                <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder="بحث بالمستفيد، العميل المحال، أو رقم الفاتورة..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="ps-9 text-sm"
                                />
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs text-slate-600 mb-1 block">
                                من تاريخ نزول
                            </Label>
                            <Input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="text-sm"
                            />
                        </div>

                        <div>
                            <Label className="text-xs text-slate-600 mb-1 block">
                                إلى تاريخ نزول
                            </Label>
                            <Input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="text-sm"
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" className="flex-1 text-xs">
                                <Filter size={14} className="me-1.5" />
                                {__('general.apply') || 'تطبيق'}
                            </Button>
                            {(search || dateFrom || dateTo || status !== 'all') && (
                                <Button type="button" variant="outline" size="icon" onClick={handleReset} title="إعادة تعيين">
                                    <X size={16} />
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Main Commissions Table */}
                <div className="bg-white rounded-[12px] border border-slate-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-slate-50/80">
                                <TableRow>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        #
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        {__('admin.recipient_marketer') || 'المستفيد (المسوق)'}
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        {__('admin.referred_source') || 'المصدر (العميل المحال / الفاتورة)'}
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        {__('admin.amount') || 'المبلغ'}
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        {__('admin.release_date') || 'تاريخ النزول'}
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        {__('admin.time_remaining') || 'المتبقي'}
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs">
                                        {__('admin.status') || 'الحالة'}
                                    </TableHead>
                                    <TableHead className="font-semibold text-slate-700 text-xs text-end">
                                        {__('general.actions') || 'الإجراءات'}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody className="divide-y divide-slate-100">
                                {commissions.data && commissions.data.length > 0 ? (
                                    commissions.data.map((item) => (
                                        <TableRow key={item.id} className="hover:bg-slate-50/60 transition-colors">
                                            <TableCell className="text-xs text-slate-500 font-mono">
                                                #{item.id}
                                            </TableCell>

                                            {/* Recipient User */}
                                            <TableCell>
                                                {item.user ? (
                                                    <div className="flex items-center gap-2.5">
                                                        <div className="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                                            {item.user.name ? item.user.name.substring(0, 2).toUpperCase() : 'U'}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <Link
                                                                href={`/admin/users/${item.user.id}`}
                                                                className="font-medium text-sm text-slate-900 hover:underline block truncate"
                                                            >
                                                                {item.user.name}
                                                            </Link>
                                                            <span className="text-xs text-slate-500 block truncate">
                                                                {item.user.email}
                                                            </span>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-slate-400">—</span>
                                                )}
                                            </TableCell>

                                            {/* Source (Referred User / Invoice) */}
                                            <TableCell>
                                                <div className="space-y-1">
                                                    {item.referred_user && (
                                                        <div className="flex items-center gap-1.5 text-xs text-slate-700">
                                                            <UserCheck size={13} className="text-slate-400 shrink-0" />
                                                            <Link
                                                                href={`/admin/users/${item.referred_user.id}`}
                                                                className="hover:underline truncate max-w-[140px]"
                                                                title={item.referred_user.name}
                                                            >
                                                                {item.referred_user.name}
                                                            </Link>
                                                        </div>
                                                    )}
                                                    {item.referred_invoice_id && (
                                                        <div className="flex items-center gap-1 text-xs text-slate-600">
                                                            <Receipt size={12} className="text-slate-400 shrink-0" />
                                                            <Link
                                                                href={`/admin/invoices/${item.referred_invoice_id}`}
                                                                className="font-mono text-slate-800 hover:underline"
                                                            >
                                                                فاتورة #{item.invoice_number || item.referred_invoice_id}
                                                            </Link>
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>

                                            {/* Amount */}
                                            <TableCell>
                                                <span className="font-semibold text-sm text-slate-900 font-mono">
                                                    {item.amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {item.currency}
                                                </span>
                                            </TableCell>

                                            {/* Release Date */}
                                            <TableCell>
                                                <div className="text-xs text-slate-800 font-medium">
                                                    {item.convert_to_balance_on || '—'}
                                                </div>
                                                {item.created_at && (
                                                    <div className="text-[11px] text-slate-400">
                                                        أنشئت: {item.created_at.split(' ')[0]}
                                                    </div>
                                                )}
                                            </TableCell>

                                            {/* Time Remaining */}
                                            <TableCell>
                                                {getTimeRemainingDisplay(item)}
                                            </TableCell>

                                            {/* Status Badge */}
                                            <TableCell>
                                                {getStatusBadge(item.status)}
                                            </TableCell>

                                            {/* Actions */}
                                            <TableCell className="text-end">
                                                {item.status !== 'cleared' ? (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="h-8 px-2.5 text-xs font-medium border-slate-300 hover:bg-slate-100 hover:text-black"
                                                        onClick={() => openClearModal(item)}
                                                    >
                                                        <Zap size={13} className="me-1 text-amber-600" />
                                                        {__('admin.clear_now') || 'صرف الآن'}
                                                    </Button>
                                                ) : (
                                                    <span className="text-xs text-slate-400 font-mono">
                                                        معاملة #{item.transaction_id}
                                                    </span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-12 text-slate-500">
                                            <div className="max-w-sm mx-auto flex flex-col items-center">
                                                <div className="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3">
                                                    <Briefcase size={22} />
                                                </div>
                                                <p className="font-medium text-slate-700 mb-1">
                                                    {__('admin.no_commissions_found') || 'لا توجد عمولات مطابقة لمعايير البحث.'}
                                                </p>
                                                <p className="text-xs text-slate-400">
                                                    جرب تغيير معايير البحث أو تصفية الحالة.
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {commissions.data && commissions.data.length > 0 && (
                        <div className="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div className="text-xs text-slate-500">
                                عرض <span className="font-semibold text-slate-800">{commissions.from || 0}</span> إلى <span className="font-semibold text-slate-800">{commissions.to || 0}</span> من أصل <span className="font-semibold text-slate-800">{commissions.total}</span> عمولة
                            </div>
                            <Pagination links={commissions.links} />
                        </div>
                    )}
                </div>
            </div>

            {/* Clear Commission Confirmation Modal */}
            <Dialog open={isClearingOpen} onOpenChange={setIsClearingOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {__('admin.clear_now') || 'صرف العمولة فوراً'}
                        </DialogTitle>
                        <DialogDescription>
                            {__('admin.clear_now_confirm') || 'هل أنت متأكد من صرف هذه العمولة وإيداعها في محفظة المستخدم فوراً دون انتظار الجدولة التلقائية؟'}
                        </DialogDescription>
                    </DialogHeader>

                    {clearingItem && (
                        <div className="p-4 bg-slate-50 border border-slate-200 rounded-lg text-sm space-y-2 my-2">
                            <div className="flex justify-between">
                                <span className="text-slate-500">المستفيد:</span>
                                <span className="font-semibold text-slate-900">{clearingItem.user?.name}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500">مبلغ العمولة:</span>
                                <span className="font-bold text-slate-900 font-mono">
                                    {clearingItem.amount} {clearingItem.currency}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500">تاريخ الاستحقاق الأصلي:</span>
                                <span className="text-slate-800">{clearingItem.convert_to_balance_on || '—'}</span>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setIsClearingOpen(false)}
                            disabled={isSubmitting}
                        >
                            {__('general.cancel') || 'إلغاء'}
                        </Button>
                        <Button
                            type="button"
                            onClick={handleConfirmClear}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? 'جاري الصرف...' : (__('admin.clear_now') || 'تأكيد الصرف')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
