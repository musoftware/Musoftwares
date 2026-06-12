import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        subtitle: '',
        description: '',
        image: null as File | null,
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
                        <Input type="file" onChange={e => setData('image', e.target.files ? e.target.files[0] : null)} accept="image/*" />
                        {errors.image && <p className="text-sm text-red-600">{errors.image}</p>}
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
