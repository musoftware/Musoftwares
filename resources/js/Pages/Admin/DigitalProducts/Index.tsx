import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import {
    BookOpen,
    Download,
    Eye,
    FolderTree,
    HardDrive,
    Layers,
    MoreHorizontal,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Sparkles,
    Trash2,
    UploadCloud,
    ExternalLink,
    CheckCircle2,
    XCircle,
    FileText,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    author_name: string | null;
    publisher: string | null;
    price: number | string;
    is_free: boolean;
    has_free_edition: boolean;
    free_edition_title: string | null;
    page_count: number | null;
    file_size: number | null;
    download_count: number;
    is_published: boolean;
    is_featured: boolean;
    cover_url: string;
    formatted_price: string;
    formatted_file_size: string;
    category?: Category | null;
    created_at: string;
}

interface Props {
    products: {
        data: Product[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number;
        to?: number;
    };
    categories: Category[];
    stats: {
        total_books: number;
        total_downloads: number;
        total_free: number;
        total_paid: number;
    };
    filters: {
        search?: string;
        category_id?: string;
        status?: string;
    };
}

export default function Index({ products, categories, stats, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryId, setCategoryId] = useState(filters.category_id || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [pendingDeleteProduct, setPendingDeleteProduct] = useState<Product | null>(null);

    const handleFilter = (overrides: Record<string, any> = {}) => {
        const queryParams: Record<string, any> = {
            search: search || undefined,
            category_id: categoryId || undefined,
            status: status !== 'all' ? status : undefined,
            ...overrides,
        };

        const cleanParams: Record<string, any> = {};
        Object.keys(queryParams).forEach((k) => {
            if (queryParams[k] !== undefined && queryParams[k] !== '' && queryParams[k] !== 'all') {
                cleanParams[k] = queryParams[k];
            }
        });

        router.get(route('admin.digitalproducts.index'), cleanParams, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearFilters = () => {
        setSearch('');
        setCategoryId('');
        setStatus('all');
        router.get(route('admin.digitalproducts.index'), {}, { preserveState: true });
    };

    const handleTogglePublish = (product: Product) => {
        router.post(
            route('admin.digitalproducts.toggle_publish', product.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        product.is_published
                            ? __('general.book_hidden_successfully') || 'Book hidden from store'
                            : __('general.book_published_successfully') || 'Book published to store'
                    );
                },
                onError: () => {
                    toast.error(__('general.error_occurred') || 'An error occurred');
                },
            }
        );
    };

    const confirmDelete = () => {
        if (!pendingDeleteProduct) return;
        router.delete(route('admin.digitalproducts.destroy', pendingDeleteProduct.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.book_deleted_successfully') || 'Book deleted successfully');
                setPendingDeleteProduct(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'An error occurred');
                setPendingDeleteProduct(null);
            },
        });
    };

    const categoryOptions = [
        { value: '', label: __('general.all_categories') || 'All Categories' },
        ...categories.map((c) => ({
            value: String(c.id),
            label: c.name,
        })),
    ];

    const statusOptions = [
        { value: 'all', label: __('general.all_statuses') || 'All Statuses' },
        { value: 'published', label: __('general.published') || 'Published' },
        { value: 'draft', label: __('general.draft') || 'Draft' },
    ];

    const hasActiveFilters = Boolean(search || categoryId || (status && status !== 'all'));

    return (
        <AdminSidebarLayout
            title={__('general.digital_books') || 'Digital Books & Publications'}
            header={
                <div className="flex items-center gap-2">
                    <BookOpen className="h-5 w-5 text-slate-700" />
                    <span>{__('general.digital_books') || 'Digital Books & Publications'}</span>
                </div>
            }
            actions={
                <div className="flex items-center gap-2">
                    <Link href={route('admin.digitalproducts.categories.index')}>
                        <Button variant="outline" size="sm" className="h-9 gap-1.5 border-slate-300">
                            <FolderTree className="h-4 w-4 text-slate-600" />
                            <span>{__('general.manage_categories') || 'Categories'}</span>
                        </Button>
                    </Link>
                    <Link href={route('admin.digitalproducts.create')}>
                        <Button size="sm" className="h-9 gap-1.5 bg-slate-900 hover:bg-slate-800 text-white shadow-sm">
                            <Plus className="h-4 w-4" />
                            <span>{__('general.upload_new_book') || 'Upload Book (PDF)'}</span>
                        </Button>
                    </Link>
                </div>
            }
        >
            <Head title={__('general.digital_books') || 'Digital Books'} />

            {/* Top Stats Cards */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card className="border border-slate-200 shadow-sm bg-white">
                    <CardContent className="p-4 flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">{__('general.total_books') || 'Total Books'}</p>
                            <h3 className="text-2xl font-bold text-slate-900 mt-1">{stats?.total_books ?? 0}</h3>
                        </div>
                        <div className="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                            <BookOpen className="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <Card className="border border-slate-200 shadow-sm bg-white">
                    <CardContent className="p-4 flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">{__('general.total_downloads') || 'Total Downloads'}</p>
                            <h3 className="text-2xl font-bold text-emerald-600 mt-1">{(stats?.total_downloads ?? 0).toLocaleString()}</h3>
                        </div>
                        <div className="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <Download className="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <Card className="border border-slate-200 shadow-sm bg-white">
                    <CardContent className="p-4 flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">{__('general.free_books') || 'Free Editions'}</p>
                            <h3 className="text-2xl font-bold text-blue-600 mt-1">{stats?.total_free ?? 0}</h3>
                        </div>
                        <div className="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <Sparkles className="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <Card className="border border-slate-200 shadow-sm bg-white">
                    <CardContent className="p-4 flex items-center justify-between">
                        <div>
                            <p className="text-xs font-semibold text-slate-500">{__('general.paid_books') || 'Paid Editions'}</p>
                            <h3 className="text-2xl font-bold text-amber-600 mt-1">{stats?.total_paid ?? 0}</h3>
                        </div>
                        <div className="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <Layers className="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Filter Bar */}
            <Card className="border border-slate-200 shadow-sm bg-white mb-6">
                <CardContent className="p-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                        <div className="lg:col-span-5">
                            <Label className="text-xs font-semibold text-slate-700 mb-1.5 block">
                                {__('general.search') || 'Search by title or author'}
                            </Label>
                            <div className="relative">
                                <Search className="h-4 w-4 absolute start-3 top-2.5 text-slate-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                    placeholder={__('general.search_books_placeholder') || 'Search title, author...'}
                                    className="ps-9 h-9 text-xs"
                                />
                            </div>
                        </div>

                        <div className="lg:col-span-3">
                            <Label className="text-xs font-semibold text-slate-700 mb-1.5 block">
                                {__('general.category') || 'Category'}
                            </Label>
                            <PremiumCombobox
                                value={categoryId}
                                onChange={(val) => {
                                    setCategoryId(String(val || ''));
                                    setTimeout(() => handleFilter({ category_id: String(val || '') }), 50);
                                }}
                                options={categoryOptions}
                                placeholder={__('general.all_categories') || 'All Categories'}
                            />
                        </div>

                        <div className="lg:col-span-2">
                            <Label className="text-xs font-semibold text-slate-700 mb-1.5 block">
                                {__('general.status') || 'Status'}
                            </Label>
                            <PremiumCombobox
                                value={status}
                                onChange={(val) => {
                                    setStatus(String(val || 'all'));
                                    setTimeout(() => handleFilter({ status: String(val || 'all') }), 50);
                                }}
                                options={statusOptions}
                                placeholder={__('general.all_statuses') || 'All Statuses'}
                            />
                        </div>

                        <div className="lg:col-span-2 flex items-center gap-2 justify-end">
                            <Button size="sm" className="h-9 px-4 flex-1 bg-slate-900 hover:bg-slate-800 text-white" onClick={() => handleFilter()}>
                                {__('general.filter') || 'Filter'}
                            </Button>
                            {hasActiveFilters && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="h-9 px-2.5 text-red-600 border-red-200 hover:bg-red-50"
                                    onClick={handleClearFilters}
                                    title={__('general.clear_filters') || 'Reset'}
                                >
                                    <RefreshCw className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Books Table */}
            <Card className="border border-slate-200 shadow-sm bg-white overflow-hidden">
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader className="bg-slate-50 border-b border-slate-200">
                            <TableRow>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4">{__('general.book_and_cover') || 'Book / Cover'}</TableHead>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4">{__('general.category') || 'Category'}</TableHead>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4">{__('general.price') || 'Price'}</TableHead>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4">{__('general.pages_and_size') || 'Pages & Size'}</TableHead>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4">{__('general.downloads') || 'Downloads'}</TableHead>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4">{__('general.status') || 'Status'}</TableHead>
                                <TableHead className="font-bold text-slate-700 text-xs py-3.5 px-4 text-end">{__('general.actions') || 'Actions'}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody className="divide-y divide-slate-100 text-xs">
                            {products.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-12 text-slate-400">
                                        <BookOpen className="h-10 w-10 mx-auto text-slate-300 mb-2" />
                                        <p className="font-medium">{__('general.no_books_found') || 'No digital books found'}</p>
                                        <Link href={route('admin.digitalproducts.create')} className="inline-block mt-3">
                                            <Button size="sm" variant="outline" className="h-8 gap-1 text-xs">
                                                <Plus className="h-3.5 w-3.5" />
                                                {__('general.upload_first_book') || 'Upload your first book'}
                                            </Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                products.data.map((product) => (
                                    <TableRow key={product.id} className="hover:bg-slate-50/80 transition-colors">
                                        <TableCell className="py-3 px-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-12 h-16 rounded-md bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center shadow-xs">
                                                    {product.cover_url ? (
                                                        <img
                                                            src={product.cover_url}
                                                            alt={product.title}
                                                            className="w-full h-full object-cover"
                                                            loading="lazy"
                                                        />
                                                    ) : (
                                                        <BookOpen className="h-6 w-6 text-slate-400" />
                                                    )}
                                                </div>
                                                <div className="min-w-0 max-w-sm">
                                                    <Link
                                                        href={route('admin.digitalproducts.edit', product.id)}
                                                        className="font-bold text-slate-900 hover:text-blue-600 transition-colors line-clamp-1 block"
                                                    >
                                                        {product.title}
                                                    </Link>
                                                    <p className="text-[11px] text-slate-500 truncate mt-0.5">
                                                        {product.author_name || '—'}
                                                    </p>
                                                    {product.has_free_edition && (
                                                        <span className="inline-flex items-center gap-1 text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 font-semibold mt-1">
                                                            <Sparkles className="h-2.5 w-2.5" /> Playbook Edition
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </TableCell>

                                        <TableCell className="py-3 px-4">
                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                {product.category?.name || __('general.uncategorized') || 'Uncategorized'}
                                            </span>
                                        </TableCell>

                                        <TableCell className="py-3 px-4 font-semibold">
                                            {product.is_free ? (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {__('general.free') || 'Free'}
                                                </span>
                                            ) : (
                                                <span className="text-slate-900 font-bold">{product.formatted_price}</span>
                                            )}
                                        </TableCell>

                                        <TableCell className="py-3 px-4 text-slate-600">
                                            <div>{product.page_count ?? '—'} {__('general.pages') || 'pages'}</div>
                                            <div className="text-[11px] text-slate-400 font-mono mt-0.5">{product.formatted_file_size}</div>
                                        </TableCell>

                                        <TableCell className="py-3 px-4">
                                            <div className="flex items-center gap-1.5 font-bold text-slate-800">
                                                <Download className="h-3.5 w-3.5 text-slate-400" />
                                                <span>{(product.download_count || 0).toLocaleString()}</span>
                                            </div>
                                        </TableCell>

                                        <TableCell className="py-3 px-4">
                                            <button
                                                type="button"
                                                onClick={() => handleTogglePublish(product)}
                                                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-all ${
                                                    product.is_published
                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                                        : 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100'
                                                }`}
                                                title={__('general.click_to_toggle_status') || 'Click to toggle status'}
                                            >
                                                {product.is_published ? (
                                                    <>
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        <span>{__('general.published') || 'Published'}</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                        <span>{__('general.draft') || 'Draft'}</span>
                                                    </>
                                                )}
                                            </button>
                                        </TableCell>

                                        <TableCell className="py-3 px-4 text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-slate-500 hover:text-slate-900">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-44 text-xs">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('admin.digitalproducts.edit', product.id)} className="flex items-center gap-2 cursor-pointer">
                                                            <Pencil className="h-3.5 w-3.5 text-slate-600" />
                                                            <span>{__('general.edit') || 'Edit'}</span>
                                                        </Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem asChild>
                                                        <a
                                                            href={`/library/${product.slug}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <ExternalLink className="h-3.5 w-3.5 text-slate-600" />
                                                            <span>{__('general.view_in_store') || 'View in Store'}</span>
                                                        </a>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuSeparator />

                                                    <DropdownMenuItem
                                                        onClick={() => setPendingDeleteProduct(product)}
                                                        className="flex items-center gap-2 text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                        <span>{__('general.delete') || 'Delete'}</span>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {products.links && products.links.length > 3 && (
                    <div className="p-4 border-t border-slate-100 flex items-center justify-between">
                        <p className="text-xs text-slate-500">
                            {__('general.showing') || 'Showing'} {products.from || 0} {__('general.to') || 'to'} {products.to || 0} {__('general.of') || 'of'} {products.total} {__('general.books') || 'books'}
                        </p>
                        <div className="flex items-center gap-1">
                            {products.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    preserveScroll
                                    className={`px-3 py-1.5 rounded text-xs font-medium transition-colors ${
                                        link.active
                                            ? 'bg-slate-900 text-white font-bold'
                                            : !link.url
                                            ? 'text-slate-300 pointer-events-none'
                                            : 'text-slate-700 hover:bg-slate-100 border border-slate-200'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </Card>

            {/* Confirm Delete Modal */}
            <ConfirmModal
                isOpen={Boolean(pendingDeleteProduct)}
                onCancel={() => setPendingDeleteProduct(null)}
                onConfirm={confirmDelete}
                title={__('general.confirm_delete_book') || 'Delete Digital Book'}
                description={
                    __('general.confirm_delete_book_message') ||
                    `Are you sure you want to delete "${pendingDeleteProduct?.title}"? This action cannot be undone.`
                }
                confirmLabel={__('general.delete') || 'Delete'}
                variant="danger"
            />
        </AdminSidebarLayout>
    );
}
