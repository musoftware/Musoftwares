import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title_en: '',
        title_ar: '',
        subtitle_en: '',
        subtitle_ar: '',
        description_en: '',
        description_ar: '',
        image_en: null as File | null,
        image_ar: null as File | null,
        seo_title_en: '',
        seo_title_ar: '',
        seo_description_en: '',
        seo_description_ar: '',
        seo_keywords_en: '',
        seo_keywords_ar: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.website-services.store'));
    };

    return (
        <AdminSidebarLayout header="Create Service">
            <Head title="Create Service" />
            <div className="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Title (English)</Label>
                            <Input value={data.title_en} onChange={e => setData('title_en', e.target.value)} required />
                            {errors.title_en && <p className="text-sm text-red-600">{errors.title_en}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Title (Arabic)</Label>
                            <Input value={data.title_ar} onChange={e => setData('title_ar', e.target.value)} required  />
                            {errors.title_ar && <p className="text-sm text-red-600">{errors.title_ar}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Subtitle (English)</Label>
                            <Input value={data.subtitle_en} onChange={e => setData('subtitle_en', e.target.value)} />
                            {errors.subtitle_en && <p className="text-sm text-red-600">{errors.subtitle_en}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Subtitle (Arabic)</Label>
                            <Input value={data.subtitle_ar} onChange={e => setData('subtitle_ar', e.target.value)}  />
                            {errors.subtitle_ar && <p className="text-sm text-red-600">{errors.subtitle_ar}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Description (English)</Label>
                            <Textarea value={data.description_en} onChange={e => setData('description_en', e.target.value)} rows={4} />
                            {errors.description_en && <p className="text-sm text-red-600">{errors.description_en}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Description (Arabic)</Label>
                            <Textarea value={data.description_ar} onChange={e => setData('description_ar', e.target.value)} rows={4}  />
                            {errors.description_ar && <p className="text-sm text-red-600">{errors.description_ar}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Icon / Image (English)</Label>
                            <Input type="file" onChange={e => setData('image_en', e.target.files ? e.target.files[0] : null)} accept="image/*" />
                            {errors.image_en && <p className="text-sm text-red-600">{errors.image_en}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Icon / Image (Arabic)</Label>
                            <Input type="file" onChange={e => setData('image_ar', e.target.files ? e.target.files[0] : null)} accept="image/*" />
                            {errors.image_ar && <p className="text-sm text-red-600">{errors.image_ar}</p>}
                        </div>
                    </div>
                    
                    <div className="border-t border-slate-200 pt-6 mt-6">
                        <h3 className="text-lg font-semibold text-slate-900 mb-4">Advanced SEO Overrides (Optional)</h3>
                        <p className="text-sm text-slate-500 mb-6">Leave these blank to have our AI automatically generate them based on the service description.</p>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div className="space-y-2">
                                <Label>SEO Title (English)</Label>
                                <Input value={data.seo_title_en} onChange={e => setData('seo_title_en', e.target.value)} maxLength={60} />
                            </div>
                            <div className="space-y-2">
                                <Label>SEO Title (Arabic)</Label>
                                <Input value={data.seo_title_ar} onChange={e => setData('seo_title_ar', e.target.value)}  maxLength={60} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div className="space-y-2">
                                <Label>SEO Description (English)</Label>
                                <Textarea value={data.seo_description_en} onChange={e => setData('seo_description_en', e.target.value)} rows={3} maxLength={160} />
                            </div>
                            <div className="space-y-2">
                                <Label>SEO Description (Arabic)</Label>
                                <Textarea value={data.seo_description_ar} onChange={e => setData('seo_description_ar', e.target.value)} rows={3}  maxLength={160} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>SEO Keywords (English) - Comma separated</Label>
                                <Input value={data.seo_keywords_en} onChange={e => setData('seo_keywords_en', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>SEO Keywords (Arabic) - Comma separated</Label>
                                <Input value={data.seo_keywords_ar} onChange={e => setData('seo_keywords_ar', e.target.value)}  />
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-2">
                        <Link href={route('admin.website-services.index')}>
                            <Button variant="outline" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" disabled={processing}>Save</Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
