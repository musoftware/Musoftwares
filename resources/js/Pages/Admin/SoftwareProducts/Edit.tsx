import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { LandingEditor } from '@/Components/Admin/SoftwareStore/LandingEditor';
import { PlansEditor, Plan } from '@/Components/Admin/SoftwareStore/PlansEditor';
import { SectionsEditor } from '@/Components/Admin/SoftwareStore/SectionsEditor';
import { ReleasePanel, Release } from '@/Components/Admin/SoftwareStore/ReleasePanel';
import { t } from '@/Components/Admin/SoftwareStore/t';

interface Product {
    id: number;
    slug: string;
    name_en: string;
    name_ar: string;
    tagline_en: string | null;
    tagline_ar: string | null;
    price: string;
    currency: string;
    video_url: string | null;
    is_active: boolean;
    serial_software_id: number | null;
    image_url: string | null;
    video_file_url: string | null;
    poster_url: string | null;
    gallery_items: Array<{ path: string; url: string }>;
    plans: Plan[];
    landing: Record<string, Record<string, any>>;
    sections: string[];
    colors: { accent: string; ink: string };
    releases: Release[];
    feed_url: string;
    page_url: string;
}

interface Props {
    product: Product;
    allSections: string[];
    serialSoftwares: Array<{ id: number; name: string }>;
}

function Field({ label, hint, error, children }: { label: string; hint?: string; error?: string; children: React.ReactNode }) {
    return (
        <label className="block space-y-1 text-sm">
            <span className="font-medium">{label}</span>
            {children}
            {hint && <span className="block text-xs text-muted-foreground">{hint}</span>}
            {error && <span className="block text-red-600">{error}</span>}
        </label>
    );
}

