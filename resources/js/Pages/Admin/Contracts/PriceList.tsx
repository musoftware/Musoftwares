import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { Plus, Trash2, Edit, Save, Loader2, X, Clock, Sparkles } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Package } from 'lucide-react';
import { toast } from 'sonner';

export default function PriceList({ items, currencies, system_hourly_rate = 25 }: any) {
    const hourlyRate = system_hourly_rate || 25;
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editingItem, setEditingItem] = useState<number | null>(null);
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        standalone_hours: 4,
        marginal_hours: 2,
        complexity: 'medium',
        currency_id: currencies[0]?.id || 1,
    });

    const handleEdit = (item: any) => {
        setEditingItem(item.id);
        setFormData({
            name: item.name,
            description: item.description || '',
            standalone_hours: item.standalone_hours || 4,
            marginal_hours: item.marginal_hours || 2,
            complexity: item.complexity || 'medium',
            currency_id: item.currency_id || currencies[0]?.id || 1,
        });
    };

    const handleCancel = () => {
        setEditingItem(null);
        setFormData({ name: '', description: '', standalone_hours: 4, marginal_hours: 2, complexity: 'medium', currency_id: currencies[0]?.id || 1 });
    };

    const handleSubmit = (e: any) => {
        e.preventDefault();
        setIsSubmitting(true);

        const onFinish = (success: boolean) => {
            setIsSubmitting(false);
            if (success) {
                toast.success(editingItem ? __('general.updated') || 'Updated' : __('general.created') || 'Created');
                handleCancel();
            } else {
                toast.error(__('general.error_occurred') || 'Something went wrong');
            }
        };

        if (editingItem) {
            router.put(`/admin/contract-price-items/${editingItem}`, formData, {
                onSuccess: () => onFinish(true),
                onError: () => onFinish(false),
            });
        } else {
            router.post('/admin/contract-price-items', formData, {
                onSuccess: () => onFinish(true),
                onError: () => onFinish(false),
            });
        }
    };

    const handleDelete = () => {
        if (!pendingDelete) return;
        router.delete(`/admin/contract-price-items/${pendingDelete}`, {
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
        <AdminSidebarLayout
            title={__('general.contract_price_list')}
            header={__('general.global_contract_price_list')}
        >
            <Head title={__('general.contract_price_list')} />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-1">
                    <Card className="border border-slate-200 shadow-sm">
                        <CardHeader className="bg-slate-900 text-white rounded-t-lg py-4">
                            <CardTitle className="text-base font-bold flex items-center gap-2">
                                <Sparkles className="w-4 h-4 text-amber-400" />
                                {editingItem ? 'تعديل مكون برمجي' : 'إضافة مكون برمجي جديد'}
                            </CardTitle>
                            <CardDescription className="text-slate-300 text-xs">
                                يتم حساب السعر تلقائياً من النظام بحاصل ضرب الساعات × سعر الساعة المعتمد (${hourlyRate}/ساعة).
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-5">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label className="text-xs font-bold text-slate-700">اسم المكون البرمجي *</Label>
                                    <Input required value={formData.name} onChange={e => setFormData({ ...formData, name: e.target.value })} placeholder="مثال: بوابة دفع إلكتروني" className="mt-1" />
                                </div>
                                <div>
                                    <Label className="text-xs font-bold text-slate-700">وصف المكون (اختياري)</Label>
                                    <Input value={formData.description} onChange={e => setFormData({ ...formData, description: e.target.value })} placeholder="وصف المختصر للوظيفة..." className="mt-1" />
                                </div>
                                
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs font-bold text-slate-700">ساعات منفصلة (Standalone)</Label>
                                        <Input required type="number" min="1" value={formData.standalone_hours} onChange={e => setFormData({ ...formData, standalone_hours: parseInt(e.target.value) || 1 })} className="mt-1" />
                                    </div>
                                    <div>
                                        <Label className="text-xs font-bold text-slate-700">ساعات داخل مشروع (Marginal)</Label>
                                        <Input required type="number" min="1" value={formData.marginal_hours} onChange={e => setFormData({ ...formData, marginal_hours: parseInt(e.target.value) || 1 })} className="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <Label className="text-xs font-bold text-slate-700">درجة التعقيد (Complexity)</Label>
                                    <select
                                        className="mt-1 flex h-10 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                        value={formData.complexity}
                                        onChange={e => setFormData({ ...formData, complexity: e.target.value })}
                                        required
                                    >
                                        <option value="low">منخفضة (Low)</option>
                                        <option value="medium">متوسطة (Medium)</option>
                                        <option value="high">عالية (High)</option>
                                    </select>
                                </div>

                                <div className="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs space-y-1">
                                    <p className="font-bold text-slate-700">السعر التقديري التلقائي (سعر الساعة: ${hourlyRate}):</p>
                                    <p className="text-slate-900 font-mono">
                                        منفرد: <strong className="text-emerald-700">${formData.standalone_hours * hourlyRate}</strong> | داخل مشروع: <strong className="text-blue-700">${formData.marginal_hours * hourlyRate}</strong>
                                    </p>
                                </div>

                                <div className="flex gap-2 pt-2">
                                    <Button type="submit" disabled={isSubmitting} className="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-5 rounded-lg">
                                        {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Save className="w-4 h-4 me-2" />}
                                        {editingItem ? 'تحديث المكون' : 'حفظ المكون'}
                                    </Button>
                                    {editingItem && (
                                        <Button type="button" variant="outline" onClick={handleCancel}>
                                            <X className="w-4 h-4" />
                                        </Button>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="md:col-span-2">
                    <Card className="border border-slate-200 shadow-sm">
                        <CardHeader className="bg-white border-b py-4">
                            <CardTitle className="text-base font-bold text-slate-900">قائمة المكونات البرمجية المسجلة ({items.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-5">
                            {items.length === 0 ? (
                                <EmptyState
                                    icon={Package}
                                    title="لا توجد مكونات برمجية مسجلة بعد"
                                    description="قم بإضافة مكونات وساعاتها ليتم استخراجها وتسعيرها تلقائياً في العقود."
                                />
                            ) : (
                                <div className="space-y-3">
                                    {items.map((item: any) => (
                                        <div key={item.id} className="flex items-center justify-between gap-4 p-4 border rounded-xl hover:bg-slate-50 transition-colors bg-white">
                                            <div className="min-w-0">
                                                <h4 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                    {item.name_ar || item.name}
                                                    <span className={`text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full ${
                                                        item.complexity === 'high' ? 'bg-rose-100 text-rose-700' : item.complexity === 'low' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'
                                                    }`}>
                                                        {item.complexity || 'medium'}
                                                    </span>
                                                </h4>
                                                {item.description && (
                                                    <p className="text-xs text-slate-500 mt-1">{item.description}</p>
                                                )}
                                                <div className="flex items-center gap-4 mt-2 text-xs text-slate-600">
                                                    <span className="flex items-center gap-1 font-medium"><Clock className="w-3.5 h-3.5 text-slate-400" /> منفرد: <strong>{item.standalone_hours || 4}h</strong> (~${(item.standalone_hours || 4) * hourlyRate})</span>
                                                    <span className="flex items-center gap-1 font-medium"><Clock className="w-3.5 h-3.5 text-slate-400" /> مشروع: <strong>{item.marginal_hours || 2}h</strong> (~${(item.marginal_hours || 2) * hourlyRate})</span>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 shrink-0">
                                                <Button variant="ghost" size="icon" onClick={() => handleEdit(item)} aria-label="تعديل">
                                                    <Edit className="w-4 h-4 text-slate-600" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="text-rose-500 hover:text-rose-600 hover:bg-rose-50" onClick={() => setPendingDelete(item.id)} aria-label="حذف">
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title="تأكيد الحذف"
                description="هل أنت متأكد من حذف هذا المكون البرمجي؟"
                confirmLabel="حذف"
                cancelLabel="إلغاء"
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}