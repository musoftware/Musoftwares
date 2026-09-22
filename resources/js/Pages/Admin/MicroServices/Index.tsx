import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    Sparkles,
    CheckCircle2,
    Clock,
    Plus,
    Search,
    Edit,
    Trash2,
    Check,
    Layers,
    AlertCircle,
    User,
    Mail
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface ServiceItem {
    id: number;
    title: string;
    description: string;
    price: number;
    formatted_price: string;
    delivery_days: number;
    is_active: boolean;
    orders_count: number;
    created_at: string | null;
}

interface OrderItem {
    id: number;
    client_name: string;
    client_email: string | null;
    service_title: string;
    amount_paid: number;
    formatted_amount: string;
    requirements: string;
    status: 'pending' | 'completed' | 'cancelled';
    admin_notes: string | null;
    completed_at: string | null;
    created_at: string | null;
}

interface Props {
    services: ServiceItem[];
    orders: {
        data: OrderItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: any[];
    };
    filters: {
        status?: string;
        search?: string;
    };
    stats: {
        total_orders: number;
        pending_orders: number;
        completed_orders: number;
        active_services: number;
    };
}

export default function AdminMicroServicesIndex({
    services = [],
    orders,
    filters = {},
    stats = {
        total_orders: 0,
        pending_orders: 0,
        completed_orders: 0,
        active_services: 0,
    },
}: Props) {
    const [activeTab, setActiveTab] = useState<'orders' | 'services'>('orders');
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    // State for Completing an Order
    const [completingOrder, setCompletingOrder] = useState<OrderItem | null>(null);
    const completeForm = useForm({
        admin_notes: '',
    });

    // State for Add / Edit Service Modal
    const [serviceModalOpen, setServiceModalOpen] = useState(false);
    const [editingService, setEditingService] = useState<ServiceItem | null>(null);
    const serviceForm = useForm({
        title: '',
        description: '',
        price: '',
        delivery_days: 1,
        is_active: true,
    });

    const openCreateServiceModal = () => {
        setEditingService(null);
        serviceForm.setData({
            title: '',
            description: '',
            price: '',
            delivery_days: 1,
            is_active: true,
        });
        setServiceModalOpen(true);
    };

    const openEditServiceModal = (service: ServiceItem) => {
        setEditingService(service);
        serviceForm.setData({
            title: service.title,
            description: service.description,
            price: String(service.price),
            delivery_days: service.delivery_days,
            is_active: service.is_active,
        });
        setServiceModalOpen(true);
    };

    const handleSaveService = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingService) {
            serviceForm.put(route('admin.micro-services.update', editingService.id), {
                preserveScroll: true,
                onSuccess: () => setServiceModalOpen(false),
            });
        } else {
            serviceForm.post(route('admin.micro-services.store'), {
                preserveScroll: true,
                onSuccess: () => setServiceModalOpen(false),
            });
        }
    };

    const handleDeleteService = (service: ServiceItem) => {
        if (confirm(`هل أنت متأكد من حذف الخدمة "${service.title}"؟`)) {
            router.delete(route('admin.micro-services.destroy', service.id), {
                preserveScroll: true,
            });
        }
    };

    const handleOpenCompleteOrder = (order: OrderItem) => {
        setCompletingOrder(order);
        completeForm.setData({ admin_notes: '' });
    };

    const handleConfirmCompleteOrder = (e: React.FormEvent) => {
        e.preventDefault();
        if (!completingOrder) return;

        completeForm.post(route('admin.micro-services.orders.complete', completingOrder.id), {
            preserveScroll: true,
            onSuccess: () => setCompletingOrder(null),
        });
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            route('admin.micro-services.index'),
            { search, status: statusFilter },
            { preserveState: true, replace: true }
        );
    };

    const handleStatusFilterChange = (status: string) => {
        setStatusFilter(status);
        router.get(
            route('admin.micro-services.index'),
            { search, status: status || undefined },
            { preserveState: true, replace: true }
        );
    };

    return (
        <AdminSidebarLayout
            title="الخدمات المصغرة | Micro Services"
            header="إدارة الخدمات المصغرة والطلبات"
            actions={
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        onClick={openCreateServiceModal}
                        className="bg-black text-white dark:bg-white dark:text-black hover:opacity-90 rounded-xl text-xs flex items-center gap-1.5"
                    >
                        <Plus className="w-3.5 h-3.5" />
                        <span>إضافة خدمة جديدة</span>
                    </Button>
                </div>
            }
        >
            <div className="p-6 space-y-6 max-w-7xl mx-auto w-full">
                {/* Stats Bento Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-white/10 rounded-2xl p-4 shadow-xs">
                        <div className="text-xs font-semibold text-slate-500 dark:text-zinc-400">إجمالي الطلبات</div>
                        <div className="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                            {stats.total_orders}
                        </div>
                    </div>
                    <div className="bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-500/20 rounded-2xl p-4 shadow-xs">
                        <div className="text-xs font-semibold text-amber-600 dark:text-amber-400">طلبات قيد التنفيذ</div>
                        <div className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {stats.pending_orders}
                        </div>
                    </div>
                    <div className="bg-white dark:bg-zinc-900 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl p-4 shadow-xs">
                        <div className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">طلبات مكتملة</div>
                        <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                            {stats.completed_orders}
                        </div>
                    </div>
                    <div className="bg-white dark:bg-zinc-900 border border-purple-200 dark:border-purple-500/20 rounded-2xl p-4 shadow-xs">
                        <div className="text-xs font-semibold text-purple-600 dark:text-purple-400">خدمات مفعلة</div>
                        <div className="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                            {stats.active_services}
                        </div>
                    </div>
                </div>

                {/* Tabs Switcher */}
                <div className="flex items-center justify-between border-b border-slate-200 dark:border-white/10">
                    <div className="flex items-center gap-4">
                        <button
                            type="button"
                            onClick={() => setActiveTab('orders')}
                            className={`pb-3 text-sm font-semibold border-b-2 transition-all cursor-pointer flex items-center gap-2 ${
                                activeTab === 'orders'
                                    ? 'border-black text-black dark:border-white dark:text-white'
                                    : 'border-transparent text-slate-500 hover:text-black dark:hover:text-white'
                            }`}
                        >
                            <span>طلبات العملاء</span>
                            {stats.pending_orders > 0 && (
                                <span className="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-300 font-mono font-bold">
                                    {stats.pending_orders} بحاجة لتنفيذ
                                </span>
                            )}
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('services')}
                            className={`pb-3 text-sm font-semibold border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
                                activeTab === 'services'
                                    ? 'border-black text-black dark:border-white dark:text-white'
                                    : 'border-transparent text-slate-500 hover:text-black dark:hover:text-white'
                            }`}
                        >
                            <Layers className="w-4 h-4" />
                            <span>دليل الخدمات المتاحة ({services.length})</span>
                        </button>
                    </div>
                </div>

                {activeTab === 'orders' ? (
                    <div className="space-y-4">
                        {/* Search & Filters */}
                        <div className="flex flex-col sm:flex-row gap-3 items-center justify-between">
                            <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 w-full sm:w-80">
                                <div className="relative w-full">
                                    <Search className="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="بحث باسم العميل، الإيميل، التفاصيل..."
                                        className="ps-9 text-xs rounded-xl"
                                    />
                                </div>
                                <Button type="submit" variant="secondary" className="text-xs rounded-xl">
                                    بحث
                                </Button>
                            </form>

                            {/* Status Filter Buttons */}
                            <div className="flex items-center gap-1.5 bg-slate-100 dark:bg-zinc-800 p-1 rounded-xl text-xs">
                                <button
                                    type="button"
                                    onClick={() => handleStatusFilterChange('')}
                                    className={`px-3 py-1.5 rounded-lg font-medium transition-all ${
                                        !statusFilter ? 'bg-white dark:bg-zinc-900 text-black dark:text-white shadow-xs' : 'text-slate-600 dark:text-zinc-400'
                                    }`}
                                >
                                    الكل ({stats.total_orders})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleStatusFilterChange('pending')}
                                    className={`px-3 py-1.5 rounded-lg font-medium transition-all ${
                                        statusFilter === 'pending' ? 'bg-white dark:bg-zinc-900 text-amber-600 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400'
                                    }`}
                                >
                                    قيد التنفيذ ({stats.pending_orders})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleStatusFilterChange('completed')}
                                    className={`px-3 py-1.5 rounded-lg font-medium transition-all ${
                                        statusFilter === 'completed' ? 'bg-white dark:bg-zinc-900 text-emerald-600 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400'
                                    }`}
                                >
                                    مكتملة ({stats.completed_orders})
                                </button>
                            </div>
                        </div>

                        {/* Orders Table */}
                        <div className="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-xs">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50 dark:bg-zinc-950/50">
                                        <TableHead className="w-16">رقم</TableHead>
                                        <TableHead>العميل</TableHead>
                                        <TableHead>الخدمة المطلوبة</TableHead>
                                        <TableHead>المبلغ المدفوع</TableHead>
                                        <TableHead>بيانات وتفاصيل الطلب</TableHead>
                                        <TableHead>الحالة</TableHead>
                                        <TableHead className="text-end">الإجراء</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-12 text-slate-500">
                                                لا توجد طلبات تطابق المعايير المحددة.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        orders.data.map((order) => (
                                            <TableRow key={order.id} className="hover:bg-slate-50/50 dark:hover:bg-zinc-800/50">
                                                <TableCell className="font-mono text-xs font-bold text-slate-500">
                                                    #{order.id}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-0.5">
                                                        <div className="font-bold text-xs flex items-center gap-1">
                                                            <User className="w-3 h-3 text-slate-400" />
                                                            <span>{order.client_name}</span>
                                                        </div>
                                                        <div className="text-[11px] text-slate-500 flex items-center gap-1">
                                                            <Mail className="w-3 h-3 text-slate-400" />
                                                            <span>{order.client_email}</span>
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-semibold text-xs text-slate-900 dark:text-white">
                                                        {order.service_title}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-bold text-xs text-emerald-600 dark:text-emerald-400">
                                                        {order.formatted_amount}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="max-w-xs">
                                                    <p className="text-xs text-slate-700 dark:text-zinc-300 line-clamp-3 whitespace-pre-wrap">
                                                        {order.requirements}
                                                    </p>
                                                    {order.admin_notes && (
                                                        <div className="mt-1 text-[11px] text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 p-1.5 rounded-lg border border-emerald-200/50">
                                                            رد الإدارة: {order.admin_notes}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {order.status === 'completed' ? (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                            <CheckCircle2 className="w-3 h-3" />
                                                            <span>مكتملة</span>
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                            <Clock className="w-3 h-3" />
                                                            <span>قيد التنفيذ</span>
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-end">
                                                    {order.status === 'pending' ? (
                                                        <Button
                                                            type="button"
                                                            onClick={() => handleOpenCompleteOrder(order)}
                                                            className="rounded-xl text-xs bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1 shadow-xs"
                                                        >
                                                            <Check className="w-3.5 h-3.5" />
                                                            <span>تحديد كمكتملة</span>
                                                        </Button>
                                                    ) : (
                                                        <span className="text-[11px] text-slate-400">تم التسليم</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                ) : (
                    /* Services Catalog Management */
                    <div className="space-y-4">
                        <div className="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-xs">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50 dark:bg-zinc-950/50">
                                        <TableHead>عنوان الخدمة</TableHead>
                                        <TableHead>الوصف البسيط (سطر)</TableHead>
                                        <TableHead>السعر الأساسي (EGP)</TableHead>
                                        <TableHead>أيام التسليم</TableHead>
                                        <TableHead>عدد الطلبات</TableHead>
                                        <TableHead>الحالة</TableHead>
                                        <TableHead className="text-end">الإجراءات</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {services.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-12 text-slate-500">
                                                لا توجد خدمات مصغرة مضافة بعد. اضغط على "إضافة خدمة جديدة" للبدء.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        services.map((service) => (
                                            <TableRow key={service.id} className="hover:bg-slate-50/50 dark:hover:bg-zinc-800/50">
                                                <TableCell className="font-bold text-xs text-slate-900 dark:text-white">
                                                    {service.title}
                                                </TableCell>
                                                <TableCell className="text-xs text-slate-600 dark:text-zinc-300 max-w-sm">
                                                    {service.description}
                                                </TableCell>
                                                <TableCell className="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400">
                                                    {service.formatted_price}
                                                </TableCell>
                                                <TableCell className="text-xs text-slate-600 dark:text-zinc-400">
                                                    {service.delivery_days} {service.delivery_days === 1 ? 'يوم' : 'أيام'}
                                                </TableCell>
                                                <TableCell className="text-xs font-mono font-semibold">
                                                    {service.orders_count}
                                                </TableCell>
                                                <TableCell>
                                                    {service.is_active ? (
                                                        <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-600">
                                                            مفعلة
                                                        </span>
                                                    ) : (
                                                        <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/10 text-slate-500">
                                                            معطلة
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-end">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => openEditServiceModal(service)}
                                                            className="h-8 w-8 p-0 rounded-lg"
                                                        >
                                                            <Edit className="w-3.5 h-3.5" />
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDeleteService(service)}
                                                            className="h-8 w-8 p-0 rounded-lg text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20"
                                                        >
                                                            <Trash2 className="w-3.5 h-3.5" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                )}
            </div>

            {/* Complete Order Dialog */}
            <Dialog open={!!completingOrder} onOpenChange={(open) => !open && setCompletingOrder(null)}>
                <DialogContent className="max-w-md rounded-2xl p-6">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold flex items-center gap-2">
                            <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                            <span>تأكيد إكمال الطلب #{completingOrder?.id}</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 pt-1">
                            خدمة: {completingOrder?.service_title} للعميل {completingOrder?.client_name}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleConfirmCompleteOrder} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <label className="text-xs font-bold text-slate-700 dark:text-zinc-200">
                                ملاحظات التسليم / رد الإدارة (اختياري)
                            </label>
                            <Textarea
                                rows={3}
                                value={completeForm.data.admin_notes}
                                onChange={(e) => completeForm.setData('admin_notes', e.target.value)}
                                placeholder="اكتب هنا أي تفاصيل أو روابط أو ملاحظات تود إظهارها للعميل عند اكتمال الخدمة..."
                                className="w-full text-xs rounded-xl"
                            />
                        </div>

                        <DialogFooter className="flex gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setCompletingOrder(null)}
                                className="rounded-xl text-xs"
                            >
                                إلغاء
                            </Button>
                            <Button
                                type="submit"
                                disabled={completeForm.processing}
                                className="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold"
                            >
                                {completeForm.processing ? 'جاري التأكيد...' : 'تحديد الخدمة كمكتملة'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Create / Edit Service Dialog */}
            <Dialog open={serviceModalOpen} onOpenChange={(open) => !open && setServiceModalOpen(false)}>
                <DialogContent className="max-w-md rounded-2xl p-6">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold flex items-center gap-2">
                            <Sparkles className="w-5 h-5 text-purple-600" />
                            <span>{editingService ? 'تعديل الخدمة المصغرة' : 'إضافة خدمة مصغرة جديدة'}</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 pt-1">
                            أدخل عنوان الخدمة ووصفها البسيط (سطر واحد) وسعرها الأساسي.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveService} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <label className="text-xs font-bold">اسم / عنوان الخدمة *</label>
                            <Input
                                value={serviceForm.data.title}
                                onChange={(e) => serviceForm.setData('title', e.target.value)}
                                placeholder="مثال: تنصيب وتفعيل قالب على ووردبريس"
                                className="text-xs rounded-xl"
                                required
                            />
                            {serviceForm.errors.title && (
                                <p className="text-xs text-red-500">{serviceForm.errors.title}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <label className="text-xs font-bold">وصف الخدمة البسيط (سطر واحد) *</label>
                            <Input
                                value={serviceForm.data.description}
                                onChange={(e) => serviceForm.setData('description', e.target.value)}
                                placeholder="مثال: نقوم برفع ملفات القالب وتثبيت الإضافات وضبط الإعدادات الأساسية خلال 24 ساعة."
                                className="text-xs rounded-xl"
                                required
                            />
                            {serviceForm.errors.description && (
                                <p className="text-xs text-red-500">{serviceForm.errors.description}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <label className="text-xs font-bold">السعر الأساسي (EGP) *</label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={serviceForm.data.price}
                                    onChange={(e) => serviceForm.setData('price', e.target.value)}
                                    placeholder="250.00"
                                    className="text-xs rounded-xl font-mono"
                                    required
                                />
                                {serviceForm.errors.price && (
                                    <p className="text-xs text-red-500">{serviceForm.errors.price}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-bold">مدة التنفيذ (بالأيام) *</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="90"
                                    value={serviceForm.data.delivery_days}
                                    onChange={(e) => serviceForm.setData('delivery_days', parseInt(e.target.value) || 1)}
                                    className="text-xs rounded-xl font-mono"
                                    required
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-2 pt-2">
                            <input
                                type="checkbox"
                                id="is_active_toggle"
                                checked={serviceForm.data.is_active}
                                onChange={(e) => serviceForm.setData('is_active', e.target.checked)}
                                className="rounded text-black focus:ring-0"
                            />
                            <label htmlFor="is_active_toggle" className="text-xs font-semibold cursor-pointer">
                                تفعيل الخدمة وظهورها للعملاء في الدليل
                            </label>
                        </div>

                        <DialogFooter className="flex gap-2 pt-3">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setServiceModalOpen(false)}
                                className="rounded-xl text-xs"
                            >
                                إلغاء
                            </Button>
                            <Button
                                type="submit"
                                disabled={serviceForm.processing}
                                className="rounded-xl bg-black text-white dark:bg-white dark:text-black text-xs font-semibold"
                            >
                                {serviceForm.processing ? 'جاري الحفظ...' : editingService ? 'حفظ التعديلات' : 'إضافة الخدمة'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}
