import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { useState } from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { MetricCard } from '@/Components/ui/MetricCard';
import {
    Eye, Check, X, Search, FilterX, Trash2, Star, StarOff,
    Ban, ChevronUp, ChevronDown, MoreVertical, ShoppingBag,
    Activity, Clock, FileWarning, Package, CheckCircle2,
    AlertCircle, XCircle, Pencil
} from 'lucide-react';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

// ── Types ───────────────────────────────────────────────────────────────────

type ConfirmAction = {
    type: 'approve' | 'reject' | 'suspend' | 'delete' | 'feature';
    id: number;
    title: string;
} | null;

// ── Helpers ──────────────────────────────────────────────────────────────────

function getStatusBadge(status: string) {
    switch (status) {
        case 'active':
            return (
                <Badge className="bg-green-50 text-green-700 border border-green-200 font-medium capitalize"><CheckCircle2 className="h-3 w-3 mr-1" />{__('admin.active')}</Badge>
            );
        case 'draft':
            return (
                <Badge className="bg-amber-50 text-amber-700 border border-amber-200 font-medium capitalize"><Clock className="h-3 w-3 mr-1" />{__('admin.pending')}</Badge>
            );
        case 'suspended':
            return (
                <Badge className="bg-red-50 text-red-700 border border-red-200 font-medium capitalize"><Ban className="h-3 w-3 mr-1" />{__('admin.suspended')}</Badge>
            );
        case 'rejected':
            return (
                <Badge className="bg-slate-100 text-slate-600 border border-slate-200 font-medium capitalize"><XCircle className="h-3 w-3 mr-1" />{__('admin.rejected')}</Badge>
            );
        default:
            return (
                <Badge variant="secondary" className="capitalize">
                    {status}
                </Badge>
            );
    }
}

function getBasePrice(packages: any[]) {
    if (!packages || packages.length === 0) return null;
    const prices = packages.map((p: any) => parseFloat(p.price));
    const minPrice = Math.min(...prices);
    const currencyId = packages[0].currency_id || 1;
    return <CurrencyDisplay amount={minPrice} currency={currencyId} />;
}

function ServiceThumb({ gallery, title }: { gallery: string[] | null; title: string }) {
    if (gallery && gallery.length > 0) {
        return (
            <img
                src={`/storage/${gallery[0]}`}
                alt={title}
                className="h-10 w-14 rounded object-cover border border-slate-100 bg-slate-50 shrink-0"
            />
        );
    }
    return (
        <div className="h-10 w-14 rounded border border-slate-100 bg-slate-50 flex items-center justify-center shrink-0">
            <FileWarning className="h-4 w-4 text-slate-300" />
        </div>
    );
}

// ── Component ─────────────────────────────────────────────────────────────────

