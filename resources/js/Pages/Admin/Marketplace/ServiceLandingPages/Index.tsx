import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Eye, Trash2, Edit, MoreHorizontal, Search, FilterX, Layers } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { EmptyState } from '@/Components/ui/EmptyState';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export default function Index({ servicesWithLandingPages, filters, auth }: any) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);

    const toggleStatus = (id: number) => {
        router.post(route('admin.marketplace.service-landing-pages.toggle-status', id), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success(__('general.updated') || 'Updated'),
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        router.delete(route('admin.marketplace.service-landing-pages.destroy', pendingDelete), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.deleted') || 'Deleted');
                setPendingDelete(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingDelete(null);
            },
        });
    };

    const items = servicesWithLandingPages?.data ?? [];
    const filtered = items.filter((s: any) => {
        const q = search.toLowerCase().trim();
        if (!q) return true;
        return (
            s.title?.toLowerCase().includes(q) ||
            s.user?.name?.toLowerCase().includes(q) ||
            s.landing_page?.hero_title?.toLowerCase().includes(q) ||
            s.landing_page?.slug?.toLowerCase().includes(q)
        );
    });

    return (
        <AdminSidebarLayout title={__('general.service_landing_pages')} header={__('general.service_landing_pages')}>
            <Head title={__('general.service_landing_pages')} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">{__('general.service_landing_pages')}</h1>
                    <p className="text-sm text-slate-500 mt-1">{__('general.manage_all_landing_pages')}</p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex justify-between items-center mb-6 gap-3 flex-wrap">
                            <div className="relative flex-1 max-w-md">
                                <Search className="absolute start-2.5 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
                                <Input
                                    placeholder={__('general.search_landing_pages') || 'Search landing pages...'}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="ps-8 h-9"
                                />
                            </div>
                            <h3 className="text-lg font-medium text-slate-900">{__('general.manage_landing_pages')}</h3>
                        </div>

                        {filtered.length === 0 ? (
                            <EmptyState
                                icon={Layers}
                                title={__('general.no_landing_pages_found')}
                                description={__('general.no_landing_pages_found_desc') || 'Landing pages will appear here as sellers create them.'}
                            />
                        ) : (
                            <div className="border rounded-md overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{__('general.service')}</TableHead>
                                            <TableHead>{__('general.seller')}</TableHead>
                                            <TableHead>{__('general.hero_title')}</TableHead>
                                            <TableHead>{__('general.variants')}</TableHead>
                                            <TableHead>{__('general.leads')}</TableHead>
                                            <TableHead>{__('general.status')}</TableHead>
                                            <TableHead className="text-end">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {filtered.map((service: any) => (
                                            <TableRow key={service.id}>
                                                <TableCell className="font-medium">
                                                    {service.title}
                                                    <br />
                                                    <a
                                                        href={`/l/${service.landing_page.slug}`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-xs text-slate-500 hover:underline font-mono"
                                                    >
                                                        /l/{service.landing_page.slug}
                                                    </a>
                                                </TableCell>
                                                <TableCell>
                                                    {service.user?.name}
                                                    <div className="text-xs text-slate-500">{service.user?.email}</div>
                                                </TableCell>
                                                <TableCell className="max-w-[220px] truncate" title={service.landing_page.hero_title}>
                                                    {service.landing_page.hero_title}
                                                </TableCell>
                                                <TableCell>
                                                    {service.landing_page.variants?.length > 0 ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                            {service.landing_page.variants.length} {__('general.variants')}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-500 text-sm">{__('general.no_ab_test')}</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {service.landing_page.formSubmissions?.length || 0}
                                                </TableCell>
                                                <TableCell>
                                                    <Switch
                                                        checked={service.landing_page.is_active}
                                                        onCheckedChange={() => toggleStatus(service.landing_page.id)}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">{__('general.actions')}</span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <a href={`/l/${service.landing_page.slug}`} target="_blank" rel="noreferrer" className="cursor-pointer flex items-center">
                                                                    <Eye className="w-4 h-4 me-2" />
                                                                    {__('general.view')}
                                                                </a>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => setPendingDelete(service.landing_page.id)} className="text-red-600 focus:text-red-700 cursor-pointer flex items-center">
                                                                <Trash2 className="w-4 h-4 me-2" />
                                                                {__('general.delete')}
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete') || 'Delete?'}
                description={__('general.confirm_delete_landing_page') || 'This landing page will be permanently deleted.'}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}