import React from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { t } from './t';

export interface Plan {
    key: string;
    name: string;
    name_ar?: string | null;
    price: number | string;
    description?: string | null;
    description_ar?: string | null;
    points?: number | string | null;
    bots?: number | string | null;
    features?: string[] | null;
    features_ar?: string[] | null;
}

const linesOf = (list?: string[] | null): string => (list ?? []).join('\n');
const listOf = (text: string): string[] => text.split('\n');

const slugOf = (name: string, fallback: string): string => name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || fallback;

/** Cards of price plans. The key is made from the name, so nobody has to type it. */
export function PlansEditor({ plans, onChange }: { plans: Plan[]; onChange: (plans: Plan[]) => void }) {
    const update = (index: number, patch: Partial<Plan>) => onChange(plans.map((plan, i) => (i === index ? { ...plan, ...patch } : plan)));

    return (
        <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
                {t(
                    'Add plans only if the service has more than one price. With no plans the program uses the single price from the Basics tab.',
                    'ضيف باقات بس لو الخدمة ليها أكتر من سعر. من غير باقات هيتستخدم السعر الواحد من تاب الأساسيات.',
                )}
            </p>
            {plans.map((plan, index) => (
                <div key={index} className="rounded-md border p-4 space-y-3">
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <label className="text-sm space-y-1">
                            <span>{t('Plan name (English)', 'اسم الباقة (إنجليزي)')}</span>
                            <Input value={plan.name} onChange={(e) => update(index, { name: e.target.value, key: plan.key || slugOf(e.target.value, `plan-${index + 1}`) })} />
                        </label>
                        <label className="text-sm space-y-1">
                            <span>{t('Plan name (Arabic)', 'اسم الباقة (عربي)')}</span>
                            <Input value={plan.name_ar ?? ''} onChange={(e) => update(index, { name_ar: e.target.value })} />
                        </label>
                        <label className="text-sm space-y-1">
                            <span>{t('Price', 'السعر')}</span>
                            <Input type="number" min="0" step="0.01" value={plan.price} onChange={(e) => update(index, { price: e.target.value })} />
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <label className="text-sm space-y-1">
                                <span>{t('Points', 'نقاط')}</span>
                                <Input type="number" min="0" value={plan.points ?? ''} onChange={(e) => update(index, { points: e.target.value })} />
                            </label>
                            <label className="text-sm space-y-1">
                                <span>{t('Bots', 'بوتات')}</span>
                                <Input type="number" min="0" value={plan.bots ?? ''} onChange={(e) => update(index, { bots: e.target.value })} />
                            </label>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label className="text-sm space-y-1">
                            <span>{t('Short description (English)', 'وصف قصير (إنجليزي)')}</span>
                            <Input value={plan.description ?? ''} onChange={(e) => update(index, { description: e.target.value })} />
                        </label>
                        <label className="text-sm space-y-1">
                            <span>{t('Short description (Arabic)', 'وصف قصير (عربي)')}</span>
                            <Input value={plan.description_ar ?? ''} onChange={(e) => update(index, { description_ar: e.target.value })} />
                        </label>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label className="text-sm space-y-1">
                            <span>{t('What is included, one line each (English)', 'اللي داخل في الباقة، سطر لكل بند (إنجليزي)')}</span>
                            <textarea className="w-full min-h-28 rounded-md border bg-background px-3 py-2 text-sm" value={linesOf(plan.features)} onChange={(e) => update(index, { features: listOf(e.target.value) })} />
                        </label>
                        <label className="text-sm space-y-1">
                            <span>{t('What is included, one line each (Arabic)', 'اللي داخل في الباقة، سطر لكل بند (عربي)')}</span>
                            <textarea dir="rtl" className="w-full min-h-28 rounded-md border bg-background px-3 py-2 text-sm" value={linesOf(plan.features_ar)} onChange={(e) => update(index, { features_ar: listOf(e.target.value) })} />
                        </label>
                    </div>
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange(plans.filter((_, i) => i !== index))}>
                        {t('Remove this plan', 'حذف الباقة دي')}
                    </Button>
                </div>
            ))}
            <Button type="button" variant="outline" onClick={() => onChange([...plans, { key: '', name: '', price: '' }])}>
                {t('Add a plan', 'إضافة باقة')}
            </Button>
        </div>
    );
}
