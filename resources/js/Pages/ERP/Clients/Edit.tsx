import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Edit2 } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { CurrencySelect } from '@/Components/CurrencySelect';
import { __ } from '@/lib/i18n';

export default function EditClient({ client, currencies }: { client: any, currencies: any[] }) {
    const [form, setForm] = useState({
        name: client.name || '',
        email: client.email || '',
        phone: client.phone || '',
        address: client.address || '',
        currency: client.currency?.currency,
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

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title={`Edit — ${client.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'clients' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.edit_client')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">Update billing information for {client.name}.</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Edit2 className="w-5 h-5" />{__('general.client_profile')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.modify_the_existing_client_profile_information_below')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.client_company_name')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder={__('general.acme_corp')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.email_address')}</label>
                                    <Input 
                                        type="email" 
                                        value={form.email} 
                                        onChange={e => setForm({...form, email: e.target.value})} 
                                        placeholder={__('general.contact_acme_com')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.email && <p className="text-xs text-red-500">{errors.email}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.phone_number')}</label>
                                    <Input 
                                        value={form.phone} 
                                        onChange={e => setForm({...form, phone: e.target.value})} 
                                        placeholder="+1 234 567 890" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.phone && <p className="text-xs text-red-500">{errors.phone}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.billing_currency')}<span className="text-red-500">*</span></label>
                                    <CurrencySelect 
                                        currencies={currencies}
                                        value={form.currency}
                                        onChange={(val) => setForm({...form, currency: val})}
                                        valueKey="currency"
                                    />
                                    {errors.currency && <p className="text-xs text-red-500">{errors.currency}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.address')}</label>
                                    <Input 
                                        value={form.address} 
                                        onChange={e => setForm({...form, address: e.target.value})} 
                                        placeholder="123 Business St, City, Country" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.address && <p className="text-xs text-red-500">{errors.address}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'clients' })}>
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

