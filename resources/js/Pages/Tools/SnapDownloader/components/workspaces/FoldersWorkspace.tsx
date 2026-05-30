import React from 'react';
import { FolderOpen, ChevronRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { SavedFolder } from '../../types/snapdownloader.types';
import { EmptyState } from '../shared/EmptyState';
import { formatBytes } from '../../utils/formatters';

export function FoldersWorkspace({
    folders,
    callRPC
}: {
    folders: SavedFolder[];
    callRPC: (action: string, data?: any) => Promise<any>;
}) {
    const handleOpenFolder = async (subdir?: string) => {
        try { await callRPC('open_folder', { subdir }); }
        catch (err: any) { alert(err.message); }
    };

    return (
        <div className="space-y-5 max-w-3xl mx-auto">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-xl sm:text-2xl font-black text-white">{__('Saved Files')}</h1>
                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Saved media, organised by profile')}</p>
                </div>
                <Button variant="ghost" onClick={() => handleOpenFolder()} className="gap-1.5 h-11 text-xs font-bold" style={{ background: 'rgba(245,158,11,0.1)', color: '#f59e0b' }}>
                    <FolderOpen className="w-3.5 h-3.5" /> {__('Open All')}
                </Button>
            </div>

            {folders.length === 0 ? (
                <EmptyState
                    icon={<FolderOpen className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                    title={__('No saved files yet')}
                    sub={__('Files will appear here after your first download')}
                />
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    {folders.map(folder => (
                        <Button
                            variant="ghost"
                            key={folder.path}
                            onClick={() => handleOpenFolder(folder.name)}
                            className="p-4 h-auto justify-start text-left transition-all hover:border-amber-500/30 active:scale-95 border"
                            style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}
                        >
                            <div className="flex items-start gap-3 w-full">
                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'rgba(245,158,11,0.1)' }}>
                                    <FolderOpen className="w-4.5 h-4.5" style={{ color: '#f59e0b' }} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="font-bold text-white text-sm truncate">{folder.name}</div>
                                    <div className="text-[11px] mt-0.5 font-normal" style={{ color: 'rgba(255,255,255,0.35)' }}>
                                        {folder.fileCount} {folder.fileCount !== 1 ? __('files') : __('file')} · {formatBytes(folder.totalSize)}
                                    </div>
                                </div>
                                <ChevronRight className="w-4 h-4 shrink-0 mt-1" style={{ color: 'rgba(255,255,255,0.2)' }} />
                            </div>
                        </Button>
                    ))}
                </div>
            )}
        </div>
    );
}
