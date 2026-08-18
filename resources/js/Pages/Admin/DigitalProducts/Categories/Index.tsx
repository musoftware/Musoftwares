import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import {
    ArrowLeft,
    BookOpen,
    FolderTree,
    Loader2,
    Pencil,
    Plus,
    Trash2,
    X,
    FolderPlus,
    Check,
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    sort_order: number;
    is_active: boolean;
    products_count: number;
}

interface Props {
    categories: Category[];
}

export default function Index({ categories }: Props) {
    const [editingCategory, setEditingCategory] = useState<Category | null>(null);
    const [pendingDeleteCategory, setPendingDeleteCategory] = useState<Category | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: '',
        slug: '',
        description: '',
        icon: 'ri-book-line',
        sort_order: '0',
    });

    const handleStartEdit = (category: Category) => {
        setEditingCategory(category);
        setData({
            name: category.name,
            slug: category.slug,
            description: category.description || '',
            icon: category.icon || 'ri-book-line',
            sort_order: String(category.sort_order ?? 0),
        });
    };

    const handleCancelEdit = () => {
        setEditingCategory(null);
        reset();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingCategory) {
            put(route('admin.digitalproducts.categories.update', editingCategory.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(__('general.category_updated_successfully') || 'Category updated successfully!');
                    handleCancelEdit();
                },
                onError: (errs) => {
                    const first = Object.values(errs)[0];
                    if (first) toast.error(String(first));
                },
            });
        } else {
            post(route('admin.digitalproducts.categories.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(__('general.category_created_successfully') || 'Category created successfully!');
                    reset();
                },
                onError: (errs) => {
                    const first = Object.values(errs)[0];
                    if (first) toast.error(String(first));
                },
            });
        }
    };

    const confirmDelete = () => {
        if (!pendingDeleteCategory) return;

        router.delete(route('admin.digitalproducts.categories.destroy', pendingDeleteCategory.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.category_deleted_successfully') || 'Category deleted successfully!');
                setPendingDeleteCategory(null);
                if (editingCategory?.id === pendingDeleteCategory.id) {
                    handleCancelEdit();
                }
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'An error occurred');
                setPendingDeleteCategory(null);
            },
        });
    };

    return (
        <AdminSidebarLayout
            title={__('general.book_categories') || 'Digital Book Categories'}
            header={
                <div className="flex items-center gap-2">
                    <Link href={route('admin.digitalproducts.index')} className="text-slate-500 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                    <FolderTree className="h-5 w-5 text-slate-700" />
                    <span>{__('general.book_categories') || 'Digital Book Categories'}</span>
                </div>
            }
            actions={
                <Link href={route('admin.digitalproducts.create')}>
                    <Button size="sm" className="h-9 gap-1.5 bg-slate-900 hover:bg-slate-800 text-white shadow-sm">
                        <Plus className="h-4 w-4" />
                        <span>{__('general.upload_new_book') || 'Upload Book'}</span>
                    </Button>
                </Link>
            }
        >
            <Head title={__('general.book_categories') || 'Book Categories'} />

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {/* Form Column (5 cols) */}
                <div className="lg:col-span-5">
                    <Card className="border border-slate-200 shadow-sm bg-white sticky top-24">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <FolderPlus className="h-4 w-4 text-blue-600" />
                                    {editingCategory
                                        ? __('general.edit_category') || `Edit Category: ${editingCategory.name}`
                                        : __('general.add_new_category') || 'Add New Category'}
                                </span>
                                {editingCategory && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="h-7 text-xs text-slate-500"
                                        onClick={handleCancelEdit}
                                    >
                                        <X className="h-3.5 w-3.5 me-1" />
                                        <span>{__('general.cancel') || 'Cancel'}</span>
                                    </Button>
                                )}
                            </CardTitle>
                        </CardHeader>

                        <CardContent className="p-5">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.category_name') || 'Category Name'} <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="h-9 text-xs"
                                        placeholder="e.g. Artificial Intelligence"
                                        required
                                    />
                                    {errors.name && <p className="text-xs text-red-600">{errors.name}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.slug') || 'Slug (Optional)'}
                                    </Label>
                                    <Input
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        className="h-9 text-xs font-mono"
                                        placeholder="e.g. artificial-intelligence"
                                    />
                                    {errors.slug && <p className="text-xs text-red-600">{errors.slug}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.sort_order') || 'Sort Order'}
                                    </Label>
                                    <Input
                                        type="number"
                                        value={data.sort_order}
                                        onChange={(e) => setData('sort_order', e.target.value)}
                                        className="h-9 text-xs"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.description') || 'Description'}
                                    </Label>
                                    <Textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={3}
                                        className="text-xs"
                                        placeholder="Brief summary of books in this category..."
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full h-10 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-sm gap-2"
                                >
                                    {processing ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : editingCategory ? (
                                        <Check className="h-4 w-4" />
                                    ) : (
                                        <Plus className="h-4 w-4" />
                                    )}
                                    <span>
                                        {editingCategory
                                            ? __('general.update_category') || 'Update Category'
                                            : __('general.create_category') || 'Create Category'}
                                    </span>
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                {/* Categories List (7 cols) */}
                <div className="lg:col-span-7">
                    <Card className="border border-slate-200 shadow-sm bg-white overflow-hidden">
                        <CardHeader className="pb-3 bg-slate-50 border-b border-slate-100">
                            <CardTitle className="text-sm font-bold text-slate-900 flex items-center justify-between">
                                <span>{__('general.available_categories') || 'Available Categories'}</span>
                                <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                                    {categories.length}
                                </span>
                            </CardTitle>
                        </CardHeader>

                        <div className="divide-y divide-slate-100">
                            {categories.length === 0 ? (
                                <div className="p-10 text-center text-slate-400 text-xs">
                                    <FolderTree className="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                    <p>{__('general.no_categories_yet') || 'No categories created yet.'}</p>
                                </div>
                            ) : (
                                categories.map((cat) => (
                                    <div
                                        key={cat.id}
                                        className={`p-4 flex items-center justify-between hover:bg-slate-50/80 transition-colors ${
                                            editingCategory?.id === cat.id ? 'bg-blue-50/40 border-s-4 border-blue-600' : ''
                                        }`}
                                    >
                                        <div className="min-w-0 pr-3">
                                            <h4 className="text-xs font-bold text-slate-900">{cat.name}</h4>
                                            <p className="text-[11px] text-slate-400 font-mono mt-0.5">{cat.slug}</p>
                                            {cat.description && (
                                                <p className="text-[11px] text-slate-500 mt-1 line-clamp-1">{cat.description}</p>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-2 flex-shrink-0">
                                            <span className="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                {cat.products_count} {__('general.books') || 'books'}
                                            </span>

                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900 border-slate-200"
                                                onClick={() => handleStartEdit(cat)}
                                                title={__('general.edit') || 'Edit'}
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                            </Button>

                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 border-slate-200"
                                                onClick={() => setPendingDeleteCategory(cat)}
                                                title={__('general.delete') || 'Delete'}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </Card>
                </div>
            </div>

            {/* Confirm Delete Modal */}
            <ConfirmModal
                isOpen={Boolean(pendingDeleteCategory)}
                onClose={() => setPendingDeleteCategory(null)}
                onConfirm={confirmDelete}
                title={__('general.confirm_delete_category') || 'Delete Category'}
                message={
                    __('general.confirm_delete_category_message') ||
                    `Are you sure you want to delete category "${pendingDeleteCategory?.name}"? Any books under this category will become uncategorized.`
                }
                confirmText={__('general.delete') || 'Delete'}
                variant="danger"
            />
        </AdminSidebarLayout>
    );
}
