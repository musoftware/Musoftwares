import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Paperclip, Download, FileIcon } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';

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

export default function ProjectFiles({ project, files = [] }: Props) {
    return (
        <AuthenticatedLayout>
            <Head title={`${project.name} · ${__('general.files')}`} />
            <div className="mx-auto w-full max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <Link href={route('client.projects.show', project.id)} className="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                        <ArrowLeft className="h-4 w-4" /> {project.name}
                    </Link>
                    <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                        <Paperclip className="h-6 w-6 text-slate-400" /> {__('general.files')}
                    </h1>
                </div>

                {files.length === 0 ? (
                    <EmptyState icon={Paperclip} tone="friendly" title={__('general.no_files')} description={__('general.no_files_desc')} />
                ) : (
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
                                            {file.human_size}{file.mime ? ` · ${file.mime}` : ''}{file.created_at ? ` · ${formatDate(file.created_at)}` : ''}
                                        </p>
                                    </div>
                                    <a
                                        href={route('client.projects.files.download', { project: project.id, file: file.id })}
                                        className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        <Download className="h-3.5 w-3.5" /> {__('general.download')}
                                    </a>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
