import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeft, Save } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Create({ services }) {
    const { data, setData, post, processing, errors, transform } = useForm({
        title: '',
        slug: '',
        content: '',
        excerpt: '',
        language: 'en',
        service_id: 'none',
        meta_title: '',
        meta_description: '',
        is_published: false,
    });

    transform((data) => ({
        ...data,
        service_id: data.service_id === 'none' ? null : data.service_id,
    }));

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.blog-articles.store'));
    };

    return (
        <AdminSidebarLayout title={__('general.create_article')} header="Add New Blog Article">
            <div className="mb-6 flex items-center">
                <Link href={route('admin.blog-articles.index')}>
                    <Button variant="ghost" className="gap-2">
                        <ArrowLeft className="h-4 w-4" />{__('general.back_to_articles')}</Button>
                </Link>
            </div>

            <div className="bg-white rounded-lg shadow border border-gray-200 p-6 max-w-4xl">
                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="title">Title *</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder={__('general.article_title')}
                                required
                            />
                            {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                placeholder={__('general.leave_blank_to_auto_generate')}
                            />
                            {errors.slug && <p className="text-sm text-red-600">{errors.slug}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="content">Content *</Label>
                        <Textarea
                            id="content"
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder={__('general.article_content_goes_here')}
                            className="min-h-[200px]"
                            required
                        />
                        {errors.content && <p className="text-sm text-red-600">{errors.content}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="excerpt">Excerpt</Label>
                        <Textarea
                            id="excerpt"
                            value={data.excerpt}
                            onChange={(e) => setData('excerpt', e.target.value)}
                            placeholder={__('general.short_summary_of_the_article')}
                            className="min-h-[80px]"
                        />
                        {errors.excerpt && <p className="text-sm text-red-600">{errors.excerpt}</p>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="language">Language *</Label>
                            <Select value={data.language} onValueChange={(val) => setData('language', val)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={__('general.select_language')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="en">English</SelectItem>
                                    <SelectItem value="ar">Arabic</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.language && <p className="text-sm text-red-600">{errors.language}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="service_id">{__('general.related_service')}</Label>
                            <Select value={data.service_id?.toString() || "none"} onValueChange={(val) => setData('service_id', val)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={__('general.select_a_service_optional')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">-- No Service --</SelectItem>
                                    {services?.map(service => (
                                        <SelectItem key={service.id} value={service.id.toString()}>
                                            {service.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.service_id && <p className="text-sm text-red-600">{errors.service_id}</p>}
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="meta_title">{__('general.meta_title')}</Label>
                            <Input
                                id="meta_title"
                                value={data.meta_title}
                                onChange={(e) => setData('meta_title', e.target.value)}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="meta_description">{__('general.meta_description')}</Label>
                            <Input
                                id="meta_description"
                                value={data.meta_description}
                                onChange={(e) => setData('meta_description', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex items-center space-x-2 pt-4">
                        <Switch
                            id="is_published"
                            checked={data.is_published}
                            onCheckedChange={(checked) => setData('is_published', checked)}
                        />
                        <Label htmlFor="is_published">{__('general.publish_immediately')}</Label>
                    </div>

                    <div className="flex justify-end pt-4 border-t">
                        <Button type="submit" disabled={processing} className="gap-2">
                            <Save className="h-4 w-4" />
                            {processing ? 'Saving...' : 'Save Article'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
