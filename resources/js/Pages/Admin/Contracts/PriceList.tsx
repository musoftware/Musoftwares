import React, { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { Plus, Trash2, Edit, Save, Loader2, X, Package } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { toast } from 'sonner';

/**
 * The one price list. Items with a platform appear in the public estimator; contracts and the AI
 * pricing use every item. Price: the fixed price, or the hours at the hourly rate when it is empty.
 */
const selectClass = 'mt-1 flex h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-slate-900 focus:outline-none';

const emptyForm = {
    name_ar: '',
    name_en: '',
    description: '',
    platform: '',
    category: '',
    price_usd: '',
    standalone_hours: 4,
    marginal_hours: 2,
    complexity: 'medium',
};

export default function PriceList({ items, system_hourly_rate = 25, platforms = [], categories = [] }: any) {
    const { locale } = usePage<any>().props;
    const ar = locale === 'ar';
    const rate = Number(system_hourly_rate) || 25;

    const [tab, setTab] = useState<string>('all');
    const [editing, setEditing] = useState<number | null>(null);
    const [form, setForm] = useState<any>(emptyForm);
    const [saving, setSaving] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);

    const platformLabel = (key: string) => {
        const p = platforms.find((x: any) => x.key === key);
        return p ? (ar ? p.title_ar : p.title) : __('admin.price_list_general');
    };
    const categoryLabel = (id: string) => {
        const c = categories.find((x: any) => x.id === id);
        return c ? (ar ? c.title_ar : c.title) : '';
    };

    const tabs = [{ key: 'all', label: __('admin.price_list_all') }, ...platforms.map((p: any) => ({ key: p.key, label: ar ? p.title_ar : p.title })), { key: 'general', label: __('admin.price_list_general') }];
    const shown = useMemo(() => items.filter((item: any) => tab === 'all' || (tab === 'general' ? !item.platform : item.platform === tab)), [items, tab]);
    const count = (key: string) => items.filter((item: any) => key === 'all' || (key === 'general' ? !item.platform : item.platform === key)).length;

    const startEdit = (item: any) => {
        setEditing(item.id);
        setForm({
            name_ar: item.name_ar || item.name || '',
            name_en: item.name_en || '',
            description: item.description || '',
            platform: item.platform || '',
            category: item.category || '',
            price_usd: item.price_usd ?? '',
            standalone_hours: item.standalone_hours || 4,
            marginal_hours: item.marginal_hours || 2,
            complexity: item.complexity || 'medium',
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const reset = () => {
        setEditing(null);
        setForm(emptyForm);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        const payload = { ...form, platform: form.platform || null, category: form.category || null, price_usd: form.price_usd === '' ? null : form.price_usd };
        const options = {
            preserveScroll: true,
            onSuccess: () => { toast.success(editing ? __('general.updated') : __('general.created')); reset(); },
            onError: () => toast.error(__('general.error_occurred')),
            onFinish: () => setSaving(false),
        };
        if (editing) router.put(`/admin/contract-price-items/${editing}`, payload, options);
        else router.post('/admin/contract-price-items', payload, options);
    };

    const destroy = () => {
        if (!pendingDelete) return;
        router.delete(`/admin/contract-price-items/${pendingDelete}`, {
            preserveScroll: true,
            onSuccess: () => toast.success(__('general.deleted')),
            onFinish: () => setPendingDelete(null),
        });
    };

    const pricePreview = form.price_usd !== '' ? Number(form.price_usd) : Number(form.standalone_hours) * rate;

    return (
        <AdminSidebarLayout title={__('admin.price_list_title')} header={__('admin.price_list_title')}>
            <Head title={__('admin.price_list_title')} />

            <p className="mb-6 text-sm text-slate-600">{__('admin.price_list_intro', { rate })}</p>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <Card className="border border-slate-200 shadow-sm lg:sticky lg:top-24">
                    <CardHeader className="border-b py-4">
                        <CardTitle className="text-base font-bold">{editing ? __('admin.price_item_edit_title') : __('admin.price_item_add_title')}</CardTitle>
                        <CardDescription className="text-xs">{__('admin.price_list_form_hint')}</CardDescription>
                    </CardHeader>
                    <CardContent className="pt-5">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-xs">{__('admin.price_list_name_ar')}</Label>
                                    <Input value={form.name_ar} onChange={(e) => setForm({ ...form, name_ar: e.target.value })} className="mt-1" dir="rtl" />
                                </div>
                                <div>
                                    <Label className="text-xs">{__('admin.price_list_name_en')}</Label>
                                    <Input value={form.name_en} onChange={(e) => setForm({ ...form, name_en: e.target.value })} className="mt-1" dir="ltr" />
                                </div>
                            </div>
                            <div>
                                <Label className="text-xs">{__('admin.price_item_description_label')}</Label>
                                <Input value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="mt-1" />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-xs">{__('admin.price_list_platform')}</Label>
                                    <select className={selectClass} value={form.platform} onChange={(e) => setForm({ ...form, platform: e.target.value })}>
                                        <option value="">{__('admin.price_list_general')}</option>
                                        {platforms.map((p: any) => <option key={p.key} value={p.key}>{ar ? p.title_ar : p.title}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <Label className="text-xs">{__('general.category')}</Label>
                                    <select className={selectClass} value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })}>
                                        <option value="">—</option>
                                        {categories.map((c: any) => <option key={c.id} value={c.id}>{ar ? c.title_ar : c.title}</option>)}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <Label className="text-xs">{__('admin.price_list_price_usd')}</Label>
                                <Input type="number" min="0" step="1" value={form.price_usd} onChange={(e) => setForm({ ...form, price_usd: e.target.value })} placeholder={__('admin.price_list_price_placeholder')} className="mt-1" dir="ltr" />
                            </div>
                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <Label className="text-xs">{__('admin.price_item_standalone_hours')}</Label>
                                    <Input required type="number" min="1" value={form.standalone_hours} onChange={(e) => setForm({ ...form, standalone_hours: parseInt(e.target.value) || 1 })} className="mt-1" />
                                </div>
                                <div>
                                    <Label className="text-xs">{__('admin.price_item_marginal_hours')}</Label>
                                    <Input required type="number" min="1" value={form.marginal_hours} onChange={(e) => setForm({ ...form, marginal_hours: parseInt(e.target.value) || 1 })} className="mt-1" />
                                </div>
                                <div>
                                    <Label className="text-xs">{__('admin.price_item_complexity')}</Label>
                                    <select className={selectClass} value={form.complexity} onChange={(e) => setForm({ ...form, complexity: e.target.value })}>
                                        <option value="low">{__('general.priority_low')}</option>
                                        <option value="medium">{__('general.priority_medium')}</option>
                                        <option value="high">{__('general.priority_high')}</option>
                                    </select>
                                </div>
                            </div>
                            <p className="rounded-lg bg-slate-50 border border-slate-200 p-3 text-sm">
                                {__('admin.price_list_price_now')}: <strong className="font-mono" dir="ltr">${pricePreview.toLocaleString('en-US')}</strong>
                            </p>
                            <div className="flex gap-2">
                                <Button type="submit" disabled={saving || (!form.name_ar && !form.name_en)} className="flex-1">
                                    {saving ? <Loader2 className="w-4 h-4 animate-spin me-2" /> : editing ? <Save className="w-4 h-4 me-2" /> : <Plus className="w-4 h-4 me-2" />}
                                    {editing ? __('admin.price_item_update') : __('admin.price_item_save')}
                                </Button>
                                {editing && (
                                    <Button type="button" variant="outline" onClick={reset} aria-label={__('general.cancel')}><X className="w-4 h-4" /></Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="lg:col-span-2 space-y-4">
                    <div className="flex flex-wrap gap-2">
                        {tabs.map((t) => (
                            <button
                                key={t.key}
                                type="button"
                                onClick={() => setTab(t.key)}
                                className={`h-9 rounded-full px-4 text-sm font-medium border ${tab === t.key ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'}`}
                            >
                                {t.label} <span className="opacity-60">({count(t.key)})</span>
                            </button>
                        ))}
                    </div>

                    <Card className="border border-slate-200 shadow-sm">
                        <CardContent className="p-0">
                            {shown.length === 0 ? (
                                <div className="p-6"><EmptyState icon={Package} title={__('admin.price_item_empty_title')} description={__('admin.price_item_empty_description')} /></div>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {shown.map((item: any) => (
                                        <li key={item.id} className={`flex items-center justify-between gap-4 p-4 ${item.is_active === false ? 'opacity-50' : ''}`}>
                                            <div className="min-w-0 flex-1">
                                                <p className="font-semibold text-slate-900 text-sm">{(ar ? item.name_ar : item.name_en) || item.name}</p>
                                                <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{platformLabel(item.platform)}</span>
                                                    {item.category && <span>{categoryLabel(item.category)}</span>}
                                                    <span>· {__('admin.price_list_hours', { hours: item.standalone_hours })}</span>
                                                </div>
                                            </div>
                                            <div className="text-end shrink-0">
                                                <p className="font-mono font-semibold text-slate-900" dir="ltr">${Number(item.unit_price_usd).toLocaleString('en-US')}</p>
                                                <p className="text-[11px] text-slate-400">{item.price_usd ? __('admin.price_list_fixed') : __('admin.price_list_by_hours')}</p>
                                            </div>
                                            <div className="flex items-center gap-1 shrink-0">
                                                <Button variant="ghost" size="icon" onClick={() => startEdit(item)} aria-label={__('general.edit')}><Edit className="w-4 h-4" /></Button>
                                                <Button variant="ghost" size="icon" className="text-rose-500 hover:bg-rose-50" onClick={() => setPendingDelete(item.id)} aria-label={__('general.delete')}><Trash2 className="w-4 h-4" /></Button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.confirm_delete')}
                description={__('admin.price_item_delete_confirm')}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={destroy}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}
