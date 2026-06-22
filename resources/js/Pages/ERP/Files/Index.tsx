import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Plus, Download, Trash, FileText, Database } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { MoreHorizontal } from 'lucide-react';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';

interface FileRecord {
    id: number;
    name: string;
    mime_type: string;
    size: number;
    folder: string;
    uploaded_by: string;
    created_at: string;
}

interface IndexProps {
    files: FileRecord[];
    storageProviders: any[];
    hasFeature: boolean;
    hasProvider: boolean;
}

export default function Index({ files, storageProviders, hasFeature, hasProvider }: IndexProps) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('documents');

    if (!hasFeature) {
        return (
            <ERPLayout title={__('general.document_storage')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
                <UpgradeOverlay module="erp-document-storage" title={__('general.document_storage')} description={__('general.upgrade_document_storage_description', undefined, 'Upgrade to access document storage.')} icon={Database} priceText={__('general.upgrade_now')} />
            </ERPLayout>
        );
    }

    const formatBytes = (bytes: number, decimals = 2) => {
        if (!+bytes) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    };

    const handleDelete = (id: number) => {
        if (confirm(__('general.are_you_sure_you_want_to_delete_this_file'))) {
            router.delete(route('erp.files.destroy', id));
        }
    };

    return (
        <ERPLayout title={__('general.document_storage')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-8">
                
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.document_storage')}</h1>
                        <p className="text-slate-500 text-sm mt-0.5">{__('general.manage_your_secure_cloud_documents')}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        {!hasProvider ? (
                            <Link href={route('erp.storage-providers.create')}>
                                <Button variant="outline" className="gap-2">
                                    <Database className="w-4 h-4" />
                                    {__('general.configure_storage')}
                                </Button>
                            </Link>
                        ) : (
                            <Link href={route('erp.files.create')}>
                                <Button className="gap-2">
                                    <Plus className="w-4 h-4" />
                                    {__('general.upload_file')}
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {!hasProvider && (
                    <div className="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
                        {__('general.storage_provider_warning_message')}
                    </div>
                )}

                <Card className="bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader className="bg-slate-50 border-b border-slate-200">
                            <TableRow>
                                <TableHead className="text-slate-600 font-semibold">{__('general.file_name')}</TableHead>
                                <TableHead className="text-slate-600 font-semibold">{__('general.category')}</TableHead>
                                <TableHead className="text-slate-600 font-semibold">{__('general.size')}</TableHead>
                                <TableHead className="text-slate-600 font-semibold">{__('general.uploaded_by')}</TableHead>
                                <TableHead className="text-slate-600 font-semibold">{__('general.date')}</TableHead>
                                <TableHead className="text-end text-slate-600 font-semibold">{__('general.actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {files.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-12 text-slate-500">
                                        <div className="flex flex-col items-center justify-center gap-2">
                                            <FileText className="w-8 h-8 text-slate-300" />
                                            <p>{__('general.no_files_found')}</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                files.map(file => (
                                    <TableRow key={file.id} className="hover:bg-slate-50 transition-colors">
                                        <TableCell className="font-medium text-slate-900">
                                            <div className="flex items-center gap-2">
                                                <FileText className="w-4 h-4 text-slate-400" />
                                                {file.name}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-slate-600">{file.folder}</TableCell>
                                        <TableCell className="text-slate-600">{formatBytes(file.size)}</TableCell>
                                        <TableCell className="text-slate-600">{file.uploaded_by}</TableCell>
                                        <TableCell className="text-slate-600">{file.created_at}</TableCell>
                                        <TableCell className="text-end">
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">{__('general.open_menu')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent className="sm:max-w-xs">
                                                    <DialogHeader>
                                                        <DialogTitle>{__('general.actions')}</DialogTitle>
                                                    </DialogHeader>
                                                    <div className="flex flex-col gap-2 py-2">
                                                        <a href={route('erp.files.show', file.id)} target="_blank" rel="noopener noreferrer">
                                                            <Button variant="outline" className="w-full justify-start gap-2">
                                                                <Download className="w-4 h-4" />
                                                                {__('general.download')}
                                                            </Button>
                                                        </a>
                                                        <Button variant="destructive" className="justify-start gap-2" onClick={() => handleDelete(file.id)}>
                                                            <Trash className="w-4 h-4" />
                                                            {__('general.delete')}
                                                        </Button>
                                                    </div>
                                                </DialogContent>
                                            </Dialog>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </Card>
            </div>
        </ERPLayout>
    );
}
