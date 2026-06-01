import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { ArrowLeft, Save, Plus, Trash2 } from 'lucide-react';

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

interface Service {
    id: number;
    title: string;
    description: string;
    category_id: number;
    status: string;
    packages: Package[];
}

interface Props {
    auth: any;
    service: Service;
    categories: Category[];
}

export default function Edit({ auth, service, categories }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        title: service.title || '',
        description: service.description || '',
        category_id: service.category_id ? String(service.category_id) : '',
        status: service.status || 'draft',
        packages: service.packages && service.packages.length > 0 ? service.packages : [{
            name: '', description: '', price: '', currency_id: 1, delivery_days: ''
        }],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.marketplace.services.update', service.id));
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

    return (
        <AdminSidebarLayout user={auth?.user} title={__('general.edit_service')} header="Edit Service">
            <Head title={`Edit Service: ${service.title}`} />

            <div className="py-8 bg-slate-50 min-h-screen">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
                    
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
                            className="bg-indigo-600 hover:bg-indigo-700 text-white gap-2"
                        >
                            <Save className="w-4 h-4" />
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        
                        {/* Basic Info */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <h2 className="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-2">{__('general.basic_information')}</h2>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">{__('general.service_title')}<span className="text-red-500">*</span></Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className={errors.title ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status">Status <span className="text-red-500">*</span></Label>
                                    <Select value={data.status} onValueChange={(val) => setData('status', val)}>
                                        <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                                            <SelectValue placeholder={__('general.select_status')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="draft">Pending (Draft)</SelectItem>
                                            <SelectItem value="suspended">Suspended</SelectItem>
                                            <SelectItem value="rejected">Rejected</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="category_id">Category <span className="text-red-500">*</span></Label>
                                    <Select value={data.category_id} onValueChange={(val) => setData('category_id', val)}>
                                        <SelectTrigger className={errors.category_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder={__('general.select_category')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.category_id && <p className="text-xs text-red-500">{errors.category_id}</p>}
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="description">Description <span className="text-red-500">*</span></Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={6}
                                        className={errors.description ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Packages */}
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h2 className="text-lg font-semibold text-slate-900">{__('general.service_packages')}</h2>
                                {data.packages.length < 3 && (
                                    <Button type="button" variant="outline" size="sm" onClick={addPackage} className="gap-1 h-8">
                                        <Plus className="w-3.5 h-3.5" />{__('general.add_package')}</Button>
                                )}
                            </div>

                            {errors.packages && <p className="text-sm text-red-500 font-medium">{errors.packages}</p>}

                            <div className="space-y-6">
                                {data.packages.map((pkg, index) => (
                                    <div key={index} className="p-4 bg-slate-50 border border-slate-200 rounded-lg relative">
                                        {data.packages.length > 1 && (
                                            <button 
                                                type="button" 
                                                onClick={() => removePackage(index)}
                                                className="absolute top-3 right-3 text-slate-400 hover:text-red-500 transition-colors bg-white p-1.5 rounded-md border border-slate-200 shadow-sm"
                                                title={__('general.remove_package')}
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        )}
                                        
                                        <h3 className="font-semibold text-slate-700 mb-4 pr-10 text-sm uppercase tracking-wider">Package {index + 1}</h3>
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>{__('general.package_name')}<span className="text-red-500">*</span></Label>
                                                <Input 
                                                    value={pkg.name} 
                                                    onChange={(e) => updatePackage(index, 'name', e.target.value)} 
                                                    placeholder={__('general.e_g_basic_logo')}
                                                    required
                                                />
                                                {/* @ts-ignore */}
                                                {errors[`packages.${index}.name`] && <p className="text-xs text-red-500">{errors[`packages.${index}.name`]}</p>}
                                            </div>
                                            
                                            <div className="space-y-2">
                                                <Label>Price <span className="text-red-500">*</span></Label>
                                                <div className="flex relative">
                                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">$</span>
                                                    <Input 
                                                        type="number" 
                                                        min="1"
                                                        value={pkg.price} 
                                                        onChange={(e) => updatePackage(index, 'price', e.target.value)} 
                                                        className="pl-7"
                                                        required
                                                    />
                                                </div>
                                                {/* @ts-ignore */}
                                                {errors[`packages.${index}.price`] && <p className="text-xs text-red-500">{errors[`packages.${index}.price`]}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label>{__('general.delivery_days')}<span className="text-red-500">*</span></Label>
                                                <Input 
                                                    type="number" 
                                                    min="1"
                                                    value={pkg.delivery_days} 
                                                    onChange={(e) => updatePackage(index, 'delivery_days', e.target.value)} 
                                                    required
                                                />
                                                {/* @ts-ignore */}
                                                {errors[`packages.${index}.delivery_days`] && <p className="text-xs text-red-500">{errors[`packages.${index}.delivery_days`]}</p>}
                                            </div>

                                            <div className="space-y-2 md:col-span-2">
                                                <Label>{__('general.package_description')}<span className="text-red-500">*</span></Label>
                                                <Textarea 
                                                    value={pkg.description} 
                                                    onChange={(e) => updatePackage(index, 'description', e.target.value)} 
                                                    rows={2}
                                                    required
                                                />
                                                {/* @ts-ignore */}
                                                {errors[`packages.${index}.description`] && <p className="text-xs text-red-500">{errors[`packages.${index}.description`]}</p>}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
