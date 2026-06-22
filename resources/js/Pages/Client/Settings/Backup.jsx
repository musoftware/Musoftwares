import React, { useRef, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Download, Upload, AlertTriangle, FileJson } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { __ } from '@/lib/i18n';

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
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">{__('general.data_management')}</h2>}>
            <Head title={__('general.backup_restore')} />

            <div className="py-12 max-w-7xl w-full mx-auto sm:px-6 lg:px-8 space-y-8">
                
                {flash?.success && (
                    <Alert className="bg-emerald-50 border-emerald-200 text-emerald-800">
                        <AlertTitle>{__('general.success')}</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert className="bg-red-50 border-red-200 text-red-800">
                        <AlertTitle>{__('general.error')}</AlertTitle>
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
                        <CardDescription>{__('general.download_a_complete_json_backup_of_all_your_erp_clients_invoices_tasks_and_crm_leads_campaigns_records')}</CardDescription>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <p className="text-sm text-slate-600 mb-4">{__('general.keep_this_file_safe_it_contains_sensitive_business_data_and_can_be_used_to_restore_your_account_if_needed')}</p>
                        <a href={route('settings.backup.export')} target="_blank" rel="noopener noreferrer">
                            <Button className="bg-indigo-600 hover:bg-indigo-700">
                                <Download className="w-4 h-4 me-2" />{__('general.download_json_backup')}</Button>
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
                        <CardDescription className="text-red-700/80">{__('general.upload_a_previously_downloaded_json_backup_file_to_restore_your_account')}</CardDescription>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <Alert variant="destructive" className="mb-6 bg-white">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>{__('general.danger_zone')}</AlertTitle>
                            <AlertDescription>{__('general.restoring_from_a_backup_will')}<strong>{__('general.permanently_delete')}</strong>{__('general.your_current_crm_and_erp_data_and_replace_it_entirely_with_the_data_from_the_backup_file_this_action_cannot_be_undone')}</AlertDescription>
                        </Alert>

                        <form onSubmit={handleImport} className="space-y-4">
                            <div className="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:bg-slate-50 transition-colors">
                                <FileJson className="w-8 h-8 text-slate-400 mx-auto mb-2" />
                                <label className="block text-sm font-medium text-slate-700 cursor-pointer">
                                    <span className="text-indigo-600 hover:text-indigo-500">{__('general.upload_a_file')}</span>{__('general.or_drag_and_drop')}<input 
                                        type="file" 
                                        className="hidden" 
                                        accept=".json" 
                                        ref={fileInputRef}
                                        onChange={handleFileChange}
                                    />
                                </label>
                                <p className="text-xs text-slate-500 mt-1">{__('general.json_backup_files_only_up_to_10mb')}</p>
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
