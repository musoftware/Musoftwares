import React, { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopField from '@/Components/Admin/Shop/ShopField';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    products_count: number;
    image_url: string | null;
    url: string;
}

function CategoryForm({ category, onDone }: { category: Category | null; onDone: () => void }) {
    const prefix = category ? `category-${category.id}` : 'new-category';
    const { data, setData, post, processing, errors, reset } = useForm({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        description: category?.description ?? '',
        sort_order: String(category?.sort_order ?? 0),
        is_active: category?.is_active ?? true,
        image: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(category ? `/admin/shop/categories/${category.id}` : '/admin/shop/categories', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { reset(); onDone(); },
        });
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl">
            <ShopField id={`${prefix}-name`} label={t('Category name', 'اسم القسم')} error={errors.name}>
                <Input id={`${prefix}-name`} value={data.name} onChange={(e) => setData('name', e.target.value)} required />
            </ShopField>
            <ShopField id={`${prefix}-slug`} label={t('Page address', 'عنوان الصفحة')} hint={t('Left empty, it is made from the name.', 'لو سبته فاضي هيتعمل من الاسم.')} error={errors.slug}>
                <Input id={`${prefix}-slug`} value={data.slug} onChange={(e) => setData('slug', e.target.value)} />
            </ShopField>
            <div className="sm:col-span-2">
                <ShopField id={`${prefix}-description`} label={t('Description (shown on the page and in Google)', 'الوصف (بيظهر في الصفحة وفي جوجل)')} error={errors.description}>
                    <Textarea id={`${prefix}-description`} rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                </ShopField>
            </div>
            <ShopField id={`${prefix}-image`} label={t('Picture', 'الصورة')} error={errors.image}>
                <Input id={`${prefix}-image`} type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />
            </ShopField>
            <ShopField id={`${prefix}-order`} label={t('Order (smaller comes first)', 'الترتيب (الأصغر الأول)')} error={errors.sort_order}>
                <Input id={`${prefix}-order`} type="number" min="0" dir="ltr" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} />
            </ShopField>
            <label className="flex items-center gap-3 min-h-11">
                <Switch checked={data.is_active} onCheckedChange={(value) => setData('is_active', value)} />
                {t('Show in the store', 'يظهر في المتجر')}
            </label>
            <div className="sm:col-span-2">
                <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                    {category ? t('Save', 'حفظ') : t('Add category', 'إضافة قسم')}
                </Button>
            </div>
        </form>
    );
}

function CategoryRow({ category }: { category: Category }) {
    const [editing, setEditing] = useState(false);

    const remove = () => {
        if (window.confirm(t(`Delete "${category.name}"? Its products stay saved.`, `حذف "${category.name}"؟ المنتجات اللي فيه هتفضل محفوظة.`))) {
            router.delete(`/admin/shop/categories/${category.id}`, { preserveScroll: true });
        }
    };

    return (
        <li className="py-4 space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                {category.image_url && <img src={category.image_url} alt="" className="w-12 h-12 object-cover shrink-0" />}
                <div className="flex-1 min-w-0">
                    <p className="font-medium break-words">{category.name}</p>
                    <p className="text-sm text-muted-foreground">
                        {t(`${category.products_count} products`, `${category.products_count} منتج`)}
                        {!category.is_active && ` · ${t('Hidden', 'مخفي')}`}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" onClick={() => setEditing(!editing)}>{editing ? t('Close', 'إغلاق') : t('Edit', 'تعديل')}</Button>
                    <Button size="sm" variant="outline" asChild><a href={category.url} target="_blank" rel="noreferrer">{t('View', 'فتح')}</a></Button>
                    <Button size="sm" variant="destructive" onClick={remove}>{t('Delete', 'حذف')}</Button>
                </div>
            </div>
            {editing && <CategoryForm category={category} onDone={() => setEditing(false)} />}
        </li>
    );
}

export default function Categories({ categories }: { categories: Category[] }) {
    return (
        <AdminSidebarLayout>
            <Head title={t('Store categories', 'أقسام المتجر')} />
            <div className="p-4 sm:p-6 space-y-6 overflow-x-clip max-w-full min-w-0">
                <h1 className="text-2xl font-bold">{t('Store categories', 'أقسام المتجر')}</h1>
                <CategoryForm category={null} onDone={() => undefined} />
                {categories.length === 0
                    ? <p className="py-10 text-center text-muted-foreground border-t">{t('No categories yet.', 'لسه مفيش أقسام.')}</p>
                    : <ul className="divide-y border-t">{categories.map((category) => <CategoryRow key={category.id} category={category} />)}</ul>}
            </div>
        </AdminSidebarLayout>
    );
}
