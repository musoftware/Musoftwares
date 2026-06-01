import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import {
    UserPlus,
} from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { __ } from '@/lib/i18n';

interface Props {
    currencies: Array<{ id: number; currency: string; name: string }>;
    tenant?: { id: number; name: string; user_id: number };
    hasMultiCurrency?: boolean;
    baseCurrency?: string;
}

export default function CreateClient({ currencies, tenant, hasMultiCurrency = false, baseCurrency = 'USD' }: Props) {
    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        address: '',
        currency: hasMultiCurrency ? 'USD' : baseCurrency,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.clients.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients', { tenantId: tenant?.id?.toString() });

    return (
        <ERPLayout
            title={__('general.add_client')}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
        >
            <div className="space-y-6">
                {/* Page Header */}
                <div>
                    <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.add_new_client')}</h2>
                    <p className="text-sm text-slate-500 mt-1">{__('general.register_a_new_client_in_your_workspace')}</p>
                </div>

                {/* Form Card */}
                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <UserPlus className="w-5 h-5" />{__('general.client_details')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.provide_the_necessary_information_to_setup_the_client_profile_and_wallet')}</CardDescription>
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
                                {hasMultiCurrency && (
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.billing_currency')}<span className="text-red-500">*</span></label>
                                    <Select value={form.currency} onValueChange={(val) => setForm({...form, currency: val})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder={__('general.select_currency_1')} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            {currencies.map(c => (
                                                <SelectItem key={c.currency} value={c.currency}>
                                                    {c.currency} - {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.currency && <p className="text-xs text-red-500">{errors.currency}</p>}
                                </div>
                                )}
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Address</label>
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
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Saving...' : 'Save Client'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
