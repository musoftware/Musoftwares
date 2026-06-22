import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Database } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { __ } from '@/lib/i18n';

export default function CreateStorageProvider() {
    const [form, setForm] = useState({
        name: '',
        driver: 's3',
        key: '',
        secret: '',
        region: '',
        bucket: '',
        endpoint: ''
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('erp.storage-providers.store'), form, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('settings');

    return (
        <ERPLayout title={__('general.configure_storage_provider')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'system' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.configure_storage_provider')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">{__('general.add_an_s3_compatible_cloud_storage_bucket')}</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Database className="w-5 h-5" />{__('general.storage_credentials')}</CardTitle>
                        <CardDescription className="text-slate-500">{__('general.securely_connect_your_workspace_to_a_cloud_storage_bucket')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.provider_name')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder={__('general.aws_us_east_1')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.driver')} <span className="text-red-500">*</span></label>
                                    <Select value={form.driver} onValueChange={(val) => setForm({...form, driver: val || ''})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder={__('general.select_driver')} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            <SelectItem value="s3">{__('general.aws_s3')}</SelectItem>
                                            <SelectItem value="s3-cloudflare">{__('general.cloudflare_r2')}</SelectItem>
                                            <SelectItem value="s3-digitalocean">{__('general.digitalocean_spaces')}</SelectItem>
                                            <SelectItem value="s3-wasabi">{__('general.wasabi')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.driver && <p className="text-xs text-red-500">{errors.driver}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.access_key')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        value={form.key} 
                                        onChange={e => setForm({...form, key: e.target.value})} 
                                        placeholder={__('general.akiaiosfodnn7example')} 
                                        className="bg-white border-slate-200 text-slate-900 font-mono text-sm"
                                    />
                                    {errors.key && <p className="text-xs text-red-500">{errors.key}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.secret_key')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        type="password"
                                        value={form.secret} 
                                        onChange={e => setForm({...form, secret: e.target.value})} 
                                        placeholder={__('general.wjalrxutnfemi_k7mdeng_bpxrficyexamplekey')} 
                                        className="bg-white border-slate-200 text-slate-900 font-mono text-sm"
                                    />
                                    {errors.secret && <p className="text-xs text-red-500">{errors.secret}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.bucket_name')}<span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        value={form.bucket} 
                                        onChange={e => setForm({...form, bucket: e.target.value})} 
                                        placeholder={__('general.my_company_assets')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.bucket && <p className="text-xs text-red-500">{errors.bucket}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.region')}</label>
                                    <Input 
                                        value={form.region} 
                                        onChange={e => setForm({...form, region: e.target.value})} 
                                        placeholder={__('general.us_east_1')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.region && <p className="text-xs text-red-500">{errors.region}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">{__('general.custom_endpoint_optional')}</label>
                                    <Input 
                                        value={form.endpoint} 
                                        onChange={e => setForm({...form, endpoint: e.target.value})} 
                                        placeholder={__('general.https_s3_us_east_1_amazonaws_com')} 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.endpoint && <p className="text-xs text-red-500">{errors.endpoint}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'system' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        {__('general.cancel')}
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? __('general.saving') : __('general.save_configuration')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