export default function All({ auth, services, categories, filters, stats }: any) {
    const [search, setSearch]       = useState(filters?.search || '');
    const [status, setStatus]       = useState(filters?.status || 'all');
    const [categoryId, setCategoryId] = useState(filters?.category_id || 'all');
    const [sortBy, setSortBy]       = useState(filters?.sort_by || 'created_at');
    const [sortDir, setSortDir]     = useState(filters?.sort_dir || 'desc');
    const [confirm, setConfirm]     = useState<ConfirmAction>(null);
    const [loading, setLoading]     = useState(false);

    // ── Filter helpers ────────────────────────────────────────────────────────

    const applyFilters = (overrides: Record<string, string> = {}) => {
        const query: Record<string, string> = {
            search,
            status,
            category_id: categoryId,
            sort_by: sortBy,
            sort_dir: sortDir,
            ...overrides,
        };

        if (!query.search) delete query.search;
        if (query.status === 'all') delete query.status;
        if (query.category_id === 'all') delete query.category_id;
        if (query.sort_by === 'created_at' && query.sort_dir === 'desc') {
            delete query.sort_by;
            delete query.sort_dir;
        }

        router.get(route('admin.marketplace.services.all'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSort = (column: string) => {
        const newDir = sortBy === column && sortDir === 'asc' ? 'desc' : 'asc';
        setSortBy(column);
        setSortDir(newDir);
        applyFilters({ sort_by: column, sort_dir: newDir });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
        setCategoryId('all');
        setSortBy('created_at');
        setSortDir('desc');
        router.get(route('admin.marketplace.services.all'));
    };

    const SortIcon = ({ col }: { col: string }) =>
        sortBy === col ? (
            sortDir === 'asc' ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />
        ) : null;

    // ── Confirm helpers ───────────────────────────────────────────────────────

    const openConfirm = (type: ConfirmAction['type'], service: any) =>
        setConfirm({ type, id: service.id, title: service.title });

    const handleConfirm = () => {
        if (!confirm) return;
        setLoading(true);

        const done = () => {
            setLoading(false);
            setConfirm(null);
        };

        const opts = { preserveScroll: true, onFinish: done, onError: done };

        switch (confirm.type) {
            case 'approve':
                router.post(route('admin.marketplace.services.approve', confirm.id), {}, opts);
                break;
            case 'reject':
                router.post(route('admin.marketplace.services.reject', confirm.id), {}, opts);
                break;
            case 'suspend':
                router.post(route('admin.marketplace.services.suspend', confirm.id), {}, opts);
                break;
            case 'feature':
                router.post(route('admin.marketplace.services.feature', confirm.id), {}, opts);
                break;
            case 'delete':
                router.delete(route('admin.marketplace.services.destroy', confirm.id), { ...opts });
                break;
        }
    };

    // ── Confirm modal meta ────────────────────────────────────────────────────

    const confirmMeta: Record<string, { title: string; description: string; label: string; variant: 'danger' | 'default' }> = {
        approve: {
            title: __('admin.approve_service'),
            description: __('admin.approve_service_desc', { title: confirm?.title || '' }),
            label: __('admin.approve'),
            variant:     'default',
        },
        reject: {
            title: __('admin.reject_service'),
            description: __('admin.reject_service_desc', { title: confirm?.title || '' }),
            label: __('admin.reject'),
            variant:     'danger',
        },
        suspend: {
            title: __('admin.suspend_service'),
            description: __('admin.suspend_service_desc', { title: confirm?.title || '' }),
            label: __('admin.suspend'),
            variant:     'danger',
        },
        feature: {
            title: __('admin.toggle_featured'),
            description: __('admin.toggle_featured_desc', { title: confirm?.title || '' }),
            label: __('admin.confirm'),
            variant:     'default',
        },
        delete: {
            title: __('admin.delete_service'),
            description: __('admin.delete_service_desc', { title: confirm?.title || '' }),
            label: __('admin.delete_permanently'),
            variant:     'danger',
        },
    };

    const meta = confirm ? confirmMeta[confirm.type] : null;

    const hasActiveFilters = !!(search || status !== 'all' || categoryId !== 'all');

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <AdminSidebarLayout user={auth?.user} title={__('admin.all_services')} header={__('admin.marketplace_services')}>
            <Head title={__('admin.all_services_marketplace')} />

            <div className="py-8 bg-slate-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                    {/* ── Page Header ─────────────────────────────────────── */}
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">{__('admin.all_services')}</h1>
                            <p className="text-sm text-slate-500 mt-0.5">
                                {__('admin.manage_approve_moderate_services')}
                            </p>
                        </div>
                        <Badge variant="secondary" className="bg-slate-200 text-slate-800 text-sm px-3 py-1.5 shrink-0">
                            {services.total ?? 0} {__('admin.total')}
                        </Badge>
                    </div>

                    {/* ── Stats Cards ──────────────────────────────────────── */}
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        <MetricCard
                            label={__('admin.total')}
                            value={stats?.total ?? 0}
                            icon={ShoppingBag}
                        />
                        <MetricCard
                            label={__('admin.active')}
                            value={stats?.active ?? 0}
                            icon={CheckCircle2}
                        />
                        <MetricCard
                            label={__('admin.pending')}
                            value={stats?.pending ?? 0}
                            icon={Clock}
                        />
                        <MetricCard
                            label={__('admin.suspended')}
                            value={stats?.suspended ?? 0}
                            icon={Ban}
                        />
                        <MetricCard
                            label={__('admin.rejected')}
                            value={stats?.rejected ?? 0}
                            icon={AlertCircle}
                        />
                        <MetricCard
                            label={__('admin.featured')}
                            value={stats?.featured ?? 0}
                            icon={Star}
                        />
                    </div>

                    {/* ── Filters Bar ──────────────────────────────────────── */}
                    <div className="flex flex-col sm:flex-row gap-3 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                        {/* Search */}
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            <Input
                                id="services-search"
                                placeholder={__('admin.search_title_seller')}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                                className="pl-9 bg-slate-50 border-slate-200 focus:bg-white"
                            />
                            {search && (
                                <button
                                    onClick={() => { setSearch(''); applyFilters({ search: '' }); }}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            )}
                        </div>

                        {/* Status filter */}
                        <div className="w-full sm:w-[170px]">
                            <Select
                                value={status}
                                onValueChange={(val) => { setStatus(val); applyFilters({ status: val }); }}
                            >
                                <SelectTrigger id="services-status-filter" className="bg-slate-50 border-slate-200">
                                    <SelectValue placeholder={__('admin.status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{__('admin.all_statuses')}</SelectItem>
                                    <SelectItem value="active">{__('admin.active')}</SelectItem>
                                    <SelectItem value="draft">{__('admin.pending')}</SelectItem>
                                    <SelectItem value="suspended">{__('admin.suspended')}</SelectItem>
                                    <SelectItem value="rejected">{__('admin.rejected')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Category filter */}
                        <div className="w-full sm:w-[200px]">
                            <Select
                                value={categoryId}
                                onValueChange={(val) => { setCategoryId(val); applyFilters({ category_id: val }); }}
                            >
                                <SelectTrigger id="services-category-filter" className="bg-slate-50 border-slate-200">
                                    <SelectValue placeholder={__('admin.category')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{__('admin.all_categories')}</SelectItem>
                                    {categories?.map((c: any) => (
                                        <SelectItem key={c.id} value={c.id.toString()}>{c.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Clear filters */}
                        {hasActiveFilters && (
                            <Button
                                variant="ghost"
                                onClick={clearFilters}
                                className="text-slate-500 hover:text-black shrink-0 gap-1.5"
                            >
                                <FilterX className="h-4 w-4" />{__('admin.clear')}</Button>
                        )}
                    </div>

                    {/* ── Table ────────────────────────────────────────────── */}
                    <div className="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
                        <Table>
                            <TableHeader className="bg-slate-50">
                                <TableRow className="border-b border-slate-200">
                                    {/* Service column */}
                                    <TableHead
                                        className="w-[320px] cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                        onClick={() => handleSort('title')}
                                    >
                                        <div className="flex items-center gap-1">
                                            {__('admin.service')}
                                            <SortIcon col="title" />
                                        </div>
                                    </TableHead>
                                    <TableHead>{__('admin.seller')}</TableHead>
                                    <TableHead>{__('admin.category')}</TableHead>
                                    <TableHead>{__('admin.packages_price')}</TableHead>
                                    <TableHead
                                        className="cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                        onClick={() => handleSort('orders_count')}
                                    >
                                        <div className="flex items-center gap-1">
                                            {__('admin.orders')}
                                            <SortIcon col="orders_count" />
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                        onClick={() => handleSort('status')}
                                    >
                                        <div className="flex items-center gap-1">
                                            {__('admin.status')}
                                            <SortIcon col="status" />
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer hover:bg-slate-100 transition-colors select-none"
                                        onClick={() => handleSort('created_at')}
                                    >
                                        <div className="flex items-center gap-1">
                                            {__('admin.date')}
                                            <SortIcon col="created_at" />
                                        </div>
                                    </TableHead>
                                    <TableHead className="text-right w-[60px]">{__('admin.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {services.data.length > 0 ? (
                                    services.data.map((service: any) => (
                                        <TableRow
                                            key={service.id}
                                            className="hover:bg-slate-50/70 transition-colors"
                                        >
                                            {/* Service name + thumbnail */}
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <ServiceThumb
                                                        gallery={service.gallery}
                                                        title={service.title}
                                                    />
                                                    <div className="min-w-0">
                                                        <div className="font-medium text-slate-900 truncate max-w-[220px]" title={service.title}>
                                                            {service.title}
                                                        </div>
                                                        <div className="flex items-center gap-1.5 mt-0.5">
                                                            <span className="text-xs text-slate-400 font-mono">#{service.id}</span>
                                                            {service.is_featured && (
                                                                <Badge className="text-[10px] px-1 py-0 h-4 bg-amber-50 text-amber-600 border border-amber-200 font-medium">
                                                                    <Star className="h-2.5 w-2.5 mr-0.5 fill-current" />{__('admin.featured')}</Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>

                                            {/* Seller */}
                                            <TableCell>
                                                {service.seller ? (
                                                    <div>
                                                        <div className="font-medium text-slate-800 text-sm">{service.seller.name}</div>
                                                        <div className="text-xs text-slate-400 truncate max-w-[140px]">{service.seller.email}</div>
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-400 italic text-sm">{__('admin.unknown')}</span>
                                                )}
                                            </TableCell>

                                            {/* Category */}
                                            <TableCell>
                                                <Badge variant="secondary" className="bg-slate-100 text-slate-700 font-normal text-xs">
                                                    {service.category?.name || __('admin.uncategorized')}
                                                </Badge>
                                            </TableCell>

                                            {/* Packages / Price */}
                                            <TableCell>
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-semibold text-slate-800 text-sm">
                                                        {getBasePrice(service.packages) ?? <span className="text-slate-400 font-normal italic text-xs">{__('admin.no_packages')}</span>}
                                                    </span>
                                                    {service.packages?.length > 0 && (
                                                        <span className="text-xs text-slate-400 flex items-center gap-1">
                                                            <Package className="h-3 w-3" />
                                                            {service.packages.length} {service.packages.length !== 1 ? __('admin.pkgs') : __('admin.pkg')}
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>

                                            {/* Orders + Rating */}
                                            <TableCell>
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-semibold text-slate-800 text-sm flex items-center gap-1">
                                                        <Activity className="h-3 w-3 text-slate-400" />
                                                        {service.orders_count ?? 0}
                                                    </span>
                                                    <span className="text-xs text-slate-400 flex items-center gap-1">
                                                        <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                                                        {service.avg_rating ?? '0.0'} ({service.review_count ?? 0})
                                                    </span>
                                                </div>
                                            </TableCell>

                                            {/* Status */}
                                            <TableCell>
                                                {getStatusBadge(service.status)}
                                            </TableCell>

                                            {/* Date */}
                                            <TableCell>
                                                <span className="text-xs text-slate-500">
                                                    <DateDisplay date={service.created_at} format="MMM D, YYYY" />
                                                </span>
                                            </TableCell>

                                            {/* Actions dropdown */}
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-slate-500 hover:text-black data-[state=open]:bg-slate-100"
                                                            id={`service-actions-${service.id}`}
                                                        >
                                                            <MoreVertical className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-48">

                                                        {/* View */}
                                                        <DropdownMenuItem
                                                            onClick={() => router.get(route('marketplace.services.show', service.id))}
                                                            className="gap-2 cursor-pointer"
                                                        ><Eye className="h-4 w-4" /> {__('admin.view_service')}</DropdownMenuItem>

                                                        {/* Edit */}
                                                        <DropdownMenuItem
                                                            onClick={() => router.get(route('admin.marketplace.services.edit', service.id))}
                                                            className="gap-2 cursor-pointer"
                                                        ><Pencil className="h-4 w-4" /> {__('admin.edit_service')}</DropdownMenuItem>

                                                        {/* Feature toggle */}
                                                        <DropdownMenuItem
                                                            onClick={() => openConfirm('feature', service)}
                                                            className="gap-2 cursor-pointer"
                                                        >
                                                            {service.is_featured ? (
                                                                <><StarOff className="h-4 w-4 text-amber-500" /> {__('admin.unfeature')}</>
                                                            ) : (
                                                                <><Star className="h-4 w-4" /> {__('admin.feature')}</>
                                                            )}
                                                        </DropdownMenuItem>

                                                        <DropdownMenuSeparator />

                                                        {/* Context-aware approve / suspend / reject */}
                                                        {(service.status === 'draft' || service.status === 'suspended') && (
                                                            <DropdownMenuItem
                                                                onClick={() => openConfirm('approve', service)}
                                                                className="gap-2 cursor-pointer text-green-700 focus:text-green-700 focus:bg-green-50"
                                                            >
                                                                <Check className="h-4 w-4" />
                                                                {service.status === 'suspended' ? __('admin.restore') : __('admin.approve')}
                                                            </DropdownMenuItem>
                                                        )}

                                                        {service.status === 'draft' && (
                                                            <DropdownMenuItem
                                                                onClick={() => openConfirm('reject', service)}
                                                                className="gap-2 cursor-pointer text-slate-700"
                                                            ><X className="h-4 w-4" /> {__('admin.reject')}</DropdownMenuItem>
                                                        )}

                                                        {service.status === 'active' && (
                                                            <DropdownMenuItem
                                                                onClick={() => openConfirm('suspend', service)}
                                                                className="gap-2 cursor-pointer text-amber-700 focus:text-amber-700 focus:bg-amber-50"
                                                            ><Ban className="h-4 w-4" /> {__('admin.suspend')}</DropdownMenuItem>
                                                        )}

                                                        <DropdownMenuSeparator />

                                                        {/* Delete */}
                                                        <DropdownMenuItem
                                                            onClick={() => openConfirm('delete', service)}
                                                            className="gap-2 cursor-pointer text-red-600 focus:text-red-700 focus:bg-red-50"
                                                        ><Trash2 className="h-4 w-4" /> {__('admin.delete')}</DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={8} className="h-40 text-center">
                                            <div className="flex flex-col items-center justify-center gap-2 text-slate-400">
                                                <Search className="h-8 w-8 text-slate-200" />
                                                <p className="text-sm font-medium text-slate-500">{__('admin.no_services_found')}</p>
                                                {hasActiveFilters && (
                                                    <button
                                                        onClick={clearFilters}
                                                        className="text-xs text-slate-400 hover:text-black underline underline-offset-2"
                                                    >{__('admin.clear_filters')}</button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* ── Pagination ──────────────────────────────────── */}
                        {services.links && services.links.length > 3 && (
                            <div className="border-t border-slate-200 bg-slate-50 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <div className="text-sm text-slate-500">
                                    {__('admin.showing')} {' '}<span className="font-medium text-slate-900">{services.from ?? 0}</span>{' '}-{' '}<span className="font-medium text-slate-900">{services.to ?? 0}</span>{' '}{__('admin.of')}{' '}<span className="font-medium text-slate-900">{services.total}</span>{' '}{__('admin.services_count')}
                                </div>
                                <div className="flex gap-1 flex-wrap justify-center">
                                    {services.links.map((link: any, idx: number) => (
                                        <button
                                            key={idx}
                                            onClick={() => {
                                                if (link.url) router.get(link.url, {}, { preserveScroll: true });
                                            }}
                                            disabled={!link.url}
                                            className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                                link.active
                                                    ? 'bg-slate-900 text-white shadow-sm'
                                                    : !link.url
                                                    ? 'text-slate-300 cursor-not-allowed'
                                                    : 'bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* ── Confirm Modal ────────────────────────────────────────────── */}
            {meta && (
                <ConfirmModal
                    isOpen={confirm !== null}
                    title={meta.title}
                    description={meta.description}
                    confirmLabel={meta.label}
                    variant={meta.variant}
                    loading={loading}
                    onConfirm={handleConfirm}
                    onCancel={() => setConfirm(null)}
                />
            )}
        </AdminSidebarLayout>
    );
}
