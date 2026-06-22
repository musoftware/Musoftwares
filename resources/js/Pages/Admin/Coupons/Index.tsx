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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tag, Plus, Pencil, Trash2, CheckCircle, XCircle, Eye } from 'lucide-react';
import { CurrencySelect } from '@/Components/CurrencySelect';
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
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(route('admin.coupons.update', editingCoupon!.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingCoupon(null);
                resetForm();
            },
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

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this coupon?')) {
            router.delete(route('admin.coupons.destroy', id));
        }
    };

    const renderFormFields = () => (
        <div className="space-y-4 max-h-[65vh] overflow-y-auto p-1 pe-2">
            {/* Code & Name */}
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

            {/* Description */}
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

            {/* Type */}
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

            {/* Discount fields */}
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

            {/* Min Purchase */}
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

            {/* Usage Limits */}
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

            {/* Dates */}
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

            {/* Admin Notes */}
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

            {/* Active toggle */}
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

    return (
        <AdminSidebarLayout title={__('general.coupons')} header="Coupons Manager">
            <Head title={__('general.admin_coupons')} />

            {/* Header bar */}
            <div className="mb-6 flex items-center justify-between">
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

            {/* Table */}
            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-start text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">{__('general.code')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.name')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.type')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.discount')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.uses')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.expires')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('general.status')}</th>
                            <th className="p-4 font-medium text-gray-600 text-end">{__('general.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((c) => (
                            <tr key={c.id} className="border-b hover:bg-gray-50">
                                <td className="p-4">
                                    <span className="font-bold font-mono text-gray-900 bg-gray-100 px-2 py-0.5 rounded text-xs">
                                        {c.code}
                                    </span>
                                </td>
                                <td className="p-4">
                                    <div className="font-medium text-gray-900">{c.name}</div>
                                    {c.description && (
                                        <div className="text-xs text-gray-400 truncate max-w-[160px]">{c.description}</div>
                                    )}
                                </td>
                                <td className="p-4">
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${
                                        c.type === 'fixed' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'
                                    }`}>
                                        {c.type}
                                    </span>
                                </td>
                                <td className="p-4 text-sm text-gray-700">
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
                                </td>
                                <td className="p-4 text-sm text-gray-700">
                                    <span className="font-bold">{c.current_uses ?? 0}</span>
                                    <span className="text-gray-400"> / {c.max_total_uses ?? '∞'}</span>
                                </td>
                                <td className="p-4 text-sm text-gray-500">
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
                                </td>
                                <td className="p-4">
                                    {c.is_active ? (
                                        <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800">
                                            <CheckCircle className="h-3 w-3" /> {__('general.active')}</span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800">
                                            <XCircle className="h-3 w-3" /> {__('general.inactive')}</span>
                                    )}
                                </td>
                                <td className="p-4 space-x-2 text-end">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.visit(route('admin.coupons.show', c.id))}
                                    >
                                        <Eye className="h-3.5 w-3.5 me-1" /> {__('general.view')}</Button>
                                    <Button variant="outline" size="sm" onClick={() => openEditModal(c)}>
                                        <Pencil className="h-3.5 w-3.5 me-1" /> {__('general.edit')}</Button>
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(c.id)}>
                                        <Trash2 className="h-3.5 w-3.5 me-1" /> {__('general.delete')}</Button>
                                </td>
                            </tr>
                        ))}
                        {items.length === 0 && (
                            <tr>
                                <td colSpan={8} className="p-8 text-center text-gray-400">
                                    <Tag className="mx-auto mb-2 h-8 w-8 opacity-30" />{__('general.no_coupons_found')}</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {coupons?.links && (
                <div className="mt-4 flex justify-center gap-1">
                    {coupons.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            className={`px-3 py-1 rounded text-sm border ${
                                link.active
                                    ? 'bg-black text-white border-black'
                                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 disabled:opacity-40'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            {/* Edit Modal */}
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
        </AdminSidebarLayout>
    );
}
