import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import Checkbox from '@/Components/Checkbox';
import { ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface PointPackage {
    id: number;
    name: string;
    points: number;
    price: number;
    is_active: boolean;
}

interface Props {
    pointPackage: PointPackage;
}

export default function Edit({ pointPackage }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: pointPackage.name,
        points: pointPackage.points,
        price: pointPackage.price,
        is_active: pointPackage.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.point-packages.update', pointPackage.id));
    };

    return (
        <AdminSidebarLayout title={__('admin.edit_point_package')} header={__('admin.edit_point_package')}>
            <Head title={__('admin.edit_point_package')} />

            <div className="max-w-2xl space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" asChild>
                        <Link href={route('admin.point-packages.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900">{__('admin.edit_point_package')}</h1>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="name">{__('admin.package_name')}</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className={errors.name ? 'border-red-500' : ''}
                            />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="points">{__('admin.points_amount')}</Label>
                                <Input
                                    id="points"
                                    type="number"
                                    value={data.points}
                                    onChange={(e) => setData('points', e.target.value)}
                                    className={errors.points ? 'border-red-500' : ''}
                                />
                                {errors.points && <p className="text-xs text-red-500">{errors.points}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="price">{__('admin.price')}</Label>
                                <Input
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    value={data.price}
                                    onChange={(e) => setData('price', e.target.value)}
                                    className={errors.price ? 'border-red-500' : ''}
                                />
                                {errors.price && <p className="text-xs text-red-500">{errors.price}</p>}
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            <Label htmlFor="is_active" className="cursor-pointer">
                                {__('admin.active')}
                            </Label>
                        </div>
                        {errors.is_active && <p className="text-xs text-red-500">{errors.is_active}</p>}

                        <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route('admin.point-packages.index')}>
                                    {__('general.cancel')}
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? __('general.saving') : __('general.save')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
