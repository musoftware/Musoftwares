import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Paperclip, Download, FileIcon } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
            <Head title={`${project.name} · ${__('general.files')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto space-y-1.5">
                        <Link
                            href={route('client.projects.show', project.id)}
                            className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                        >
                            <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                            {project.name}
                        </Link>
                        <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                            {__('general.files')}
                        </h1>
                        <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                            Attached specifications, documents, and media for this project workspace.
                        </p>
                    </div>
                </div>

                {/* Main List */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8">
                    {files.length === 0 ? (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm max-w-xl mx-auto">
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] mx-auto mb-4">
                                <Paperclip className="w-7 h-7" />
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                {__('general.no_files')}
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 max-w-md mx-auto mt-1.5 leading-relaxed">
                                {__('general.no_files_desc')}
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl">
                            {files.map((file) => (
                                <div
                                    key={file.id}
                                    className="bg-white border border-black/5 rounded-[20px] p-4 shadow-sm flex items-center gap-4 hover:border-[#0071e3]/30 hover:shadow-md transition-all"
                                >
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#f5f5f7] border border-black/5 text-[#0071e3]">
                                        <FileIcon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-bold text-xs sm:text-sm text-[#1d1d1f]">{file.original_name}</p>
                                        <p className="text-[11px] text-[#1d1d1f]/50 mt-0.5">
                                            {file.human_size}{file.mime ? ` · ${file.mime}` : ''}{file.created_at ? ` · ${formatDate(file.created_at)}` : ''}
                                        </p>
                                    </div>
                                    <a
                                        href={route('client.projects.files.download', { project: project.id, file: file.id })}
                                        className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#f5f5f7] hover:bg-[#0071e3] hover:text-white border border-black/5 px-3 py-1.5 text-xs font-semibold text-[#1d1d1f] transition-all cursor-pointer"
                                    >
                                        <Download className="h-3.5 w-3.5" />
                                        <span>{__('general.download')}</span>
                                    </a>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
