import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Button } from '@/Components/ui/button';
import {
    Package, Plus, Edit2, Trash2, ToggleLeft, ToggleRight,
    Download, Users, Star, Tag, BarChart3, Settings,
    Layers, ChevronLeft, ChevronRight, Clock,
} from 'lucide-react';
import { Switch } from '@/Components/ui/switch';
import { __ } from '@/lib/i18n';

interface Tool {
    id: number;
    slug: string;
    title: string;
    category: string;
    icon_url: string | null;
    current_version: string;
    is_active: boolean;
    is_featured: boolean;
    max_subscription_months: number | null;
    subscriptions: number;
    downloads: number;
    deleted_at: string | null;
}

interface PaginatedTools {
    data: Tool[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    tools: PaginatedTools;
    categories: Record<string, string>;
}

const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: BarChart3, href: '/admin', isActive: false },
    { id: 'clients', label: 'Clients', icon: Users, href: '/admin/clients', isActive: false },
    { id: 'tools', label: 'Tools', icon: Package, href: '/admin/tools', isActive: true },
    { id: 'settings', label: 'Settings', icon: Settings, href: '/admin/settings', isActive: false },
];

export default function AdminToolsIndex({ tools, categories }: Props) {
    const [confirmDelete, setConfirmDelete] = useState<number | null>(null);

    const handleDelete = (id: number) => {
        router.delete(route('admin.tools.destroy', id), {
            onSuccess: () => setConfirmDelete(null),
        });
    };

    const toggleActive = (tool: Tool) => {
        router.put(route('admin.tools.update', tool.id), {
            ...tool,
            is_active: !tool.is_active,
        }, { preserveScroll: true });
    };

    return (
        <WorkspaceLayout
            title={__('general.admin_tools')}
            workspaceName="Musoftware Admin"
            tenantId="SYS-ADMIN"
            menuItems={menuItems}
        >
            <Head title={__('general.tools_marketplace_admin')} />

            <div className="space-y-6">
                <ModulePageHeader
                    title={__('general.tools_marketplace')}
                    description={__('general.manage_downloadable_desktop_tools_pricing_plans_and_release_versions')}
                    actions={
                        <Link href={route('admin.tools.create')}>
                            <Button size="sm" className="gap-1.5 text-xs bg-slate-900 hover:bg-slate-800 text-white">
                                <Plus className="h-3.5 w-3.5" />{__('general.new_tool')}</Button>
                        </Link>
                    }
                />

                <OperationalCard noPadding>
                    {tools.data.length === 0 ? (
                        <EmptyState
                            icon={Package}
                            title={__('general.no_tools_yet')}
                            description={__('general.create_your_first_downloadable_tool_to_populate_the_marketplace')}
                            action={route('admin.tools.create')}
                            actionLabel="Create First Tool"
                        />
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border bg-surface-raised">
                                            <th className="text-left px-5 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Tool</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider hidden md:table-cell">Category</th>
                                            <th className="text-left px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider hidden lg:table-cell">Version</th>
                                            <th className="text-center px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider hidden sm:table-cell">Subs</th>
                                            <th className="text-center px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider hidden sm:table-cell">{__('general.active_users')}</th>
                                            <th className="text-center px-4 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                                            <th className="text-right px-5 py-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/50">
                                        {tools.data.map(tool => (
                                            <tr key={tool.id} className={`hover:bg-surface-raised/50 transition-colors ${tool.deleted_at ? 'opacity-50' : ''}`}>
                                                {/* Tool */}
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                                            {tool.icon_url ? (
                                                                <img src={tool.icon_url} alt={tool.title} className="w-full h-full object-cover rounded-lg" />
                                                            ) : (
                                                                <Package className="h-4 w-4 text-slate-400" />
                                                            )}
                                                        </div>
                                                        <div>
                                                            <div className="flex items-center gap-1.5">
                                                                <p className="font-semibold text-text-primary text-sm leading-tight">{tool.title}</p>
                                                                {tool.max_subscription_months === 1 && (
                                                                    <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-amber-50 border border-amber-200 text-amber-700 rounded text-[10px] font-semibold" title={__('general.monthly_subscriptions_only')}>
                                                                        <Clock className="h-2.5 w-2.5" />{__('general.monthly_only')}</span>
                                                                )}
                                                            </div>
                                                            <p className="text-xs text-text-muted mt-0.5">{tool.slug}</p>
                                                        </div>
                                                        {tool.is_featured && (
                                                            <Star className="h-3.5 w-3.5 text-amber-400 fill-amber-400 flex-shrink-0" />
                                                        )}
                                                    </div>
                                                </td>

                                                {/* Category */}
                                                <td className="px-4 py-3.5 hidden md:table-cell">
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-xs font-medium">
                                                        <Tag className="h-3 w-3" />
                                                        {categories[tool.category] ?? tool.category}
                                                    </span>
                                                </td>

                                                {/* Version */}
                                                <td className="px-4 py-3.5 hidden lg:table-cell">
                                                    <span className="font-mono text-xs text-text-muted">v{tool.current_version}</span>
                                                </td>

                                                {/* Subs */}
                                                <td className="px-4 py-3.5 text-center hidden sm:table-cell">
                                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-text-primary">
                                                        <Users className="h-3 w-3 text-text-muted" />
                                                        {tool.subscriptions.toLocaleString()}
                                                    </span>
                                                </td>

                                                {/* Downloads */}
                                                <td className="px-4 py-3.5 text-center hidden sm:table-cell">
                                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-text-primary">
                                                        <Download className="h-3 w-3 text-text-muted" />
                                                        {tool.downloads.toLocaleString()}
                                                    </span>
                                                </td>

                                                {/* Status */}
                                                <td className="px-4 py-3.5 text-center">
                                                    {tool.deleted_at ? (
                                                        <StatusBadge status="danger" label="Archived" size="sm" />
                                                    ) : (
                                                        <StatusBadge
                                                            status={tool.is_active ? 'success' : 'neutral'}
                                                            label={tool.is_active ? 'Active' : 'Hidden'}
                                                            size="sm"
                                                        />
                                                    )}
                                                </td>

                                                {/* Actions */}
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        {!tool.deleted_at && (
                                                            <>
                                                                <Switch
                                                                    checked={tool.is_active}
                                                                    onCheckedChange={() => toggleActive(tool)}
                                                                    title={tool.is_active ? 'Deactivate' : 'Activate'}
                                                                />

                                                                <Link
                                                                    href={route('admin.tools.edit', tool.id)}
                                                                    className="p-1.5 rounded-md text-text-muted hover:text-text-primary hover:bg-surface-raised transition-colors"
                                                                    title="Edit"
                                                                >
                                                                    <Edit2 className="h-3.5 w-3.5" />
                                                                </Link>

                                                                {confirmDelete === tool.id ? (
                                                                    <div className="flex items-center gap-1">
                                                                        <button
                                                                            onClick={() => handleDelete(tool.id)}
                                                                            className="px-2 py-1 text-xs bg-red-500 text-white rounded-md font-medium hover:bg-red-600 transition-colors"
                                                                        >
                                                                            Confirm
                                                                        </button>
                                                                        <button
                                                                            onClick={() => setConfirmDelete(null)}
                                                                            className="px-2 py-1 text-xs border border-border rounded-md font-medium hover:bg-surface-raised transition-colors"
                                                                        >
                                                                            Cancel
                                                                        </button>
                                                                    </div>
                                                                ) : (
                                                                    <button
                                                                        onClick={() => setConfirmDelete(tool.id)}
                                                                        className="p-1.5 rounded-md text-text-muted hover:text-red-500 hover:bg-red-50 transition-colors"
                                                                        title="Archive"
                                                                    >
                                                                        <Trash2 className="h-3.5 w-3.5" />
                                                                    </button>
                                                                )}
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {tools.last_page > 1 && (
                                <div className="px-5 py-3 border-t border-border flex items-center justify-between">
                                    <p className="text-xs text-text-muted">
                                        Showing {tools.from}–{tools.to} of {tools.total} tools
                                    </p>
                                    <div className="flex items-center gap-1">
                                        {tools.links.map((link, i) => (
                                            <button
                                                key={i}
                                                disabled={!link.url}
                                                onClick={() => link.url && router.visit(link.url)}
                                                className={`px-2.5 py-1 rounded-md text-xs font-medium transition-colors ${
                                                    link.active
                                                        ? 'bg-slate-900 text-white'
                                                        : link.url
                                                            ? 'text-text-muted hover:bg-surface-raised hover:text-text-primary'
                                                            : 'text-text-muted/40 cursor-not-allowed'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </OperationalCard>
            </div>
        </WorkspaceLayout>
    );
}
