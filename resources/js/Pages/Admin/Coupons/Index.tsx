import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tag, Plus, Pencil, Trash2, CheckCircle, XCircle, Eye, MoreHorizontal } from 'lucide-react';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { __ } from '@/lib/i18n';

const emptyForm = {
    code: '',
    name: '',
    description: '',
    type: 'fixed',
    discount_amount: '',
    discount_percentage: '',
    currency: '',
    min_purchase_amount: '',
    max_uses_per_user: '',
    max_total_uses: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
    admin_notes: '',
};

interface Currency {
    id: number;
    currency: string;
}

interface CouponData {
    id: number;
    code: string;
    name: string;
    description?: string;
    type: 'fixed' | 'percentage';
    discount_amount: number;
    discount_percentage: number;
    currency_id: number;
    currency_relation?: Currency;
    min_purchase_amount: number;
    max_uses_per_user?: number;
    max_total_uses?: number;
    current_uses?: number;
    starts_at?: string;
    expires_at?: string;
    is_active: boolean;
    admin_notes?: string;
    created_at?: string;
}

interface PaginatedCoupons {
    data: CouponData[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

export default function Index({ coupons, currencies = [] }: { coupons: PaginatedCoupons; currencies?: Currency[] }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingCoupon, setEditingCoupon] = useState<CouponData | null>(null);
    const [pendingDelete, setPendingDelete] = useState<CouponData | null>(null);
    const [formData, setFormData] = useState({ ...emptyForm });

    const set = (key: string, value: any) =>
        setFormData((prev) => ({ ...prev, [key]: value }));

    const resetForm = () => setFormData({ ...emptyForm });

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.coupons.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                resetForm();
                toastSuccess(__('general.created') || 'Coupon created');
            },
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(route('admin.coupons.update', editingCoupon!.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingCoupon(null);
                resetForm();
                toastSuccess(__('general.updated') || 'Coupon updated');
            },
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const openEditModal = (coupon: CouponData) => {
        setEditingCoupon(coupon);
        setFormData({
            code: coupon.code ?? '',
            name: coupon.name ?? '',
            description: coupon.description ?? '',
            type: coupon.type ?? 'fixed',
            discount_amount: coupon.discount_amount?.toString() ?? '',
            discount_percentage: coupon.discount_percentage?.toString() ?? '',
            currency: coupon.currency_id?.toString() ?? '',
            min_purchase_amount: coupon.min_purchase_amount?.toString() ?? '',
            max_uses_per_user: coupon.max_uses_per_user?.toString() ?? '',
            max_total_uses: coupon.max_total_uses?.toString() ?? '',
            starts_at: coupon.starts_at ? coupon.starts_at.slice(0, 16) : '',
            expires_at: coupon.expires_at ? coupon.expires_at.slice(0, 16) : '',
            is_active: coupon.is_active ?? true,
            admin_notes: coupon.admin_notes ?? '',
        });
        setIsEditOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        const id = pendingDelete.id;
        setPendingDelete(null);
        router.delete(route('admin.coupons.destroy', id), {
            onSuccess: () => toastSuccess(__('general.deleted') || 'Coupon deleted'),
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const renderFormFields = () => (
        <div className="space-y-4 max-h-[65vh] overflow-y-auto p-1 pe-2">
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="code">{__('general.coupon_code')}</Label>
                    <Input
                        id="code"
                        value={formData.code}
                        onChange={(e) => set('code', e.target.value.toUpperCase())}
                        placeholder={__('general.leave_empty_to_auto_generate')}
                    />
                </div>
                <div>
                    <Label htmlFor="name">{__('general.name')}<span className="text-red-500">*</span></Label>
                    <Input
                        id="name"
                        value={formData.name}
                        onChange={(e) => set('name', e.target.value)}
                        placeholder={__('general.e_g_summer_sale')}
                        required
                    />
                </div>
            </div>

            <div>
                <Label htmlFor="description">{__('general.description')}</Label>
                <textarea
                    id="description"
                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                    rows={2}
                    value={formData.description}
                    onChange={(e) => set('description', e.target.value)}
                    placeholder={__('general.optional_description')}
                />
            </div>

            <div>
                <Label htmlFor="type">{__('general.discount_type')}<span className="text-red-500">*</span></Label>
                <select
                    id="type"
                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                    value={formData.type}
                    onChange={(e) => set('type', e.target.value)}
                    required
                >
                    <option value="fixed">{__('general.fixed_amount')}</option>
                    <option value="percentage">{__('general.percentage')}</option>
                </select>
            </div>

            <div className="grid grid-cols-2 gap-4">
                {formData.type === 'fixed' ? (
                    <div>
                        <Label htmlFor="discount_amount">{__('general.discount_amount')}</Label>
                        <Input
                            id="discount_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.discount_amount}
                            onChange={(e) => set('discount_amount', e.target.value)}
                            placeholder="0.00"
                        />
                    </div>
                ) : (
                    <div>
                        <Label htmlFor="discount_percentage">Discount Percentage (%)</Label>
                        <Input
                            id="discount_percentage"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            value={formData.discount_percentage}
                            onChange={(e) => set('discount_percentage', e.target.value)}
                            placeholder={__('general.e_g_10')}
                        />
                    </div>
                )}
                <div>
                    <Label htmlFor="currency">{__('general.currency')}<span className="text-red-500">*</span></Label>
                    <CurrencySelect
                        currencies={currencies}
                        value={formData.currency}
                        onChange={(val) => set('currency', val)}
                    />
                </div>
            </div>

            <div>
                <Label htmlFor="min_purchase_amount">{__('general.minimum_purchase_amount')}</Label>
                <Input
                    id="min_purchase_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    value={formData.min_purchase_amount}
                    onChange={(e) => set('min_purchase_amount', e.target.value)}
                    placeholder={__('general.leave_empty_for_no_minimum')}
                />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="max_uses_per_user">{__('general.max_uses_user')}</Label>
                    <Input
                        id="max_uses_per_user"
                        type="number"
                        min="1"
                        value={formData.max_uses_per_user}
                        onChange={(e) => set('max_uses_per_user', e.target.value)}
                        placeholder={__('general.unlimited')}
                    />
                </div>
                <div>
                    <Label htmlFor="max_total_uses">{__('general.max_total_uses')}</Label>
                    <Input
                        id="max_total_uses"
                        type="number"
                        min="1"
                        value={formData.max_total_uses}
                        onChange={(e) => set('max_total_uses', e.target.value)}
                        placeholder={__('general.unlimited')}
                    />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="starts_at">{__('general.starts_at')}</Label>
                    <Input
                        id="starts_at"
                        type="datetime-local"
                        value={formData.starts_at}
                        onChange={(e) => set('starts_at', e.target.value)}
                    />
                </div>
                <div>
                    <Label htmlFor="expires_at">{__('general.expires_at')}</Label>
                    <Input
                        id="expires_at"
                        type="datetime-local"
                        value={formData.expires_at}
                        onChange={(e) => set('expires_at', e.target.value)}
                    />
                </div>
            </div>

            <div>
                <Label htmlFor="admin_notes">{__('general.admin_notes')}</Label>
                <textarea
                    id="admin_notes"
                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-black focus:ring-1 focus:ring-black"
                    rows={2}
                    value={formData.admin_notes}
                    onChange={(e) => set('admin_notes', e.target.value)}
                    placeholder={__('general.internal_notes')}
                />
            </div>

            <div className="flex items-center space-x-2">
                <input
                    type="checkbox"
                    id="is_active"
                    checked={formData.is_active as boolean}
                    onChange={(e) => set('is_active', e.target.checked)}
                    className="rounded border-gray-300 text-black focus:ring-black"
                />
                <Label htmlFor="is_active">{__('general.coupon_is_active')}</Label>
            </div>
        </div>
    );

    const items = coupons?.data ?? [];
    const paginationLinks = coupons?.links;

    return (
        <AdminSidebarLayout title={__('general.coupons')} header="Coupons Manager">
            <Head title={__('general.admin_coupons')} />

            <div className="mb-6 flex items-center justify-end gap-4">
                <div className="flex items-center gap-2 text-gray-500 text-sm">
                    <Tag className="h-4 w-4" />
                    <span>{coupons?.total ?? items.length} coupon{(coupons?.total ?? items.length) !== 1 ? 's' : ''}</span>
                </div>

                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger render={<Button><Plus className="me-2 h-4 w-4" />{__('general.create_coupon')}</Button>} />
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{__('general.create_new_coupon')}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit}>
                            {renderFormFields()}
                            <DialogFooter className="mt-6">
                                <Button type="button" variant="outline" onClick={() => { setIsCreateOpen(false); resetForm(); }}>
                                    {__('general.cancel')}</Button>
                                <Button type="submit">{__('general.save_coupon')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {items.length === 0 ? (
                <EmptyState
                    icon={Tag}
                    title={__('general.no_coupons_found') || 'No coupons found'}
                    description={__('general.create_first_coupon_cta') || 'Create your first coupon to get started.'}
                />
            ) : (
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{__('general.code')}</TableHead>
                                    <TableHead>{__('general.name')}</TableHead>
                                    <TableHead>{__('general.type')}</TableHead>
                                    <TableHead>{__('general.discount')}</TableHead>
                                    <TableHead>{__('general.uses')}</TableHead>
                                    <TableHead>{__('general.expires')}</TableHead>
                                    <TableHead className="text-center">{__('general.status')}</TableHead>
                                    <TableHead className="text-end">{__('general.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.map((c) => (
                                    <TableRow key={c.id}>
                                        <TableCell>
                                            <span className="font-bold font-mono text-gray-900 bg-gray-100 px-2 py-0.5 rounded text-xs">
                                                {c.code}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{c.name}</div>
                                            {c.description && (
                                                <div className="text-xs text-gray-400 truncate max-w-[160px]">{c.description}</div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${
                                                c.type === 'fixed'
                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                    : 'bg-blue-50 text-blue-700 border border-blue-200'
                                            }`}>
                                                {c.type}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-700">
                                            {c.type === 'percentage'
                                                ? <span className="font-medium">{parseFloat(String(c.discount_percentage ?? 0)).toFixed(2)}%</span>
                                                : (
                                                    <>
                                                        <span className="font-medium">{parseFloat(String(c.discount_amount ?? 0)).toFixed(2)}</span>
                                                        {c.currency_relation && (
                                                            <span className="text-gray-400 ms-1">{c.currency_relation.currency}</span>
                                                        )}
                                                    </>
                                                )
                                            }
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-700">
                                            <span className="font-bold">{c.current_uses ?? 0}</span>
                                            <span className="text-gray-400"> / {c.max_total_uses ?? '∞'}</span>
                                        </TableCell>
                                        <TableCell className="text-sm text-gray-500">
                                            {c.expires_at ? (
                                                <div>
                                                    <div className={new Date(c.expires_at) < new Date() ? 'text-red-600 font-bold' : ''}>
                                                        {new Date(c.expires_at).toLocaleDateString()}
                                                    </div>
                                                    <div className="text-xs text-gray-400">
                                                        {new Date(c.expires_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-gray-400">{__('general.never')}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <StatusBadge status={c.is_active ? 'active' : 'inactive'} />
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
                                                    <DropdownMenuItem onClick={() => router.visit(route('admin.coupons.show', c.id))}>
                                                        <Eye className="h-4 w-4 me-2" /> {__('general.view')}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => openEditModal(c)}>
                                                        <Pencil className="h-4 w-4 me-2" /> {__('general.edit')}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => setPendingDelete(c)} className="text-red-600 focus:text-red-600">
                                                        <Trash2 className="h-4 w-4 me-2" /> {__('general.delete')}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            )}

            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                <div className="mt-4 flex justify-center gap-1">
                    {paginationLinks.map((link, i) => (
                        <Button
                            key={i}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                        >
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Button>
                    ))}
                </div>
            )}

            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit Coupon — {editingCoupon?.code}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        {renderFormFields()}
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => { setIsEditOpen(false); resetForm(); }}>
                                {__('general.cancel')}</Button>
                            <Button type="submit">{__('general.save_changes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.delete_coupon') || 'Delete coupon?'}
                description={__('general.confirm_delete_coupon_desc') || `This will permanently delete coupon "${pendingDelete?.code}".`}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}