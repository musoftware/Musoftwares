import React, { useState } from 'react';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { UserSearchCombobox } from '@/Components/Admin/UserSearchCombobox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { ArrowLeft, ArrowRight, Save, Plus, Trash2, Globe, Image as ImageIcon } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { toast } from 'sonner';

interface Category {
    id: number;
    name: string;
}

interface Package {
    id?: number;
    features?: string[];
    name: string;
    description: string;
    price: number | string;
    currency_id: number | string;
    delivery_days: number | string;
    old_price?: number | string | null;
    revisions?: number | string | null;
}

interface Currency {
    id: number;
    currency: string;
    symbol?: string;
}

interface Extra {
    id?: number;
    title: string;
    price: number | string;
    duration_days: number | string;
}

interface Faq {
    question: string;
    answer: string;
}

interface Service {
    id: number;
    seller_id: number;
    title: string;
    tagline?: string;
    description: string;
    auto_reply?: string;
    auto_complete?: boolean;
    tags?: string[];
    requirements?: string[];
    video_url?: string;
    title_translations?: any;
    tagline_translations?: any;
    description_translations?: any;
    auto_reply_translations?: any;
    category_id: number;
    status: string;
    packages: Package[];
    extras?: Extra[];
    faq?: Faq[];
    gallery?: string[];
    is_free?: boolean;
    service_link?: string;
    generate_serials?: boolean;
    allow_random_serial?: boolean;
    validity_days?: number;
    referral_commission_from?: string;
    referral_commission_percentage?: number;
}

interface Props {
    auth: any;
    service: Service | null;
    categories: Category[];
    currencies: Currency[];
    mode: 'admin' | 'seller';
    backUrl: string;
    needsReview?: boolean;
    adminUrl?: string | null;
}

const WIZARD_STEPS = ['basics', 'packages', 'extras', 'gallery', 'delivery'];

const ERROR_FIELD_STEPS: Record<string, number> = {
    packages: 1, extras: 2, faq: 2, gallery: 3, kept_gallery: 3,
    auto_reply: 4, auto_reply_translations: 4, auto_complete: 4, requirements: 4, service_link: 4, is_free: 4,
    generate_serials: 4, allow_random_serial: 4, validity_days: 4, referral_commission_from: 4, referral_commission_percentage: 4,
};

/** The wizard step that holds a server error, so the admin lands on the field to fix. */
function stepOfError(field: string): number {
    return ERROR_FIELD_STEPS[field.split('.')[0]] ?? 0;
}

/** Older services have only the plain text: show it as the English version. */
function initialTranslations(plain?: string, translations?: any) {
    return { en: translations?.en ?? plain ?? '', ar: translations?.ar ?? '' };
}

function splitList(value: string, separator: string): string[] {
    return value.split(separator).map((item) => item.trim()).filter(Boolean);
}

/**
 * The full service form. Admins and sellers share it, so a field added here reaches both.
 * Owner, status, auto delivery and referral settings are admin decisions, so sellers do not see them.
 */
