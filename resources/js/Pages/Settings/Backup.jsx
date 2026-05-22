import React, { useRef, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Download, Upload, AlertTriangle, FileJson } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export default function Backup({ auth, flash }) {
    const fileInputRef = useRef(null);
    const [selectedFile, setSelectedFile] = useState(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        backup_file: null,
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        setSelectedFile(file);
        setData('backup_file', file);
    };

    const handleImport = (e) => {
        e.preventDefault();
        if (!confirm("WARNING: This will completely WIPE your existing CRM and ERP data and replace it with the data from the backup file. Are you absolutely sure?")) {
            return;
        }

        post(route('settings.backup.import'), {
            onSuccess: () => {
                reset();
                setSelectedFile(null);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Data Management</h2>}>
            <Head title="Backup & Restore" />

            <div className="py-12 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                {flash?.success && (
                    <Alert className="bg-emerald-50 border-emerald-200 text-emerald-800">
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert className="bg-red-50 border-red-200 text-red-800">
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* Export Card */}
                <Card className="border-slate-200">
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                        <div className="flex items-center gap-2">
                            <Download className="w-5 h-5 text-indigo-600" />
                            <CardTitle>Export Data (Backup)</CardTitle>
                        </div>
                        <CardDescription>
                            Download a complete JSON backup of all your ERP (Clients, Invoices, Tasks) and CRM (Leads, Campaigns) records.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <p className="text-sm text-slate-600 mb-4">
                            Keep this file safe. It contains sensitive business data and can be used to restore your account if needed.
                        </p>
                        <a href={route('settings.backup.export')} target="_blank" rel="noopener noreferrer">
                            <Button className="bg-indigo-600 hover:bg-indigo-700">
                                <Download className="w-4 h-4 mr-2" /> Download JSON Backup
                            </Button>
                        </a>
                    </CardContent>
                </Card>

                {/* Import Card */}
                <Card className="border-red-100 border-2 shadow-sm">
                    <CardHeader className="bg-red-50/50 border-b border-red-100">
                        <div className="flex items-center gap-2">
                            <Upload className="w-5 h-5 text-red-600" />
                            <CardTitle className="text-red-900">Restore Data (Import)</CardTitle>
                        </div>
                        <CardDescription className="text-red-700/80">
                            Upload a previously downloaded JSON backup file to restore your account.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <Alert variant="destructive" className="mb-6 bg-white">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>Danger Zone</AlertTitle>
                            <AlertDescription>
                                Restoring from a backup will <strong>permanently delete</strong> your current CRM and ERP data and replace it entirely with the data from the backup file. This action cannot be undone.
                            </AlertDescription>
                        </Alert>

                        <form onSubmit={handleImport} className="space-y-4">
                            <div className="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:bg-slate-50 transition-colors">
                                <FileJson className="w-8 h-8 text-slate-400 mx-auto mb-2" />
                                <label className="block text-sm font-medium text-slate-700 cursor-pointer">
                                    <span className="text-indigo-600 hover:text-indigo-500">Upload a file</span> or drag and drop
                                    <input 
                                        type="file" 
                                        className="hidden" 
                                        accept=".json" 
                                        ref={fileInputRef}
                                        onChange={handleFileChange}
                                    />
                                </label>
                                <p className="text-xs text-slate-500 mt-1">JSON backup files only up to 10MB</p>
                            </div>

                            {selectedFile && (
                                <div className="text-sm text-slate-600 bg-slate-100 px-3 py-2 rounded flex justify-between items-center">
                                    <span>Selected: <strong>{selectedFile.name}</strong></span>
                                </div>
                            )}
                            
                            {errors.backup_file && <p className="text-sm text-red-500">{errors.backup_file}</p>}

                            <Button 
                                type="submit" 
                                variant="destructive" 
                                className="w-full" 
                                disabled={!selectedFile || processing}
                            >
                                {processing ? 'Restoring Data...' : 'Wipe & Restore Data'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

            </div>
        </AuthenticatedLayout>
    );
}
