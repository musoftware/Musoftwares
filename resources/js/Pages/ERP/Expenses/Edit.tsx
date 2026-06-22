import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Edit2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface EditExpenseProps {
    expense: {
        id: number;
        title: string;
        amount: string | number;
        category: string;
        date: string;
        description: string;
    };
}

export default function EditExpense({ expense }: EditExpenseProps) {
    const [form, setForm] = useState({
        title: expense.title,
        amount: expense.amount.toString(),
        category: expense.category,
        date: expense.date,
        description: expense.description === '-' ? '' : expense.description
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.put(route('erp.expenses.update', expense.id), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('expenses');

    return (
        <ERPLayout title={__('general.edit_expense')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={__('general.edit_expense')} />
            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'expenses' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.edit_expense')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">{__('general.modify_the_details_of_this_business_expense')}</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Edit2 className="w-5 h-5 text-indigo-600" />{__('general.expense_details')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.update_the_details_of_the_expense_and_save_changes')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.title')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.title} 
                                        onChange={e => setForm({...form, title: e.target.value})} 
                                        placeholder={__('general.figma_enterprise_seats')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.amount')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.amount} 
                                        onChange={e => setForm({...form, amount: e.target.value})} 
                                        placeholder="99.00" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.amount && <p className="text-xs text-red-500">{errors.amount}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.date')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        type="date"
                                        value={form.date} 
                                        onChange={e => setForm({...form, date: e.target.value})} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.date && <p className="text-xs text-red-500">{errors.date}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.category')}</label>
                                    <Input 
                                        value={form.category} 
                                        onChange={e => setForm({...form, category: e.target.value})} 
                                        placeholder={__('general.software_subscriptions')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.category && <p className="text-xs text-red-500">{errors.category}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.description')}</label>
                                    <Textarea 
                                        value={form.description} 
                                        onChange={e => setForm({...form, description: e.target.value})} 
                                        placeholder={__('general.optional_description_notes_about_the_expense')} 
                                        className="bg-white border-slate-200 text-slate-900 min-h-[100px]"
                                    />
                                    {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'expenses' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        {__('general.cancel')}</Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
