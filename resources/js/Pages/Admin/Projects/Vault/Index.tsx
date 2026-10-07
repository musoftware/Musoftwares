import React, { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    ArrowLeft, Lock, ShieldCheck, Upload, Trash2,
    Download, FileCode, Package, FileText, HardDrive,
    Plus, X, Clock
} from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';

interface VaultAssetItem {
    id: number;
    title: string;
    asset_type: string;
    file_mime_type?: string;
    file_size_bytes: number;
    human_size: string;
    download_count: number;
    last_accessed_at: string | null;
    created_at: string | null;
}

interface Props {
    project: {
        id: number;
        name: string;
        client_name: string;
        client_id?: number;
    };
    assets: VaultAssetItem[];
}

export default function AdminProjectVaultIndex({ project, assets = [] }: Props) {
    const [isUploadOpen, setIsUploadOpen] = useState(false);
    const [title, setTitle] = useState('');
    const [assetType, setAssetType] = useState<'source_code' | 'delivery_build' | 'contract' | 'final_invoice'>('source_code');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const { confirm, confirmDialog } = useConfirm();

    const getAssetMeta = (type: string) => {
        switch (type) {
            case 'source_code':
                return {
                    label: __('admin.vault_type_source_code'),
                    icon: FileCode,
                    color: 'text-emerald-600 dark:text-emerald-400',
                    badge: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/20',
                };
            case 'delivery_build':
                return {
                    label: __('admin.vault_type_delivery_build'),
                    icon: Package,
                    color: 'text-[#0071e3] dark:text-sky-400',
                    badge: 'bg-blue-50 dark:bg-sky-500/10 text-[#0071e3] dark:text-sky-300 border-blue-200 dark:border-sky-500/20',
                };
            case 'final_invoice':
            case 'contract':
                return {
                    label: type === 'contract' ? __('admin.vault_type_contract') : __('admin.vault_type_final_invoice'),
                    icon: FileText,
                    color: 'text-amber-600 dark:text-amber-400',
                    badge: 'bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-500/20',
                };
            default:
                return {
                    label: __('admin.vault_type_other'),
                    icon: HardDrive,
                    color: 'text-zinc-500 dark:text-zinc-400',
                    badge: 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-black/10 dark:border-white/10',
                };
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            setSelectedFile(file);
            if (!title) {
                // Auto-fill title from filename without extension
                const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");
                setTitle(nameWithoutExt);
            }
        }
    };

    const handleUploadSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});

        if (!title.trim()) {
            setErrors({ title: __('admin.vault_title_required') });
            return;
        }

        if (!selectedFile) {
            setErrors({ file: __('admin.vault_file_required') });
            return;
        }

        const formData = new FormData();
        formData.append('title', title.trim());
        formData.append('asset_type', assetType);
        formData.append('file', selectedFile);

        setUploading(true);
        router.post(route('admin.projects.vault.store', { project: project.id }), formData, {
            preserveScroll: true,
            onSuccess: () => {
                setIsUploadOpen(false);
                setTitle('');
                setSelectedFile(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
            onError: (errs) => {
                setErrors(errs as Record<string, string>);
            },
            onFinish: () => {
                setUploading(false);
            },
        });
    };

    const handleDelete = async (id: number, assetTitle: string) => {
        const accepted = await confirm({
            title: __('admin.vault_delete_title'),
            description: __('admin.vault_delete_confirm', { title: assetTitle }),
            variant: 'danger',
            confirmLabel: __('general.delete'),
        });
        if (!accepted) return;

        router.delete(route('admin.projects.vault.destroy', { project: project.id, asset: id }), {
            preserveScroll: true,
        });
    };

    return (
        <AdminSidebarLayout
            title={__('admin.vault_page_title', { project: project.name })}
            header={__('admin.vault_page_title', { project: project.name })}
        >
            <div className="space-y-6 p-6 max-w-7xl mx-auto">
                {/* Top Bar Navigation & Actions */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <Link
                            href={route('admin.projects.index')}
                            className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors"
                        >
                            <ArrowLeft className="h-3.5 w-3.5 rtl:rotate-180" /> {__('general.back_to_projects')}
                        </Link>
                        <div className="flex items-center gap-2 mt-1">
                            <h2 className="text-xl font-bold tracking-tight text-slate-900">
                                {__('admin.vault_heading')}
                            </h2>
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <ShieldCheck className="w-3 h-3 text-emerald-600" />
                                {__('admin.vault_encrypted_storage')}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            {__('admin.vault_client_label')} <span className="font-semibold text-slate-700">{project.client_name}</span> · {__('admin.vault_portal_hint')}
                        </p>
                    </div>

                    <Button
                        onClick={() => setIsUploadOpen(true)}
                        className="bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold px-4 py-2 rounded-lg transition-all flex items-center gap-1.5 shadow-sm"
                    >
                        <Plus className="h-4 w-4" />
                        <span>{__('admin.vault_upload_deliverable')}</span>
                    </Button>
                </div>

                {/* Vault Deliverables Table Card */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {assets.length === 0 ? (
                        <div className="py-16 px-6 text-center space-y-3">
                            <div className="mx-auto w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                <Lock className="w-6 h-6" />
                            </div>
                            <h3 className="text-base font-semibold text-slate-800">
                                {__('admin.vault_empty_title')}
                            </h3>
                            <p className="text-xs text-slate-500 max-w-md mx-auto">
                                {__('admin.vault_empty_description')}
                            </p>
                            <Button
                                onClick={() => setIsUploadOpen(true)}
                                variant="outline"
                                className="text-xs mt-2"
                            >
                                <Upload className="w-3.5 h-3.5 me-1.5" /> {__('admin.vault_upload_first')}
                            </Button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-start text-xs border-collapse">
                                <thead>
                                    <tr className="border-b border-slate-100 bg-slate-50/60 text-slate-500 font-mono">
                                        <th className="py-3 px-4">{__('admin.vault_col_asset')}</th>
                                        <th className="py-3 px-4">{__('admin.vault_col_classification')}</th>
                                        <th className="py-3 px-4">{__('admin.vault_col_size')}</th>
                                        <th className="py-3 px-4">{__('admin.vault_col_downloads')}</th>
                                        <th className="py-3 px-4">{__('admin.vault_col_last_accessed')}</th>
                                        <th className="py-3 px-4 text-end">{__('general.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 font-mono">
                                    {assets.map((asset) => {
                                        const meta = getAssetMeta(asset.asset_type);
                                        const Icon = meta.icon;

                                        return (
                                            <tr key={asset.id} className="hover:bg-slate-50/80 transition-colors">
                                                <td className="py-3.5 px-4 font-sans font-medium text-slate-900">
                                                    <div className="flex items-center gap-3">
                                                        <div className={`p-2 rounded-lg bg-slate-100 ${meta.color}`}>
                                                            <Icon className="w-4 h-4" />
                                                        </div>
                                                        <div className="min-w-0">
                                                            <div className="font-semibold text-slate-900 truncate">
                                                                {asset.title}
                                                            </div>
                                                            <div className="text-[11px] text-slate-400 font-mono">
                                                                {__('admin.vault_uploaded_at', { date: asset.created_at ?? '' })}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-3.5 px-4">
                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-medium border ${meta.badge}`}>
                                                        {meta.label}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-slate-600">
                                                    {asset.human_size}
                                                </td>
                                                <td className="py-3.5 px-4">
                                                    <span className="inline-flex items-center gap-1 font-semibold text-slate-800">
                                                        <Download className="w-3 h-3 text-slate-400" />
                                                        {__('admin.vault_download_count', { count: asset.download_count })}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-slate-500">
                                                    {asset.last_accessed_at ? (
                                                        <span className="flex items-center gap-1">
                                                            <Clock className="w-3 h-3 text-slate-400" />
                                                            {asset.last_accessed_at}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400 italic">{__('admin.vault_not_downloaded')}</span>
                                                    )}
                                                </td>
                                                <td className="py-3.5 px-4 text-end">
                                                    <div className="flex items-center justify-end gap-1.5 font-sans">
                                                        <a
                                                            href={route('admin.projects.vault.download', { project: project.id, asset: asset.id })}
                                                            className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-slate-700 bg-white hover:bg-slate-100 rounded-md border border-slate-200 transition-colors shadow-xs"
                                                            title={__('admin.vault_download_file')}
                                                        >
                                                            <Download className="w-3.5 h-3.5" />
                                                            <span>{__('admin.vault_download')}</span>
                                                        </a>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDelete(asset.id, asset.title)}
                                                            className="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-md transition-colors cursor-pointer"
                                                            title={__('admin.vault_delete_title')}
                                                            aria-label={__('admin.vault_delete_title')}
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Upload Deliverable Modal Dialog */}
            <Dialog open={isUploadOpen} onOpenChange={setIsUploadOpen}>
                <DialogContent className="sm:max-w-lg bg-white p-6 rounded-2xl shadow-xl border border-slate-200">
                    <DialogHeader className="space-y-1">
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-lg bg-blue-50 text-[#0071e3]">
                                <Lock className="w-4 h-4" />
                            </div>
                            <DialogTitle className="text-lg font-bold text-slate-900">
                                {__('admin.vault_upload_title')}
                            </DialogTitle>
                        </div>
                        <DialogDescription className="text-xs text-slate-500">
                            {__('admin.vault_upload_description', { client: project.client_name })}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleUploadSubmit} className="space-y-4 pt-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="title" className="text-xs font-semibold text-slate-700">
                                {__('admin.vault_title_label')} *
                            </Label>
                            <Input
                                id="title"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder={__('admin.vault_title_placeholder')}
                                className="text-xs"
                                disabled={uploading}
                            />
                            {errors.title && <p className="text-xs text-rose-500">{errors.title}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="assetType" className="text-xs font-semibold text-slate-700">
                                {__('admin.vault_category_label')} *
                            </Label>
                            <select
                                id="assetType"
                                value={assetType}
                                onChange={(e: any) => setAssetType(e.target.value)}
                                className="w-full text-xs rounded-md border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-sans"
                                disabled={uploading}
                            >
                                <option value="source_code">{__('admin.vault_option_source_code')}</option>
                                <option value="delivery_build">{__('admin.vault_option_delivery_build')}</option>
                                <option value="contract">{__('admin.vault_option_contract')}</option>
                                <option value="final_invoice">{__('admin.vault_option_final_invoice')}</option>
                            </select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="file" className="text-xs font-semibold text-slate-700">
                                {__('admin.vault_file_label')}
                            </Label>
                            <div className="border-2 border-dashed border-slate-200 hover:border-slate-300 rounded-xl p-4 text-center bg-slate-50/50 transition-colors">
                                <input
                                    ref={fileInputRef}
                                    id="file"
                                    type="file"
                                    onChange={handleFileSelect}
                                    className="hidden"
                                    disabled={uploading}
                                />
                                {selectedFile ? (
                                    <div className="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200 text-xs">
                                        <div className="flex items-center gap-2 truncate">
                                            <Package className="w-4 h-4 text-blue-500 shrink-0" />
                                            <span className="font-medium text-slate-800 truncate">{selectedFile.name}</span>
                                            <span className="text-slate-400 font-mono">({(selectedFile.size / (1024 * 1024)).toFixed(2)} MB)</span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSelectedFile(null);
                                                if (fileInputRef.current) fileInputRef.current.value = '';
                                            }}
                                            className="text-slate-400 hover:text-rose-500 p-1"
                                            aria-label={__('admin.vault_remove_file')}
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="w-full flex flex-col items-center justify-center gap-2 cursor-pointer py-2"
                                    >
                                        <Upload className="w-6 h-6 text-slate-400" />
                                        <span className="text-xs font-medium text-slate-600">
                                            {__('admin.vault_browse_file')}
                                        </span>
                                        <span className="text-[11px] text-slate-400 font-mono">
                                            {__('admin.vault_supported_formats')}
                                        </span>
                                    </button>
                                )}
                            </div>
                            {errors.file && <p className="text-xs text-rose-500">{errors.file}</p>}
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsUploadOpen(false)}
                                disabled={uploading}
                                className="text-xs"
                            >
                                {__('general.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                disabled={uploading}
                                className="bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold"
                            >
                                {uploading ? (
                                    <span>{__('admin.vault_uploading')}</span>
                                ) : (
                                    <span>{__('admin.vault_store')}</span>
                                )}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
            {confirmDialog}
        </AdminSidebarLayout>
    );
}
