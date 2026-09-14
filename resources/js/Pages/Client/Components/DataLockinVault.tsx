import React, { useState } from 'react';
import { Lock, FileCode, Package, FileText, Download, HardDrive, ShieldCheck, Check } from 'lucide-react';

export interface VaultAssetItem {
    id: number;
    title: string;
    asset_type: string;
    file_mime_type?: string;
    file_size_bytes: number;
    download_count: number;
    last_accessed_at?: string;
    created_at?: string;
}

interface DataLockinVaultProps {
    assets: VaultAssetItem[];
    vaultStats?: {
        total_assets: number;
        source_code_count: number;
        builds_count: number;
        invoices_count: number;
        total_size_bytes: number;
    };
}

export const DataLockinVault: React.FC<DataLockinVaultProps> = ({
    assets = [],
    vaultStats,
}) => {
    const [downloadingId, setDownloadingId] = useState<number | null>(null);
    const [filterType, setFilterType] = useState<string>('all');

    const formatBytes = (bytes: number): string => {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const getAssetMeta = (type: string) => {
        switch (type) {
            case 'source_code':
                return {
                    label: 'Source Code',
                    icon: FileCode,
                    color: 'text-emerald-600 dark:text-emerald-400',
                    badge: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/20',
                };
            case 'delivery_build':
                return {
                    label: 'Production Build',
                    icon: Package,
                    color: 'text-[#0071e3] dark:text-sky-400',
                    badge: 'bg-blue-50 dark:bg-sky-500/10 text-[#0071e3] dark:text-sky-300 border-blue-200 dark:border-sky-500/20',
                };
            case 'final_invoice':
            case 'contract':
                return {
                    label: 'Signed Deed / Invoice',
                    icon: FileText,
                    color: 'text-amber-600 dark:text-amber-400',
                    badge: 'bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-500/20',
                };
            default:
                return {
                    label: 'Binary Asset',
                    icon: HardDrive,
                    color: 'text-zinc-500 dark:text-zinc-400',
                    badge: 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-black/10 dark:border-white/10',
                };
        }
    };

    const filteredAssets = filterType === 'all'
        ? assets
        : assets.filter((a) => a.asset_type === filterType);

    const handleDownload = (id: number) => {
        setDownloadingId(id);
        window.location.href = `/api/portal/vault/assets/${id}/download`;
        setTimeout(() => setDownloadingId(null), 2500);
    };

    return (
        <section className="w-full rounded-2xl bg-white dark:bg-[#141416]/95 border border-black/5 dark:border-white/10 p-6 sm:p-7 shadow-sm dark:shadow-2xl backdrop-blur-2xl transition-colors duration-200">
            {/* Header with macOS Finder Vault Vibe */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-black/5 dark:border-white/5">
                <div className="flex items-center gap-3">
                    <div className="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-900 border border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-white shadow-inner">
                        <Lock className="w-4 h-4 text-zinc-700 dark:text-zinc-200" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="text-lg font-semibold text-[#1d1d1f] dark:text-white tracking-tight">
                                Sovereign Client Vault
                            </h3>
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-100 dark:bg-zinc-800 border border-black/10 dark:border-white/10 text-zinc-600 dark:text-zinc-400">
                                <ShieldCheck className="w-3 h-3 text-emerald-500 dark:text-emerald-400" />
                                Encrypted
                            </span>
                        </div>
                        <p className="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                            Authenticated repository for intellectual property, compilation artifacts, and verified legal invoices.
                        </p>
                    </div>
                </div>

                {/* Filter Tabs */}
                <div className="flex items-center gap-1 bg-[#f5f5f7] dark:bg-zinc-950/80 p-1 rounded-xl border border-black/5 dark:border-white/10 text-xs font-mono">
                    <button
                        type="button"
                        onClick={() => setFilterType('all')}
                        className={`px-3 py-1.5 rounded-lg transition-all cursor-pointer ${filterType === 'all' ? 'bg-white dark:bg-zinc-800 text-[#1d1d1f] dark:text-white shadow-sm font-semibold' : 'text-zinc-500 dark:text-zinc-400 hover:text-black dark:hover:text-white'}`}
                    >
                        All ({assets.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setFilterType('source_code')}
                        className={`px-3 py-1.5 rounded-lg transition-all cursor-pointer ${filterType === 'source_code' ? 'bg-white dark:bg-zinc-800 text-[#1d1d1f] dark:text-white shadow-sm font-semibold' : 'text-zinc-500 dark:text-zinc-400 hover:text-black dark:hover:text-white'}`}
                    >
                        Code
                    </button>
                    <button
                        type="button"
                        onClick={() => setFilterType('delivery_build')}
                        className={`px-3 py-1.5 rounded-lg transition-all cursor-pointer ${filterType === 'delivery_build' ? 'bg-white dark:bg-zinc-800 text-[#1d1d1f] dark:text-white shadow-sm font-semibold' : 'text-zinc-500 dark:text-zinc-400 hover:text-black dark:hover:text-white'}`}
                    >
                        Builds
                    </button>
                </div>
            </div>

            {/* Assets Table */}
            <div className="mt-4 overflow-x-auto">
                <table className="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr className="border-b border-black/5 dark:border-white/5 text-zinc-500 dark:text-zinc-400 font-mono">
                            <th className="py-3 px-3">Asset Designation</th>
                            <th className="py-3 px-3">Classification</th>
                            <th className="py-3 px-3">Byte Size</th>
                            <th className="py-3 px-3">Integrity & Logs</th>
                            <th className="py-3 px-3 text-right">Access</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-black/5 dark:divide-white/5 font-sans">
                        {filteredAssets.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="py-10 text-center text-zinc-400 dark:text-zinc-500 font-mono text-xs">
                                    No confidential assets cataloged in this classification.
                                </td>
                            </tr>
                        ) : (
                            filteredAssets.map((asset) => {
                                const meta = getAssetMeta(asset.asset_type);
                                const IconComp = meta.icon;

                                return (
                                    <tr key={asset.id} className="hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors group">
                                        <td className="py-3.5 px-3 font-medium text-[#1d1d1f] dark:text-zinc-100">
                                            <div className="flex items-center gap-2.5">
                                                <div className="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-black/5 dark:border-white/5">
                                                    <IconComp className={`w-4 h-4 ${meta.color}`} />
                                                </div>
                                                <span className="group-hover:text-[#0071e3] dark:group-hover:text-white transition-colors">{asset.title}</span>
                                            </div>
                                        </td>
                                        <td className="py-3.5 px-3">
                                            <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-mono tracking-wider uppercase border ${meta.badge}`}>
                                                {meta.label}
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-3 font-mono text-zinc-600 dark:text-zinc-400 tabular-nums">
                                            {formatBytes(asset.file_size_bytes)}
                                        </td>
                                        <td className="py-3.5 px-3 font-mono text-[11px] text-zinc-500 dark:text-zinc-400 tabular-nums">
                                            <span>{asset.download_count} authenticated pulls</span>
                                        </td>
                                        <td className="py-3.5 px-3 text-right">
                                            <button
                                                type="button"
                                                disabled={downloadingId === asset.id}
                                                onClick={() => handleDownload(asset.id)}
                                                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-[#1d1d1f] dark:text-white border border-black/10 dark:border-white/10 transition-all active:scale-95 disabled:opacity-40 cursor-pointer"
                                            >
                                                <Download className="w-3.5 h-3.5" />
                                                <span>{downloadingId === asset.id ? 'Decrypting...' : 'Acquire'}</span>
                                            </button>
                                        </td>
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>
        </section>
    );
};
