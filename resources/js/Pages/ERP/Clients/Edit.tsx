import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Edit2 } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function EditClient({ client, currencies }: { client: any, currencies: any[] }) {
    const [form, setForm] = useState({
        name: client.name || '',
        email: client.email || '',
        phone: client.phone || '',
        address: client.address || '',
        currency: client.currency?.currency || 'USD',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.put(route('erp.clients.update', client.id), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit Client — ${client.name}`} />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'clients' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Edit Client</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Update billing information for {client.name}.</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardHeader>
                        <CardTitle className="text-white flex items-center gap-2">
                            <Edit2 className="w-5 h-5" /> Client Profile
                        </CardTitle>
                        <CardDescription className="text-zinc-400">
                            Modify the existing client profile information below.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Client/Company Name <span className="text-red-400">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder="Acme Corp" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.name && <p className="text-xs text-red-400">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Email Address</label>
                                    <Input 
                                        type="email" 
                                        value={form.email} 
                                        onChange={e => setForm({...form, email: e.target.value})} 
                                        placeholder="contact@acme.com" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.email && <p className="text-xs text-red-400">{errors.email}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Phone Number</label>
                                    <Input 
                                        value={form.phone} 
                                        onChange={e => setForm({...form, phone: e.target.value})} 
                                        placeholder="+1 234 567 890" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.phone && <p className="text-xs text-red-400">{errors.phone}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Billing Currency <span className="text-red-400">*</span></label>
                                    <Select value={form.currency} onValueChange={(val) => setForm({...form, currency: val})}>
                                        <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white">
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                            {currencies.map(c => (
                                                <SelectItem key={c.currency} value={c.currency}>
                                                    {c.currency} - {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.currency && <p className="text-xs text-red-400">{errors.currency}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Address</label>
                                    <Input 
                                        value={form.address} 
                                        onChange={e => setForm({...form, address: e.target.value})} 
                                        placeholder="123 Business St, City, Country" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.address && <p className="text-xs text-red-400">{errors.address}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                <Link href={route('erp.dashboard', { section: 'clients' })}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                    {isSubmitting ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
