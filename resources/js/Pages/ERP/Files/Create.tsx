import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

    return (
        <AuthenticatedLayout>
            <Head title="Upload Document" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('erp.dashboard', { section: 'documents' })} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-white">Upload Document</h1>
                        <p className="text-zinc-400 text-sm mt-0.5">Upload a new file to your tenant storage.</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardHeader>
                        <CardTitle className="text-white flex items-center gap-2">
                            <UploadCloud className="w-5 h-5" /> File Upload
                        </CardTitle>
                        <CardDescription className="text-zinc-400">
                            Select a file from your computer and assign a category.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-zinc-300">File <span className="text-red-400">*</span></label>
                                <Input 
                                    required 
                                    type="file"
                                    onChange={e => setForm({...form, file: e.target.files ? e.target.files[0] : null})} 
                                    className="bg-zinc-950 border-zinc-800 text-zinc-300 file:bg-zinc-800 file:text-zinc-300 file:border-0 file:rounded-md file:mr-4 file:px-4 file:py-2 hover:file:bg-zinc-700 transition-colors"
                                />
                                {errors.file && <p className="text-xs text-red-400">{errors.file}</p>}
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-zinc-300">Document Category</label>
                                <Select value={form.type} onValueChange={(val) => setForm({...form, type: val})}>
                                    <SelectTrigger className="bg-zinc-950 border-zinc-800 text-white">
                                        <SelectValue placeholder="Select Category" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-zinc-900 border-zinc-800 text-white">
                                        <SelectItem value="Document">Document</SelectItem>
                                        <SelectItem value="Design Asset">Design Asset</SelectItem>
                                        <SelectItem value="Invoice PDF">Invoice PDF</SelectItem>
                                        <SelectItem value="Brief">Brief</SelectItem>
                                        <SelectItem value="Contract">Contract</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && <p className="text-xs text-red-400">{errors.type}</p>}
                            </div>
                            
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                                <Link href={route('erp.dashboard', { section: 'documents' })}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white hover:bg-zinc-800">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting} className="bg-violet-600 hover:bg-violet-500 text-white">
                                    {isSubmitting ? 'Uploading...' : 'Upload File'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
