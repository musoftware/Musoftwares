import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Package, BarChart3, Users, Settings,
    Plus, X, AlertCircle,
} from 'lucide-react';

interface Props {
    categories: Record<string, string>;
}

const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: BarChart3, href: '/admin', isActive: false },
    { id: 'clients', label: 'Clients', icon: Users, href: '/admin/clients', isActive: false },
    { id: 'tools', label: 'Tools', icon: Package, href: '/admin/tools', isActive: true },
    { id: 'settings', label: 'Settings', icon: Settings, href: '/admin/settings', isActive: false },
];

const OS_OPTIONS = ['windows', 'mac', 'linux'];

export default function AdminToolCreate({ categories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        short_description: '',
        description: '',
        category: Object.keys(categories)[0] ?? 'scraper',
        supported_os: ['windows'] as string[],
        max_devices: 3,
        is_active: true,
        is_featured: false,
        features: [] as string[],
        requirements: [] as string[],
    });

    const [newFeature, setNewFeature] = useState('');
    const [newRequirement, setNewRequirement] = useState('');

    const addItem = (field: 'features' | 'requirements', value: string, clear: () => void) => {
        const trimmed = value.trim();
        if (!trimmed) return;
        setData(field, [...data[field], trimmed]);
        clear();
    };

    const removeItem = (field: 'features' | 'requirements', index: number) => {
        setData(field, data[field].filter((_, i) => i !== index));
    };

    const toggleOs = (os: string) => {
        const current = data.supported_os;
        if (current.includes(os)) {
            if (current.length > 1) setData('supported_os', current.filter(o => o !== os));
        } else {
            setData('supported_os', [...current, os]);
        }
    };

    const autoSlug = (title: string) => {
        return title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    };

    const handleTitleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData(prev => ({ ...prev, title: e.target.value, slug: autoSlug(e.target.value) }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.tools.store'));
    };

    return (
        <WorkspaceLayout
            title="Admin — Create Tool"
            workspaceName="Musoftware Admin"
            tenantId="SYS-ADMIN"
            menuItems={menuItems}
        >
            <Head title="Create Tool — Admin" />

            <div className="space-y-6 max-w-3xl">
                <ModulePageHeader
                    title="Create New Tool"
                    description="Add a new downloadable desktop tool to the marketplace."
                    actions={
                        <Button
                            variant="outline"
                            size="sm"
                            className="text-xs gap-1.5"
                            onClick={() => router.visit(route('admin.tools.index'))}
                        >
                            ← Back to Tools
                        </Button>
                    }
                />

                <form onSubmit={handleSubmit} className="space-y-5">
                    {/* Basic Info */}
                    <OperationalCard title="Basic Information">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="title" className="text-xs font-semibold">Title *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={handleTitleChange}
                                    placeholder="TikTok Scraper Pro"
                                    className="h-9 text-sm"
                                />
                                {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="slug" className="text-xs font-semibold">Slug *</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={e => setData('slug', e.target.value)}
                                    placeholder="tiktok-scraper-pro"
                                    className="h-9 text-sm font-mono"
                                />
                                {errors.slug && <p className="text-xs text-red-500">{errors.slug}</p>}
                            </div>

                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="short_description" className="text-xs font-semibold">Short Description</Label>
                                <Input
                                    id="short_description"
                                    value={data.short_description}
                                    onChange={e => setData('short_description', e.target.value)}
                                    placeholder="One-line description shown on card listings..."
                                    className="h-9 text-sm"
                                    maxLength={250}
                                />
                                {errors.short_description && <p className="text-xs text-red-500">{errors.short_description}</p>}
                            </div>

                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="description" className="text-xs font-semibold">Full Description</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder="Detailed markdown-supported description for the tool detail page..."
                                    rows={5}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                />
                            </div>
                        </div>
                    </OperationalCard>

                    {/* Classification */}
                    <OperationalCard title="Classification &amp; Availability">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Category *</Label>
                                <select
                                    value={data.category}
                                    onChange={e => setData('category', e.target.value)}
                                    className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                >
                                    {Object.entries(categories).map(([value, label]) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </select>
                                {errors.category && <p className="text-xs text-red-500">{errors.category}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Max Devices *</Label>
                                <Input
                                    type="number"
                                    value={data.max_devices}
                                    onChange={e => setData('max_devices', parseInt(e.target.value))}
                                    min={1}
                                    max={100}
                                    className="h-9 text-sm"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label className="text-xs font-semibold">Supported OS *</Label>
                                <div className="flex gap-2 flex-wrap">
                                    {OS_OPTIONS.map(os => (
                                        <button
                                            key={os}
                                            type="button"
                                            onClick={() => toggleOs(os)}
                                            className={`px-3 py-1.5 rounded-lg text-xs font-medium border-2 capitalize transition-all ${
                                                data.supported_os.includes(os)
                                                    ? 'border-slate-900 bg-slate-900 text-white'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                            }`}
                                        >
                                            {os}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center gap-6">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={e => setData('is_active', e.target.checked)}
                                        className="w-4 h-4 rounded border-slate-300"
                                    />
                                    <span className="text-sm font-medium">Active (visible in marketplace)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_featured}
                                        onChange={e => setData('is_featured', e.target.checked)}
                                        className="w-4 h-4 rounded border-slate-300"
                                    />
                                    <span className="text-sm font-medium">Featured</span>
                                </label>
                            </div>
                        </div>
                    </OperationalCard>

                    {/* Features */}
                    <OperationalCard title="Features">
                        <div className="space-y-3">
                            <div className="flex gap-2">
                                <Input
                                    value={newFeature}
                                    onChange={e => setNewFeature(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addItem('features', newFeature, () => setNewFeature('')))}
                                    placeholder="e.g. Proxy rotation built-in"
                                    className="h-9 text-sm flex-1"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-9 gap-1"
                                    onClick={() => addItem('features', newFeature, () => setNewFeature(''))}
                                >
                                    <Plus className="h-3.5 w-3.5" /> Add
                                </Button>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {data.features.map((f, i) => (
                                    <span key={i} className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-medium">
                                        {f}
                                        <button type="button" onClick={() => removeItem('features', i)} className="hover:text-red-500 transition-colors">
                                            <X className="h-3 w-3" />
                                        </button>
                                    </span>
                                ))}
                                {data.features.length === 0 && (
                                    <p className="text-xs text-text-muted italic">No features added yet. Press Enter or click Add.</p>
                                )}
                            </div>
                        </div>
                    </OperationalCard>

                    {/* Requirements */}
                    <OperationalCard title="System Requirements">
                        <div className="space-y-3">
                            <div className="flex gap-2">
                                <Input
                                    value={newRequirement}
                                    onChange={e => setNewRequirement(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addItem('requirements', newRequirement, () => setNewRequirement('')))}
                                    placeholder="e.g. Windows 10 / macOS 12+"
                                    className="h-9 text-sm flex-1"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-9 gap-1"
                                    onClick={() => addItem('requirements', newRequirement, () => setNewRequirement(''))}
                                >
                                    <Plus className="h-3.5 w-3.5" /> Add
                                </Button>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {data.requirements.map((r, i) => (
                                    <span key={i} className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-xs font-medium">
                                        {r}
                                        <button type="button" onClick={() => removeItem('requirements', i)} className="hover:text-red-500 transition-colors">
                                            <X className="h-3 w-3" />
                                        </button>
                                    </span>
                                ))}
                                {data.requirements.length === 0 && (
                                    <p className="text-xs text-text-muted italic">No requirements added yet.</p>
                                )}
                            </div>
                        </div>
                    </OperationalCard>

                    {/* Submit */}
                    <div className="flex items-center justify-end gap-3 pb-4">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('admin.tools.index'))}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing}
                            className="gap-1.5 bg-slate-900 hover:bg-slate-800 text-white"
                        >
                            {processing ? 'Creating...' : 'Create Tool'}
                        </Button>
                    </div>
                </form>
            </div>
        </WorkspaceLayout>
    );
}
