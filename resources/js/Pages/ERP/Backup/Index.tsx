import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { 
    HardDrive, Download, UploadCloud, AlertTriangle, Lock, ArrowRight, Loader2
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { useToast } from '@/Components/ui/use-toast';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';

export default function BackupIndex({ hasBackupFeature }: { hasBackupFeature: boolean }) {
    const { auth } = usePage().props as any;
    const { toast } = useToast();
    
    const [isRestoring, setIsRestoring] = useState(false);
    const [restoreFile, setRestoreFile] = useState<File | null>(null);
    const [confirmRestore, setConfirmRestore] = useState(false);

    const { menuItems, lockedAddons } = useERPMenu('backup');

    const handleUpgradeSimulate = () => {
        router.visit(route('subscriptions.plans', { module: 'erp' }));
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
                toast({ description: 'Backup restored successfully. Your workspace data has been updated.' });
                setRestoreFile(null);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string || 'Failed to restore backup.' });
            },
            onFinish: () => setIsRestoring(false)
        });
    };

    return (
        <ERPLayout title="Backup" menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title="ERP Backup Service" />
            
            <div className="max-w-4xl mx-auto space-y-6">
                <div>
                    <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">Data Backup & Restore</h2>
                    <p className="text-sm text-slate-500 mt-1">Download a complete snapshot of your workspace or restore from a previous backup.</p>
                </div>

                {!hasBackupFeature ? (
                    <Card className="border-primary/20 bg-muted/10 shadow-none overflow-hidden relative mt-6">
                        <div className="absolute top-0 right-0 translate-x-12 -translate-y-12 opacity-5 pointer-events-none">
                            <HardDrive className="h-64 w-64 text-primary" />
                        </div>
                        <CardContent className="p-8 md:p-10 relative z-10 space-y-6">
                            <div className="flex items-center gap-2">
                                <Lock className="h-5 w-5 text-primary" />
                                <span className="font-semibold text-primary">Premium Feature</span>
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-foreground leading-tight">
                                    Secure Your Operational Data with One-Click Backups
                                </h1>
                                <p className="text-muted-foreground leading-relaxed max-w-2xl">
                                    Upgrade your workspace to unlock the ERP Backup Service. Download full snapshots of your clients, invoices, tasks, and ledgers in JSON format, and instantly restore them if needed. 
                                </p>
                            </div>
                            <div className="pt-2">
                                <Button 
                                    onClick={handleUpgradeSimulate}
                                    className="shadow-none flex items-center gap-2 group h-11 px-8 transition-all"
                                >
                                    Unlock Backup Addon for 500 EGP/Yr
                                    <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        {/* Download Backup */}
                        <Card className="shadow-none border-border">
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Download className="h-5 w-5 text-primary" /> Create Backup
                                </CardTitle>
                                <CardDescription>
                                    Download a JSON file containing all your clients, invoices, tasks, and configurations.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="pt-4 border-t border-border mt-2">
                                    <a 
                                        href={route('erp.backup.download')} 
                                        className="flex items-center justify-center w-full h-10 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                                    >
                                        <Download className="mr-2 h-4 w-4" /> Download Backup File
                                    </a>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Restore Backup */}
                        <Card className="shadow-none border-border border-destructive/20 relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10">
                                <AlertTriangle className="h-24 w-24 text-destructive" />
                            </div>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2 text-destructive">
                                    <UploadCloud className="h-5 w-5" /> Restore Backup
                                </CardTitle>
                                <CardDescription>
                                    Upload a previously downloaded JSON backup file to restore your workspace.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="relative z-10">
                                <div className="space-y-4 pt-4 border-t border-border mt-2">
                                    <div className="text-sm bg-destructive/10 text-destructive p-3 rounded border border-destructive/20">
                                        <strong>Warning:</strong> Restoring a backup will overwrite your current workspace data. Proceed with caution.
                                    </div>
                                    <input 
                                        type="file" 
                                        accept=".json,.zip"
                                        onChange={(e) => setRestoreFile(e.target.files?.[0] || null)}
                                        className="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                                    />
                                    <Button 
                                        variant="destructive" 
                                        disabled={!restoreFile || isRestoring} 
                                        onClick={handleRestoreSubmit}
                                        className="w-full"
                                    >
                                        {isRestoring ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Restoring...</> : 'Restore from File'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>

            <ConfirmModal
                isOpen={confirmRestore}
                title="Confirm Data Restore"
                description="Are you absolutely sure you want to restore from this backup file? This action will overwrite all current clients, invoices, tasks, and data for this workspace. This action cannot be undone."
                confirmLabel="Yes, Wipe and Restore"
                variant="danger"
                onConfirm={performRestore}
                onCancel={() => setConfirmRestore(false)}
            />
        </ERPLayout>
    );
}
