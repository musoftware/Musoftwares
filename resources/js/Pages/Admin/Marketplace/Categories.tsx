import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Plus, Trash2, FolderTree } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { toast } from 'sonner';

export default function Categories({ categories }: any) {
    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        description: '',
    });
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.marketplace.categories.store'), {
            onSuccess: () => {
                reset();
                toast.success(__('general.created') || 'Created');
            },
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const handleDelete = () => {
        if (!pendingDelete) return;
        router.delete(route('admin.marketplace.categories.destroy', pendingDelete), {
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

    return (
        <AdminSidebarLayout title={__('general.marketplace_categories')} header={__('general.marketplace_categories')}>
            <Head title={__('general.marketplace_categories')} />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-1">
                    <Card>
                        <CardContent className="p-6">
                            <h3 className="mb-4 text-lg font-bold">{__('general.add_category')}</h3>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div>
                                    <Label htmlFor="name">{__('general.name')}</Label>
                                    <Input
                                        id="name"
                                        required
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                    {errors.name && <p className="text-sm text-destructive mt-1">{errors.name}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="description">{__('general.description')}</Label>
                                    <Textarea
                                        id="description"
                                        rows={3}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                    />
                                </div>
                                <Button type="submit" disabled={processing} className="w-full gap-2">
                                    <Plus className="w-4 h-4" />{__('general.save')}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="lg:col-span-2">
                    <Card>
                        <CardContent className="p-6">
                            <h3 className="mb-4 text-lg font-bold">{__('general.categories_list')}</h3>
                            {categories.length === 0 ? (
                                <EmptyState
                                    icon={FolderTree}
                                    title={__('general.no_categories_found')}
                                    description={__('general.create_your_first_category') || 'Create your first marketplace category.'}
                                />
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/50">
                                            <TableHead className="uppercase text-xs">{__('general.name')}</TableHead>
                                            <TableHead className="uppercase text-xs">{__('general.slug')}</TableHead>
                                            <TableHead className="text-end uppercase text-xs">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {categories.map((cat: any) => (
                                            <TableRow key={cat.id}>
                                                <TableCell className="font-medium">{cat.name}</TableCell>
                                                <TableCell className="text-slate-500 font-mono text-sm">{cat.slug}</TableCell>
                                                <TableCell className="text-end">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setPendingDelete(cat.id)}
                                                        className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                    >
                                                        <Trash2 className="w-4 h-4 me-1" />{__('general.delete')}
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete') || 'Delete?'}
                description={__('general.confirm_delete_category') || 'This category will be permanently deleted.'}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}