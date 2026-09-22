import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
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
    Clock,
    Wallet,
    Plus,
    CheckCircle2,
    AlertCircle,
    FileText,
    ArrowRight,
    Search,
    ShieldCheck,
    HelpCircle
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface MicroServiceItem {
    id: number;
    title: string;
    description: string;
    base_price: number;
    price: number;
    formatted_price: string;
    delivery_days: number;
    is_active: boolean;
}

interface MicroServiceOrderItem {
    id: number;
    service_title: string;
    service_description: string | null;
    amount_paid: number;
    currency_symbol: string;
    formatted_amount: string;
    requirements: string;
    status: 'pending' | 'completed' | 'cancelled';
    admin_notes: string | null;
    completed_at: string | null;
    created_at: string | null;
}

interface Props {
    services: MicroServiceItem[];
    orders: MicroServiceOrderItem[];
    available_balance: number;
    formatted_balance: string;
    currency_symbol: string;
}

export default function MicroServicesIndex({
    services = [],
    orders = [],
    available_balance = 0,
    formatted_balance = '0.00',
    currency_symbol = 'EGP',
}: Props) {
    const [activeTab, setActiveTab] = useState<'catalog' | 'orders'>('catalog');
    const [selectedService, setSelectedService] = useState<MicroServiceItem | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        requirements: '',
    });

    const openOrderModal = (service: MicroServiceItem) => {
        setSelectedService(service);
        setData('requirements', '');
    };

    const closeOrderModal = () => {
        setSelectedService(null);
        reset();
    };

    const handleConfirmOrder = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedService) return;

        post(route('micro-services.order', selectedService.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeOrderModal();
                setActiveTab('orders');
            },
        });
    };

    const hasEnoughBalance = selectedService
        ? available_balance >= selectedService.price
        : true;

    const filteredServices = services.filter((s) =>
        s.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        s.description.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <AuthenticatedLayout>
            <Head title="الخدمات المصغرة | Micro Services" />

            <div className="border-b border-black/5 dark:border-white/10 bg-white/70 dark:bg-zinc-950/70 backdrop-blur-md">
                <PageShell maxWidth="7xl" className="py-8">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div className="space-y-1.5">
                            <div className="flex items-center gap-2">
                                <span className="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                    <Sparkles className="w-5 h-5" />
                                </span>
                                <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] dark:text-white">
                                    الخدمات المصغرة (Micro Services)
                                </h1>
                            </div>
                            <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400 max-w-2xl">
                                حلول سريعة وفورية ينفذها فريق العمل بدقة. اختر الخدمة، واكتب بيانات طلبك، وسيتم الخصم مباشرة من رصيدك المتاح.
                            </p>
                        </div>

                        {/* Balance Card */}
                        <div className="flex items-center gap-4 bg-[#f5f5f7] dark:bg-zinc-900 border border-black/5 dark:border-white/10 p-4 rounded-2xl">
                            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <Wallet className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-[11px] font-medium text-[#1d1d1f]/60 dark:text-zinc-400">
                                    رصيدك المتاح الحالي
                                </div>
                                <div className="text-lg font-bold text-[#1d1d1f] dark:text-zinc-100">
                                    {formatted_balance}
                                </div>
                            </div>
                            <Link
                                href={route('financial.add-balance')}
                                className="ms-2 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-all flex items-center gap-1 shadow-xs"
                            >
                                <Plus className="w-3.5 h-3.5" />
                                <span>شحن</span>
                            </Link>
                        </div>
                    </div>

                    {/* Navigation Tabs */}
                    <div className="flex items-center gap-2 mt-8 border-b border-black/5 dark:border-white/10 pb-0">
                        <button
                            type="button"
                            onClick={() => setActiveTab('catalog')}
                            className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-all cursor-pointer ${
                                activeTab === 'catalog'
                                    ? 'border-[#0071e3] text-[#0071e3] dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-[#1d1d1f]/60 dark:text-zinc-400 hover:text-[#1d1d1f] dark:hover:text-white'
                            }`}
                        >
                            دليل الخدمات ({services.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('orders')}
                            className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
                                activeTab === 'orders'
                                    ? 'border-[#0071e3] text-[#0071e3] dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-[#1d1d1f]/60 dark:text-zinc-400 hover:text-[#1d1d1f] dark:hover:text-white'
                            }`}
                        >
                            <span>طلباتي السابقة</span>
                            {orders.length > 0 && (
                                <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 font-mono font-bold">
                                    {orders.length}
                                </span>
                            )}
                        </button>
                    </div>
                </PageShell>
            </div>

            <PageShell maxWidth="7xl" className="py-8">
                {activeTab === 'catalog' ? (
                    <div className="space-y-6">
                        {/* Search Bar */}
                        <div className="relative max-w-md">
                            <Search className="w-4 h-4 text-[#1d1d1f]/40 dark:text-zinc-500 absolute start-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="ابحث في الخدمات المتاحة..."
                                className="w-full ps-10 pe-4 py-2.5 rounded-xl border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#0071e3]/30"
                            />
                        </div>

                        {filteredServices.length === 0 ? (
                            <div className="text-center py-16 bg-white dark:bg-zinc-900 rounded-3xl border border-black/5 dark:border-white/10 p-8">
                                <Sparkles className="w-12 h-12 text-purple-400 mx-auto mb-3 opacity-60" />
                                <h3 className="text-lg font-bold text-[#1d1d1f] dark:text-white">
                                    لا توجد خدمات مطابقة
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400 mt-1">
                                    لم يتم العثور على خدمات مصغرة تطابق بحثك حالياً.
                                </p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {filteredServices.map((service) => (
                                    <div
                                        key={service.id}
                                        className="bg-white dark:bg-zinc-900 border border-black/5 dark:border-white/10 rounded-2xl p-6 flex flex-col justify-between shadow-xs hover:shadow-md hover:border-black/15 dark:hover:border-white/20 transition-all duration-200"
                                    >
                                        <div className="space-y-3">
                                            <div className="flex items-start justify-between gap-2">
                                                <h3 className="font-bold text-base text-[#1d1d1f] dark:text-white line-clamp-2">
                                                    {service.title}
                                                </h3>
                                                <span className="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 shrink-0">
                                                    <Sparkles className="w-4 h-4" />
                                                </span>
                                            </div>

                                            <p className="text-xs sm:text-sm text-[#1d1d1f]/70 dark:text-zinc-300 leading-relaxed min-h-[40px]">
                                                {service.description}
                                            </p>

                                            <div className="flex items-center gap-2 text-xs text-[#1d1d1f]/50 dark:text-zinc-400 pt-2 border-t border-black/5 dark:border-white/5">
                                                <Clock className="w-3.5 h-3.5 text-blue-500" />
                                                <span>
                                                    مدة التنفيذ المتوقعة: {service.delivery_days} {service.delivery_days === 1 ? 'يوم عمل' : 'أيام عمل'}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="mt-6 pt-4 border-t border-black/5 dark:border-white/10 flex items-center justify-between gap-4">
                                            <div>
                                                <div className="text-[11px] text-[#1d1d1f]/50 dark:text-zinc-400 font-medium">
                                                    السعر
                                                </div>
                                                <div className="text-lg font-extrabold text-[#0071e3] dark:text-blue-400">
                                                    {service.formatted_price}
                                                </div>
                                            </div>

                                            <Button
                                                type="button"
                                                onClick={() => openOrderModal(service)}
                                                className="rounded-xl px-4 py-2 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold shadow-sm active:scale-98 transition-all"
                                            >
                                                <span>طلب الخدمة</span>
                                                <ArrowRight className="w-3.5 h-3.5 ms-1 rtl:rotate-180" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    /* Orders View */
                    <div className="space-y-4">
                        {orders.length === 0 ? (
                            <div className="text-center py-16 bg-white dark:bg-zinc-900 rounded-3xl border border-black/5 dark:border-white/10 p-8">
                                <FileText className="w-12 h-12 text-[#1d1d1f]/30 dark:text-zinc-600 mx-auto mb-3" />
                                <h3 className="text-lg font-bold text-[#1d1d1f] dark:text-white">
                                    لا توجد لديك طلبات سابقة
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                                    يمكنك تصفح دليل الخدمات المصغرة وطلب أي خدمة لتظهر لك تفاصيل وحالة تنفيذها هنا.
                                </p>
                                <Button
                                    type="button"
                                    onClick={() => setActiveTab('catalog')}
                                    className="mt-4 rounded-xl text-xs"
                                >
                                    تصفح الخدمات
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {orders.map((order) => (
                                    <div
                                        key={order.id}
                                        className="bg-white dark:bg-zinc-900 border border-black/5 dark:border-white/10 rounded-2xl p-5 shadow-xs space-y-3"
                                    >
                                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-black/5 dark:border-white/10">
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs font-mono font-bold text-[#1d1d1f]/50 dark:text-zinc-400">
                                                        #{order.id}
                                                    </span>
                                                    <h4 className="font-bold text-base text-[#1d1d1f] dark:text-white">
                                                        {order.service_title}
                                                    </h4>
                                                </div>
                                                <div className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400">
                                                    تاريخ الطلب: {order.created_at ? new Date(order.created_at).toLocaleString('ar-EG', { timeZone: 'Africa/Cairo' }) : '-'}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-3">
                                                <div className="text-end">
                                                    <div className="text-[11px] text-[#1d1d1f]/50 dark:text-zinc-400">
                                                        المبلغ المدفوع
                                                    </div>
                                                    <div className="text-sm font-bold text-[#1d1d1f] dark:text-zinc-100">
                                                        {order.formatted_amount}
                                                    </div>
                                                </div>

                                                {order.status === 'completed' ? (
                                                    <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        <CheckCircle2 className="w-3.5 h-3.5" />
                                                        <span>مكتملة</span>
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        <Clock className="w-3.5 h-3.5 animate-spin" />
                                                        <span>قيد التنفيذ</span>
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        {/* Requirements */}
                                        <div className="bg-[#f9f9fb] dark:bg-zinc-950/50 p-3.5 rounded-xl border border-black/5 dark:border-white/5 space-y-1">
                                            <span className="text-[11px] font-bold text-[#1d1d1f]/60 dark:text-zinc-400">
                                                بيانات وتفاصيل الطلب المرسلة:
                                            </span>
                                            <p className="text-xs text-[#1d1d1f]/80 dark:text-zinc-200 whitespace-pre-wrap leading-relaxed">
                                                {order.requirements}
                                            </p>
                                        </div>

                                        {/* Admin Completion Notes */}
                                        {order.admin_notes && (
                                            <div className="bg-emerald-500/5 dark:bg-emerald-500/10 p-3.5 rounded-xl border border-emerald-500/20 space-y-1">
                                                <span className="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                                    <ShieldCheck className="w-3.5 h-3.5" />
                                                    رد وملاحظات الإدارة:
                                                </span>
                                                <p className="text-xs text-emerald-950 dark:text-emerald-200 whitespace-pre-wrap leading-relaxed">
                                                    {order.admin_notes}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </PageShell>

            {/* Order Confirmation Modal */}
            <Dialog open={!!selectedService} onOpenChange={(open) => !open && closeOrderModal()}>
                <DialogContent className="max-w-lg rounded-3xl border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-900 p-6">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-bold flex items-center gap-2">
                            <Sparkles className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                            <span>طلب خدمة: {selectedService?.title}</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400 pt-1">
                            {selectedService?.description}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleConfirmOrder} className="space-y-4 pt-2">
                        {/* Financial breakdown */}
                        <div className="bg-[#f5f5f7] dark:bg-zinc-950/60 p-4 rounded-2xl border border-black/5 dark:border-white/10 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-[#1d1d1f]/60 dark:text-zinc-400">سعر الخدمة المطلوب:</span>
                                <span className="font-bold text-base text-[#0071e3] dark:text-blue-400">
                                    {selectedService?.formatted_price}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-[#1d1d1f]/60 dark:text-zinc-400">رصيدك المتاح الحالي:</span>
                                <span className={`font-bold text-sm ${hasEnoughBalance ? 'text-emerald-600' : 'text-red-500'}`}>
                                    {formatted_balance}
                                </span>
                            </div>

                            {!hasEnoughBalance && (
                                <div className="mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-xs text-red-600 dark:text-red-400 flex items-start gap-2">
                                    <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                                    <div className="space-y-1">
                                        <p className="font-semibold">رصيدك المتاح غير كافٍ لشراء هذه الخدمة.</p>
                                        <p className="text-[11px] opacity-80">
                                            يرجى شحن رصيدك أولاً لإتمام طلب الخدمة.
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Requirements Textarea */}
                        <div className="space-y-1.5">
                            <label className="text-xs font-bold text-[#1d1d1f] dark:text-zinc-200">
                                بيانات وتفاصيل الطلب <span className="text-red-500">*</span>
                            </label>
                            <Textarea
                                rows={4}
                                value={data.requirements}
                                onChange={(e) => setData('requirements', e.target.value)}
                                placeholder="اكتب هنا كافة البيانات والتفاصيل والروابط المطلوبة لتنفيذ هذه الخدمة بدقة..."
                                className="w-full text-xs rounded-xl border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-950 p-3 focus:ring-2 focus:ring-[#0071e3]/30"
                                required
                            />
                            {errors.requirements && (
                                <p className="text-xs text-red-500 font-medium">{errors.requirements}</p>
                            )}
                        </div>

                        <DialogFooter className="flex flex-col sm:flex-row gap-2 pt-3">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeOrderModal}
                                className="rounded-xl text-xs"
                            >
                                إلغاء
                            </Button>

                            {!hasEnoughBalance ? (
                                <Link
                                    href={route('financial.add-balance')}
                                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl flex items-center justify-center gap-1.5 shadow-sm"
                                >
                                    <Plus className="w-4 h-4" />
                                    <span>شحن الرصيد الآن</span>
                                </Link>
                            ) : (
                                <Button
                                    type="submit"
                                    disabled={processing || !data.requirements.trim()}
                                    className="rounded-xl bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold shadow-sm"
                                >
                                    {processing ? 'جاري الخصم والطلب...' : 'تأكيد الطلب والخصم من الرصيد'}
                                </Button>
                            )}
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
