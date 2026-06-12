import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';

export default function Edit({ service }: { service: any }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        title: service.title,
        subtitle: service.subtitle || '',
        description: service.description || '',
        image: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.website-services.update', service.id));
    };

    return (
        <AdminSidebarLayout header="Edit Service">
            <Head title="Edit Service" />
            <div className="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <Label>Title</Label>
                        <Input value={data.title} onChange={e => setData('title', e.target.value)} required />
                        {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label>Subtitle</Label>
                        <Input value={data.subtitle} onChange={e => setData('subtitle', e.target.value)} />
                        {errors.subtitle && <p className="text-sm text-red-600">{errors.subtitle}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <Textarea value={data.description} onChange={e => setData('description', e.target.value)} rows={4} />
                        {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label>Icon / Image</Label>
                        {service.image_path && (
                            <img src={`/storage/${service.image_path}`} alt={service.title} className="w-16 h-16 rounded object-cover mb-2" />
                        )}
                        <Input type="file" onChange={e => setData('image', e.target.files ? e.target.files[0] : null)} accept="image/*" />
                        <p className="text-xs text-slate-500">Leave empty to keep the current image.</p>
                        {errors.image && <p className="text-sm text-red-600">{errors.image}</p>}
                    </div>
                    <div className="flex justify-end gap-2">
                        <Link href={route('admin.website-services.index')}>
                            <Button variant="outline" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" disabled={processing}>Update</Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
