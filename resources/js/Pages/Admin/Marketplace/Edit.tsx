import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { ArrowLeft, Save, Plus, Trash2, Globe, Image as ImageIcon } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
}

interface Package {
    id?: number;
    name: string;
    description: string;
    price: number | string;
    currency_id: number;
    delivery_days: number | string;
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
    title: string;
    tagline?: string;
    description: string;
    auto_reply?: string;
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
    service: Service;
    categories: Category[];
}

export default function Edit({ auth, service, categories }: Props) {
    const [locale, setLocale] = useState<'en' | 'ar'>('en');

    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        title: service.title || '',
        title_translations: service.title_translations || { en: '', ar: '' },
        tagline: service.tagline || '',
        tagline_translations: service.tagline_translations || { en: '', ar: '' },
        description: service.description || '',
        description_translations: service.description_translations || { en: '', ar: '' },
        auto_reply: service.auto_reply || '',
        auto_reply_translations: service.auto_reply_translations || { en: '', ar: '' },
        category_id: service.category_id ? String(service.category_id) : '',
        status: service.status || 'draft',
        packages: service.packages && service.packages.length > 0 ? service.packages : [{
            name: '', description: '', price: '', currency_id: 1, delivery_days: ''
        }],
        extras: service.extras || [],
        faq: service.faq || [],
        kept_gallery: service.gallery || [],
        gallery: [] as File[],
        is_free: service.is_free || false,
        service_link: service.service_link || '',
        generate_serials: service.generate_serials || false,
        allow_random_serial: service.allow_random_serial || false,
        validity_days: service.validity_days || '',
        referral_commission_from: service.referral_commission_from || 'fee',
        referral_commission_percentage: service.referral_commission_percentage || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // Use post with _method=put to handle file uploads properly in Laravel
        post(route('admin.marketplace.services.update', service.id));
    };

    const addPackage = () => {
        if (data.packages.length >= 3) return;
        setData('packages', [...data.packages, {
            name: '', description: '', price: '', currency_id: 1, delivery_days: ''
        }]);
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
                alert(__('general.max_5_images_allowed'));
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

    return (
        <AdminSidebarLayout user={auth?.user} title={__('general.edit_service')} header="Edit Service">
            <Head title={`Edit Service: ${service.title}`} />

            <div className="py-8 bg-slate-50 min-h-screen">
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <Link 
                                href={route('admin.marketplace.services.all')}
                                className="p-2 bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-slate-900 transition-colors shadow-sm"
                            >
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900">Edit Service #{service.id}</h1>
                                <p className="text-sm text-slate-500">{__('general.make_changes_to_the_service_details_or_packages')}</p>
                            </div>
                        </div>
                        <Button 
                            onClick={handleSubmit} 
                            disabled={processing}
                            className="bg-slate-900 hover:bg-slate-900 text-white gap-2"
                        >
                            <Save className="w-4 h-4" />
                            {processing ? __('general.saving') : __('general.save_changes')}
                        </Button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        
                        {/* Basic Info */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('general.basic_information')}</h2>
                                <div className="flex gap-2">
                                    <Button 
                                        type="button" 
                                        variant={locale === 'en' ? 'default' : 'outline'} 
                                        size="sm" 
                                        onClick={() => setLocale('en')}
                                    >
                                        {__('general.english')}</Button>
                                    <Button 
                                        type="button" 
                                        variant={locale === 'ar' ? 'default' : 'outline'} 
                                        size="sm" 
                                        onClick={() => setLocale('ar')}
                                    >
                                        العربية
                                    </Button>
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">{__('general.service_title')} ({locale.toUpperCase()}) <span className="text-red-500">*</span></Label>
                                    <Input
                                        id="title"
                                        value={data.title_translations[locale] || ''}
                                        onChange={(e) => handleTranslationChange('title', e.target.value)}
                                        dir={__('ltr')}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tagline">{__('general.tagline')} ({locale.toUpperCase()})</Label>
                                    <Input
                                        id="tagline"
                                        value={data.tagline_translations[locale] || ''}
                                        onChange={(e) => handleTranslationChange('tagline', e.target.value)}
                                        dir={__('ltr')}
                                    />
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="category_id">{__('general.category')} <span className="text-red-500">*</span></Label>
                                    <Select value={data.category_id} onValueChange={(val) => setData('category_id', val || '')}>
                                        <SelectTrigger>
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
                                        dir={__('ltr')}
                                    />
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="auto_reply">{__('general.auto_reply_message')} ({locale.toUpperCase()})</Label>
                                    <Textarea
                                        id="auto_reply"
                                        value={data.auto_reply_translations[locale] || ''}
                                        onChange={(e) => handleTranslationChange('auto_reply', e.target.value)}
                                        rows={3}
                                        dir={__('ltr')}
                                    />
                                </div>
                            </div>
                        </div>

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
                                                className="absolute top-3 end-3 text-slate-400 hover:text-red-500 transition-colors bg-white p-1.5 rounded-md border border-slate-200 shadow-sm"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        )}
                                        
                                        <h3 className="font-semibold text-slate-700 mb-4 pe-10 text-sm uppercase tracking-wider">Package {index + 1}</h3>
                                        
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
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

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
                                    <div key={index} className="flex items-start gap-4">
                                        <div className="flex-1 space-y-2">
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
                                        <Button type="button" variant="ghost" className="text-red-500 mt-0" onClick={() => removeExtra(index)}>
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

                        {/* Gallery */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <h2 className="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-2">{__('general.gallery')} (Max 5)</h2>
                            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                {data.kept_gallery.map((path, index) => (
                                    <div key={`kept-${index}`} className="relative aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                                        <img src={path.startsWith('http') ? path : (path.startsWith('/') ? path : (path.startsWith('services/') ? `/uploads/${path}` : `/${path}`))} alt="Gallery" className="w-full h-full object-cover" />
                                        <Button type="button" onClick={() => removeKeptImage(path)} className="absolute top-1 end-1 bg-white p-1 rounded shadow text-red-500">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                                {data.gallery.map((file, index) => (
                                    <div key={`new-${index}`} className="relative aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                                        <img src={URL.createObjectURL(file)} alt="New Gallery" className="w-full h-full object-cover opacity-70" />
                                        <Button type="button" onClick={() => removeNewImage(index)} className="absolute top-1 end-1 bg-white p-1 rounded shadow text-red-500">
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
                                    
                                    <div className="space-y-2 border-t border-slate-100 pt-4">
                                        <Label>{__('general.referral_commission')}</Label>
                                        <div className="flex gap-4">
                                            <Select value={data.referral_commission_from} onValueChange={(val) => setData('referral_commission_from', val || '')}>
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
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
