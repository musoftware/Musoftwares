import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Ticket } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function CreateTicket() {
    const [form, setForm] = useState({
        title: '',
        description: '',
        priority: 'Normal'
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.tickets.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Ticket" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'tickets' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Create New Ticket</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Submit a new support ticket or task.</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardHeader>
                        <CardTitle className="text-white flex items-center gap-2">
                            <Ticket className="w-5 h-5" /> Ticket Details
                        </CardTitle>
                        <CardDescription className="text-zinc-400">
                            Describe the issue or task that needs to be addressed.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Ticket Subject <span className="text-red-400">*</span></label>
                                    <Input 
                                        required 
                                        value={form.title} 
                                        onChange={e => setForm({...form, title: e.target.value})} 
                                        placeholder="Invoice transaction double charge error" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.title && <p className="text-xs text-red-400">{errors.title}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Priority</label>
                                    <Select value={form.priority} onValueChange={(val) => setForm({...form, priority: val})}>
                                        <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white w-full">
                                            <SelectValue placeholder="Select priority" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                            <SelectItem value="Low">Low</SelectItem>
                                            <SelectItem value="Normal">Normal</SelectItem>
                                            <SelectItem value="High">High</SelectItem>
                                            <SelectItem value="Urgent">Urgent</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.priority && <p className="text-xs text-red-400">{errors.priority}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Description</label>
                                    <Textarea 
                                        value={form.description} 
                                        onChange={e => setForm({...form, description: e.target.value})} 
                                        placeholder="Provide more details about this ticket..." 
                                        className="bg-zinc-950 border-zinc-800 text-white min-h-[100px]"
                                    />
                                    {errors.description && <p className="text-xs text-red-400">{errors.description}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                <Link href={route('erp.dashboard', { section: 'tickets' })}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                    {isSubmitting ? 'Creating...' : 'Create Ticket'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