export default function ServiceForm({ auth, service, categories, currencies, mode, backUrl, needsReview = false, adminUrl = null }: Props) {
    const isWizard = service === null;
    const pageLocale = (usePage().props as any).locale;
    // A saved service keeps its text on the English tab. A new one starts in the language of the page.
    const [locale, setLocale] = useState<'en' | 'ar'>(isWizard && pageLocale === 'ar' ? 'ar' : 'en');
    const [step, setStep] = useState(0);
    const isAdminMode = mode === 'admin';
    const showStep = (index: number) => !isWizard || step === index;
    const defaultCurrencyId = currencies[0]?.id ?? 1;
    const emptyPackage = () => ({
        name: '', description: '', price: '', currency_id: defaultCurrencyId, delivery_days: '', old_price: '', revisions: 2, features: [] as string[],
    });

    const { data, setData, post, processing, errors } = useForm({
        _method: isWizard ? 'post' : 'put',
        full_form: true,
        seller_id: (service?.seller_id ?? auth?.user?.id ?? '') as number | string,
        title: service?.title || '',
        title_translations: initialTranslations(service?.title, service?.title_translations),
        tagline: service?.tagline || '',
        tagline_translations: initialTranslations(service?.tagline, service?.tagline_translations),
        description: service?.description || '',
        description_translations: initialTranslations(service?.description, service?.description_translations),
        auto_reply: service?.auto_reply || '',
        auto_reply_translations: initialTranslations(service?.auto_reply, service?.auto_reply_translations),
        auto_complete: service?.auto_complete || false,
        category_id: service?.category_id ? String(service.category_id) : '',
        status: service?.status || 'active',
        packages: (service?.packages?.length ? service.packages : [emptyPackage()]) as Package[],
        extras: service?.extras || [],
        faq: service?.faq || [],
        tags: service?.tags || [],
        requirements: service?.requirements || [],
        video_url: service?.video_url || '',
        kept_gallery: service?.gallery || [],
        gallery: [] as File[],
        gallery_order: [] as string[],
        is_free: service?.is_free || false,
        service_link: service?.service_link || '',
        generate_serials: service?.generate_serials || false,
        allow_random_serial: service?.allow_random_serial || false,
        validity_days: service?.validity_days || '',
        referral_commission_from: service?.referral_commission_from || 'fee',
        referral_commission_percentage: service?.referral_commission_percentage || '',
    });

    const save = () => {
        // Post with _method so file uploads work on updates too
        const routePrefix = isAdminMode ? 'admin.marketplace' : 'marketplace';
        const url = isWizard ? route(`${routePrefix}.services.store`) : route(`${routePrefix}.services.update`, service.id);
        post(url, {
            onError: (found) => {
                toast.error(__('admin.marketplace_fix_errors'));
                if (isWizard) setStep(stepOfError(Object.keys(found)[0] ?? ''));
            },
        });
    };

    const stepIsComplete = (index: number): boolean => {
        const translated = (field: { en: string; ar: string }) => Boolean(field.en?.trim() || field.ar?.trim());
        const checks: Record<number, () => boolean> = {
            0: () => Boolean((!isAdminMode || data.seller_id) && data.category_id) && translated(data.title_translations) && translated(data.description_translations),
            1: () => data.packages.every((pkg) => pkg.name && pkg.description && pkg.price && pkg.delivery_days),
        };
        return checks[index]?.() ?? true;
    };

    const goNext = () => {
        if (!stepIsComplete(step)) {
            toast.error(__('admin.marketplace_fill_required'));
            return;
        }
        setStep(step + 1);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isWizard && step < WIZARD_STEPS.length - 1) {
            goNext();
            return;
        }
        save();
    };

    const addPackage = () => {
        if (data.packages.length >= 3) return;
        setData('packages', [...data.packages, emptyPackage()]);
    };

    const removePackage = (index: number) => {
        if (data.packages.length <= 1) return;
        const newPkgs = [...data.packages];
        newPkgs.splice(index, 1);
        setData('packages', newPkgs);
    };

    const updatePackage = (index: number, field: keyof Package, value: any) => {
        const newPkgs = [...data.packages];
        newPkgs[index] = { ...newPkgs[index], [field]: value };
        setData('packages', newPkgs);
    };

    const addExtra = () => {
        setData('extras', [...data.extras, { title: '', price: '', duration_days: '' }]);
    };

    const updateExtra = (index: number, field: keyof Extra, value: any) => {
        const newExtras = [...data.extras];
        newExtras[index] = { ...newExtras[index], [field]: value };
        setData('extras', newExtras);
    };

    const removeExtra = (index: number) => {
        const newExtras = [...data.extras];
        newExtras.splice(index, 1);
        setData('extras', newExtras);
    };

    const addFaq = () => {
        setData('faq', [...data.faq, { question: '', answer: '' }]);
    };

    const updateFaq = (index: number, field: keyof Faq, value: any) => {
        const newFaqs = [...data.faq];
        newFaqs[index] = { ...newFaqs[index], [field]: value };
        setData('faq', newFaqs);
    };

    const removeFaq = (index: number) => {
        const newFaqs = [...data.faq];
        newFaqs.splice(index, 1);
        setData('faq', newFaqs);
    };

    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const files = Array.from(e.target.files);
            // Limit total images to 5
            const totalImages = data.kept_gallery.length + data.gallery.length + files.length;
            if (totalImages > 5) {
                toast.error(__('general.max_5_images_allowed'));
                return;
            }
            setData('gallery', [...data.gallery, ...files]);
        }
    };

    const removeKeptImage = (path: string) => {
        setData('kept_gallery', data.kept_gallery.filter(p => p !== path));
    };

    const removeNewImage = (index: number) => {
        const newGallery = [...data.gallery];
        newGallery.splice(index, 1);
        setData('gallery', newGallery);
    };

    const handleTranslationChange = (field: string, value: string) => {
        const transField = `${field}_translations` as keyof typeof data;
        setData(transField, {
            ...data[transField] as any,
            [locale]: value
        });
        if (locale === 'en') {
            setData(field as any, value);
        }
    };

    const statusItems = {
        active: __('admin.active'), draft: __('admin.pending'), suspended: __('admin.suspended'), rejected: __('admin.rejected'),
    };
    const referralItems = { fee: __('general.from_fee'), seller: __('general.from_seller') };
    const categoryItems = Object.fromEntries(categories.map((c) => [String(c.id), c.name]));
    const currencyItems = Object.fromEntries(currencies.map((c) => [String(c.id), c.currency]));
    const pageTitle = isWizard ? __('admin.marketplace_create_service') : __('general.edit_service');
    const languageSwitch = (
        <div className="flex gap-2">
            <Button type="button" variant={locale === 'en' ? 'default' : 'outline'} size="sm" onClick={() => setLocale('en')}>
                {__('general.english')}
            </Button>
            <Button type="button" variant={locale === 'ar' ? 'default' : 'outline'} size="sm" onClick={() => setLocale('ar')}>
                {__('general.arabic')}
            </Button>
        </div>
    );

    return (
        <>
            <Head title={isWizard ? pageTitle : __('admin.marketplace_edit_service_title', { title: service.title })} />

            <div className="py-8 bg-slate-50 min-h-screen">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-4">
                            <Link 
                                href={backUrl}
                                className="p-2 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-slate-900 transition-colors shadow-sm"
                                aria-label={__('general.back')}
                                title={__('general.back')}
                            >
                                <ArrowLeft className="w-5 h-5 rtl:rotate-180" />
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900">
                                    {isWizard ? pageTitle : __('admin.marketplace_edit_service_heading', { id: service.id })}
                                </h1>
                                <p className="text-sm text-slate-500">
                                    {isWizard ? __(isAdminMode ? 'admin.marketplace_create_service_hint' : 'marketplace.service_create_hint') : __('general.make_changes_to_the_service_details_or_packages')}
                                </p>
                            </div>
                        </div>
                        {!isWizard && (
                            <Button
                                onClick={handleSubmit}
                                disabled={processing}
                                className="bg-slate-900 hover:bg-slate-900 text-white gap-2"
                            >
                                <Save className="w-4 h-4" />
                                {processing ? __('general.saving') : __('general.save_changes')}
                            </Button>
                        )}
                    </div>

                    {isWizard && (
                        <ol className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap gap-x-5 gap-y-2" aria-label={__('admin.marketplace_step_of', { current: step + 1, total: WIZARD_STEPS.length })}>
                            {WIZARD_STEPS.map((key, index) => (
                                <li key={key} aria-current={index === step ? 'step' : undefined} className={`flex items-center gap-2 text-sm ${index === step ? 'font-bold text-slate-900' : 'text-slate-500'}`}>
                                    <span className={`flex h-6 w-6 items-center justify-center rounded-full text-xs ${index <= step ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500'}`}>{index + 1}</span>
                                    {__(`admin.marketplace_step_${key}`)}
                                </li>
                            ))}
                        </ol>
                    )}

                    {needsReview && (
                        <p className="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4 text-sm">{__(isWizard ? 'marketplace.service_create_review_hint' : 'marketplace.service_edit_review_hint')}</p>
                    )}

                    {adminUrl && (
                        <Link href={adminUrl} className="inline-block text-sm font-semibold text-[#0071e3] hover:underline">{__('marketplace.service_edit_admin_link')}</Link>
                    )}

                    {Object.keys(errors).length > 0 && (
                        <div role="alert" className="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm">
                            <p className="font-semibold">{__('admin.marketplace_fix_errors')}</p>
                            <ul className="mt-1 list-disc ps-5">
                                {Object.entries(errors).map(([field, message]) => <li key={field}>{message as string}</li>)}
                            </ul>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">

                        {showStep(0) && (<>
                        {isAdminMode && (<>
                        {/* Owner and status */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>{__('admin.marketplace_owner')} <span className="text-red-500">*</span></Label>
                                    <UserSearchCombobox value={data.seller_id} onChange={(value) => setData('seller_id', value ?? '')} />
                                    <p className="text-xs text-slate-500">{__('admin.marketplace_owner_hint')}</p>
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('admin.marketplace_service_status')} <span className="text-red-500">*</span></Label>
                                    <Select items={statusItems} value={data.status} onValueChange={(val) => setData('status', val || 'draft')}>
                                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">{__('admin.active')}</SelectItem>
                                            <SelectItem value="draft">{__('admin.pending')}</SelectItem>
                                            <SelectItem value="suspended">{__('admin.suspended')}</SelectItem>
                                            <SelectItem value="rejected">{__('admin.rejected')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                        </>)}

                        {/* Basic Info */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('general.basic_information')}</h2>
                                {languageSwitch}
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">{__('general.service_title')} ({locale.toUpperCase()}) <span className="text-red-500">*</span></Label>
                                    <Input
                                        id="title"
                                        value={data.title_translations[locale] || ''}
                                        onChange={(e) => handleTranslationChange('title', e.target.value)}
                                        dir={locale === 'ar' ? 'rtl' : 'ltr'}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tagline">{__('general.tagline')} ({locale.toUpperCase()})</Label>
                                    <Input
                                        id="tagline"
                                        value={data.tagline_translations[locale] || ''}
                                        onChange={(e) => handleTranslationChange('tagline', e.target.value)}
                                        dir={locale === 'ar' ? 'rtl' : 'ltr'}
                                    />
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="category_id">{__('general.category')} <span className="text-red-500">*</span></Label>
                                    <Select items={categoryItems} value={data.category_id} onValueChange={(val) => setData('category_id', val || '')}>
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder={__('general.select_category')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="description">{__('general.description')} ({locale.toUpperCase()}) <span className="text-red-500">*</span></Label>
                                    <Textarea
                                        id="description"
                                        value={data.description_translations[locale] || ''}
                                        onChange={(e) => handleTranslationChange('description', e.target.value)}
                                        rows={6}
                                        dir={locale === 'ar' ? 'rtl' : 'ltr'}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tags">{__('admin.marketplace_tags')}</Label>
                                    <Input
                                        id="tags"
                                        defaultValue={data.tags.join(', ')}
                                        onChange={(e) => setData('tags', splitList(e.target.value, ','))}
                                    />
                                    <p className="text-xs text-slate-500">{__('admin.marketplace_tags_hint')}</p>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="video_url">{__('admin.marketplace_video_url')}</Label>
                                    <Input
                                        id="video_url"
                                        dir="ltr"
                                        value={data.video_url}
                                        onChange={(e) => setData('video_url', e.target.value)}
                                        placeholder="https://..."
                                    />
                                </div>
                            </div>
                        </div>
                        </>)}

                        {showStep(1) && (<>
                        {/* Packages */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('general.service_packages')}</h2>
                                {data.packages.length < 3 && (
                                    <Button type="button" variant="outline" size="sm" onClick={addPackage} className="gap-1 h-8">
                                        <Plus className="w-3.5 h-3.5" />{__('general.add_package')}
                                    </Button>
                                )}
                            </div>

                            <div className="space-y-6">
                                {data.packages.map((pkg, index) => (
                                    <div key={index} className="p-4 bg-slate-50 border border-slate-200 rounded-lg relative">
                                        {data.packages.length > 1 && (
                                            <Button 
                                                type="button" 
                                                onClick={() => removePackage(index)}
                                                aria-label={__('general.remove_package')}
                                                title={__('general.remove_package')}
                                                className="absolute top-3 end-3 text-slate-400 hover:text-red-500 transition-colors bg-white p-1.5 rounded-md border border-slate-200 shadow-sm"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        )}
                                        
                                        <h3 className="font-semibold text-slate-700 mb-4 pe-10 text-sm uppercase tracking-wider">{__('admin.marketplace_package_number', { number: index + 1 })}</h3>
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>{__('general.package_name')}<span className="text-red-500">*</span></Label>
                                                <Input 
                                                    value={pkg.name} 
                                                    onChange={(e) => updatePackage(index, 'name', e.target.value)} 
                                                />
                                            </div>
                                            
                                            <div className="space-y-2">
                                                <Label>{__('general.price')} <span className="text-red-500">*</span></Label>
                                                <Input 
                                                    type="number" 
                                                    min="1"
                                                    value={pkg.price} 
                                                    onChange={(e) => updatePackage(index, 'price', e.target.value)} 
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>{__('admin.marketplace_currency')} <span className="text-red-500">*</span></Label>
                                                <Select items={currencyItems} value={String(pkg.currency_id ?? defaultCurrencyId)} onValueChange={(val) => updatePackage(index, 'currency_id', val)}>
                                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                                    <SelectContent>
                                                        {currencies.map((currency) => (
                                                            <SelectItem key={currency.id} value={String(currency.id)}>{currency.currency}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label>{__('admin.marketplace_old_price')}</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    value={pkg.old_price ?? ''}
                                                    onChange={(e) => updatePackage(index, 'old_price', e.target.value)}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>{__('admin.marketplace_revisions')}</Label>
                                                <Input
                                                    type="number"
                                                    min="-1"
                                                    value={pkg.revisions ?? ''}
                                                    onChange={(e) => updatePackage(index, 'revisions', e.target.value)}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>{__('general.delivery_days')}<span className="text-red-500">*</span></Label>
                                                <Input 
                                                    type="number" 
                                                    min="1"
                                                    value={pkg.delivery_days} 
                                                    onChange={(e) => updatePackage(index, 'delivery_days', e.target.value)} 
                                                />
                                            </div>

                                            <div className="space-y-2 md:col-span-2">
                                                <Label>{__('general.package_description')}<span className="text-red-500">*</span></Label>
                                                <Textarea 
                                                    value={pkg.description} 
                                                    onChange={(e) => updatePackage(index, 'description', e.target.value)} 
                                                    rows={2}
                                                />
                                            </div>

                                            <div className="space-y-2 md:col-span-2">
                                                <Label htmlFor={`package-${index}-features`}>{__('admin.marketplace_package_features')}</Label>
                                                <Textarea
                                                    id={`package-${index}-features`}
                                                    defaultValue={(pkg.features ?? []).join('\n')}
                                                    onChange={(e) => updatePackage(index, 'features', splitList(e.target.value, '\n'))}
                                                    rows={3}
                                                />
                                                <p className="text-xs text-slate-500">{__('admin.marketplace_package_features_hint')}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        </>)}

                        {showStep(2) && (<>
                        {/* Extras */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('general.service_extras_upsells')}</h2>
                                <Button type="button" variant="outline" size="sm" onClick={addExtra} className="gap-1 h-8">
                                    <Plus className="w-3.5 h-3.5" />{__('general.add_extra')}
                                </Button>
                            </div>
                            
                            <div className="space-y-4">
                                {data.extras.map((extra, index) => (
                                    <div key={index} className="flex flex-wrap items-start gap-4">
                                        <div className="min-w-[200px] flex-1 space-y-2">
                                            <Input 
                                                placeholder={__('general.extra_title')} 
                                                value={extra.title} 
                                                onChange={(e) => updateExtra(index, 'title', e.target.value)} 
                                            />
                                        </div>
                                        <div className="w-32 space-y-2">
                                            <Input 
                                                type="number" 
                                                placeholder={__('general.price')} 
                                                value={extra.price} 
                                                onChange={(e) => updateExtra(index, 'price', e.target.value)} 
                                            />
                                        </div>
                                        <div className="w-32 space-y-2">
                                            <Input 
                                                type="number" 
                                                placeholder={__('general.duration_days')} 
                                                value={extra.duration_days} 
                                                onChange={(e) => updateExtra(index, 'duration_days', e.target.value)} 
                                            />
                                        </div>
                                        <Button type="button" variant="ghost" className="text-red-500 mt-0" onClick={() => removeExtra(index)} aria-label={__('admin.marketplace_remove_extra')} title={__('admin.marketplace_remove_extra')}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                                {data.extras.length === 0 && (
                                    <p className="text-sm text-slate-500 text-center py-4">{__('general.no_extras_added')}</p>
                                )}
                            </div>
                        </div>

                        {/* FAQs */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('general.frequently_asked_questions')}</h2>
                                <Button type="button" variant="outline" size="sm" onClick={addFaq} className="gap-1 h-8">
                                    <Plus className="w-3.5 h-3.5" />{__('general.add_faq')}
                                </Button>
                            </div>
                            
                            <div className="space-y-4">
                                {data.faq.map((f, index) => (
                                    <div key={index} className="p-4 bg-slate-50 border border-slate-200 rounded-lg relative">
                                        <Button 
                                            type="button" 
                                            onClick={() => removeFaq(index)}
                                            aria-label={__('admin.marketplace_remove_faq')}
                                            title={__('admin.marketplace_remove_faq')}
                                            className="absolute top-3 end-3 text-slate-400 hover:text-red-500 transition-colors bg-white p-1.5 rounded-md border border-slate-200 shadow-sm"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                        <div className="space-y-4 pe-10">
                                            <div className="space-y-2">
                                                <Label>{__('general.question')}</Label>
                                                <Input 
                                                    value={f.question} 
                                                    onChange={(e) => updateFaq(index, 'question', e.target.value)} 
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>{__('general.answer')}</Label>
                                                <Textarea 
                                                    value={f.answer} 
                                                    onChange={(e) => updateFaq(index, 'answer', e.target.value)} 
                                                    rows={2}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {data.faq.length === 0 && (
                                    <p className="text-sm text-slate-500 text-center py-4">{__('general.no_faqs_added')}</p>
                                )}
                            </div>
                        </div>

                        </>)}

                        {showStep(3) && (<>
                        {/* Gallery */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <h2 className="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-2">{__('general.gallery')} ({__('admin.marketplace_gallery_max_images', { max: 5 })})</h2>
                            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                {data.kept_gallery.map((path, index) => (
                                    <div key={`kept-${index}`} className="relative aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                                        <img src={path.startsWith('http') ? path : (path.startsWith('/') ? path : `/uploads/${path.replace(/^storage\//, '').replace(/^uploads\//, '')}`)} alt={__('admin.marketplace_gallery_image_alt')} className="w-full h-full object-cover" />
                                        <Button type="button" onClick={() => removeKeptImage(path)} aria-label={__('admin.marketplace_remove_image')} title={__('admin.marketplace_remove_image')} className="absolute top-1 end-1 bg-white p-1 rounded shadow text-red-500">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                                {data.gallery.map((file, index) => (
                                    <div key={`new-${index}`} className="relative aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                                        <img src={URL.createObjectURL(file)} alt={__('admin.marketplace_new_gallery_image_alt')} className="w-full h-full object-cover opacity-70" />
                                        <Button type="button" onClick={() => removeNewImage(index)} aria-label={__('admin.marketplace_remove_image')} title={__('admin.marketplace_remove_image')} className="absolute top-1 end-1 bg-white p-1 rounded shadow text-red-500">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                                {(data.kept_gallery.length + data.gallery.length) < 5 && (
                                    <label className="aspect-video bg-slate-50 rounded-lg border-2 border-dashed border-slate-200 flex flex-col items-center justify-center cursor-pointer hover:bg-slate-100 transition-colors text-slate-500">
                                        <ImageIcon className="w-6 h-6 mb-2" />
                                        <span className="text-xs">{__('general.upload_image')}</span>
                                        <Input type="file" multiple accept="image/*" className="hidden" onChange={handleImageChange} />
                                    </label>
                                )}
                            </div>
                        </div>

                        </>)}

                        {showStep(4) && (<>
                        {/* Auto reply and delivery */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('admin.marketplace_delivery_settings')}</h2>
                                {languageSwitch}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="auto_reply">{__('general.auto_reply_message')} ({locale.toUpperCase()})</Label>
                                <Textarea
                                    id="auto_reply"
                                    value={data.auto_reply_translations[locale] || ''}
                                    onChange={(e) => handleTranslationChange('auto_reply', e.target.value)}
                                    rows={4}
                                    dir={locale === 'ar' ? 'rtl' : 'ltr'}
                                />
                                <p className="text-xs text-slate-500">{__('admin.marketplace_auto_reply_hint')}</p>
                            </div>
                            {isAdminMode && (
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <Label htmlFor="auto_complete" className="cursor-pointer">{__('admin.marketplace_auto_complete')}</Label>
                                    <p className="text-xs text-slate-500 mt-1">{__('admin.marketplace_auto_complete_hint')}</p>
                                </div>
                                <Switch
                                    id="auto_complete"
                                    checked={data.auto_complete}
                                    onCheckedChange={(checked) => setData('auto_complete', checked)}
                                />
                            </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="requirements">{__('admin.marketplace_requirements')}</Label>
                                <Textarea
                                    id="requirements"
                                    defaultValue={data.requirements.join('\n')}
                                    onChange={(e) => setData('requirements', splitList(e.target.value, '\n'))}
                                    rows={3}
                                />
                                <p className="text-xs text-slate-500">{__('admin.marketplace_requirements_hint')}</p>
                            </div>
                        </div>

                        {/* Advanced Settings */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
                            <h2 className="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-2">{__('general.advanced_settings')}</h2>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <Label className="cursor-pointer">{__('general.generate_serials')}</Label>
                                        <Switch 
                                            checked={data.generate_serials} 
                                            onCheckedChange={(checked) => setData('generate_serials', checked)} 
                                        />
                                    </div>
                                    {data.generate_serials && (
                                        <>
                                            <div className="flex items-center justify-between">
                                                <Label className="cursor-pointer">{__('general.allow_random_serial')}</Label>
                                                <Switch 
                                                    checked={data.allow_random_serial} 
                                                    onCheckedChange={(checked) => setData('allow_random_serial', checked)} 
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>{__('general.validity_days')}</Label>
                                                <Input 
                                                    type="number" 
                                                    value={data.validity_days} 
                                                    onChange={(e) => setData('validity_days', e.target.value)} 
                                                />
                                            </div>
                                        </>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <Label className="cursor-pointer">{__('general.is_free_service')}</Label>
                                        <Switch 
                                            checked={data.is_free} 
                                            onCheckedChange={(checked) => setData('is_free', checked)} 
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label>{__('general.service_link')}</Label>
                                        <div className="flex">
                                            <span className="flex items-center justify-center px-3 border border-e-0 border-slate-200 bg-slate-50 text-slate-500 rounded-s-md">
                                                <Globe className="w-4 h-4" />
                                            </span>
                                            <Input 
                                                className="rounded-s-none" 
                                                value={data.service_link} 
                                                onChange={(e) => setData('service_link', e.target.value)} 
                                                placeholder="https://..."
                                            />
                                        </div>
                                    </div>
                                    
                                    {isAdminMode && (
                                    <div className="space-y-2 border-t border-slate-100 pt-4">
                                        <Label>{__('general.referral_commission')}</Label>
                                        <div className="flex gap-4">
                                            <Select items={referralItems} value={data.referral_commission_from} onValueChange={(val) => setData('referral_commission_from', val || '')}>
                                                <SelectTrigger className="w-[140px]">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="fee">{__('general.from_fee')}</SelectItem>
                                                    <SelectItem value="seller">{__('general.from_seller')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <div className="relative flex-1">
                                                <Input 
                                                    type="number" 
                                                    value={data.referral_commission_percentage} 
                                                    onChange={(e) => setData('referral_commission_percentage', e.target.value)} 
                                                    placeholder="%"
                                                    className="pe-8"
                                                />
                                                <span className="absolute end-3 top-1/2 -translate-y-1/2 text-slate-500">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    )}
                                </div>
                            </div>
                        </div>
                        </>)}

                        {isWizard && (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <Button type="button" variant="outline" disabled={step === 0} onClick={() => setStep(step - 1)} className="gap-2">
                                    <ArrowLeft className="w-4 h-4 rtl:rotate-180" /> {__('admin.marketplace_previous_step')}
                                </Button>
                                <span className="text-sm text-slate-500">{__('admin.marketplace_step_of', { current: step + 1, total: WIZARD_STEPS.length })}</span>
                                {step < WIZARD_STEPS.length - 1 ? (
                                    <Button type="submit" className="gap-2">
                                        {__('admin.marketplace_next_step')} <ArrowRight className="w-4 h-4 rtl:rotate-180" />
                                    </Button>
                                ) : (
                                    <Button type="submit" disabled={processing} className="gap-2">
                                        <Save className="w-4 h-4" /> {processing ? __('general.saving') : __('admin.marketplace_save_service')}
                                    </Button>
                                )}
                            </div>
                        )}
                    </form>
                </div>
            </div>
        </>
    );
}
