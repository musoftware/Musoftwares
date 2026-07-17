import React from 'react';
import { Download, FileIcon, Paperclip } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { __ } from '@/lib/i18n';
import { formatDate } from '@/lib/utils';

export interface TabFileItem {
    id: number;
    original_name: string;
    mime?: string | null;
    human_size?: string;
    size?: number;
    created_at?: string | null;
}

export default function FilesTab({ files = [], projectId }: { files?: TabFileItem[]; projectId: number }) {
    if (files.length === 0) {
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
        <div className="space-y-3">
            {files.map((file) => (
                <Card key={file.id} className="rounded-xl border border-slate-200">
                    <CardContent className="flex items-center gap-4 p-4">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <FileIcon className="h-5 w-5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-medium text-slate-900">{file.original_name}</p>
                            <p className="text-xs text-slate-400">
                                {file.human_size ??
                                    (file.size != null ? `${(file.size / 1024).toFixed(1)} KB` : '')}
                                {file.mime ? ` · ${file.mime}` : ''}
                                {file.created_at ? ` · ${formatDate(file.created_at)}` : ''}
                            </p>
                        </div>
                        <a
                            href={route('client.projects.files.download', { project: projectId, file: file.id })}
                            className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        >
                            <Download className="h-3.5 w-3.5" /> {__('general.download')}
                        </a>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
