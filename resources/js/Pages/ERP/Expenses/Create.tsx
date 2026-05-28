import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, DollarSign } from 'lucide-react';

export default function CreateExpense() {
    const [form, setForm] = useState({
        title: '',
        amount: '',
        category: '',
        date: ''
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.expenses.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Log Expense" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'expenses' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Log Expense</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Record a new business expense.</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardHeader>
                        <CardTitle className="text-white flex items-center gap-2">
                            <DollarSign className="w-5 h-5" /> Expense Details
                        </CardTitle>
                        <CardDescription className="text-zinc-400">
                            Enter the details of the expense you want to log.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Title <span className="text-red-400">*</span></label>
                                    <Input 
                                        required 
                                        value={form.title} 
                                        onChange={e => setForm({...form, title: e.target.value})} 
                                        placeholder="Figma Enterprise seats" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.title && <p className="text-xs text-red-400">{errors.title}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Amount <span className="text-red-400">*</span></label>
                                    <Input 
                                        required
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.amount} 
                                        onChange={e => setForm({...form, amount: e.target.value})} 
                                        placeholder="99.00" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.amount && <p className="text-xs text-red-400">{errors.amount}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Category</label>
                                    <Input 
                                        value={form.category} 
                                        onChange={e => setForm({...form, category: e.target.value})} 
                                        placeholder="Software Subscriptions" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.category && <p className="text-xs text-red-400">{errors.category}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                <Link href={route('erp.dashboard', { section: 'expenses' })}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                    {isSubmitting ? 'Saving...' : 'Log Expense'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
