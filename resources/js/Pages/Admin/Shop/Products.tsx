import React, { FormEvent, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopPager, { Paginated } from '@/Components/Admin/Shop/ShopPager';

interface Row {
    id: number;
    name: string;
    category: string | null;
    price: string;
    image_url: string | null;
    in_stock: boolean;
    is_active: boolean;
    url: string;
}

interface ImportReport {
    created: number;
    skipped: number;
    problems: string[];
}

function ImportPanel({ report }: { report: ImportReport | null }) {
    const [file, setFile] = useState<File | null>(null);
    const [busy, setBusy] = useState(false);

    const upload = (event: FormEvent) => {
        event.preventDefault();
        if (!file) return;
        router.post('/admin/shop/products/import', { file }, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <section className="border-t pt-6 space-y-3">
            <h2 className="text-lg font-semibold">{t('Add many products from a file', 'إضافة منتجات كتير من ملف')}</h2>
            <p className="text-sm text-muted-foreground">
                {t(
                    'Upload a CSV file. New products stay hidden until you add a picture and show them. Names that already exist are skipped.',
                    'ارفع ملف CSV. المنتجات الجديدة بتفضل مخفية لحد ما تضيف صورة وتظهرها. الأسماء الموجودة قبل كده بتتسكب.',
                )}{' '}
                <a className="text-blue-600 underline" href="/admin/shop/products/import-template">{t('Download a sample file', 'نزل ملف نموذج')}</a>
            </p>
            <form onSubmit={upload} className="flex flex-col sm:flex-row gap-2 max-w-xl">
                <Input type="file" accept=".csv,text/csv" aria-label={t('CSV file', 'ملف CSV')} onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
                <Button type="submit" disabled={!file || busy}>{busy ? t('Uploading...', 'جاري الرفع...') : t('Upload', 'رفع')}</Button>
            </form>
            {report && (
                <div className="text-sm" role="status">
                    <p>{t(`Added ${report.created}, skipped ${report.skipped}.`, `تمت إضافة ${report.created}، وتم تخطي ${report.skipped}.`)}</p>
                    {report.problems.map((problem) => <p key={problem} className="text-red-700">{problem}</p>)}
                </div>
            )}
        </section>
    );
}

function ProductRow({ row }: { row: Row }) {
    const remove = () => {
        if (window.confirm(t(`Delete "${row.name}"?`, `حذف "${row.name}"؟`))) {
            router.delete(`/admin/shop/products/${row.id}`, { preserveScroll: true });
        }
    };

    return (
        <TableRow>
            <TableCell>
                <div className="flex items-center gap-3 min-w-[14rem]">
                    {row.image_url
                        ? <img src={row.image_url} alt="" className="w-12 h-12 object-cover shrink-0" />
                        : <span className="w-12 h-12 shrink-0 bg-muted" aria-hidden="true" />}
                    <span className="font-medium break-words min-w-0">{row.name}</span>
                </div>
            </TableCell>
            <TableCell>{row.category ?? '-'}</TableCell>
            <TableCell className="whitespace-nowrap">{Number(row.price).toLocaleString('en-US')}</TableCell>
            <TableCell>{row.in_stock ? t('Available', 'متاح') : t('Sold out', 'نفذ')}</TableCell>
            <TableCell>
                <Button size="sm" variant="outline" onClick={() => router.post(`/admin/shop/products/${row.id}/toggle`, {}, { preserveScroll: true })}>
                    {row.is_active ? t('Hide', 'إخفاء') : t('Show', 'إظهار')}
                </Button>
            </TableCell>
            <TableCell>
                <div className="flex flex-wrap gap-2">
                    <Button size="sm" asChild><Link href={`/admin/shop/products/${row.id}/edit`}>{t('Edit', 'تعديل')}</Link></Button>
                    {row.is_active && <Button size="sm" variant="outline" asChild><a href={row.url} target="_blank" rel="noreferrer">{t('View', 'فتح')}</a></Button>}
                    <Button size="sm" variant="destructive" onClick={remove}>{t('Delete', 'حذف')}</Button>
                </div>
            </TableCell>
        </TableRow>
    );
}

export default function Products({ products, search, importReport }: { products: Paginated<Row>; search: string; importReport: ImportReport | null }) {
    const { errors } = usePage().props as unknown as { errors: Record<string, string> };
    const [term, setTerm] = useState(search);

    const runSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get('/admin/shop/products', term ? { search: term } : {}, { preserveState: true });
    };

    return (
        <AdminSidebarLayout>
            <Head title={t('Store products', 'منتجات المتجر')} />
            <div className="p-4 sm:p-6 space-y-6 overflow-x-clip max-w-full min-w-0">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">{t('Store products', 'منتجات المتجر')}</h1>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild><Link href="/admin/shop/products/create">{t('Add product', 'إضافة منتج')}</Link></Button>
                        <Button asChild variant="outline"><a href="/store" target="_blank" rel="noreferrer">{t('Open the store', 'فتح المتجر')}</a></Button>
                    </div>
                </div>

                <form onSubmit={runSearch} className="flex gap-2 max-w-md">
                    <Input value={term} onChange={(e) => setTerm(e.target.value)} placeholder={t('Search by name', 'بحث بالاسم')} aria-label={t('Search by name', 'بحث بالاسم')} />
                    <Button type="submit" variant="outline">{t('Search', 'بحث')}</Button>
                </form>

                {errors.images_add && <p className="text-sm text-red-700" role="alert">{errors.images_add}</p>}

                {products.data.length === 0 ? (
                    <p className="py-10 text-center text-muted-foreground">{t('No products yet. Add your first product.', 'لسه مفيش منتجات. ضيف أول منتج.')}</p>
                ) : (
                    <div className="overflow-x-auto w-full max-w-full rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('Product', 'المنتج')}</TableHead>
                                    <TableHead>{t('Category', 'القسم')}</TableHead>
                                    <TableHead>{t('Price', 'السعر')}</TableHead>
                                    <TableHead>{t('Stock', 'المخزون')}</TableHead>
                                    <TableHead>{t('In the store', 'في المتجر')}</TableHead>
                                    <TableHead>{t('Actions', 'الإجراءات')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {products.data.map((row) => <ProductRow key={row.id} row={row} />)}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <ShopPager page={products} />
                <ImportPanel report={importReport} />
            </div>
        </AdminSidebarLayout>
    );
}