export default function Edit({ product, allSections, serialSoftwares }: Props) {
    const { errors } = usePage().props as { errors?: Record<string, string> };
    const [form, setForm] = useState({
        name_en: product.name_en,
        name_ar: product.name_ar,
        tagline_en: product.tagline_en ?? '',
        tagline_ar: product.tagline_ar ?? '',
        price: product.price,
        currency: product.currency,
        video_url: product.video_url ?? '',
        is_active: product.is_active,
        serial_software_id: product.serial_software_id ? String(product.serial_software_id) : '',
    });
    const [plans, setPlans] = useState<Plan[]>(product.plans);
    const [landing, setLanding] = useState(product.landing);
    const [sections, setSections] = useState<string[]>(product.sections);
    const [colors, setColors] = useState(product.colors);
    const [lang, setLang] = useState<'ar' | 'en'>('ar');
    const [image, setImage] = useState<File | null>(null);
    const [video, setVideo] = useState<File | null>(null);
    const [poster, setPoster] = useState<File | null>(null);
    const [galleryAdd, setGalleryAdd] = useState<File[]>([]);
    const [galleryRemove, setGalleryRemove] = useState<string[]>([]);
    const [saving, setSaving] = useState(false);

    const set = (key: keyof typeof form, value: string | boolean) => setForm((f) => ({ ...f, [key]: value }));
    const toggleRemove = (path: string) => setGalleryRemove((list) => (list.includes(path) ? list.filter((p) => p !== path) : [...list, path]));

    const save = () => {
        router.post(
            `/admin/software-products/${product.id}`,
            {
                ...form,
                is_active: form.is_active ? 1 : 0,
                plans: plans as unknown as Record<string, string>[],
                landing,
                sections,
                colors,
                image,
                video,
                poster,
                gallery_add: galleryAdd,
                gallery_remove: galleryRemove,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                onSuccess: () => {
                    setGalleryAdd([]);
                    setGalleryRemove([]);
                },
            },
        );
    };

    return (
        <AdminSidebarLayout>
            <Head title={product.name_en} />
            <div className="p-4 sm:p-6 space-y-6 overflow-x-clip max-w-4xl">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link href="/admin/software-products" className="text-sm text-blue-600">
                            {t('Back to the list', 'رجوع للقايمة')}
                        </Link>
                        <h1 className="text-2xl font-bold mt-1">{product.name_en}</h1>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <a href={product.page_url} target="_blank" rel="noreferrer">{t('View page', 'فتح الصفحة')}</a>
                        </Button>
                        <Button onClick={save} disabled={saving}>
                            {saving ? t('Saving...', 'جاري الحفظ...') : t('Save changes', 'حفظ التعديلات')}
                        </Button>
                    </div>
                </div>

                <Tabs defaultValue="basics">
                    <TabsList className="flex flex-wrap h-auto">
                        <TabsTrigger value="basics">{t('Basics and price', 'الأساسيات والسعر')}</TabsTrigger>
                        <TabsTrigger value="plans">{t('Plans', 'الباقات')}</TabsTrigger>
                        <TabsTrigger value="media">{t('Pictures and video', 'الصور والفيديو')}</TabsTrigger>
                        <TabsTrigger value="text">{t('Page text', 'نص الصفحة')}</TabsTrigger>
                        <TabsTrigger value="updates">{t('Updates', 'التحديثات')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="basics" className="space-y-4 pt-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Field label={t('Name (English)', 'الاسم (إنجليزي)')} error={errors?.name_en}>
                                <Input value={form.name_en} onChange={(e) => set('name_en', e.target.value)} />
                            </Field>
                            <Field label={t('Name (Arabic)', 'الاسم (عربي)')} error={errors?.name_ar}>
                                <Input value={form.name_ar} onChange={(e) => set('name_ar', e.target.value)} />
                            </Field>
                            <Field label={t('Short line (English)', 'سطر قصير (إنجليزي)')}>
                                <Input value={form.tagline_en} onChange={(e) => set('tagline_en', e.target.value)} />
                            </Field>
                            <Field label={t('Short line (Arabic)', 'سطر قصير (عربي)')}>
                                <Input value={form.tagline_ar} onChange={(e) => set('tagline_ar', e.target.value)} />
                            </Field>
                            <Field label={t('Price per year', 'السعر في السنة')} error={errors?.price} hint={t('Ignored when you add plans.', 'بيتجاهل لو ضفت باقات.')}>
                                <Input type="number" min="0" value={form.price} onChange={(e) => set('price', e.target.value)} />
                            </Field>
                            <Field label={t('Currency (3 letters)', 'العملة (3 حروف)')} error={errors?.currency}>
                                <Input maxLength={3} value={form.currency} onChange={(e) => set('currency', e.target.value.toUpperCase())} />
                            </Field>
                        </div>
                        <Field
                            label={t('Licensed program', 'البرنامج المرخّص')}
                            hint={t(
                                'Choose the program name used in the license system. Its price, WhatsApp number and automatic activation are then linked to this page. Leave empty for a service that is delivered by hand.',
                                'اختار اسم البرنامج في نظام الترخيص. سعره ورقم الواتساب والتفعيل التلقائي بيتربطوا بالصفحة دي. سيبه فاضي للخدمة اللي بتسلمها يدوي.',
                            )}
                            error={errors?.serial_software_id}
                        >
                            <select className="w-full h-9 rounded-md border px-2 text-sm bg-background" value={form.serial_software_id} onChange={(e) => set('serial_software_id', e.target.value)}>
                                <option value="">{t('Not linked (manual delivery)', 'غير مربوط (تسليم يدوي)')}</option>
                                {serialSoftwares.map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>
                        </Field>
                        <label className="flex items-center gap-3 text-sm">
                            <input type="checkbox" checked={form.is_active} onChange={(e) => set('is_active', e.target.checked)} />
                            <span>{t('Show this program on the website', 'اعرض البرنامج ده على الموقع')}</span>
                        </label>
                    </TabsContent>

                    <TabsContent value="plans" className="pt-4">
                        <PlansEditor plans={plans} onChange={setPlans} />
                        {errors && Object.keys(errors).some((k) => k.startsWith('plans')) && (
                            <p className="mt-3 text-sm text-red-600">{t('Check the plans: every plan needs a name and a price.', 'راجع الباقات: كل باقة لازم ليها اسم وسعر.')}</p>
                        )}
                    </TabsContent>

                    <TabsContent value="media" className="space-y-6 pt-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Field label={t('Program icon', 'أيقونة البرنامج')}>
                                <Input type="file" accept="image/*" onChange={(e) => setImage(e.target.files?.[0] ?? null)} />
                                {product.image_url && <img src={product.image_url} alt="" className="h-14 w-14 object-contain mt-2" />}
                            </Field>
                            <Field label={t('Video file (mp4 or webm)', 'ملف فيديو (mp4 أو webm)')} hint={product.video_file_url ? t('A video is already uploaded.', 'فيه فيديو مرفوع بالفعل.') : undefined}>
                                <Input type="file" accept="video/mp4,video/webm" onChange={(e) => setVideo(e.target.files?.[0] ?? null)} />
                            </Field>
                            <Field label={t('Or a YouTube link', 'أو رابط يوتيوب')} error={errors?.video_url}>
                                <Input dir="ltr" value={form.video_url} onChange={(e) => set('video_url', e.target.value)} placeholder="https://www.youtube.com/watch?v=..." />
                            </Field>
                            <Field label={t('Video cover picture (optional)', 'صورة غلاف الفيديو (اختياري)')}>
                                <Input type="file" accept="image/*" onChange={(e) => setPoster(e.target.files?.[0] ?? null)} />
                            </Field>
                        </div>
                        <div className="space-y-3">
                            <Field label={t('Add screenshots (the first one is the big picture at the top)', 'أضف صور للبرنامج (أول صورة هي الكبيرة فوق)')}>
                                <Input type="file" accept="image/*" multiple onChange={(e) => setGalleryAdd(Array.from(e.target.files ?? []))} />
                            </Field>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                {product.gallery_items.map((item) => (
                                    <button type="button" key={item.path} onClick={() => toggleRemove(item.path)} className="text-start space-y-1">
                                        <img src={item.url} alt="" className={`w-full rounded border ${galleryRemove.includes(item.path) ? 'opacity-30' : ''}`} />
                                        <span className="text-xs text-muted-foreground">
                                            {galleryRemove.includes(item.path) ? t('Will be removed. Click to undo.', 'هتتحذف. اضغط للتراجع.') : t('Click to remove', 'اضغط للحذف')}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="text" className="space-y-6 pt-4">
                        <div className="rounded-md border p-4 space-y-4">
                            <h3 className="font-semibold">{t('Colors', 'الألوان')}</h3>
                            <div className="flex flex-wrap gap-6 text-sm">
                                <label className="flex items-center gap-3">
                                    <input type="color" value={colors.accent} onChange={(e) => setColors({ ...colors, accent: e.target.value })} />
                                    <span>{t('Main color (buttons and numbers)', 'اللون الأساسي (الأزرار والأرقام)')}</span>
                                </label>
                                <label className="flex items-center gap-3">
                                    <input type="color" value={colors.ink} onChange={(e) => setColors({ ...colors, ink: e.target.value })} />
                                    <span>{t('Dark background color', 'لون الخلفية الغامقة')}</span>
                                </label>
                            </div>
                        </div>
                        <div className="rounded-md border p-4 space-y-3">
                            <h3 className="font-semibold">{t('Blocks on the page', 'أقسام الصفحة')}</h3>
                            <p className="text-sm text-muted-foreground">{t('Tick what to show and move blocks up or down.', 'علّم اللي عايز تعرضه وحرّك الأقسام فوق وتحت.')}</p>
                            <SectionsEditor all={allSections} shown={sections} onChange={setSections} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="button" variant={lang === 'ar' ? 'default' : 'outline'} onClick={() => setLang('ar')}>العربي</Button>
                            <Button type="button" variant={lang === 'en' ? 'default' : 'outline'} onClick={() => setLang('en')}>English</Button>
                        </div>
                        <LandingEditor
                            key={lang}
                            copy={landing[lang] ?? {}}
                            order={sections}
                            onChange={(next) => setLanding({ ...landing, [lang]: next })}
                        />
                    </TabsContent>

                    <TabsContent value="updates" className="pt-4">
                        <ReleasePanel productId={product.id} releases={product.releases} feedUrl={product.feed_url} />
                    </TabsContent>
                </Tabs>

                <div className="flex justify-end">
                    <Button onClick={save} disabled={saving}>
                        {saving ? t('Saving...', 'جاري الحفظ...') : t('Save changes', 'حفظ التعديلات')}
                    </Button>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
