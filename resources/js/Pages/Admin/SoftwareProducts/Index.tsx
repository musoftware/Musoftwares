import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { t } from '@/Components/Admin/SoftwareStore/t';

interface Row {
    id: number;
    slug: string;
    name: string;
    name_ar: string;
    image_url: string | null;
    price: number;
    has_plans: boolean;
    currency: string;
    is_active: boolean;
    version: string | null;
    paid_orders: number;
    page_url: string;
}

interface Order {
    id: number;
    client_name: string;
    client_email: string;
    client_whatsapp: string;
    plan_name: string | null;
    amount: string;
    currency: string;
    status: string;
    created_at: string;
    product?: { name_en: string };
}

const formatPrice = (row: Row) => `${row.has_plans ? t('from ', 'من ') : ''}${Number(row.price).toLocaleString('en-US')} ${row.currency}`;

export default function Index({ products, orders }: { products: Row[]; orders: Order[] }) {
    return (
        <AdminSidebarLayout>
            <Head title={t('Desktop programs', 'برامج سطح المكتب')} />
            <div className="p-4 sm:p-6 space-y-8 overflow-x-clip">
                <div>
                    <h1 className="text-2xl font-bold">{t('Desktop programs', 'برامج سطح المكتب')}</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        {t('The programs sold on the website. Press Edit to change a price, text, pictures or to upload an update.', 'البرامج المعروضة على الموقع. اضغط تعديل لتغيير السعر أو النص أو الصور أو لرفع تحديث.')}
                    </p>
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Program', 'البرنامج')}</TableHead>
                                <TableHead>{t('Price per year', 'السعر في السنة')}</TableHead>
                                <TableHead>{t('On the website', 'على الموقع')}</TableHead>
                                <TableHead>{t('Version', 'النسخة')}</TableHead>
                                <TableHead>{t('Paid orders', 'طلبات مدفوعة')}</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            {row.image_url && <img src={row.image_url} alt="" className="h-9 w-9 object-contain" />}
                                            <div>
                                                <div className="font-medium">{row.name}</div>
                                                <div className="text-xs text-muted-foreground">{row.name_ar}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">{formatPrice(row)}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <span className={row.is_active ? 'text-green-700' : 'text-muted-foreground'}>
                                                {row.is_active ? t('Visible', 'ظاهر') : t('Hidden', 'مخفي')}
                                            </span>
                                            <Button variant="outline" size="sm" onClick={() => router.post(`/admin/software-products/${row.id}/toggle`, {}, { preserveScroll: true })}>
                                                {row.is_active ? t('Hide', 'إخفاء') : t('Show', 'إظهار')}
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell>{row.version ?? t('none yet', 'لسه مفيش')}</TableCell>
                                    <TableCell>{row.paid_orders}</TableCell>
                                    <TableCell className="whitespace-nowrap space-x-2 text-end">
                                        <Button asChild size="sm">
                                            <Link href={`/admin/software-products/${row.id}/edit`}>{t('Edit', 'تعديل')}</Link>
                                        </Button>
                                        <Button asChild variant="outline" size="sm">
                                            <a href={row.page_url} target="_blank" rel="noreferrer">{t('View page', 'فتح الصفحة')}</a>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="space-y-3">
                    <h2 className="text-lg font-semibold">{t('Latest orders', 'آخر الطلبات')}</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('Order', 'الطلب')}</TableHead>
                                    <TableHead>{t('Program', 'البرنامج')}</TableHead>
                                    <TableHead>{t('Client', 'العميل')}</TableHead>
                                    <TableHead>{t('WhatsApp', 'واتساب')}</TableHead>
                                    <TableHead>{t('Amount', 'المبلغ')}</TableHead>
                                    <TableHead>{t('Status', 'الحالة')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orders.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center text-muted-foreground">
                                            {t('No orders yet.', 'مفيش طلبات لسه.')}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {orders.map((order) => (
                                    <TableRow key={order.id}>
                                        <TableCell>SW-{String(order.id).padStart(5, '0')}</TableCell>
                                        <TableCell>
                                            {order.product?.name_en}
                                            {order.plan_name && <div className="text-xs text-muted-foreground">{order.plan_name}</div>}
                                        </TableCell>
                                        <TableCell>
                                            {order.client_name}
                                            <div className="text-xs text-muted-foreground">{order.client_email}</div>
                                        </TableCell>
                                        <TableCell>
                                            <a className="text-blue-600" href={`https://wa.me/${order.client_whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noreferrer" dir="ltr">
                                                {order.client_whatsapp}
                                            </a>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">{order.amount} {order.currency}</TableCell>
                                        <TableCell>{order.status === 'paid' ? t('Paid', 'مدفوع') : t('Waiting for payment', 'في انتظار الدفع')}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
