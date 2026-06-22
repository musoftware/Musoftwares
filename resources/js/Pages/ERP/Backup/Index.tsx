import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import { useERPMenu } from '@/hooks/useERPMenu';
import { 
    HardDrive, Download, UploadCloud, AlertTriangle, Lock, ArrowRight, Loader2
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { useToast } from '@/Components/ui/use-toast';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { __ } from '@/lib/i18n';

export default function BackupIndex({ hasBackupFeature }: { hasBackupFeature: boolean }) {
    const { auth } = usePage().props as any;
    const { toast } = useToast();
    
    const [isRestoring, setIsRestoring] = useState(false);
    const [restoreFile, setRestoreFile] = useState<File | null>(null);
    const [confirmRestore, setConfirmRestore] = useState(false);

    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('backup');

    const handleUpgradeSimulate = () => {
        router.visit(route('subscriptions.plans', { module: 'erp-backup' }));
    };

    const handleRestoreSubmit = () => {
        if (!restoreFile) return;
        setConfirmRestore(true);
    };

    const performRestore = () => {
        if (!restoreFile) return;
        setIsRestoring(true);
        setConfirmRestore(false);
        
        const formData = new FormData();
        formData.append('backup_file', restoreFile);

        router.post(route('erp.backup.restore'), formData, {
            forceFormData: true,
            onSuccess: () => {
                setRestoreFile(null);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string || 'Failed to restore backup.' });
            },
            onFinish: () => setIsRestoring(false)
        });
    };

    return (
        <ERPLayout 
            title={__('general.backup')} 
            menuItems={menuItems} 
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('general.erp_backup_service')} />
            
            <div className="max-w-4xl mx-auto space-y-6">
                <div>
                    <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.data_backup_restore')}</h2>
                    <p className="text-sm text-slate-500 mt-1">{__('general.download_a_complete_snapshot_of_your_workspace_or_restore_from_a_previous_backup')}</p>
                </div>

                {!hasBackupFeature ? (
                    <UpgradeOverlay 
                        title={__('general.secure_your_operational_data_with_one_click_backups')}
                        description={__('general.upgrade_your_workspace_to_unlock_the_erp_backup_service_download_full_snapshots_of_your_clients_invoices_tasks_and_ledgers_in_json_format_and_instantly_restore_them_if_needed')}
                        icon={HardDrive}
                        module="erp-backup"
                        priceText={__('general.unlock_backup_addon_price', {price: '500 EGP'})}
                    />
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        {/* Download Backup */}
                        <Card className="shadow-none border-border">
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Download className="h-5 w-5 text-primary" />{__('general.create_backup')}</CardTitle>
                                <CardDescription>{__('general.download_a_json_file_containing_all_your_clients_invoices_tasks_and_configurations')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="pt-4 border-t border-border mt-2">
                                    <a 
                                        href={route('erp.backup.download')} 
                                        className="flex items-center justify-center w-full h-10 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                                    >
                                        <Download className="me-2 h-4 w-4" />{__('general.download_backup_file')}</a>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Restore Backup */}
                        <Card className="shadow-none border-border border-destructive/20 relative overflow-hidden">
                            <div className="absolute top-0 end-0 p-4 opacity-10">
                                <AlertTriangle className="h-24 w-24 text-destructive" />
                            </div>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2 text-destructive">
                                    <UploadCloud className="h-5 w-5" />{__('general.restore_backup')}</CardTitle>
                                <CardDescription>{__('general.upload_a_previously_downloaded_json_backup_file_to_restore_your_workspace')}</CardDescription>
                            </CardHeader>
                            <CardContent className="relative z-10">
                                <div className="space-y-4 pt-4 border-t border-border mt-2">
                                    <div className="text-sm bg-destructive/10 text-destructive p-3 rounded border border-destructive/20">
                                        <strong>Warning:</strong>{__('general.restoring_a_backup_will_overwrite_your_current_workspace_data_proceed_with_caution')}</div>
                                    <input 
                                        type="file" 
                                        accept=".json,.zip"
                                        onChange={(e) => setRestoreFile(e.target.files?.[0] || null)}
                                        className="block w-full text-sm text-slate-500 file:me-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                                    />
                                    <Button 
                                        variant="destructive" 
                                        disabled={!restoreFile || isRestoring} 
                                        onClick={handleRestoreSubmit}
                                        className="w-full"
                                    >
                                        {isRestoring ? <><Loader2 className="me-2 h-4 w-4 animate-spin" />{__('general.restoring')}</> : __('general.restore_from_file')}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>

            <ConfirmModal
                isOpen={confirmRestore}
                title={__('general.confirm_data_restore')}
                description={__('general.are_you_absolutely_sure_you_want_to_restore_from_this_backup_file_this_action_will_overwrite_all_current_clients_invoices_tasks_and_data_for_this_workspace_this_action_cannot_be_undone')}
                confirmLabel={__('general.yes_wipe_and_restore')}
                variant="danger"
                onConfirm={performRestore}
                onCancel={() => setConfirmRestore(false)}
            />
        </ERPLayout>
    );
}
