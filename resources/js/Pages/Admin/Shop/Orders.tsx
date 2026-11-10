import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopPager, { Paginated } from '@/Components/Admin/Shop/ShopPager';

interface Item {
    id: number;
    product_name: string;
    size: string | null;
    color: string | null;
    quantity: number;
    line_total: string;
}

interface Order {
    id: number;
    customer_name: string;
    phone: string;
    governorate: string;
    address: string;
    notes: string | null;
    total: string;
    status: string;
    created_at: string;
    items: Item[];
    angazny_status: string | null;
    angazny_order_code: string | null;
    angazny_error: string | null;
    area: { name: string } | null;
}

const STATUS_LABELS: Record<string, [string, string]> = {
    new: ['New', 'جديد'],
    confirmed: ['Confirmed', 'تم التأكيد'],
    shipped: ['Shipped', 'تم الشحن'],
    delivered: ['Delivered', 'تم التسليم'],
    cancelled: ['Cancelled', 'ملغي'],
};

const ANGAZNY_LABELS: Record<string, [string, string]> = {
    pending: ['Not sent to Angazny yet', 'لسه ما اتبعتش لانجزني'],
    sending: ['Sending to Angazny', 'جاري الإرسال لانجزني'],
    sent: ['Sent to Angazny', 'اتبعت لانجزني'],
    failed: ['Angazny send failed', 'فشل الإرسال لانجزني'],
    unknown: ['Check Angazny before resending', 'راجع انجزني قبل أي إرسال تاني'],
};

const SENDABLE_TO_ANGAZNY = ['pending', 'failed'];

function AngaznyState({ order }: { order: Order }) {
    if (!order.angazny_status) return null;
    const [en, ar] = ANGAZNY_LABELS[order.angazny_status];

    return (
        <div className="text-sm space-y-1">
            <p>{t(en, ar)}{order.angazny_order_code && ` · ${order.angazny_order_code}`}</p>
            {order.angazny_error && <p className="text-red-700 break-words">{order.angazny_error}</p>}
            {SENDABLE_TO_ANGAZNY.includes(order.angazny_status) && order.status !== 'cancelled' && (
                <button
                    type="button"
                    className="inline-flex items-center min-h-11 text-blue-600 underline"
                    onClick={() => router.post(`/admin/shop/orders/${order.id}/angazny`, {}, { preserveScroll: true })}
                >
                    {t('Send to Angazny', 'إرسال لانجزني')}
                </button>
            )}
        </div>
    );
}

const statusLabel = (status: string) => t(STATUS_LABELS[status][0], STATUS_LABELS[status][1]);

const cairoTime = (value: string) => new Date(value).toLocaleString('en-GB', { timeZone: 'Africa/Cairo', dateStyle: 'medium', timeStyle: 'short' });

function OrderRow({ order, statuses, currency }: { order: Order; statuses: string[]; currency: string }) {
    const changeStatus = (status: string) => router.post(`/admin/shop/orders/${order.id}/status`, { status }, { preserveScroll: true });

    return (
        <li className="py-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div className="min-w-0">
                <p className="font-semibold">#{String(order.id).padStart(5, '0')} · {order.customer_name}</p>
                <a className="text-blue-600 inline-flex items-center min-h-11" dir="ltr" href={`tel:${order.phone}`}>{order.phone}</a>
                <p className="text-sm break-words">{order.governorate}{order.area && ` - ${order.area.name}`}: {order.address}</p>
                {order.notes && <p className="text-sm text-muted-foreground break-words">{order.notes}</p>}
                <p className="text-sm text-muted-foreground">{cairoTime(order.created_at)}</p>
            </div>
            <ul className="text-sm space-y-1 min-w-0">
                {order.items.map((item) => (
                    <li key={item.id} className="break-words">
                        {item.quantity} × {item.product_name}
                        {item.size && ` / ${item.size}`}
                        {item.color && ` / ${item.color}`}
                    </li>
                ))}
            </ul>
            <div className="space-y-2">
                <p className="font-semibold">{Number(order.total).toLocaleString('en-US')} {currency}</p>
                <select
                    className="h-11 w-full rounded-md border bg-background px-3 text-sm"
                    value={order.status}
                    onChange={(e) => changeStatus(e.target.value)}
                    aria-label={t(`Status of order ${order.id}`, `حالة الطلب ${order.id}`)}
                >
                    {statuses.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}
                </select>
                <AngaznyState order={order} />
            </div>
        </li>
    );
}

export default function Orders({ orders, statuses, status, currency }: { orders: Paginated<Order>; statuses: string[]; status: string; currency: string }) {
    const filterClass = (active: boolean) => `inline-flex items-center min-h-11 ${active ? 'font-bold underline underline-offset-4' : 'text-blue-600'}`;

    return (
        <AdminSidebarLayout>
            <Head title={t('Store orders', 'طلبات المتجر')} />
            <div className="p-4 sm:p-6 space-y-4 overflow-x-clip max-w-full min-w-0">
                <h1 className="text-2xl font-bold">{t('Store orders', 'طلبات المتجر')}</h1>
                <nav className="flex flex-wrap gap-x-5" aria-label={t('Filter by status', 'فلترة بالحالة')}>
                    <Link href="/admin/shop/orders" className={filterClass(status === '')}>{t('All', 'الكل')}</Link>
                    {statuses.map((key) => (
                        <Link key={key} href={`/admin/shop/orders?status=${key}`} className={filterClass(status === key)}>{statusLabel(key)}</Link>
                    ))}
                </nav>
                {orders.data.length === 0
                    ? <p className="py-10 text-center text-muted-foreground">{t('No orders here.', 'مفيش طلبات هنا.')}</p>
                    : <ul className="divide-y border-t">{orders.data.map((order) => <OrderRow key={order.id} order={order} statuses={statuses} currency={currency} />)}</ul>}
                <ShopPager page={orders} />
            </div>
        </AdminSidebarLayout>
    );
}
