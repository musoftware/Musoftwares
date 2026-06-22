import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, FileSignature } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { __ } from '@/lib/i18n';

export default function CreateContract({ clients = [] }: { clients?: any[] }) {
    const [form, setForm] = useState({
        title: '',
        client: '',
        value: '',
        status: 'Draft'
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.contracts.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('overview');

    return (
        <ERPLayout title={__('general.draft_contract')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'documents' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.draft_new_contract')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">{__('general.create_a_new_agreement_or_contract_sheet')}</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <FileSignature className="w-5 h-5" />{__('general.contract_details')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.enter_the_details_of_the_contract_agreement')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.agreement_sheet_title')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.title} 
                                        onChange={e => setForm({...form, title: e.target.value})} 
                                        placeholder={__('general.mutual_non_disclosure_agreement')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.client')}<span className="text-red-500">*</span></label>
                                    <Select value={form.client} onValueChange={(val) => setForm({...form, client: val || ''})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder={__('general.select_client')} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            {clients.map(c => (
                                                <SelectItem key={c.id} value={c.id.toString()}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.client && <p className="text-xs text-red-500">{errors.client}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Total Contract Value ($)</label>
                                    <Input 
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.value} 
                                        onChange={e => setForm({...form, value: e.target.value})} 
                                        placeholder="15000.00" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.value && <p className="text-xs text-red-500">{errors.value}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'documents' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        {__('general.cancel')}</Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Drafting...' : 'Draft Contract'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
