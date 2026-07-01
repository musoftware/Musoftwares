import React, { useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Paperclip, Upload, Trash2, FileIcon } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { formatDate } from '@/lib/utils';

interface FileItem {
    id: number;
    original_name: string;
    mime: string | null;
    size: number;
    human_size: string;
    created_at: string | null;
}

interface Props {
    project: { id: number; name: string };
    files: FileItem[];
}

export default function AdminProjectFilesIndex({ project, files = [] }: Props) {
    const inputRef = useRef<HTMLInputElement | null>(null);
    const [uploading, setUploading] = useState(false);

    const onUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const data = new FormData();
        data.append('file', file);
        setUploading(true);
        router.post(route('admin.projects.files.store', { project: project.id }), data, {
            preserveScroll: true,
            onFinish: () => { setUploading(false); if (inputRef.current) inputRef.current.value = ''; },
        });
    };

    const destroy = (id: number) => {
        if (!confirm(__('general.delete_this_file'))) return;
        router.delete(route('admin.projects.files.destroy', { project: project.id, file: id }), { preserveScroll: true });
    };

    return (
        <AdminSidebarLayout title={`${project.name} · ${__('general.files')}`} header={`${project.name} — ${__('general.files')}`}>
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <Link href={route('admin.projects.index')} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                        <ArrowLeft className="h-4 w-4" /> {__('general.back_to_projects')}
                    </Link>
                    <div>
                        <input ref={inputRef} type="file" className="hidden" onChange={onUpload} />
                        <Button onClick={() => inputRef.current?.click()} disabled={uploading}>
                            <Upload className="me-1 h-4 w-4" /> {uploading ? __('general.uploading') : __('general.upload_file')}
                        </Button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    {files.length === 0 ? (
                        <div className="px-4 py-12 text-center text-slate-400">{__('general.no_files_uploaded')}</div>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {files.map((file) => (
                                <li key={file.id} className="flex items-center gap-4 px-4 py-3 hover:bg-slate-50">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                        <FileIcon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium text-slate-900">{file.original_name}</p>
                                        <p className="text-xs text-slate-400">{file.human_size}{file.created_at ? ` · ${formatDate(file.created_at)}` : ''}</p>
                                    </div>
                                    <button onClick={() => destroy(file.id)} className="rounded-md p-1.5 text-rose-500 hover:bg-rose-50" title={__('general.delete')}>
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
