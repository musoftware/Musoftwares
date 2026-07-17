import React from 'react';
import { Download, FileIcon, Paperclip } from 'lucide-react';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export interface TabFile {
    id: number;
    original_name: string;
    mime?: string | null;
    size: number;
    human_size?: string;
    created_at?: string | null;
}

interface Props {
    files: TabFile[];
    projectId: number;
}

function humanSize(bytes: number) {
    if (!bytes || bytes <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unitIndex = 0;
    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }
    return `${value.toFixed(value < 10 && unitIndex > 0 ? 1 : 0)} ${units[unitIndex]}`;
}

export function ProjectFilesTab({ files = [], projectId }: Props) {
    if (!files.length) {
        return (
            <EmptyState
                icon={Paperclip}
                tone="friendly"
                title={__('general.no_files')}
                description={__('general.no_files_desc')}
            />
        );
    }

    return (
        <ul className="space-y-3">
            {files.map((file) => (
                <li
                    key={file.id}
                    className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4"
                >
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <FileIcon className="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-medium text-slate-900">{file.original_name}</p>
                        <p className="text-xs text-slate-400">
                            {file.human_size || humanSize(file.size)}
                            {file.mime ? ` · ${file.mime}` : ''}
                            {file.created_at ? ` · ${formatDate(file.created_at)}` : ''}
                        </p>
                    </div>
                    <a
                        href={route('client.projects.files.download', { project: projectId, file: file.id })}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
                    >
                        <Download className="h-3.5 w-3.5" aria-hidden="true" /> {__('general.download')}
                    </a>
                </li>
            ))}
        </ul>
    );
}

export default ProjectFilesTab;
