import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, Database } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

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
    const { menuItems, workspaceName, tenantId } = useERPMenu('settings');

    return (
        <ERPLayout title="Configure Storage Provider" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'system' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Configure Storage Provider</h1>
                        <p className="text-slate-500 text-sm mt-0.5">Add an S3-compatible cloud storage bucket.</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <Database className="w-5 h-5" /> Storage Credentials
                        </CardTitle>
                        <CardDescription className="text-slate-500">
                            Securely connect your workspace to a cloud storage bucket.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Provider Name <span className="text-red-500">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder="AWS US-East 1" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Driver <span className="text-red-500">*</span></label>
                                    <Select value={form.driver} onValueChange={(val) => setForm({...form, driver: val})}>
                                        <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                            <SelectValue placeholder="Select driver" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white border-slate-200 text-slate-900">
                                            <SelectItem value="s3">AWS S3</SelectItem>
                                            <SelectItem value="s3-cloudflare">Cloudflare R2</SelectItem>
                                            <SelectItem value="s3-digitalocean">DigitalOcean Spaces</SelectItem>
                                            <SelectItem value="s3-wasabi">Wasabi</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.driver && <p className="text-xs text-red-500">{errors.driver}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Access Key <span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        value={form.key} 
                                        onChange={e => setForm({...form, key: e.target.value})} 
                                        placeholder="AKIAIOSFODNN7EXAMPLE" 
                                        className="bg-white border-slate-200 text-slate-900 font-mono text-sm"
                                    />
                                    {errors.key && <p className="text-xs text-red-500">{errors.key}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Secret Key <span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        type="password"
                                        value={form.secret} 
                                        onChange={e => setForm({...form, secret: e.target.value})} 
                                        placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" 
                                        className="bg-white border-slate-200 text-slate-900 font-mono text-sm"
                                    />
                                    {errors.secret && <p className="text-xs text-red-500">{errors.secret}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Bucket Name <span className="text-red-500">*</span></label>
                                    <Input 
                                        required
                                        value={form.bucket} 
                                        onChange={e => setForm({...form, bucket: e.target.value})} 
                                        placeholder="my-company-assets" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.bucket && <p className="text-xs text-red-500">{errors.bucket}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-slate-700">Region</label>
                                    <Input 
                                        value={form.region} 
                                        onChange={e => setForm({...form, region: e.target.value})} 
                                        placeholder="us-east-1" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.region && <p className="text-xs text-red-500">{errors.region}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-slate-700">Custom Endpoint (Optional)</label>
                                    <Input 
                                        value={form.endpoint} 
                                        onChange={e => setForm({...form, endpoint: e.target.value})} 
                                        placeholder="https://s3.us-east-1.amazonaws.com" 
                                        className="bg-white border-slate-200 text-slate-900"
                                    />
                                    {errors.endpoint && <p className="text-xs text-red-500">{errors.endpoint}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'system' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
