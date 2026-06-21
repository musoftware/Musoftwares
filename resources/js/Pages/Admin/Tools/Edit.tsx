import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { __ } from '@/lib/i18n';
import {
    Package, BarChart3, Users, Settings,
    Plus, X, Upload, CheckCircle2,
} from 'lucide-react';

interface Tool {
    id: number;
    slug: string;
    title: string;
    description: string;
    short_description: string;
    category: string;
    supported_os: string[];
    icon_url: string | null;
    features: string[];
    requirements: string[];
    is_active: boolean;
    is_featured: boolean;
    max_subscription_months: number | null;
}

interface Props {
    tool: Tool;
    categories: Record<string, string>;
}

const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: BarChart3, href: '/admin', isActive: false },
    { id: 'clients', label: 'Clients', icon: Users, href: '/admin/clients', isActive: false },
    { id: 'tools', label: 'Tools', icon: Package, href: '/admin/tools', isActive: true },
    { id: 'settings', label: 'Settings', icon: Settings, href: '/admin/settings', isActive: false },
];

const OS_OPTIONS = ['windows', 'mac'];

export default function AdminToolEdit({ tool, categories }: Props) {
    // ─── Tool Update Form ───────────────────────────────────────────────────────
    const { data, setData, put, processing, errors } = useForm({
        title: tool.title,
        short_description: tool.short_description ?? '',
        description: tool.description ?? '',
        category: tool.category,
        supported_os: tool.supported_os ?? ['windows'],
        
        is_active: tool.is_active,
        is_featured: tool.is_featured,
        features: tool.features ?? [],
        requirements: tool.requirements ?? [],
        max_subscription_months: tool.max_subscription_months ?? null as number | null,
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

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.tools.update', tool.id));
    };

    // ─── Version Upload Form ────────────────────────────────────────────────────
    const versionForm = useForm({
        version: '',
        changelog: '',
        file: null as File | null,
        is_beta: false,
        set_latest: true,
    });

    const handleVersionUpload = (e: React.FormEvent) => {
        e.preventDefault();
        versionForm.post(route('admin.tools.upload-version', tool.id), {
            forceFormData: true,
        });
    };

    return (
        <WorkspaceLayout
            title={`Admin — Edit: ${tool.title}`}
            workspaceName="Musoftware Admin"
            tenantId="SYS-ADMIN"
            menuItems={menuItems}
        >
            <Head title={`Edit ${tool.title} — Admin`} />

            <div className="space-y-6 max-w-3xl">
                <ModulePageHeader
                    title={`Edit: ${tool.title}`}
                    description={`Manage tool configuration and release versions.`}
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

                {/* Tool Edit Form */}
                <form onSubmit={handleUpdate} className="space-y-5">
                    <OperationalCard title={__('general.basic_information')}>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="title" className="text-xs font-semibold">Title *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    className="h-9 text-sm"
                                />
                                {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="short_description" className="text-xs font-semibold">{__('general.short_description')}</Label>
                                <Input
                                    id="short_description"
                                    value={data.short_description}
                                    onChange={e => setData('short_description', e.target.value)}
                                    className="h-9 text-sm"
                                    maxLength={250}
                                />
                            </div>

                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="description" className="text-xs font-semibold">{__('general.full_description')}</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    rows={5}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                />
                            </div>
                        </div>
                    </OperationalCard>

                    <OperationalCard title={__('general.classification_amp_availability')}>
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
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">{__('general.max_devices')}</Label>
                                <Input
                                    type="number"
                                    min={1} max={100}
                                    className="h-9 text-sm"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label className="text-xs font-semibold">{__('general.supported_os')}</Label>
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

                            <div className="flex items-center gap-6 mt-1">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={e => setData('is_active', e.target.checked)}
                                        className="w-4 h-4 rounded border-slate-300"
                                    />
                                    <span className="text-sm font-medium">Active</span>
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

                            <div className="space-y-1.5 sm:col-span-2">
                                <Label className="text-xs font-semibold">{__('general.max_subscription_duration')}</Label>
                                <p className="text-xs text-text-muted mb-1.5">{__('general.restrict_how_long_users_can_subscribe_set_to_1_month_to_prevent_yearly_subscriptions')}</p>
                                <select
                                    value={data.max_subscription_months ?? ''}
                                    onChange={e => {
                                        const val = e.target.value;
                                        setData('max_subscription_months', val === '' ? null : parseInt(val));
                                    }}
                                    className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                >
                                    <option value="">No Limit (Monthly & Yearly)</option>
                                    <option value="1">1 Month Only (Block Yearly)</option>
                                </select>
                            </div>
                        </div>
                    </OperationalCard>

                    <OperationalCard title="Features">
                        <div className="space-y-3">
                            <div className="flex gap-2">
                                <Input
                                    value={newFeature}
                                    onChange={e => setNewFeature(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addItem('features', newFeature, () => setNewFeature('')))}
                                    placeholder={__('general.e_g_proxy_rotation_built_in')}
                                    className="h-9 text-sm flex-1"
                                />
                                <Button type="button" variant="outline" size="sm" className="h-9 gap-1"
                                    onClick={() => addItem('features', newFeature, () => setNewFeature(''))}>
                                    <Plus className="h-3.5 w-3.5" /> Add
                                </Button>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {data.features.map((f, i) => (
                                    <span key={i} className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-medium">
                                        {f}
                                        <button type="button" onClick={() => removeItem('features', i)} className="hover:text-red-500 transition-colors"><X className="h-3 w-3" /></button>
                                    </span>
                                ))}
                                {data.features.length === 0 && <p className="text-xs text-text-muted italic">{__('general.no_features_added')}</p>}
                            </div>
                        </div>
                    </OperationalCard>

                    <OperationalCard title={__('general.system_requirements')}>
                        <div className="space-y-3">
                            <div className="flex gap-2">
                                <Input
                                    value={newRequirement}
                                    onChange={e => setNewRequirement(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addItem('requirements', newRequirement, () => setNewRequirement('')))}
                                    placeholder={__('general.e_g_windows_10_macos_12')}
                                    className="h-9 text-sm flex-1"
                                />
                                <Button type="button" variant="outline" size="sm" className="h-9 gap-1"
                                    onClick={() => addItem('requirements', newRequirement, () => setNewRequirement(''))}>
                                    <Plus className="h-3.5 w-3.5" /> Add
                                </Button>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {data.requirements.map((r, i) => (
                                    <span key={i} className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-xs font-medium">
                                        {r}
                                        <button type="button" onClick={() => removeItem('requirements', i)} className="hover:text-red-500 transition-colors"><X className="h-3 w-3" /></button>
                                    </span>
                                ))}
                                {data.requirements.length === 0 && <p className="text-xs text-text-muted italic">{__('general.no_requirements_added')}</p>}
                            </div>
                        </div>
                    </OperationalCard>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" size="sm" onClick={() => router.visit(route('admin.tools.index'))}>
                            Cancel
                        </Button>
                        <Button type="submit" size="sm" disabled={processing} className="gap-1.5 bg-slate-900 hover:bg-slate-800 text-white">
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>

                {/* Version Upload */}
                <form onSubmit={handleVersionUpload}>
                    <OperationalCard title={__('general.upload_new_release_version')}>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="version" className="text-xs font-semibold">Version Number *</Label>
                                <Input
                                    id="version"
                                    value={versionForm.data.version}
                                    onChange={e => versionForm.setData('version', e.target.value)}
                                    placeholder="2.1.5"
                                    className="h-9 text-sm font-mono"
                                />
                                {versionForm.errors.version && <p className="text-xs text-red-500">{versionForm.errors.version}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="file" className="text-xs font-semibold">Release File (.zip / .exe) *</Label>
                                <input
                                    id="file"
                                    type="file"
                                    accept=".zip,.exe"
                                    onChange={e => versionForm.setData('file', e.target.files?.[0] ?? null)}
                                    className="block w-full text-xs text-slate-600 file:me-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                                />
                                {versionForm.errors.file && <p className="text-xs text-red-500">{versionForm.errors.file}</p>}
                            </div>

                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="changelog" className="text-xs font-semibold">Changelog</Label>
                                <textarea
                                    id="changelog"
                                    value={versionForm.data.changelog}
                                    onChange={e => versionForm.setData('changelog', e.target.value)}
                                    placeholder="What's new in this release..."
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                />
                            </div>

                            <div className="flex items-center gap-5">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={versionForm.data.is_beta}
                                        onChange={e => versionForm.setData('is_beta', e.target.checked)}
                                        className="w-4 h-4 rounded border-slate-300"
                                    />
                                    <span className="text-sm font-medium">{__('general.beta_release')}</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={versionForm.data.set_latest}
                                        onChange={e => versionForm.setData('set_latest', e.target.checked)}
                                        className="w-4 h-4 rounded border-slate-300"
                                    />
                                    <span className="text-sm font-medium">{__('general.set_as_latest')}</span>
                                </label>
                            </div>
                        </div>

                        <div className="flex justify-end pt-2 mt-2 border-t border-border">
                            <Button
                                type="submit"
                                size="sm"
                                disabled={versionForm.processing}
                                className="gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                <Upload className="h-3.5 w-3.5" />
                                {versionForm.processing ? 'Uploading...' : 'Upload Version'}
                            </Button>
                        </div>
                    </OperationalCard>
                </form>
            </div>
        </WorkspaceLayout>
    );
}
