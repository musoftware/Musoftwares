import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, UploadCloud } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function CreateFile() {
    const [form, setForm] = useState({
        file: null as File | null,
        type: 'Document'
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!form.file) {
            setErrors({ file: 'Please select a file to upload.' });
            return;
        }

        setIsSubmitting(true);
        
        const formData = new FormData();
        formData.append('file', form.file);
        formData.append('type', form.type);

        router.post(route('erp.files.store'), formData, {
            onSuccess: () => setIsSubmitting(false),
            onError: (errs) => {
                setErrors(errs);
                setIsSubmitting(false);
            }
        });
    };

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('documents');

    return (
        <ERPLayout title="Upload Document" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'documents' })} className="text-slate-400 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Upload Document</h1>
                        <p className="text-slate-500 text-sm mt-0.5">Upload a new file to your tenant storage.</p>
                    </div>
                </div>

                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-slate-900 flex items-center gap-2">
                            <UploadCloud className="w-5 h-5" /> File Upload
                        </CardTitle>
                        <CardDescription className="text-slate-500">
                            Select a file from your computer and assign a category.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-slate-700">File <span className="text-red-500">*</span></label>
                                <Input 
                                    required 
                                    type="file"
                                    onChange={e => setForm({...form, file: e.target.files ? e.target.files[0] : null})} 
                                    className="bg-white border-slate-200 text-slate-700 file:bg-slate-100 file:text-slate-700 file:border-0 file:rounded-md file:mr-4 file:px-4 file:py-2 hover:file:bg-slate-200 transition-colors"
                                />
                                {errors.file && <p className="text-xs text-red-500">{errors.file}</p>}
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-slate-700">Document Category</label>
                                <Select value={form.type} onValueChange={(val) => setForm({...form, type: val})}>
                                    <SelectTrigger className="bg-white border-slate-200 text-slate-900">
                                        <SelectValue placeholder="Select Category" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white border-slate-200 text-slate-900">
                                        <SelectItem value="Document">Document</SelectItem>
                                        <SelectItem value="Design Asset">Design Asset</SelectItem>
                                        <SelectItem value="Invoice PDF">Invoice PDF</SelectItem>
                                        <SelectItem value="Brief">Brief</SelectItem>
                                        <SelectItem value="Contract">Contract</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && <p className="text-xs text-red-500">{errors.type}</p>}
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Link href={route('erp.dashboard', { section: 'documents' })}>
                                    <Button type="button" variant="ghost" className="text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Uploading...' : 'Upload File'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}
