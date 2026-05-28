import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

    return (
        <AuthenticatedLayout>
            <Head title="Configure Storage Provider" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'system' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Configure Storage Provider</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Add an S3-compatible cloud storage bucket.</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardHeader>
                        <CardTitle className="text-white flex items-center gap-2">
                            <Database className="w-5 h-5" /> Storage Credentials
                        </CardTitle>
                        <CardDescription className="text-zinc-400">
                            Securely connect your workspace to a cloud storage bucket.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Provider Name <span className="text-red-400">*</span></label>
                                    <Input 
                                        required 
                                        value={form.name} 
                                        onChange={e => setForm({...form, name: e.target.value})} 
                                        placeholder="AWS US-East 1" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.name && <p className="text-xs text-red-400">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Driver <span className="text-red-400">*</span></label>
                                    <Select value={form.driver} onValueChange={(val) => setForm({...form, driver: val})}>
                                        <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white">
                                            <SelectValue placeholder="Select driver" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                            <SelectItem value="s3">AWS S3</SelectItem>
                                            <SelectItem value="s3-cloudflare">Cloudflare R2</SelectItem>
                                            <SelectItem value="s3-digitalocean">DigitalOcean Spaces</SelectItem>
                                            <SelectItem value="s3-wasabi">Wasabi</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.driver && <p className="text-xs text-red-400">{errors.driver}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Access Key <span className="text-red-400">*</span></label>
                                    <Input 
                                        required
                                        value={form.key} 
                                        onChange={e => setForm({...form, key: e.target.value})} 
                                        placeholder="AKIAIOSFODNN7EXAMPLE" 
                                        className="bg-zinc-950 border-zinc-800 text-white font-mono text-sm"
                                    />
                                    {errors.key && <p className="text-xs text-red-400">{errors.key}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Secret Key <span className="text-red-400">*</span></label>
                                    <Input 
                                        required
                                        type="password"
                                        value={form.secret} 
                                        onChange={e => setForm({...form, secret: e.target.value})} 
                                        placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" 
                                        className="bg-zinc-950 border-zinc-800 text-white font-mono text-sm"
                                    />
                                    {errors.secret && <p className="text-xs text-red-400">{errors.secret}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Bucket Name <span className="text-red-400">*</span></label>
                                    <Input 
                                        required
                                        value={form.bucket} 
                                        onChange={e => setForm({...form, bucket: e.target.value})} 
                                        placeholder="my-company-assets" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.bucket && <p className="text-xs text-red-400">{errors.bucket}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-zinc-300">Region</label>
                                    <Input 
                                        value={form.region} 
                                        onChange={e => setForm({...form, region: e.target.value})} 
                                        placeholder="us-east-1" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.region && <p className="text-xs text-red-400">{errors.region}</p>}
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <label className="text-sm font-medium text-zinc-300">Custom Endpoint (Optional)</label>
                                    <Input 
                                        value={form.endpoint} 
                                        onChange={e => setForm({...form, endpoint: e.target.value})} 
                                        placeholder="https://s3.us-east-1.amazonaws.com" 
                                        className="bg-zinc-950 border-zinc-800 text-white"
                                    />
                                    {errors.endpoint && <p className="text-xs text-red-400">{errors.endpoint}</p>}
                                </div>
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                <Link href={route('erp.dashboard', { section: 'system' })}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                    {isSubmitting ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
