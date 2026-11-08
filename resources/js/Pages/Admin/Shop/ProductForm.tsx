import React, { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { t } from '@/Components/Admin/SoftwareStore/t';
import ShopField from '@/Components/Admin/Shop/ShopField';

interface Product {
    id: number;
    category_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    compare_at_price: string | null;
    images: string[] | null;
    image_urls: string[];
    sizes: string[] | null;
    colors: string[] | null;
    in_stock: boolean;
    is_active: boolean;
    sort_order: number;
    seo_title: string | null;
    seo_description: string | null;
    url: string;
}

interface Category {
    id: number;
    name: string;
}

function initialValues(product: Product | null) {
    return {
        category_id: product?.category_id ? String(product.category_id) : '',
        name: product?.name ?? '',
        slug: product?.slug ?? '',
        description: product?.description ?? '',
        price: product?.price ?? '',
        compare_at_price: product?.compare_at_price ?? '',
        sizes: (product?.sizes ?? []).join(', '),
        colors: (product?.colors ?? []).join(', '),
        in_stock: product?.in_stock ?? true,
        is_active: product?.is_active ?? true,
        sort_order: String(product?.sort_order ?? 0),
        seo_title: product?.seo_title ?? '',
        seo_description: product?.seo_description ?? '',
        images_add: [] as File[],
        images_remove: [] as string[],
    };
}

function CurrentImages({ product, removed, onToggle }: { product: Product; removed: string[]; onToggle: (path: string) => void }) {
    const paths = product.images ?? [];

    return (
        <div className="flex flex-wrap gap-3">
            {paths.map((path, index) => (
                <div key={path} className="w-24">
                    <img src={product.image_urls[index]} alt="" className={`w-24 h-24 object-cover ${removed.includes(path) ? 'opacity-30' : ''}`} />
                    <Button type="button" size="sm" variant="ghost" className="w-full" onClick={() => onToggle(path)}>
                        {removed.includes(path) ? t('Keep', 'إبقاء') : t('Remove', 'إزالة')}
                    </Button>
                </div>
            ))}
        </div>
    );
}

export default function ProductForm({ product, categories }: { product: Product | null; categories: Category[] }) {
    const { data, setData, post, processing, errors } = useForm(initialValues(product));
    const heading = product ? t('Edit product', 'تعديل المنتج') : t('Add product', 'إضافة منتج');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(product ? `/admin/shop/products/${product.id}` : '/admin/shop/products', { forceFormData: true, preserveScroll: true });
    };

    const toggleRemoved = (path: string) => {
        const removed = data.images_remove;
        setData('images_remove', removed.includes(path) ? removed.filter((item) => item !== path) : [...removed, path]);
    };

    return (
        <AdminSidebarLayout>
            <Head title={heading} />
            <form onSubmit={submit} className="p-4 sm:p-6 space-y-5 overflow-x-clip max-w-3xl min-w-0">
                <div>
                    <Link href="/admin/shop/products" className="text-sm text-blue-600">{t('Back to the list', 'رجوع للقايمة')}</Link>
                    <h1 className="text-2xl font-bold mt-1">{heading}</h1>
                </div>

                <ShopField id="name" label={t('Product name', 'اسم المنتج')} error={errors.name}>
                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                </ShopField>

                <ShopField id="category_id" label={t('Category', 'القسم')} error={errors.category_id}>
                    <select id="category_id" className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={data.category_id} onChange={(e) => setData('category_id', e.target.value)}>
                        <option value="">{t('No category', 'بدون قسم')}</option>
                        {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                    </select>
                </ShopField>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <ShopField id="price" label={t('Price (EGP)', 'السعر (ج.م)')} error={errors.price}>
                        <Input id="price" type="number" min="0" step="0.01" dir="ltr" value={data.price} onChange={(e) => setData('price', e.target.value)} required />
                    </ShopField>
                    <ShopField id="compare_at_price" label={t('Old price (optional)', 'السعر قبل الخصم (اختياري)')} error={errors.compare_at_price}>
                        <Input id="compare_at_price" type="number" min="0" step="0.01" dir="ltr" value={data.compare_at_price} onChange={(e) => setData('compare_at_price', e.target.value)} />
                    </ShopField>
                </div>

                <ShopField id="images_add" label={t('Add pictures', 'إضافة صور')} hint={t('The first picture is the main one. JPG, PNG or WebP.', 'أول صورة هي الصورة الرئيسية. JPG أو PNG أو WebP.')} error={errors.images_add}>
                    {product && <CurrentImages product={product} removed={data.images_remove} onToggle={toggleRemoved} />}
                    <Input id="images_add" type="file" multiple accept="image/jpeg,image/png,image/webp" onChange={(e) => setData('images_add', Array.from(e.target.files ?? []))} />
                </ShopField>

                <ShopField id="description" label={t('Description', 'الوصف')} error={errors.description}>
                    <Textarea id="description" rows={6} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                </ShopField>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <ShopField id="sizes" label={t('Sizes', 'المقاسات')} hint={t('Separate with commas: 40, 41, 42', 'افصل بينهم بفاصلة: 40، 41، 42')} error={errors.sizes}>
                        <Input id="sizes" value={data.sizes} onChange={(e) => setData('sizes', e.target.value)} />
                    </ShopField>
                    <ShopField id="colors" label={t('Colors', 'الألوان')} hint={t('Separate with commas: Black, White', 'افصل بينهم بفاصلة: أسود، أبيض')} error={errors.colors}>
                        <Input id="colors" value={data.colors} onChange={(e) => setData('colors', e.target.value)} />
                    </ShopField>
                </div>

                <div className="flex flex-col sm:flex-row gap-4 sm:gap-8">
                    <label className="flex items-center gap-3 min-h-11">
                        <Switch checked={data.is_active} onCheckedChange={(value) => setData('is_active', value)} />
                        {t('Show in the store', 'يظهر في المتجر')}
                    </label>
                    <label className="flex items-center gap-3 min-h-11">
                        <Switch checked={data.in_stock} onCheckedChange={(value) => setData('in_stock', value)} />
                        {t('Available to order', 'متاح للطلب')}
                    </label>
                </div>

                <fieldset className="border-t pt-5 space-y-4">
                    <legend className="font-semibold pe-3">{t('Google search (optional)', 'الظهور في جوجل (اختياري)')}</legend>
                    <ShopField id="seo_title" label={t('Title in Google', 'العنوان في جوجل')} hint={t('Left empty, the product name is used.', 'لو سبته فاضي هيتاخد اسم المنتج.')} error={errors.seo_title}>
                        <Input id="seo_title" maxLength={255} value={data.seo_title} onChange={(e) => setData('seo_title', e.target.value)} />
                    </ShopField>
                    <ShopField id="seo_description" label={t('Description in Google', 'الوصف في جوجل')} hint={t('Left empty, the start of the description is used.', 'لو سبته فاضي هيتاخد أول الوصف.')} error={errors.seo_description}>
                        <Textarea id="seo_description" rows={2} maxLength={320} value={data.seo_description} onChange={(e) => setData('seo_description', e.target.value)} />
                    </ShopField>
                    <ShopField id="slug" label={t('Page address', 'عنوان الصفحة')} hint={t('Left empty, it is made from the name.', 'لو سبته فاضي هيتعمل من الاسم.')} error={errors.slug}>
                        <Input id="slug" value={data.slug} onChange={(e) => setData('slug', e.target.value)} />
                    </ShopField>
                    <ShopField id="sort_order" label={t('Order in lists (smaller comes first)', 'الترتيب في القوايم (الأصغر الأول)')} error={errors.sort_order}>
                        <Input id="sort_order" type="number" min="0" dir="ltr" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} />
                    </ShopField>
                </fieldset>

                <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                    {processing ? t('Saving...', 'جاري الحفظ...') : t('Save product', 'حفظ المنتج')}
                </Button>
            </form>
        </AdminSidebarLayout>
    );
}
